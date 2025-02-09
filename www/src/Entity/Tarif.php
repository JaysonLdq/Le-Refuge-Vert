<?php

namespace App\Entity;

use App\Repository\TarifRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifRepository::class)]
class Tarif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "integer")]
    private ?int $price = null;
    

    // Suppression de la relation OneToMany avec Logement car elle n'est pas nécessaire
    #[ORM\ManyToOne(targetEntity: Logement::class, inversedBy: 'tarifs')]
    #[ORM\JoinColumn(nullable: false)]  // Ajout pour éviter les valeurs null
    private ?Logement $logement = null;

    #[ORM\ManyToOne(inversedBy: 'tarifs')]
    #[ORM\JoinColumn(nullable: false)]  // Ajout pour éviter les valeurs null
    private ?Saison $saison = null;

    public function __construct()
    {
        // Initialisation de la propriété logement et saison dans le constructeur si nécessaire
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getLogement(): ?Logement
    {
        return $this->logement;
    }

    public function setLogement(?Logement $logement): static
    {
        $this->logement = $logement;
        return $this;
    }

    public function getSaison(): ?Saison
    {
        return $this->saison;
    }

    public function setSaison(?Saison $saison): static
    {
        $this->saison = $saison;
        return $this;
    }
}
