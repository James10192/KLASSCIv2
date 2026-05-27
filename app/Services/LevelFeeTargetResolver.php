<?php

namespace App\Services;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Collection;

class LevelFeeTargetResolver
{
    public function __construct(
        private readonly FraisScopeResolver $scopeResolver,
    ) {
    }

    public function resolve(string $systeme, int $niveauId, string $mode = 'global', ?int $anneeId = null): Collection
    {
        $systeme = strtoupper($systeme);
        $niveau = ESBTPNiveauEtude::findOrFail($niveauId);
        $mandatoryCount = ESBTPFraisCategory::active()->mandatory()->count();

        if ($systeme === FraisScopeResolver::SYSTEME_LMD) {
            return ESBTPLMDParcours::query()
                ->with(['mention.domaine', 'filiere'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (ESBTPLMDParcours $parcours) => $this->buildTarget(
                    $this->scopeResolver->resolveFromConfigurationParams([
                        'systeme' => FraisScopeResolver::SYSTEME_LMD,
                        'parcours_id' => $parcours->id,
                        'niveau_id' => $niveau->id,
                        'niveau_name' => $niveau->name,
                        'filiere_name' => $parcours->filiere?->name,
                    ]),
                    $mandatoryCount,
                    $mode,
                    $anneeId
                ))->values();
        }

        return ESBTPFiliere::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ESBTPFiliere $filiere) => $this->buildTarget(
                $this->scopeResolver->resolveFromConfigurationParams([
                    'systeme' => FraisScopeResolver::SYSTEME_BTS,
                    'filiere_id' => $filiere->id,
                    'niveau_id' => $niveau->id,
                    'filiere_name' => $filiere->name,
                    'niveau_name' => $niveau->name,
                ]),
                $mandatoryCount,
                $mode,
                $anneeId
            ))->values();
    }

    public function buildTargetKey(array $scope): string
    {
        return implode(':', [
            $scope['systeme'] ?? FraisScopeResolver::SYSTEME_BTS,
            $scope['niveau_id'] ?? 0,
            $scope['filiere_id'] ?? 'na',
            $scope['parcours_id'] ?? 'na',
        ]);
    }

    private function buildTarget(array $scope, int $mandatoryCount, string $mode, ?int $anneeId): array
    {
        $resolvedConfigurations = $this->getResolvedConfigurations($scope, $mode, $anneeId);
        $overrideConfigurations = $mode === 'annual'
            ? ESBTPFraisConfiguration::getConfigurationsForScope($scope, $anneeId, 'annual')
            : collect();

        return [
            'key' => $this->buildTargetKey($scope),
            'systeme' => $scope['systeme'],
            'niveau_id' => $scope['niveau_id'],
            'filiere_id' => $scope['filiere_id'],
            'parcours_id' => $scope['parcours_id'],
            'label_scope' => $scope['label_scope'],
            'mention' => $scope['mention'],
            'domaine' => $scope['domaine'],
            'configured_count' => $resolvedConfigurations->count(),
            'mandatory_total' => $mandatoryCount,
            'has_override' => $overrideConfigurations->isNotEmpty(),
        ];
    }

    private function getResolvedConfigurations(array $scope, string $mode, ?int $anneeId): Collection
    {
        $configurations = $mode === 'annual'
            ? ESBTPFraisConfiguration::getConfigurationsForScope($scope, $anneeId, 'effective')
            : ESBTPFraisConfiguration::getConfigurationsForScope($scope, null, 'global');

        return $configurations
            ->filter(fn (ESBTPFraisConfiguration $configuration) => (bool) optional($configuration->fraisCategory)->is_mandatory)
            ->values();
    }
}
