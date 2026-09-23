<?php

namespace App\Services\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Remet en cohérence les colonnes de portée dénormalisées des notes finalisées.
 * Les brouillons ne sont jamais modifiés.
 */
class NoteSubmissionSynchronizationService
{
    /**
     * Après une validation groupée : ne synchronise que si elle est complète
     * (aucun refus) et que l'utilisateur a un droit de saisie, sur les seules
     * évaluations qu'il est autorisé à gérer. Les brouillons, jamais.
     *
     * @param array<int, int> $evaluationIds
     * @return array{performed: bool, reason: string, evaluations_synchronized: int, notes_synchronized: int}
     */
    public function apresValidation(?User $user, bool $submitFinal, int $errors, array $evaluationIds): array
    {
        $peutSaisir = $user && ($user->can('notes.create') || $user->can('notes.edit') || $user->can('notes.manage_own'));
        if (! $submitFinal || $errors > 0 || ! $peutSaisir) {
            return [
                'performed' => false,
                'reason' => $submitFinal ? ($errors === 0 ? 'permission_missing' : 'validation_errors') : 'draft',
                'evaluations_synchronized' => 0,
                'notes_synchronized' => 0,
            ];
        }

        $synchronization = $this->synchronize($evaluationIds);
        Log::info('Notes final submission synchronized', [
            'user_id' => $user->id,
            'evaluation_ids' => $evaluationIds,
            'evaluations_synchronized' => $synchronization['evaluations_synchronized'],
            'notes_synchronized' => $synchronization['notes_synchronized'],
        ]);

        return $synchronization;
    }

    /**
     * @param array<int, int|string> $evaluationIds
     * @return array{performed: bool, reason: string, evaluations_synchronized: int, notes_synchronized: int}
     */
    public function synchronize(array $evaluationIds): array
    {
        $ids = collect($evaluationIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [
                'performed' => true,
                'reason' => 'no_authorized_evaluation',
                'evaluations_synchronized' => 0,
                'notes_synchronized' => 0,
            ];
        }

        $evaluations = ESBTPEvaluation::query()
            ->whereIn('id', $ids)
            ->get(['id', 'classe_id', 'matiere_id', 'periode']);

        $evaluationsSynchronized = 0;
        $notesSynchronized = 0;

        foreach ($evaluations as $evaluation) {
            $semestre = (int) str_replace('semestre', '', (string) $evaluation->periode);

            $updated = ESBTPNote::query()
                ->where('evaluation_id', $evaluation->id)
                // null est le statut « validé » des données créées avant
                // l'introduction explicite des brouillons.
                ->where(function ($query) {
                    $query->whereNull('submission_status')
                        ->orWhere('submission_status', ESBTPNote::SUBMISSION_SUBMITTED);
                })
                ->where(function ($query) use ($evaluation, $semestre) {
                    $query->where('classe_id', '!=', $evaluation->classe_id)
                        ->orWhere('matiere_id', '!=', $evaluation->matiere_id)
                        ->orWhere('semestre', '!=', $semestre);
                })
                ->update([
                    'classe_id' => $evaluation->classe_id,
                    'matiere_id' => $evaluation->matiere_id,
                    'semestre' => $semestre,
                ]);

            $evaluationsSynchronized++;
            $notesSynchronized += $updated;
        }

        return [
            'performed' => true,
            'reason' => 'final_submission',
            'evaluations_synchronized' => $evaluationsSynchronized,
            'notes_synchronized' => $notesSynchronized,
        ];
    }
}
