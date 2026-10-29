<?php

namespace App\Services\ParentChatbot;

/**
 * Raised when MailPulse durably rejects a reply because the WhatsApp 24h
 * service window has closed. A conversational answer that arrives a day late
 * is worthless, so the inbound event is dead-lettered instead of retried.
 */
class ParentChatbotWindowClosedException extends \RuntimeException
{
    public function __construct(string $message = 'The WhatsApp service window is closed for this parent.')
    {
        parent::__construct($message);
    }
}
