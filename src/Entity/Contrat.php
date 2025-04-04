<?php

namespace App\Entity;

use App\Repository\ContratRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
class Contrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateD = null;

    #[ORM\Column(length: 255)]
    private ?string $dateF = null;

    #[ORM\Column]
    private ?int $cinlocateur = null;

    #[ORM\Column(length: 255)]
    private ?string $photo_permit = null;

    #[ORM\Column]
    private ?int $id_vehicule = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateD(): ?\DateTimeInterface
    {
        return $this->dateD;
    }

    public function setDateD(\DateTimeInterface $dateD): static
    {
        $this->dateD = $dateD;

        return $this;
    }

    public function getDateF(): ?string
    {
        return $this->dateF;
    }

    public function setDateF(string $dateF): static
    {
        $this->dateF = $dateF;

        return $this;
    }

    public function getCinlocateur(): ?int
    {
        return $this->cinlocateur;
    }

    public function setCinlocateur(int $cinlocateur): static
    {
        $this->cinlocateur = $cinlocateur;

        return $this;
    }

    public function getPhotoPermit(): ?string
    {
        return $this->photo_permit;
    }

    public function setPhotoPermit(string $photo_permit): static
    {
        $this->photo_permit = $photo_permit;

        return $this;
    }

    public function getIdVehicule(): ?int
    {
        return $this->id_vehicule;
    }

    public function setIdVehicule(int $id_vehicule): static
    {
        $this->id_vehicule = $id_vehicule;

        return $this;
    }
}
