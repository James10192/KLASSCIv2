<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;

class ApplicableFraisResolver
{
    public function __construct(
        private readonly FraisScopeResolver $scopeResolver,
    ) {
    }

    public function resolveMandatoryFeesForInscription(ESBTPInscription $inscription, ?string $affectationStatus = null): Collection
    {
        $scope = $this->scopeResolver->resolveForInscription($inscription);
        $status = $affectationStatus ?? $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

        return ESBTPFraisCategory::active()
            ->mandatory()
            ->ordered()
            ->get()
            ->map(function (ESBTPFraisCategory $category) use ($scope, $status) {
                $configuration = ESBTPFraisConfiguration::getApplicableForScope($category->id, $scope);
                $amount = $configuration
                    ? $configuration->getMontantByStatus($status)
                    : (float) ($category->default_amount ?? 0);

                return [
                    'category' => $category,
                    'configuration' => $configuration,
                    'amount' => (float) $amount,
                    'description' => $category->name,
                    'type' => 'mandatory',
                    'scope' => $scope,
                ];
            });
    }

    public function resolveFeesForClasse(ESBTPClasse $classe, ?string $affectationStatus = null): Collection
    {
        $inscription = new ESBTPInscription([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'affectation_status' => $affectationStatus ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
        ]);
        $inscription->setRelation('classe', $classe);

        return $this->resolveMandatoryFeesForInscription($inscription, $affectationStatus);
    }

    public function getSubscribedOptionalFeesForInscription(ESBTPInscription $inscription): Collection
    {
        return ESBTPFraisSubscription::resolveSubscriptionsForStudentContext($inscription);
    }

    public function getAvailableOptionalOptionsForInscription(ESBTPInscription $inscription): Collection
    {
        $scope = $this->scopeResolver->resolveForInscription($inscription);

        return ESBTPFraisCategory::active()
            ->optional()
            ->ordered()
            ->get()
            ->map(function (ESBTPFraisCategory $category) use ($scope) {
                $configuration = ESBTPFraisConfiguration::getApplicableForScope($category->id, $scope);

                $options = ESBTPFraisOption::active()
                    ->with(['assignments', 'fraisCategory', 'configuration.fraisCategory'])
                    ->where(function ($query) use ($category, $configuration) {
                        $query->where('frais_category_id', $category->id);

                        if ($configuration) {
                            $query->orWhere('configuration_id', $configuration->id);
                        }
                    })
                    ->get()
                    ->filter(fn (ESBTPFraisOption $option) => $this->optionMatchesScope($option, $scope))
                    ->values();

                return [
                    'category' => $category,
                    'configuration' => $configuration,
                    'options' => $options,
                    'scope' => $scope,
                ];
            })
            ->filter(fn (array $row) => $row['options']->isNotEmpty())
            ->values();
    }

    private function optionMatchesScope(ESBTPFraisOption $option, array $scope): bool
    {
        if ($option->isClassBased()) {
            return (int) $option->configuration?->id === (int) ESBTPFraisConfiguration::getApplicableForScope(
                $option->configuration?->frais_category_id,
                $scope
            )?->id;
        }

        $assignments = $option->assignments->where('is_active', true);
        if ($assignments->isEmpty()) {
            return true;
        }

        foreach ($assignments as $assignment) {
            if ($assignment->assignment_type === 'all') {
                return true;
            }

            if (($scope['systeme'] ?? null) === FraisScopeResolver::SYSTEME_LMD && $assignment->parcours_id ?? null) {
                if ((int) $assignment->parcours_id === (int) ($scope['parcours_id'] ?? 0)) {
                    return true;
                }
            }

            $filiereMatch = $assignment->filiere_id === null || (int) $assignment->filiere_id === (int) ($scope['filiere_id'] ?? 0);
            $niveauMatch = $assignment->niveau_id === null || (int) $assignment->niveau_id === (int) ($scope['niveau_id'] ?? 0);

            if ($filiereMatch && $niveauMatch) {
                return true;
            }
        }

        return false;
    }
}
