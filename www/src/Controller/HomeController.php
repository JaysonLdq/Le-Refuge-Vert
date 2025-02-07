<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\SaisonRepository;
use App\Repository\LogementRepository;
use App\Repository\EquipementRepository;
use App\Repository\RentalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    // Injection des repositories nécessaires
    private $userRepo;
    private $seasonRepo;
    private $logementRepo;
    private $equipmentRepo;
    private $rentalRepo;

    public function __construct(
        UserRepository $userRepo,
        SaisonRepository $seasonRepo,
        LogementRepository $logementRepo,
        EquipementRepository $equipmentRepo,
        RentalRepository $rentalRepo
    ) {
        // On initialise les repositories
        $this->userRepo = $userRepo;
        $this->seasonRepo = $seasonRepo;
        $this->logementRepo = $logementRepo;
        $this->equipmentRepo = $equipmentRepo;
        $this->rentalRepo = $rentalRepo;
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Récupérer les données depuis les repositories
        $users = $this->userRepo->findAll();
        $seasons = $this->seasonRepo->findAll();
        $logements = $this->logementRepo->findAll();
        $equipments = $this->equipmentRepo->findAll();
        $rentals = $this->rentalRepo->findAll();

        // Passer les données à la vue
        return $this->render('home/index.html.twig', [
            'users' => $users,
            'seasons' => $seasons,
            'logements' => $logements,
            'equipments' => $equipments,
            'rentals' => $rentals,
        ]);
    }
}
