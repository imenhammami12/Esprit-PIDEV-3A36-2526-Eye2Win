<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\AccountStatus;
use App\Entity\AuditLog;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    // ──────────────────────────────────────────────────────────────
    //  INDEX — liste paginée + stats globales
    // ──────────────────────────────────────────────────────────────
    #[Route('/', name: 'admin_users_index')]
    public function index(
        Request            $request,
        UserRepository     $userRepository,
        PaginatorInterface $paginator
    ): Response {
        $search       = $request->query->get('search', '');
        $roleFilter   = $request->query->get('role', '');
        $statusFilter = $request->query->get('status', '');

        // ── Filtered query (for the table) ─────────────────────────
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        if ($search) {
            $queryBuilder
                ->andWhere('u.username LIKE :search OR u.email LIKE :search OR u.fullName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($roleFilter) {
            $queryBuilder
                ->andWhere('u.rolesJson LIKE :role')
                ->setParameter('role', '%' . $roleFilter . '%');
        }
        if ($statusFilter) {
            $queryBuilder
                ->andWhere('u.accountStatus = :status')
                ->setParameter('status', $statusFilter);
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // ── Stats globales (calculées PHP-side, fiables) ────────────
        $stats = $userRepository->getGlobalStats();

        return $this->render('admin/users/index.html.twig', [
            'pagination'      => $pagination,
            'search'          => $search,
            'roleFilter'      => $roleFilter,
            'statusFilter'    => $statusFilter,
            'accountStatuses' => AccountStatus::cases(),
            'stats'           => $stats,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  STATS AJAX — endpoint pour le rafraîchissement dynamique
    // ──────────────────────────────────────────────────────────────
    #[Route('/stats', name: 'admin_users_stats', methods: ['GET'])]
    public function statsJson(UserRepository $userRepository): JsonResponse
    {
        return $this->json($userRepository->getGlobalStats());
    }

    // ──────────────────────────────────────────────────────────────
    //  SHOW
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}', name: 'admin_users_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        $stats = [
            'totalConnections'    => 0,
            'totalTimeSpent'      => 0,
            'teamsCount'          => $user->getTeamMemberships()->count(),
            'ownedTeamsCount'     => $user->getOwnedTeams()->count(),
            'notificationsCount'  => $user->getNotifications()->count(),
            'unreadNotifications' => $user->getNotifications()->filter(fn($n) => !$n->isRead())->count(),
        ];

        return $this->render('admin/users/show.html.twig', [
            'user'  => $user,
            'stats' => $stats,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  EDIT ROLE
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}/edit-role', name: 'admin_users_edit_role', methods: ['POST'])]
    public function editRole(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('edit-role-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot modify your own role');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        $newRole    = $request->request->get('role');
        $validRoles = ['ROLE_USER', 'ROLE_COACH', 'ROLE_ADMIN'];

        if (!in_array($newRole, $validRoles)) {
            $this->addFlash('error', 'Invalid role');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }
        if ($newRole === 'ROLE_ADMIN' && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Only Super Administrators can assign the Admin role');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }
        if (in_array('ROLE_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Only Super Administrators can modify an Administrator\'s role');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        $roles = ['ROLE_USER'];
        if ($newRole !== 'ROLE_USER') {
            $roles[] = $newRole;
        }
        $user->setRoles($roles);

        $this->createAuditLog($em, 'USER_ROLE_CHANGED', 'User', $user->getId(), "Role changed to: $newRole");
        $em->flush();

        $this->addFlash('success', 'User role updated successfully');
        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }

    // ──────────────────────────────────────────────────────────────
    //  SUSPEND
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}/suspend', name: 'admin_users_suspend', methods: ['POST'])]
    public function suspend(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('suspend-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot suspend your own account');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }
        if (in_array('ROLE_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Only Super Administrators can suspend Administrators');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        $user->setAccountStatus(AccountStatus::SUSPENDED);
        $this->createAuditLog($em, 'USER_SUSPENDED', 'User', $user->getId(), 'Account suspended by administrator');
        $em->flush();

        $this->addFlash('warning', 'User account suspended successfully.');
        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }

    // ──────────────────────────────────────────────────────────────
    //  BAN
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}/ban', name: 'admin_users_ban', methods: ['POST'])]
    public function ban(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('ban-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot ban your own account');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }
        if (in_array('ROLE_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Only Super Administrators can ban Administrators');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        $user->setAccountStatus(AccountStatus::BANNED);
        $this->createAuditLog($em, 'USER_BANNED', 'User', $user->getId(), 'Account permanently banned by administrator');
        $em->flush();

        $this->addFlash('danger', 'User account banned successfully.');
        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }

    // ──────────────────────────────────────────────────────────────
    //  ACTIVATE
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}/activate', name: 'admin_users_activate', methods: ['POST'])]
    public function activate(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('activate-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        if (
            in_array('ROLE_ADMIN', $user->getRoles()) &&
            $user->getAccountStatus() === AccountStatus::BANNED &&
            !$this->isGranted('ROLE_SUPER_ADMIN')
        ) {
            $this->addFlash('error', 'Only Super Administrators can reactivate banned Administrators');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        $previousStatus = $user->getAccountStatus()->value;
        $user->setAccountStatus(AccountStatus::ACTIVE);
        $this->createAuditLog($em, 'USER_ACTIVATED', 'User', $user->getId(), "Account reactivated (was: $previousStatus)");
        $em->flush();

        $this->addFlash('success', 'User account reactivated successfully.');
        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }

    // ──────────────────────────────────────────────────────────────
    //  DELETE
    // ──────────────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot delete your own account');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }
        if (in_array('ROLE_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Only Super Administrators can delete Administrators');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        $userId   = $user->getId();
        $username = $user->getUsername();
        $this->createAuditLog($em, 'USER_DELETED', 'User', $userId, "User $username permanently deleted");

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted successfully');
        return $this->redirectToRoute('admin_users_index');
    }

    // ──────────────────────────────────────────────────────────────
    //  CREATE
    // ──────────────────────────────────────────────────────────────
    #[Route('/create', name: 'admin_users_create')]
    public function create(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository              $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create-user', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }

            $username = $request->request->get('username');
            $email    = $request->request->get('email');

            if ($userRepository->findOneBy(['username' => $username])) {
                $this->addFlash('error', 'This username is already taken');
                return $this->render('admin/users/create.html.twig');
            }
            if ($userRepository->findOneBy(['email' => strtolower(trim($email))])) {
                $this->addFlash('error', 'This email is already registered');
                return $this->render('admin/users/create.html.twig');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setFullName($request->request->get('fullName'));
            $user->setPassword($passwordHasher->hashPassword($user, $request->request->get('password')));

            $role = $request->request->get('role', 'ROLE_USER');
            if ($role === 'ROLE_ADMIN' && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->addFlash('error', 'Only Super Administrators can create Administrator accounts');
                return $this->render('admin/users/create.html.twig');
            }

            $roles = ['ROLE_USER'];
            if ($role !== 'ROLE_USER') {
                $roles[] = $role;
            }
            $user->setRoles($roles);

            $this->createAuditLog($em, 'USER_CREATED', 'User', null, "New user: {$user->getUsername()} with role: $role");

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User created successfully');
            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/users/create.html.twig');
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