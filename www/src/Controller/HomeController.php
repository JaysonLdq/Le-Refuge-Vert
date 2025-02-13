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
use App\Entity\User;
use App\Form\RentalType;
use App\Form\UserType;
use App\Repository\EquipementRepository;
use App\Repository\RentalRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
        $saisonActuelle = $saisonRepository->findSeason() ??
            $saisonRepository->findOneBy(['label' => ['Haute saison', 'Basse saison', 'Hors saison']]);

        // Création d'une saison par défaut si aucune trouvée
        if (!$saisonActuelle) {
            $saisonActuelle = new Saison();
            $saisonActuelle->setLabel("Haute saison");
            $saisonActuelle->setDateS(new \DateTime("2024-06-01"));
            $saisonActuelle->setDateE(new \DateTime("2024-09-01"));
            $em->persist($saisonActuelle);
            $em->flush();
        }

        // Récupération des logements et de leur tarif
        $logements = $logementRepository->findAll();
        $logementsAvecPrix = array_map(function($logement) use ($tarifRepository, $saisonActuelle) {
            $tarif = $saisonActuelle ? $tarifRepository->findTarif($logement, $saisonActuelle) : null;
            return ['logement' => $logement, 'price' => $tarif ? $tarif->getPrice() : "Tarif indisponible"];
        }, $logements);

        return $this->render('home/index.html.twig', [
            'saison' => $saisonActuelle,
            'logementsAvecPrix' => $logementsAvecPrix
        ]);
    }

    #[Route('/logement/{id}', name: 'logement_detail', methods: ['GET', 'POST'])]
    public function logementById(
        LogementRepository $logementRepository,
        TarifRepository $tarifRepository,
        SaisonRepository $saisonRepository,
        RentalRepository $rentalRepository,
        EntityManagerInterface $em,
        Request $request,
        int $id
    ): Response {
        $logement = $logementRepository->find($id);
        if (!$logement) {
            throw $this->createNotFoundException('Logement non trouvé');
        }
    
        $saisonActuelle = $saisonRepository->findSeason() ??
            $saisonRepository->findOneBy(['label' => 'Haute saison']);
    
        $tarif = $saisonActuelle ? $tarifRepository->findTarif($logement, $saisonActuelle) : null;
        $pricePerNight = $tarif ? $tarif->getPrice() : 0;
    
        // Récupérer les équipements du logement
        $equipements = $logement->getEquipements();
    
        // Créer une réservation
        $reservation = new Rental();
        $reservationForm = $this->createForm(RentalType::class, $reservation);
        $reservationForm->handleRequest($request);
    
        // Initialisation des variables
        $daysCount = 0;
        $totalPrice = 0;
        $error = null;
    
        // Calcul automatique du nombre de jours AVANT soumission
        $dateStart = $reservation->getDateStart();
        $dateEnd = $reservation->getDateEnd();
    
        if ($dateStart && $dateEnd && $dateEnd > $dateStart) {
            $interval = $dateStart->diff($dateEnd);
            $daysCount = $interval->days;
            $totalPrice = $daysCount * $pricePerNight;
        }
    
        // Gestion de la soumission du formulaire
        if ($reservationForm->isSubmitted() && $reservationForm->isValid()) {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour réserver.');
                return $this->redirectToRoute('app_login');
            }
    
            // Vérifier que les dates sont valides
            if (!$dateStart || !$dateEnd || $dateEnd <= $dateStart) {
                $reservationForm->addError(new FormError('Les dates sélectionnées ne sont pas valides.'));
            } else {
                // Vérifier si la période est déjà réservée
                $existingReservations = $rentalRepository->createQueryBuilder('r')
                    ->where('r.logement = :logement')
                    ->andWhere('r.dateStart < :dateEnd')
                    ->andWhere('r.dateEnd > :dateStart')
                    ->setParameter('logement', $logement)
                    ->setParameter('dateStart', $dateStart)
                    ->setParameter('dateEnd', $dateEnd)
                    ->getQuery()
                    ->getResult();
    
                if (count($existingReservations) > 0) {
                    $reservationForm->addError(new FormError('Cette période est déjà réservée. Veuillez choisir une autre date.'));
                } else {
                    // Enregistrement de la réservation
                    $reservation->setUsers($user);
                    $reservation->setLogement($logement);
                    $em->persist($reservation);
                    $em->flush();
    
                
                    return $this->redirectToRoute('reservation_page', ['id' => $reservation->getId()]);
                }
            }
        }
    
        return $this->render('home/details.html.twig', [
            'logement' => $logement,
            'saison' => $saisonActuelle,
            'price' => $pricePerNight,
            'equipements' => $equipements, // ✅ Equipements ajoutés ici
            'nbDays' => $daysCount,
            'totalPrice' => $totalPrice,
            'reservationForm' => $reservationForm->createView(),
            'error' => $error
        ]);


    }
    

