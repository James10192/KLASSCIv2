<?php

namespace App\Domain\Comptabilite\Paiements\Services;

use App\Helpers\SettingsHelper;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Source unique du verrou de période comptable.
 *
 * Les contrôleurs historiques et la création d'un paiement passent ici afin
 * que le setting, la comparaison de date et le bypass ne puissent pas diverger.
 */
class AccountingPeriodGuard
{
    /**
     * @return array{date: CarbonImmutable, locked_until: CarbonImmutable}|null
     */
    public function lockedContext(mixed $rawDate, ?User $user, array $logContext = []): ?array
    {
        $lockedUntil = SettingsHelper::get('comptabilite.period_locked_until');
        if (empty($lockedUntil)) {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($rawDate)->startOfDay();
            $lockDate = CarbonImmutable::parse($lockedUntil)->endOfDay();
        } catch (\Throwable $e) {
            Log::warning('[comptabilite] Setting de verrouillage de période invalide', [
                'period_locked_until' => $lockedUntil,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($date->gt($lockDate)) {
            return null;
        }

        if ($user?->can('comptabilite.period.bypass_lock')) {
            Log::warning('[comptabilite] Bypass verrouillage période utilisé', array_merge([
                'paiement_date' => $date->toDateString(),
                'period_locked_until' => $lockDate->toDateString(),
                'user_id' => $user->id,
            ], $logContext));

            return null;
        }

        return [
            'date' => $date,
            'locked_until' => $lockDate,
        ];
    }
}
