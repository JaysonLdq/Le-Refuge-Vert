<?php

namespace App\Controller;

use App\Entity\Rental;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SaisonRepository;
use App\Repository\LogementRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Saison;
use App\Form\RentalType;
use App\Repository\EquipementRepository;
use Symfony\Component\HttpFoundation\Request;


class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        SaisonRepository $saisonRepository, 
        LogementRepository $logementRepository, 
        TarifRepository $tarifRepository,
        EntityManagerInterface $em
    ): Response {
        // Récupération de la saison actuelle
        $saisonActuelle = $saisonRepository->findSeason();
        dump("Saison Actuelle :", $saisonActuelle);

        // Si aucune saison actuelle n'est trouvée, rechercher une saison par défaut
        if (!$saisonActuelle) {
            dump("Aucune saison actuelle trouvée, recherche d'une saison par défaut...");

            $saisonActuelle = $saisonRepository->createQueryBuilder('s')
                ->where('s.label IN (:defaultSeasons)')
                ->setParameter('defaultSeasons', ['Haute saison', 'Basse saison', 'Hors saison'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // Si aucune saison par défaut n'existe, création d'une nouvelle saison "Haute saison"
            if (!$saisonActuelle) {
                dump("Aucune saison par défaut trouvée, création de 'Haute saison'...");

                $saisonActuelle = new Saison();
                $saisonActuelle->setLabel("Haute saison");
                $saisonActuelle->setDateS(new \DateTime("2024-06-01"));
                $saisonActuelle->setDateE(new \DateTime("2024-09-01"));

                $em->persist($saisonActuelle);
                $em->flush();

                dump("Nouvelle saison créée :", $saisonActuelle);
            }
        }

        // Récupération des logements
        $logements = $logementRepository->findAll();
        dump("Logements trouvés :", $logements);

        $logementsAvecPrix = [];

        // Parcours des logements pour associer un tarif si disponible
        foreach ($logements as $logement) {
            if (!$logement) {
                continue;
            }
        
            // Vérification de la saison et récupération du tarif
            $price = "Tarif indisponible";
            if ($saisonActuelle) {
                $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
                dump("Tarif récupéré pour logement ID {$logement->getId()} :", $tarif);
        
                if ($tarif) {
                    $price = $tarif->getPrice();
                }
            }
        
            $logementsAvecPrix[] = [
                'logement' => $logement,
                'price' => $price
            ];
        }

        // Vérification finale avant affichage
        dump("Logements avec tarifs :", $logementsAvecPrix);

        return $this->render('home/index.html.twig', [
            'saison' => $saisonActuelle,
            'logements' => $logements,
            'logementsAvecPrix' => $logementsAvecPrix
        ]);
    }
    
    #[Route('/logement/{id}', name: 'logement_detail')]
    public function logementById(
        LogementRepository $logementRepository,
        TarifRepository $tarifRepository,
        SaisonRepository $saisonRepository,
        EquipementRepository $equipementRepository,
        EntityManagerInterface $em,
        Request $request,  // On prend bien Request
        int $id
    ): Response {

        // Récupérer le logement par son ID
        $logement = $logementRepository->find($id);

        // Vérifier si le logement existe
        if (!$logement) {
            throw $this->createNotFoundException('Logement non trouvé');
        }

        // Récupérer la saison actuelle
        $saisonActuelle = $saisonRepository->findSeason();

        // Si aucune saison actuelle n'est trouvée, rechercher une saison par défaut
        if (!$saisonActuelle) {
            // Logique pour la saison actuelle (comme dans ton exemple)
        }

        // Récupérer les équipements
        $equipements = $logement->getEquipements();

        // Récupérer le tarif du logement
        $tarif = null;
        if ($saisonActuelle) {
            $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
        }

        $price = $tarif ? $tarif->getPrice() : "Tarif indisponible";

        // Créer un objet de réservation
        $reservation = new Rental();
        $reservationForm = $this->createForm(RentalType::class, $reservation);

       // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if ($user) {
        // Utiliser la méthode `setUsers` pour lier l'utilisateur à la réservation
                $reservation->setUsers($user);  // Correction ici, il faut utiliser `setUsers`
        }


        // Gérer la soumission du formulaire
        $reservationForm->handleRequest($request);
        if ($reservationForm->isSubmitted() && $reservationForm->isValid()) {
            // Lier la réservation au logement
            $reservation->setLogement($logement);
            $em->persist($reservation);
            $em->flush();

            // Message de succès
            $this->addFlash('success', 'Réservation effectuée avec succès!');
            
            // Redirection après soumission
            return $this->redirectToRoute('logement_detail', ['id' => $id]);
        }

        // Passer les données à la vue
        return $this->render('home/details.html.twig', [
            'logement' => $logement,
            'saison' => $saisonActuelle,
            'price' => $price,
            'equipements' => $equipements,
            'reservationForm' => $reservationForm->createView(),
        ]);
    }
}

