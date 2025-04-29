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
use Psr\Log\LoggerInterface;
use App\Entity\ReservationChambre;

#[Route('/hotels')]
class HotelChambreController extends AbstractController
{
    private $logger;
    private $entityManager;
    private $hotelRepository;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, HotelRepository $hotelRepository)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->hotelRepository = $hotelRepository;
    }

    #[Route('/', name: 'app_hotel_chambre_index')]
    public function index(Request $request): Response
    {
        return $this->render('hotel_chambre/index.html.twig');
    }

    #[Route('/list', name: 'app_hotel_chambre_list', methods: ['GET'])]
    public function listAction(Request $request, HotelRepository $hotelRepository): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $perPage = 6;
            $search = $request->query->get('search');
            $sort = $request->query->get('sort', 'default');
            $minPrice = $request->query->get('minPrice');
            $maxPrice = $request->query->get('maxPrice', 1000);

            // Create base query for hotels
            $queryBuilder = $hotelRepository->createQueryBuilder('h')
                ->select('DISTINCT h')
                ->leftJoin('h.chambres', 'c');

            // Apply search filter
            if ($search) {
                $queryBuilder->andWhere('h.nom LIKE :search OR h.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            // Apply price range filter
            if ($minPrice !== null && $maxPrice !== null) {
                $queryBuilder->andWhere('c.prix BETWEEN :minPrice AND :maxPrice')
                    ->setParameter('minPrice', $minPrice)
                    ->setParameter('maxPrice', $maxPrice);
            }

            // Apply sorting
            switch ($sort) {
                case 'price_asc':
                    $queryBuilder->orderBy('MIN(c.prix)', 'ASC');
                    break;
                case 'price_desc':
                    $queryBuilder->orderBy('MIN(c.prix)', 'DESC');
                    break;
                case 'promotion':
                    $queryBuilder->orderBy('h.promotion', 'DESC');
                    break;
                case 'reservations':
                    $queryBuilder->leftJoin('c.reservations', 'res')
                                ->groupBy('h.id')
                                ->orderBy('COUNT(res)', 'DESC');
                    break;
                default:
                    $queryBuilder->orderBy('h.nom', 'ASC');
                    break;
            }

            // Get total count before applying pagination
            $countQuery = clone $queryBuilder;
            $countQuery->select('COUNT(DISTINCT h.id)');
            $countQuery->resetDQLPart('orderBy');
            $countQuery->resetDQLPart('groupBy');
            $totalItems = (int) $countQuery->getQuery()->getSingleScalarResult();

            // Calculate pagination
            $totalPages = ceil($totalItems / $perPage);
            $offset = ($page - 1) * $perPage;

            // Apply pagination
            $queryBuilder->setMaxResults($perPage)
                        ->setFirstResult($offset);

            $hotels = $queryBuilder->getQuery()->getResult();

            // Add rating information for each hotel
            foreach ($hotels as $hotel) {
                $hotel->averageRating = $hotel->getAverageRating();
                $hotel->totalRatings = $hotel->getTotalRatings();
            }

            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->render('hotel_chambre/_hotel_list.html.twig', [
                    'hotels' => $hotels,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => $totalPages,
                        'totalItems' => $totalItems,
                        'hasPreviousPage' => $page > 1,
                        'hasNextPage' => $page < $totalPages,
                        'pages' => range(max(1, $page - 2), min($totalPages, $page + 2))
                    ]
                ]);
            }

            // If not an AJAX request, return the full page
            return $this->render('hotel_chambre/index.html.twig', [
                'hotels' => $hotels,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalItems' => $totalItems,
                    'hasPreviousPage' => $page > 1,
                    'hasNextPage' => $page < $totalPages,
                    'pages' => range(max(1, $page - 2), min($totalPages, $page + 2))
                ]
            ]);
        } catch (\Exception $e) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse([
                    'error' => $e->getMessage()
                ], 400);
            }
            throw $e;
        }
    }

    #[Route('/hotel-chambre/filter', name: 'app_hotel_chambre_filter', methods: ['GET'])]
    public function filter(
        Request $request,
        HotelRepository $hotelRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            // Get all filter parameters
            $search = $request->query->get('search');
            $location = $request->query->get('location');
            $type = $request->query->get('type');
            $minPrice = $request->query->get('minPrice');
            $maxPrice = $request->query->get('maxPrice');
            $sort = $request->query->get('sort', 'popular');
            $page = $request->query->getInt('page', 1);
            $rating = $request->query->get('rating');

            $queryBuilder = $hotelRepository->createQueryBuilder('h')
                ->leftJoin('h.chambres', 'c')
                ->leftJoin('h.ratings', 'r')
                ->groupBy('h.id');

            // Apply search filter
            if ($search) {
                $queryBuilder->andWhere('h.nom LIKE :search OR h.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            // Apply location filter
            if ($location) {
                $queryBuilder->andWhere('h.localisation LIKE :location')
                    ->setParameter('location', '%' . $location . '%');
            }

            // Apply room type filter
            if ($type) {
                $queryBuilder->andWhere('c.type = :type')
                    ->setParameter('type', $type);
            }

            // Apply price range filter
            if ($minPrice !== null && $maxPrice !== null) {
                $queryBuilder->andWhere('h.prix BETWEEN :minPrice AND :maxPrice')
                    ->setParameter('minPrice', $minPrice)
                    ->setParameter('maxPrice', $maxPrice);
            }

            // Apply rating filter
            if ($rating) {
                $queryBuilder->having('AVG(r.stars) >= :rating')
                    ->setParameter('rating', $rating);
            }

            // Apply sorting
            switch ($sort) {
                case 'price_asc':
                    $queryBuilder->orderBy('h.prix', 'ASC');
                    break;
                case 'price_desc':
                    $queryBuilder->orderBy('h.prix', 'DESC');
                    break;
                case 'rating':
                    $queryBuilder->orderBy('AVG(r.stars)', 'DESC');
                    break;
                case 'new':
                    $queryBuilder->orderBy('h.id', 'DESC');
                    break;
                case 'featured':
                    $queryBuilder->andWhere('h.promotion > 0')
                        ->orderBy('h.promotion', 'DESC');
                    break;
                case 'popular':
                default:
                    $queryBuilder->orderBy('h.nom', 'ASC');
                    break;
            }

            // Get paginated results
            $pagination = $paginator->paginate(
                $queryBuilder->getQuery(),
                $page,
                9
            );

            // Prepare hotels data
            $hotels = [];
            foreach ($pagination->getItems() as $hotel) {
                $stats = $hotelRepository->getRatingStats($hotel);
                $minRoomPrice = $hotelRepository->getMinRoomPrice($hotel);
                
                $hotels[] = [
                    'id' => $hotel->getId(),
                    'nom' => $hotel->getNom(),
                    'image' => $hotel->getImage(),
                    'promotion' => $hotel->getPromotion(),
                    'minPrice' => $minRoomPrice,
                    'discountedPrice' => $hotel->getPromotion() > 0 ? 
                        $hotelRepository->getDiscountedPrice($minRoomPrice, $hotel->getPromotion()) : 
                        null,
                    'rating' => [
                        'average' => $stats['average'],
                        'total' => $stats['total']
                    ]
                ];
            }

            return new JsonResponse([
                'success' => true,
                'hotels' => $hotels,
                'pagination' => [
                    'current' => $pagination->getCurrentPageNumber(),
                    'total' => ceil($pagination->getTotalItemCount() / 9),
                    'count' => $pagination->getTotalItemCount()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
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

    #[Route('/hotel-chambre/{id}', name: 'app_hotel_rooms', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showHotelRooms(Request $request, Hotel $hotel, ChambreRepository $chambreRepository): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $perPage = 6;
            $search = $request->query->get('search');
            $sort = $request->query->get('sort', 'default');

            // Create base query for rooms
            $queryBuilder = $chambreRepository->createQueryBuilder('c')
                ->where('c.hotel = :hotel')
                ->setParameter('hotel', $hotel);

            // Apply search filter
            if ($search) {
                $queryBuilder->andWhere('c.type LIKE :search OR c.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            // Apply sorting
            switch ($sort) {
                case 'price_asc':
                    $queryBuilder->orderBy('c.prix', 'ASC');
                    break;
                case 'price_desc':
                    $queryBuilder->orderBy('c.prix', 'DESC');
                    break;
                case 'type':
                    $queryBuilder->orderBy('c.type', 'ASC');
                    break;
                default:
                    $queryBuilder->orderBy('c.id', 'ASC');
                    break;
            }

            // Get total count before applying pagination
            $countQuery = clone $queryBuilder;
            $countQuery->select('COUNT(c.id)');
            $totalItems = (int) $countQuery->getQuery()->getSingleScalarResult();

            // Calculate pagination
            $totalPages = ceil($totalItems / $perPage);
            $offset = ($page - 1) * $perPage;

            // Apply pagination
            $queryBuilder->setMaxResults($perPage)
                        ->setFirstResult($offset);

            $chambres = $queryBuilder->getQuery()->getResult();

            // Prepare pagination data
            $pagination = [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'hasPreviousPage' => $page > 1,
                'hasNextPage' => $page < $totalPages,
                'pages' => range(max(1, $page - 2), min($totalPages, $page + 2))
            ];

            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->render('hotel_chambre/_room_list.html.twig', [
                    'chambres' => $chambres,
                    'pagination' => $pagination,
                    'hotel' => $hotel
                ]);
            }

            return $this->render('hotel_chambre/rooms.html.twig', [
                'hotel' => $hotel,
                'chambres' => $chambres,
                'pagination' => $pagination
            ]);
        } catch (\Exception $e) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse([
                    'error' => $e->getMessage()
                ], 400);
            }
            throw $e;
        }
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
        // Get all reservations for this room
        $reservations = $reservationRepo->findBy(['id_chambre' => $room->getId()]);
        
        // Format the dates for the template
        $unavailableDates = [];
        foreach ($reservations as $reservation) {
            $unavailableDates[] = [
                'start' => $reservation->getDateDebut()->format('Y-m-d'),
                'end' => $reservation->getDateFin()->format('Y-m-d')
            ];
        }

        return $this->render('hotel_chambre/reservation.html.twig', [
            'room' => $room,
            'hotel' => $room->getHotel(),
            'unavailableDates' => $unavailableDates,
            'stripe_public_key' => $this->getParameter('stripe_public_key')
        ]);
    }

    #[Route('/hotels/map', name: 'app_hotel_map')]
    public function map(): Response
    {
        $hotels = $this->hotelRepository->findAll();
        return $this->render('hotel_chambre/map.html.twig', [
            'hotels' => $hotels,
        ]);
    }
} 