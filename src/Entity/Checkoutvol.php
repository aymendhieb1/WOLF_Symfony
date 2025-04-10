<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Vol;

#[ORM\Entity]
class Checkoutvol
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $CheckoutID;

        #[ORM\ManyToOne(targetEntity: Vol::class, inversedBy: "checkoutvols")]
    #[ORM\JoinColumn(name: 'FlightID', referencedColumnName: 'FlightID', onDelete: 'CASCADE')]
    private Vol $FlightID;

    #[ORM\Column(type: "integer")]
    private int $UserID;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $ReservationDate;

    #[ORM\Column(type: "integer")]
    private int $TotalPassengers;

    #[ORM\Column(type: "string")]
    private string $ReservationStatus;

    #[ORM\Column(type: "integer")]
    private int $TotalPrice;

    public function getCheckoutID(): int
    {
        return $this->CheckoutID;
    }

    public function setCheckoutID(int $value): self
    {
        $this->CheckoutID = $value;
        return $this;
    }

    public function getFlightID(): int
    {
        return $this->FlightID;
    }

    public function setFlightID(int $value): self
    {
        $this->FlightID = $value;
        return $this;
    }

    public function getUserID(): int
    {
        return $this->UserID;
    }

    public function setUserID(int $value): self
    {
        $this->UserID = $value;
        return $this;
    }

    public function getReservationDate(): \DateTimeInterface
    {
        return $this->ReservationDate;
    }

    public function setReservationDate(\DateTimeInterface $value): self
    {
        $this->ReservationDate = $value;
        return $this;
    }

    public function getTotalPassengers(): int
    {
        return $this->TotalPassengers;
    }

    public function setTotalPassengers(int $value): self
    {
        $this->TotalPassengers = $value;
        return $this;
    }

    public function getReservationStatus(): string
    {
        return $this->ReservationStatus;
    }

    public function setReservationStatus(string $value): self
    {
        $this->ReservationStatus = $value;
        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->TotalPrice;
    }

    public function setTotalPrice(int $value): self
    {
        $this->TotalPrice = $value;
        return $this;
    }
}
