<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Hotel
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_hotel;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom_hotel;

    #[ORM\Column(type: "string", length: 500)]
    private string $localisation_hotel;

    #[ORM\Column(type: "string", length: 100)]
    private string $num_telephone_hotel;

    #[ORM\Column(type: "string", length: 100)]
    private string $email_hotel;

    #[ORM\Column(type: "string", length: 500)]
    private string $image_hotel;

    #[ORM\Column(type: "string", length: 1000)]
    private string $description_hotel;

    public function getId_hotel(): int
    {
        return $this->id_hotel;
    }

    public function setId_hotel(int $value): self
    {
        $this->id_hotel = $value;
        return $this;
    }

    public function getNom_hotel(): string
    {
        return $this->nom_hotel;
    }

    public function setNom_hotel(string $value): self
    {
        $this->nom_hotel = $value;
        return $this;
    }

    public function getLocalisation_hotel(): string
    {
        return $this->localisation_hotel;
    }

    public function setLocalisation_hotel(string $value): self
    {
        $this->localisation_hotel = $value;
        return $this;
    }

    public function getNum_telephone_hotel(): string
    {
        return $this->num_telephone_hotel;
    }

    public function setNum_telephone_hotel(string $value): self
    {
        $this->num_telephone_hotel = $value;
        return $this;
    }

    public function getEmail_hotel(): string
    {
        return $this->email_hotel;
    }

    public function setEmail_hotel(string $value): self
    {
        $this->email_hotel = $value;
        return $this;
    }

    public function getImage_hotel(): string
    {
        return $this->image_hotel;
    }

    public function setImage_hotel(string $value): self
    {
        $this->image_hotel = $value;
        return $this;
    }

    public function getDescription_hotel(): string
    {
        return $this->description_hotel;
    }

    public function setDescription_hotel(string $value): self
    {
        $this->description_hotel = $value;
        return $this;
    }
}
