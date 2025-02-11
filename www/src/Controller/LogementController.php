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
        dump("Saison Actuelle :", $saisonActuelle);

        // Si aucune saison actuelle n'est trouvée, rechercher une saison par défaut
        if (!$saisonActuelle) {
            dump("Aucune saison actuelle trouvée, recherche d'une saison par défaut...");

            $saisonActuelle = $saisonRepository->createQueryBuilder('s')
                ->where('s.label IN (:defaultSeasons)')
                ->setParameter('defaultSeasons', ['Haute saison', 'Basse saison', 'Hors saison'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // Si aucune saison par défaut n'existe, création d'une nouvelle saison "Haute saison"
            if (!$saisonActuelle) {
                dump("Aucune saison par défaut trouvée, création de 'Haute saison'...");

                $saisonActuelle = new Saison();
                $saisonActuelle->setLabel("Haute saison");
                $saisonActuelle->setDateS(new \DateTime("2024-06-01"));
                $saisonActuelle->setDateE(new \DateTime("2024-09-01"));

                $em->persist($saisonActuelle);
                $em->flush();

                dump("Nouvelle saison créée :", $saisonActuelle);
            }
        }

        // Récupération des logements
        $logements = $logementRepository->findAll();
        dump("Logements trouvés :", $logements);

        $logementsAvecPrix = [];

        // Parcours des logements pour associer un tarif si disponible
        foreach ($logements as $logement) {
            if (!$logement) {
                continue;
            }
        
            // Vérification de la saison et récupération du tarif
            $price = "Tarif indisponible";
            if ($saisonActuelle) {
                $tarif = $tarifRepository->findTarif($logement, $saisonActuelle);
                dump("Tarif récupéré pour logement ID {$logement->getId()} :", $tarif);
        
                if ($tarif) {
                    $price = $tarif->getPrice();
                }
            }
        
            $logementsAvecPrix[] = [
                'logement' => $logement,
                'price' => $price
            ];
        }

        // Vérification finale avant affichage
        dump("Logements avec tarifs :", $logementsAvecPrix);

        return $this->render('logement/index.html.twig', [
            'saison' => $saisonActuelle,
            'logements' => $logements,
            'logementsAvecPrix' => $logementsAvecPrix
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
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
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

    // ✅ Passe bien "logement" à la vue pour éviter l'erreur
    return $this->render('logement/new.html.twig', [
        'form' => $form->createView(),
        'logement' => $logement,  // Ajoute bien cette ligne
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
        // Récupérer le logement à partir de l'ID
        $logement = $logementRepository->find($id);

        // Vérifier si le logement existe
        if (!$logement) {
            throw $this->createNotFoundException('Le logement avec cet ID n\'existe pas.');
        }

        // Créer le formulaire d'édition pour le logement
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        // Traitement du formulaire si soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image uploadée
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                // Déplacer l'image dans le dossier spécifié
                $imageFile->move(
                    $this->getParameter('logement_images_directory'), // Récupérer le paramètre du dossier
                    $newFilename
                );
                // Mettre à jour le chemin de l'image dans l'entité
                $logement->setImagePath($newFilename);
            }

            // Sauvegarder les modifications dans la base de données
            $em->persist($logement);
            $em->flush();

            // Ajouter un message flash de succès
            $this->addFlash('success', 'Logement mis à jour avec succès !');

            // Rediriger vers la liste des logements après la mise à jour
            return $this->redirectToRoute('app_logement');
        }

        // Retourner le formulaire et le logement à la vue
        return $this->render('logement/edit.html.twig', [
            'form' => $form->createView(),
            'logement' => $logement,
        ]);
    }

   

    
}



