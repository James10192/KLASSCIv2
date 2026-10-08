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
        return 'Recherche les enseignants par nom, spécialisation ou statut et retourne leur teacher_id exact. '
            .'À utiliser avant proposer_profil_enseignant pour modifier la fiche RH/pédagogique (nom, titre, grade, spécialisation, régime, statut...) et avant proposer_professeur_classe_lmd pour changer le professeur officiel d’un ECUE dans une classe LMD. '
            .'Ne confonds pas les deux : modifier la fiche d’une personne ne réaffecte pas ses ECUE ; changer le professeur LMD ne réécrit pas son profil.';
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
                'teacher_id' => (int) $teacher->id,
                'user_id' => $teacher->user_id ? (int) $teacher->user_id : null,
                'nom' => $teacher->user?->name ?? 'N/A',
                'matricule' => $teacher->matricule ?? 'N/A',
                'titre_academique' => $teacher->title ?? 'N/A',
                'grade_academique' => $teacher->grade ?? 'N/A',
                'specialisation' => $teacher->specialization ?? 'N/A',
                'regime' => $teacher->regime ?? 'N/A',
                'charge_hebdomadaire' => (int) ($teacher->teaching_hours_due ?? 0),
                'statut' => $teacher->is_active ? 'Actif' : 'Inactif',
                'email' => $teacher->email ?? $teacher->user?->email ?? 'N/A',
                'telephone' => $teacher->phone ?? $teacher->user?->phone ?? 'N/A',
            ];
        })->toArray();

        return [
            'results' => $teachers,
            'count' => count($teachers),
            'total' => $total,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.enseignants.index') ? route('esbtp.enseignants.index') : null,
            'actions_suivantes' => [
                'modifier_fiche' => 'proposer_profil_enseignant avec teacher_id',
                'changer_professeur_lmd' => 'proposer_professeur_classe_lmd avec teacher_id + classe_id + matiere_id + annee_universitaire_id + semestre',
            ],
        ];
    }
}
