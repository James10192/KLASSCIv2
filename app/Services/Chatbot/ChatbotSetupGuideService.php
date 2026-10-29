<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ChatbotSetupGuideService
{
    public function buildGuide(User $user, ?string $scope = null): array
    {
        $scope = $scope ?: 'global';

        $currentYear = DB::table('esbtp_annee_universitaires')
            ->where('is_current', true)
            ->whereNull('deleted_at')
            ->first();

        $currentYearId = $currentYear?->id;

        $stats = [
            'annee_universitaire' => $currentYearId !== null,
            'filieres' => $this->countTable('esbtp_filieres') > 0,
            'niveaux' => $this->countTable('esbtp_niveau_etudes') > 0,
            'classes' => $this->countClasses($currentYearId) > 0,
            'matieres' => $this->countTable('esbtp_matieres') > 0,
            'enseignants' => $this->countTable('esbtp_teachers') > 0,
            'disponibilites' => $this->countAvailabilities() > 0,
            'planning_general' => $this->countPlanifications($currentYearId) > 0,
            'emploi_temps' => $this->countEmploiTemps($currentYearId) > 0,
            'frais_categories' => $this->countMandatoryCategories() > 0,
            'frais_mandatory_configs' => $this->countMandatoryConfigurations() > 0,
            'frais_optional_options' => $this->countOptionalOptions() > 0,
            'inscriptions' => $this->countInscriptions($currentYearId) > 0,
            'paiements' => $this->countPaiements($currentYearId) > 0,
            'evaluations' => $this->countEvaluations($currentYearId) > 0,
            'notes' => $this->countTable('esbtp_notes') > 0,
            'absences' => $this->countTable('esbtp_attendances') > 0,
            'bulletins' => $this->countTable('esbtp_bulletins') > 0,
        ];

        $sections = [];

        if (in_array($scope, ['global', 'academique'], true)) {
            $sections[] = $this->buildAcademicSection($stats);
        }

        if (in_array($scope, ['global', 'financier'], true)) {
            $sections[] = $this->buildFinanceSection($stats);
        }

        if (in_array($scope, ['global', 'pedagogie'], true)) {
            $sections[] = $this->buildPedagogySection($stats);
        }

        $summary = $this->buildSummary($sections);

        return [
            'title' => 'Guide de mise en route KLASSCI',
            'summary' => $summary,
            'sections' => $sections,
        ];
    }

    public function buildGuidePreview(User $user, ?string $scope = null, int $stepLimit = 3): array
    {
        $guide = $this->buildGuide($user, $scope);

        $sections = $this->filterSectionsByScope($guide['sections'], $scope);
        $section = $this->selectActiveSection($sections);

        if (!$section) {
            return $guide;
        }

        $section['steps'] = $this->limitSteps($section['steps'], $stepLimit);

        return [
            'title' => $guide['title'],
            'summary' => $guide['summary'],
            'sections' => [$section],
            'is_preview' => true,
            'full_available' => true,
        ];
    }

    public function getStepContext(User $user, string $scope, string $stepId): array
    {
        $guide = $this->buildGuide($user, $scope);
        $sections = $this->filterSectionsByScope($guide['sections'], $scope);

        foreach ($sections as $section) {
            foreach ($section['steps'] as $step) {
                if ($step['id'] === $stepId) {
                    $missing = $this->missingPrerequisites($section['steps'], $step);

                    return [
                        'step' => $step,
                        'missing_prerequisites' => $missing,
                        'missing_prerequisite_ids' => array_values(array_map(fn ($item) => $item['id'], $missing)),
                        'missing_prerequisite_titles' => array_values(array_map(fn ($item) => $item['title'], $missing)),
                        'section' => $section['title'] ?? null,
                        'deep_link' => $step['deep_link'] ?? null,
                    ];
                }
            }
        }

        return [
            'step' => null,
            'missing_prerequisites' => [],
            'missing_prerequisite_ids' => [],
            'missing_prerequisite_titles' => [],
            'section' => null,
            'deep_link' => null,
        ];
    }

    public function buildMissingStepsPreview(User $user, string $scope, array $missingIds, int $limit = 3): ?array
    {
        if (empty($missingIds)) {
            return null;
        }

        $guide = $this->buildGuide($user, $scope);
        $sections = $this->filterSectionsByScope($guide['sections'], $scope);

        foreach ($sections as $section) {
            $filtered = array_values(array_filter($section['steps'], function ($step) use ($missingIds) {
                return in_array($step['id'], $missingIds, true);
            }));

            if (empty($filtered)) {
                continue;
            }

            $section['steps'] = array_slice($filtered, 0, $limit);

            return [
                'title' => 'Étapes à faire avant',
                'summary' => sprintf('%d étape(s) à compléter', count($filtered)),
                'sections' => [$section],
                'is_preview' => true,
                'full_available' => true,
            ];
        }

        return null;
    }

    protected function buildAcademicSection(array $stats): array
    {
        $emploiTempsDescription = 'Créer les emplois du temps à partir du planning.';
        if (empty($stats['inscriptions'])) {
            $emploiTempsDescription .= ' Tu peux le faire meme sans inscriptions; si une classe n\'a pas d\'etudiants inscrits (annee courante), c\'est normal.';
        }

        $steps = [
            $this->step('annee_universitaire', 'Définir l\'année universitaire',
                'Créer l\'année courante et l\'activer (is_current=true).',
                $stats['annee_universitaire'], $this->routeUrl('esbtp.annees-universitaires.index')),
            $this->step('filieres', 'Créer les filières',
                'Configurer les filières (BTS, Licence, etc.).',
                $stats['filieres'], $this->routeUrl('esbtp.filieres.index'), ['annee_universitaire']),
            $this->step('niveaux', 'Créer les niveaux',
                'Définir Première/Deuxième Année, L3, etc.',
                $stats['niveaux'], $this->routeUrl('esbtp.niveaux-etudes.index'), ['filieres']),
            $this->step('classes', 'Créer les classes',
                'Associer filière + niveau + capacité + année.',
                $stats['classes'], $this->routeUrl('esbtp.classes.index'), ['niveaux']),
            $this->step('matieres', 'Créer les matières',
                'Définir les matières par combinaison filière + niveau.',
                $stats['matieres'], $this->routeUrl('esbtp.matieres.index'), ['classes']),
            $this->step('enseignants', 'Créer les enseignants',
                'Ajouter les enseignants et leurs spécialités.',
                $stats['enseignants'], $this->routeUrl('esbtp.enseignants.index'), ['matieres']),
            $this->step('disponibilites', 'Renseigner les disponibilités',
                'Définir les créneaux disponibles pour chaque enseignant.',
                $stats['disponibilites'], $this->routeUrl('esbtp.enseignants.index'), ['enseignants']),
            $this->step('planning_general', 'Configurer le planning général',
                'Configurer volumes horaires et assigner les enseignants.',
                $stats['planning_general'], $this->routeUrl('esbtp.planning-general.index'), ['disponibilites']),
            $this->step('emploi_temps', 'Générer l\'emploi du temps',
                $emploiTempsDescription,
                $stats['emploi_temps'], $this->routeUrl('esbtp.emploi-temps.index'), ['planning_general']),
        ];

        return $this->finalizeSection(
            'Phase 1 - Configuration académique',
            'Objectif : structurer l\'établissement jusqu\'à l\'emploi du temps.',
            $steps
        );
    }

    protected function buildFinanceSection(array $stats): array
    {
        $steps = [
            $this->step('frais_categories', 'Créer les catégories de frais obligatoires',
                'Menu Comptabilité > Gestion des frais : créer au moins une catégorie obligatoire.',
                $stats['frais_categories'], $this->routeUrl('esbtp.frais.index'), ['annee_universitaire']),
            $this->step('frais_mandatory_configs', 'Configurer les frais obligatoires',
                'Dans Gestion des frais, cliquer sur Configuration par classe pour définir les montants.',
                $stats['frais_mandatory_configs'], $this->routeUrl('esbtp.frais.configure'), ['frais_categories', 'classes']),
            $this->step('frais_optional_options', 'Créer les options de frais',
                'Options/variants pour les catégories optionnelles.',
                $stats['frais_optional_options'], $this->routeUrl('esbtp.frais.optional-config'), ['frais_categories']),
            $this->step('inscriptions', 'Créer les inscriptions',
                'Inscrire les étudiants dans l\'année courante.',
                $stats['inscriptions'], $this->routeUrl('esbtp.inscriptions.create'), ['classes', 'emploi_temps', 'frais_mandatory_configs']),
            $this->step('paiements', 'Enregistrer les paiements',
                'Enregistrer les paiements après inscription.',
                $stats['paiements'], $this->routeUrl('esbtp.paiements.index'), ['inscriptions']),
        ];

        return $this->finalizeSection(
            'Phase 2 - Frais & inscriptions',
            'Objectif : préparer les frais puis inscrire les étudiants de l\'année en cours.',
            $steps
        );
    }

    protected function buildPedagogySection(array $stats): array
    {
        $steps = [
            $this->step('evaluations', 'Programmer les évaluations',
                'Créer les évaluations pour les classes inscrites.',
                $stats['evaluations'], $this->routeUrl('esbtp.evaluations.index'), ['inscriptions']),
            $this->step('notes', 'Saisir les notes',
                'Ajouter les notes après les évaluations.',
                $stats['notes'], $this->routeUrl('esbtp.notes.index'), ['evaluations']),
            $this->step('absences', 'Enregistrer les absences',
                'Noter les absences une fois les cours planifiés.',
                $stats['absences'], $this->routeUrl('esbtp.attendances.index'), ['emploi_temps', 'inscriptions']),
            $this->step('bulletins', 'Générer les bulletins',
                'Générer après notes et absences.',
                $stats['bulletins'], $this->routeUrl('esbtp.bulletins.index'), ['notes', 'absences']),
        ];

        return $this->finalizeSection(
            'Phase 3 - Évaluations & suivi',
            'Objectif : suivre les notes/absences une fois les inscriptions actives.',
            $steps
        );
    }

    protected function finalizeSection(string $title, string $description, array $steps): array
    {
        $steps = $this->resolveStatuses($steps);

        $doneCount = count(array_filter($steps, fn ($step) => $step['status'] === 'done'));
        $totalCount = count($steps);

        return [
            'title' => $title,
            'description' => $description,
            'progress' => sprintf('%d/%d complétées', $doneCount, $totalCount),
            'steps' => $steps,
        ];
    }

    protected function resolveStatuses(array $steps): array
    {
        $doneMap = [];
        foreach ($steps as $step) {
            $doneMap[$step['id']] = $step['done'];
        }

        $nextAssigned = false;

        return array_map(function ($step) use ($doneMap, &$nextAssigned) {
            if ($step['done']) {
                $step['status'] = 'done';
                return $step;
            }

            $requires = $step['requires'] ?? [];
            $canStart = true;
            foreach ($requires as $requiredId) {
                if (!($doneMap[$requiredId] ?? false)) {
                    $canStart = false;
                    break;
                }
            }

            if (!$canStart) {
                $step['status'] = 'blocked';
                return $step;
            }

            if (!$nextAssigned) {
                $step['status'] = 'next';
                $nextAssigned = true;
                return $step;
            }

            $step['status'] = 'todo';
            return $step;
        }, $steps);
    }

    protected function step(
        string $id,
        string $title,
        string $description,
        bool $done,
        ?string $deepLink,
        array $requires = []
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'done' => $done,
            'status' => 'todo',
            'deep_link' => $deepLink,
            'action_label' => $deepLink ? 'Ouvrir' : null,
            'requires' => $requires,
        ];
    }

    protected function buildSummary(array $sections): string
    {
        $total = 0;
        $done = 0;

        foreach ($sections as $section) {
            foreach ($section['steps'] as $step) {
                $total += 1;
                if ($step['status'] === 'done') {
                    $done += 1;
                }
            }
        }

        if ($total === 0) {
            return 'Aucune étape disponible pour ce guide.';
        }

        return sprintf('%d/%d étapes complétées', $done, $total);
    }

    protected function filterSectionsByScope(array $sections, ?string $scope): array
    {
        if (!$scope || $scope === 'global') {
            return $sections;
        }

        $map = [
            'academique' => 'Phase 1',
            'financier' => 'Phase 2',
            'pedagogie' => 'Phase 3',
        ];

        $needle = $map[$scope] ?? null;
        if (!$needle) {
            return $sections;
        }

        return array_values(array_filter($sections, function ($section) use ($needle) {
            return isset($section['title']) && str_contains($section['title'], $needle);
        }));
    }

    protected function selectActiveSection(array $sections): ?array
    {
        foreach ($sections as $section) {
            foreach ($section['steps'] as $step) {
                if (in_array($step['status'], ['next', 'todo', 'blocked'], true)) {
                    return $section;
                }
            }
        }

        return $sections[0] ?? null;
    }

    protected function limitSteps(array $steps, int $limit): array
    {
        $priority = array_values(array_filter($steps, function ($step) {
            return in_array($step['status'], ['next', 'todo', 'blocked'], true);
        }));

        if (count($priority) === 0) {
            return array_slice($steps, 0, $limit);
        }

        return array_slice($priority, 0, $limit);
    }

    protected function missingPrerequisites(array $steps, array $targetStep): array
    {
        $stepMap = collect($steps)->keyBy('id');
        $missingIds = [];
        $visited = [];

        $collectMissing = function (string $stepId) use (&$collectMissing, $stepMap, &$missingIds, &$visited): void {
            if (isset($visited[$stepId])) {
                return;
            }
            $visited[$stepId] = true;

            $step = $stepMap->get($stepId);
            if (!$step || ($step['done'] ?? false)) {
                return;
            }

            $missingIds[$stepId] = true;

            foreach ($step['requires'] ?? [] as $requiredId) {
                $collectMissing($requiredId);
            }
        };

        foreach ($targetStep['requires'] ?? [] as $requiredId) {
            $collectMissing($requiredId);
        }

        $missing = [];
        foreach ($steps as $step) {
            if (!empty($missingIds[$step['id']])) {
                $missing[] = [
                    'id' => $step['id'],
                    'title' => $step['title'] ?? $step['id'],
                    'deep_link' => $step['deep_link'] ?? null,
                ];
            }
        }

        return $missing;
    }

    protected function routeUrl(string $routeName, array $parameters = []): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return route($routeName, $parameters);
    }

    protected function countTable(string $table): int
    {
        $query = DB::table($table);
        $this->applySoftDeleteFilter($query, $table);

        return $query->count();
    }

    protected function countClasses(?int $currentYearId): int
    {
        $query = DB::table('esbtp_classes');
        $this->applySoftDeleteFilter($query, 'esbtp_classes');

        if ($currentYearId) {
            $query->where('annee_universitaire_id', $currentYearId);
        }

        return $query->count();
    }

    protected function countPlanifications(?int $currentYearId): int
    {
        $query = DB::table('esbtp_planifications_academiques');
        $this->applySoftDeleteFilter($query, 'esbtp_planifications_academiques');

        if ($currentYearId) {
            $query->where('annee_universitaire_id', $currentYearId);
        }

        return $query->count();
    }

    protected function countEmploiTemps(?int $currentYearId): int
    {
        $query = DB::table('esbtp_emploi_temps');
        $this->applySoftDeleteFilter($query, 'esbtp_emploi_temps');

        if ($currentYearId) {
            $query->where('annee_universitaire_id', $currentYearId);
        }

        return $query->count();
    }

    protected function countAvailabilities(): int
    {
        $teacherAvailabilityQuery = DB::table('esbtp_teacher_availabilities');
        $this->applySoftDeleteFilter($teacherAvailabilityQuery, 'esbtp_teacher_availabilities');
        $teacherAvailability = $teacherAvailabilityQuery->count();

        if ($teacherAvailability > 0) {
            return $teacherAvailability;
        }

        $legacyQuery = DB::table('esbtp_enseignant_disponibilites');
        $this->applySoftDeleteFilter($legacyQuery, 'esbtp_enseignant_disponibilites');

        return $legacyQuery->count();
    }

    protected function countMandatoryCategories(): int
    {
        $query = DB::table('esbtp_frais_categories')
            ->where('is_mandatory', true);
        $this->applySoftDeleteFilter($query, 'esbtp_frais_categories');

        return $query->count();
    }

    protected function countMandatoryConfigurations(): int
    {
        return DB::table('esbtp_frais_configurations')
            ->join('esbtp_frais_categories', 'esbtp_frais_configurations.frais_category_id', '=', 'esbtp_frais_categories.id')
            ->where('esbtp_frais_categories.is_mandatory', true)
            ->when(Schema::hasColumn('esbtp_frais_configurations', 'deleted_at'), function ($query) {
                $query->whereNull('esbtp_frais_configurations.deleted_at');
            })
            ->count();
    }

    protected function countOptionalOptions(): int
    {
        return DB::table('esbtp_frais_options')
            ->join('esbtp_frais_categories', 'esbtp_frais_options.frais_category_id', '=', 'esbtp_frais_categories.id')
            ->where('esbtp_frais_categories.is_mandatory', false)
            ->when(Schema::hasColumn('esbtp_frais_options', 'deleted_at'), function ($query) {
                $query->whereNull('esbtp_frais_options.deleted_at');
            })
            ->count();
    }

    protected function countInscriptions(?int $currentYearId): int
    {
        $query = DB::table('esbtp_inscriptions');
        $this->applySoftDeleteFilter($query, 'esbtp_inscriptions');

        if ($currentYearId) {
            $query->where('annee_universitaire_id', $currentYearId);
        }

        return $query->count();
    }

    protected function countPaiements(?int $currentYearId): int
    {
        $query = DB::table('esbtp_paiements');
        $this->applySoftDeleteFilter($query, 'esbtp_paiements');

        if ($currentYearId) {
            $query->join('esbtp_inscriptions', 'esbtp_paiements.inscription_id', '=', 'esbtp_inscriptions.id')
                ->where('esbtp_inscriptions.annee_universitaire_id', $currentYearId)
                ->when(Schema::hasColumn('esbtp_inscriptions', 'deleted_at'), function ($query) {
                    $query->whereNull('esbtp_inscriptions.deleted_at');
                });
        }

        return $query->count();
    }

    protected function countEvaluations(?int $currentYearId): int
    {
        $query = DB::table('esbtp_evaluations');
        $this->applySoftDeleteFilter($query, 'esbtp_evaluations');

        if ($currentYearId) {
            $query->where('annee_universitaire_id', $currentYearId);
        }

        return $query->count();
    }

    protected function applySoftDeleteFilter($query, string $table): void
    {
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }
    }
}
