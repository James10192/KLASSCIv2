<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\ApplicableFraisResolver;
use App\Services\ESBTPInscriptionService;

class StatutEtablissementFeeSync
{
    public function __construct(
        private readonly ApplicableFraisResolver $resolver,
        private readonly ESBTPInscriptionService $inscriptions,
    ) {
    }

    public function sync(ESBTPInscription $inscription, ?string $previousStatut): array
    {
        $current = $inscription->statut_etablissement;
        if ($previousStatut === $current) {
            return ['added' => 0, 'removed' => 0, 'kept_paid' => 0];
        }

        if ($current === ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU) {
            return $this->attachNouveauxFees($inscription);
        }

        if ($current === ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN) {
            return $this->detachUnpaidNouveauxFees($inscription);
        }

        return ['added' => 0, 'removed' => 0, 'kept_paid' => 0];
    }

    private function attachNouveauxFees(ESBTPInscription $inscription): array
    {
        $fees = $this->resolver->resolveMandatoryFeesForInscription($inscription)
            ->filter(fn (array $fee) => $fee['audience'] === ESBTPFraisCategory::AUDIENCE_NOUVEAUX)
            ->map(fn (array $fee) => [
                'category_id' => $fee['category']->id,
                'description' => $fee['description'],
                'amount' => $fee['amount'],
                'type' => 'mandatory',
            ])
            ->values()
            ->all();

        $this->inscriptions->saveGeneratedFeesAsSubscriptions($inscription, $fees);

        return ['added' => count($fees), 'removed' => 0, 'kept_paid' => 0];
    }

    private function detachUnpaidNouveauxFees(ESBTPInscription $inscription): array
    {
        $removed = 0;
        $keptPaid = 0;

        $subscriptions = ESBTPFraisSubscription::query()
            ->with('fraisCategory')
            ->where('inscription_id', $inscription->id)
            ->get();

        foreach ($subscriptions as $subscription) {
            $category = $subscription->fraisCategory;
            if (! $category || $this->resolver->audienceForInscription($category, $inscription) !== ESBTPFraisCategory::AUDIENCE_NOUVEAUX) {
                continue;
            }

            $paid = ESBTPPaiement::query()
                ->where('inscription_id', $inscription->id)
                ->where('frais_category_id', $subscription->frais_category_id)
                ->encaissements()
                ->exists();

            if ($paid) {
                $keptPaid++;
                continue;
            }

            $subscription->delete();
            $removed++;
        }

        return ['added' => 0, 'removed' => $removed, 'kept_paid' => $keptPaid];
    }
}
