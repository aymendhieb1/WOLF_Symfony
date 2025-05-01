<?php

namespace App\Entity;

use App\Repository\VolRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VolRepository::class)]
class Vol
{
    public const CLASSE_CHAISES = [
        'ECONOMY',
        'PREMIUM_ECONOMY',
        'BUSINESS',
        'FIRST_CLASS',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'FlightID', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'Departure', type: 'string', length: 60)]
    #[Assert\NotBlank(message: "Le lieu de départ est obligatoire.")]
    #[Assert\Length(max: 60, maxMessage: "Le lieu de départ ne doit pas dépasser 60 caractères.")]
    private ?string $depart = null;

    #[ORM\Column(name: 'Destination', type: 'string', length: 60)]
    #[Assert\NotBlank(message: "La destination est obligatoire.")]
    #[Assert\Length(max: 60, maxMessage: "La destination ne doit pas dépasser 60 caractères.")]
    private ?string $destination = null;

    #[ORM\Column(name: 'DepartureTime', type: 'datetime')]
    #[Assert\NotBlank(message: "L'heure de départ est obligatoire.")]
    private ?\DateTimeInterface $heureDepart = null;

    #[ORM\Column(name: 'ArrivalTime', type: 'datetime')]
    #[Assert\NotBlank(message: "L'heure d'arrivée est obligatoire.")]
    private ?\DateTimeInterface $heureArrivee = null;

    #[ORM\Column(name: 'ClasseChaise', type: 'string', length: 20)]
    #[Assert\NotBlank(message: "La classe de chaise est obligatoire.")]
    #[Assert\Choice(choices: self::CLASSE_CHAISES, message: "Classe invalide.")]
    private ?string $classeChaise = null;

    #[ORM\Column(name: 'Airline', type: 'string', length: 20)]
    #[Assert\NotBlank(message: "La compagnie aérienne est obligatoire.")]
    #[Assert\Length(max: 20, maxMessage: "La compagnie aérienne ne doit pas dépasser 20 caractères.")]
    private ?string $compagnie = null;

    #[ORM\Column(name: 'FlightPrice', type: 'integer')]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif.")]
    private ?int $prix = null;

    #[ORM\Column(name: 'AvailableSeats', type: 'integer')]
    #[Assert\NotBlank(message: "Le nombre de sièges disponibles est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le nombre de sièges doit être positif ou nul.")]
    private ?int $siegesDisponibles = null;

    #[ORM\Column(name: 'Description', type: 'string', length: 500)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(max: 500, maxMessage: "La description ne doit pas dépasser 500 caractères.")]
    private ?string $description = null;

    // ========================
    // Getters and Setters
    // ========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepart(): ?string
    {
        return $this->depart;
    }

    public function setDepart(string $depart): self
    {
        $this->depart = $depart;
        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(\DateTimeInterface $heureDepart): self
    {
        $this->heureDepart = $heureDepart;
        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(\DateTimeInterface $heureArrivee): self
    {
        $this->heureArrivee = $heureArrivee;
        return $this;
    }

    public function getClasseChaise(): ?string
    {
        return $this->classeChaise;
    }

    public function setClasseChaise(string $classeChaise): self
    {
        $this->classeChaise = $classeChaise;
        return $this;
    }

    public function getCompagnie(): ?string
    {
        return $this->compagnie;
    }

    public function setCompagnie(string $compagnie): self
    {
        $this->compagnie = $compagnie;
        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getSiegesDisponibles(): ?int
    {
        return $this->siegesDisponibles;
    }

    public function setSiegesDisponibles(int $siegesDisponibles): self
    {
        $this->siegesDisponibles = $siegesDisponibles;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
