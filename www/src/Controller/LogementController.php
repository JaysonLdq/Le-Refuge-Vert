<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogementController extends AbstractController
{
    #[Route('/logement', name: 'app_logement')]
    public function index(): Response
    {
        return $this->render('logement/index.html.twig', [
            'controller_name' => 'LogementController',
        ]);
    }
}
