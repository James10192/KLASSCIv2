<?php

namespace App\Services\MailPulse;

final class ResultatVerificationDistante
{
    /**
     * Refus qui tiennent a la configuration ou a l'indisponibilite du canal,
     * pas a la famille : attendre ne les leve pas. Ils sont journalises comme
     * tels, pour distinguer une panne d'instance d'un contact injoignable.
     */
    private const DEFINITIFS = [
        MailPulseApi::DESACTIVE, MailPulseApi::CLE_ABSENTE, 'auth_failed',
        'whatsapp_indisponible', 'verification_indisponible', 'invalid_contract',
    ];

    private function __construct(
        public readonly bool $ok,
        public readonly string $code,
        public readonly ?string $id = null,
        public readonly ?int $httpStatus = null,
        public readonly ?int $retryAfter = null,
    ) {}

    public static function ok(string $code, string $id): self
    {
        return new self(true, $code, $id);
    }

    public static function echec(string $code, ?int $httpStatus = null, ?int $retryAfter = null): self
    {
        return new self(false, $code, null, $httpStatus, $retryAfter);
    }

    public function definitif(): bool
    {
        return ! $this->ok && in_array($this->code, self::DEFINITIFS, true);
    }
}
