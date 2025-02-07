<?php

namespace App\DataFixtures;

use App\Entity\Equipement;
use App\Entity\Logement;
use App\Entity\Rental;
use App\Entity\Saison;
use App\Entity\Tarif;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    //propriété pour encoder le mdp 
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }


    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $this->loadUsers($manager);
        $this->loadSeasons($manager);
        $this->loadRental($manager);
        $this->loadEquipment($manager);
        $this->loadLogement($manager);
        $this->loadTarif($manager);


        $manager->flush();
    }

    /**
     * méthode pour générer des utilisateurs
     * @param ObjectManager $manager
     * @return void
     */
    public function loadUsers(ObjectManager $manager): void
    {
        //on crée un tableau avec les infos des users
        $array_user = [
            [
                'email' => 'admin@admin.com', 
                'password'=>'admin',
                'firstname' => 'Admin',
                'lastname' => 'Admin',
                'roles' => ['ROLE_ADMIN']
            
            ],
            [
                'email' => 'user@user.com',
                'password' => 'user',
                'firstname' => 'User',
                'lastname' => 'User',
                'roles' => ['ROLE_USER']
            ]
        ];

        //on va boucler sur le tableau pour créer les users
        foreach($array_user as $key => $value)
        {
            //on instancie un user
            $user = new User(); 
            $user->setEmail($value['email']);
            $user->setPassword($this->encoder->hashPassword($user, $value['password']));
            $user->setFirstname($value['firstname']);
            $user->setLastname($value['lastname']);
            $user->setRoles($value['roles']);
            //on persiste les données
            $manager->persist($user);
        }
    }

    /**
     * méthode pour générer des saisons (Hautes saisons, Basse saison ...)
     * @param ObjectManager $manager
     * @return void
     */

    public function loadSeasons(ObjectManager $manager): void
    {
        //on crée un tableau avec les infos des saisons
        $array_season = [
            [
                'label' => 'Haute saison',
                'date_s' => '2025-06-01',  // Date de début pour Haute saison
                'date_e' => '2025-09-01',  // Date de fin pour Haute saison
            ],
            [
                'label' => 'Basse saison',
                'date_s' => '2025-09-01',
                'date_e' => '2026-06-30',
            ],
            [
                'label' => 'Hors saison', // C'est la saison de fermeture
                'date_s' => '2025-12-01',
                'date_e' => '2025-12-31',
            ]

        ];

        //on va boucler sur le tableau pour créer les saisons
        foreach($array_season as $key => $value)
        {
            //on instancie une saison
            $season = new Saison(); 
            $season->setLabel($value['label']);
            $season->setDateS(new \DateTime($value['date_s']));
            $season->setDateE(new \DateTime($value['date_e']));
            //on persiste les données
            $manager->persist($season);
        }
    }

    /**
     * méthode pour générer des réservations
     * @param ObjectManager $manager
     * @return void
     */
    public function loadRental(ObjectManager $manager): void
    {
        //on crée un tableau avec les infos des réservations
        $array_rental = [
            [   
                'user_id' => 1,
                'logement_id' => 1,
                'date_start' => '2025-06-15',
                'date_end' => '2025-06-30',
                'user_id' => 1,
                'nb_adulte' => 2,
                'nb_child' => 1,
            ],
            [   
                'user_id' => 2,
                'logement_id' => 2,
                'date_start' => '2025-09-15',
                'date_end' => '2025-09-30',
                'user_id' => 2,
                'nb_adulte' => 2,
                'nb_child' => 1,
            ],
            [   
                'user_id' => 1,
                'logement_id' => 3,
                'date_start' => '2025-12-15',
                'date_end' => '2025-12-30',
                'user_id' => 1,
                'nb_adulte' => 2,
                'nb_child' => 1,
            ]
        ];

        //on va boucler sur le tableau pour créer les réservations

        foreach($array_rental as $key => $value)
        {
            //on instancie une réservation
            $rental = new Rental(); 
            $rental->setDateStart(new \DateTime($value['date_start']));
            $rental->setDateEnd(new \DateTime($value['date_end']));
            $rental->setNbAdulte($value['nb_adulte']);
            $rental->setNbChild($value['nb_child']);
            //on persiste les données
            $manager->persist($rental);
        }

    }

    /**
     * méthode pour générer des equipement 
     * @param ObjectManager $manager
     * @return void
     */
    public function loadEquipment(ObjectManager $manager): void
    {
       // Tableau des équipements de camping
       $array_equipment = [
        ['label' => 'Climatisation'],
        ['label' => 'Chauffage'],
        ['label' => 'TV'],
        ['label' => 'Wifi'],
        ['label' => 'Parking'],
        ['label' => 'Piscine'],
        ['label' => 'Lave-linge'],
        ['label' => 'Sèche-linge'],
        ['label' => 'Réfrigérateur'],
        ['label' => 'Barbecue'],
        ['label' => 'Aire de jeux'],
        ['label' => 'Restaurant'],
        ['label' => 'Snack'],
        ['label' => 'Borne de recharge pour voiture électrique'],
        ['label' => 'Location de vélos'],
        ['label' => 'Animations'],
        ['label' => 'Salle de sport'],
        ['label' => 'Accès direct à la plage'],
        ['label' => 'Location de kayak'],
        ['label' => 'Club enfants'],
        ['label' => 'Mini-golf'],
        ['label' => 'Service de ménage'],
        ['label' => 'Draps et serviettes'],
        ['label' => 'Location de tentes/cabanes'],
        ['label' => 'Toilettes et douches'],
        ['label' => 'Salle de réunion'],
        ['label' => 'Piste cyclable'],
        ['label' => 'Bord de lac/rivière']
    ];

        //on va boucler sur le tableau pour créer les équipements
        foreach($array_equipment as $key => $value)
        {
            //on instancie un équipement
            $equipment = new Equipement(); 
            $equipment->setLabel($value['label']);
            //on persiste les données
            $manager->persist($equipment);
        }
    }

    /**
     * méthode pour générer des logements
     * @param ObjectManager $manager
     * @return void
     */
    public function loadLogement(ObjectManager $manager): void
    {
        // Tableau des logements
        $array_logement = [
            [
                'label' => 'Mobil-home',
                'surface' => 30,
                'imagePath' => 'mobil-home.jpg',
                'nb_personnes' => 4,
                'emplacement' => 2,
                'equipments' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'description' => 'Mobil-home de 30m² avec 2 chambres, 1 salle de bain, 1 WC, 1 cuisine équipée, 1 salon, 1 terrasse couverte.'
            ],
            [
                'label' => 'terrain',
                'surface' => 40,
                'imagePath' => 'chalet.jpg',
                'nb_personnes' => 6,
                'emplacement' => 3,
                'equipments' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'description' => 'terrain de 40m²'
            ],
            [
                'label' => 'Tente',
                'surface' => 20,
                'imagePath' => 'tente.jpg',
                'nb_personnes' => 2,
                'emplacement' => 1,
                'equipments' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'description' => 'Tente de 20m² avec 1 chambre, 1 salle de bain, 1 WC, 1 cuisine équipée, 1 salon, 1 terrasse couverte.'
            ]
        ];

        //on va boucler sur le tableau pour créer les logements 
        foreach($array_logement as $key => $value)
        {
            //on instancie un logement
            $logement = new Logement(); 
            $logement->setLabel($value['label']);
            $logement->setSurface($value['surface']);
            $logement->setImagePath($value['imagePath']);
            $logement->setNbPersonne($value['nb_personnes']);
            $logement->setEmplacement($value['emplacement']);
            $logement->setDescription($value['description']);
            //on persiste les données
            $manager->persist($logement);
        }


    }

    /**
/**
 * méthode pour générer des tarifs
 * @param ObjectManager $manager
 * @return void
 */
public function loadTarif(ObjectManager $manager): void
{
    // Tableau des tarifs avec des IDs existants
    $array_tarif = [
        [
            'saison_id' => 1, 
            'price' => 110,
            'logement_id' => 1  
        ],
        [
            'saison_id' => 2,
            'price' => 50,
            'logement_id' => 2
        ],
        [
            'saison_id' => 3,
            'price' => 40,
            'logement_id' => 3
        ]
    ];

    // On boucle sur le tableau pour créer les tarifs
    foreach($array_tarif as $value)
    {
        // On instancie un tarif
        $tarif = new Tarif(); 
        $tarif->setPrice($value['price']);
        
        // Récupérer l'objet Saison avec l'ID
        $saison = $manager->getRepository(Saison::class)->find($value['saison_id']);
        $tarif->setSaison($saison);

        // Récupérer l'objet Logement avec l'ID
        $logement = $manager->getRepository(Logement::class)->find($value['logement_id']);
        $tarif->setLogement($logement); // Associer le logement au tarif

        // Persister le tarif dans la base de données
        $manager->persist($tarif);
    }

    // Enregistrer tous les changements dans la base de données
    $manager->flush();
}


}
