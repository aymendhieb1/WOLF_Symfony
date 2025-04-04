<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Session;

#[ORM\Entity]
class Activite
{
    public function __construct()
    {
        $this->sessions = new \Doctrine\Common\Collections\ArrayCollection();
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_act;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom_act;

    #[ORM\Column(type: "string", length: 255)]
    private string $descript;

    #[ORM\Column(type: "string", length: 255)]
    private string $localisation;

    #[ORM\Column(type: "string", length: 255)]
    private string $type;

    #[ORM\Column(type: "float")]
    private float $prix_act;

    public function getId_act(): int
    {
        return $this->id_act;
    }

    public function setId_act(int $value): self
    {
        $this->id_act = $value;
        return $this;
    }

    public function getNom_act(): string
    {
        return $this->nom_act;
    }

    public function setNom_act(string $value): self
    {
        $this->nom_act = $value;
        return $this;
    }

    public function getDescript(): string
    {
        return $this->descript;
    }

    public function setDescript(string $value): self
    {
        $this->descript = $value;
        return $this;
    }

    public function getLocalisation(): string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $value): self
    {
        $this->localisation = $value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $value): self
    {
        $this->type = $value;
        return $this;
    }

    public function getPrix_act(): float
    {
        return $this->prix_act;
    }

    public function setPrix_act(float $value): self
    {
        $this->prix_act = $value;
        return $this;
    }

    #[ORM\OneToMany(mappedBy: "id_act", targetEntity: Session::class)]
    private Collection $sessions;

    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): self
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions[] = $session;
            $session->setId_act($this);
        }
        return $this;
    }

    public function removeSession(Session $session): self
    {
        if ($this->sessions->removeElement($session)) {
            if ($session->getId_act() === $this) {
                $session->setId_act(null);
            }
        }
        return $this;
    }
}
