<?php

namespace App\Domain\Admissions;

use App\Models\User;
use App\Services\RendezVous\FamillesAPrevenirRdv;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Les nombres de la section « Admissions » du menu qui ne viennent pas deja de
 * FileDesDemandes : les dossiers a traiter par type (sous-liens de
 * « Dossiers ») et les familles a prevenir par telephone.
 *
 * Le menu est rendu sur CHAQUE page : cache court, et garde sur les tables,
 * parce qu'au deploiement le code precede la migration de quelques secondes.
 */
final class CompteursDuMenu
{
    private const CLE_A_PREVENIR = 'admissions.menu.familles_a_prevenir';

    /** @return array<string, int> les dossiers a traiter, par type lisible */
    public static function aTraiterParType(?User $agent): array
    {
        $comptes = [];
        foreach (FileDesDemandes::typesVisibles($agent) as $type) {
            $comptes[$type] = FileDesDemandes::aTraiterDuType($type);
        }

        return $comptes;
    }

    /** Le meme compte que la liste d'appel du planning (FamillesAPrevenirRdv). */
    public static function famillesAPrevenir(?User $agent): int
    {
        if (! $agent?->can('inscriptions.rdv.view')) {
            return 0;
        }

        return Cache::remember(self::CLE_A_PREVENIR, 60, fn (): int => Schema::hasColumn('esbtp_rdv_reservations', 'convocation_statut')
            ? app(FamillesAPrevenirRdv::class)->compter()
            : 0);
    }
}
