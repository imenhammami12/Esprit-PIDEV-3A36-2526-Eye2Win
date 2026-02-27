<?php

namespace App\Controller\Admin;

use App\Entity\CoachApplication;
use App\Entity\ApplicationStatus;
use App\Entity\AuditLog;
use App\Repository\CoachApplicationRepository;
use App\Service\CoachApplicationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\NotificationService;

#[Route('/admin/coach-applications')]
#[IsGranted('ROLE_ADMIN')]
class AdminCoachApplicationController extends AbstractController
{
    public function __construct(
        private NotificationService          $notificationService,
        private CoachApplicationEmailService $emailService
    ) {}

    // ──────────────────────────────────────────────────────────────
    //  INDEX
    // ──────────────────────────────────────────────────────────────
    #[Route('/', name: 'admin_coach_applications_index')]
    public function index(
        Request                    $request,
        CoachApplicationRepository $repository,
        PaginatorInterface         $paginator
    ): Response {
        $statusFilter = $request->query->get('status', '');
        $search       = $request->query->get('search', '');
        $dateFrom     = $request->query->get('date_from', '');
        $dateTo       = $request->query->get('date_to', '');
        $sortBy       = $request->query->get('sort_by', 'submittedAt');
        $sortOrder    = $request->query->get('sort_order', 'DESC');

        $queryBuilder = $repository->createQueryBuilder('ca')
            ->leftJoin('ca.user', 'u')
            ->addSelect('u');

        if ($statusFilter) {
            $queryBuilder->andWhere('ca.status = :status')
                ->setParameter('status', $statusFilter);
        }

        if ($search) {
            $queryBuilder->andWhere(
                'u.username LIKE :search OR u.email LIKE :search OR u.fullName LIKE :search
                 OR ca.certifications LIKE :search OR ca.experience LIKE :search'
            )->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom) {
            try {
                $queryBuilder->andWhere('ca.submittedAt >= :dateFrom')
                    ->setParameter('dateFrom', new \DateTime($dateFrom . ' 00:00:00'));
            } catch (\Exception) {}
        }

        if ($dateTo) {
            try {
                $queryBuilder->andWhere('ca.submittedAt <= :dateTo')
                    ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
            } catch (\Exception) {}
        }

        $validSortFields = ['submittedAt', 'reviewedAt', 'status'];
        $validSortOrder  = in_array(strtoupper($sortOrder), ['ASC', 'DESC']) ? strtoupper($sortOrder) : 'DESC';
        if (in_array($sortBy, $validSortFields)) {
            $queryBuilder->orderBy('ca.' . $sortBy, $validSortOrder);
        } else {
            $queryBuilder->orderBy('ca.submittedAt', 'DESC');
        }
        $queryBuilder->addOrderBy('u.username', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        $stats = $repository->getGlobalStats();

        return $this->render('admin/coach_applications/index.html.twig', [
            'pagination'          => $pagination,
            'statusFilter'        => $statusFilter,
            'search'              => $search,
            'dateFrom'            => $dateFrom,
            'dateTo'              => $dateTo,
            'sortBy'              => $sortBy,
            'sortOrder'           => $sortOrder,
            'stats'               => $stats,
            'applicationStatuses' => ApplicationStatus::cases(),
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  STATS AJAX
    //  IMPORTANT: déclarée AVANT /{id} pour éviter le conflit de route
    // ──────────────────────────────────────────────────────────────
    #[Route('/stats', name: 'admin_coach_applications_stats', methods: ['GET'])]
    public function statsJson(CoachApplicationRepository $repository): JsonResponse
    {
        return $this->json($repository->getGlobalStats());
    }

    // ──────────────────────────────────────────────────────────────
    //  COACHES LIST
    //  Déclarée AVANT /{id} pour éviter le conflit de route
    // ──────────────────────────────────────────────────────────────
    #[Route('/coaches', name: 'admin_coaches_list')]
    public function coachesList(EntityManagerInterface $em): Response
    {
        $coaches = $em->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.rolesJson LIKE :role')
            ->setParameter('role', '%ROLE_COACH%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/coach_applications/coaches_list.html.twig', [
            'coaches' => $coaches,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  SHOW — requirements: id doit être un entier
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}', name: 'admin_coach_applications_show', requirements: ['id' => '\d+'])]
    public function show(CoachApplication $application): Response
    {
        return $this->render('admin/coach_applications/show.html.twig', [
            'application' => $application,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  APPROVE
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}/approve', name: 'admin_coach_applications_approve', methods: ['POST'])]
    public function approve(
        Request                $request,
        CoachApplication       $application,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('approve-' . $application->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $application->approve($request->request->get('comment', ''));

        $this->createAuditLog(
            $em, 'COACH_APPLICATION_APPROVED', 'CoachApplication',
            $application->getId(),
            "Application from " . $application->getUser()->getUsername() . " approved"
        );

        $em->flush();

        $this->notificationService->notifyCoachApplicationApproved($application);
        $this->emailService->sendApprovalEmail($application);

        $this->addFlash('success', 'The application has been approved, the user is now a coach and has received a confirmation email');
        return $this->redirectToRoute('admin_coach_applications_show', ['id' => $application->getId()]);
    }

    // ──────────────────────────────────────────────────────────────
    //  REJECT
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}/reject', name: 'admin_coach_applications_reject', methods: ['POST'])]
    public function reject(
        Request                $request,
        CoachApplication       $application,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('reject-' . $application->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $comment = $request->request->get('comment');
        if (!$comment) {
            $this->addFlash('error', 'A comment is required to reject an application');
            return $this->redirectToRoute('admin_coach_applications_show', ['id' => $application->getId()]);
        }

        $application->reject($comment);

        $this->createAuditLog(
            $em, 'COACH_APPLICATION_REJECTED', 'CoachApplication',
            $application->getId(),
            "Application from " . $application->getUser()->getUsername() . " rejected: $comment"
        );

        $em->flush();

        $this->notificationService->notifyCoachApplicationRejected($application);
        $this->emailService->sendRejectionEmail($application);

        $this->addFlash('warning', 'The application has been rejected and the user has been notified by email');
        return $this->redirectToRoute('admin_coach_applications_show', ['id' => $application->getId()]);
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPER — Audit log
    // ──────────────────────────────────────────────────────────────
    private function createAuditLog(
        EntityManagerInterface $em,
        string                 $action,
        string                 $entityType,
        ?int                   $entityId,
        string                 $details
    ): void {
        $auditLog = new AuditLog();
        $auditLog->setUser($this->getUser());
        $auditLog->setAction($action);
        $auditLog->setEntityType($entityType);
        $auditLog->setEntityId($entityId);
        $auditLog->setDetails($details);
        $auditLog->setIpAddress($_SERVER['REMOTE_ADDR'] ?? null);

        $em->persist($auditLog);
    }
}