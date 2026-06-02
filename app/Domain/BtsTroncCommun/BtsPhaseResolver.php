<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;

class BtsPhaseResolver
{
    public function detectSourceModel(ESBTPInscription $inscription): string
    {
        if ($inscription->phases->isNotEmpty()) {
            return 'phase_based';
        }

        if ($inscription->isSpecialisation() || $inscription->hasSpecialisation() || $inscription->inscriptionOrigine) {
            return 'legacy_dual_inscription';
        }

        return 'phase_based';
    }

    public function getCurrentPhase(ESBTPInscription $inscription): ?array
    {
        $journey = $this->buildJourney($inscription);

        return $journey['current_phase'];
    }

    public function resolveSemesterPhase(ESBTPInscription $inscription, int $semester): ?array
    {
        $journey = $this->buildJourney($inscription);

        return collect($journey['timeline'])->first(function (array $phase) use ($semester) {
            $end = $phase['semestre_fin'] ?? $phase['semestre_debut'];

            return $semester >= $phase['semestre_debut'] && $semester <= $end;
        });
    }

    public function buildJourney(ESBTPInscription $inscription): array
    {
        $sourceModel = $this->detectSourceModel($inscription);

        return $sourceModel === 'legacy_dual_inscription'
            ? $this->buildLegacyJourney($inscription)
            : $this->buildPhaseBasedJourney($inscription);
    }

    private function buildPhaseBasedJourney(ESBTPInscription $inscription): array
    {
        $phases = $inscription->phases
            ->sortBy(['semestre_debut', 'id'])
            ->values();

        if ($phases->isEmpty()) {
            // Defensive: only synthesize a TC phase if the inscription is actually TC.
            // Without this guard, BTS2 inscriptions with no phase get a phantom TC banner.
            $inscriptionIsTc = $inscription->filiere?->isTroncCommun()
                || $inscription->classe?->filiere?->isTroncCommun();

            if (! $inscriptionIsTc) {
                return [
                    'source_model' => 'phase_based',
                    'current_phase' => null,
                    'timeline' => [],
                ];
            }

            $timeline = [$this->formatPhaseArray([
                'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
                'classe_id' => $inscription->classe_id,
                'filiere_id' => $inscription->filiere_id,
                'semestre_debut' => 1,
                'semestre_fin' => $inscription->filiere?->semestres_tronc_commun ?: 1,
                'is_active' => true,
                'date_activation' => $inscription->date_inscription,
            ], $inscription)];

            return [
                'source_model' => 'phase_based',
                'current_phase' => $timeline[0],
                'timeline' => $timeline,
            ];
        }

        $timeline = $phases
            ->map(fn (ESBTPInscriptionPhase $phase) => $this->formatPhaseArray($phase, $inscription))
            ->values()
            ->all();

        $currentPhase = collect($timeline)->firstWhere('is_active', true) ?? last($timeline);

        return [
            'source_model' => 'phase_based',
            'current_phase' => $currentPhase,
            'timeline' => $timeline,
        ];
    }

    private function buildLegacyJourney(ESBTPInscription $inscription): array
    {
        $root = $inscription->isSpecialisation() ? ($inscription->inscriptionOrigine ?? $inscription) : $inscription;
        $specialisation = $root->relationLoaded('inscriptionSpecialisation')
            ? $root->inscriptionSpecialisation
            : $root->inscriptionSpecialisation()->with(['classe.filiere'])->first();

        $tcEnd = max(1, (int) ($root->filiere?->semestres_tronc_commun ?: 1));
        $timeline = [
            $this->formatPhaseArray([
                'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
                'classe_id' => $root->classe_id,
                'filiere_id' => $root->filiere_id,
                'semestre_debut' => 1,
                'semestre_fin' => $tcEnd,
                'is_active' => $specialisation === null,
                'legacy_inscription_id' => $root->id,
                'date_activation' => $root->date_inscription,
                'date_cloture' => $specialisation ? $specialisation->date_inscription : null,
            ], $root),
        ];

        if ($specialisation) {
            $timeline[] = $this->formatPhaseArray([
                'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
                'classe_id' => $specialisation->classe_id,
                'filiere_id' => $specialisation->filiere_id,
                'semestre_debut' => $tcEnd + 1,
                'semestre_fin' => null,
                'is_active' => true,
                'legacy_inscription_id' => $specialisation->id,
                'date_activation' => $specialisation->date_inscription,
            ], $specialisation);
        }

        return [
            'source_model' => 'legacy_dual_inscription',
            'current_phase' => collect($timeline)->firstWhere('is_active', true) ?? last($timeline),
            'timeline' => $timeline,
        ];
    }

    private function formatPhaseArray(ESBTPInscriptionPhase|array $phase, ESBTPInscription $fallbackInscription): array
    {
        $classe = $phase instanceof ESBTPInscriptionPhase
            ? $phase->classe
            : ($phase['classe_id'] ? ESBTPClasse::query()->with(['filiere', 'niveau'])->find($phase['classe_id']) : null);
        $filiere = $phase instanceof ESBTPInscriptionPhase
            ? ($phase->filiere ?? $phase->classe?->filiere)
            : ($classe?->filiere ?? $fallbackInscription->filiere);

        return [
            'type_phase' => $phase instanceof ESBTPInscriptionPhase ? $phase->type_phase : $phase['type_phase'],
            'label' => ($phase instanceof ESBTPInscriptionPhase ? $phase->type_phase : $phase['type_phase']) === ESBTPInscriptionPhase::TYPE_SPECIALISATION
                ? 'Spécialisation'
                : 'Tronc commun',
            'classe_id' => $phase instanceof ESBTPInscriptionPhase ? $phase->classe_id : $phase['classe_id'],
            'classe' => $classe?->name,
            'filiere_id' => $phase instanceof ESBTPInscriptionPhase ? $phase->filiere_id : ($phase['filiere_id'] ?? null),
            'filiere' => $filiere?->name,
            'semestre_debut' => $phase instanceof ESBTPInscriptionPhase ? $phase->semestre_debut : $phase['semestre_debut'],
            'semestre_fin' => $phase instanceof ESBTPInscriptionPhase ? $phase->semestre_fin : ($phase['semestre_fin'] ?? null),
            'is_active' => $phase instanceof ESBTPInscriptionPhase ? $phase->is_active : (bool) ($phase['is_active'] ?? false),
            'date_activation' => optional($phase instanceof ESBTPInscriptionPhase ? $phase->date_activation : ($phase['date_activation'] ?? null))->format('Y-m-d H:i:s'),
            'date_cloture' => optional($phase instanceof ESBTPInscriptionPhase ? $phase->date_cloture : ($phase['date_cloture'] ?? null))->format('Y-m-d H:i:s'),
            'legacy_inscription_id' => $phase instanceof ESBTPInscriptionPhase ? null : ($phase['legacy_inscription_id'] ?? null),
        ];
    }
}
