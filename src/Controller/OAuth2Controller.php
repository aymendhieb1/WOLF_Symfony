<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Google\Client as GoogleClient;
use Psr\Log\LoggerInterface;
use App\Service\GmailApiService;

#[Route('/oauth2')]
class OAuth2Controller extends AbstractController
{
    private $logger;
    private $gmailService;
    private const TOKEN_PATH = __DIR__ . '/../../config/gmail_token.json';
    private const REDIRECT_URI = 'http://localhost:8000/oauth2/callback';
    private const SUCCESS_REDIRECT = '/back/contrat';

    public function __construct(LoggerInterface $logger, GmailApiService $gmailService)
    {
        $this->logger = $logger;
        $this->gmailService = $gmailService;
    }

    #[Route('/authorize', name: 'oauth2_authorize', methods: ['GET'])]
    public function authorize(): Response
    {
        try {
            $client = new GoogleClient();
            $client->setAuthConfig(__DIR__ . '/../../config/credentials.json');
            $client->setRedirectUri(self::REDIRECT_URI);
            $client->addScope('https://www.googleapis.com/auth/gmail.send');
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            $authUrl = $client->createAuthUrl();
            return $this->redirect($authUrl);
        } catch (\Exception $e) {
            $this->logger->error('Authorization initialization failed: ' . $e->getMessage());
            $this->addFlash('error', 'Failed to initialize authorization: ' . $e->getMessage());
            return $this->redirect(self::SUCCESS_REDIRECT);
        }
    }

    #[Route('/callback', name: 'oauth2_callback', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        
        if (!$code) {
            $this->addFlash('error', 'No authorization code received from Google');
            return $this->redirect(self::SUCCESS_REDIRECT);
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig(__DIR__ . '/../../config/credentials.json');
            $client->setRedirectUri(self::REDIRECT_URI);
            $client->addScope('https://www.googleapis.com/auth/gmail.send');

            // Exchange authorization code for access token
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                $this->logger->error('Error fetching access token: ' . $token['error']);
                $this->addFlash('error', 'Failed to get access token: ' . $token['error']);
                return $this->redirect(self::SUCCESS_REDIRECT);
            }

            // Store the token
            if (!file_exists(dirname(self::TOKEN_PATH))) {
                mkdir(dirname(self::TOKEN_PATH), 0700, true);
            }
            file_put_contents(self::TOKEN_PATH, json_encode($token));

            $this->addFlash('success', 'Gmail authorization successful!');
        } catch (\Exception $e) {
            $this->logger->error('OAuth callback error: ' . $e->getMessage());
            $this->addFlash('error', 'Authorization failed: ' . $e->getMessage());
        }

        return $this->redirect(self::SUCCESS_REDIRECT);
    }
} 