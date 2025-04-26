<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Entity\Chambre;
use App\Repository\HotelRepository;
use App\Repository\ChambreRepository;
use App\Repository\ReservationChambreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class HotelChambreController extends AbstractController
{
    #[Route('/hotel-chambre', name: 'app_hotel_chambre')]
    public function index(Request $request, HotelRepository $hotelRepository, PaginatorInterface $paginator): Response
    {
        // Get all hotels
        $query = $hotelRepository->createQueryBuilder('h')
            ->orderBy('h.nom', 'ASC')
            ->getQuery();

        // Paginate the results
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9 // Items per page
        );

        // Get min and max prices for the price slider
        $minPrice = $hotelRepository->createQueryBuilder('h')
            ->select('MIN(h.promotion)')
            ->getQuery()
            ->getSingleScalarResult();

        $maxPrice = $hotelRepository->createQueryBuilder('h')
            ->select('MAX(h.promotion)')
            ->getQuery()
            ->getSingleScalarResult();

        // Define room types for the type filter
        $roomTypes = [
            ['value' => 'Simple', 'name' => 'Simple'],
            ['value' => 'Double', 'name' => 'Double'],
            ['value' => 'Suite', 'name' => 'Suite'],
            ['value' => 'Suite', 'name' => 'Deluxe'],
            ['value' => 'Suite', 'name' => 'Executive Room'],

        ];

        // Define filters for the filter section
        $filters = [
            ['id' => 'filter1', 'value' => 'popular', 'label' => 'Populaire'],
            ['id' => 'filter2', 'value' => 'new', 'label' => 'Nouveau'],
            ['id' => 'filter3', 'value' => 'featured', 'label' => 'En vedette'],
        ];

        return $this->render('hotel_chambre/index.html.twig', [
            'hotels' => $pagination,
            'totalPages' => ceil($pagination->getTotalItemCount() / 9),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'roomTypes' => $roomTypes,
            'filters' => $filters,
        ]);
    }

    #[Route('/hotel-chambre/search', name: 'app_hotel_chambre_search')]
    public function search(Request $request, HotelRepository $hotelRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $location = $request->query->get('location');
        $type = $request->query->get('type');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $filter = $request->query->get('filter');

        $queryBuilder = $hotelRepository->createQueryBuilder('h');

        if ($search) {
            $queryBuilder->andWhere('h.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($location) {
            $queryBuilder->andWhere('h.localisation LIKE :location')
                ->setParameter('location', '%' . $location . '%');
        }

        if ($type) {
            $queryBuilder->join('h.chambres', 'c')
                ->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        if ($minPrice && $maxPrice) {
            $queryBuilder->andWhere('h.promotion BETWEEN :minPrice AND :maxPrice')
                ->setParameter('minPrice', $minPrice)
                ->setParameter('maxPrice', $maxPrice);
        }

        // Apply sorting based on filter
        if ($filter) {
            switch ($filter) {
                case 'popular':
                    $queryBuilder->orderBy('h.nom', 'ASC');
                    break;
                case 'new':
                    $queryBuilder->orderBy('h.nom', 'DESC');
                    break;
                case 'featured':
                    $queryBuilder->orderBy('h.promotion', 'DESC');
                    break;
            }
        }

        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9
        );

        // Get min and max prices for the price slider
        $minPrice = $hotelRepository->createQueryBuilder('h')
            ->select('MIN(h.promotion)')
            ->getQuery()
            ->getSingleScalarResult();

        $maxPrice = $hotelRepository->createQueryBuilder('h')
            ->select('MAX(h.promotion)')
            ->getQuery()
            ->getSingleScalarResult();

        // Define room types for the type filter
        $roomTypes = [
            ['value' => 'Simple', 'name' => 'Simple'],
            ['value' => 'Double', 'name' => 'Double'],
            ['value' => 'Suite', 'name' => 'Suite'],
        ];

        // Define filters for the filter section
        $filters = [
            ['id' => 'filter1', 'value' => 'popular', 'label' => 'Populaire'],
            ['id' => 'filter2', 'value' => 'new', 'label' => 'Nouveau'],
            ['id' => 'filter3', 'value' => 'featured', 'label' => 'En vedette'],
        ];

        return $this->render('hotel_chambre/index.html.twig', [
            'hotels' => $pagination,
            'totalPages' => ceil($pagination->getTotalItemCount() / 9),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'roomTypes' => $roomTypes,
            'filters' => $filters,
        ]);
    }

    #[Route('/hotel-chambre/{id}', name: 'app_hotel_rooms')]
    public function showHotelRooms(Hotel $hotel, ChambreRepository $chambreRepository): Response
    {
        $chambres = $chambreRepository->findBy(['hotel' => $hotel]);

        return $this->render('hotel_chambre/hotel_rooms.html.twig', [
            'hotel' => $hotel,
            'chambres' => $chambres,
        ]);
    }

    #[Route('/hotel-chambre/rooms/{hotelId}', name: 'app_hotel_chambre_rooms', methods: ['GET'])]
    public function getRooms(int $hotelId, ChambreRepository $chambreRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // First, get the hotel entity
            $hotel = $entityManager->getRepository(Hotel::class)->find($hotelId);
            
            if (!$hotel) {
                return new JsonResponse([
                    'error' => 'Hotel not found',
                    'hotelId' => $hotelId
                ], 404);
            }

            // Get rooms for this hotel
            $rooms = $chambreRepository->findBy(['hotel' => $hotel]);
            
            $roomsData = [];
            foreach ($rooms as $room) {
                $roomsData[] = [
                    'id' => $room->getId(),
                    'type' => $room->getType(),
                    'prix' => $room->getPrix(),
                    'disponibilite' => $room->getDisponibilite(),
                    'description' => $room->getDescription(),
                ];
            }

            return new JsonResponse([
                'success' => true,
                'hotelId' => $hotelId,
                'hotelName' => $hotel->getNom(),
                'roomCount' => count($roomsData),
                'rooms' => $roomsData
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred while fetching rooms',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/hotel-chambre/reservation/{id}', name: 'app_room_reservation')]
    public function reservation(
        Chambre $room,
        ReservationChambreRepository $reservationRepo
    ): Response {
        // Get all reserved dates for this room
        $reservations = $reservationRepo->findBy(['id_chambre' => $room->getId()]);
        
        $reservedDates = [];
        foreach ($reservations as $reservation) {
            $start = $reservation->getDateDebut();
            $end = $reservation->getDateFin();
            
            // Create array of all dates between start and end
            $period = new \DatePeriod(
                $start,
                new \DateInterval('P1D'),
                $end->modify('+1 day')
            );
            
            foreach ($period as $date) {
                $reservedDates[] = $date->format('Y-m-d');
            }
        }

        return $this->render('hotel_chambre/reservation.html.twig', [
            'room' => $room,
            'hotel' => $room->getHotel(),
            'reserved_dates' => array_unique($reservedDates)
        ]);
    }
} 