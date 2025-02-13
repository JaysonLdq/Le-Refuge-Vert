<?php

namespace App\Repository;

use App\Entity\Logement;
use App\Entity\Saison;
use App\Entity\Tarif;
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

    /**
     * trouve tout les équipements 
     * 
     */
    public function findAllEquipements(int $logementId)
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.equipements', 'e')
            ->addSelect('e')
            ->where('l.id = :logementId')
            ->setParameter('logementId', $logementId)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithPrices(TarifRepository $tarifRepository, ?Saison $saisonActuelle): array
    {
        $logements = $this->findAll();  // Récupérer tous les logements
        $logementsAvecPrix = [];
    
        foreach ($logements as $logement) {
            // Initialisation de la variable de prix
            $price = "Tarif indisponible";
    
            // Si la saison actuelle est disponible, récupérer le tarif pour ce logement
            if ($saisonActuelle) {
                $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
                if ($tarif) {
                    $price = $tarif->getPrice();
                }
            }
    
            // Ajouter les données au tableau avec les bonnes clés
            $logementsAvecPrix[] = [
                'logement' => $logement,  // Ici, vous pouvez garder 'logement' et l'utiliser dans la vue
                'price' => $price,        // Le prix associé
                'logement_id' => $logement->getId()  // Id du logement
            ];
        }
    
        return $logementsAvecPrix;
    }
    
    public function findTarifForLogementAndSaison(Logement $logement, Saison $saison): ?Tarif
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
