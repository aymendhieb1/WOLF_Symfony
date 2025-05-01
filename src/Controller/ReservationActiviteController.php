<?php

namespace App\Controller;

use App\Entity\ReservationActivite;
use App\Entity\Session;
use App\Entity\User;
use App\Repository\ReservationActiviteRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\PdfGenerator;
use Symfony\Component\Security\Core\Security;

#[Route('/reservation/activite')]
class ReservationActiviteController extends AbstractController
{
    private $entityManager;
    private $reservationRepository;
    private $sessionRepository;
    private $security;
    private $pdfGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationActiviteRepository $reservationRepository,
        SessionRepository $sessionRepository,
        Security $security,
        PdfGenerator $pdfGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
        $this->sessionRepository = $sessionRepository;
        $this->security = $security;
        $this->pdfGenerator = $pdfGenerator;
    }

    #[Route('/add/{sessionId}', name: 'app_reservation_activite_add', methods: ['POST'])]
    public function add(int $sessionId): JsonResponse
    {
        // Check if user is authenticated
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User must be authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Find the session
        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
            return new JsonResponse(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if there are available places
        if ($session->getNbPlace() <= 0) {
            return new JsonResponse(['error' => 'No available places'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already has a reservation for this session
        $existingReservation = $this->reservationRepository->findByUserAndSession($user->getId(), $sessionId);
        
        if ($existingReservation) {
            return new JsonResponse(['error' => 'You already have a reservation for this session'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Create new reservation
            $reservation = new ReservationActivite();
            $reservation->setUser($user);
            $reservation->setSession($session);
            $reservation->setDateRes(new \DateTime());

            // Update session capacity
            $session->setNbPlace($session->getNbPlace() - 1);

            // Save changes
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            // Generate PDF
            $pdfContent = $this->pdfGenerator->generateReservationPdf($reservation);
            
            // Convert PDF content to base64
            $pdfBase64 = base64_encode($pdfContent);

            return new JsonResponse([
                'success' => true,
                'message' => 'Reservation created successfully',
                'reservationId' => $reservation->getId(),
                'pdf' => $pdfBase64
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error creating reservation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', name: 'app_reservation_activite_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            // Get the current user
            $user = $this->security->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
            }

            // Find the reservation
            $reservation = $this->reservationRepository->find($id);
            if (!$reservation) {
                return new JsonResponse(['error' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if the reservation belongs to the current user
            if ($reservation->getUser() !== $user) {
                return new JsonResponse(['error' => 'You cannot delete this reservation'], Response::HTTP_FORBIDDEN);
            }

            // Increment available places in the session
            $session = $reservation->getSession();
            $session->setNbPlace($session->getNbPlace() + 1);

            // Remove the reservation
            $this->entityManager->remove($reservation);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Reservation deleted successfully'], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while deleting the reservation'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/reservations', name: 'app_user_reservations', methods: ['GET'])]
    public function getUserReservations(): JsonResponse
    {
        try {
            $user = $this->security->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
            }

            $reservations = $this->reservationRepository->findUserReservations($user->getId());
            
            $formattedReservations = array_map(function($reservation) {
                $session = $reservation->getSession();
                $activite = $session->getActivite();
                
                return [
                    'id' => $reservation->getId(),
                    'dateReservation' => $reservation->getDateRes()->format('Y-m-d H:i:s'),
                    'session' => [
                        'id' => $session->getId(),
                        'date' => $session->getDate()->format('Y-m-d'),
                        'heure' => $session->getHeure()->format('H:i:s'),
                        'activite' => [
                            'nom' => $activite->getNom(),
                            'type' => $activite->getType(),
                            'prix' => $activite->getPrix()
                        ]
                    ]
                ];
            }, $reservations);

            return new JsonResponse($formattedReservations, Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while fetching reservations'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/pdf/{id}', name: 'app_reservation_pdf', methods: ['GET'])]
    public function getReservationPdf(int $id): JsonResponse
    {
        try {
            // Get the current user
            $user = $this->security->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
            }

            // Find the reservation
            $reservation = $this->reservationRepository->find($id);
            if (!$reservation) {
                return new JsonResponse(['error' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if the reservation belongs to the current user
            if ($reservation->getUser() !== $user) {
                return new JsonResponse(['error' => 'Not authorized to access this reservation'], Response::HTTP_FORBIDDEN);
            }

            // Generate PDF
            $pdfContent = $this->pdfGenerator->generateReservationPdf($reservation);
            
            // Convert to base64
            $pdfBase64 = base64_encode($pdfContent);

            return new JsonResponse([
                'success' => true,
                'pdf' => $pdfBase64
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error generating PDF: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list', name: 'app_reservation_activite_list', methods: ['GET'])]
    public function listAllReservations(): JsonResponse
    {
        // Check if user is authenticated and has admin role
        $user = $this->security->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Get all reservations with related data
        $reservations = $this->reservationRepository->findAllWithDetails();
        
        // Format the data for JSON response
        $formattedReservations = [];
        foreach ($reservations as $reservation) {
            $formattedReservations[] = [
                'id' => $reservation->getId(),
                'user' => [
                    'nom' => $reservation->getUser()->getNom(),
                    'prenom' => $reservation->getUser()->getPrenom()
                ],
                'session' => [
                    'date' => $reservation->getSession()->getDate()->format('Y-m-d'),
                    'activite' => [
                        'nom' => $reservation->getSession()->getActivite()->getNom()
                    ]
                ],
                'dateReservation' => $reservation->getDateRes()->format('Y-m-d')
            ];
        }

        return new JsonResponse(['reservations' => $formattedReservations]);
    }

    #[Route('/admin/delete/{id}', name: 'app_reservation_activite_admin_delete', methods: ['POST'])]
    public function adminDelete(int $id): JsonResponse
    {
        // Check if user is authenticated and has admin role
        $user = $this->security->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Find the reservation
        $reservation = $this->reservationRepository->find($id);
        if (!$reservation) {
            return new JsonResponse(['error' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Remove the reservation
            $this->entityManager->remove($reservation);
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete reservation: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 

