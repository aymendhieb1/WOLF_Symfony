<?php

namespace App\Service;

use Twilio\Rest\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwilioService
{
    private $client;
    private $fromNumber;
    private $logger;
    private $accountSid;
    private $authToken;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->accountSid = $params->get('twilio_account_sid');
        $this->authToken = $params->get('twilio_auth_token');
        $this->fromNumber = $params->get('twilio_from_number');

        $this->logger->info('Initializing TwilioService', [
            'accountSid' => $this->accountSid,
            'fromNumber' => $this->fromNumber
        ]);

        try {
            $this->client = new Client($this->accountSid, $this->authToken);
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Twilio client', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendSMS(string $to, string $message): void
    {
        try {
            $this->logger->info('Attempting to send SMS', [
                'to' => $to,
                'from' => $this->fromNumber,
                'message' => $message,
                'accountSid' => $this->accountSid
            ]);

            // Validate phone numbers
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $to)) {
                throw new \InvalidArgumentException('Invalid recipient phone number format. Must be in E.164 format.');
            }

            if (!preg_match('/^\+[1-9]\d{1,14}$/', $this->fromNumber)) {
                throw new \InvalidArgumentException('Invalid sender phone number format. Must be in E.164 format.');
            }

            $result = $this->client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            $this->logger->info('SMS sent successfully', [
                'sid' => $result->sid,
                'status' => $result->status
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send SMS', [
                'error' => $e->getMessage(),
                'to' => $to,
                'from' => $this->fromNumber,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function testConnection(): bool
    {
        try {
            // Try to fetch account info as a test
            $account = $this->client->api->v2010->accounts($this->accountSid)->fetch();
            $this->logger->info('Twilio connection test successful', [
                'accountStatus' => $account->status
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Twilio connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }







}



