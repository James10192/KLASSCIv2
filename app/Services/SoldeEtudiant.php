<?php

namespace App\Services;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;

class SoldeEtudiant
{
    public function impaye(int $etudiantId): float
    {
        return $this->impayes([$etudiantId])[$etudiantId] ?? 0.0;
    }

    /**
     * @param  array<int, int>  $etudiantIds
     * @return array<int, float>
     */
    public function impayes(array $etudiantIds): array
    {
        $etudiantIds = array_values(array_unique(array_map('intval', $etudiantIds)));
        $soldes = array_fill_keys($etudiantIds, 0.0);

        if ($etudiantIds === []) {
            return $soldes;
        }

        $inscriptions = ESBTPInscription::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('status', 'active')
            ->whereHas('anneeUniversitaire', fn ($q) => $q->where('is_current', true))
            ->get(['id', 'etudiant_id']);

        if ($inscriptions->isEmpty()) {
            return $soldes;
        }

        $inscriptionIds = $inscriptions->pluck('id')->all();

        $dus = ESBTPFraisSubscription::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->charged()
            ->selectRaw('inscription_id, SUM(amount) as total')
            ->groupBy('inscription_id')
            ->pluck('total', 'inscription_id');

        $encaisses = ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->valides()
            ->encaissements()
            ->selectRaw('inscription_id, SUM(montant) as total')
            ->groupBy('inscription_id')
            ->pluck('total', 'inscription_id');

        $avoirs = ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->valides()
            ->avoires()
            ->selectRaw('inscription_id, SUM(montant) as total')
            ->groupBy('inscription_id')
            ->pluck('total', 'inscription_id');

        foreach ($inscriptions as $inscription) {
            $du = (float) ($dus[$inscription->id] ?? 0);
            $paye = max(0.0, (float) ($encaisses[$inscription->id] ?? 0) - (float) ($avoirs[$inscription->id] ?? 0));
            $soldes[(int) $inscription->etudiant_id] = round(max(0, $du - $paye), 2);
        }

        return $soldes;
    }
}
