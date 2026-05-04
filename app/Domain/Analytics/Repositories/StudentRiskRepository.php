<?php

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\StudentRiskFeatures;
use App\Models\ESBTPInscription;
use App\Services\RelanceCalculationService;

/**
 * Charge les inscriptions actives filtrées par contexte et extrait les features
 * financières via RelanceCalculationService (logique anti-N+1 préchargée).
 */
class StudentRiskRepository
{
    public function __construct(
        private readonly RelanceCalculationService $relances,
    ) {}

    /**
     * @return array<int, StudentRiskFeatures>
     */
    public function activeStudents(AnalyticsContext $context, int $limit = 1000): array
    {
        $inscriptions = ESBTPInscription::query()
            ->with([
                'etudiant:id,nom,prenoms',
                'classe:id,name,filiere_id',
                'paiements' => fn ($q) => $q->whereNull('deleted_at'),
            ])
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereNull('deleted_at')
            ->when($context->anneeId, fn ($q) => $q->where('annee_universitaire_id', $context->anneeId))
            ->when($context->classeId, fn ($q) => $q->where('classe_id', $context->classeId))
            ->when($context->filiereId, fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('filiere_id', $context->filiereId)))
            ->limit($limit)
            ->get();

        if ($inscriptions->isEmpty()) {
            return [];
        }

        $this->relances->preloadForInscriptions($inscriptions);

        return $inscriptions->map(function (ESBTPInscription $inscription) {
            $state = $this->relances->getFinancialState($inscription);
            $totalDu = (float) ($state['total_due'] ?? 0);
            $totalPaye = (float) ($state['total_paid_validated'] ?? 0);
            $soldeRestant = (float) ($state['remaining_total'] ?? max(0.0, $totalDu - $totalPaye));
            $ratioPaye = $totalDu > 0 ? min(1.0, $totalPaye / $totalDu) : 1.0;
            $joursRetard = (int) ($state['overdue_days'] ?? 0);
            $nbPaiements = $inscription->paiements->where('status', 'validé')->count();

            $etudiant = $inscription->etudiant;
            $classe = $inscription->classe;

            return new StudentRiskFeatures(
                inscriptionId: (int) $inscription->id,
                etudiantId: (int) ($etudiant?->id ?? 0),
                etudiantNom: trim(($etudiant?->prenoms ?? '') . ' ' . ($etudiant?->nom ?? '')),
                classeId: (int) ($classe?->id ?? 0),
                classeNom: $classe?->name ?? 'N/A',
                totalAttendu: (float) $totalDu,
                totalPaye: $totalPaye,
                soldeRestant: $soldeRestant,
                ratioPaye: $ratioPaye,
                joursRetard: $joursRetard,
                nbPaiements: $nbPaiements,
            );
        })->all();
    }
}
