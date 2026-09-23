<?php

namespace App\Services\MailPulse;

final class ResultatVerificationDistante
{
    /**
     * Refus qui tiennent a la configuration ou a l'indisponibilite du canal,
     * pas a la famille : attendre ne les leve pas. La demande ne doit pas
     * rester masquee a l'ecole a cause d'eux.
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
