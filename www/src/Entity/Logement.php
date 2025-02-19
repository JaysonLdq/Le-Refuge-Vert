<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['rental:read']],
    denormalizationContext: ['groups' => ['rental:write']]
)]

#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups (['rental:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups (['rental:read'])]
    private ?string $label = null;
    
    #[ORM\Column]
    private ?int $surface = null;

    #[ORM\Column(length: 255)]
    private ?string $imagePath = null;

    #[ORM\Column]
    private ?int $nbPersonne = null;

    #[ORM\Column]
    private ?int $emplacement = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: Tarif::class, mappedBy: 'logement', cascade: ['persist', 'remove'])]
    private Collection $tarifs;

    #[ORM\OneToMany(targetEntity: Rental::class, mappedBy: 'logement')]
    private Collection $rentals;

    #[ORM\ManyToMany(targetEntity: Equipement::class, inversedBy: 'logements')]
    private Collection $equipements;

    #[ORM\Column(length: 150)]
    #[Groups (['rental:read', 'rental:write'])]
    private ?string $status = null;

    

    public function __construct()
    {
        $this->tarifs = new ArrayCollection();
        $this->rentals = new ArrayCollection();
        $this->equipements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getSurface(): ?int
    {
        return $this->surface;
    }

    public function setSurface(int $surface): static
    {
        $this->surface = $surface;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): static
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function getNbPersonne(): ?int
    {
        return $this->nbPersonne;
    }

    public function setNbPersonne(int $nbPersonne): static
    {
        $this->nbPersonne = $nbPersonne;
        return $this;
    }

    public function getEmplacement(): ?int
    {
        return $this->emplacement;
    }

    public function setEmplacement(int $emplacement): static
    {
        $this->emplacement = $emplacement;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Collection<int, Tarif>
     */
    public function getTarifs(): Collection
    {
        return $this->tarifs;
    }

    public function addTarif(Tarif $tarif): static
    {
        if (!$this->tarifs->contains($tarif)) {
            $this->tarifs->add($tarif);
            $tarif->setLogement($this);
        }

        return $this;
    }

    public function removeTarif(Tarif $tarif): static
    {
        if ($this->tarifs->removeElement($tarif)) {
            // Set the owning side to null (unless already changed)
            if ($tarif->getLogement() === $this) {
                $tarif->setLogement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Equipement>
     */
    public function getEquipements(): Collection
    {
        return $this->equipements;
    }

    public function addEquipement(Equipement $equipement): static
    {
        if (!$this->equipements->contains($equipement)) {
            $this->equipements->add($equipement);
            $equipement->addLogement($this);
        }

        return $this;
    }

    public function removeEquipement(Equipement $equipement): static
    {
        if ($this->equipements->removeElement($equipement)) {
            $equipement->removeLogement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Rental>
     */
    public function getRentals(): Collection
    {
        return $this->rentals;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        // Vérification de validité du statut
        $validStatuses = ['available', 'unavailable', 'pending'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }

        return $this;
    }

    /**
     * Mettre à jour le statut basé sur la réservation
     * @param string $newStatus
     */
    public function updateStatusBasedOnRental(string $newStatus): void
    {
        $validStatuses = ['available', 'unavailable', 'pending'];
        if (in_array($newStatus, $validStatuses)) {
            $this->status = $newStatus;
        }
    }
}
