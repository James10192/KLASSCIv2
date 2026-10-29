<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use Illuminate\Support\Facades\Route;

class SearchStudentsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_students';
    }

    public function description(): string
    {
        return 'Rechercher des étudiants dans le système KLASSCI. Par défaut limité aux inscriptions validées (workflow_step=etudiant_cree). Retourne nom, matricule, classe, statut.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Nom ou prénom de l\'étudiant à rechercher',
                ],
                'matricule' => [
                    'type' => 'string',
                    'description' => 'Matricule de l\'étudiant',
                ],
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom de la classe (ex: "BTS Bâtiment 1ère année")',
                ],
                'filiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la filière (ex: "BTS Bâtiment", "Génie Civil")',
                ],
                'workflow_step' => [
                    'type' => 'string',
                    'description' => 'Filtre sur le workflow d\'inscription. Par défaut "etudiant_cree" (inscription validée, l\'étudiant est réellement inscrit). Utiliser "all" pour désactiver le filtre (inclure brouillons/pré-inscriptions).',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Statut de l\'inscription : "active", "inactive", "annule". Par défaut, pas de filtre.',
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
        if (! $this->isAvailableFor($user)) {
            return $this->unavailableResponse();
        }

        $query = ESBTPEtudiant::query()->with(['inscriptions' => fn($q) => $q->orderByDesc('date_inscription'), 'inscriptions.classe.filiere']);

        if ($user->can('identity.student')) {
            $query->where('user_id', $user->id);
        } elseif ($user->can('identity.teach')) {
            $activeAcademicYearId = ESBTPAnneeUniversitaire::query()
                ->where('is_current', true)
                ->value('id');

            if (! $activeAcademicYearId) {
                $query->whereRaw('1 = 0');
            } else {
                $scopeAssignedClasses = function ($classes) use ($user, $activeAcademicYearId) {
                    $classes->whereExists(function ($assignments) use ($user, $activeAcademicYearId) {
                        $assignments->selectRaw('1')
                            ->from('esbtp_planifications_academiques as planifications')
                            ->whereColumn('planifications.filiere_id', 'esbtp_classes.filiere_id')
                            ->whereColumn('planifications.niveau_etude_id', 'esbtp_classes.niveau_etude_id')
                            ->where('planifications.annee_universitaire_id', $activeAcademicYearId)
                            ->where('planifications.is_active', true)
                            ->whereNotNull('planifications.matiere_id')
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
                };

                $scopeAssignedInscriptions = function ($inscriptions) use ($activeAcademicYearId, $scopeAssignedClasses) {
                    $inscriptions->where('annee_universitaire_id', $activeAcademicYearId)
                        ->where('status', 'active')
                        ->whereHas('classe', $scopeAssignedClasses);
                };

                $query->whereHas('inscriptions', $scopeAssignedInscriptions)
                    ->with(['inscriptions' => fn ($q) => $scopeAssignedInscriptions($q->orderByDesc('date_inscription')), 'inscriptions.classe.filiere']);
            }
        } elseif (! $user->can('students.view')) {
            return $this->unavailableResponse();
        }

        if (!empty($args['name'])) {
            $this->applyFuzzyNameSearch($query, $args['name']);
        }

        if (!empty($args['matricule'])) {
            $query->where('matricule', 'like', "%{$args['matricule']}%");
        }

        if (!empty($args['classe'])) {
            $classeName = $args['classe'];
            $query->whereHas('inscriptions.classe', function ($q) use ($classeName) {
                $q->where('name', 'like', "%{$classeName}%");
            });
        }

        if (!empty($args['filiere'])) {
            $filiereName = $args['filiere'];
            $query->whereHas('inscriptions.classe.filiere', function ($q) use ($filiereName) {
                $q->where('name', 'like', "%{$filiereName}%");
            });
        }

        $workflowStep = $args['workflow_step'] ?? 'etudiant_cree';
        if ($workflowStep !== 'all' && $workflowStep !== '') {
            $query->whereHas('inscriptions', function ($q) use ($workflowStep) {
                $q->where('workflow_step', $workflowStep);
            });
        }

        if (!empty($args['status'])) {
            $status = $args['status'];
            $query->whereHas('inscriptions', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $limit = $this->clampLimit($args);
        $total = (clone $query)->count();
        $results = $query->latest()->limit($limit)->get();

        $students = $results->map(function ($etudiant) {
            $inscription = $etudiant->inscriptions->first();
            $nom = $this->studentFullName($etudiant);
            $initials = $this->studentInitials($etudiant);
            return [
                'id' => $etudiant->id,
                'nom' => $nom,
                'initials' => $initials,
                'matricule' => $etudiant->matricule ?? 'N/A',
                'classe' => $inscription?->classe?->name ?? 'Non inscrit',
                'filiere' => $inscription?->classe?->filiere?->name ?? 'N/A',
                'statut' => ucfirst(str_replace('_', ' ', $inscription?->status ?? 'N/A')),
                'lien' => Route::has('esbtp.etudiants.show') ? route('esbtp.etudiants.show', $etudiant->id) : null,
                'lien_label' => 'Fiche',
                'lien_icon' => 'fas fa-user',
            ];
        })->toArray();

        return [
            'results' => $students,
            'count' => count($students),
            'total' => $total,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.etudiants.index') ? route('esbtp.etudiants.index') : null,
        ];
    }
}
