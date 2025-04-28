<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TwoFactorController extends AbstractController
{
    private const SESSION_2FA_SECRET = '2fa_secret';
    private const SESSION_2FA_TEMP = '2fa_temp_secret';
    private const SESSION_2FA_ENABLED = '2fa_enabled';
    private const SESSION_2FA_AUTHENTICATED = '2fa_authenticated';

    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    public function setup(SessionInterface $session): Response
    {
        // Generate a random secret
        $secret = random_bytes(32);
        $secretBase32 = Base32::encodeUpper($secret);
        
        // Create TOTP object
        $totp = TOTP::create($secretBase32);
        $totp->setLabel($this->getUser()->getUserIdentifier());
        $totp->setIssuer('TripToGo');
        
        // Store secret in session temporarily
        $session->set(self::SESSION_2FA_TEMP, $secretBase32);
        
        // Generate QR code provisioning URI
        $qrCodeUri = $totp->getProvisioningUri();
        
        return $this->render('two_factor/setup.html.twig', [
            'qr_code' => $qrCodeUri,
            'secret' => $secretBase32
        ]);
    }

    #[Route('/2fa/verify', name: 'app_2fa_verify', methods: ['POST'])]
    public function verify(Request $request, SessionInterface $session): Response
    {
        $code = $request->request->get('code');
        $secret = $session->get(self::SESSION_2FA_TEMP);
        
        if (!$secret) {
            return $this->json(['error' => 'No 2FA setup in progress'], 400);
        }
        
        $totp = TOTP::create($secret);
        
        if ($totp->verify($code)) {
            // Store 2FA secret in session
            $session->set(self::SESSION_2FA_SECRET, $secret);
            $session->set(self::SESSION_2FA_ENABLED, true);
            $session->remove(self::SESSION_2FA_TEMP);
            
            return $this->json(['success' => true]);
        }
        
        return $this->json(['error' => 'Invalid code'], 400);
    }

    #[Route('/2fa/validate', name: 'app_2fa_validate')]
    public function validateForm(): Response
    {
        return $this->render('two_factor/validate.html.twig');
    }

    #[Route('/2fa/check', name: 'app_2fa_check', methods: ['POST'])]
    public function validate(Request $request, SessionInterface $session): Response
    {
        $code = $request->request->get('code');
        $secret = $session->get(self::SESSION_2FA_SECRET);
        
        if (!$secret || !$session->get(self::SESSION_2FA_ENABLED)) {
            return $this->json(['error' => '2FA is not enabled'], 400);
        }
        
        $totp = TOTP::create($secret);
        
        if ($totp->verify($code)) {
            $session->set(self::SESSION_2FA_AUTHENTICATED, true);
            return $this->json(['success' => true]);
        }
        
        return $this->json(['error' => 'Invalid code'], 400);
    }

    #[Route('/2fa/status', name: 'app_2fa_status')]
    public function status(SessionInterface $session): Response
    {
        return $this->json([
            'enabled' => $session->get(self::SESSION_2FA_ENABLED, false),
            'authenticated' => $session->get(self::SESSION_2FA_AUTHENTICATED, false)
        ]);
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable')]
    public function disable(SessionInterface $session): Response
    {
        $session->remove(self::SESSION_2FA_SECRET);
        $session->remove(self::SESSION_2FA_ENABLED);
        $session->remove(self::SESSION_2FA_AUTHENTICATED);
        
        return $this->json(['success' => true]);
    }
} 