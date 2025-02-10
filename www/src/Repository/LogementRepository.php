<?php

namespace App\Repository;

use App\Entity\Logement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Logement>
 */
class LogementRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        parent::__construct($registry, Logement::class);
        $this->em = $em; // 🔥 Assigne bien EntityManager
    }

    /**
     * Sauvegarde un logement
     */
    public function save(Logement $logement, bool $flush = false): void
    {
        $this->em->persist($logement);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime un logement
     */
    public function remove(Logement $logement, bool $flush = false): void
    {
        $this->em->remove($logement);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Trouve tous les logements avec leurs tarifs
     */
    public function findAllWithTarifs(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.tarifs', 't')
            ->addSelect('t')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche un logement par son ID avec tarifs
     */
    public function findWithTarif(int $id): ?Logement
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.tarifs', 't')
            ->addSelect('t')
            ->where('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
