<?php

namespace App\Repository;

use App\Entity\Saison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Saison>
 */
class SaisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Saison::class);
    }

    public function findSeason(): ?Saison
    {
        $currentDate = new \DateTime();
    
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.dateS <= :currentDate')
            ->andWhere('s.dateE >= :currentDate')
            ->setParameter('currentDate', $currentDate);
    
    
        return $qb->getQuery()->getOneOrNullResult();
    }
    



}
