<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\RentalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RentalRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['rental:read']],
    denormalizationContext: ['groups' => ['rental:write']]
)]
class Rental
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rentals')]
    #[Groups (['rental:read'])]
    private ?User $users = null;

    #[ORM\ManyToOne(inversedBy: 'rentals')]
    #[Groups (['rental:read'])]
    private ?Logement $logement = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups (['rental:read'])]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups (['rental:read'])]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column]
    private ?int $nbAdulte = null;

    #[ORM\Column]
    private ?int $nbChild = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsers(): ?User
    {
        return $this->users;
    }

    public function setUsers(?User $users): static
    {
        $this->users = $users;

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

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): static
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTimeInterface $dateEnd): static
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getNbAdulte(): ?int
    {
        return $this->nbAdulte;
    }

    public function setNbAdulte(int $nbAdulte): static
    {
        $this->nbAdulte = $nbAdulte;

        return $this;
    }

    public function getNbChild(): ?int
    {
        return $this->nbChild;
    }

    public function setNbChild(int $nbChild): static
    {
        $this->nbChild = $nbChild;

        return $this;
    }
}
