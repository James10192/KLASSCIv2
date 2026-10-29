<?php

namespace App\Services;

use App\Models\ESBTPInscription;

class SoldeEtudiant
{
    public function __construct(private readonly RelanceCalculationService $relances)
    {
    }

    public function impaye(int $etudiantId): float
    {
        return $this->impayes([$etudiantId])[$etudiantId] ?? 0.0;
    }

    /**
     * Dette ECHUE, pas le restant total.
     *
     * Une tranche de décembre non encore due ne bloque pas un document en novembre
     * si les échéances permises à ce jour sont payées.
     *
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
            ->with([
                'fraisSubscriptions.selectedOption.assignments',
                'paiements' => fn ($q) => $q->where('status', 'validé')->whereNull('deleted_at'),
            ])
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('status', 'active')
            ->whereHas('anneeUniversitaire', fn ($q) => $q->where('is_current', true))
            ->get();

        if ($inscriptions->isEmpty()) {
            return $soldes;
        }

        $this->relances->preloadForInscriptions($inscriptions);

        foreach ($inscriptions as $inscription) {
            $state = $this->relances->getFinancialState($inscription);
            $eid = (int) $inscription->etudiant_id;
            $soldes[$eid] = round(($soldes[$eid] ?? 0) + (float) ($state['overdue_amount'] ?? 0), 2);
        }

        return $soldes;
    }
}
