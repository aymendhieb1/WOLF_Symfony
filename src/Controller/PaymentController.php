<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\ReservationChambre;
use App\Entity\Chambre;
use App\Entity\User;
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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Doctrine\DBAL\LockMode;
use Endroid\QrCode\Builder\Builder;
use chillerlan\QRCode\QRCode as ChillerlanQRCode;
use chillerlan\QRCode\QROptions;
use Psr\Log\LoggerInterface;

class PaymentController extends AbstractController
{
    private $stripe;
    private $mailer;
    private $logger;

    public function __construct(StripeClient $stripe, MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->stripe = $stripe;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    #[Route('/payment/{chambreId}/{dateDebut}/{dateFin}/{amount}', name: 'app_payment')]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        string $chambreId,
        string $dateDebut,
        string $dateFin,
        float $amount
    ): Response {
        return $this->render('payment/index.html.twig', [
            'chambreId' => $chambreId,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'amount' => $amount,
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
        ]);
    }

    #[Route('/process-payment', name: 'app_process_payment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function processPayment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $this->logger->info('Starting payment process');
            $data = json_decode($request->getContent(), true);
            
            // Log received data
            $this->logger->info('Received payment data', [
                'chambreId' => $data['chambreId'] ?? 'not set',
                'dateDebut' => $data['dateDebut'] ?? 'not set',
                'dateFin' => $data['dateFin'] ?? 'not set',
                'amount' => $data['amount'] ?? 'not set'
            ]);
            
            // Validate required fields
            if (!isset($data['chambreId'], $data['dateDebut'], $data['dateFin'], $data['amount'])) {
                $this->logger->error('Missing required fields in payment data');
                throw new \Exception('Tous les champs requis doivent être remplis.');
            }

            $user = $this->getUser();
            if (!$user) {
                $this->logger->error('User not authenticated during payment');
                throw new \Exception('Vous devez être connecté pour effectuer un paiement.');
            }

            $startDate = new \DateTime($data['dateDebut']);
            $endDate = new \DateTime($data['dateFin']);

            // Check if room is still available
            $chambre = $entityManager->getRepository(Chambre::class)->find($data['chambreId']);
            if (!$chambre) {
                $this->logger->error('Room not found', ['chambreId' => $data['chambreId']]);
                throw new \Exception('La chambre sélectionnée n\'existe plus.');
            }

            // Check for existing reservations
            $existingReservations = $entityManager->getRepository(ReservationChambre::class)->findOverlappingReservations(
                $data['chambreId'],
                $startDate,
                $endDate
            );

            if (count($existingReservations) > 0) {
                $this->logger->error('Room already reserved for selected dates', [
                    'chambreId' => $data['chambreId'],
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate' => $endDate->format('Y-m-d')
                ]);
                throw new \Exception('Cette chambre est déjà réservée pour les dates sélectionnées.');
            }

