<?php

namespace App\Repository;

use App\Entity\Complaint;
use App\Entity\ComplaintStatus;
use App\Entity\ComplaintPriority;
use App\Entity\ComplaintCategory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<Complaint>
 */
class ComplaintRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CacheInterface $complaintStatsCache,
        private readonly CacheInterface $dashboardCache,
        private readonly CacheInterface $monthlyStatsCache,
    ) {
        parent::__construct($registry, Complaint::class);
    }

    // -------------------------------------------------------------------------
    // Cache invalidation — appeler après chaque create / update / delete
    // -------------------------------------------------------------------------

    public function invalidateStatisticsCache(): void
    {
        $this->complaintStatsCache->delete('complaint_statistics');
        $this->complaintStatsCache->delete('complaint_priority_statistics');
        $this->complaintStatsCache->delete('complaint_category_statistics');
        $this->dashboardCache->delete('dashboard_stats');
        $this->monthlyStatsCache->delete('monthly_statistics');
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /**
     * Find complaints by user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.submittedBy', 'u')
            ->leftJoin('c.assignedTo', 'a')
            ->addSelect('u', 'a')
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
        return $this->countByStatus(ComplaintStatus::PENDING);
    }

    /**
     * Count complaints by status
     */
    public function countByStatus(ComplaintStatus $status): int
    {
        return (int) $this->createQueryBuilder('c')
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
        return (int) $this->createQueryBuilder('c')
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
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count complaints created today
     */
    public function countToday(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count complaints resolved today
     */
    public function countResolvedToday(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.resolvedAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count unassigned complaints
     */
    public function countUnassigned(): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                'SELECT COUNT(c.id) FROM App\Entity\Complaint c
                 WHERE c.assignedTo IS NULL
                 AND c.status NOT IN (:excludedStatuses)'
            )
            ->setParameter('excludedStatuses', [
                ComplaintStatus::RESOLVED,
                ComplaintStatus::CLOSED,
                ComplaintStatus::REJECTED,
            ])
            ->getSingleScalarResult();
    }

    /**
     * Find recent complaints (last 30 days)
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.submittedBy', 'u')
            ->leftJoin('c.assignedTo', 'a')
            ->addSelect('u', 'a')
            ->where('c.createdAt >= :date')
            ->setParameter('date', new \DateTime('-30 days'))
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
            ->leftJoin('c.submittedBy', 'u')
            ->addSelect('u')
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
     * Find complaints assigned to a specific admin
     */
    public function findByAssignedAdmin(User $admin): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.submittedBy', 'u')
            ->addSelect('u')
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
            ->addSelect('u')
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
        ?string $status       = null,
        ?string $priority     = null,
        ?string $category     = null,
        ?int    $assignedToId = null
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

    // -------------------------------------------------------------------------
    // Statistics — cached in Redis
    // -------------------------------------------------------------------------

    /**
     * Dashboard statistics — CACHED 5 minutes in Redis
     */
    public function getStatistics(): array
    {
        return $this->complaintStatsCache->get('complaint_statistics', function (ItemInterface $item) {
            $item->expiresAfter(300); // 5 minutes

            $stats = [
                'total'       => 0,
                'pending'     => 0,
                'in_progress' => 0,
                'resolved'    => 0,
                'closed'      => 0,
                'rejected'    => 0,
            ];

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
                $statusValue = $row['status'] instanceof ComplaintStatus
                    ? $row['status']->value
                    : (string) $row['status'];

                $key = $keyMap[$statusValue] ?? strtolower(str_replace(' ', '_', $statusValue));

                if (array_key_exists($key, $stats)) {
                    $stats[$key] = (int) $row['cnt'];
                }
                $stats['total'] += (int) $row['cnt'];
            }

            $stats['unassigned']           = $this->countUnassigned();
            $stats['avg_resolution_hours'] = $this->getAverageResolutionTime();

            return $stats;
        });
    }

    /**
     * Priority statistics — CACHED 5 minutes in Redis
     */
    public function getPriorityStatistics(): array
    {
        return $this->complaintStatsCache->get('complaint_priority_statistics', function (ItemInterface $item) {
            $item->expiresAfter(300);

            $stats = ['low' => 0, 'medium' => 0, 'high' => 0, 'urgent' => 0];
            $conn  = $this->getEntityManager()->getConnection();

            foreach ($conn->executeQuery('SELECT priority, COUNT(id) AS cnt FROM complaint GROUP BY priority')
                         ->fetchAllAssociative() as $row) {
                $key = strtolower($row['priority']);
                if (array_key_exists($key, $stats)) {
                    $stats[$key] = (int) $row['cnt'];
                }
            }

            return $stats;
        });
    }

    /**
     * Category statistics — CACHED 5 minutes in Redis
     */
    public function getCategoryStatistics(): array
    {
        return $this->complaintStatsCache->get('complaint_category_statistics', function (ItemInterface $item) {
            $item->expiresAfter(300);

            $stats = [];
            $conn  = $this->getEntityManager()->getConnection();

            foreach ($conn->executeQuery('SELECT category, COUNT(id) AS cnt FROM complaint GROUP BY category')
                         ->fetchAllAssociative() as $row) {
                $stats[strtolower($row['category'])] = (int) $row['cnt'];
            }

            return $stats;
        });
    }

    /**
     * Average resolution time in hours
     */
    public function getAverageResolutionTime(): ?float
    {
        $result = $this->getEntityManager()->getConnection()->executeQuery('
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) AS avg_hours
            FROM complaint
            WHERE resolved_at IS NOT NULL
        ')->fetchOne();

        return $result ? (float) $result : null;
    }

    /**
     * Monthly statistics — CACHED 1 hour in Redis
     */
    public function getMonthlyStatistics(): array
    {
        return $this->monthlyStatsCache->get('monthly_statistics', function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1 hour

            return $this->getEntityManager()->getConnection()->executeQuery('
                SELECT
                    DATE_FORMAT(created_at, "%Y-%m") AS month,
                    COUNT(*)                          AS total,
                    SUM(CASE WHEN status = "RESOLVED" THEN 1 ELSE 0 END) AS resolved
                FROM complaint
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month
                ORDER BY month ASC
            ')->fetchAllAssociative();
        });
    }
}