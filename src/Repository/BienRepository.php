<?php

namespace App\Repository;

use App\Entity\Bien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Bien>
 */
class BienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bien::class);
    }

    /**
     * Search Bien entities based on criteria.
     *
     * @param array $criteria Keys: city, minPrice, maxPrice, rooms
     * @return Bien[]
     */
    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('b');
        if (!empty($criteria['city'])) {
            $qb->andWhere('b.city LIKE :city')
               ->setParameter('city', '%' . $criteria['city'] . '%');
        }
        if (!empty($criteria['minPrice'])) {
            $qb->andWhere('b.prix >= :minPrice')
               ->setParameter('minPrice', $criteria['minPrice']);
        }
        if (!empty($criteria['maxPrice'])) {
            $qb->andWhere('b.prix <= :maxPrice')
               ->setParameter('maxPrice', $criteria['maxPrice']);
        }
        if (!empty($criteria['rooms'])) {
            $qb->andWhere('b.nbPieces = :rooms')
               ->setParameter('rooms', $criteria['rooms']);
        }
        return $qb->orderBy('b.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
