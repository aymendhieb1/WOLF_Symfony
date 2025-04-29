<?php

namespace App\Message;

class SendEmailMessage
{
    private $to;
    private $subject;
    private $content;

    public function __construct(string $to, string $subject, string $content)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->content = $content;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContent(): string
    {
        return $this->content;
    }
} 