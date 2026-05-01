<?php

namespace App\Actions\Comptabilite;

use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\DTOs\Comptabilite\TotalDuResult;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;

/**
 * Calcule le total dû cohérent avec suivi-catégories : itère inscriptions actives
 * × catégories, avec fallback config/default pour frais obligatoires.
 *
 * Extracted from ESBTPComptabiliteController::calculerTotalDu (legacy private helper).
 */
class CalculerTotalDuAction
{
    private const ACTIVE_INSCRIPTION_STATUSES = ['active', 'en_attente', 'validée'];

    public function __invoke(ComptabiliteFilters $filters): TotalDuResult
    {
        $inscriptions = $this->fetchInscriptions($filters);

        if ($inscriptions->isEmpty()) {
            return TotalDuResult::empty();
        }

        $categories = ESBTPFraisCategory::where('is_active', true)->get();
        $subscriptions = $this->fetchSubscriptionsByInscription($inscriptions->pluck('id')->all());
        $configurations = $this->fetchConfigurationsByKey($categories->pluck('id')->all());

        $totalDue = 0.0;
        $countDue = 0;

        foreach ($inscriptions as $inscription) {
            $perInscription = $this->calculerParInscription(
                $inscription,
                $categories,
                $subscriptions,
                $configurations,
            );
            if ($perInscription > 0) {
                $totalDue += $perInscription;
                $countDue++;
            }
        }

        return new TotalDuResult(totalDue: $totalDue, countDue: $countDue);
    }

    /**
     * Calcul par inscription — exposé pour réutilisation par GetImpayesAgingAction.
     *
     * @param  Collection<int, ESBTPFraisCategory>     $categories
     * @param  Collection<int, Collection>             $subscriptionsByInscription
     * @param  Collection<string, Collection>          $configurations
     */
    public function calculerParInscription(
        ESBTPInscription $inscription,
        Collection $categories,
        Collection $subscriptionsByInscription,
        Collection $configurations,
    ): float {
        $inscriptionSubs = $subscriptionsByInscription->get($inscription->id, collect());
        $totalDu = 0.0;

        foreach ($categories as $category) {
            $sub = $inscriptionSubs->where('frais_category_id', $category->id)->first();
            $montant = $this->resolveMontant($category, $sub, $inscription, $configurations);
            if ($montant > 0) {
                $totalDu += $montant;
            }
        }

        return $totalDu;
    }

    private function fetchInscriptions(ComptabiliteFilters $filters): Collection
    {
        return ESBTPInscription::query()
            ->whereIn('status', self::ACTIVE_INSCRIPTION_STATUSES)
            ->when($filters->anneeId, fn ($q) => $q->where('annee_universitaire_id', $filters->anneeId))
            ->when($filters->filiereId, fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
            ->when($filters->classeId, fn ($q) => $q->where('classe_id', $filters->classeId))
            ->get(['id', 'filiere_id', 'niveau_id', 'affectation_status']);
    }

    private function fetchSubscriptionsByInscription(array $inscriptionIds): Collection
    {
        return ESBTPFraisSubscription::query()
            ->where('is_active', true)
            ->whereIn('inscription_id', $inscriptionIds)
            ->get()
            ->groupBy('inscription_id');
    }

    private function fetchConfigurationsByKey(array $categoryIds): Collection
    {
        return ESBTPFraisConfiguration::query()
            ->where('is_active', true)
            ->whereIn('frais_category_id', $categoryIds)
            ->get()
            ->groupBy(fn ($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);
    }

    private function resolveMontant(
        ESBTPFraisCategory $category,
        ?ESBTPFraisSubscription $sub,
        ESBTPInscription $inscription,
        Collection $configurations,
    ): float {
        if (!$category->is_mandatory) {
            return $sub ? (float) $sub->amount : 0.0;
        }

        if ($sub) {
            return (float) $sub->amount;
        }

        $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
        $config = $configurations->get($configKey, collect())->first();

        if ($config) {
            return (float) $config->getMontantByStatus(
                $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS
            );
        }

        return (float) $category->default_amount;
    }
}
