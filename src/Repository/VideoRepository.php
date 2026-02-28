<?php

namespace App\Repository;

use App\Entity\Video;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Video>
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    /**
     * @return Video[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.uploadedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('v.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Video[]
     */
    public function findLatest(int $limit): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.uploadedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Video[]
     */
    public function findVisibleForUser(User $user): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.uploadedBy', 'u')
            ->addSelect('u')
            ->andWhere('v.uploadedBy = :user OR v.visibility = :public')
            ->setParameter('user', $user)
            ->setParameter('public', 'PUBLIC')
            ->orderBy('v.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Video[]
     */
    public function findPublicHighlights(?string $gameType = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.uploadedBy', 'u')->addSelect('u')
            ->leftJoin('v.clips', 'c')->addSelect('c')
            ->andWhere('v.type = :type')
            ->andWhere('v.visibility = :visibility')
            ->setParameter('type', Video::TYPE_HIGHLIGHT)
            ->setParameter('visibility', Video::VISIBILITY_PUBLIC)
            ->orderBy('v.uploadedAt', 'DESC');

        if ($gameType !== null && $gameType !== '') {
            $qb->andWhere('v.gameType = :gameType')
                ->setParameter('gameType', $gameType);
        }

        return $qb->getQuery()->getResult();
    }

    public function findLatestHighlightByUser(User $user): ?Video
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.clips', 'c')->addSelect('c')
            ->andWhere('v.uploadedBy = :user')
            ->andWhere('v.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', Video::TYPE_HIGHLIGHT)
            ->orderBy('v.uploadedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findHighlightByUserId(int $userId): ?Video
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.uploadedBy', 'u')->addSelect('u')
            ->leftJoin('v.clips', 'c')->addSelect('c')
            ->andWhere('u.id = :userId')
            ->andWhere('v.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', Video::TYPE_HIGHLIGHT)
            ->orderBy('v.uploadedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Video[]
     */
    public function findClipsForMatch(User $user, string $matchId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.uploadedBy = :user')
            ->andWhere('v.matchExternalId = :matchId')
            ->andWhere('v.type = :type')
            ->setParameter('user', $user)
            ->setParameter('matchId', $matchId)
            ->setParameter('type', Video::TYPE_CLIP)
            ->orderBy('v.uploadedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUserSourceVideoForMatch(User $user, string $matchId): ?Video
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.uploadedBy = :user')
            ->andWhere('v.matchExternalId = :matchId')
            ->andWhere('v.type = :type')
            ->setParameter('user', $user)
            ->setParameter('matchId', $matchId)
            ->setParameter('type', Video::TYPE_UPLOAD)
            ->orderBy('v.uploadedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Video[] Returns an array of Video objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Video
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
