<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\ResVol;
use App\Entity\Vol;
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
use Doctrine\DBAL\LockMode;
use Psr\Log\LoggerInterface;
use App\Entity\PaiementVol;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaiementVolController extends AbstractController
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

    #[Route('/payment/vol/{volId}/{amount}', name: 'app_payment_vol')]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        string $volId,
        float $amount
    ): Response {
        return $this->render('paiement_vol/index.html.twig', [
            'volId' => $volId,
            'amount' => $amount,
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
        ]);
    }

    #[Route('/process-payment-vol', name: 'app_process_payment_vol', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function processPayment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $this->logger->info('Starting flight payment process');
            $data = json_decode($request->getContent(), true);
            
            // Log received data
            $this->logger->info('Received payment data', [
                'volId' => $data['volId'] ?? 'not set',
                'amount' => $data['amount'] ?? 'not set'
            ]);
            
            // Validate required fields
            if (!isset($data['volId'], $data['amount'])) {
                $this->logger->error('Missing required fields in payment data');
                throw new \Exception('Tous les champs requis doivent être remplis.');
            }

            $user = $this->getUser();
            if (!$user) {
                $this->logger->error('User not authenticated during payment');
                throw new \Exception('Vous devez être connecté pour effectuer un paiement.');
            }

            // Check if flight is still available
            $vol = $entityManager->getRepository(Vol::class)->find($data['volId']);
            if (!$vol) {
                $this->logger->error('Flight not found', ['volId' => $data['volId']]);
                throw new \Exception('Le vol sélectionné n\'existe plus.');
            }

            // Check if there are available seats
            if ($vol->getSiegesDisponibles() <= 0) {
                $this->logger->error('No available seats', ['volId' => $data['volId']]);
                throw new \Exception('Désolé, il n\'y a plus de sièges disponibles pour ce vol.');
            }

            // Create Stripe Checkout Session
            try {
                $this->logger->info('Creating Stripe checkout session');
                
                $successUrl = $this->generateUrl('app_reservation_vol_success', [
                    'volId' => $data['volId'],
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
                                'name' => 'Vol ' . $vol->getDepart() . ' - ' . $vol->getDestination(),
                                'description' => 'Compagnie: ' . $vol->getCompagnie() . ' | Classe: ' . $vol->getClasseChaise(),
                            ],
                            'unit_amount' => (int)($data['amount'] * 100), // Convert to cents
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => $successUrl,
                    'cancel_url' => $this->generateUrl('app_payment_vol_error', [
                        'error' => 'Paiement annulé',
                        'vol_id' => $data['volId'],
                        'amount' => $data['amount']
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

    #[Route('/reservation-vol-success', name: 'app_reservation_vol_success')]
    #[IsGranted('ROLE_USER')]
    public function success(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $this->logger->info('Starting flight reservation success process');
            
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

            $volId = $request->query->get('volId');
            $amount = $request->query->get('amount');

            $this->logger->info('Processing reservation data', [
                'volId' => $volId,
                'amount' => $amount
            ]);

            $user = $this->getUser();
            if (!$user) {
                $this->logger->error('User not authenticated in success callback');
                throw new \Exception('Utilisateur non authentifié');
            }

            $reservation = null;
            $payment = null;
            $vol = null;

            // Handle database operations in transaction
            try {
                $entityManager->beginTransaction();

                // Get the Vol entity with lock
                $vol = $entityManager->find(Vol::class, $volId, LockMode::PESSIMISTIC_WRITE);
                if (!$vol) {
                    throw new \Exception('Vol non trouvé');
                }

                // Check if there are still available seats
                if ($vol->getSiegesDisponibles() <= 0) {
                    throw new \Exception('Désolé, il n\'y a plus de sièges disponibles pour ce vol.');
                }

                // Create reservation
                $reservation = new ResVol();
                $reservation->setFlightId($vol->getId());
                $reservation->setVol($vol);
                $reservation->setIdUser($user->getId());
                $reservation->setUser($user);
                $reservation->setDeparture($vol->getDepart());
                $reservation->setDestination($vol->getDestination());
                $reservation->setDepartureTime($vol->getHeureDepart());
                $reservation->setArrivalTime($vol->getHeureArrivee());
                $reservation->setClasseChaise($vol->getClasseChaise());
                $reservation->setFlightPrice($vol->getPrix());
                $reservation->setAirline($vol->getCompagnie());
                $reservation->setVolReservationDate(new \DateTime());

                // Create payment record
                $payment = new PaiementVol();
                $payment->setIdUser($user->getId());
                $payment->setUser($user);
                $payment->setMontant((int)$amount);

                // Update available seats
                $vol->setSiegesDisponibles($vol->getSiegesDisponibles() - 1);

                // Save to database
                $entityManager->persist($reservation);
                $entityManager->persist($payment);
                $entityManager->flush();

                // Commit transaction
                $entityManager->commit();
                $this->logger->info('Successfully saved reservation and payment to database');

            } catch (\Exception $e) {
                // Rollback transaction on database error
                if ($entityManager->getConnection()->isTransactionActive()) {
                    $entityManager->rollback();
                }
                throw $e;
            }

            // Handle PDF generation and email sending outside transaction
            try {
                // Generate PDF receipt
                $html = $this->renderView('paiement_vol/receipt.html.twig', [
                    'payment' => $payment,
                    'vol' => $vol
                ]);

                $options = new Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isPhpEnabled', true);

                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                // Save PDF to a temporary location
                $pdfFilename = 'flight_receipt_' . $payment->getId() . '.pdf';
                $tempDir = sys_get_temp_dir();
                $pdfPath = $tempDir . '/' . $pdfFilename;
                file_put_contents($pdfPath, $dompdf->output());

                // Generate QR code for the flight ticket
                $ticketData = json_encode([
                    'reservation_id' => $reservation->getId(),
                    'flight_number' => $vol->getId(),
                    'passenger' => $user->getPrenom() . ' ' . $user->getNom(),
                    'from' => $vol->getDepart(),
                    'to' => $vol->getDestination(),
                    'date' => $vol->getHeureDepart()->format('Y-m-d'),
                    'time' => $vol->getHeureDepart()->format('H:i')
                ]);
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($ticketData);

                // Send confirmation email
                try {
                    $this->logger->info('Starting email sending process', [
                        'to' => $user->getMail(),
                        'payment_id' => $payment->getId(),
                        'reservation_id' => $reservation->getId(),
                        'mailer_class' => get_class($this->mailer)
                    ]);

                    // Log the mailer configuration
                    $this->logger->info('Mailer configuration', [
                        'dsn' => $this->getParameter('mailer_dsn'),
                        'from' => 'triptogo2025@gmail.com',
                        'environment' => $this->getParameter('kernel.environment')
                    ]);

                    $email = (new Email())
                        ->from('triptogo2025@gmail.com')
                        ->to($user->getMail())
                        ->subject('Confirmation de votre réservation de vol - TripToGo')
                        ->attachFromPath($pdfPath, 'Votre_reçu.pdf');

                    // Log the email content before sending
                    $this->logger->info('Preparing email content', [
                        'subject' => $email->getSubject(),
                        'to' => $email->getTo()[0]->getAddress(),
                        'from' => $email->getFrom()[0]->getAddress(),
                        'has_attachment' => !empty($email->getAttachments()),
                        'attachment_path' => $pdfPath,
                        'attachment_exists' => file_exists($pdfPath)
                    ]);

                    // Render and set the HTML content
                    $htmlContent = $this->renderView('emails/vol_reservation_confirmation.html.twig', [
                        'reservation' => $reservation,
                        'payment' => $payment,
                        'vol' => $vol,
                        'qrCodeUrl' => $qrCodeUrl,
                        'user' => $user
                    ]);
                    $email->html($htmlContent);

                    // Try to embed the logo
                    $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/primary.png';
                    if (file_exists($logoPath)) {
                        $this->logger->info('Logo file found, attempting to embed', ['path' => $logoPath]);
                        $email->embedFromPath($logoPath, 'logo');
                    } else {
                        $this->logger->warning('Logo file not found at path: ' . $logoPath);
                    }

                    // Send the email
                    $this->logger->info('Attempting to send email...');
                    try {
                        $this->mailer->send($email);
                        $this->logger->info('Email sent successfully');
                    } catch (\Exception $e) {
                        $this->logger->error('Failed to send email', [
                            'error' => $e->getMessage(),
                            'error_class' => get_class($e),
                            'error_code' => $e->getCode(),
                            'error_file' => $e->getFile(),
                            'error_line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }

                    // Clean up the temporary PDF file
                    if (file_exists($pdfPath)) {
                        unlink($pdfPath);
                        $this->logger->info('Temporary PDF file cleaned up');
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Failed to send flight confirmation email', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'to' => $user->getMail(),
                        'payment_id' => $payment->getId(),
                        'reservation_id' => $reservation->getId(),
                        'error_class' => get_class($e),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]);
                    $email_error = true;

                    // Clean up the temporary PDF file even if email fails
                    if (file_exists($pdfPath)) {
                        unlink($pdfPath);
                        $this->logger->info('Temporary PDF file cleaned up after error');
                    }
                }

                // Show confirmation page
                return $this->render('paiement_vol/success.html.twig', [
                    'vol' => $vol,
                    'reservation' => $reservation,
                    'payment' => $payment
                ]);

            } catch (\Exception $e) {
                $this->logger->error('PDF generation or email sending failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Even if PDF/email fails, show success page since the reservation was saved
                return $this->render('paiement_vol/success.html.twig', [
                    'vol' => $vol,
                    'reservation' => $reservation,
                    'payment' => $payment,
                    'email_error' => $email_error
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Reservation success process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->render('paiement_vol/error.html.twig', [
                'error_message' => $e->getMessage(),
                'vol_id' => $request->query->get('volId')
            ]);
        }
    }

    #[Route('/payment-vol-error', name: 'app_payment_vol_error')]
    public function error(Request $request, EntityManagerInterface $entityManager): Response
    {
        $errorMessage = $request->query->get('error', 'Une erreur est survenue lors du traitement de votre paiement.');
        $volId = $request->query->get('vol_id');
        
        // Get the flight price from the Vol entity
        $vol = $entityManager->getRepository(Vol::class)->find($volId);
        $flightPrice = $vol ? $vol->getPrix() : null;

        return $this->render('paiement_vol/error.html.twig', [
            'error_message' => $errorMessage,
            'vol_id' => $volId,
            'flightPrice' => $flightPrice
        ]);
    }
} 