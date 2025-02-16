<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Rental;
use App\Entity\Saison;
use App\Form\UserType;
use App\Form\ProfilType;
use App\Form\RentalType;
use App\Repository\TarifRepository;
use App\Repository\RentalRepository;
use App\Repository\SaisonRepository;
use Symfony\Component\Form\FormError;
use App\Repository\LogementRepository;
use App\Repository\EquipementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        // Récupérer la saison active (celle qui correspond à la période actuelle)
        $saisonActuelle = $saisonRepository->findSeasonsActiveOnCurrentDate(); // Tu peux utiliser la méthode que tu as déjà dans le SaisonRepository
    
        if (!$saisonActuelle) {
            // Si aucune saison active n'est trouvée, définir une saison par défaut (par exemple Haute saison)
            $saisonActuelle = $saisonRepository->findOneBy(['label' => 'Haute saison']);
        }
    
        // Récupérer tous les tarifs existants
        $tarifs = $tarifRepository->findAll();
    
        // Mettre à jour les tarifs avec la saison active
        foreach ($tarifs as $tarif) {
            // Mettre à jour la saison pour chaque tarif si la saison du tarif ne correspond pas déjà à la saison actuelle
            if ($tarif->getSaison() !== $saisonActuelle) {
                $tarif->setSaison($saisonActuelle);
                $em->persist($tarif); // Persist les changements
            }
        }
    
        // Sauvegarder les changements dans la base de données
        $em->flush();
    
        // Récupérer tous les logements et leur prix pour l'affichage
        $logements = $logementRepository->findAll();
    
        // Mettre à jour les prix des logements en fonction des tarifs actuels
        $logementsAvecPrix = array_map(function($logement) use ($tarifRepository, $saisonActuelle) {
            $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
            $price = $tarif ? $tarif->getPrice() : "Tarif indisponible";
            return ['logement' => $logement, 'price' => $price];
        }, $logements);
    
        return $this->render('home/index.html.twig', [
            'saison' => $saisonActuelle,  // Affichage de la saison actuelle
            'logementsAvecPrix' => $logementsAvecPrix,
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
    
        // Récupérer la saison actuelle
        $saisonActuelle = $saisonRepository->findSeason();
        

        // Trouver le tarif pour le logement et la saison actuelle
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
    
        // Calcul automatique du nombre de jours avant soumission
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
            'equipements' => $equipements, 
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
    $form = $this->createForm(ProfilType::class, $user, ['is_edit' => true]);
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

#[Route('/reservation/{id}/delete', name: 'app_reservation_delete', methods: ['GET', 'POST'])]
public function delete(Request $request, RentalRepository $rentalRepository, EntityManagerInterface $entityManager, int $id): Response
{
    $rental = $rentalRepository->find($id);

    if (!$rental) {
        return $this->render('rental/error.html.twig', [
            'message' => 'Réservation non trouvée',
        ]);
    }

    if ($request->isMethod('POST')) {
        $entityManager->remove($rental);
        $entityManager->flush();

        return $this->redirectToRoute('logement_detail');
    }

    return $this->render('rental/delete.html.twig', [
        'rental' => $rental,
    ]);
}
}

