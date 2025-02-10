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

     private array $saisons = [];

     public function loadSeasons(ObjectManager $manager): void
     {
         $array_season = [
             ['label' => 'Haute saison', 'date_s' => '2025-06-01', 'date_e' => '2025-09-01'],
             ['label' => 'Basse saison', 'date_s' => '2025-09-01', 'date_e' => '2026-06-30'],
             ['label' => 'Hors saison', 'date_s' => '2025-12-01', 'date_e' => '2025-12-31']
         ];
     
         foreach ($array_season as $key => $value) {
             $season = new Saison();
             $season->setLabel($value['label']);
             $season->setDateS(new \DateTime($value['date_s']));
             $season->setDateE(new \DateTime($value['date_e']));
             $manager->persist($season);
     
             // Stocker l'objet Saison dans le tableau
             $this->saisons[$key] = $season;
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
    private array $logements = [];

public function loadLogement(ObjectManager $manager): void
{
    $array_logement = [
        ['label' => 'Mobil-home', 'surface' => 30, 'imagePath' => 'mobil-home.jpg', 'nb_personnes' => 4, 'emplacement' => 2, 'description' => 'Mobil-home de 30m²'],
        ['label' => 'terrain', 'surface' => 40, 'imagePath' => 'chalet.jpg', 'nb_personnes' => 6, 'emplacement' => 3, 'description' => 'terrain de 40m²'],
        ['label' => 'Tente', 'surface' => 20, 'imagePath' => 'tente.jpg', 'nb_personnes' => 2, 'emplacement' => 1, 'description' => 'Tente de 20m²']
    ];

    foreach ($array_logement as $key => $value) {
        $logement = new Logement(); 
        $logement->setLabel($value['label']);
        $logement->setSurface($value['surface']);
        $logement->setImagePath($value['imagePath']);
        $logement->setNbPersonne($value['nb_personnes']);
        $logement->setEmplacement($value['emplacement']);
        $logement->setDescription($value['description']);
        $manager->persist($logement);

        // Stocker l'objet Logement dans le tableau pour utilisation future
        $this->logements[$key] = $logement;
    }
}


/**
 * méthode pour générer des tarifs
 * @param ObjectManager $manager
 * @return void
 */
public function loadTarif(ObjectManager $manager): void
{
    $array_tarif = [
        ['saison_index' => 0, 'price' => 110, 'logement_index' => 0],
        ['saison_index' => 1, 'price' => 50, 'logement_index' => 1],
        ['saison_index' => 2, 'price' => 40, 'logement_index' => 2]
    ];

    foreach ($array_tarif as $value) {
        $tarif = new Tarif();
        $tarif->setPrice($value['price']);

        // Récupérer la Saison via le tableau en mémoire
        $saison = $this->saisons[$value['saison_index']] ?? null;
        if (!$saison) {
            throw new \Exception("❌ Saison index " . $value['saison_index'] . " introuvable !");
        }
        $tarif->setSaison($saison);

        // Récupérer le Logement via le tableau en mémoire
        $logement = $this->logements[$value['logement_index']] ?? null;
        if (!$logement) {
            throw new \Exception("❌ Logement index " . $value['logement_index'] . " introuvable !");
        }
        $tarif->setLogement($logement);

        // Debug : Voir si Logement est bien trouvé
        dump(" Logement Index " . $value['logement_index'], $logement);

        $manager->persist($tarif);
    }
}






}
