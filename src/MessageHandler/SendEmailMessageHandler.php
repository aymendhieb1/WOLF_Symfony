<?php

namespace App\MessageHandler;

use App\Message\SendEmailMessage;
use App\Service\GmailApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendEmailMessageHandler implements MessageHandlerInterface
{
    private $gmailService;
    private $logger;

    public function __construct(GmailApiService $gmailService, LoggerInterface $logger)
    {
        $this->gmailService = $gmailService;
        $this->logger = $logger;
    }

    public function __invoke(SendEmailMessage $message)
    {
        $this->logger->info('Processing email message in handler', [
            'to' => $message->getTo(),
            'subject' => $message->getSubject()
        ]);

        try {
            $this->gmailService->sendEmail(
                $message->getTo(),
                $message->getSubject(),
                $message->getContent()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email in handler', [
                'error' => $e->getMessage(),
                'to' => $message->getTo()
            ]);
            throw $e;
        }
    }
} 