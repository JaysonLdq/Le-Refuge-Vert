<?php 

namespace App\Controller;

use App\Entity\Saison;
use App\Entity\Tarif;
use App\Form\SaisonType;
use App\Repository\SaisonRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/saison')]
final class SaisonController extends AbstractController
{
    #[Route(name: 'app_saison_index', methods: ['GET'])]
    public function index(SaisonRepository $saisonRepository): Response
    {
        return $this->render('saison/index.html.twig', [
            'saisons' => $saisonRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_saison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $saison = new Saison();
        $form = $this->createForm(SaisonType::class, $saison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($saison);
            $entityManager->flush();

            return $this->redirectToRoute('app_saison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('saison/new.html.twig', [
            'saison' => $saison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_saison_show', methods: ['GET'])]
    public function show(Saison $saison): Response
    {
        return $this->render('saison/show.html.twig', [
            'saison' => $saison,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_saison_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Saison $saison, EntityManagerInterface $entityManager): Response
{
    // Utilise directement l'ID passé dans l'URL
    $id = $saison->getId(); // Pas besoin de chercher à nouveau la saison via le repository

    $form = $this->createForm(SaisonType::class, $saison);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        return $this->redirectToRoute('app_saison_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('saison/edit.html.twig', [
        'saison' => $saison,
        'form' => $form->createView(), // Assure-toi de passer le formulaire à la vue
    ]);
}


    #[Route('/{id}', name: 'app_saison_delete', methods: ['POST'])]
    public function delete(Request $request, Saison $saison, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$saison->getId(), $request->get('_token'))) {
            $entityManager->remove($saison);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_saison_index', [], Response::HTTP_SEE_OTHER);
    }

    // Route pour sélectionner une saison et l'affecter à tous les tarifs
    #[Route('/{id}/select', name: 'app_saison_select', methods: ['POST'])]
    public function select(Saison $saison, TarifRepository $tarifRepository, EntityManagerInterface $entityManager): Response
    {
        // Mettre à jour tous les tarifs en associant la saison sélectionnée
        $tarifs = $tarifRepository->findAll();

        foreach ($tarifs as $tarif) {
            $tarif->setSaison($saison);
            $entityManager->persist($tarif);
        }

        // Sauvegarder les changements dans la base de données
        $entityManager->flush();

        // Ajouter un message flash de succès
        $this->addFlash('success', 'La saison a été mise à jour pour tous les tarifs.');

        // Rediriger l'utilisateur vers la page des saisons
        return $this->redirectToRoute('app_saison_index');
    }
}
