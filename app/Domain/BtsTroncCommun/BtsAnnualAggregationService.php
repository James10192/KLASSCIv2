<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;

class BtsAnnualAggregationService
{
    public function __construct(
        private BtsPhaseResolver $resolver
    ) {
    }

    public function resolveStudentContext(
        ESBTPEtudiant $etudiant,
        ?int $anneeId,
        ?int $requestedClasseId,
        string $periode,
        bool $includeAllStatuses = true
    ): array {
        $allYearInscriptions = $etudiant->inscriptions()
            ->with([
                'filiere',
                'classe.filiere',
                'classe.niveau',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ])
            ->when($anneeId, fn ($query) => $query->where('annee_universitaire_id', $anneeId))
            ->orderByDesc('date_inscription')
            ->orderByDesc('id')
            ->get();

        $filtered = $includeAllStatuses ? $allYearInscriptions : $allYearInscriptions->where('status', 'active')->values();
        $selected = $requestedClasseId
            ? $filtered->firstWhere('classe_id', $requestedClasseId)
            : $filtered->first();
        $selected ??= $requestedClasseId
            ? $allYearInscriptions->firstWhere('classe_id', $requestedClasseId)
            : $allYearInscriptions->first();

        if (! $selected) {
            return [
                'inscription' => null,
                'effective_classe_id' => $requestedClasseId,
                'journey' => null,
                'source_model' => 'phase_based',
            ];
        }

        $journey = $this->resolver->buildJourney($selected);
        $semester = match ($periode) {
            'semestre2' => 2,
            default => 1,
        };
        $phase = $periode === 'annuel'
            ? ($journey['current_phase'] ?? null)
            : $this->resolver->resolveSemesterPhase($selected, $semester);

        return [
            'inscription' => $selected,
            'effective_classe_id' => $phase['classe_id'] ?? $selected->classe_id,
            'journey' => $journey,
            'source_model' => $journey['source_model'],
            'effective_phase' => $phase,
            'all_inscriptions' => $allYearInscriptions,
        ];
    }
}
