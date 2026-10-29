<?php

namespace App\Domain\Comptabilite\Paiements\Services;

use App\Models\User;
use App\Services\Caisse\CashSessionService;
use Carbon\CarbonImmutable;

/**
 * Décide si la date métier d'un nouvel encaissement peut être utilisée.
 *
 * created_at reste la date de saisie dans KLASSCI ; date_paiement représente
 * la date réelle du versement. Les verrous eux-mêmes restent centralisés dans
 * AccountingPeriodGuard et CashSessionService.
 */
class PaymentDatePolicy
{
    public function __construct(
        private readonly AccountingPeriodGuard $periodGuard,
        private readonly CashSessionService $cashSessions,
    ) {
    }

    public function validationMessage(?User $user, mixed $rawDate, mixed $rawMode): ?string
    {
        if (! is_string($rawDate) || trim($rawDate) === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($rawDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        $today = CarbonImmutable::today();

        if ($date->gt($today)) {
            return 'La date du paiement ne peut pas être dans le futur.';
        }

        $locked = $this->periodGuard->lockedContext(
            $date,
            $user,
            ['action' => 'create_payment']
        );

        if ($locked !== null) {
            return sprintf(
                'La date du %s appartient à une période comptable verrouillée jusqu’au %s. '
                .'Choisissez une date ouverte ou passez par une écriture corrective.',
                $locked['date']->format('d/m/Y'),
                $locked['locked_until']->format('d/m/Y'),
            );
        }

        if ($user) {
            return $this->cashSessions->dateValidationMessage(
                $user,
                is_string($rawMode) ? $rawMode : null,
                $date->toDateString()
            );
        }

        return null;
    }
}
