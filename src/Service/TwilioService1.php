<?php

namespace App\Service;

use Twilio\Rest\Client;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwilioService1
{
    private $twilioClient;
    private $twilioPhoneNumber;
    private $adminPhoneNumber;
    private $session;

    public function __construct(
        string $accountSid,
        string $authToken,
        string $twilioPhoneNumber,
        string $adminPhoneNumber,
        SessionInterface $session
    ) {
        $this->twilioClient = new Client($accountSid, $authToken);
        $this->twilioPhoneNumber = $twilioPhoneNumber;
        $this->adminPhoneNumber = $adminPhoneNumber;
        $this->session = $session;
    }

    public function sendVerificationCode(string $phoneNumber): string
    {
        // Generate a random 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store the code in the session
        $this->session->set('verification_code', $code);
        $this->session->set('verification_phone', $phoneNumber);

        try {
            // Log the phone number and Twilio phone number for debugging
            error_log('Sending SMS to: ' . $phoneNumber . ' from: ' . $this->twilioPhoneNumber);
            // Send the SMS using Twilio
            $message = $this->twilioClient->messages->create(
                $phoneNumber,
                [
                    'from' => $this->twilioPhoneNumber,
                    'body' => "Votre code de vérification TripToGo est: $code"
                ]
            );

            return $code;
        } catch (\Exception $e) {
            // Log the error details
            error_log('Twilio SMS Error: ' . $e->getMessage());
            throw new \Exception('Failed to send SMS: ' . $e->getMessage());
        }
    }

    public function verifyCode(string $code): bool
    {
        $storedCode = $this->session->get('verification_code');
        return $code === $storedCode;
    }
}