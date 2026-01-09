<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use Carbon\Carbon;

class EvaluationPublishShortcutService
{
    public function getShortcutSummary(?ESBTPAnneeUniversitaire $anneeEnCours, ?Carbon $today = null): array
    {
        if (!$anneeEnCours) {
            return ['show' => false];
        }

        $today = $today ?: Carbon::today();

        $baseQuery = ESBTPEvaluation::query()
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->where('is_published', false)
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED);

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            return ['show' => false];
        }

        $undated = (clone $baseQuery)
            ->whereNull('date_evaluation')
            ->count();

        $overdue = (clone $baseQuery)
            ->whereNotNull('date_evaluation')
            ->whereDate('date_evaluation', '<', $today)
            ->count();

        $upcoming = (clone $baseQuery)
            ->whereNotNull('date_evaluation')
            ->whereDate('date_evaluation', '>=', $today)
            ->count();

        $soon = (clone $baseQuery)
            ->whereNotNull('date_evaluation')
            ->whereBetween('date_evaluation', [$today->copy()->startOfDay(), $today->copy()->addDays(7)->endOfDay()])
            ->count();

        return [
            'show' => true,
            'total' => $total,
            'undated' => $undated,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'soon' => $soon,
        ];
    }
}
