<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Entity\Vehicule;
use App\Form\ContratType;
use App\Repository\ContratRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TCPDF;
use App\Service\TwilioService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\GmailApiService;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\SendEmailMessage;
use App\Service\GoogleCalendarService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Symfony\Component\Validator\Validator\Validator;


#[Route('/back/contrat')]
class BackContratController extends AbstractController
{
    private $slugger;
    private $uploadsDirectory;
    private $validator;
    private $logger;
    private $twilioService;
    private $entityManager;
    private $mailer;
    private $params;
    private $gmailService;
    private $messageBus;
    private $googleCalendarService;
    private $session;

    public function __construct(
        SluggerInterface $slugger,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        string $uploadsDirectory = 'Uploads/permits',
        TwilioService $twilioService,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        ParameterBagInterface $params,
        GmailApiService $gmailService,
        MessageBusInterface $messageBus,
        GoogleCalendarService $googleCalendarService,
        SessionInterface $session
    ) {
        $this->slugger = $slugger;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->twilioService = $twilioService;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->params = $params;
        $this->gmailService = $gmailService;
        $this->messageBus = $messageBus;
        $this->googleCalendarService = $googleCalendarService;
        $this->session = $session;
    }

    #[Route('/', name: 'app_back_contrat', methods: ['GET'])]
    public function index(ContratRepository $contratRepository, VehiculeRepository $vehiculeRepository): Response
    {
        $addForm = $this->createForm(ContratType::class, new Contrat());
        $editForm = $this->createForm(ContratType::class, new Contrat(), [
            'action' => $this->generateUrl('app_back_contrat_edit', ['id_location' => 0])
        ]);

        return $this->render('back_contrat/TableContrat.html.twig', [
            'contrats' => $contratRepository->findAll(),
            'vehicules' => $vehiculeRepository->findAll(),
            'form' => $addForm->createView(),
            'edit_form' => $editForm->createView(),
        ]);
    }

    #[Route('/new', name: 'app_back_contrat_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        try {
            $logger->info('Starting contract creation process');
            $logger->info('Received data: ' . json_encode($request->request->all()));

            // Validate required fields
            $dateD = $request->request->get('contrat')['dateD'] ?? null;
            $dateF = $request->request->get('contrat')['dateF'] ?? null;
            $cinLocateur = $request->request->get('contrat')['cinLocateur'] ?? null;
            $idVehicule = $request->request->get('contrat')['id_vehicule'] ?? null;

            if (!$dateD || !$dateF || !$cinLocateur || !$idVehicule) {
                throw new \InvalidArgumentException('Missing required fields');
            }

