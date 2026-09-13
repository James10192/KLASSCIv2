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
                'Impression bloquée : échéance(s) en retard de %s F. L\'étudiant doit régulariser en caisse.',
                number_format($this->solde, 0, ',', ' ')
            ),
            self::APPROVAL => 'Impression bloquée : l\'accord de la responsable scolarité est requis.',
            self::PERMISSION => 'Impression bloquée : vous n\'avez pas le droit d\'imprimer ce document.',
            default => 'Impression bloquée.',
        };
    }

    public function isUnpaid(): bool
    {
        return $this->reason === self::SOLDE;
    }

    public function needsApprovalRequest(): bool
    {
        // Le refus faute de droit se demande aussi : c'est la sortie prevue.
        // Qui n'a pas le droit d'imprimer peut le faire une fois qu'une personne
        // habilitee le lui a accorde, document par document.
        //
        // Le message, lui, ne promet pas la demarche : l'ecran ne montre le
        // bouton qu'a qui porte l'un des droits `documents.*`, et promettre a
        // tous une porte que sept roles n'ont pas serait mentir.
        return $this->gated && in_array($this->reason, [self::APPROVAL, self::PERMISSION], true);
    }

    public function isApproved(): bool
    {
        return $this->gated && $this->allowed && $this->approval !== null;
    }
}
