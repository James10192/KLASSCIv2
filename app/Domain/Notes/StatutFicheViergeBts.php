<?php

namespace App\Domain\Notes;

use App\Models\ESBTPEcheancierRule;

/**
 * Libellés courts pour les feuilles manuscrites BTS.
 * Ne jamais transformer un statut inconnu ou absent en « affecté ».
 */
final class StatutFicheViergeBts
{
    public static function abreger(?string $statut): string
    {
        $brut = trim((string) $statut);
        if ($brut === '') {
            return '—';
        }

        return match (ESBTPEcheancierRule::normalizeStatus($brut)) {
            ESBTPEcheancierRule::STATUS_AFFECTE => 'AFF',
            ESBTPEcheancierRule::STATUS_NON_AFFECTE => 'NAFF',
            ESBTPEcheancierRule::STATUS_REAFFECTE => 'RAFF',
            default => $brut,
        };
    }
}
