<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Hotel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\HotelRepository;
use App\Repository\RatingRepository;
use App\Repository\ReservationChambreRepository;

class RatingController extends AbstractController
{
    #[Route('/rating/{hotelId}/submit', name: 'app_rating_submit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitRating(Request $request, int $hotelId, HotelRepository $hotelRepository, RatingRepository $ratingRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$this->isCsrfTokenValid('rate', $request->request->get('_token'))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token invalide'
                ], Response::HTTP_BAD_REQUEST);
            }

            $hotel = $hotelRepository->find($hotelId);
            if (!$hotel) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Hôtel non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être connecté pour noter un hôtel'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Check if user has already rated this hotel
            $existingRating = $ratingRepository->findOneBy([
                'hotel' => $hotel,
                'user' => $user
            ]);

            if ($existingRating) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous avez déjà noté cet hôtel'
                ], Response::HTTP_BAD_REQUEST);
            }

            $rating = $request->request->get('rating');
            if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Note invalide'
                ], Response::HTTP_BAD_REQUEST);
            }

            $newRating = new Rating();
            $newRating->setHotel($hotel)
                     ->setUser($user)
                     ->setStars((int)$rating);

            $entityManager->persist($newRating);
            $entityManager->flush();

            $stats = $ratingRepository->getRatingStats($hotel);

            return new JsonResponse([
                'success' => true,
                'message' => 'Note enregistrée avec succès',
                'averageRating' => $stats['average'],
                'totalRatings' => $stats['total']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement de la note'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rating/{hotelId}/check', name: 'app_rating_check', methods: ['GET'])]
    public function checkRating(int $hotelId, HotelRepository $hotelRepository, RatingRepository $ratingRepository, ReservationChambreRepository $reservationRepo): JsonResponse
    {
        try {
            $hotel = $hotelRepository->find($hotelId);
            if (!$hotel) {
                throw new NotFoundHttpException('Hotel not found');
            }

            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse([
                    'canRate' => false,
                    'message' => 'Vous devez être connecté pour noter un hôtel'
                ]);
            }

            // Check if user has already rated this hotel
            $existingRating = $ratingRepository->findOneBy([
                'hotel' => $hotel,
                'user' => $user
            ]);

            if ($existingRating) {
                return new JsonResponse([
                    'canRate' => false,
                    'message' => 'Vous avez déjà noté cet hôtel'
                ]);
            }

            // Check if user has a reservation for this hotel
            $hasReservation = $reservationRepo->createQueryBuilder('r')
                ->join('r.chambre', 'c')
                ->join('c.hotel', 'h')
                ->where('h.id = :hotelId')
                ->andWhere('r.user = :user')
                ->andWhere('r.dateFin >= :today')
                ->setParameter('hotelId', $hotelId)
                ->setParameter('user', $user)
                ->setParameter('today', new \DateTime())
                ->getQuery()
                ->getResult();

            if (empty($hasReservation)) {
                return new JsonResponse([
                    'canRate' => false,
                    'message' => 'Vous devez avoir effectué une réservation dans cet hôtel pour pouvoir le noter'
                ]);
            }

            $stats = $ratingRepository->getRatingStats($hotel);

            return new JsonResponse([
                'canRate' => true,
                'averageRating' => $stats['average'],
                'totalRatings' => $stats['total']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 