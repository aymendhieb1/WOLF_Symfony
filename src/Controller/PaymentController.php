<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\ReservationChambre;
use App\Entity\Chambre;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PaymentController extends AbstractController
{
    private $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }

    #[Route('/process-payment', name: 'app_process_payment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function processPayment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Validate required fields
            if (!isset($data['chambreId'], $data['dateDebut'], $data['dateFin'], $data['amount'])) {
                throw new \Exception('Missing required fields');
            }

            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Check if room is still available
            $chambre = $entityManager->getRepository(Chambre::class)->find($data['chambreId']);
            if (!$chambre) {
                throw new \Exception('Room not found');
            }

            $startDate = new \DateTime($data['dateDebut']);
            $endDate = new \DateTime($data['dateFin']);

            // Check for existing reservations
            $existingReservations = $entityManager->getRepository(ReservationChambre::class)->findOverlappingReservations(
                $data['chambreId'],
                $startDate,
                $endDate
            );

            if (count($existingReservations) > 0) {
                throw new \Exception('Room is no longer available for these dates');
            }

            // Create Stripe Checkout Session using the injected client
            try {
                $session = $this->stripe->checkout->sessions->create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => $chambre->getType() . ' - ' . $chambre->getHotel()->getNom(),
                                'description' => 'Du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y'),
                            ],
                            'unit_amount' => (int)($data['amount'] * 100), // Convert to cents
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => $this->generateUrl('app_reservation_success', [
                        'chambreId' => $data['chambreId'],
                        'dateDebut' => $data['dateDebut'],
                        'dateFin' => $data['dateFin'],
                        'amount' => $data['amount']
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    'cancel_url' => $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);

                return new JsonResponse([
                    'sessionId' => $session->id
                ]);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                throw new \Exception('Stripe error: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/reservation-success', name: 'app_reservation_success')]
    #[IsGranted('ROLE_USER')]
    public function success(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $chambreId = $request->query->get('chambreId');
            $dateDebut = new \DateTime($request->query->get('dateDebut'));
            $dateFin = new \DateTime($request->query->get('dateFin'));
            $amount = $request->query->get('amount');

            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Create reservation
            $reservation = new ReservationChambre();
            $reservation->setIdChambre($chambreId);
            $reservation->setIdUser($user->getId());
            $reservation->setDateDebut($dateDebut);
            $reservation->setDateFin($dateFin);
            $reservation->setDateReservation(new \DateTime());

            // Create payment record
            $payment = new Paiement();
            $payment->setIdUser($user->getId());
            $payment->setMontant($amount);

            // Save to database
            $entityManager->persist($reservation);
            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->render('hotel_chambre/reservation_success.html.twig', [
                'reservation' => $reservation
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la finalisation de la réservation.');
            return $this->redirectToRoute('app_home');
        }
    }
} 