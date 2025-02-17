<?php
namespace App\Controller;

use App\Entity\Rental;
use App\Entity\Saison;
use App\Entity\User;
use App\Form\RentalType;
use App\Repository\LogementRepository;
use App\Repository\RentalRepository;
use App\Repository\SaisonRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RentalController extends AbstractController
{
    //  Afficher la liste des réservations dans une vue Twig
    #[Route('/admin/rentals', name: 'app_rental_list', methods: ['GET'])]
    public function index(RentalRepository $rentalRepository, LogementRepository $logementRepository): Response
{
    //on recupere les logements 
    $logements = $logementRepository->findAll();
    $rentals = $rentalRepository->findAll();
    

    // Vérifie s'il y a une réservation
    foreach ($rentals as $rental) {
        // Utilisez la méthode getUsers() au lieu de getUser()
        $user = $rental->getUsers(); // Récupérer l'utilisateur associé à la réservation
        
        if ($user instanceof User) {
            $firstname = $user->getFirstname();
            $lastname = $user->getLastname();
            $phone = $user->getPhone();
        } else {
            $firstname = null;
            $lastname = null;
        }
    }

    return $this->render('rental/index.html.twig', [
        'rentals' => $rentals,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'phone' => $phone,
        'logements' => $logements,
    ]);
}


    //  Afficher une réservation spécifique
    #[Route('/admin/rental/{id<\d+>}', name: 'app_rental_show', methods: ['GET'])]
    public function show(Rental $rental, SaisonRepository $saisonRepository, TarifRepository $tarifRepository, EntityManagerInterface $em, LogementRepository $logementRepository): Response
    {
        $user = $this->getUser();
    
        // Vérifie si l'utilisateur est bien une instance de User
        if ($user instanceof User) {
            $firstname = $user->getFirstname();
            $lastname = $user->getLastname();
            $phone = $user->getPhone();
        } else {
            $firstname = null;
            $lastname = null;
        }
    
        // Récupérer le logement associé à la réservation
        $logement = $rental->getLogement();
        if (!$logement) {
            throw $this->createNotFoundException('Logement non trouvé');
        }
    
        // Récupérer la saison active (celle qui correspond à la période actuelle)
        $saisonActuelle = $saisonRepository->findSeasonsActiveOnCurrentDate();
    
        if (!$saisonActuelle) {
            // Si aucune saison active n'est trouvée, définir une saison par défaut
            $saisonActuelle = $saisonRepository->findOneBy(['label' => 'Haute saison']);
        }
    
        // Récupérer le tarif du logement en fonction de la saison active
        $tarif = $saisonActuelle ? $tarifRepository->findTarif($logement, $saisonActuelle) : null;
        $price = $tarif ? $tarif->getPrice() : "Tarif indisponible";
    
        // Appliquer un ajustement de prix en fonction de la saison
        if ($saisonActuelle->getLabel() === 'Haute saison' && $tarif) {
            $price = $tarif->getPrice() * 1.2;  // Appliquer une augmentation de 20% pour la haute saison
        }
    
        if ($saisonActuelle->getLabel() === 'Basse saison' && $tarif) {
            $price = number_format($tarif->getPrice() / 1.2, 2);  // Appliquer une réduction de 20% pour la basse saison
        }
    
        return $this->render('rental/show.html.twig', [
            'rental' => $rental,
            'logement' => $logement,
            'price' => $price,
            'user' => $user,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone' => $phone,
            'saison' => $saisonActuelle,  // Passer la saison actuelle à la vue
        ]);
    }
    
    //  Formulaire pour créer une nouvelle réservation
    #[Route('/admin/rental/new', name: 'app_rental_new', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rental = new Rental();
        $form = $this->createForm(RentalType::class, $rental);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rental);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation ajoutée avec succès.');
            return $this->redirectToRoute('app_rental_list'); // Vérifie bien que tu rediriges vers la bonne route
        }

        return $this->render('rental/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    //  Formulaire pour mettre à jour une réservation
    #[Route('/admin/rental/{id}/edit', name: 'app_rental_edit', methods: ['GET', 'POST'])]
    public function update(Request $request, RentalRepository $rentalRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $rental = $rentalRepository->find($id);

        if (!$rental) {
            return $this->render('rental/error.html.twig', [
                'message' => 'Réservation non trouvée',
            ]);
        }

        // Créer le formulaire basé sur RentalType
        $form = $this->createForm(RentalType::class, $rental);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation mise à jour avec succès.');
            return $this->redirectToRoute('app_rental_list');
        }

        return $this->render('rental/edit.html.twig', [
            'form' => $form->createView(), // Passe le formulaire à la vue
            'rental' => $rental,
        ]);
    }

    //  Supprimer une réservation avec confirmation
    #[Route('/admin/rental/{id}/delete', name: 'app_rental_delete', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('app_rental_list');
        }

        return $this->render('rental/delete.html.twig', [
            'rental' => $rental,
        ]);
    }

   // Afficher les réservations d'un utilisateur
#[Route('/rentals', name: 'app_user_rentals', methods: ['GET'])]
public function userRentals(RentalRepository $rentalRepository): Response
{
    $user = $this->getUser();

    // Vérifie si l'utilisateur est bien une instance de User
    if ($user instanceof User) {
        $firstname = $user->getFirstname();
        $lastname = $user->getLastname();
        $phone = $user->getPhone();
    } else {
        $firstname = null;
        $lastname = null;
    }

    // Récupérer toutes les réservations de l'utilisateur connecté
    $rentals = $rentalRepository->findBy(['users' => $user]);

    // Récupérer la date actuelle
    $currentDate = new \DateTime();

    // Créer des tableaux pour les différentes catégories de réservations
    $upcomingRentals = [];
    $currentRentals = [];
    $pastRentals = [];

    foreach ($rentals as $rental) {
        // Vérifier si la réservation est en cours
        $isBetweenDates = $currentDate >= $rental->getDateStart() && $currentDate <= $rental->getDateEnd();

        // Si la réservation est en cours
        if ($isBetweenDates) {
            $currentRentals[] = $rental;
        } 
        // Si la réservation est à venir
        elseif ($rental->getDateStart() > $currentDate) {
            $upcomingRentals[] = $rental;
        } 
        // Si la réservation est passée
        else {
            $pastRentals[] = $rental;
        }
    }

   // Créer une variable pour savoir si le bouton de suppression doit être affiché
$rentalsWithCancelButton = [];
foreach ($upcomingRentals as $rental) {
    // Vérifie la différence de dates entre la date actuelle et la date de fin de la réservation
    $dateDiff = $currentDate->diff($rental->getDateEnd());

    // Si la différence en jours est supérieure à 2 jours (soit plus de 48 heures)
    $isBetweenDates = $currentDate >= $rental->getDateStart() && $currentDate <= $rental->getDateEnd();

    if ($dateDiff->days > 2 && !$isBetweenDates) {
        // Ajouter la réservation à la liste avec le bouton de suppression
        $rentalsWithCancelButton[] = $rental;
    }
}


    return $this->render('rental/user_rentals.html.twig', [
        'upcomingRentals' => $upcomingRentals,
        'currentRentals' => $currentRentals,
        'pastRentals' => $pastRentals,
        'rentalsWithCancelButton' => $rentalsWithCancelButton,
        'lastname' => $lastname,
        'phone' => $phone,
    ]);
}

}
