<?php

namespace App\Entity;

use App\Repository\VehiculeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_vehicule")]
    private ?int $id_vehicule = null;

    #[ORM\Column(length: 255)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(name: "nbPlace", nullable: true)]
    private ?int $nbPlace = null;    

    #[ORM\Column(nullable: true)]
    private ?int $cylinder = null;

    public function getId_vehicule(): ?int
    {
        return $this->id_vehicule;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getNbPlace(): ?int
    {
        return $this->nbPlace;
    }

    public function setNbPlace(?int $nbPlace): static
    {
        $this->nbPlace = $nbPlace;
        return $this;
    }

    public function getCylinder(): ?int
    {
        return $this->cylinder;
    }

    public function setCylinder(?int $cylinder): static
    {
        $this->cylinder = $cylinder;
        return $this;
    }
}
