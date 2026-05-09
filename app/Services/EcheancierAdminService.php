<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPOptionAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EcheancierAdminService
{
    /**
     * Compte les inscriptions actives potentiellement impactées par une règle d'échéancier.
     * Utilisé en bandeau UI pour afficher "Cette règle s'appliquera à X étudiants".
     * Cache 5 min pour éviter le coût sur tenants 3000+ inscriptions.
     */
    public function countAffectedInscriptions(string $scopeType, int $scopeId, string $status): int
    {
        $cacheKey = sprintf('echeancier:affected:%s:%d:%s', $scopeType, $scopeId, $status);

        return (int) Cache::remember($cacheKey, 300, function () use ($scopeType, $scopeId, $status) {
            $query = ESBTPInscription::query()
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->whereNull('deleted_at');

            // Statut d'affectation : 'all' = toutes les inscriptions, sinon filtrer
            if ($status !== ESBTPEcheancierRule::STATUS_ALL) {
                $query->where('affectation_status', $status);
            }

            // Scope = configuration → limiter aux inscriptions filiere/niveau correspondants
            if ($scopeType === ESBTPEcheancierRule::SCOPE_CONFIGURATION) {
                $config = ESBTPFraisConfiguration::find($scopeId);
                if (!$config) return 0;
                $query->where('filiere_id', $config->filiere_id)
                      ->where('niveau_id', $config->niveau_id);
            }

            // Scope = option assignment → limiter aux inscriptions filiere/niveau de l'assignment
            if ($scopeType === ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT) {
                $assignment = ESBTPOptionAssignment::find($scopeId);
                if (!$assignment) return 0;
                if ($assignment->filiere_id) $query->where('filiere_id', $assignment->filiere_id);
                if ($assignment->niveau_id) $query->where('niveau_id', $assignment->niveau_id);
            }

            return $query->count();
        });
    }

    /**
     * Statuts d'affectation pour lesquels une règle ACTIVE existe sur un scope donné.
     * Sert au bandeau UI "checklist 4 statuts" pour signaler les règles manquantes.
     */
    public function activeStatusesForScope(string $scopeType, int $scopeId): array
    {
        return ESBTPEcheancierRule::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('is_active', true)
            ->pluck('affectation_status')
            ->all();
    }


    public function buildDiagnostics(Collection $configurations, Collection $optionAssignments, Collection $rulesByScope): array
    {
        $allScopes = collect();
        foreach ($configurations as $configuration) {
            $allScopes->push(['key' => 'configuration:' . $configuration->id]);
        }
        foreach ($optionAssignments as $assignment) {
            $allScopes->push(['key' => 'option_assignment:' . $assignment->id]);
        }

        $allRules = $rulesByScope->flatten(1);
        $configured = $allScopes->filter(fn ($scope) => ($rulesByScope->get($scope['key'], collect()))->isNotEmpty())->count();

        return [
            'total' => $allScopes->count(),
            'configured' => $configured,
            'unconfigured' => max(0, $allScopes->count() - $configured),
            'active_rules' => $allRules->where('is_active', true)->count(),
            'inactive_rules' => $allRules->where('is_active', false)->count(),
            'invalid_totals' => $allRules->filter(fn ($rule) => !$this->ruleHasCoherentTotal($rule))->count(),
        ];
    }

    public function ruleHasCoherentTotal(ESBTPEcheancierRule $rule): bool
    {
        $activeLines = $rule->lines->where('is_active', true);
        if ($activeLines->isEmpty()) {
            return false;
        }

        $percentLines = $activeLines->where('amount_mode', ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT);
        if ($percentLines->isEmpty()) {
            return true;
        }

        return abs((float) $percentLines->sum('amount_value') - 100.0) < 0.01;
    }

    public function resolvePreviewAmount(string $scopeType, int $scopeId, string $status): float
    {
        if ($scopeType === ESBTPEcheancierRule::SCOPE_CONFIGURATION) {
            $configuration = ESBTPFraisConfiguration::find($scopeId);
            return $configuration ? (float) $configuration->getMontantByStatus($status) : 150000.0;
        }

        $assignment = ESBTPOptionAssignment::with('option')->find($scopeId);
        return $assignment?->option ? (float) $assignment->option->additional_amount : 150000.0;
    }

    public function copyRule(ESBTPEcheancierRule $sourceRule, string $mode, int $sourceScopeId, ?int $userId): int
    {
        $targets = $this->copyTargets($sourceRule->scope_type, $sourceScopeId, $mode, $sourceRule->affectation_status);
        $copied = 0;

        DB::transaction(function () use ($targets, $sourceRule, $userId, &$copied) {
            foreach ($targets as $target) {
                $rule = ESBTPEcheancierRule::updateOrCreate(
                    [
                        'scope_type' => $target['scope_type'],
                        'scope_id' => $target['scope_id'],
                        'affectation_status' => $sourceRule->affectation_status,
                    ],
                    [
                        'priority' => $sourceRule->priority,
                        'is_active' => $sourceRule->is_active,
                        'effective_from' => $sourceRule->effective_from,
                        'effective_to' => $sourceRule->effective_to,
                        'notes' => $sourceRule->notes,
                        'updated_by' => $userId,
                        'created_by' => $userId,
                    ]
                );

                $rule->lines()->delete();
                foreach ($sourceRule->lines as $line) {
                    $rule->lines()->create([
                        'label' => $line->label,
                        'sort_order' => $line->sort_order,
                        'amount_mode' => $line->amount_mode,
                        'amount_value' => $line->amount_value,
                        'due_mode' => $line->due_mode,
                        'due_value' => $line->due_value,
                        'grace_days' => $line->grace_days,
                        'is_active' => $line->is_active,
                    ]);
                }

                $copied++;
            }
        });

        return $copied;
    }

    private function copyTargets(string $scopeType, int $scopeId, string $mode, string $status): Collection
    {
        if ($scopeType === ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT) {
            $source = ESBTPOptionAssignment::findOrFail($scopeId);
            $query = ESBTPOptionAssignment::query()->where('is_active', true)->where('id', '!=', $scopeId);

            if ($mode === 'same_filiere') {
                if (!$source->filiere_id) {
                    return collect();
                }
                $query->where('filiere_id', $source->filiere_id);
            } elseif ($mode === 'same_niveau') {
                if (!$source->niveau_id) {
                    return collect();
                }
                $query->where('niveau_id', $source->niveau_id);
            } elseif ($mode === 'all_unconfigured') {
                $query->whereDoesntHave('echeancierRules', fn ($q) => $q->where('affectation_status', $status));
            }

            return $query->limit(300)->get()->map(fn ($assignment) => [
                'scope_type' => ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT,
                'scope_id' => (int) $assignment->id,
            ]);
        }

        $source = ESBTPFraisConfiguration::findOrFail($scopeId);
        $query = ESBTPFraisConfiguration::query()->where('is_active', true)->where('id', '!=', $scopeId);

        if ($mode === 'same_filiere') {
            if (!$source->filiere_id) {
                return collect();
            }
            $query->where('filiere_id', $source->filiere_id);
        } elseif ($mode === 'same_niveau') {
            if (!$source->niveau_id) {
                return collect();
            }
            $query->where('niveau_id', $source->niveau_id);
        } elseif ($mode === 'all_unconfigured') {
            $query->whereDoesntHave('echeancierRules', fn ($q) => $q->where('affectation_status', $status));
        }

        return $query->limit(300)->get()->map(fn ($configuration) => [
            'scope_type' => ESBTPEcheancierRule::SCOPE_CONFIGURATION,
            'scope_id' => (int) $configuration->id,
        ]);
    }
}