            // Check for existing contract with same vehicle and overlapping dates
            $existingContract = $entityManager->getRepository(Contrat::class)->createQueryBuilder('c')
                ->where('c.id_vehicule = :vehicule')
                ->andWhere('(c.dateD BETWEEN :startDate AND :endDate OR c.dateF BETWEEN :startDate AND :endDate)')
                ->setParameters([
                    'vehicule' => $idVehicule,
                    'startDate' => new \DateTime($dateD),
                    'endDate' => new \DateTime($dateF)
                ])
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingContract) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'A contract already exists for this vehicle during the specified dates'
                ], 400);
            }

            // Create new contract
            $contrat = new Contrat();
            $contrat->setDateD(new \DateTime($dateD));
            $contrat->setDateF(new \DateTime($dateF));
            $contrat->setCinLocateur($cinLocateur);
            $contrat->setIdVehicule($idVehicule);

            // Handle photo upload
            if ($request->files->has('contrat') && isset($request->files->get('contrat')['photo_permit'])) {
                $photoFile = $request->files->get('contrat')['photo_permit'];

                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $originalFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    try {
                        $photoFile->move(
                            $this->getParameter('photos_directory'),
                            $newFilename
                        );
                        $contrat->setPhotoPermit($newFilename);
                    } catch (FileException $e) {
                        $logger->error('Error uploading file: ' . $e->getMessage());
                        throw new \Exception('Error uploading file');
                    }
                }
            }

            // Validate and save
            $validator = Validator::createValidator();
            $errors = $validator->validate($contrat);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                throw new \InvalidArgumentException(implode(', ', $errorMessages));
            }

            $entityManager->persist($contrat);
            $entityManager->flush();

            $logger->info('Contract created successfully with ID: ' . $contrat->getId());

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Contract created successfully'
            ]);

        } catch (\Exception $e) {
            $logger->error('Error creating contract: ' . $e->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id_location}/edit', name: 'app_back_contrat_edit', methods: ['POST'])]
    public function edit(Request $request, int $id_location): JsonResponse
    {
        try {
            $contrat = $this->getDoctrine()->getRepository(Contrat::class)->find($id_location);
            if (!$contrat) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Contract not found.'
                ], 404);
            }

            // Get data from request
            $dateD = $request->request->get('dateD');
            $dateF = $request->request->get('dateF');
            $cinLocateur = $request->request->get('cinLocateur');
            $idVehicule = $request->request->get('id_vehicule');

            // Validate required fields
            if (!$dateD || !$dateF || !$cinLocateur || !$idVehicule) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'All fields are required.'
                ], 400);
            }

            // Update contract data
            $contrat->setDateD($dateD);
            $contrat->setDateF($dateF);
            $contrat->setCinLocateur($cinLocateur);

            // Update vehicle
            $vehicule = $this->getDoctrine()->getRepository(Vehicule::class)->find($idVehicule);
            if (!$vehicule) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Vehicle not found.'
                ], 404);
            }
            $contrat->setIdVehicule($vehicule);

            // Handle photo upload
            $photoFile = $request->files->get('photo_permit');
            if ($photoFile) {
                // Delete old photo if exists
                if ($contrat->getPhotoPermit()) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public/Uploads/permits/' . $contrat->getPhotoPermit();
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Generate unique filename
                $newFilename = uniqid() . '.' . $photoFile->guessExtension();

                try {
                    // Ensure upload directory exists
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/Uploads/permits';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    // Move the file
                    $photoFile->move($uploadDir, $newFilename);
                    $contrat->setPhotoPermit($newFilename);
                } catch (FileException $e) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Failed to upload photo: ' . $e->getMessage()
                    ], 500);
                }
            }

            // Save changes
            $this->getDoctrine()->getManager()->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Contract updated successfully.',
                'data' => [
                    'id_location' => $contrat->getIdLocation(),
                    'dateD' => $contrat->getDateD(),
                    'dateF' => $contrat->getDateF(),
                    'cinLocateur' => $contrat->getCinLocateur(),
                    'photo_permit' => $contrat->getPhotoPermit(),
                    'photo_url' => $contrat->getPhotoPermit() ? '/Uploads/permits/' . $contrat->getPhotoPermit() : null,
                    'idVehicule' => [
                        'id_vehicule' => $contrat->getIdVehicule()->getId_vehicule(),
                        'matricule' => $contrat->getIdVehicule()->getMatricule()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/delete/{id_location}', name: 'app_back_contrat_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, int $id_location): JsonResponse
    {
        $connection = $entityManager->getConnection();

        try {
            $this->logger->info("Attempting to delete contrat with id_location: {$id_location}");

            // Start transaction
            $connection->beginTransaction();

            // Find the contract
            $contrat = $entityManager->getRepository(Contrat::class)->find($id_location);

            if (!$contrat) {
                throw new \Exception('Contrat non trouvé.');
            }

            // Delete photo if exists
            if ($contrat->getPhotoPermit()) {
                $photoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/permits/' . $contrat->getPhotoPermit();
                if (file_exists($photoPath)) {
                    if (!unlink($photoPath)) {
                        throw new \Exception('Erreur lors de la suppression du fichier photo.');
                    }
                }
            }

            // Delete using DQL and check affected rows
            $query = $entityManager->createQuery('DELETE FROM App\Entity\Contrat c WHERE c.id_location = :id');
            $query->setParameter('id', $id_location);
            $sql = $query->getSQL();
            $this->logger->info("Executing DQL: {$sql} with id: {$id_location}");
            $affectedRows = $query->execute();

            // Verify that exactly one row was deleted
            if ($affectedRows !== 1) {
                throw new \Exception('Aucun contrat n\'a été supprimé. Vérifiez les contraintes de la base de données.');
            }

            // Commit transaction
            $connection->commit();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Contrat supprimé avec succès.',
                'id' => $id_location
            ]);

        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            // Handle specific database constraint errors
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->logger->error("Constraint violation during deletion of contrat {$id_location}: {$e->getMessage()}");
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Impossible de supprimer le contrat en raison de contraintes de la base de données.'
            ], 400);
        } catch (\Exception $e) {
            // Rollback transaction
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->logger->error("Error during deletion of contrat {$id_location}: {$e->getMessage()}");
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/generate-pdf', name: 'app_back_contrat_generate_pdf', methods: ['POST'])]
    public function generatePdf(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['id_location'])) {
                throw new \Exception('Contract ID is required');
            }

            $contrat = $entityManager->getRepository(Contrat::class)->find($data['id_location']);
            if (!$contrat) {
                throw new \Exception('Contrat not found');
            }

            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('TripToGo');
            $pdf->SetTitle('Contrat de Location');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(20, 20, 20);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 12);

            // Format dates properly
            $dateD = $contrat->getDateD();
            $dateF = $contrat->getDateF();

            $startDate = $dateD instanceof \DateTime ? $dateD->format('Y-m-d') : $dateD;
            $endDate = $dateF instanceof \DateTime ? $dateF->format('Y-m-d') : $dateF;

            // Add content
            $html = '
            <h1 style="text-align: center;">Contrat de Location</h1>
            <br><br>
            <table cellpadding="5">
                <tr>
                    <td><strong>Date de début:</strong></td>
                    <td>' . $startDate . '</td>
                </tr>
                <tr>
                    <td><strong>Date de fin:</strong></td>
                    <td>' . $endDate . '</td>
                </tr>
                <tr>
                    <td><strong>CIN Locataire:</strong></td>
                    <td>' . $contrat->getCinLocateur() . '</td>
                </tr>
                <tr>
                    <td><strong>Matricule du véhicule:</strong></td>
                    <td>' . $contrat->getIdVehicule()->getMatricule() . '</td>
                </tr>
            </table>
            ';

            // Print text using writeHTMLCell()
            $pdf->writeHTML($html, true, false, true, false, '');

            // Close and output PDF document
            $pdfContent = $pdf->Output('', 'S');

            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="contrat_' . $contrat->getIdLocation() . '.pdf"');

            return $response;

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/convert-to-qr', name: 'app_back_contrat_convert_to_qr', methods: ['POST'])]
    public function convertToQr(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $this->logger->info('Received QR code request', ['data' => $data]);

            if (!isset($data['contratId'])) {
                throw new \Exception('Contract ID is required');
            }

            // Find the contract
            $contrat = $entityManager->getRepository(Contrat::class)->find($data['contratId']);
            if (!$contrat) {
                throw new \Exception('Contract not found');
            }

            // Create directories if they don't exist
            $projectDir = $this->getParameter('kernel.project_dir');
            $pdfDir = $projectDir . '/public/uploads/pdfs';
            $qrCodeDir = $projectDir . '/public/uploads/qrcodes';

            foreach ([$pdfDir, $qrCodeDir] as $dir) {
                if (!file_exists($dir)) {
                    if (!mkdir($dir, 0777, true)) {
                        throw new \Exception("Failed to create directory: $dir");
                    }
                }
                // Ensure directory is writable
                if (!is_writable($dir)) {
                    chmod($dir, 0777);
                }
            }

            // Generate unique filenames
            $pdfFilename = sprintf('contrat_%d_%s.pdf', $contrat->getIdLocation(), uniqid());
            $qrFilename = sprintf('contrat_%d_%s.png', $contrat->getIdLocation(), uniqid());

            // Create PDF content
            $html = $this->renderView('back_contrat/pdf_template.html.twig', [
                'contrat' => $contrat,
                'dateD' => $contrat->getDateD() instanceof \DateTime ? $contrat->getDateD()->format('Y-m-d') : $contrat->getDateD(),
                'dateF' => $contrat->getDateF() instanceof \DateTime ? $contrat->getDateF()->format('Y-m-d') : $contrat->getDateF(),
                'cinLocateur' => $contrat->getCinLocateur(),
                'matricule' => $contrat->getIdVehicule()->getMatricule()
            ]);

            // Generate PDF
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('TripToGo');
            $pdf->SetTitle('Contrat de Location');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(20, 20, 20);
            $pdf->AddPage();
            $pdf->writeHTML($html);

            // Save PDF
            $pdfPath = $pdfDir . '/' . $pdfFilename;
            $pdf->Output($pdfPath, 'F');

            // Get the absolute URL for the PDF
            $baseUrl = $request->getSchemeAndHttpHost();
            $pdfUrl = $baseUrl . '/uploads/pdfs/' . $pdfFilename;

            // Generate QR Code
            $qrCode = QrCode::create($pdfUrl)
                ->setSize(300)
                ->setMargin(10)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // Save QR Code
            $qrPath = $qrCodeDir . '/' . $qrFilename;
            file_put_contents($qrPath, $result->getString());

            $this->logger->info('QR Code generated successfully', [
                'contractId' => $contrat->getIdLocation(),
                'qrPath' => $qrPath,
                'pdfPath' => $pdfPath,
                'pdfUrl' => $pdfUrl
            ]);

            return new JsonResponse([
                'success' => true,
                'qrCodeUrl' => '/uploads/qrcodes/' . $qrFilename,
                'pdfUrl' => '/uploads/pdfs/' . $pdfFilename
            ]);

        } catch (\Exception $e) {
            $this->logger->error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Error generating QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/pdf-to-qr', name: 'app_back_contrat_pdf_to_qr', methods: ['POST'])]
    public function pdfToQr(Request $request): Response
    {
        try {
            $pdfFile = $request->files->get('pdfFile');

            if (!$pdfFile) {
                throw new \Exception('No PDF file uploaded');
            }

            // Create directories if they don't exist
            $pdfDir = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs';
            $qrCodeDir = $this->getParameter('kernel.project_dir') . '/public/uploads/qrcodes';

            if (!file_exists($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }
            if (!file_exists($qrCodeDir)) {
                mkdir($qrCodeDir, 0777, true);
            }

            // Save the uploaded PDF
            $pdfFilename = 'uploaded_' . uniqid() . '.pdf';
            $pdfPath = $pdfDir . '/' . $pdfFilename;
            $pdfFile->move($pdfDir, $pdfFilename);

            // Extract text from PDF
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();

            // Generate unique filename for QR code
            $qrFilename = 'qr_' . uniqid() . '.png';
            $qrPath = $qrCodeDir . '/' . $qrFilename;

            // Create QR code with the PDF text
            $qrCode = \Endroid\QrCode\QrCode::create($text)
                ->setSize(300)
                ->setMargin(10)
                ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh());

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);

            // Save QR code to file
            $result->saveToFile($qrPath);

            return new JsonResponse([
                'success' => true,
                'qrCodeUrl' => '/uploads/qrcodes/' . $qrFilename,
                'pdfUrl' => '/uploads/pdfs/' . $pdfFilename
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/send-sms', name: 'app_back_contrat_send_sms', methods: ['POST'])]
    public function sendSms(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['contratId'], $data['phoneNumber'], $data['matricule'])) {
                throw new \Exception('Missing required parameters');
            }

            $contrat = $entityManager->getRepository(Contrat::class)->find($data['contratId']);
            if (!$contrat) {
                throw new \Exception('Contract not found');
            }

            // Validate phone number format
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $data['phoneNumber'])) {
                throw new \Exception('Invalid phone number format. Must be in E.164 format (e.g., +21694006772)');
            }

            // Test Twilio connection
            if (!$this->twilioService->testConnection()) {
                throw new \Exception('Failed to connect to Twilio service');
            }

            $message = sprintf(
                "Contract details - Vehicle: %s, Contract ID: %d",
                $data['matricule'],
                $data['contratId']
            );

            $this->logger->info('Sending SMS to custom number', [
                'to' => $data['phoneNumber'],
                'contractId' => $data['contratId']
            ]);

            $this->twilioService->sendSMS($data['phoneNumber'], $message);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'SMS sent successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error sending SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/send-mail', name: 'app_back_contrat_send_mail', methods: ['POST'])]
    public function sendMail(Request $request, MailerInterface $mailer): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $this->logger->info('Received email request', [
                'data' => $data
            ]);

            if (!isset($data['emailAddress'])) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Email address is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!filter_var($data['emailAddress'], FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid email address format'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Create the email
            $email = (new Email())
                ->from('triptogo2025@gmail.com')
                ->to($data['emailAddress'])
                ->subject('Contract Creation Confirmation - TripToGo')
                ->html('
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            h1 { color: #2c3e50; }
                            .container { padding: 20px; }
                            .footer { margin-top: 20px; color: #7f8c8d; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h1>Contract Creation Confirmation</h1>
                            <p>Dear Customer,</p>
                            <p>Your contract has been successfully created in the TripToGo system.</p>
                            <p>You can access your contract details through our platform at any time.</p>
                            <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>
                            <div class="footer">
                                <p>Best regards,<br>TripToGo Team</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ');

            // Send the email
            $mailer->send($email);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Email sent to ' . $data['emailAddress']
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error sending email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'An error occurred while sending the email'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/google-calendar/auth', name: 'app_back_contrat_google_calendar_auth')]
    public function googleCalendarAuth()
    {
        $authUrl = $this->googleCalendarService->getAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/google-calendar/callback', name: 'app_back_contrat_google_calendar_callback')]
    public function googleCalendarCallback(Request $request)
    {
        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Authorization code not found');
            return $this->redirectToRoute('app_back_contrat');
        }

        try {
            $token = $this->googleCalendarService->getAccessToken($code);
            $this->session->set('google_calendar_token', $token);
            $this->addFlash('success', 'Successfully connected to Google Calendar');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to authenticate with Google Calendar: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_back_contrat');
    }

    #[Route('/{id}/add-to-calendar', name: 'app_back_contrat_add_to_calendar', methods: ['POST'])]
    public function addToCalendar(Request $request, $id)
    {
        try {
            $contrat = $this->getDoctrine()->getRepository(Contrat::class)->find($id);
            if (!$contrat) {
                return $this->json(['success' => false, 'message' => 'Contract not found']);
            }

            // Check if we have a valid access token
            $accessToken = $this->session->get('google_calendar_token');
            if (!$accessToken) {
                return $this->json([
                    'success' => false,
                    'message' => 'Please authenticate with Google Calendar first'
                ]);
            }

            // Set the access token
            $this->googleCalendarService->setAccessToken($accessToken);

            // Generate a unique color ID for this contract (1-11 are valid Google Calendar color IDs)
            $colorId = (($id % 11) + 1);

            // Convert string dates to DateTime objects if they aren't already
            $startDate = $contrat->getDateD();
            $endDate = $contrat->getDateF();
            if (!$startDate instanceof \DateTime) {
                $startDate = new \DateTime($startDate);
            }
            if (!$endDate instanceof \DateTime) {
                $endDate = new \DateTime($endDate);
            }

            // Create the event with color
            $event = $this->googleCalendarService->createEvent(
                'Contract: ' . $contrat->getIdVehicule()->getMatricule(),
                $startDate,
                $endDate,
                sprintf(
                    "Contract Details:\nVehicle: %s\nTenant CIN: %s\nStart Date: %s\nEnd Date: %s",
                    $contrat->getIdVehicule()->getMatricule(),
                    $contrat->getCinLocateur(),
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ),
                $colorId
            );

            return $this->json([
                'success' => true,
                'message' => 'Contract added to Google Calendar successfully',
                'eventId' => $event->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error adding contract to calendar: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Error adding contract to calendar: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/sync-all-to-calendar', name: 'app_back_contrat_sync_all_to_calendar', methods: ['POST'])]
    public function syncAllToCalendar(Request $request)
    {
        try {
            // Check if we have a valid access token
            $accessToken = $this->session->get('google_calendar_token');
            if (!$accessToken) {
                return $this->json([
                    'success' => false,
                    'message' => 'Please authenticate with Google Calendar first'
                ]);
            }

            // Set the access token
            $this->googleCalendarService->setAccessToken($accessToken);

            // Get all contracts
            $contracts = $this->getDoctrine()->getRepository(Contrat::class)->findAll();

            $successCount = 0;
            $failedCount = 0;
            $results = [];

            foreach ($contracts as $contrat) {
                try {
                    // Convert string dates to DateTime objects if they aren't already
                    $startDate = $contrat->getDateD();
                    $endDate = $contrat->getDateF();
                    if (!$startDate instanceof \DateTime) {
                        $startDate = new \DateTime($startDate);
                    }
                    if (!$endDate instanceof \DateTime) {
                        $endDate = new \DateTime($endDate);
                    }

                    // Generate a unique color ID for this contract (1-11 are valid Google Calendar color IDs)
                    $colorId = (($contrat->getIdLocation() % 11) + 1);

                    // Create the event
                    $event = $this->googleCalendarService->createEvent(
                        'Location de Voiture: ' . $contrat->getIdVehicule()->getMatricule(),
                        $startDate,
                        $endDate,
                        sprintf(
                            "Détails du Contrat:\nVéhicule: %s\nCIN Locataire: %s\nDébut: %s\nFin: %s",
                            $contrat->getIdVehicule()->getMatricule(),
                            $contrat->getCinLocateur(),
                            $startDate->format('Y-m-d'),
                            $endDate->format('Y-m-d')
                        ),
                        $colorId
                    );

                    $successCount++;
                    $results[] = [
                        'id' => $contrat->getIdLocation(),
                        'status' => 'success',
                        'eventId' => $event->getId()
                    ];
                } catch (\Exception $e) {
                    $failedCount++;
                    $results[] = [
                        'id' => $contrat->getIdLocation(),
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    $this->logger->error('Error syncing contract ' . $contrat->getIdLocation() . ': ' . $e->getMessage());
                }
            }

            return $this->json([
                'success' => true,
                'message' => sprintf('Synced %d contracts successfully, %d failed', $successCount, $failedCount),
                'results' => $results,
                'totalSuccess' => $successCount,
                'totalFailed' => $failedCount
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error in bulk sync: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Error syncing contracts: ' . $e->getMessage()
            ]);
        }
    }
}