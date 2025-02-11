<?php

namespace App\Controller;

use App\Entity\Rental;
use App\Entity\Saison;
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
    // 📌 Afficher la liste des réservations dans une vue Twig
    #[Route('/admin/rentals', name: 'app_rental_list', methods: ['GET'])]
    public function index(RentalRepository $rentalRepository): Response
    {
        $rentals = $rentalRepository->findAll();

        return $this->render('rental/index.html.twig', [
            'rentals' => $rentals,
        ]);
    }

    // 📌 Afficher une réservation spécifique
    #[Route('/admin/rental/{id<\d+>}', name: 'app_rental_show', methods: ['GET'])]
    public function show(Rental $rental, SaisonRepository $saisonRepository, TarifRepository $tarifRepository, EntityManagerInterface $em, LogementRepository $logementRepository): Response
    {
        // Récupérer le logement associé à la réservation
        $logement = $rental->getLogement();
        if (!$logement) {
            throw $this->createNotFoundException('Logement non trouvé');
        }
    
        // Récupérer la saison actuelle
        $saisonActuelle = $saisonRepository->findSeason();
    
        // Si aucune saison trouvée, chercher une saison par défaut
        if (!$saisonActuelle) {
            $saisonActuelle = $saisonRepository->createQueryBuilder('s')
                ->where('s.label IN (:defaultSeasons)')
                ->setParameter('defaultSeasons', ['Haute saison', 'Basse saison', 'Hors saison'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
    
            // Si toujours rien, créer une saison "Haute saison"
            if (!$saisonActuelle) {
                $saisonActuelle = new Saison();
                $saisonActuelle->setLabel("Haute saison");
                $saisonActuelle->setDateS(new \DateTime("2024-06-01"));
                $saisonActuelle->setDateE(new \DateTime("2024-09-01"));
    
                $em->persist($saisonActuelle);
                $em->flush();
            }
        }
    
        // Récupérer le tarif du logement en fonction de la saison
        $tarif = $saisonActuelle ? $tarifRepository->findTarif($logement, $saisonActuelle) : null;
        $price = $tarif ? $tarif->getPrice() : "Tarif indisponible";
    
        return $this->render('rental/show.html.twig', [
            'rental' => $rental,
            'logement' => $logement,
            'price' => $price,
        ]);
    }
    

    // 📌 Formulaire pour créer une nouvelle réservation
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



    // 📌 Formulaire pour mettre à jour une réservation
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


    // 📌 Supprimer une réservation avec confirmation
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

    
}
