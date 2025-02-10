<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

 #[Route('/admin')]
class AdminController extends AbstractController
{
   
     /**
     * méthode qui renvoit la page d'accueil de l'admin
     * @Route("/dashboard", name="app_admin_dashboard")
     * @return Response
     */
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard():Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
}
