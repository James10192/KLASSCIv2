<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPNote;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Facades\Route;

class SearchNotesTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_notes';
    }

    public function description(): string
    {
        return 'Rechercher les notes/grades des étudiants par étudiant, classe, matière ou évaluation. Retourne note, moyenne, rang, appréciation.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'student_name' => [
                    'type' => 'string',
                    'description' => 'Nom ou prénom de l\'étudiant',
                ],
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom de la classe',
                ],
                'matiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la matière',
                ],
                'semestre' => [
                    'type' => 'string',
                    'description' => 'Semestre: "semestre1" ou "semestre2"',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 15, max: 25)',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        if (! $this->isAvailableFor($user)) {
            return $this->unavailableResponse();
        }

        $query = ESBTPNote::query()->with(['evaluation', 'etudiant', 'matiere', 'classe']);

        if ($user->can('identity.student')) {
            $query->whereHas('etudiant', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($user->can('identity.teach')) {
            $activeAcademicYearId = ESBTPAnneeUniversitaire::query()
                ->where('is_current', true)
                ->value('id');

            if (! $activeAcademicYearId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereExists(function ($assignments) use ($user, $activeAcademicYearId) {
                    $assignments->selectRaw('1')
                        ->from('esbtp_evaluations as evaluations')
                        ->join('esbtp_classes as classes', 'classes.id', '=', 'evaluations.classe_id')
                        ->join('esbtp_planifications_academiques as planifications', function ($join) {
                            $join->on('planifications.filiere_id', '=', 'classes.filiere_id')
                                ->on('planifications.niveau_etude_id', '=', 'classes.niveau_etude_id')
                                ->on('planifications.matiere_id', '=', 'evaluations.matiere_id');
                        })
                        ->whereColumn('evaluations.id', 'esbtp_notes.evaluation_id')
                        ->whereColumn('evaluations.classe_id', 'esbtp_notes.classe_id')
                        ->whereColumn('evaluations.matiere_id', 'esbtp_notes.matiere_id')
                        ->where('evaluations.annee_universitaire_id', $activeAcademicYearId)
                        ->where('planifications.annee_universitaire_id', $activeAcademicYearId)
                        ->where('planifications.is_active', true)
                        ->where(function ($teacherScope) use ($user) {
                            $teacherScope->whereExists(function ($teacherAssignments) use ($user) {
                                $teacherAssignments->selectRaw('1')
                                    ->from('esbtp_planification_teachers as planification_teachers')
                                    ->join('esbtp_teachers as teachers', 'teachers.id', '=', 'planification_teachers.teacher_id')
                                    ->whereColumn('planification_teachers.planification_id', 'planifications.id')
                                    ->where('teachers.user_id', $user->id);
                            })->orWhere('planifications.enseignant_principal_id', $user->id);
                        });
                });
            }
        } elseif (! $user->can('notes.view')) {
            return $this->unavailableResponse();
        }

        if (!empty($args['student_name'])) {
            $tool = $this;
            $query->whereHas('etudiant', function ($q) use ($args, $tool) {
                $tool->applyFuzzyNameSearch($q, $args['student_name']);
            });
        }

        if (!empty($args['classe'])) {
            $classe = $args['classe'];
            $query->whereHas('classe', fn($q) => $q->where('name', 'like', "%{$classe}%"));
        }

        if (!empty($args['matiere'])) {
            $matiere = $args['matiere'];
            $query->whereHas('matiere', fn($q) => $q->where('name', 'like', "%{$matiere}%"));
        }

        if (!empty($args['semestre'])) {
            $query->where('semestre', $args['semestre']);
        }

        $limit = min(max((int) ($args['limit'] ?? 15), 1), 25);
        $total = (clone $query)->count();
        $results = $query->orderByDesc('created_at')->limit($limit)->get();

        $notes = $results->map(function ($note) {
            $etudiant = $note->etudiant;
            return [
                'etudiant' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : 'Inconnu',
                'matiere' => $note->matiere?->name ?? 'N/A',
                'classe' => $note->classe?->name ?? 'N/A',
                'evaluation' => $note->evaluation?->titre ?? 'N/A',
                'note' => $note->note . '/' . ($note->evaluation?->bareme ?? 20),
                'type' => ucfirst(str_replace('_', ' ', $note->type_evaluation ?? 'N/A')),
                'semestre' => $note->semestre === 'semestre1' ? 'S1' : 'S2',
                'absent' => $note->is_absent ? 'Oui' : 'Non',
            ];
        })->toArray();

        return [
            'results' => $notes,
            'count' => count($notes),
            'total' => $total,
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.notes.index') ? route('esbtp.notes.index') : null,
        ];
    }
}
