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
    
    return $this->createQueryBuilder('s')
        ->andWhere('s.dateS <= :currentDate')
        ->andWhere('s.dateE >= :currentDate')
        ->setParameter('currentDate', $currentDate)
        ->getQuery()
        ->getOneOrNullResult();
}


    public function findOrCreateDefaultSeason(): ?Saison
    {
        // Essayer de trouver la saison actuelle
        $saisonActuelle = $this->createQueryBuilder('s')
            ->where('s.dateS <= :now AND s.dateE >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    
        // Si aucune saison trouvée, créer une saison par défaut
        if (!$saisonActuelle) {
            $saisonActuelle = new Saison();
            $saisonActuelle->setLabel("Haute saison");
            $saisonActuelle->setDateS(new \DateTime("2024-06-01"));
            $saisonActuelle->setDateE(new \DateTime("2024-09-01"));
    
            // Utilisation du gestionnaire d'entité pour persister l'objet
            $this->getEntityManager()->persist($saisonActuelle);
            $this->getEntityManager()->flush();
        }
    
        return $saisonActuelle;
    }
    
    public function findCurrentSeason(): ?Saison
{
    $currentDate = new \DateTime();
    return $this->createQueryBuilder('s')
        ->where('s.dateS <= :currentDate')
        ->andWhere('s.dateE >= :currentDate')
        ->setParameter('currentDate', $currentDate)
        ->getQuery()
        ->getOneOrNullResult();
}

public function findSeasonById($id): ?Saison
{
    return $this->createQueryBuilder('s')
        ->where('s.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}

    

    



}
