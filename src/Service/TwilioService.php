<?php

namespace App\Service;

use Twilio\Rest\Client;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwilioService
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
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store the code in session
        $this->session->set('verification_code', $verificationCode);
        
        // In production, we would send to $phoneNumber
        // For testing, we only send to admin number
        $message = $this->twilioClient->messages->create(
            $this->adminPhoneNumber, // Always send to admin number
            [
                'from' => $this->twilioPhoneNumber,
                'body' => "Your TripToGo verification code is: $verificationCode"
            ]
        );

        return $verificationCode;
    }

    public function verifyCode(string $code): bool
    {
        $storedCode = $this->session->get('verification_code');
        return $storedCode === $code;
    }
} 