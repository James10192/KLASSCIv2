<?php

namespace App\Services;

use Illuminate\Support\Collection;

class BulkLevelFraisApplicator
{
    public function __construct(
        private readonly LevelFeeTargetResolver $targetResolver,
        private readonly FraisConfigurationWriter $configurationWriter,
        private readonly FraisScopeResolver $scopeResolver,
    ) {
    }

    public function preview(string $systeme, int $niveauId, string $mode = 'global', ?int $anneeId = null): Collection
    {
        return $this->targetResolver->resolve($systeme, $niveauId, $mode, $anneeId);
    }

    public function apply(
        string $systeme,
        int $niveauId,
        array $categories,
        array $targets,
        string $mode = 'global',
        ?int $anneeId = null,
        string $conflictStrategy = 'overwrite_all',
        ?int $userId = null
    ): array {
        $availableTargets = $this->preview($systeme, $niveauId, $mode, $anneeId)->keyBy('key');
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'targets_count' => 0,
            'affected_targets' => [],
        ];

        foreach ($targets as $target) {
            $key = $this->targetResolver->buildTargetKey([
                'systeme' => $target['systeme'] ?? $systeme,
                'niveau_id' => $target['niveau_id'] ?? $niveauId,
                'filiere_id' => $target['filiere_id'] ?? null,
                'parcours_id' => $target['parcours_id'] ?? null,
            ]);

            $available = $availableTargets->get($key);
            if (! $available) {
                continue;
            }

            $scope = $this->scopeResolver->resolveFromConfigurationParams([
                'systeme' => $available['systeme'],
                'niveau_id' => $available['niveau_id'],
                'filiere_id' => $available['filiere_id'],
                'parcours_id' => $available['parcours_id'],
            ]);

            $result = $this->configurationWriter->persistCategories(
                $scope,
                $categories,
                $mode,
                $anneeId,
                $userId,
                $conflictStrategy
            );

            $summary['created'] += $result['created'];
            $summary['updated'] += $result['updated'];
            $summary['skipped'] += $result['skipped'];
            $summary['targets_count']++;
            $summary['affected_targets'][] = [
                'key' => $key,
                'label_scope' => $available['label_scope'],
                'systeme' => $available['systeme'],
                'niveau_id' => $available['niveau_id'],
                'filiere_id' => $available['filiere_id'],
                'parcours_id' => $available['parcours_id'],
            ];
        }

        return $summary;
    }
}
