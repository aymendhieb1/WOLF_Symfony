<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiement')]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_paiement')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_user')]
    private ?int $id_user = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', insertable: false, updatable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'numero_card', length: 50)]
    private ?string $numeroCard = null;

    #[ORM\Column(name: 'montant')]
    private ?int $montant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): self
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

    public function getNumeroCard(): ?string
    {
        return $this->numeroCard;
    }

    public function setNumeroCard(string $numeroCard): self
    {
        // Mask the card number except last 4 digits
        $maskedCard = preg_replace('/(\d{4} \d{4} \d{4} )(\d{4})/', '$1$2****', $numeroCard);
        $this->numeroCard = $maskedCard;
        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): self
    {
        $this->montant = $montant;
        return $this;
    }
} 