<?php

namespace App\Entity;

use App\Repository\ContratRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
#[ORM\Table(name: 'contrat')]
class Contrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_location", type: "integer")]
    private ?int $id_location = null;

    #[ORM\Column(name: "dateD", type: "string", length: 255)]
    #[Assert\NotBlank(message: "La date de début est requise.")]
    private ?string $dateD = null;

    #[ORM\Column(name: "dateF", type: "string", length: 255)]
    #[Assert\NotBlank(message: "La date de fin est requise.")]
    private ?string $dateF = null;

    #[ORM\Column(name: "cinLocateur", type: "integer")]
    #[Assert\NotBlank(message: "Le CIN est requis.")]
    #[Assert\Regex(pattern: "/^\d{8}$/", message: "Le CIN doit contenir exactement 8 chiffres.")]
    private ?int $cinLocateur = null;

    #[ORM\Column(name: "photo_permit", type: "string", length: 255, nullable: true)]
    private ?string $photo_permit = null;

    #[ORM\ManyToOne(inversedBy: 'contrats')]
    #[ORM\JoinColumn(name: 'id_vehicule', referencedColumnName: 'id_vehicule', nullable: false)]
    #[Assert\NotNull(message: "Le véhicule est requis.")]
    private ?Vehicule $id_vehicule = null;

    public function getIdLocation(): ?int
    {
        return $this->id_location;
    }

    public function setIdLocation(int $id_location): static
    {
        $this->id_location = $id_location;
        return $this;
    }

    public function getDateD(): ?string
    {
        return $this->dateD;
    }

    public function setDateD(?string $dateD): static
    {
        $this->dateD = $dateD;
        return $this;
    }

    public function getDateF(): ?string
    {
        return $this->dateF;
    }

    public function setDateF(?string $dateF): static
    {
        $this->dateF = $dateF;
        return $this;
    }

    public function getCinLocateur(): ?int
    {
        return $this->cinLocateur;
    }

    public function setCinLocateur(int $cinLocateur): static
    {
        $this->cinLocateur = $cinLocateur;
        return $this;
    }

    public function getPhotoPermit(): ?string
    {
        return $this->photo_permit;
    }

    public function setPhotoPermit(?string $photo_permit): static
    {
        $this->photo_permit = $photo_permit;
        return $this;
    }

    public function getIdVehicule(): ?Vehicule
    {
        return $this->id_vehicule;
    }

    public function setIdVehicule(?Vehicule $id_vehicule): static
    {
        $this->id_vehicule = $id_vehicule;
        return $this;
    }
}