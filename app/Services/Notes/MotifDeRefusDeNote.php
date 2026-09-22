<?php

namespace App\Services\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;

/**
 * Pourquoi une ligne de la saisie groupée des notes est refusée.
 *
 * Une instance sert une seule requête : elle garde la cohorte de chaque
 * évaluation autorisée, et ces évaluations sont celles que la validation
 * finale a le droit de synchroniser.
 */
class MotifDeRefusDeNote
{
    /** @var array<int, int[]> identifiant d'évaluation => étudiants admis */
    private array $cohortes = [];

    public function __construct(private NoteStudentCohortService $cohortService)
    {
    }

    /**
     * @param callable(ESBTPEvaluation): bool $peutGerer
     * @return string|null la raison du refus, ou null si la ligne s'enregistre
     */
    public function pour(array $entry, ?ESBTPEvaluation $evaluation, ?ESBTPNote $note, bool $peutModifierValidee, callable $peutGerer): ?string
    {
        if (! $evaluation || ! $evaluation->is_published) {
            return 'évaluation introuvable ou non publiée';
        }
        if (! $peutGerer($evaluation)) {
            return 'non autorisé sur cette évaluation';
        }

        $evalKey = (int) $evaluation->id;
        $this->cohortes[$evalKey] ??= $this->cohortService->allowedStudentIdsForEvaluation($evaluation)->all();
        if (! in_array((int) $entry['etudiant_id'], $this->cohortes[$evalKey], true)) {
            return 'étudiant hors de la classe';
        }

        // Garde-fou note ≤ barème (défense en profondeur, déjà couvert pour
        // les saisies unitaires par NoteRespectsBareme).
        $rawNote = $entry['note'] ?? null;
        if (! (bool) ($entry['is_absent'] ?? false) && $rawNote !== null && $rawNote !== '') {
            $bareme = (float) $evaluation->bareme;
            if ($bareme <= 0 || (float) $rawNote > $bareme) {
                return 'note hors barème';
            }
        }

        if ($note && $note->isSubmitted() && ! $peutModifierValidee) {
            return 'note déjà validée';
        }

        return null;
    }

    /** @return int[] les évaluations que l'utilisateur est autorisé à gérer */
    public function evaluationsAutorisees(): array
    {
        return array_keys($this->cohortes);
    }
}
