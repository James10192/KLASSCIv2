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

    public function categoryAppliesToStudent(ESBTPFraisCategory $category, ?string $statutEtablissement): bool
    {
        $audience = $category->audience ?? ESBTPFraisCategory::AUDIENCE_TOUS;

        // Les deux audiences restreintes exigent une reponse EXPLICITE. Un statut
        // absent ne veut pas dire « nouveau », il veut dire « on ne sait pas » —
        // et on ne facture pas sur une supposition.
        //
        // La regle etait dissymetrique : « anciens » demandait un statut egal a
        // « ancien », mais « nouveaux » se contentait de « pas ancien », donc un
        // champ vide suffisait. Un ancien d'ISLG a paye 50 000 F de tenue pour
        // cette seule raison, et rendre l'argent a demande une reventilation.
        //
        // Le choix, pose par l'ecole en septembre 2026 : mieux vaut sous-facturer
        // que sur-facturer. Un frais oublie se reclame ; un frais encaisse a tort
        // ne se retire plus, parce que retirer une souscription payee laisserait
        // le versement sans affectation.
        return match ($audience) {
            ESBTPFraisCategory::AUDIENCE_NOUVEAUX => $statutEtablissement === ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
            ESBTPFraisCategory::AUDIENCE_ANCIENS => $statutEtablissement === ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
            default => true,
        };
    }

    public function resolveMandatoryFeesForInscription(ESBTPInscription $inscription, ?string $affectationStatus = null): Collection
    {
        $scope = $this->scopeResolver->resolveForInscription($inscription);
        $status = $affectationStatus ?? $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

        return ESBTPFraisCategory::active()
            ->mandatory()
            ->ordered()
            ->get()
            ->filter(fn (ESBTPFraisCategory $category) => $this->categoryAppliesToStudent(
                $category,
                $inscription->statut_etablissement,
            ))
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
            ->filter(fn (ESBTPFraisCategory $category) => $this->categoryAppliesToStudent(
                $category,
                $inscription->statut_etablissement,
            ))
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
