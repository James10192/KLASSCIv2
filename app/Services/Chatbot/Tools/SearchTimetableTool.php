<?php

namespace App\Services\Chatbot\Tools;

use App\Domain\EmploiTemps\JourDeLaSemaine;
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
        if (! $this->isAvailableFor($user)) {
            return $this->unavailableResponse();
        }

        $search = $args['classe'];

        $baseQuery = ESBTPEmploiTemps::query()
            ->with(['classe.filiere', 'seances.matiere', 'seances.teacher.user', 'annee']);
        $this->applyClasseSearch($baseQuery, $search);

        $emploiTemps = (clone $baseQuery)->where('is_current', true)->first();

        if (!$emploiTemps) {
            $emploiTemps = (clone $baseQuery)->orderByDesc('created_at')->first();
        }

        if (!$emploiTemps) {
            return [
                'results' => [],
                'count' => 0,
                'display_type' => 'text',
                'message' => "Aucun emploi du temps trouvé pour la classe \"{$search}\".",
            ];
        }

        $seances = $emploiTemps->seances->filter(fn ($s) => $s->is_active && $s->type === 'course');

        // Filtrer par jour si spécifié.
        //
        // Ce filtre ne rendait JAMAIS rien : sa table était à base zéro
        // (`lundi => 0`) et la comparaison stricte, alors que la colonne `jour`
        // porte soit l'entier à base un, soit le libellé « Lundi ». Ni `1 === 0`
        // ni `'Lundi' === 0` ne tiennent. Demander l'emploi du temps « du
        // mercredi » au chatbot rendait donc une journée vide.
        if (! empty($args['jour'])) {
            $rangDemande = JourDeLaSemaine::rang($args['jour']);
            if ($rangDemande !== null) {
                $seances = $seances->filter(
                    fn ($s) => JourDeLaSemaine::rang($s->jour) === $rangDemande
                );
            }
        }

        // Grouper par RANG et non par l'écriture brute : sans quoi le même
        // mercredi forme deux groupes selon l'écran qui a saisi la séance.
        // Un jour illisible va en fin de semaine plutôt que de disparaître.
        $grouped = $seances->groupBy(fn ($s) => JourDeLaSemaine::rang($s->jour) ?? 99)->sortKeys();

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
                'jour' => JourDeLaSemaine::libelle($jour + 1) ?? 'Jour inconnu',
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
