<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityManagerInterface;

#[ORM\Entity]
#[ORM\Table(name: "checkoutvol")]
class CheckoutVol
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "CheckoutID", type: "integer")]
    private ?int $checkoutID = null;

    #[ORM\Column(name: "FlightID", type: "integer")]
    #[Assert\Positive(message: 'Flight ID must be a positive integer.')]
    private int $flightID;

    #[ORM\Column(type: "string", length: 255)]
    private string $aircraft;

    #[ORM\Column(name: "FlightCrew", type: "integer")]
    #[Assert\Range(min: 4, max: 20, notInRangeMessage: 'Crew number must be between 4 and 20.')]
    private ?int $flightCrew = null;

    #[ORM\Column(type: "string", length: 10)]
    private string $gate;

    #[ORM\Column(name: "ReservationDate", type: "datetime")]
    #[Assert\GreaterThanOrEqual("today", message: "Reservation date must be today or in the future.")]
    private \DateTimeInterface $reservationDate;

    #[ORM\Column(name: "TotalPassengers", type: "integer")]
    private int $totalPassengers;

    #[ORM\Column(name: "ReservationStatus", type: "string", length: 20)]
    private string $reservationStatus;

    #[ORM\Column(name: "TotalPrice", type: "integer")]
    private int $totalPrice;

    // --- GETTERS AND SETTERS ---

    public function getCheckoutID(): ?int
    {
        return $this->checkoutID;
    }

    public function getFlightID(): int
    {
        return $this->flightID;
    }

    public function setFlightID(int $flightID): self
    {
        $this->flightID = $flightID;
        return $this;
    }

    public function getAircraft(): string
    {
        return $this->aircraft;
    }

    public function setAircraft(string $aircraft): self
    {
        $this->aircraft = $aircraft;
        return $this;
    }

    public function getFlightCrew(): ?int
    {
        return $this->flightCrew;
    }

    public function setFlightCrew(?int $flightCrew): self
    {
        $this->flightCrew = $flightCrew;
        return $this;
    }

    public function getGate(): string
    {
        return $this->gate;
    }

    public function setGate(string $gate): self
    {
        $this->gate = $gate;
        return $this;
    }

    public function getReservationDate(): \DateTimeInterface
    {
        return $this->reservationDate;
    }

    public function setReservationDate(\DateTimeInterface $reservationDate): self
    {
        if ($reservationDate < new \DateTime()) {
            $this->reservationDate = new \DateTime();
        } else {
            $this->reservationDate = $reservationDate;
        }
        return $this;
    }

    public function getTotalPassengers(): int
    {
        return $this->totalPassengers;
    }

    public function setTotalPassengers(int $totalPassengers): self
    {
        $this->totalPassengers = $totalPassengers;
        return $this;
    }

    public function getReservationStatus(): string
    {
        return $this->reservationStatus;
    }

    public function setReservationStatus(string $reservationStatus): self
    {
        $this->reservationStatus = $reservationStatus;
        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function generateRandomValues(EntityManagerInterface $entityManager): void
    {
        $this->setFlightCrew(rand(4, 20));

        $aircraftModels = ['Airbus A320', 'Boeing 737', 'Boeing 747', 'Airbus A380', 'Cessna 172'];
        $this->setAircraft($aircraftModels[array_rand($aircraftModels)]);

        if ($this->reservationDate < new \DateTime()) {
            $this->setReservationDate(new \DateTime());
        }

        $this->setGate('A' . rand(1, 20));

        $this->setTotalPrice($this->totalPassengers * rand(50, 100));
    }
}
