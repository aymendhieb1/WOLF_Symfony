<?php

namespace App\Service;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GmailApiService
{
    private const TOKEN_PATH = __DIR__ . '/../../config/gmail_token.json';
    private const REDIRECT_URI = 'http://localhost:8000/oauth2/callback';
    private $logger;
    private $router;
    private $client;

    public function __construct(LoggerInterface $logger, UrlGeneratorInterface $router)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        try {
            $this->logger->info('Initializing Google Client');
            
            $this->client = new GoogleClient([
                'timeout' => 10,
                'connect_timeout' => 3,
                'retry' => [
                    'max_retries' => 1
                ]
            ]);
            
            $this->client->setApplicationName('TripToGo');
            $this->client->setAuthConfig(__DIR__ . '/../../config/credentials.json');
            $this->client->setRedirectUri(self::REDIRECT_URI);
            $this->client->addScope('https://www.googleapis.com/auth/gmail.send');
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');

            // Load previously authorized token, if it exists
            if (file_exists(self::TOKEN_PATH)) {
                $this->logger->info('Loading existing token');
                $accessToken = json_decode(file_get_contents(self::TOKEN_PATH), true);
                if ($accessToken === null) {
                    $this->logger->error('Failed to parse token file');
                    throw new \RuntimeException('Invalid token file format');
                }
                $this->client->setAccessToken($accessToken);
                $this->logger->info('Token loaded successfully');
            } else {
                $this->logger->info('No existing token found');
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to initialize Gmail client: ' . $e->getMessage());
        }
    }

    public function isAuthorized(): bool
    {
        try {
            $isExpired = $this->client->isAccessTokenExpired();
            $this->logger->info('Checking authorization status', ['is_expired' => $isExpired]);
            return !$isExpired;
        } catch (\Exception $e) {
            $this->logger->error('Error checking authorization status', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function sendEmail(string $to, string $subject, string $messageBody): void
    {
        $this->logger->info('Starting email send process');
        
        if (!$this->isAuthorized()) {
            $this->logger->info('Not authorized, attempting to refresh token');
            $this->refreshTokenIfNeeded();
            if (!$this->isAuthorized()) {
                $this->logger->error('Still not authorized after refresh attempt');
                throw new \RuntimeException('Gmail API not authorized. Please authorize first.');
            }
        }

        try {
            $this->logger->info('Creating Gmail service');
            $service = new Gmail($this->client);
            
            $this->logger->info('Preparing email message');
            // Create the email message with From header
            $rawMessage = "From: TripToGo <me>\r\n";
            $rawMessage .= "To: {$to}\r\n";
            $rawMessage .= "Subject: {$subject}\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
            $rawMessage .= $messageBody;

            // Encode the email in base64url format
            $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
            
            $message = new Message();
            $message->setRaw($encodedMessage);

            $this->logger->info('Sending email', [
                'to' => $to,
                'subject' => $subject
            ]);

            // Set a timeout for the email sending operation
            set_time_limit(30);
            
            $service->users_messages->send('me', $message);
            
            $this->logger->info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject
            ]);
        } catch (\Google\Service\Exception $e) {
            $this->logger->error('Gmail API error', [
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'to' => $to,
                'subject' => $subject
            ]);
            throw new \RuntimeException('Gmail API error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'to' => $to,
                'subject' => $subject
            ]);
            throw new \RuntimeException('Failed to send email: ' . $e->getMessage());
        }
    }

    public function refreshTokenIfNeeded(): void
    {
        if ($this->client->isAccessTokenExpired()) {
            try {
                $this->logger->info('Token is expired, checking for refresh token');
                if ($this->client->getRefreshToken()) {
                    $this->logger->info('Refreshing access token');
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    
                    // Verify the new token
                    $newToken = $this->client->getAccessToken();
                    if (!isset($newToken['access_token'])) {
                        throw new \RuntimeException('Failed to obtain new access token');
                    }
                    
                    file_put_contents(self::TOKEN_PATH, json_encode($newToken));
                    $this->logger->info('Access token refreshed successfully');
                } else {
                    $this->logger->error('No refresh token available');
                    throw new \RuntimeException('No refresh token available');
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to refresh token', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \RuntimeException('Failed to refresh token: ' . $e->getMessage());
            }
        }
    }
} 