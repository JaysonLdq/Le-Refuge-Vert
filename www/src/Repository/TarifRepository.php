<?php 
namespace App\Repository;

use App\Entity\Logement;
use App\Entity\Saison;
use App\Entity\Tarif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TarifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tarif::class);
    }
    
    
    
    
    public function findTarif(Logement $logement, Saison $saison): ?Tarif
    {
        return $this->createQueryBuilder('t')
            ->where('t.logement = :logement')
            ->andWhere('t.saison = :saison')
            ->setParameter('logement', $logement)
            ->setParameter('saison', $saison)
            ->getQuery()
            ->getOneOrNullResult();
    }
    






}
