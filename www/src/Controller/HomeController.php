<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SaisonRepository;  // Utiliser le repository
use App\Repository\LogementRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(SaisonRepository $saisonRepository, LogementRepository $logementRepository): Response
    {
       
        // Récupérer la saison actuelle
        $saisonActuelle = $saisonRepository->findSeason();

        // Récupérer les logements
        $logements = $logementRepository->findAll();

        return $this->render('home/index.html.twig', [
            'saison' => $saisonActuelle,
            'logements' => $logements
        ]);
    }
}
