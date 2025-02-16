<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Saison;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use App\Repository\SaisonRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class LogementController extends AbstractController
{
    #[Route('/admin/logement', name: 'app_logement')]
    public function index(
        SaisonRepository $saisonRepository, 
        LogementRepository $logementRepository, 
        TarifRepository $tarifRepository,
        EntityManagerInterface $em
    ): Response {
       
         // Récupération de la saison actuelle
         $saisonActuelle = $saisonRepository->findSeason();

       

        // Récupération des logements avec leurs prix associés
        $logementsAvecPrix = $logementRepository->findAllWithPrices($tarifRepository, $saisonActuelle);

        return $this->render('logement/index.html.twig', [
            'saison' => $saisonActuelle,
            'logements' => $logementsAvecPrix
        ]);
    }

    #[Route('/admin/logement/delete/{id}', name: 'app_logement_delete', methods: ['POST'])]
    public function delete(LogementRepository $logementRepository, Logement $logement, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $logement->getId(), $request->request->get('_token'))) {
            $logementRepository->remove($logement, true);
            $this->addFlash('success', 'Logement supprimé avec succès.');
        }

        return $this->redirectToRoute('app_logement');
    }

    #[Route('/admin/logement/new', name: 'app_logement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, LogementRepository $logementRepository, EntityManagerInterface $em): Response
    {
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('logement_images_directory'),
                    $newFilename
                );
                $logement->setImagePath($newFilename);
            }

            $logementRepository->save($logement, true);
            $this->addFlash('success', 'Logement ajouté avec succès.');

            return $this->redirectToRoute('app_logement');
        }

        return $this->render('logement/new.html.twig', [
            'form' => $form->createView(),
            'logement' => $logement,
        ]);
    }

    #[Route('/logement/edit/{id}', name: 'app_logement_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id, 
        Request $request, 
        LogementRepository $logementRepository, 
        EntityManagerInterface $em
    ): Response
    {
        // Récupérer le logement à partir de l'ID via la fonction repository
        $logement = $logementRepository->find($id);

        // Vérifier si le logement existe
        if (!$logement) {
            throw $this->createNotFoundException('Le logement avec cet ID n\'existe pas.');
        }

        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        // Traitement du formulaire si soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image uploadée
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('logement_images_directory'), // Récupérer le paramètre du dossier
                    $newFilename
                );
                $logement->setImagePath($newFilename);
            }

            // Sauvegarder les modifications dans la base de données
            $em->persist($logement);
            $em->flush();

            $this->addFlash('success', 'Logement mis à jour avec succès !');

            return $this->redirectToRoute('app_logement');
        }

        return $this->render('logement/edit.html.twig', [
            'form' => $form->createView(),
            'logement' => $logement,
        ]);
    }
}
