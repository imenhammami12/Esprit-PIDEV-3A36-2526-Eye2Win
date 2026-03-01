<?php

namespace App\Controller\Admin;

use App\Entity\Complaint;
use App\Entity\ComplaintStatus;
use App\Entity\ComplaintPriority;
use App\Entity\User;
use App\Entity\AuditLog;
use App\Repository\ComplaintRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\NotificationService;

#[Route('/admin/complaints')]
#[IsGranted('ROLE_ADMIN')]
class AdminComplaintController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    // -------------------------------------------------------------------------
    // LIST — avec HTTP Cache headers
    // -------------------------------------------------------------------------

    #[Route('/', name: 'admin_complaints_index')]
    public function index(
        Request             $request,
        ComplaintRepository $complaintRepository,
        PaginatorInterface  $paginator
    ): Response {
        $search         = $request->query->get('search', '');
        $statusFilter   = $request->query->get('status', '');
        $priorityFilter = $request->query->get('priority', '');
        $categoryFilter = $request->query->get('category', '');

        $queryBuilder = $complaintRepository->createQueryBuilder('c')
            ->leftJoin('c.submittedBy', 'u')
            ->leftJoin('c.assignedTo', 'a')
            ->addSelect('u', 'a')
            ->orderBy('c.priority', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC');

        if ($search) {
            $queryBuilder
                ->andWhere('c.subject LIKE :search OR c.description LIKE :search OR u.username LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statusFilter) {
            $queryBuilder
                ->andWhere('c.status = :status')
                ->setParameter('status', $statusFilter);
        }

        if ($priorityFilter) {
            $queryBuilder
                ->andWhere('c.priority = :priority')
                ->setParameter('priority', $priorityFilter);
        }

        if ($categoryFilter) {
            $queryBuilder
                ->andWhere('c.category = :category')
                ->setParameter('category', $categoryFilter);
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        // ── Statistics depuis Redis (déjà cachées 5 min) ──────────────────────
        $stats = $complaintRepository->getStatistics();

        // ── Construire la réponse ─────────────────────────────────────────────
        $response = $this->render('admin/complaints/index.html.twig', [
            'pagination'     => $pagination,
            'search'         => $search,
            'statusFilter'   => $statusFilter,
            'priorityFilter' => $priorityFilter,
            'categoryFilter' => $categoryFilter,
            'stats'          => $stats,
        ]);

        // ── HTTP Cache headers ────────────────────────────────────────────────
        // Les pages admin sont privées (ne pas mettre en cache sur les proxies publics)
        // On utilise uniquement le cache navigateur (private) + ETag pour revalidation

        // ETag basé sur les filtres actifs + total des complaints
        // Si rien ne change, le navigateur reuse sa version en cache
        $etagData = $search . $statusFilter . $priorityFilter . $categoryFilter
            . $request->query->getInt('page', 1)
            . ($stats['total'] ?? 0)
            . ($stats['pending'] ?? 0);

        $response->setEtag(md5($etagData));
        $response->setPrivate();                  // jamais de cache proxy public
        $response->setMaxAge(60);                 // cache navigateur : 60 secondes
        $response->headers->addCacheControlDirective('must-revalidate');

        // Si le navigateur envoie If-None-Match et que l'ETag correspond → 304 Not Modified
        // Zéro contenu retourné, zéro rendu Twig, zéro SQL
        if ($response->isNotModified($request)) {
            return $response; // 304 — réponse vide, très rapide
        }

        return $response;
    }

    // -------------------------------------------------------------------------
    // SHOW — avec HTTP Cache headers
    // -------------------------------------------------------------------------

    #[Route('/{id}', name: 'admin_complaints_show', requirements: ['id' => '\d+'])]
    public function show(Complaint $complaint, UserRepository $userRepository): Response
    {
        $admins = [];

        try {
            $admins = $userRepository->findAdmins();
        } catch (\Exception $e) {
            try {
                $allUsers = $userRepository->findAll();
                foreach ($allUsers as $user) {
                    if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                        $admins[] = $user;
                    }
                }
                usort($admins, fn($a, $b) => strcmp($a->getUsername(), $b->getUsername()));
            } catch (\Exception $e2) {
                $admins = [];
            }
        }

        if (empty($admins)) {
            $this->addFlash('warning', 'No administrators found in the system. Please check user roles.');
        }

        $response = $this->render('admin/complaints/show.html.twig', [
            'complaint' => $complaint,
            'admins'    => $admins,
        ]);

        // ── HTTP Cache headers ────────────────────────────────────────────────
        // ETag basé sur l'ID + statut + updatedAt du complaint
        // Si le complaint n'a pas changé → 304 Not Modified
        $updatedAt = $complaint->getUpdatedAt()?->getTimestamp() ?? $complaint->getCreatedAt()->getTimestamp();

        $response->setEtag(md5($complaint->getId() . $complaint->getStatus()->value . $updatedAt));
        $response->setPrivate();
        $response->setMaxAge(30);  // 30 secondes — plus court car les détails changent plus souvent
        $response->headers->addCacheControlDirective('must-revalidate');

        if ($response->isNotModified($request ?? new Request())) {
            return $response;
        }

        return $response;
    }

    // -------------------------------------------------------------------------
    // ASSIGN
    // -------------------------------------------------------------------------

    #[Route('/{id}/assign', name: 'admin_complaints_assign', methods: ['POST'])]
    public function assign(
        Request                $request,
        Complaint              $complaint,
        UserRepository         $userRepository,
        EntityManagerInterface $em,
        ComplaintRepository    $complaintRepository
    ): Response {
        if (!$this->isCsrfTokenValid('assign-complaint-' . $complaint->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $adminId = $request->request->get('admin_id');

        if ($adminId === 'unassign') {
            $previousAdmin = $complaint->getAssignedTo();
            $complaint->setAssignedTo(null);

            $this->createAuditLog(
                $em, 'COMPLAINT_UNASSIGNED', 'Complaint', $complaint->getId(),
                "Complaint #{$complaint->getId()} unassigned from " . ($previousAdmin?->getUsername() ?? 'unknown')
            );

            $em->flush();
            $complaintRepository->invalidateStatisticsCache(); // invalider Redis
            $this->addFlash('success', 'Complaint unassigned successfully');

        } else {
            $admin = $userRepository->find($adminId);

            if (!$admin || !in_array('ROLE_ADMIN', $admin->getRoles())) {
                $this->addFlash('error', 'Invalid administrator selected');
                return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
            }

            $complaint->setAssignedTo($admin);

            if ($complaint->getStatus() === ComplaintStatus::PENDING) {
                $complaint->setStatus(ComplaintStatus::IN_PROGRESS);
            }

            $this->createAuditLog(
                $em, 'COMPLAINT_ASSIGNED', 'Complaint', $complaint->getId(),
                "Complaint #{$complaint->getId()} assigned to {$admin->getUsername()}"
            );

            $em->flush();
            $complaintRepository->invalidateStatisticsCache(); // invalider Redis
            $this->notificationService->notifyComplaintAssigned($complaint, $admin);
            $this->addFlash('success', "Complaint assigned to {$admin->getUsername()} successfully");
        }

        return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
    }

    // -------------------------------------------------------------------------
    // UPDATE STATUS
    // -------------------------------------------------------------------------

    #[Route('/{id}/update-status', name: 'admin_complaints_update_status', methods: ['POST'])]
    public function updateStatus(
        Request                $request,
        Complaint              $complaint,
        EntityManagerInterface $em,
        ComplaintRepository    $complaintRepository
    ): Response {
        if (!$this->isCsrfTokenValid('update-status-' . $complaint->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $newStatus = ComplaintStatus::from($request->request->get('status'));
        $oldStatus = $complaint->getStatus();

        $complaint->setStatus($newStatus);

        $this->createAuditLog(
            $em, 'COMPLAINT_STATUS_CHANGED', 'Complaint', $complaint->getId(),
            "Status changed from {$oldStatus->value} to {$newStatus->value}"
        );

        $em->flush();
        $complaintRepository->invalidateStatisticsCache(); // invalider Redis

        $this->notificationService->notifyComplaintStatusChanged(
            $complaint, $oldStatus->getLabel(), $newStatus->getLabel()
        );

        $this->addFlash('success', "Complaint status updated to {$newStatus->getLabel()}");
        return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
    }

    // -------------------------------------------------------------------------
    // UPDATE PRIORITY
    // -------------------------------------------------------------------------

    #[Route('/{id}/update-priority', name: 'admin_complaints_update_priority', methods: ['POST'])]
    public function updatePriority(
        Request                $request,
        Complaint              $complaint,
        EntityManagerInterface $em,
        ComplaintRepository    $complaintRepository
    ): Response {
        if (!$this->isCsrfTokenValid('update-priority-' . $complaint->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $newPriority = ComplaintPriority::from($request->request->get('priority'));
        $oldPriority = $complaint->getPriority();

        $complaint->setPriority($newPriority);

        $this->createAuditLog(
            $em, 'COMPLAINT_PRIORITY_CHANGED', 'Complaint', $complaint->getId(),
            "Priority changed from {$oldPriority->value} to {$newPriority->value}"
        );

        $em->flush();
        $complaintRepository->invalidateStatisticsCache(); // invalider Redis

        $this->notificationService->notifyComplaintPriorityChanged(
            $complaint, $oldPriority->getLabel(), $newPriority->getLabel()
        );

        $this->addFlash('success', "Complaint priority updated to {$newPriority->getLabel()}");
        return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
    }

    // -------------------------------------------------------------------------
    // RESPOND
    // -------------------------------------------------------------------------

    #[Route('/{id}/respond', name: 'admin_complaints_respond', methods: ['POST'])]
    public function respond(
        Request                $request,
        Complaint              $complaint,
        EntityManagerInterface $em,
        ComplaintRepository    $complaintRepository
    ): Response {
        if (!$this->isCsrfTokenValid('respond-' . $complaint->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $response = $request->request->get('response');

        if (empty(trim($response))) {
            $this->addFlash('error', 'Response cannot be empty');
            return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
        }

        $complaint->setAdminResponse($response);

        if (!$complaint->getAssignedTo()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $complaint->setAssignedTo($user);
        }

        if ($complaint->getStatus() === ComplaintStatus::PENDING) {
            $complaint->setStatus(ComplaintStatus::IN_PROGRESS);
        }

        $this->createAuditLog(
            $em, 'COMPLAINT_RESPONDED', 'Complaint', $complaint->getId(),
            "Admin response added to complaint #{$complaint->getId()}"
        );

        $em->flush();
        $complaintRepository->invalidateStatisticsCache(); // invalider Redis

        $this->notificationService->notifyComplaintResponded($complaint);
        $this->addFlash('success', 'Response submitted successfully');

        return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
    }

    // -------------------------------------------------------------------------
    // RESOLVE
    // -------------------------------------------------------------------------

    #[Route('/{id}/resolve', name: 'admin_complaints_resolve', methods: ['POST'])]
    public function resolve(
        Request                $request,
        Complaint              $complaint,
        EntityManagerInterface $em,
        ComplaintRepository    $complaintRepository
    ): Response {
        if (!$this->isCsrfTokenValid('resolve-' . $complaint->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $resolutionNotes = $request->request->get('resolution_notes');

        if (empty(trim($resolutionNotes))) {
            $this->addFlash('error', 'Resolution notes are required');
            return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
        }

        $complaint->setResolutionNotes($resolutionNotes);
        $complaint->setStatus(ComplaintStatus::RESOLVED);

        $this->createAuditLog(
            $em, 'COMPLAINT_RESOLVED', 'Complaint', $complaint->getId(),
            "Complaint #{$complaint->getId()} marked as resolved"
        );

        $em->flush();
        $complaintRepository->invalidateStatisticsCache(); // invalider Redis

        $this->notificationService->notifyComplaintResolved($complaint);
        $this->addFlash('success', 'Complaint resolved successfully');

        return $this->redirectToRoute('admin_complaints_show', ['id' => $complaint->getId()]);
    }

    // -------------------------------------------------------------------------
    // DELETE
    // -------------------------------------------------------------------------

    #[Route('/{id}/delete', name: 'admin_complaints_delete', methods: ['POST'])]
    public function delete(
        Request                $request,
        Complaint              $complaint,
        EntityManagerInterface $em,
        ComplaintRepository    $complaintRepository
    ): Response {
        if (!$this->isCsrfTokenValid('delete-' . $complaint->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $complaintId = $complaint->getId();

        $this->createAuditLog(
            $em, 'COMPLAINT_DELETED', 'Complaint', $complaintId,
            "Complaint #{$complaintId} deleted by administrator"
        );

        $em->remove($complaint);
        $em->flush();
        $complaintRepository->invalidateStatisticsCache(); // invalider Redis

        $this->addFlash('success', 'Complaint deleted successfully');
        return $this->redirectToRoute('admin_complaints_index');
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function createAuditLog(
        EntityManagerInterface $em,
        string  $action,
        string  $entityType,
        ?int    $entityId,
        string  $details
    ): void {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $auditLog = new AuditLog();
        $auditLog->setUser($currentUser);
        $auditLog->setAction($action);
        $auditLog->setEntityType($entityType);
        $auditLog->setEntityId($entityId);
        $auditLog->setDetails($details);
        $auditLog->setIpAddress($_SERVER['REMOTE_ADDR'] ?? null);

        $em->persist($auditLog);
    }
}