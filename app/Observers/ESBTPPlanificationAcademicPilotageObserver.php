<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPPlanificationAcademique;

final class ESBTPPlanificationAcademicPilotageObserver
{
    public function __construct(private readonly AcademicMetricSnapshotInvalidationService $invalidation) {}

    public function saved(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);

        if ($planning->wasRecentlyCreated || $planning->wasChanged('enseignant_principal_id')) {
            $this->synchroniserEvaluationsLmd($planning, $planning->enseignant_principal_id ? (int) $planning->enseignant_principal_id : null);
        }
    }

    public function deleted(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
        $this->synchroniserEvaluationsLmd($planning, null);
    }

    public function restored(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
        $this->synchroniserEvaluationsLmd($planning, $planning->enseignant_principal_id ? (int) $planning->enseignant_principal_id : null);
    }

    /**
     * Une correction faite directement dans le planning doit atteindre les
     * évaluations déjà créées. Le bulk update est volontaire : il évite que la
     * garde de création d'évaluation refuse le bref état « enseignant retiré »
     * et la modification du planning reste l'événement audité qui explique le
     * changement dérivé.
     */
    private function synchroniserEvaluationsLmd(ESBTPPlanificationAcademique $planning, ?int $enseignantId): void
    {
        if (! $planning->matiere_id || ! $planning->filiere_id || ! $planning->niveau_etude_id
            || ! $planning->annee_universitaire_id || ! $planning->semestre) {
            return;
        }

        $classeIds = ESBTPClasse::query()
            ->where('systeme_academique', 'LMD')
            ->where('niveau_etude_id', $planning->niveau_etude_id)
            ->where(function ($query) use ($planning) {
                $query->where('filiere_id', $planning->filiere_id)
                    ->orWhereHas('parcours', fn ($parcours) => $parcours->where('filiere_id', $planning->filiere_id));
            })
            ->pluck('id');

        if ($classeIds->isEmpty()) {
            return;
        }

        $s = (int) $planning->semestre;
        $periodes = [(string) $s, 'semestre'.$s, 'S'.$s, 'Semestre '.$s, 'semestre '.$s];

        ESBTPEvaluation::query()
            ->whereIn('classe_id', $classeIds)
            ->where('matiere_id', $planning->matiere_id)
            ->where('annee_universitaire_id', $planning->annee_universitaire_id)
            ->whereIn('periode', $periodes)
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->update([
                'enseignant_id' => $enseignantId,
                'enseignant_externe_nom' => null,
                'updated_at' => now(),
            ]);
    }
}
