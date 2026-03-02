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

    public function invalidateStatisticsCache(): void
    {
        $this->complaintStatsCache->delete('complaint_statistics');
        $this->complaintStatsCache->delete('complaint_priority_statistics');
        $this->complaintStatsCache->delete('complaint_category_statistics');
        $this->dashboardCache->delete('dashboard_stats');
        $this->monthlyStatsCache->delete('monthly_statistics');
    }

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
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countResolvedToday(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.resolvedAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

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
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }
        if ($priority) {
            $qb->andWhere('c.priority = :priority')->setParameter('priority', $priority);
        }
        if ($category) {
            $qb->andWhere('c.category = :category')->setParameter('category', $category);
        }
        if ($assignedToId) {
            $qb->andWhere('c.assignedTo = :assignedTo')->setParameter('assignedTo', $assignedToId);
        }

        return $qb->orderBy('c.priority', 'DESC')
                  ->addOrderBy('c.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function getStatistics(): array
    {
        return $this->complaintStatsCache->get('complaint_statistics', function (ItemInterface $item) {
            $item->expiresAfter(300);

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
     * PostgreSQL: use EXTRACT(EPOCH FROM ...) instead of TIMESTAMPDIFF
     */
    public function getAverageResolutionTime(): ?float
    {
        $result = $this->getEntityManager()->getConnection()->executeQuery('
            SELECT AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600) AS avg_hours
            FROM complaint
            WHERE resolved_at IS NOT NULL
        ')->fetchOne();

        return $result ? (float) $result : null;
    }

    /**
     * Monthly statistics
     * PostgreSQL: use TO_CHAR and DATE_TRUNC instead of DATE_FORMAT/DATE_SUB
     */
    public function getMonthlyStatistics(): array
    {
        return $this->monthlyStatsCache->get('monthly_statistics', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return $this->getEntityManager()->getConnection()->executeQuery("
                SELECT
                    TO_CHAR(created_at, 'YYYY-MM') AS month,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) AS resolved
                FROM complaint
                WHERE created_at >= NOW() - INTERVAL '12 months'
                GROUP BY month
                ORDER BY month ASC
            ")->fetchAllAssociative();
        });
    }
}