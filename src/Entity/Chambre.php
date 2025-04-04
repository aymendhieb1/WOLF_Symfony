<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Chambre
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_Chambre;

    #[ORM\Column(type: "string", length: 100)]
    private string $type_Chambre;

    #[ORM\Column(type: "float")]
    private float $prix_Chambre;

    #[ORM\Column(type: "string", length: 100)]
    private string $disponibilite_Chambre;

    #[ORM\Column(type: "string", length: 300)]
    private string $description_Chambre;

    #[ORM\Column(type: "integer")]
    private int $id_hotel_Chambre;

    public function getId_Chambre(): int
    {
        return $this->id_Chambre;
    }

    public function setId_Chambre(int $value): self
    {
        $this->id_Chambre = $value;
        return $this;
    }

    public function getType_Chambre(): string
    {
        return $this->type_Chambre;
    }

    public function setType_Chambre(string $value): self
    {
        $this->type_Chambre = $value;
        return $this;
    }

    public function getPrix_Chambre(): float
    {
        return $this->prix_Chambre;
    }

    public function setPrix_Chambre(float $value): self
    {
        $this->prix_Chambre = $value;
        return $this;
    }

    public function getDisponibilite_Chambre(): string
    {
        return $this->disponibilite_Chambre;
    }

    public function setDisponibilite_Chambre(string $value): self
    {
        $this->disponibilite_Chambre = $value;
        return $this;
    }

    public function getDescription_Chambre(): string
    {
        return $this->description_Chambre;
    }

    public function setDescription_Chambre(string $value): self
    {
        $this->description_Chambre = $value;
        return $this;
    }

    public function getId_hotel_Chambre(): int
    {
        return $this->id_hotel_Chambre;
    }

    public function setId_hotel_Chambre(int $value): self
    {
        $this->id_hotel_Chambre = $value;
        return $this;
    }
}
