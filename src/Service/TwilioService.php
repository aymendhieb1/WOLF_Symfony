<?php

namespace App\Service;

// use Twilio\Rest\Client; // Commented out as SMS is disabled
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwilioService
{
    // private $twilioClient;
    // private $twilioPhoneNumber;
    private $adminPhoneNumber;
    private $session;
    private const DEFAULT_CODE = '0000'; // Added default code constant

    public function __construct(
        string $accountSid,
        string $authToken,
        string $twilioPhoneNumber,
        string $adminPhoneNumber,
        SessionInterface $session
    ) {
        // $this->twilioClient = new Client($accountSid, $authToken); // Commented out as SMS is disabled
        // $this->twilioPhoneNumber = $twilioPhoneNumber;
        $this->adminPhoneNumber = $adminPhoneNumber;
        $this->session = $session;
    }

    public function sendVerificationCode(string $phoneNumber): string
    {
        // Always return the default code '0000' for testing
        return self::DEFAULT_CODE;
    }

    public function verifyCode(string $code): bool
    {
        // Always accept '0000' as valid code
        return $code === self::DEFAULT_CODE;
    }
} 