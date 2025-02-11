<?php

namespace App\Repository;

use App\Entity\Logement;
use App\Entity\Rental;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rental>
 */
class RentalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rental::class);
    }

    //    /**
    //     * @return Rental[] Returns an array of Rental objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Rental
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findLogementByRental(int $rentalId): ?Logement
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT l FROM App\Entity\Logement l
                JOIN App\Entity\Rental r WITH r.logement = l
                WHERE r.id = :id
            ')
            ->setParameter('id', $rentalId)
            ->getOneOrNullResult();
    }

    public function findByPrice($price)
    {
        return $this->createQueryBuilder('r')
            ->where('r.price = :price')
            ->setParameter('price', $price) // 🔥 Ajout de `setParameter`
            ->getQuery()
            ->getOneOrNullResult();
    }
    

}
