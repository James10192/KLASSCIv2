<?php

namespace App\Services;

use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Schema;

/**
 * Mesure la couverture des règles d'échéancier sur le parc d'inscriptions actives :
 * combien ont un snapshot calculé vs combien sont en mode fallback. Sert au diagnostic
 * (commande analytics:diagnose) et au bandeau "qualité données" de la page Analytics.
 */
class EcheancierCoverageService
{
    public function __construct(private readonly EcheancierReadinessService $readiness) {}

    /**
     * @return array{
     *   total_actives:int,
     *   with_snapshot:int,
     *   without_snapshot:int,
     *   coverage_pct:float,
     *   mode:string,
     *   note:?string
     * }
     */
    public function summary(?int $anneeId = null): array
    {
        $base = ESBTPInscription::query()
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereNull('deleted_at')
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId));

        $total = (clone $base)->count();

        if (!Schema::hasTable('esbtp_inscription_echeancier_snapshots')) {
            return [
                'total_actives'    => $total,
                'with_snapshot'    => 0,
                'without_snapshot' => $total,
                'coverage_pct'     => 0.0,
                'mode'             => $this->readiness->mode(),
                'note'             => 'Table snapshots inexistante',
            ];
        }

        $with = (clone $base)
            ->whereExists(fn ($q) => $q->selectRaw(1)
                ->from('esbtp_inscription_echeancier_snapshots as s')
                ->whereColumn('s.inscription_id', 'esbtp_inscriptions.id')
                ->whereNull('s.deleted_at'))
            ->count();

        return [
            'total_actives'    => $total,
            'with_snapshot'    => $with,
            'without_snapshot' => max(0, $total - $with),
            'coverage_pct'     => $total > 0 ? round($with / $total * 100, 1) : 0.0,
            'mode'             => $this->readiness->mode(),
            'note'             => $this->readiness->noteForMode(),
        ];
    }
}
