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

class PaymentController extends AbstractController
{
    private $stripe;
    private $mailer;

    public function __construct(StripeClient $stripe, MailerInterface $mailer)
    {
        $this->stripe = $stripe;
        $this->mailer = $mailer;
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
            $data = json_decode($request->getContent(), true);
            
            // Validate required fields
            if (!isset($data['chambreId'], $data['dateDebut'], $data['dateFin'], $data['amount'])) {
                throw new \Exception('Missing required fields');
            }

            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $startDate = new \DateTime($data['dateDebut']);
            $endDate = new \DateTime($data['dateFin']);

            // Check if room is still available
            $chambre = $entityManager->getRepository(Chambre::class)->find($data['chambreId']);
            if (!$chambre) {
                throw new \Exception('Room not found');
            }

            // Check for existing reservations
            $existingReservations = $entityManager->getRepository(ReservationChambre::class)->findOverlappingReservations(
                $data['chambreId'],
                $startDate,
                $endDate
            );

            if (count($existingReservations) > 0) {
                throw new \Exception('Cette chambre est déjà réservée pour les dates sélectionnées.');
            }

            // Create Stripe Checkout Session
            try {
                $successUrl = $this->generateUrl('app_reservation_success', [
                    'chambreId' => $data['chambreId'],
                    'dateDebut' => $data['dateDebut'],
                    'dateFin' => $data['dateFin'],
                    'amount' => $data['amount']
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                
                // Append the session_id parameter
                $successUrl .= (parse_url($successUrl, PHP_URL_QUERY) ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';

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
            // Verify the payment was successful with Stripe
            $sessionId = $request->query->get('session_id');
            if (!$sessionId) {
                throw new \Exception('No session ID provided');
            }

            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            if ($session->payment_status !== 'paid') {
                throw new \Exception('Payment not completed');
            }

            $chambreId = $request->query->get('chambreId');
            $dateDebut = new \DateTime($request->query->get('dateDebut'));
            $dateFin = new \DateTime($request->query->get('dateFin'));
            $amount = $request->query->get('amount');

            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Start transaction
            $entityManager->beginTransaction();
            try {
                // Get the Chambre entity with lock
                $chambre = $entityManager->find(Chambre::class, $chambreId, LockMode::PESSIMISTIC_WRITE);
                if (!$chambre) {
                    throw new \Exception('Room not found');
                }

                // Check for overlapping reservations within transaction
                $existingReservations = $entityManager->getRepository(ReservationChambre::class)
                    ->findOverlappingReservations($chambreId, $dateDebut, $dateFin);

                if (count($existingReservations) > 0) {
                    throw new \Exception('Cette chambre a été réservée par quelqu\'un d\'autre. Veuillez choisir une autre chambre ou d\'autres dates.');
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

                // Create payment record
                $payment = new Paiement();
                $payment->setIdUser($user->getId());
                $payment->setUser($user);
                $payment->setMontant((float)$amount);
                $payment->setDatePaiement(new \DateTime());

                // Save to database
                $entityManager->persist($reservation);
                $entityManager->persist($payment);
                $entityManager->flush();

                // Generate QR code
                $qrCode = new QrCode($chambre->getHotel()->getLocalisation());
                $writer = new SvgWriter();
                $result = $writer->write($qrCode);
                $qrCodeSvg = $result->getString();

                // Send confirmation email
                try {
                    $email = (new Email())
                        ->from('triptogo2025@gmail.com')
                        ->to($user->getMail())
                        ->subject('Confirmation de votre réservation - TripToGo')
                        ->html('
                        <div style="font-family: Arial, sans-serif; background-color: #f4f7fa; padding: 30px;">
                            <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <h1 style="color: #ff681a; text-align: center;">Confirmation de Réservation</h1>
                                
                                <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
                                    <h2 style="color: #333333;">Détails de la réservation</h2>
                                    <p><strong>Numéro de réservation:</strong> #' . $reservation->getId() . '</p>
                                    <p><strong>Hôtel:</strong> ' . $chambre->getHotel()->getNom() . '</p>
                                    <p><strong>Localisation:</strong> ' . $chambre->getHotel()->getLocalisation() . '</p>
                                    <p><strong>Type de chambre:</strong> ' . $chambre->getType() . '</p>
                                    <p><strong>Date d\'arrivée:</strong> ' . $dateDebut->format('d/m/Y') . '</p>
                                    <p><strong>Date de départ:</strong> ' . $dateFin->format('d/m/Y') . '</p>
                                    <p><strong>Nombre de nuits:</strong> ' . $dateDebut->diff($dateFin)->days . '</p>
                                    <p><strong>Montant total:</strong> ' . $amount . ' DT</p>
                                </div>
                                
                                <div style="text-align: center; margin: 20px 0;">
                                    <p>Scannez le QR code ci-dessous pour voir la localisation de l\'hôtel:</p>
                                    ' . $qrCodeSvg . '
                                </div>
                                
                                <hr style="border: none; border-top: 1px solid #eeeeee; margin: 30px 0;">
                                <p style="font-size: 12px; color: #aaaaaa; text-align: center;">
                                    © ' . date("Y") . ' TripToGo. Tous droits réservés.
                                </p>
                            </div>
                        </div>
                        ');

                    $this->mailer->send($email);
                } catch (\Exception $e) {
                    // Log the error but don't stop the process
                    // The reservation is still valid even if email fails
                    $this->addFlash('warning', 'La réservation est confirmée mais l\'email de confirmation n\'a pas pu être envoyé.');
                }

                // Commit transaction
                $entityManager->commit();

                // Add success message
                $this->addFlash('success', 'Votre réservation a été confirmée avec succès !');

                return $this->render('hotel_chambre/reservation_success.html.twig', [
                    'reservation' => $reservation,
                    'payment' => $payment
                ]);

            } catch (\Exception $e) {
                // Rollback transaction on error
                $entityManager->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la finalisation de la réservation: ' . $e->getMessage());
            return $this->redirectToRoute('app_home');
        }
    }
} 