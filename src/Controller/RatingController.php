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

class RatingController extends AbstractController
{
    #[Route('/rating/submit/{hotelId}', name: 'app_rating_submit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitRating(Request $request, EntityManagerInterface $entityManager, int $hotelId): JsonResponse
    {
        try {
            if (!$request->isXmlHttpRequest()) {
                throw new \Exception('Only AJAX requests are allowed');
            }

            // Validate CSRF token
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('rating', $submittedToken)) {
                throw new InvalidCsrfTokenException('Invalid CSRF token');
            }

            $user = $this->getUser();
            $hotel = $entityManager->getRepository(Hotel::class)->find($hotelId);

            if (!$hotel) {
                throw new \Exception('Hôtel non trouvé');
            }

            $ratingRepository = $entityManager->getRepository(Rating::class);
            
            // Check if user can rate
            if (!$ratingRepository->canUserRateHotel($user, $hotel)) {
                throw new \Exception('Vous ne pouvez pas noter cet hôtel. Soit vous n\'avez pas de réservation, soit vous avez déjà noté cet hôtel.');
            }

            $stars = $request->request->get('stars');
            if (!is_numeric($stars) || $stars < 1 || $stars > 5) {
                throw new \Exception('Note invalide. La note doit être entre 1 et 5.');
            }

            $rating = new Rating();
            $rating->setUser($user);
            $rating->setHotel($hotel);
            $rating->setStars((int)$stars);

            $entityManager->persist($rating);
            $entityManager->flush();

            // Get updated stats
            $stats = $ratingRepository->getRatingStats($hotel);

            return new JsonResponse([
                'success' => true,
                'message' => 'Note enregistrée avec succès',
                'averageRating' => $stats['average'],
                'totalRatings' => $stats['total']
            ]);

        } catch (InvalidCsrfTokenException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Session expirée. Veuillez rafraîchir la page.'
            ], 403);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/rating/check/{hotelId}', name: 'app_rating_check', methods: ['GET'])]
    public function checkRating(Request $request, EntityManagerInterface $entityManager, int $hotelId): JsonResponse
    {
        try {
            $hotel = $entityManager->getRepository(Hotel::class)->find($hotelId);

            if (!$hotel) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Hôtel non trouvé',
                    'averageRating' => 0,
                    'totalRatings' => 0
                ]);
            }

            $ratingRepository = $entityManager->getRepository(Rating::class);
            $stats = $ratingRepository->getRatingStats($hotel);
            
            $user = $this->getUser();
            if ($user) {
                $canRate = $ratingRepository->canUserRateHotel($user, $hotel);
                $currentRating = $ratingRepository->findUserRatingForHotel($user, $hotel);
                
                return new JsonResponse([
                    'success' => true,
                    'canRate' => $canRate,
                    'currentRating' => $currentRating ? $currentRating->getStars() : null,
                    'averageRating' => $stats['average'],
                    'totalRatings' => $stats['total']
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'averageRating' => $stats['average'],
                'totalRatings' => $stats['total']
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'averageRating' => 0,
                'totalRatings' => 0
            ]);
        }
    }
} 