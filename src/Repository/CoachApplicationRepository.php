<?php

namespace App\Repository;

use App\Entity\CoachApplication;
use App\Entity\ApplicationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CoachApplication>
 */
class CoachApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoachApplication::class);
    }

    // ══════════════════════════════════════════════════════════════
    //  STATS GLOBALES — source unique de vérité
    //  Calculées PHP-side pour éviter tout problème de mapping.
    // ══════════════════════════════════════════════════════════════

    /**
     * Retourne toutes les statistiques globales en un seul appel.
     * Utilisé par le controller (rendu initial) et la route AJAX /stats.
     */
    public function getGlobalStats(): array
    {
        $all = $this->findAll();

        $pending  = 0;
        $approved = 0;
        $rejected = 0;

        foreach ($all as $application) {
            $status = $application->getStatus();
            if ($status === ApplicationStatus::PENDING)  $pending++;
            if ($status === ApplicationStatus::APPROVED) $approved++;
            if ($status === ApplicationStatus::REJECTED) $rejected++;
        }

        $total = count($all);

        return [
            'total'    => $total,
            'pending'  => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
        ];
    }
}