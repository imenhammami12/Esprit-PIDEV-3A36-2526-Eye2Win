<?php

namespace App\Repository;

use App\Entity\Complaint;
use App\Entity\ComplaintStatus;
use App\Entity\ComplaintPriority;
use App\Entity\ComplaintCategory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Complaint>
 */
class ComplaintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Complaint::class);
    }

    /**
     * Find complaints by user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.submittedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count pending complaints
     */
    public function countPending(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', ComplaintStatus::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count complaints by status
     */
    public function countByStatus(ComplaintStatus $status): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count complaints by priority
     */
    public function countByPriority(ComplaintPriority $priority): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.priority = :priority')
            ->setParameter('priority', $priority)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count complaints by category
     */
    public function countByCategory(ComplaintCategory $category): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get statistics for dashboard.
     *
     * Uses a single DQL query that groups by status enum object.
     * Doctrine handles all enum ↔ string mapping automatically.
     */
    public function getStatistics(): array
    {
        $stats = [
            'total'       => 0,
            'pending'     => 0,
            'in_progress' => 0,
            'resolved'    => 0,
            'closed'      => 0,
            'rejected'    => 0,
        ];

        // Map enum case name (lowercase) to stats key
        $keyMap = [
            'PENDING'     => 'pending',
            'IN_PROGRESS' => 'in_progress',
            'RESOLVED'    => 'resolved',
            'CLOSED'      => 'closed',
            'REJECTED'    => 'rejected',
        ];

        $results = $this->getEntityManager()
            ->createQuery('SELECT c.status, COUNT(c.id) AS cnt FROM App\Entity\Complaint c GROUP BY c.status')
            ->getResult();

        foreach ($results as $row) {
            // $row['status'] is a ComplaintStatus enum object
            $statusValue = $row['status'] instanceof ComplaintStatus
                ? $row['status']->value          // e.g. "PENDING"
                : (string) $row['status'];        // fallback if string

            $key = $keyMap[$statusValue] ?? strtolower(str_replace(' ', '_', $statusValue));

            if (array_key_exists($key, $stats)) {
                $stats[$key] = (int) $row['cnt'];
            }
            $stats['total'] += (int) $row['cnt'];
        }

        return $stats;
    }

    /**
     * Get priority statistics
     */
    public function getPriorityStatistics(): array
    {
        $stats = [
            'low'    => 0,
            'medium' => 0,
            'high'   => 0,
            'urgent' => 0,
        ];

        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT priority, COUNT(id) AS cnt FROM complaint GROUP BY priority';

        foreach ($conn->executeQuery($sql)->fetchAllAssociative() as $row) {
            $key = strtolower($row['priority']);
            if (array_key_exists($key, $stats)) {
                $stats[$key] = (int) $row['cnt'];
            }
        }

        return $stats;
    }

    /**
     * Get category statistics
     */
    public function getCategoryStatistics(): array
    {
        $stats = [];

        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT category, COUNT(id) AS cnt FROM complaint GROUP BY category';

        foreach ($conn->executeQuery($sql)->fetchAllAssociative() as $row) {
            $key          = strtolower($row['category']);
            $stats[$key]  = (int) $row['cnt'];
        }

        return $stats;
    }

    /**
     * Get average resolution time in hours
     */
    public function getAverageResolutionTime(): ?float
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) AS avg_hours
            FROM complaint
            WHERE resolved_at IS NOT NULL
        ';

        $result = $conn->executeQuery($sql)->fetchOne();

        return $result ? (float) $result : null;
    }

    /**
     * Find recent complaints (last 30 days)
     */
    public function findRecent(int $limit = 10): array
    {
        $date = new \DateTime('-30 days');

        return $this->createQueryBuilder('c')
            ->where('c.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unassigned complaints
     */
    public function findUnassigned(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.assignedTo IS NULL')
            ->andWhere('c.status != :resolved')
            ->andWhere('c.status != :closed')
            ->setParameter('resolved', ComplaintStatus::RESOLVED)
            ->setParameter('closed', ComplaintStatus::CLOSED)
            ->orderBy('c.priority', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unassigned complaints (efficient — no full collection load)
     */
    public function countUnassigned(): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                'SELECT COUNT(c.id) FROM App\Entity\Complaint c
                 WHERE c.assignedTo IS NULL
                 AND c.status NOT IN (:excludedStatuses)'
            )
            ->setParameter('excludedStatuses', [ComplaintStatus::RESOLVED, ComplaintStatus::CLOSED])
            ->getSingleScalarResult();
    }

    /**
     * Find complaints assigned to a specific admin
     */
    public function findByAssignedAdmin(User $admin): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.assignedTo = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('c.priority', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find overdue complaints (pending for more than X days)
     */
    public function findOverdue(int $days = 7): array
    {
        $date = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('c')
            ->where('c.createdAt <= :date')
            ->andWhere('c.status = :pending OR c.status = :in_progress')
            ->setParameter('date', $date)
            ->setParameter('pending', ComplaintStatus::PENDING)
            ->setParameter('in_progress', ComplaintStatus::IN_PROGRESS)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search complaints by keyword
     */
    public function search(string $keyword): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.submittedBy', 'u')
            ->where('c.subject LIKE :keyword')
            ->orWhere('c.description LIKE :keyword')
            ->orWhere('u.username LIKE :keyword')
            ->orWhere('u.email LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get complaints with filters for admin panel
     */
    public function findWithFilters(
        ?string $status = null,
        ?string $priority = null,
        ?string $category = null,
        ?int $assignedToId = null
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.submittedBy', 'u')
            ->leftJoin('c.assignedTo', 'a')
            ->addSelect('u', 'a');

        if ($status) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        if ($priority) {
            $qb->andWhere('c.priority = :priority')
               ->setParameter('priority', $priority);
        }

        if ($category) {
            $qb->andWhere('c.category = :category')
               ->setParameter('category', $category);
        }

        if ($assignedToId) {
            $qb->andWhere('c.assignedTo = :assignedTo')
               ->setParameter('assignedTo', $assignedToId);
        }

        return $qb->orderBy('c.priority', 'DESC')
                  ->addOrderBy('c.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count complaints created today
     */
    public function countToday(): int
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count complaints resolved today
     */
    public function countResolvedToday(): int
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.resolvedAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get monthly statistics (last 12 months)
     */
    public function getMonthlyStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                DATE_FORMAT(created_at, "%Y-%m") AS month,
                COUNT(*)                          AS total,
                SUM(CASE WHEN status = "RESOLVED" THEN 1 ELSE 0 END) AS resolved
            FROM complaint
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ';

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }
}