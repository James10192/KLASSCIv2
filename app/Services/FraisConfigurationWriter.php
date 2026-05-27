<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;

class FraisConfigurationWriter
{
    public function persistCategories(
        array $scope,
        array $categories,
        string $mode = 'global',
        ?int $anneeId = null,
        ?int $userId = null,
        string $conflictStrategy = 'overwrite_all'
    ): array {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'affected_configuration_ids' => [],
        ];

        $scopeYear = $mode === 'annual' ? $anneeId : null;

        foreach ($categories as $categoryId => $categoryData) {
            $category = ESBTPFraisCategory::find($categoryId);
            if (! $category) {
                continue;
            }

            $payload = $this->buildPayload($categoryData, $userId);
            if ($payload === null) {
                continue;
            }

            $existing = ESBTPFraisConfiguration::queryForScope($scope)
                ->where('frais_category_id', $categoryId)
                ->when(
                    $mode === 'annual',
                    fn ($query) => $query->where('annee_universitaire_id', $scopeYear),
                    fn ($query) => $query->whereNull('annee_universitaire_id')
                )
                ->first();

            if ($existing && $conflictStrategy === 'create_missing_only') {
                $summary['skipped']++;
                $summary['affected_configuration_ids'][] = $existing->id;

                continue;
            }

            if ($mode === 'annual' && ! $existing) {
                $global = ESBTPFraisConfiguration::getGlobalForScope($categoryId, $scope);
                if ($global && $this->matchesConfiguration($payload, $global)) {
                    $summary['skipped']++;

                    continue;
                }
            }

            if (! $existing) {
                $existing = new ESBTPFraisConfiguration([
                    'frais_category_id' => $categoryId,
                    'systeme_academique' => $scope['systeme'],
                    'filiere_id' => $scope['filiere_id'],
                    'parcours_id' => $scope['parcours_id'],
                    'niveau_id' => $scope['niveau_id'],
                    'annee_universitaire_id' => $scopeYear,
                    'created_by' => $userId,
                ]);
                $summary['created']++;
            } else {
                $summary['updated']++;
            }

            $existing->fill($payload);
            $existing->systeme_academique = $scope['systeme'];
            $existing->filiere_id = $scope['filiere_id'];
            $existing->parcours_id = $scope['parcours_id'];
            $existing->niveau_id = $scope['niveau_id'];
            $existing->annee_universitaire_id = $scopeYear;
            $existing->created_by = $existing->created_by ?: $userId;
            $existing->is_active = true;
            $existing->save();

            $summary['affected_configuration_ids'][] = $existing->id;
        }

        $summary['affected_configuration_ids'] = array_values(array_unique(array_filter($summary['affected_configuration_ids'])));

        return $summary;
    }

    private function buildPayload(array $categoryData, ?int $userId): ?array
    {
        $hasValue = fn (string $key) => isset($categoryData[$key]) && $categoryData[$key] !== '' && is_numeric($categoryData[$key]);
        $value = fn (string $key) => (float) $categoryData[$key];
        $mainAmount = match (true) {
            $hasValue('amount') => $value('amount'),
            $hasValue('amount_affecte') => $value('amount_affecte'),
            $hasValue('amount_reaffecte') => $value('amount_reaffecte'),
            $hasValue('amount_non_affecte') => $value('amount_non_affecte'),
            default => null,
        };

        if ($mainAmount === null) {
            return null;
        }

        return [
            'amount' => $mainAmount,
            'amount_affecte' => $hasValue('amount_affecte') ? $value('amount_affecte') : null,
            'amount_reaffecte' => $hasValue('amount_reaffecte') ? $value('amount_reaffecte') : null,
            'amount_non_affecte' => $hasValue('amount_non_affecte') ? $value('amount_non_affecte') : null,
            'payment_deadline_days' => (int) ($categoryData['deadline_days'] ?? 30),
            'installments_allowed' => (bool) ($categoryData['installments_allowed'] ?? false),
            'max_installments' => (int) ($categoryData['max_installments'] ?? 1),
            'early_payment_discount' => (float) ($categoryData['early_payment_discount'] ?? 0),
            'effective_date' => now(),
            'is_active' => true,
            'created_by' => $userId,
        ];
    }

    private function matchesConfiguration(array $payload, ESBTPFraisConfiguration $configuration): bool
    {
        return (float) $configuration->amount === (float) $payload['amount']
            && $this->nullableFloatEquals($configuration->amount_affecte, $payload['amount_affecte'])
            && $this->nullableFloatEquals($configuration->amount_reaffecte, $payload['amount_reaffecte'])
            && $this->nullableFloatEquals($configuration->amount_non_affecte, $payload['amount_non_affecte'])
            && (int) $configuration->payment_deadline_days === (int) $payload['payment_deadline_days']
            && (bool) $configuration->installments_allowed === (bool) $payload['installments_allowed']
            && (int) $configuration->max_installments === (int) $payload['max_installments']
            && (float) $configuration->early_payment_discount === (float) $payload['early_payment_discount'];
    }

    private function nullableFloatEquals($left, $right): bool
    {
        if ($left === null && $right === null) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        return (float) $left === (float) $right;
    }
}
