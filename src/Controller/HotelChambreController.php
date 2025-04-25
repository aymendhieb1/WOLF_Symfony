<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Entity\Chambre;
use App\Repository\HotelRepository;
use App\Repository\ChambreRepository;
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

    #[Route('/hotel-chambre/rooms/{hotelId}', name: 'app_hotel_chambre_rooms', methods: ['GET'])]
    public function getRooms(int $hotelId, ChambreRepository $chambreRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // First, get the hotel entity
            $hotel = $entityManager->getRepository(Hotel::class)->find($hotelId);
            
            if (!$hotel) {
                return new JsonResponse(['error' => 'Hotel not found', 'hotelId' => $hotelId], 404);
            }

            // Get rooms directly from repository to ensure we're getting all rooms
            $rooms = $chambreRepository->findBy(['hotel' => $hotel]);
            
            if (empty($rooms)) {
                // Log debugging information
                return new JsonResponse([
                    'message' => 'No rooms found for this hotel',
                    'hotelId' => $hotelId,
                    'hotelName' => $hotel->getNom(),
                    'roomCount' => 0
                ]);
            }
            
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

            // Add debug information to the response
            $response = new JsonResponse([
                'rooms' => $roomsData,
                'debug' => [
                    'hotelId' => $hotelId,
                    'hotelName' => $hotel->getNom(),
                    'roomCount' => count($roomsData)
                ]
            ]);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error fetching rooms',
                'message' => $e->getMessage(),
                'hotelId' => $hotelId
            ], 500);
        }
    }
} 