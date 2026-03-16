<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPTeacher;
use Illuminate\Support\Facades\Route;

class SearchTeachersTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_teachers';
    }

    public function description(): string
    {
        return 'Rechercher les enseignants par nom, spécialisation ou statut. Retourne nom, matricule, spécialisation, statut et nombre d\'heures.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Nom de l\'enseignant',
                ],
                'specialization' => [
                    'type' => 'string',
                    'description' => 'Spécialisation ou matière enseignée',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Statut: "active", "inactive"',
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
        $query = ESBTPTeacher::query()->with(['user']);

        if (!empty($args['name'])) {
            $name = $args['name'];
            $query->where(function ($q) use ($name) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$name}%"))
                  ->orWhere('specialization', 'like', "%{$name}%");
            });
        }

        if (!empty($args['specialization'])) {
            $query->where('specialization', 'like', "%{$args['specialization']}%");
        }

        if (!empty($args['status'])) {
            $query->where('is_active', $args['status'] === 'active');
        }

        $limit = min(max((int) ($args['limit'] ?? 10), 1), 25);
        $total = (clone $query)->count();
        $results = $query->latest()->limit($limit)->get();

        $teachers = $results->map(function ($teacher) {
            return [
                'id' => $teacher->id,
                'nom' => $teacher->user?->name ?? 'N/A',
                'matricule' => $teacher->matricule ?? 'N/A',
                'specialisation' => $teacher->specialization ?? 'N/A',
                'grade' => $teacher->grade ?? 'N/A',
                'heures_prevues' => $teacher->teaching_hours_due ?? 0,
                'statut' => $teacher->is_active ? 'Actif' : 'Inactif',
                'email' => $teacher->email ?? $teacher->user?->email ?? 'N/A',
                'telephone' => $teacher->phone ?? 'N/A',
            ];
        })->toArray();

        return [
            'results' => $teachers,
            'count' => count($teachers),
            'total' => $total,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.enseignants.index') ? route('esbtp.enseignants.index') : null,
        ];
    }
}
