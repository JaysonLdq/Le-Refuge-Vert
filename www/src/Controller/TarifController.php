<?php

namespace App\Controller;

use App\Entity\Tarif;
use App\Form\TarifType;
use App\Repository\LogementRepository;
use App\Repository\SaisonRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tarif')]
final class TarifController extends AbstractController
{
    #[Route(name: 'app_tarif_index', methods: ['GET'])]
    public function index(
        TarifRepository $tarifRepository,
        LogementRepository $logementRepository,
        SaisonRepository $saisonRepository
    ): Response {
        // Récupérer toutes les données de la table Tarif
        $tarifs = $tarifRepository->findAll();
    
        // Récupérer toutes les données de la table Logement
        $logements = $logementRepository->findAll();
    
        // Récupérer toutes les données de la table Saison
        $saisons = $saisonRepository->findAll();
    
        return $this->render('tarif/index.html.twig', [
            'tarifs' => $tarifs,
            'logements' => $logements,
            'saisons' => $saisons,
        ]);
    }
    

    #[Route('/new', name: 'app_tarif_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tarif = new Tarif();
        $form = $this->createForm(TarifType::class, $tarif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tarif);
            $entityManager->flush();

            return $this->redirectToRoute('app_tarif_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tarif/new.html.twig', [
            'tarif' => $tarif,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tarif_show', methods: ['GET'])]
    public function show(Tarif $tarif): Response
    {
        return $this->render('tarif/show.html.twig', [
            'tarif' => $tarif,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tarif_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    Tarif $tarif,
    EntityManagerInterface $entityManager,
    LogementRepository $logementRepository,
    SaisonRepository $saisonRepository
): Response {
    // Récupérer toutes les données de la table Logement
    $logements = $logementRepository->findAll();

    // Récupérer toutes les données de la table Saison
    $saisons = $saisonRepository->findAll();

    $form = $this->createForm(TarifType::class, $tarif);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        return $this->redirectToRoute('app_tarif_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('tarif/edit.html.twig', [
        'tarif' => $tarif,
        'form' => $form,
        'logements' => $logements,  // Passer les données Logement à la vue
        'saisons' => $saisons,      // Passer les données Saison à la vue
    ]);
}


    #[Route('/{id}', name: 'app_tarif_delete', methods: ['POST'])]
    public function delete(Request $request, Tarif $tarif, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tarif->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tarif);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tarif_index', [], Response::HTTP_SEE_OTHER);
    }
}
