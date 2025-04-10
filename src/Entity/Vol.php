<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Checkoutvol;

#[ORM\Entity]
class Vol
{
    public function __construct()
    {
        $this->checkoutvols = new \Doctrine\Common\Collections\ArrayCollection();
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $FlightID;

    #[ORM\Column(type: "string", length: 60)]
    private string $Departure;

    #[ORM\Column(type: "string", length: 60)]
    private string $Destination;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $DepartureTime;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $ArrivalTime;

    #[ORM\Column(type: "string")]
    private string $ClasseChaise;

    #[ORM\Column(type: "string", length: 20)]
    private string $Airline;

    #[ORM\Column(type: "integer")]
    private int $FlightPrice;

    #[ORM\Column(type: "integer")]
    private int $AvailableSeats;

    #[ORM\Column(type: "string", length: 500)]
    private string $Description;

    public function getFlightID(): int
    {
        return $this->FlightID;
    }

    public function setFlightID(int $value): self
    {
        $this->FlightID = $value;
        return $this;
    }

    public function getDeparture(): string
    {
        return $this->Departure;
    }

    public function setDeparture(string $value): self
    {
        $this->Departure = $value;
        return $this;
    }

    public function getDestination(): string
    {
        return $this->Destination;
    }

    public function setDestination(string $value): self
    {
        $this->Destination = $value;
        return $this;
    }

    public function getDepartureTime(): \DateTimeInterface
    {
        return $this->DepartureTime;
    }

    public function setDepartureTime(\DateTimeInterface $value): self
    {
        $this->DepartureTime = $value;
        return $this;
    }

    public function getArrivalTime(): \DateTimeInterface
    {
        return $this->ArrivalTime;
    }

    public function setArrivalTime(\DateTimeInterface $value): self
    {
        $this->ArrivalTime = $value;
        return $this;
    }

    public function getClasseChaise(): string
    {
        return $this->ClasseChaise;
    }

    public function setClasseChaise(string $value): self
    {
        $this->ClasseChaise = $value;
        return $this;
    }

    public function getAirline(): string
    {
        return $this->Airline;
    }

    public function setAirline(string $value): self
    {
        $this->Airline = $value;
        return $this;
    }

    public function getFlightPrice(): int
    {
        return $this->FlightPrice;
    }

    public function setFlightPrice(int $value): self
    {
        $this->FlightPrice = $value;
        return $this;
    }

    public function getAvailableSeats(): int
    {
        return $this->AvailableSeats;
    }

    public function setAvailableSeats(int $value): self
    {
        $this->AvailableSeats = $value;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->Description;
    }

    public function setDescription(string $value): self
    {
        $this->Description = $value;
        return $this;
    }

    #[ORM\OneToMany(mappedBy: "FlightID", targetEntity: Checkoutvol::class)]
    private Collection $checkoutvols;

    public function getCheckoutvols(): Collection
    {
        return $this->checkoutvols;
    }

    public function addCheckoutvol(Checkoutvol $checkoutvol): self
    {
        if (!$this->checkoutvols->contains($checkoutvol)) {
            $this->checkoutvols[] = $checkoutvol;
            $checkoutvol->setFlightID($this);
        }
        return $this;
    }

    public function removeCheckoutvol(Checkoutvol $checkoutvol): self
    {
        if ($this->checkoutvols->removeElement($checkoutvol)) {
            if ($checkoutvol->getFlightID() === $this) {
                $checkoutvol->setFlightID(null);
            }
        }
        return $this;
    }
}