            // Create Stripe Checkout Session
            try {
                $this->logger->info('Creating Stripe checkout session');
                
                $successUrl = $this->generateUrl('app_reservation_success', [
                    'chambreId' => $data['chambreId'],
                    'dateDebut' => $data['dateDebut'],
                    'dateFin' => $data['dateFin'],
                    'amount' => $data['amount']
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                
                // Append the session_id parameter
                $successUrl .= (parse_url($successUrl, PHP_URL_QUERY) ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';

                $this->logger->info('Generated success URL', ['url' => $successUrl]);

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
                    'success_url' => $successUrl,
                    'cancel_url' => $this->generateUrl('app_payment_error', [
                        'error' => 'Paiement annulé',
                        'room_id' => $data['chambreId']
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);

                $this->logger->info('Stripe session created successfully', ['sessionId' => $session->id]);

                return new JsonResponse([
                    'sessionId' => $session->id
                ]);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->logger->error('Stripe API error', [
                    'error' => $e->getMessage(),
                    'code' => $e->getStripeCode()
                ]);
                throw new \Exception('Erreur Stripe: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            $this->logger->error('Payment process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            $this->logger->info('Starting reservation success process');
            
            // Verify the payment was successful with Stripe
            $sessionId = $request->query->get('session_id');
            if (!$sessionId) {
                $this->logger->error('No session ID provided in success callback');
                throw new \Exception('Session ID manquant');
            }

            $this->logger->info('Retrieving Stripe session', ['sessionId' => $sessionId]);
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            
            if ($session->payment_status !== 'paid') {
                $this->logger->error('Payment not completed', ['status' => $session->payment_status]);
                throw new \Exception('Le paiement n\'a pas été complété');
            }

            $chambreId = $request->query->get('chambreId');
            $dateDebut = new \DateTime($request->query->get('dateDebut'));
            $dateFin = new \DateTime($request->query->get('dateFin'));
            $amount = $request->query->get('amount');

            $this->logger->info('Processing reservation data', [
                'chambreId' => $chambreId,
                'dateDebut' => $dateDebut->format('Y-m-d'),
                'dateFin' => $dateFin->format('Y-m-d'),
                'amount' => $amount
            ]);

            $user = $this->getUser();
            if (!$user) {
                $this->logger->error('User not authenticated in success callback');
                throw new \Exception('Utilisateur non authentifié');
            }

            // Start transaction
            $entityManager->beginTransaction();
            try {
                $this->logger->info('Starting database transaction');

                // Get the Chambre entity with lock
                $chambre = $entityManager->find(Chambre::class, $chambreId, LockMode::PESSIMISTIC_WRITE);
                if (!$chambre) {
                    $this->logger->error('Room not found in success callback', ['chambreId' => $chambreId]);
                    throw new \Exception('Chambre non trouvée');
                }

                // Check for overlapping reservations within transaction
                $existingReservations = $entityManager->getRepository(ReservationChambre::class)
                    ->findOverlappingReservations($chambreId, $dateDebut, $dateFin);

                if (count($existingReservations) > 0) {
                    $this->logger->error('Room already reserved in success callback', [
                        'chambreId' => $chambreId,
                        'dateDebut' => $dateDebut->format('Y-m-d'),
                        'dateFin' => $dateFin->format('Y-m-d')
                    ]);
                    throw new \Exception('Cette chambre a été réservée par quelqu\'un d\'autre');
                }

                // Create reservation
                $reservation = new ReservationChambre();
                $reservation->setIdChambre($chambreId);
                $reservation->setChambre($chambre);
                $reservation->setIdUser($user->getId());
                $reservation->setUser($user);
                $reservation->setDateDebut($dateDebut);
                $reservation->setDateFin($dateFin);
                $reservation->setDateReservation(new \DateTime());

                $this->logger->info('Created reservation entity', ['reservationId' => $reservation->getId()]);

                // Create payment record
                $payment = new Paiement();
                $payment->setIdUser($user->getId());
                $payment->setUser($user);
                $payment->setMontant((float)$amount);

                $this->logger->info('Created payment entity', ['paymentId' => $payment->getId()]);

                // Save to database
                $entityManager->persist($reservation);
                $entityManager->persist($payment);
                $entityManager->flush();

                $this->logger->info('Successfully saved reservation and payment to database');

                // Generate QR code with hotel location
                $location = $chambre->getHotel()->getLocalisation();
                $mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($location);
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($mapsUrl);

                // Send confirmation email
                try {
                    $this->logger->info('Sending confirmation email');
                    $email = (new Email())
                        ->from('triptogo2025@gmail.com')
                        ->to($user->getMail())
                        ->subject('Confirmation de votre réservation - TripToGo')
                        ->html($this->renderView('emails/reservation_confirmation.html.twig', [
                            'reservation' => $reservation,
                            'payment' => $payment,
                            'hotelName' => $chambre->getHotel()->getNom(),
                            'qrCodeUrl' => $qrCodeUrl,
                            'location' => $location
                        ]));

                    $this->mailer->send($email);
                    $this->logger->info('Confirmation email sent successfully');
                } catch (\Exception $e) {
                    $this->logger->error('Failed to send confirmation email', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                // Commit transaction
                $entityManager->commit();
                $this->logger->info('Database transaction committed successfully');

                // Render success page
                return $this->render('payment/success.html.twig', [
                    'reservation' => $reservation,
                    'payment' => $payment,
                    'hotelName' => $chambre->getHotel()->getNom(),
                    'qrCodeUrl' => $qrCodeUrl,
                    'location' => $location
                ]);

            } catch (\Exception $e) {
                $this->logger->error('Error during database transaction', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $entityManager->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            $this->logger->error('Reservation success process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->render('payment/error.html.twig', [
                'error_message' => $e->getMessage(),
                'room_id' => $request->query->get('chambreId')
            ]);
        }
    }

    #[Route('/payment-error', name: 'app_payment_error')]
    public function error(Request $request): Response
    {
        $errorMessage = $request->query->get('error', 'Une erreur est survenue lors du traitement de votre paiement.');
        $roomId = $request->query->get('room_id');

        return $this->render('payment/error.html.twig', [
            'error_message' => $errorMessage,
            'room_id' => $roomId
        ]);
    }
} 