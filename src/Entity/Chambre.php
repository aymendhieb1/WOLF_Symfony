<?php

namespace App\Entity;

use App\Repository\ChambreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChambreRepository::class)]
#[ORM\Table(name: 'chambre')]
class Chambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Chambre', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'type_Chambre', length: 100)]
    #[Assert\NotBlank(message: 'Le type de chambre est obligatoire')]
    #[Assert\Choice(choices: ['Simple', 'Double', 'Suite'], message: 'Veuillez choisir un type valide (Simple, Double ou Suite)')]
    private ?string $type = null;

    #[ORM\Column(name: 'prix_Chambre')]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0')]
    #[Assert\Type(type: 'float', message: 'Le prix doit être un nombre')]
    private ?float $prix = null;

    #[ORM\Column(name: 'disponibilite_Chambre', length: 100)]
    #[Assert\NotBlank(message: 'La disponibilité est obligatoire')]
    private ?bool $disponibilite = true;

    #[ORM\Column(name: 'description_Chambre', length: 300)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'La description doit faire au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class)]
    #[ORM\JoinColumn(name: 'id_hotel_Chambre', referencedColumnName: 'id_hotel')]
    #[Assert\NotBlank(message: "L'hôtel est obligatoire")]
    private ?Hotel $hotel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getDisponibilite(): ?bool
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(bool $disponibilite): static
    {
        $this->disponibilite = $disponibilite;
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

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): static
    {
        $this->hotel = $hotel;
        return $this;
    }
} 