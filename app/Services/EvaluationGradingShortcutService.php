<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use Carbon\Carbon;

class EvaluationGradingShortcutService
{
    public function getShortcutSummary(?ESBTPAnneeUniversitaire $anneeEnCours, User $user, ?Carbon $today = null): array
    {
        if (! $anneeEnCours || ! $user) {
            return ['show' => false];
        }

        $today = $today ?: Carbon::today();

        $baseQuery = ESBTPEvaluation::query()
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->whereNotNull('date_evaluation')
            ->whereDate('date_evaluation', '<', $today)
            ->where('is_published', true)
            ->where('notes_published', false);

        $isTeacher = $user->can('can_teach');
        $canSeeAll = $user->can('access_admin')
            || $user->can('can_manage_school')
            || $user->can('can_coordinate_academics')
            || $user->can('view_exams')
            || $user->can('view_evaluations');

        if ($isTeacher && ! $canSeeAll) {
            $baseQuery->where('enseignant_id', $user->id);
        }

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            return ['show' => false];
        }

        $missingNotes = (clone $baseQuery)
            ->whereDoesntHave('notes')
            ->count();

        $notesUnpublished = (clone $baseQuery)
            ->whereHas('notes')
            ->count();

        $items = (clone $baseQuery)
            ->with(['classe', 'matiere'])
            ->withCount('notes')
            ->orderBy('date_evaluation', 'asc')
            ->limit(5)
            ->get()
            ->map(function (ESBTPEvaluation $evaluation) {
                return [
                    'id' => $evaluation->id,
                    'title' => $evaluation->titre,
                    'date' => $evaluation->date_evaluation,
                    'classe' => $evaluation->classe?->name,
                    'matiere' => $evaluation->matiere?->name,
                    'notes_count' => $evaluation->notes_count ?? 0,
                ];
            })
            ->toArray();

        return [
            'show' => true,
            'total' => $total,
            'missing_notes' => $missingNotes,
            'notes_unpublished' => $notesUnpublished,
            'items' => $items,
        ];
    }
}