#[Route('/reservation/{id}', name: 'reservation_page', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]

public function update(
    Request $request,
    RentalRepository $rentalRepository,
    EntityManagerInterface $em,
    SaisonRepository $saisonRepository,
    TarifRepository $tarifRepository,
    int $id
): Response {
    $id = (int) $id;

    // Récupérer la réservation
    $rental = $rentalRepository->find($id);
    if (!$rental) {
        return $this->render('rental/error.html.twig', [
            'message' => 'Réservation non trouvée',
        ]);
    }

    // Récupérer le logement et la saison actuelle
    $logement = $rental->getLogement();
    $saisonActuelle = $saisonRepository->findSeason();
    if (!$saisonActuelle) {
        $saisonActuelle = $saisonRepository->createQueryBuilder('s')
            ->where('s.label IN (:defaultSeasons)')
            ->setParameter('defaultSeasons', ['Haute saison', 'Basse saison', 'Hors saison'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Récupérer le tarif
    $pricePerNight = 0;
    if ($saisonActuelle) {
        $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
        if ($tarif) {
            $pricePerNight = $tarif->getPrice();
        }
    }

    // Créer le formulaire
    $form = $this->createForm(RentalType::class, $rental);
    $form->handleRequest($request);

    // Initialiser les variables pour le calcul des jours et du prix total
    $daysCount = 0;
    $totalPrice = 0;
    $error = null;

    // Calcul automatique du nombre de jours avant soumission du formulaire
    $dateStart = $rental->getDateStart();
    $dateEnd = $rental->getDateEnd();

    if ($dateStart && $dateEnd && $dateEnd > $dateStart) {
        $interval = $dateStart->diff($dateEnd);
        $daysCount = $interval->days;
        $totalPrice = $daysCount * $pricePerNight;
    }

    // Gestion de la soumission du formulaire
    if ($form->isSubmitted() && $form->isValid()) {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une réservation.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si les dates sont valides avant d'enregistrer
        if (!$dateStart || !$dateEnd || $dateEnd <= $dateStart) {
            $form->addError(new FormError('Les dates sélectionnées ne sont pas valides.'));
        } else {
            // Vérifier les disponibilités
            $existingReservations = $rentalRepository->createQueryBuilder('r')
                ->where('r.logement = :logement')
                ->andWhere('r.dateStart < :dateEnd')
                ->andWhere('r.dateEnd > :dateStart')
                ->setParameter('logement', $logement)
                ->setParameter('dateStart', $dateStart)
                ->setParameter('dateEnd', $dateEnd)
                ->getQuery()
                ->getResult();

            if (count($existingReservations) > 0) {
                $form->addError(new FormError('Cette période est déjà réservée. Veuillez choisir une autre date.'));
            } else {
                // Enregistrer la réservation en base de données
                $rental->setUsers($user);
                $em->persist($rental);
                $em->flush();

                // Ajouter un message de succès et rediriger vers la page d'accueil
                $this->addFlash('success', 'Réservation confirmée avec succès!');
               
            }
        }
    }

    return $this->render('home/details-rental.html.twig', [
        'form' => $form->createView(),
        'rental' => $rental,
        'price' => $pricePerNight,
        'nbDays' => $daysCount,
        'totalPrice' => $totalPrice,
        'error' => $error,
    ]);
}

#[Route('/profil', name: 'app_profil', methods: ['GET', 'POST'])]   
public function profil(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('user/profil.html.twig', [
        'user' => $user,
        'form' => $form,
    ]);



}
}
