<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
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

        return [
            'btsClasses' => $this->buildCards($classes->where('systeme_academique', '!=', 'LMD')->values(), $categories, 'BTS'),
            'lmdClasses' => $this->buildCards($classes->where('systeme_academique', 'LMD')->values(), $categories, 'LMD'),
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

            $optionnelsAssignes = ESBTPOptionAssignment::query()
                ->when($systeme === 'LMD' && $scope['parcours_id'], function ($query) use ($scope) {
                    $query->where('parcours_id', $scope['parcours_id']);
                }, function ($query) use ($classe) {
                    $query->where(function ($q) use ($classe) {
                        $q->where('filiere_id', $classe->filiere_id)
                            ->where('niveau_id', $classe->niveau_etude_id);
                    })->orWhere(function ($q) use ($classe) {
                        $q->where('filiere_id', $classe->filiere_id)->whereNull('niveau_id');
                    })->orWhere(function ($q) use ($classe) {
                        $q->whereNull('filiere_id')->where('niveau_id', $classe->niveau_etude_id);
                    })->orWhere(function ($q) {
                        $q->whereNull('filiere_id')->whereNull('niveau_id');
                    });
                })
                ->count();

            return (object) [
                'classe' => $classe,
                'filiere' => $filiereDisplay,
                'niveau' => $classe->niveau,
                'scope' => $scope,
                'name' => $scope['label_scope'] ?: $classe->name,
                'effectif' => $effectif,
                'configurations' => $configurations->values(),
                'obligatoires_configures' => $configurations->count(),
                'optionnels_configures' => $optionnelsAssignes,
                'total_obligatoires' => $categories->where('is_mandatory', true)->count(),
                'total_optionnels' => $categories->where('is_mandatory', false)->count(),
            ];
        })->values();
    }
}
