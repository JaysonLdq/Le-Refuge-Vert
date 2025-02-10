<?php

namespace App\Controller;

use App\Entity\Rental;
use App\Repository\RentalRepository;
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
    #[Route('/admin/rental/{id}', name: 'app_rental_show', methods: ['GET'])]
    public function show(RentalRepository $rentalRepository, int $id): Response
    {
        $rental = $rentalRepository->find($id);

        if (!$rental) {
            return $this->render('rental/error.html.twig', [
                'message' => 'Réservation non trouvée',
            ]);
        }

        return $this->render('rental/show.html.twig', [
            'rental' => $rental,
        ]);
    }

    // 📌 Formulaire pour créer une nouvelle réservation
    #[Route('/admin/rental/new', name: 'app_rental_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $rental = new Rental();
            $rental->setUsers($data['users_id']);
            $rental->setLogement($data['logement_id']);
            $rental->setDateStart(new \DateTime($data['date_start']));
            $rental->setDateEnd(new \DateTime($data['date_end']));
            $rental->setNbAdulte($data['nb_adulte']);
            $rental->setNbChild($data['nb_child']);

            $entityManager->persist($rental);
            $entityManager->flush();

            return $this->redirectToRoute('app_rental_list');
        }

        return $this->render('rental/new.html.twig');
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

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            if (isset($data['users_id'])) $rental->setUsers($data['users_id']);
            if (isset($data['logement_id'])) $rental->setLogement($data['logement_id']);
            if (isset($data['date_start'])) $rental->setDateStart(new \DateTime($data['date_start']));
            if (isset($data['date_end'])) $rental->setDateEnd(new \DateTime($data['date_end']));
            if (isset($data['nb_adulte'])) $rental->setNbAdulte($data['nb_adulte']);
            if (isset($data['nb_child'])) $rental->setNbChild($data['nb_child']);

            $entityManager->flush();

            return $this->redirectToRoute('app_rental_list');
        }

        return $this->render('rental/edit.html.twig', [
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
