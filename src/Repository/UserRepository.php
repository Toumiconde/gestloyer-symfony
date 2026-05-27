<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\RoleUtilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countActiveAdminsExcluding(?User $excludedUser = null): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.role = :adminRole')
            ->andWhere('u.isActive = true')
            ->setParameter('adminRole', RoleUtilisateur::ADMIN);

        if ($excludedUser?->getId() !== null) {
            $qb->andWhere('u.id != :excludedId')
                ->setParameter('excludedId', $excludedUser->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
