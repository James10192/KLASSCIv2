<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPOptionAssignment;
use Illuminate\Support\Collection;

class FraisConfigurationPageBuilder
{
    public function __construct(
        private readonly FraisScopeResolver $scopeResolver,
        private readonly FraisCacheService $fraisCacheService,
    ) {
    }

    public function build(string $mode = 'global', ?int $anneeId = null): array
    {
        $categories = ESBTPFraisCategory::active()->ordered()->get();
        $classes = ESBTPClasse::query()
            ->with(['filiere', 'niveau', 'parcours.mention.domaine'])
            ->withCount(['inscriptions as active_inscriptions_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->where('is_active', true)
            ->get();
        $btsFilieres = ESBTPFiliere::query()
            ->active()
            ->orderBy('name')
            ->get();
        $lmdParcours = ESBTPLMDParcours::query()
            ->with(['filiere', 'mention.domaine'])
            ->where('is_active', true)
            ->get();
        $lmdNiveaux = ESBTPNiveauEtude::query()
            ->active()
            ->whereIn('type', \App\Models\ESBTPNiveauEtude::CYCLES_LMD)
            ->orderBy('year')
            ->orderBy('name')
            ->get();
        $btsNiveaux = ESBTPNiveauEtude::query()
            ->active()
            ->whereNotIn('type', \App\Models\ESBTPNiveauEtude::CYCLES_LMD)
            ->orderBy('year')
            ->orderBy('name')
            ->get();
        $configurations = $this->preloadConfigurations($anneeId);
        $optionalAssignmentCounts = $this->preloadOptionalAssignmentCounts(
            $classes,
            $lmdParcours,
            $lmdNiveaux,
            $btsFilieres,
            $btsNiveaux,
        );

        return [
            'btsClasses' => $this->buildBtsCards(
                $btsFilieres,
                $btsNiveaux,
                $classes->where('systeme_academique', '!=', 'LMD')->values(),
                $categories,
                $mode,
                $anneeId,
                $configurations,
                $optionalAssignmentCounts,
            ),
            'lmdClasses' => $this->buildLmdCards(
                $lmdParcours,
                $lmdNiveaux,
                $classes->where('systeme_academique', 'LMD')->values(),
                $categories,
                $mode,
                $anneeId,
                $configurations,
                $optionalAssignmentCounts,
            ),
            'btsLevels' => $this->buildLevelCards(FraisScopeResolver::SYSTEME_BTS, $btsNiveaux, $btsFilieres->count()),
            'lmdLevels' => $this->buildLevelCards(FraisScopeResolver::SYSTEME_LMD, $lmdNiveaux, $lmdParcours->count()),
        ];
    }

    /**
     * Cartes BTS, construites par filiere x niveau et non a partir des classes.
     *
     * Les configurations de frais sont stockees par (filiere, niveau) : les
     * batir depuis les classes obligeait a ouvrir une classe avant que la
     * comptabilite puisse travailler, alors que les montants d'une rentree se
     * decident avant. Le LMD procedait deja ainsi par parcours x niveau ; le
     * BTS suit desormais la meme regle.
     *
     * Les classes ne servent plus qu'a compter l'effectif concerne.
     */
    private function buildBtsCards(
        Collection $filieres,
        Collection $niveaux,
        Collection $classes,
        Collection $categories,
        string $mode,
        ?int $anneeId,
        array $configurations,
        array $optionalAssignmentCounts,
    ): Collection {
        return $filieres->flatMap(function (ESBTPFiliere $filiere) use ($niveaux, $classes, $categories, $mode, $anneeId, $configurations, $optionalAssignmentCounts) {
            return $niveaux->map(function (ESBTPNiveauEtude $niveau) use ($filiere, $classes, $categories, $mode, $anneeId, $configurations, $optionalAssignmentCounts) {
                $classesDuScope = $classes->filter(
                    fn (ESBTPClasse $classe) => (int) $classe->filiere_id === (int) $filiere->id
                        && (int) $classe->niveau_etude_id === (int) $niveau->id
                )->values();

                $scope = $this->scopeResolver->resolveFromConfigurationParams([
                    'systeme' => FraisScopeResolver::SYSTEME_BTS,
                    'niveau_id' => $niveau->id,
                    'filiere_id' => $filiere->id,
                    'filiere_name' => $filiere->name,
                    'niveau_name' => $niveau->name,
                ]);
                $scopeKey = $this->scopeKey(FraisScopeResolver::SYSTEME_BTS, $filiere->id, null, $niveau->id);
                $scopeConfigurations = $this->resolveConfigurationsForMode($configurations, $scopeKey, $mode, $anneeId);
                $annualOverrides = $anneeId ? collect($configurations['annual'][$scopeKey] ?? []) : collect();

                return (object) [
                    'classe' => $classesDuScope->first(),
                    'filiere' => $filiere,
                    'niveau' => $niveau,
                    'scope' => $scope,
                    'name' => $scope['label_scope'] ?: ($filiere->name.' - '.$niveau->name),
                    'meta_line' => collect([$filiere->name, $niveau->name])->filter()->implode(' · '),
                    'effectif' => $classesDuScope->sum(fn (ESBTPClasse $classe) => (int) ($classe->active_inscriptions_count ?? 0)),
                    'classes_count' => $classesDuScope->count(),
                    'configurations' => $scopeConfigurations->values(),
                    'annual_overrides_count' => $annualOverrides->count(),
                    'obligatoires_configures' => $this->countConfiguredMandatory($scopeConfigurations),
                    'optionnels_configures' => $optionalAssignmentCounts[$scopeKey] ?? 0,
                    'total_obligatoires' => $categories->where('is_mandatory', true)->count(),
                    'total_optionnels' => $categories->where('is_mandatory', false)->count(),
                ];
            });
        })->values();
    }

    private function buildLmdCards(
        Collection $parcoursCollection,
        Collection $niveaux,
        Collection $classes,
        Collection $categories,
        string $mode,
        ?int $anneeId,
        array $configurations,
        array $optionalAssignmentCounts,
    ): Collection {
        return $parcoursCollection->flatMap(function (ESBTPLMDParcours $parcours) use ($niveaux, $classes, $categories, $mode, $anneeId, $configurations, $optionalAssignmentCounts) {
            return $niveaux->map(function (ESBTPNiveauEtude $niveau) use ($parcours, $classes, $categories, $mode, $anneeId, $configurations, $optionalAssignmentCounts) {
                $matchingClasses = $classes->filter(function (ESBTPClasse $classe) use ($parcours, $niveau) {
                    return (int) $classe->parcours_id === (int) $parcours->id
                        && (int) $classe->niveau_etude_id === (int) $niveau->id;
                })->values();

                $classe = $matchingClasses->first();
                $scope = $this->scopeResolver->resolveFromConfigurationParams([
                    'systeme' => FraisScopeResolver::SYSTEME_LMD,
                    'niveau_id' => $niveau->id,
                    'parcours_id' => $parcours->id,
                    'filiere_name' => $parcours->filiere?->name,
                    'niveau_name' => $niveau->name,
                ]);
                $scopeKey = $this->scopeKey(FraisScopeResolver::SYSTEME_LMD, null, $parcours->id, $niveau->id);
                $scopeConfigurations = $this->resolveConfigurationsForMode($configurations, $scopeKey, $mode, $anneeId);
                $annualOverrides = $anneeId ? collect($configurations['annual'][$scopeKey] ?? []) : collect();
                $effectif = $matchingClasses->sum(fn (ESBTPClasse $currentClass) => (int) ($currentClass->active_inscriptions_count ?? 0));

                return (object) [
                    'classe' => $classe,
                    'filiere' => (object) [
                        'id' => $parcours->id,
                        'name' => $parcours->name,
                    ],
                    'niveau' => $niveau,
                    'scope' => $scope,
                    'name' => $scope['label_scope'] ?: ($parcours->name . ' - ' . $niveau->name),
                    'meta_line' => collect([$scope['mention'], $scope['domaine']])->filter()->implode(' Â· '),
                    'effectif' => $effectif,
                    'configurations' => $scopeConfigurations->values(),
                    'annual_overrides_count' => $annualOverrides->count(),
                    'obligatoires_configures' => $this->countConfiguredMandatory($scopeConfigurations),
                    'optionnels_configures' => $optionalAssignmentCounts[$scopeKey] ?? 0,
                    'total_obligatoires' => $categories->where('is_mandatory', true)->count(),
                    'total_optionnels' => $categories->where('is_mandatory', false)->count(),
                ];
            });
        })->values();
    }

    private function buildLevelCards(string $systeme, Collection $niveaux, int $targetCount): Collection
    {
        return $niveaux->map(function (ESBTPNiveauEtude $niveau) use ($systeme, $targetCount) {
            return (object) [
                'systeme' => $systeme,
                'niveau' => $niveau,
                'target_count' => $targetCount,
                'label' => $niveau->name,
            ];
        })->values();
    }

    private function preloadConfigurations(?int $anneeId): array
    {
        $baseQuery = ESBTPFraisConfiguration::query()
            ->active()
            ->valid()
            ->with(['fraisCategory', 'options' => fn ($optionsQuery) => $optionsQuery->active()->ordered()]);

        $global = (clone $baseQuery)
            ->whereNull('annee_universitaire_id')
            ->get()
            ->groupBy(fn (ESBTPFraisConfiguration $configuration) => $this->scopeKey(
                $configuration->systeme_academique ?: FraisScopeResolver::SYSTEME_BTS,
                $configuration->filiere_id,
                $configuration->parcours_id,
                $configuration->niveau_id,
            ));

        $annual = collect();
        if ($anneeId) {
            $annual = (clone $baseQuery)
                ->where('annee_universitaire_id', $anneeId)
                ->get()
                ->groupBy(fn (ESBTPFraisConfiguration $configuration) => $this->scopeKey(
                    $configuration->systeme_academique ?: FraisScopeResolver::SYSTEME_BTS,
                    $configuration->filiere_id,
                    $configuration->parcours_id,
                    $configuration->niveau_id,
                ));
        }

        return [
            'global' => $global->map(fn (Collection $rows) => $rows->keyBy('frais_category_id'))->all(),
            'annual' => $annual->map(fn (Collection $rows) => $rows->keyBy('frais_category_id'))->all(),
        ];
    }

    private function preloadOptionalAssignmentCounts(
        Collection $classes,
        Collection $lmdParcours,
        Collection $lmdNiveaux,
        Collection $btsFilieres,
        Collection $btsNiveaux,
    ): array {
        $assignments = ESBTPOptionAssignment::query()->active()->get(['filiere_id', 'niveau_id', 'parcours_id']);
        $counts = [];

        foreach ($classes as $classe) {
            $systeme = $classe->systeme_academique === FraisScopeResolver::SYSTEME_LMD
                ? FraisScopeResolver::SYSTEME_LMD
                : FraisScopeResolver::SYSTEME_BTS;
            $scopeKey = $this->scopeKey($systeme, $classe->filiere_id, $classe->parcours_id, $classe->niveau_etude_id);
            $counts[$scopeKey] = $this->countOptionalAssignmentsForScope($assignments, [
                'systeme' => $systeme,
                'filiere_id' => $classe->filiere_id,
                'parcours_id' => $classe->parcours_id,
                'niveau_id' => $classe->niveau_etude_id,
            ]);
        }

        // Meme raison que pour le LMD : une combinaison sans classe ouverte doit
        // quand meme remonter ses options, sinon la comptabilite verrait zero.
        foreach ($btsFilieres as $filiere) {
            foreach ($btsNiveaux as $niveau) {
                $scopeKey = $this->scopeKey(FraisScopeResolver::SYSTEME_BTS, $filiere->id, null, $niveau->id);
                if (array_key_exists($scopeKey, $counts)) {
                    continue;
                }

                $counts[$scopeKey] = $this->countOptionalAssignmentsForScope($assignments, [
                    'systeme' => FraisScopeResolver::SYSTEME_BTS,
                    'filiere_id' => $filiere->id,
                    'niveau_id' => $niveau->id,
                ]);
            }
        }

        foreach ($lmdParcours as $parcours) {
            foreach ($lmdNiveaux as $niveau) {
                $scopeKey = $this->scopeKey(FraisScopeResolver::SYSTEME_LMD, null, $parcours->id, $niveau->id);
                if (array_key_exists($scopeKey, $counts)) {
                    continue;
                }

                $counts[$scopeKey] = $this->countOptionalAssignmentsForScope($assignments, [
                    'systeme' => FraisScopeResolver::SYSTEME_LMD,
                    'parcours_id' => $parcours->id,
                    'niveau_id' => $niveau->id,
                ]);
            }
        }

        return $counts;
    }

    private function resolveConfigurationsForMode(array $configurations, string $scopeKey, string $mode, ?int $anneeId): Collection
    {
        $global = collect($configurations['global'][$scopeKey] ?? []);
        $annual = $anneeId ? collect($configurations['annual'][$scopeKey] ?? []) : collect();

        if ($mode === 'annual') {
            return $annual->isNotEmpty() ? $global->merge($annual) : $global;
        }

        if ($mode === 'global' || ! $anneeId) {
            return $global;
        }

        return $global->merge($annual);
    }

    private function countConfiguredMandatory(Collection $configurations): int
    {
        return $configurations->filter(function ($configuration) {
            return (bool) optional($configuration->fraisCategory)->is_mandatory;
        })->count();
    }

    private function countOptionalAssignmentsForScope(Collection $assignments, array $scope): int
    {
        return $assignments->filter(function (ESBTPOptionAssignment $assignment) use ($scope) {
            if (($scope['systeme'] ?? null) === FraisScopeResolver::SYSTEME_LMD && ! empty($scope['parcours_id'])) {
                return (
                    (int) $assignment->parcours_id === (int) $scope['parcours_id'] && (int) $assignment->niveau_id === (int) $scope['niveau_id']
                ) || (
                    (int) $assignment->parcours_id === (int) $scope['parcours_id'] && $assignment->niveau_id === null
                ) || (
                    $assignment->parcours_id === null && (int) $assignment->niveau_id === (int) $scope['niveau_id']
                ) || (
                    $assignment->parcours_id === null && $assignment->niveau_id === null && $assignment->filiere_id === null
                );
            }

            return (
                (int) $assignment->filiere_id === (int) ($scope['filiere_id'] ?? 0) && (int) $assignment->niveau_id === (int) $scope['niveau_id']
            ) || (
                (int) $assignment->filiere_id === (int) ($scope['filiere_id'] ?? 0) && $assignment->niveau_id === null
            ) || (
                $assignment->filiere_id === null && (int) $assignment->niveau_id === (int) $scope['niveau_id']
            ) || (
                $assignment->filiere_id === null && $assignment->niveau_id === null
            );
        })->count();
    }

    private function scopeKey(?string $systeme, ?int $filiereId, ?int $parcoursId, ?int $niveauId): string
    {
        return implode('|', [
            strtoupper((string) ($systeme ?: FraisScopeResolver::SYSTEME_BTS)),
            $filiereId ?? 'null',
            $parcoursId ?? 'null',
            $niveauId ?? 'null',
        ]);
    }
}
