<?php
namespace App\Controller;

use App\Entity\Rental;
use App\Form\ProfilType;
use App\Form\RentalType;
use App\Repository\TarifRepository;
use App\Repository\RentalRepository;
use App\Repository\SaisonRepository;
use Symfony\Component\Form\FormError;
use App\Repository\LogementRepository;
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
    EntityManagerInterface $em,
    Request $request
): Response {
    // Récupérer la saison active (celle qui correspond à la période actuelle)
    $saisonActuelle = $saisonRepository->findSeasonsActiveOnCurrentDate(); 

    if (!$saisonActuelle) {
        $saisonActuelle = $saisonRepository->findOneBy(['label' => 'Haute saison']);
    }

    // Récupérer tous les tarifs existants
    $tarifs = $tarifRepository->findAll();

    // Mettre à jour les tarifs avec la saison active
    foreach ($tarifs as $tarif) {
        if ($tarif->getSaison() !== $saisonActuelle) {
            $tarif->setSaison($saisonActuelle);
            $em->persist($tarif); 
        }
    }
    $em->flush();

    // Récupérer tous les logements
    $logements = $logementRepository->findAll();

    // Filtrage des logements par prix croissant ou décroissant
    $priceOrder = $request->query->get('price_order', 'asc'); // asc par défaut
    if ($priceOrder === 'desc') {
        usort($logements, function($a, $b) use ($tarifRepository, $saisonActuelle) {
            $priceA = $tarifRepository->findTarif($a, $saisonActuelle)->getPrice();
            $priceB = $tarifRepository->findTarif($b, $saisonActuelle)->getPrice();
            return $priceB <=> $priceA;
        });
    } else {
        usort($logements, function($a, $b) use ($tarifRepository, $saisonActuelle) {
            $priceA = $tarifRepository->findTarif($a, $saisonActuelle)->getPrice();
            $priceB = $tarifRepository->findTarif($b, $saisonActuelle)->getPrice();
            return $priceA <=> $priceB;
        });
    }

    // Filtrer par type de logement si sélectionné
    $typeFilter = $request->query->get('type', null);
    if ($typeFilter) {
        $logements = array_filter($logements, function($logement) use ($typeFilter) {
            return $logement->getLabel() === $typeFilter;
        });
    }

    // Filtrer par disponibilité
    $dateStart = $request->query->get('date_start');
    $dateEnd = $request->query->get('date_end');
    if ($dateStart && $dateEnd) {
        $dateStart = new \DateTime($dateStart);
        $dateEnd = new \DateTime($dateEnd);

        // Filtrer les logements en fonction de la disponibilité
        $logements = array_filter($logements, function($logement) use ($dateStart, $dateEnd) {
            // Récupérer les réservations du logement
            $reservations = $logement->getRentals();  
            
            foreach ($reservations as $reservation) {
                $reservationStart = $reservation->getDateStart();
                $reservationEnd = $reservation->getDateEnd();

                // Vérifie si les dates de début et de fin se chevauchent
                if (($dateStart >= $reservationStart && $dateStart <= $reservationEnd) || 
                    ($dateEnd >= $reservationStart && $dateEnd <= $reservationEnd)) {
                    return false; // Le logement est réservé pendant cette période
                }
            }
            return true; // Le logement est disponible
        });
    }

    // Récupérer les prix des logements en fonction des tarifs actuels
    $logementsAvecPrix = array_map(function($logement) use ($tarifRepository, $saisonActuelle) {
        $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
        $price = $tarif ? $tarif->getPrice() : "Tarif indisponible";

        // Ajuster le prix en fonction de la saison
        if ($saisonActuelle->getLabel() === 'Haute saison' && $tarif) {
            $price = $tarif->getPrice() * 1.2;  // Augmentation de 20% pour la haute saison
        }

        if ($saisonActuelle->getLabel() === 'Basse saison' && $tarif) {
            $price = number_format($tarif->getPrice() / 1.2, 2);  // Réduction de 20% pour la basse saison
        }

        return ['logement' => $logement, 'price' => $price];
    }, $logements);

    return $this->render('home/index.html.twig', [
        'saison' => $saisonActuelle,
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

    // Récupérer la saison active
    $saisonActuelle = $saisonRepository->findSeasonsActiveOnCurrentDate();

    if (!$saisonActuelle) {
        // Si aucune saison active n'est trouvée, définir une saison par défaut
        $saisonActuelle = $saisonRepository->findOneBy(['label' => 'Haute saison']);
    }

    // Trouver le tarif pour le logement et la saison actuelle
    $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
    $pricePerNight = $tarif ? $tarif->getPrice() : 0;

    // Si la saison est la haute saison, appliquer une augmentation de 20%
    if ($saisonActuelle->getLabel() === 'Haute saison' && $tarif) {
        $pricePerNight = $tarif->getPrice() * 1.2;  // Augmenter de 20%
    }

    // Si la saison est la basse saison, appliquer une réduction de 20%
    if ($saisonActuelle->getLabel() === 'Basse saison' && $tarif) {
        $pricePerNight = number_format($tarif->getPrice() / 1.2, 2);  // Réduire de 20%
    }

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

    // Récupérer la période "Hors saison"
    $offSeasonStart = $saisonRepository->findOneBy(['label' => 'Hors saison']) ? $saisonRepository->findOneBy(['label' => 'Hors saison'])->getDateS() : null;
    $offSeasonEnd = $saisonRepository->findOneBy(['label' => 'Hors saison']) ? $saisonRepository->findOneBy(['label' => 'Hors saison'])->getDateE() : null;
    $nextSeasonStartDate = $saisonRepository->findOneBy(['label' => 'Haute saison'])->getDateS();
    dump($offSeasonStart, $offSeasonEnd, $nextSeasonStartDate);

    // Vérifier si les dates choisies sont dans la période "Hors saison"
    if ($offSeasonStart && $offSeasonEnd && $dateStart >= $offSeasonStart && $dateEnd <= $offSeasonEnd) {
        $reservationForm->addError(new FormError(
            'Vous ne pouvez pas réserver pour cette période. Veuillez choisir une autre date a partir du '  . $nextSeasonStartDate->format('d-m-Y')
        ));
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
    $saisonActuelle = $saisonRepository->findSeasonsActiveOnCurrentDate();  // Ici, on récupère la saison actuelle

    if (!$saisonActuelle) {
        // Si aucune saison active n'est trouvée, définir une saison par défaut
        $saisonActuelle = $saisonRepository->findOneBy(['label' => 'Haute saison']);
    }

    // Récupérer le tarif
    $pricePerNight = 0;
    if ($saisonActuelle) {
        $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
        if ($tarif) {
            $pricePerNight = $tarif->getPrice();
        }
    }

    // Appliquer l'ajustement de prix basé sur la saison
    if ($saisonActuelle->getLabel() === 'Haute saison' && $tarif) {
        $pricePerNight = $tarif->getPrice() * 1.2;  // Augmenter de 20% pour la haute saison
    }

    // Appliquer l'ajustement de prix pour la basse saison
    if ($saisonActuelle->getLabel() === 'Basse saison' && $tarif) {
        $pricePerNight = number_format($tarif->getPrice() / 1.2, 2);  // Réduire de 20% pour la basse saison
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

           // Rediriger vers le détail du logement associé à la réservation
           return $this->redirectToRoute('logement_detail', ['id' => $rental->getLogement()->getId()]);
        
    }

    return $this->render('rental/delete.html.twig', [
        'rental' => $rental,
    ]);
}
}

