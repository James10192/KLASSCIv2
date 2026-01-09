<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use Carbon\Carbon;

class TimetableShortcutService
{
    public function getShortcutSummary(?ESBTPAnneeUniversitaire $anneeEnCours, ?Carbon $today = null): array
    {
        $items = $this->getClassesNeedingTimetables($anneeEnCours, $today);

        return [
            'show' => count($items) > 0,
            'total' => count($items),
            'missing' => count(array_filter($items, fn ($item) => $item['status'] === 'missing')),
            'expired' => count(array_filter($items, fn ($item) => $item['status'] === 'expired')),
            'expiring_soon' => count(array_filter($items, fn ($item) => $item['status'] === 'expiring_soon')),
            'items' => $items,
        ];
    }

    public function getClassesNeedingTimetables(?ESBTPAnneeUniversitaire $anneeEnCours, ?Carbon $today = null): array
    {
        if (!$anneeEnCours) {
            return [];
        }

        $today = $today ?: Carbon::today();
        $classes = ESBTPClasse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($classes->isEmpty()) {
            return [];
        }

        $emploiTempsQuery = ESBTPEmploiTemps::query()
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->whereIn('classe_id', $classes->pluck('id'))
            ->orderBy('date_fin', 'desc')
            ->orderBy('created_at', 'desc');

        $emploisTemps = $emploiTempsQuery->get()->groupBy('classe_id');
        $items = [];

        $currentWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $nextWeekStart = $currentWeekStart->copy()->addWeek();

        foreach ($classes as $classe) {
            $classTimetables = $emploisTemps->get($classe->id, collect());

            if ($classTimetables->isEmpty()) {
                $items[] = [
                    'class' => $classe,
                    'status' => 'missing',
                    'source' => null,
                    'target_start' => $currentWeekStart->copy(),
                    'target_end' => $currentWeekStart->copy()->addDays(6),
                ];
                continue;
            }

            $validTimetables = $classTimetables->filter(function ($timetable) {
                return $timetable->date_debut && $timetable->date_fin;
            });

            $expiringSoon = $validTimetables
                ->filter(function ($timetable) use ($today) {
                    $endDate = Carbon::parse($timetable->date_fin);
                    return $endDate->between($today, $today->copy()->addDays(3));
                })
                ->sortBy('date_fin')
                ->first();

            $hasCurrentOrUpcoming = $validTimetables->first(function ($timetable) use ($today) {
                return Carbon::parse($timetable->date_fin)->gte($today);
            });

            if ($expiringSoon) {
                $items[] = [
                    'class' => $classe,
                    'status' => 'expiring_soon',
                    'source' => $expiringSoon,
                    'target_start' => $nextWeekStart->copy(),
                    'target_end' => $nextWeekStart->copy()->addDays(6),
                ];
                continue;
            }

            if (!$hasCurrentOrUpcoming) {
                $source = $validTimetables->sortByDesc('date_fin')->first()
                    ?: $classTimetables->sortByDesc('created_at')->first();

                $items[] = [
                    'class' => $classe,
                    'status' => 'expired',
                    'source' => $source,
                    'target_start' => $nextWeekStart->copy(),
                    'target_end' => $nextWeekStart->copy()->addDays(6),
                ];
            }
        }

        return $items;
    }
}
