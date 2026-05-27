<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * @return ActivityLog[]
     */
    public function findRecentConnections(int $limit = 30): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.action IN (:actions)')
            ->setParameter('actions', ['LOGIN_SUCCESS', 'LOGOUT'])
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ActivityLog[]
     */
    public function findRecentCsvHistory(int $limit = 30): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.action IN (:actions)')
            ->setParameter('actions', ['CSV_EXPORT', 'CSV_IMPORT'])
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ActivityLog[]
     */
    public function findRecentUserActions(int $limit = 30): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.action NOT IN (:excludedActions)')
            ->setParameter('excludedActions', ['LOGIN_SUCCESS', 'LOGOUT', 'CSV_EXPORT', 'CSV_IMPORT'])
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ActivityLog[]
     */
    public function findBySessionId(string $sessionId, int $limit = 500): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.sessionId = :sid')
            ->setParameter('sid', $sessionId)
            ->orderBy('l.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnseen(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.isSeen = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return ActivityLog[]
     */
    public function findLatestUnseen(int $limit = 8): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.isSeen = false')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function markAllSeen(): int
    {
        return $this->createQueryBuilder('l')
            ->update()
            ->set('l.isSeen', ':seen')
            ->setParameter('seen', true)
            ->where('l.isSeen = false')
            ->getQuery()
            ->execute();
    }
}

