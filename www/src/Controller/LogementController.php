<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Rental;
use App\Entity\Saison;
use App\Entity\Tarif;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use App\Repository\SaisonRepository;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    
        // Récupérer la saison active (celle qui correspond à la période actuelle)
        $saisonActuelle = $saisonRepository->findSeasonsActiveOnCurrentDate();
    
        if (!$saisonActuelle) {
            // Si aucune saison active n'est trouvée, définir une saison par défaut
            $saisonActuelle = $saisonRepository->findOneBy(['label' => 'Haute saison']);
        }
    
        // Récupérer les logements avec leurs prix associés
        $logements = $logementRepository->findAll();
    
        // Appliquer la saison aux tarifs des logements
        $logementsAvecPrix = array_map(function($logement) use ($tarifRepository, $saisonActuelle) {
            $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
            $price = $tarif ? $tarif->getPrice() : "Tarif indisponible";
    
            // Si la saison est la haute saison, appliquer une augmentation de 20%
            if ($saisonActuelle->getLabel() === 'Haute saison' && $tarif) {
                $price = $tarif->getPrice() * 1.2;  // Augmenter de 20%
            }
    
            // Si la saison est la basse saison, appliquer une réduction de 20%
            if ($saisonActuelle->getLabel() === 'Basse saison' && $tarif) {
                $price = number_format($tarif->getPrice() / 1.2, 2);  // Réduire de 20%
            }
    
            return ['logement' => $logement, 'price' => $price];
        }, $logements);
    
        // Passer la saison active et les logements avec leurs prix à la vue
        return $this->render('logement/index.html.twig', [
            'saison' => $saisonActuelle,  // Affichage de la saison actuelle
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
public function new(Request $request, LogementRepository $logementRepository, EntityManagerInterface $em, TarifRepository $tarifRepository, SaisonRepository $saisonRepository): Response
{
    $logement = new Logement();
    $form = $this->createForm(LogementType::class, $logement);
    $form->handleRequest($request);
    // Récupérer la saison active (celle qui correspond à la période actuelle)
    $saisonActuelle = $saisonRepository->findSeasonsActiveOnCurrentDate();
    
    if (!$saisonActuelle) {
        // Si aucune saison active n'est trouvée, définir une saison par défaut
        $saisonActuelle = $saisonRepository->findOneBy(['label' => 'Haute saison']);
    }

    // Ajouter un status de base 
    $logement->setStatus('pending');
    

    if ($form->isSubmitted() && $form->isValid()) {
        // Gestion de l'image (si applicable)
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('logement_images_directory'),
                $newFilename
            );
            $logement->setImagePath($newFilename);
        }

        // Enregistrer le logement dans la base de données
        $logementRepository->save($logement, true);

        // Créer un tarif pour le logement (par exemple, un tarif par défaut)
        $tarif = new Tarif();
        $tarif->setLogement($logement);  // Lier le tarif au logement
        $tarif->setSaison($saisonActuelle);  // Associe la saison actuelle, si applicable
        $tarif->setPrice(100);  // Défini un prix par défaut (tu peux aussi le récupérer du formulaire)

        // Enregistrer le tarif dans la base de données
        $em->persist($tarif);
        $em->flush();

        // Message de succès
        $this->addFlash('success', 'Logement et tarif ajouté avec succès.');

        // Rediriger vers la page de gestion des logements
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


    #[Route("/api/logements/{id}/update-status", name: "api_update_logement_status", methods: ["PATCH"])]
    /**
     * @Route("/api/logements/{id}/update-status", name="api_update_logement_status", methods={"PATCH"})
     */
    public function updateStatus($id, Request $request, EntityManagerInterface $em)
    {
        
        // Récupérer l'entité Logement par son ID
        $logement = $em->getRepository(Logement::class)->find($id);

        // Si le logement n'existe pas, retourner une erreur
        if (!$logement) {
            return new JsonResponse(['status' => 'error', 'message' => 'Logement non trouvé'], 404);
        }

        // Récupérer le statut envoyé dans la requête

        $status = $request->get('status');
        dump($status);
        // Vérifier si le statut est valide
        if (!in_array($status, ['pending', 'available', 'unavailable'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Statut invalide'], 400);
        }

        // Mettre à jour le statut du logement
        $logement->setStatus($status);
        
        // Sauvegarder les changements
        $em->flush();

        // Retourner une réponse JSON indiquant que la mise à jour a réussi
        return new JsonResponse(['status' => 'success', 'message' => 'Statut mis à jour']);
    }
}
