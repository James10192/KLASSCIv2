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
        private readonly TenantScolariteSettings $scolariteSettings,
    ) {
    }

    /**
     * Une seule définition de l'audience effective : la configuration précise
     * gagne, puis le catalogue sert uniquement de repli pour les anciennes données.
     */
    public function effectiveAudience(
        ESBTPFraisCategory $category,
        ?ESBTPFraisConfiguration $configuration = null
    ): string {
        return match ($configuration?->audience ?? $category->audience ?? ESBTPFraisCategory::AUDIENCE_TOUS) {
            ESBTPFraisCategory::AUDIENCE_NOUVEAUX => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
            ESBTPFraisCategory::AUDIENCE_ANCIENS => ESBTPFraisCategory::AUDIENCE_ANCIENS,
            default => ESBTPFraisCategory::AUDIENCE_TOUS,
        };
    }

    public function categoryAppliesToStudent(
        ESBTPFraisCategory $category,
        ?string $statutEtablissement,
        ?ESBTPFraisConfiguration $configuration = null
    ): bool {
        return $this->audienceAppliesToStatus(
            $this->effectiveAudience($category, $configuration),
            $statutEtablissement,
        );
    }

    /**
     * Audience réellement applicable à cette inscription : priorité à la
     * configuration filière/parcours + niveau + année, puis repli catalogue.
     */
    public function audienceForInscription(ESBTPFraisCategory $category, ESBTPInscription $inscription): string
    {
        $scope = $this->scopeResolver->resolveForInscription($inscription);
        $configuration = ESBTPFraisConfiguration::getApplicableForScope($category->id, $scope);

        return $this->effectiveAudience($category, $configuration);
    }

    /**
     * Variante sûre dès qu'une inscription est disponible : elle résout d'abord
     * la configuration effective de SA filière/parcours + niveau + année.
     */
    public function categoryAppliesToInscription(ESBTPFraisCategory $category, ESBTPInscription $inscription): bool
    {
        return $this->audienceAppliesToStatus(
            $this->audienceForInscription($category, $inscription),
            $inscription->statut_etablissement,
        );
    }

    public function resolveMandatoryFeesForInscription(ESBTPInscription $inscription, ?string $affectationStatus = null): Collection
    {
        $scope = $this->scopeResolver->resolveForInscription($inscription);
        $status = $affectationStatus ?? $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

        return ESBTPFraisCategory::active()
            ->mandatory()
            ->ordered()
            ->get()
            ->map(function (ESBTPFraisCategory $category) use ($scope) {
                $configuration = ESBTPFraisConfiguration::getApplicableForScope($category->id, $scope);

                return [
                    'category' => $category,
                    'configuration' => $configuration,
                    'audience' => $this->effectiveAudience($category, $configuration),
                ];
            })
            ->filter(fn (array $row) => $this->audienceAppliesToStatus(
                $row['audience'],
                $inscription->statut_etablissement,
            ))
            ->map(function (array $row) use ($scope, $status) {
                $category = $row['category'];
                $configuration = $row['configuration'];
                $amount = $configuration
                    ? $configuration->getMontantByStatus($status)
                    : (float) ($category->default_amount ?? 0);

                return [
                    'category' => $category,
                    'configuration' => $configuration,
                    'audience' => $row['audience'],
                    'amount' => (float) $amount,
                    'description' => $category->name,
                    'type' => 'mandatory',
                    'scope' => $scope,
                ];
            })
            ->values();
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

                return [
                    'category' => $category,
                    'configuration' => $configuration,
                    'audience' => $this->effectiveAudience($category, $configuration),
                ];
            })
            ->filter(fn (array $row) => $this->audienceAppliesToStatus(
                $row['audience'],
                $inscription->statut_etablissement,
            ))
            ->map(function (array $row) use ($scope) {
                $category = $row['category'];
                $configuration = $row['configuration'];

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
                    'audience' => $row['audience'],
                    'options' => $options,
                    'scope' => $scope,
                ];
            })
            ->filter(fn (array $row) => $row['options']->isNotEmpty())
            ->values();
    }

    private function audienceAppliesToStatus(string $audience, ?string $statutEtablissement): bool
    {
        // Une audience restreinte exige un statut explicite : l'absence de statut
        // ne doit jamais être interprétée comme « nouveau » ou « ancien ».
        return match ($audience) {
            ESBTPFraisCategory::AUDIENCE_NOUVEAUX => $statutEtablissement === ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
            ESBTPFraisCategory::AUDIENCE_ANCIENS => $statutEtablissement === ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
            default => true,
        };
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
