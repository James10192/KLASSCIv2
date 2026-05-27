<?php

namespace App\Services;

use App\Models\ESBTPClasse;
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
    ) {
    }

    public function build(): array
    {
        $categories = ESBTPFraisCategory::active()->ordered()->get();
        $classes = ESBTPClasse::query()
            ->with(['filiere', 'niveau', 'parcours.mention.domaine'])
            ->where('is_active', true)
            ->get();
        $lmdParcours = ESBTPLMDParcours::query()
            ->with(['filiere', 'mention.domaine'])
            ->where('is_active', true)
            ->get();
        $lmdNiveaux = ESBTPNiveauEtude::query()
            ->active()
            ->whereIn('type', ['Licence', 'Master', 'Doctorat'])
            ->orderBy('year')
            ->orderBy('name')
            ->get();

        return [
            'btsClasses' => $this->buildCards($classes->where('systeme_academique', '!=', 'LMD')->values(), $categories, 'BTS'),
            'lmdClasses' => $this->buildLmdCards($lmdParcours, $lmdNiveaux, $classes->where('systeme_academique', 'LMD')->values(), $categories),
        ];
    }

    private function buildCards(Collection $classes, Collection $categories, string $systeme): Collection
    {
        return $classes->map(function (ESBTPClasse $classe) use ($categories, $systeme) {
            $scope = $this->scopeResolver->resolveForClasse($classe);
            $configurations = ESBTPFraisConfiguration::getApplicableForClass($classe)->keyBy('frais_category_id');
            $effectif = $classe->inscriptions()
                ->where('status', 'active')
                ->count();
            $filiereDisplay = $systeme === 'LMD'
                ? (object) [
                    'id' => $scope['parcours_id'],
                    'name' => $scope['parcours'] ?? $classe->filiere?->name ?? $classe->name,
                ]
                : $classe->filiere;

            return (object) [
                'classe' => $classe,
                'filiere' => $filiereDisplay,
                'niveau' => $classe->niveau,
                'scope' => $scope,
                'name' => $scope['label_scope'] ?: $classe->name,
                'meta_line' => $systeme === 'LMD'
                    ? collect([$scope['mention'], $scope['domaine']])->filter()->implode(' · ')
                    : collect([$classe->filiere?->name, $classe->niveau?->name])->filter()->implode(' · '),
                'effectif' => $effectif,
                'configurations' => $configurations->values(),
                'obligatoires_configures' => $this->countConfiguredMandatory($configurations),
                'optionnels_configures' => $this->countOptionalAssignments($scope),
                'total_obligatoires' => $categories->where('is_mandatory', true)->count(),
                'total_optionnels' => $categories->where('is_mandatory', false)->count(),
            ];
        })->values();
    }

    private function buildLmdCards(Collection $parcoursCollection, Collection $niveaux, Collection $classes, Collection $categories): Collection
    {
        return $parcoursCollection->flatMap(function (ESBTPLMDParcours $parcours) use ($niveaux, $classes, $categories) {
            return $niveaux->map(function (ESBTPNiveauEtude $niveau) use ($parcours, $classes, $categories) {
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
                $configurations = ESBTPFraisConfiguration::query()
                    ->with('fraisCategory')
                    ->active()
                    ->valid()
                    ->where('systeme_academique', FraisScopeResolver::SYSTEME_LMD)
                    ->where('parcours_id', $parcours->id)
                    ->where('niveau_id', $niveau->id)
                    ->get()
                    ->keyBy('frais_category_id');
                $effectif = $matchingClasses->sum(function (ESBTPClasse $currentClass) {
                    return $currentClass->inscriptions()
                        ->where('status', 'active')
                        ->count();
                });

                return (object) [
                    'classe' => $classe,
                    'filiere' => (object) [
                        'id' => $parcours->id,
                        'name' => $parcours->name,
                    ],
                    'niveau' => $niveau,
                    'scope' => $scope,
                    'name' => $scope['label_scope'] ?: ($parcours->name . ' - ' . $niveau->name),
                    'meta_line' => collect([$scope['mention'], $scope['domaine']])->filter()->implode(' · '),
                    'effectif' => $effectif,
                    'configurations' => $configurations->values(),
                    'obligatoires_configures' => $this->countConfiguredMandatory($configurations),
                    'optionnels_configures' => $this->countOptionalAssignments($scope),
                    'total_obligatoires' => $categories->where('is_mandatory', true)->count(),
                    'total_optionnels' => $categories->where('is_mandatory', false)->count(),
                ];
            });
        })->values();
    }

    private function countConfiguredMandatory(Collection $configurations): int
    {
        return $configurations->filter(function (ESBTPFraisConfiguration $configuration) {
            return (bool) optional($configuration->fraisCategory)->is_mandatory;
        })->count();
    }

    private function countOptionalAssignments(array $scope): int
    {
        return ESBTPOptionAssignment::query()
            ->when(($scope['systeme'] ?? null) === FraisScopeResolver::SYSTEME_LMD && ! empty($scope['parcours_id']), function ($query) use ($scope) {
                $query->where(function ($q) use ($scope) {
                    $q->where('parcours_id', $scope['parcours_id'])
                        ->where('niveau_id', $scope['niveau_id']);
                })->orWhere(function ($q) use ($scope) {
                    $q->where('parcours_id', $scope['parcours_id'])
                        ->whereNull('niveau_id');
                })->orWhere(function ($q) use ($scope) {
                    $q->whereNull('parcours_id')
                        ->where('niveau_id', $scope['niveau_id']);
                })->orWhere(function ($q) {
                    $q->whereNull('parcours_id')
                        ->whereNull('niveau_id')
                        ->whereNull('filiere_id');
                });
            }, function ($query) use ($scope) {
                $query->where(function ($q) use ($scope) {
                    $q->where('filiere_id', $scope['filiere_id'])
                        ->where('niveau_id', $scope['niveau_id']);
                })->orWhere(function ($q) use ($scope) {
                    $q->where('filiere_id', $scope['filiere_id'])->whereNull('niveau_id');
                })->orWhere(function ($q) use ($scope) {
                    $q->whereNull('filiere_id')->where('niveau_id', $scope['niveau_id']);
                })->orWhere(function ($q) {
                    $q->whereNull('filiere_id')->whereNull('niveau_id');
                });
            })
            ->count();
    }
}
