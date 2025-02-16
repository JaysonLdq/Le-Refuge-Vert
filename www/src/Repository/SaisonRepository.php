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


    public function findAllSeasons(): array
    {
        return $this->createQueryBuilder('s')
            ->getQuery()
            ->getResult();
    }
    
   
    
    

    public function findSeasonsActiveOnCurrentDate(): ?Saison
    {
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));  // Date actuelle en UTC
        $currentDate->setTime(0, 0, 0);  // Supprime l'heure, pour ne comparer que les dates
    
        // On recherche la saison qui est active à la date actuelle
        return $this->createQueryBuilder('s')
            ->where('s.dateS <= :currentDate') // La date de début de la saison doit être avant la date actuelle
            ->andWhere('s.dateE >= :currentDate') // La date de fin de la saison doit être après la date actuelle
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getOneOrNullResult(); // Retourne la saison correspondante ou null si aucune saison n'est trouvée
    }
    



}
