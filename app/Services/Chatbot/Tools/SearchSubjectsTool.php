<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPClasse;
use Illuminate\Support\Facades\Route;

class SearchSubjectsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_subjects';
    }

    public function description(): string
    {
        return 'Rechercher les matières enseignées dans une classe, avec coefficient, volume horaire et enseignant assigné.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom ou code de la classe (ex: "B3 COM")',
                ],
                'matiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la matière à chercher (ex: "mathématiques", "anglais")',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        if (!empty($args['classe'])) {
            return $this->searchByClasse($args);
        }

        if (!empty($args['matiere'])) {
            return $this->searchByMatiere($args);
        }

        return [
            'results' => [],
            'count' => 0,
            'display_type' => 'text',
            'message' => 'Précisez une classe ou une matière à rechercher.',
        ];
    }

    private function searchByClasse(array $args): array
    {
        $search = $args['classe'];

        $classeQuery = ESBTPClasse::query()
            ->with(['filiere', 'niveauEtude', 'matieres.enseignants'])
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        $classe = $classeQuery->first();

        if (!$classe) {
            return [
                'results' => [],
                'count' => 0,
                'display_type' => 'text',
                'message' => "Aucune classe trouvée pour \"{$search}\".",
            ];
        }

        $matieres = $classe->matieres->where('is_active', true);

        // Filtrer par matière si spécifié
        if (!empty($args['matiere'])) {
            $mSearch = mb_strtolower($args['matiere']);
            $matieres = $matieres->filter(function ($m) use ($mSearch) {
                return str_contains(mb_strtolower($m->name ?? $m->nom ?? ''), $mSearch);
            });
        }

        $results = $matieres->map(function ($m) use ($classe) {
            $pivot = $m->pivot;
            $coef = $pivot?->coefficient ?? $m->coefficient ?? 'N/A';
            $heures = $pivot?->total_heures ?? ($m->heures_cm + $m->heures_td + $m->heures_tp) ?: 'N/A';

            // Trouver l'enseignant assigné
            $enseignant = $m->enseignants->first();
            $enseignantName = $enseignant ? $enseignant->name : 'Non assigné';

            return [
                'matiere' => $m->name ?? $m->nom ?? 'N/A',
                'code' => $m->code ?? '',
                'coefficient' => $coef,
                'volume_horaire' => is_numeric($heures) ? $heures . 'h' : $heures,
                'enseignant' => $enseignantName,
                'type' => ucfirst($m->type_formation ?? 'general'),
            ];
        })->sortBy('matiere')->values()->toArray();

        return [
            'results' => $results,
            'count' => count($results),
            'classe' => $classe->name,
            'filiere' => $classe->filiere?->name ?? 'N/A',
            'niveau' => $classe->niveauEtude?->name ?? 'N/A',
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.classes.show') ? route('esbtp.classes.show', $classe->id) : null,
        ];
    }

    private function searchByMatiere(array $args): array
    {
        $search = $args['matiere'];

        $matieres = \App\Models\ESBTPMatiere::query()
            ->with(['classes.filiere', 'enseignants'])
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get();

        $results = $matieres->map(function ($m) {
            $classes = $m->classes->where('is_active', true)->pluck('name')->implode(', ');
            $enseignant = $m->enseignants->first();

            return [
                'matiere' => $m->name ?? $m->nom ?? 'N/A',
                'code' => $m->code ?? '',
                'coefficient' => $m->coefficient ?? 'N/A',
                'volume_horaire' => ($m->heures_cm + $m->heures_td + $m->heures_tp) ? ($m->heures_cm + $m->heures_td + $m->heures_tp) . 'h' : 'N/A',
                'classes' => $classes ?: 'Aucune',
                'enseignant' => $enseignant ? $enseignant->name : 'Non assigné',
            ];
        })->toArray();

        return [
            'results' => $results,
            'count' => count($results),
            'display_type' => 'table',
            'deep_link' => null,
        ];
    }
}
