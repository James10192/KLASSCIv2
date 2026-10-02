<?php

namespace App\Services\Paywall;

use App\Models\ESBTPSystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Les codes d'urgence encore utilisables (non consommes, non expires).
 *
 * Lus par l'ecran du paywall et par le tableau de bord du service technique,
 * qui recopiait jusque-la la meme boucle.
 */
class CodesDUrgence
{
    public function actifs(): Collection
    {
        return ESBTPSystemSetting::where('key', 'LIKE', 'emergency_code_%')->get()
            ->map(function ($reglage) {
                $donnees = json_decode((string) $reglage->value, true);
                if (! is_array($donnees) || ! empty($donnees['used']) || time() > (int) ($donnees['expires_at'] ?? 0)) {
                    return null;
                }

                return (object) [
                    'code' => str_replace('emergency_code_', '', $reglage->key),
                    'expires_at' => Carbon::createFromTimestamp((int) $donnees['expires_at']),
                    'created_by' => $donnees['created_by'] ?? '—',
                ];
            })
            ->filter()
            ->sortBy('expires_at')
            ->values();
    }
}
