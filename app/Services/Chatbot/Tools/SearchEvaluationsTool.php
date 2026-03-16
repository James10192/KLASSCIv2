<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPEvaluation;
use Illuminate\Support\Facades\Route;

class SearchEvaluationsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_evaluations';
    }

    public function description(): string
    {
        return 'Rechercher les évaluations (devoirs, examens, contrôles continus) par classe, matière, type ou statut. Retourne titre, type, date, coefficient, statut.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom de la classe (ex: "B3 COM", "BTS Bâtiment")',
                ],
                'matiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la matière (ex: "Mathématiques", "Français")',
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Type d\'évaluation: "devoir", "examen", "controle_continu", "rattrapage"',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Statut: "planned", "in_progress", "completed", "cancelled"',
                ],
                'periode' => [
                    'type' => 'string',
                    'description' => 'Période: "semestre1" ou "semestre2"',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 10, max: 25)',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $query = ESBTPEvaluation::query()->with(['matiere', 'classe', 'enseignant.user']);

        if (!empty($args['classe'])) {
            $classe = $args['classe'];
            $query->whereHas('classe', fn($q) => $q->where('name', 'like', "%{$classe}%"));
        }

        if (!empty($args['matiere'])) {
            $matiere = $args['matiere'];
            $query->whereHas('matiere', fn($q) => $q->where('name', 'like', "%{$matiere}%"));
        }

        if (!empty($args['type'])) {
            $query->where('type', $args['type']);
        }

        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        if (!empty($args['periode'])) {
            $query->where('periode', $args['periode']);
        }

        $limit = min(max((int) ($args['limit'] ?? 10), 1), 25);
        $total = (clone $query)->count();
        $results = $query->orderByDesc('date_evaluation')->limit($limit)->get();

        $evaluations = $results->map(function ($eval) {
            return [
                'id' => $eval->id,
                'titre' => $eval->titre,
                'classe' => $eval->classe?->name ?? 'N/A',
                'matiere' => $eval->matiere?->name ?? 'N/A',
                'type' => ucfirst(str_replace('_', ' ', $eval->type ?? 'N/A')),
                'date' => $eval->date_evaluation?->format('d/m/Y') ?? 'N/A',
                'coefficient' => $eval->coefficient ?? 1,
                'bareme' => $eval->bareme ?? 20,
                'statut' => ucfirst($eval->status ?? 'N/A'),
                'publiee' => $eval->is_published ? 'Oui' : 'Non',
                'enseignant' => $eval->enseignant?->user?->name ?? $eval->enseignant_externe_nom ?? 'N/A',
                'nb_notes' => $eval->notes()->count(),
                'lien' => Route::has('esbtp.evaluations.show') ? route('esbtp.evaluations.show', $eval->id) : null,
            ];
        })->toArray();

        return [
            'results' => $evaluations,
            'count' => count($evaluations),
            'total' => $total,
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.evaluations.index') ? route('esbtp.evaluations.index') : null,
        ];
    }
}
