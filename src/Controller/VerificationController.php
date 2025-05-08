<?php

namespace App\Controller;

use App\Service\TwilioService1;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class VerificationController extends AbstractController
{
    private $twilioService;
    private $security;
    private $entityManager;

    public function __construct(
        TwilioService1 $twilioService,
        Security $security,
        EntityManagerInterface $entityManager
    ) {
        $this->twilioService = $twilioService;
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    #[Route('/verification/send', name: 'send_verification', methods: ['POST'])]
    public function sendVerification(Request $request): Response
    {
        $phoneNumber = $request->request->get('phone_number');
        $user = $this->security->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Get the last two digits of the user's phone number for the hint
        $userPhone = $user->getNumTel();
        $lastTwoDigits = substr($userPhone, -2);

        // Ensure phone number is properly formatted
        $phoneNumber = trim($phoneNumber);
        $userPhone = trim($userPhone);

        // Check if the phone number matches the user's number
        if ($phoneNumber !== $userPhone) {
            error_log('Phone number mismatch: ' . $phoneNumber . ' vs ' . $userPhone);
            return $this->json([
                'success' => false,
                'message' => 'Numéro incorrect. Indice: Le numéro se termine par ' . $lastTwoDigits
            ], 400);
        }

        try {
            // Note: SMS is disabled to save costs. The code will be returned in the response for testing.
            $code = $this->twilioService->sendVerificationCode($this->getParameter(56252246));
            return $this->json([
                'success' => true,
                'message' => 'Code de vérification généré avec succès',
                'code' => $code // Only for testing while SMS is disabled
            ]);
        } catch (\Exception $e) {
            error_log('Verification SMS Error: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du code de vérification'
            ], 500);
        }
    }

    #[Route('/verification/verify', name: 'verify_code', methods: ['POST'])]
    public function verifyCode(Request $request): Response
    {
        $code = $request->request->get('code');

        if ($this->twilioService->verifyCode($code)) {
            return $this->json([
                'success' => true,
                'message' => 'Code vérifié avec succès'
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Code de vérification invalide'
        ], 400);
    }

    #[Route('/verification/get-hint', name: 'get_phone_hint', methods: ['GET'])]
    public function getPhoneHint(): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $lastTwoDigits = substr($user->getNumTel(), -2);

        return $this->json([
            'success' => true,
            'lastTwoDigits' => $lastTwoDigits
        ]);
    }
}