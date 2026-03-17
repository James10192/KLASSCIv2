<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPEmploiTemps;
use Illuminate\Support\Facades\Route;

class SearchTimetableTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_timetable';
    }

    public function description(): string
    {
        return 'Rechercher l\'emploi du temps d\'une classe. Retourne les séances de cours organisées par jour (lundi à samedi) avec matière, enseignant, horaire et salle.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom ou code de la classe (ex: "B3 COM", "L1 GC")',
                ],
                'jour' => [
                    'type' => 'string',
                    'description' => 'Jour spécifique: "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi". Si non précisé, retourne toute la semaine.',
                ],
            ],
            'required' => ['classe'],
        ];
    }

    public function execute(array $args, $user): array
    {
        $search = $args['classe'];

        $emploiTemps = ESBTPEmploiTemps::query()
            ->with(['classe.filiere', 'seances.matiere', 'seances.teacher.user', 'annee'])
            ->whereHas('classe', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->where('is_current', true)
            ->first();

        if (!$emploiTemps) {
            // Essayer sans is_current (le plus récent)
            $emploiTemps = ESBTPEmploiTemps::query()
                ->with(['classe.filiere', 'seances.matiere', 'seances.teacher.user', 'annee'])
                ->whereHas('classe', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                })
                ->orderByDesc('created_at')
                ->first();
        }

        if (!$emploiTemps) {
            return [
                'results' => [],
                'count' => 0,
                'display_type' => 'text',
                'message' => "Aucun emploi du temps trouvé pour la classe \"{$search}\".",
            ];
        }

        $jourMap = ['lundi' => 0, 'mardi' => 1, 'mercredi' => 2, 'jeudi' => 3, 'vendredi' => 4, 'samedi' => 5];
        $jourNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

        $seances = $emploiTemps->seances->filter(fn ($s) => $s->is_active && $s->type === 'course');

        // Filtrer par jour si spécifié
        if (!empty($args['jour'])) {
            $jourNum = $jourMap[mb_strtolower(trim($args['jour']))] ?? null;
            if ($jourNum !== null) {
                $seances = $seances->filter(fn ($s) => $s->jour === $jourNum);
            }
        }

        // Grouper par jour et trier par heure
        $grouped = $seances->groupBy('jour')->sortKeys();

        $days = [];
        foreach ($grouped as $jour => $daySeances) {
            $slots = $daySeances->sortBy(function ($s) {
                return $s->heure_debut?->format('H:i') ?? '00:00';
            })->map(function ($s) {
                $teacher = $s->teacher?->user;
                $teacherName = $teacher ? trim(($teacher->name ?? '')) : ($s->teacher?->specialization ?? 'N/A');

                return [
                    'horaire' => ($s->heure_debut?->format('H:i') ?? '?') . ' - ' . ($s->heure_fin?->format('H:i') ?? '?'),
                    'matiere' => $s->matiere?->name ?? $s->matiere?->nom ?? 'N/A',
                    'enseignant' => $teacherName,
                    'salle' => $s->salle ?? 'N/A',
                ];
            })->values()->toArray();

            $days[] = [
                'jour' => $jourNames[$jour] ?? "Jour {$jour}",
                'slots' => $slots,
            ];
        }

        $classe = $emploiTemps->classe;

        return [
            'results' => $days,
            'count' => $seances->count(),
            'classe' => $classe?->name ?? 'N/A',
            'filiere' => $classe?->filiere?->name ?? 'N/A',
            'semestre' => $emploiTemps->semestre ?? 'N/A',
            'annee' => $emploiTemps->annee?->name ?? 'N/A',
            'periode' => $emploiTemps->date_debut?->format('d/m/Y') . ' — ' . $emploiTemps->date_fin?->format('d/m/Y'),
            'display_type' => 'timetable',
            'deep_link' => Route::has('esbtp.emploi-temps.show')
                ? route('esbtp.emploi-temps.show', $emploiTemps->id) : null,
        ];
    }
}
