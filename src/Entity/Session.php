<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Activite;

#[ORM\Entity]
class Session
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_sess;

        #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: "sessions")]
    #[ORM\JoinColumn(name: 'id_act', referencedColumnName: 'id_act', onDelete: 'CASCADE')]
    private Activite $id_act;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_sess;

    #[ORM\Column(type: "string")]
    private string $time_sess;

    #[ORM\Column(type: "integer")]
    private int $cap_sess;

    #[ORM\Column(type: "integer")]
    private int $nbr_places_sess;

    public function getId_sess(): int
    {
        return $this->id_sess;
    }

    public function setId_sess(int $value): self
    {
        $this->id_sess = $value;
        return $this;
    }

    public function getId_act(): int
    {
        return $this->id_act;
    }

    public function setId_act(int $value): self
    {
        $this->id_act = $value;
        return $this;
    }

    public function getDate_sess(): \DateTimeInterface
    {
        return $this->date_sess;
    }

    public function setDate_sess(\DateTimeInterface $value): self
    {
        $this->date_sess = $value;
        return $this;
    }

    public function getTime_sess(): \DateTimeInterface
    {
        return $this->time_sess;
    }

    public function setTime_sess(\DateTimeInterface $value): self
    {
        $this->time_sess = $value;
        return $this;
    }

    public function getCap_sess(): int
    {
        return $this->cap_sess;
    }

    public function setCap_sess(int $value): self
    {
        $this->cap_sess = $value;
        return $this;
    }

    public function getNbr_places_sess(): int
    {
        return $this->nbr_places_sess;
    }

    public function setNbr_places_sess(int $value): self
    {
        $this->nbr_places_sess = $value;
        return $this;
    }
}
