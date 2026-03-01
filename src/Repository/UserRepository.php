<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\AccountStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // ══════════════════════════════════════════════════════════════
    //  STATS GLOBALES — source unique de vérité
    // ══════════════════════════════════════════════════════════════

    public function getGlobalStats(): array
    {
        $allUsers = $this->findAll();

        $total       = count($allUsers);
        $active      = 0;
        $suspended   = 0;
        $banned      = 0;
        $coaches     = 0;
        $admins      = 0;
        $superAdmins = 0;

        foreach ($allUsers as $user) {
            $status = $user->getAccountStatus();
            if ($status === AccountStatus::ACTIVE)    $active++;
            if ($status === AccountStatus::SUSPENDED) $suspended++;
            if ($status === AccountStatus::BANNED)    $banned++;

            $roles = $user->getRoles();

            if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                $superAdmins++;
            } elseif (in_array('ROLE_ADMIN', $roles, true)) {
                $admins++;
            }

            if (in_array('ROLE_COACH', $roles, true)) {
                $coaches++;
            }
        }

        return [
            'total'       => $total,
            'active'      => $active,
            'inactive'    => $total - $active,
            'suspended'   => $suspended,
            'banned'      => $banned,
            'coaches'     => $coaches,
            'admins'      => $admins,
            'superAdmins' => $superAdmins,
            'activeRate'  => $total > 0 ? (int) round(($active / $total) * 100) : 0,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  FIND ADMINS
    // ══════════════════════════════════════════════════════════════

    public function findAdmins(): array
    {
        $admins = [];

        foreach ($this->findAll() as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                $admins[] = $user;
            }
        }

        usort($admins, fn($a, $b) => strcmp($a->getUsername(), $b->getUsername()));

        return $admins;
    }

    // ══════════════════════════════════════════════════════════════
    //  MÉTHODES UTILITAIRES — inchangées
    // ══════════════════════════════════════════════════════════════

    public function findUsersByRole(string $role): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(User $user) => in_array($role, $user->getRoles(), true)
        ));
    }

    public function countByRole(string $role): int
    {
        return count($this->findUsersByRole($role));
    }

    public function searchForInvitation(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username LIKE :query OR u.email LIKE :query')
            ->andWhere('u.accountStatus = :status')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('status', AccountStatus::ACTIVE)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.accountStatus = :status')
            ->setParameter('status', AccountStatus::ACTIVE)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchUsers(string $search): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username LIKE :search OR u.email LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecent(int $days = 30): array
    {
        $date = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('u')
            ->where('u.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ══════════════════════════════════════════════════════════════
    //  NOUVEAU — stats page d'accueil
    // ══════════════════════════════════════════════════════════════

    /**
     * Compte le nombre total d'utilisateurs enregistrés.
     */
    public function countAllUsers(): int
    {
        return $this->countTotal(); // réutilise la méthode existante
    }

    /**
     * Compte les utilisateurs ayant le rôle ROLE_COACH.
     */
    public function countCoaches(): int
    {
        return $this->countByRole('ROLE_COACH'); // réutilise la méthode existante
    }
}