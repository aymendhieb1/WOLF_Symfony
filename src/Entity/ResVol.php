<?php

namespace App\Entity;

use App\Repository\ResVolRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: ResVolRepository::class)]
#[ORM\Table(name: 'reservation_vol')]
class ResVol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_resvol')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_user')]
    private ?int $idUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')]
    #[Ignore]
    private ?User $user = null;

    #[ORM\Column(name: 'FlightID')]
    private ?int $flightId = null;

    #[ORM\ManyToOne(targetEntity: Vol::class)]
    #[ORM\JoinColumn(name: 'FlightID', referencedColumnName: 'FlightID')]
    #[Ignore]
    private ?Vol $vol = null;

    #[ORM\Column(name: 'Departure', length: 255)]
    private ?string $departure = null;

    #[ORM\Column(name: 'Destination', length: 255)]
    private ?string $destination = null;

    #[ORM\Column(name: 'DepartureTime', type: 'datetime')]
    private ?\DateTimeInterface $departureTime = null;

    #[ORM\Column(name: 'ArrivalTime', type: 'datetime')]
    private ?\DateTimeInterface $arrivalTime = null;

    #[ORM\Column(name: 'ClasseChaise', length: 255)]
    private ?string $classeChaise = null;

    #[ORM\Column(name: 'FlightPrice')]
    private ?int $flightPrice = null;

    #[ORM\Column(name: 'Airline', length: 255)]
    private ?string $airline = null;

    #[ORM\Column(name: 'VolReservationDate', type: 'datetime')]
    private ?\DateTimeInterface $volReservationDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function setIdUser(int $idUser): static
    {
        $this->idUser = $idUser;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        if ($user) {
            $this->idUser = $user->getId();
        }
        return $this;
    }

    public function getFlightId(): ?int
    {
        return $this->flightId;
    }

    public function setFlightId(int $flightId): static
    {
        $this->flightId = $flightId;
        return $this;
    }

    public function getVol(): ?Vol
    {
        return $this->vol;
    }

    public function setVol(?Vol $vol): static
    {
        $this->vol = $vol;
        if ($vol) {
            $this->flightId = $vol->getId();
        }
        return $this;
    }

    public function getDeparture(): ?string
    {
        return $this->departure;
    }

    public function setDeparture(string $departure): static
    {
        $this->departure = $departure;
        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;
        return $this;
    }

    public function getDepartureTime(): ?\DateTimeInterface
    {
        return $this->departureTime;
    }

    public function setDepartureTime(\DateTimeInterface $departureTime): static
    {
        $this->departureTime = $departureTime;
        return $this;
    }

    public function getArrivalTime(): ?\DateTimeInterface
    {
        return $this->arrivalTime;
    }

    public function setArrivalTime(\DateTimeInterface $arrivalTime): static
    {
        $this->arrivalTime = $arrivalTime;
        return $this;
    }

    public function getClasseChaise(): ?string
    {
        return $this->classeChaise;
    }

    public function setClasseChaise(string $classeChaise): static
    {
        $this->classeChaise = $classeChaise;
        return $this;
    }

    public function getFlightPrice(): ?int
    {
        return $this->flightPrice;
    }

    public function setFlightPrice(int $flightPrice): static
    {
        $this->flightPrice = $flightPrice;
        return $this;
    }

    public function getAirline(): ?string
    {
        return $this->airline;
    }

    public function setAirline(string $airline): static
    {
        $this->airline = $airline;
        return $this;
    }

    public function getVolReservationDate(): ?\DateTimeInterface
    {
        return $this->volReservationDate;
    }

    public function setVolReservationDate(\DateTimeInterface $volReservationDate): static
    {
        $this->volReservationDate = $volReservationDate;
        return $this;
    }
} 