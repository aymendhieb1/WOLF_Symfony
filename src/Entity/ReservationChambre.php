<?php

namespace App\Entity;

use App\Repository\ReservationChambreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationChambreRepository::class)]
#[ORM\Table(name: 'reservation_chambre')]
class ReservationChambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reservation_chambre')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_chambre')]
    private ?int $id_chambre = null;

    #[ORM\ManyToOne(targetEntity: Chambre::class)]
    #[ORM\JoinColumn(name: 'id_chambre', referencedColumnName: 'id_Chambre')]
    private ?Chambre $chambre = null;

    #[ORM\Column(name: 'id_user')]
    private ?int $id_user = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')]
    private ?User $user = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateReservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdChambre(): ?int
    {
        return $this->id_chambre;
    }

    public function setIdChambre(?int $id_chambre): self
    {
        $this->id_chambre = $id_chambre;
        return $this;
    }

    public function getChambre(): ?Chambre
    {
        return $this->chambre;
    }

    public function setChambre(?Chambre $chambre): self
    {
        $this->chambre = $chambre;
        if ($chambre) {
            $this->id_chambre = $chambre->getId();
        }
        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(?int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        if ($user) {
            $this->id_user = $user->getId();
        }
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }
}
