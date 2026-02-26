<?php

namespace App\Repository;

use App\Entity\Complaint;
use App\Entity\ComplaintStatus;
use App\Entity\ComplaintPriority;
use App\Entity\ComplaintCategory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Base QueryBuilder that always eager-loads submittedBy and assignedTo
     * to prevent N+1 queries in list views.
     */
    private function baseQb(string $alias = 'c'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->leftJoin("{$alias}.submittedBy", 'u')
            ->leftJoin("{$alias}.assignedTo", 'a')
            ->addSelect('u', 'a');
    }

    // -------------------------------------------------------------------------
    // Public query methods
    // -------------------------------------------------------------------------

    /**
     * Find all complaints submitted by a given user, newest first.
     */
    public function findByUser(User $user): array
    {
        return $this->baseQb()
            ->where('c.submittedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Full-text search across subject, username and email.
     * NOTE: description intentionally excluded — LIKE '%…%' on a TEXT column
     *       causes a full-table scan. Add a FULLTEXT index + MATCH … AGAINST
     *       if you need description search.
     */
    public function search(string $keyword): array
    {
        $param = '%' . $keyword . '%';

        return $this->baseQb()
            ->where('c.subject LIKE :kw')
            ->orWhere('u.username LIKE :kw')
            ->orWhere('u.email LIKE :kw')
            ->setParameter('kw', $param)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filter complaints for the admin list view.
     * All parameters are optional; pass null to skip a filter.
     */
    public function findWithFilters(
        ?string $status       = null,
        ?string $priority     = null,
        ?string $category     = null,
        ?int    $assignedToId = null
    ): array {
        $qb = $this->baseQb();

        if ($status !== null && $status !== '') {
            $statusEnum = ComplaintStatus::tryFrom($status);
            if ($statusEnum !== null) {
                $qb->andWhere('c.status = :status')
                   ->setParameter('status', $statusEnum);
            }
        }

        if ($priority !== null && $priority !== '') {
            $priorityEnum = ComplaintPriority::tryFrom($priority);
            if ($priorityEnum !== null) {
                $qb->andWhere('c.priority = :priority')
                   ->setParameter('priority', $priorityEnum);
            }
        }

        if ($category !== null && $category !== '') {
            $categoryEnum = ComplaintCategory::tryFrom($category);
            if ($categoryEnum !== null) {
                $qb->andWhere('c.category = :category')
                   ->setParameter('category', $categoryEnum);
            }
        }

        if ($assignedToId !== null) {
            $qb->andWhere('c.assignedTo = :assignedTo')
               ->setParameter('assignedTo', $assignedToId);
        }

        return $qb->orderBy('c.priority', 'DESC')
                  ->addOrderBy('c.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Recent complaints (last N days), newest first.
     */
    public function findRecent(int $limit = 10, int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->baseQb()
            ->where('c.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Unassigned, non-final complaints — ordered by priority then age.
     */
    public function findUnassigned(): array
    {
        return $this->baseQb()
            ->where('c.assignedTo IS NULL')
            ->andWhere('c.status NOT IN (:finals)')
            ->setParameter('finals', [
                ComplaintStatus::RESOLVED,
                ComplaintStatus::CLOSED,
                ComplaintStatus::REJECTED,
            ])
            ->orderBy('c.priority', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Complaints assigned to a specific admin user.
     */
    public function findByAssignedAdmin(User $admin): array
    {
        return $this->baseQb()
            ->where('c.assignedTo = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('c.priority', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Overdue complaints: still open after $days days without resolution.
     */
    public function findOverdue(int $days = 7): array
    {
        $threshold = new \DateTimeImmutable("-{$days} days");

        return $this->baseQb()
            ->where('c.createdAt <= :threshold')
            ->andWhere('c.status IN (:open)')
            ->setParameter('threshold', $threshold)
            ->setParameter('open', [ComplaintStatus::PENDING, ComplaintStatus::IN_PROGRESS])
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // -------------------------------------------------------------------------
    // Count helpers (scalar queries — no SELECT * overhead)
    // -------------------------------------------------------------------------

    public function countPending(): int
    {
        return $this->countByStatus(ComplaintStatus::PENDING);
    }

    public function countByStatus(ComplaintStatus $status): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByPriority(ComplaintPriority $priority): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.priority = :priority')
            ->setParameter('priority', $priority)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCategory(ComplaintCategory $category): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countToday(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countResolvedToday(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.resolvedAt >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    // -------------------------------------------------------------------------
    // Statistics — consolidated into as few queries as possible
    // -------------------------------------------------------------------------

    /**
     * Returns all dashboard statistics in 2 DB round-trips instead of the
     * previous 4 (status counts + unassigned count + avg resolution time).
     *
     * Query 1: status counts + unassigned count in a single GROUP BY query.
     * Query 2: AVG(resolved_at) via raw SQL (unchanged).
     *
     * @return array{
     *   total: int,
     *   pending: int,
     *   in_progress: int,
     *   resolved: int,
     *   closed: int,
     *   rejected: int,
     *   unassigned: int,
     *   avg_resolution_hours: float|null
     * }
     */
    public function getStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Single SQL query: status counts + unassigned sub-total in one pass.
        // The SUM expression counts rows that are unassigned AND not in a final state.
        $rows = $conn->executeQuery('
            SELECT
                status,
                COUNT(*)  AS cnt,
                SUM(
                    assigned_to_id IS NULL
                    AND status NOT IN ("RESOLVED", "CLOSED", "REJECTED")
                ) AS unassigned_cnt
            FROM complaint
            GROUP BY status
        ')->fetchAllAssociative();

        $stats = [
            'total'       => 0,
            'pending'     => 0,
            'in_progress' => 0,
            'resolved'    => 0,
            'closed'      => 0,
            'rejected'    => 0,
            'unassigned'  => 0,
        ];

        foreach ($rows as $row) {
            // Normalize "IN_PROGRESS" → "in_progress" for array key
            $key = strtolower(str_replace('_', '_', $row['status']));

            if (array_key_exists($key, $stats)) {
                $stats[$key] = (int) $row['cnt'];
            }

            $stats['total']      += (int) $row['cnt'];
            $stats['unassigned'] += (int) $row['unassigned_cnt'];
        }

        // Second query: average resolution time
        $stats['avg_resolution_hours'] = $this->getAverageResolutionTime();

        return $stats;
    }

    /**
     * @return array{low:int,medium:int,high:int,urgent:int}
     */
    public function getPriorityStatistics(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.priority, COUNT(c.id) as cnt')
            ->groupBy('c.priority')
            ->getQuery()
            ->getResult();

        $stats = ['low' => 0, 'medium' => 0, 'high' => 0, 'urgent' => 0];

        foreach ($rows as $row) {
            $stats[strtolower($row['priority']->value)] = (int) $row['cnt'];
        }

        return $stats;
    }

    /**
     * @return array<string, int>  keyed by lowercase category value
     */
    public function getCategoryStatistics(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.category, COUNT(c.id) as cnt')
            ->groupBy('c.category')
            ->getQuery()
            ->getResult();

        $stats = [];

        foreach ($rows as $row) {
            $stats[strtolower($row['category']->value)] = (int) $row['cnt'];
        }

        return $stats;
    }

    /**
     * Average time from creation to resolution, in hours.
     * Returns null when there are no resolved complaints yet.
     */
    public function getAverageResolutionTime(): ?float
    {
        $conn = $this->getEntityManager()->getConnection();

        $result = $conn->executeQuery('
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at))
            FROM complaint
            WHERE resolved_at IS NOT NULL
        ')->fetchOne();

        return ($result !== false && $result !== null) ? (float) $result : null;
    }

    /**
     * Monthly totals + resolved counts for the last 12 months.
     *
     * @return list<array{month: string, total: int, resolved: int}>
     */
    public function getMonthlyStatistics(): array
    {
        return $this->getEntityManager()->getConnection()->executeQuery('
            SELECT
                DATE_FORMAT(created_at, "%Y-%m") AS month,
                COUNT(*)                          AS total,
                SUM(status = "RESOLVED")          AS resolved
            FROM complaint
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ')->fetchAllAssociative();
    }
}