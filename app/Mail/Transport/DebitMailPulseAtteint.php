<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Courriel refusé pour cause de débit : le plafond local, ou un 429 de débit de
 * MailPulse. Il passera plus tard, à l'identique ; `reessayerDans` dit quand.
 * Reste une `TransportException` : qui attrape les échecs d'envoi l'attrape aussi.
 */
final class DebitMailPulseAtteint extends TransportException
{
    public function __construct(string $message, public readonly int $reessayerDans)
    {
        parent::__construct($message);
    }
}
