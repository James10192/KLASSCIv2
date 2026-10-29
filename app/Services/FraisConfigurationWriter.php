<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FraisConfigurationWriter
{
    /** Valeur UI explicite : ne pas toucher à l'audience de la portée. */
    public const AUDIENCE_CONSERVER = '__keep__';

    public function persistCategories(
        array $scope,
        array $categories,
        string $mode = 'global',
        ?int $anneeId = null,
        ?int $userId = null,
        string $conflictStrategy = 'overwrite_all'
    ): array {
        return DB::transaction(function () use ($scope, $categories, $mode, $anneeId, $userId, $conflictStrategy) {
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
                    throw new InvalidArgumentException("Catégorie de frais #{$categoryId} introuvable.");
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

                $global = null;
                if ($mode === 'annual' && ! $existing) {
                    $global = ESBTPFraisConfiguration::getGlobalForScope($categoryId, $scope);
                }
                $baseline = $existing ?? $global;

                $rawAudience = $categoryData['audience'] ?? null;
                $audienceProvided = array_key_exists('audience', $categoryData)
                    && $rawAudience !== null
                    && $rawAudience !== ''
                    && $rawAudience !== self::AUDIENCE_CONSERVER;
                $audience = $audienceProvided
                    ? $this->normalizeAudience($rawAudience)
                    : null;
                $payload = $this->buildPayload($categoryData, $userId, $baseline);

                // L'audience appartient désormais à la configuration (filière/parcours
                // + niveau), jamais à la catégorie catalogue. On autorise donc une
                // modification d'audience seule sur une configuration qui existe déjà.
                if ($payload === null) {
                    if ($existing && $audienceProvided && $this->effectiveAudience($existing, $category) !== $audience) {
                        $existing->audience = $audience;
                        $existing->save();
                        $summary['updated']++;
                        $summary['affected_configuration_ids'][] = $existing->id;
                    }

                    continue;
                }

                if ($audienceProvided) {
                    $payload['audience'] = $audience;
                }

                if ($mode === 'annual' && ! $existing && $global && $this->matchesConfiguration($payload, $global, $category)) {
                    $summary['skipped']++;

                    continue;
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
                if ($audienceProvided) {
                    $existing->audience = $audience;
                } elseif (! $existing->exists || $existing->audience === null) {
                    // Une nouvelle portée sans choix explicite hérite du global de
                    // CETTE portée ; sinon du défaut catalogue. Une portée existante,
                    // elle, conserve toujours son audience.
                    $existing->audience = $global
                        ? $this->effectiveAudience($global, $category)
                        : $this->normalizeAudience($category->audience ?? ESBTPFraisCategory::AUDIENCE_TOUS);
                }
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
        });
    }

    private function normalizeAudience(mixed $rawAudience): string
    {
        if (is_array($rawAudience)) {
            $rawAudience = match (true) {
                in_array(ESBTPFraisCategory::AUDIENCE_ANCIENS, $rawAudience, true) => ESBTPFraisCategory::AUDIENCE_ANCIENS,
                in_array(ESBTPFraisCategory::AUDIENCE_NOUVEAUX, $rawAudience, true) => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
                in_array(ESBTPFraisCategory::AUDIENCE_TOUS, $rawAudience, true) => ESBTPFraisCategory::AUDIENCE_TOUS,
                default => throw new InvalidArgumentException('Audience de frais invalide.'),
            };
        }

        return match ($rawAudience) {
            ESBTPFraisCategory::AUDIENCE_TOUS => ESBTPFraisCategory::AUDIENCE_TOUS,
            ESBTPFraisCategory::AUDIENCE_NOUVEAUX => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
            ESBTPFraisCategory::AUDIENCE_ANCIENS => ESBTPFraisCategory::AUDIENCE_ANCIENS,
            default => throw new InvalidArgumentException('Audience de frais invalide.'),
        };
    }

    private function effectiveAudience(ESBTPFraisConfiguration $configuration, ESBTPFraisCategory $category): string
    {
        return $this->normalizeAudience(
            $configuration->audience ?? $category->audience ?? ESBTPFraisCategory::AUDIENCE_TOUS
        );
    }

    /**
     * Construit uniquement un vrai barème. Les champs de calendrier/échelonnement
     * absents héritent de la configuration effective au lieu d'être remis
     * silencieusement à 30 jours / sans échéancier lors d'une autre modification.
     */
    private function buildPayload(
        array $categoryData,
        ?int $userId,
        ?ESBTPFraisConfiguration $baseline = null
    ): ?array {
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
            'payment_deadline_days' => array_key_exists('deadline_days', $categoryData)
                ? (int) $categoryData['deadline_days']
                : (int) ($baseline?->payment_deadline_days ?? 30),
            'installments_allowed' => array_key_exists('installments_allowed', $categoryData)
                ? (bool) $categoryData['installments_allowed']
                : (bool) ($baseline?->installments_allowed ?? false),
            'max_installments' => array_key_exists('max_installments', $categoryData)
                ? (int) $categoryData['max_installments']
                : (int) ($baseline?->max_installments ?? 1),
            'early_payment_discount' => array_key_exists('early_payment_discount', $categoryData)
                ? (float) $categoryData['early_payment_discount']
                : (float) ($baseline?->early_payment_discount ?? 0),
            'effective_date' => now(),
            'is_active' => true,
            'created_by' => $userId,
        ];
    }

    private function matchesConfiguration(
        array $payload,
        ESBTPFraisConfiguration $configuration,
        ESBTPFraisCategory $category
    ): bool {
        $sameAudience = ! array_key_exists('audience', $payload)
            || $this->effectiveAudience($configuration, $category) === $payload['audience'];

        return $sameAudience
            && (float) $configuration->amount === (float) $payload['amount']
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
