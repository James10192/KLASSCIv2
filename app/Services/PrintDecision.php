<?php

namespace App\Services;

use App\Models\ESBTPDocumentApproval;

final class PrintDecision
{
    public const SOLDE = 'solde';

    public const APPROVAL = 'approval';

    public const PERMISSION = 'permission';

    public function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason,
        public readonly float $solde,
        public readonly ?ESBTPDocumentApproval $approval,
        public readonly bool $gated,
    ) {
    }

    public static function open(): self
    {
        return new self(true, null, 0.0, null, false);
    }

    public static function denied(string $reason, float $solde = 0.0, bool $gated = true): self
    {
        return new self(false, $reason, $solde, null, $gated);
    }

    public static function approved(ESBTPDocumentApproval $approval, float $solde = 0.0): self
    {
        return new self(true, null, $solde, $approval, true);
    }

    public function message(): string
    {
        return match ($this->reason) {
            self::SOLDE => sprintf(
                'Impression bloquée : solde impayé de %s F. L\'étudiant doit régulariser en caisse.',
                number_format($this->solde, 0, ',', ' ')
            ),
            self::APPROVAL => 'Impression bloquée : l\'accord de la responsable scolarité est requis.',
            self::PERMISSION => 'Vous n\'avez pas le droit d\'imprimer ce document.',
            default => 'Impression bloquée.',
        };
    }

    public function isUnpaid(): bool
    {
        return $this->reason === self::SOLDE;
    }

    public function needsApprovalRequest(): bool
    {
        return $this->gated && $this->reason === self::APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->gated && $this->allowed && $this->approval !== null;
    }
}
