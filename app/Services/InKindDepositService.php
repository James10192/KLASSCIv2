<?php

namespace App\Services;

use App\Exceptions\InKindDepositForbiddenException;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\ApplicableFraisResolver;

class InKindDepositService
{
    public function applyDeposits(ESBTPInscription $inscription, array $flags, ?int $userId): void
    {
        foreach ($flags as $categoryId => $value) {
            if (! self::isDepositedFlag($flags, (int) $categoryId)) {
                continue;
            }

            $subscription = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                ->where('frais_category_id', $categoryId)
                ->first();

            if (! $subscription) {
                continue;
            }

            $subscription->loadMissing('fraisCategory');
            if (! $subscription->fraisCategory?->accepts_in_kind) {
                continue;
            }

            $this->stampDeposited($subscription, (int) $userId);
        }
    }

    public function markDepositedFor(ESBTPInscription $inscription, ESBTPFraisCategory $category, int $userId): ESBTPFraisSubscription
    {
        if (! $category->accepts_in_kind) {
            throw new InKindDepositForbiddenException('Cette catégorie n\'accepte pas de dépôt en nature.');
        }

        if (! app(ApplicableFraisResolver::class)->categoryAppliesToStudent(
            $category,
            $inscription->statut_etablissement,
        )) {
            throw new InKindDepositForbiddenException('Ce frais ne s\'applique pas à cet étudiant.');
        }

        $subscription = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('frais_category_id', $category->id)
            ->first();

        if (! $subscription) {
            $subscription = ESBTPFraisSubscription::create([
                'inscription_id' => $inscription->id,
                'frais_category_id' => $category->id,
                'amount' => $this->montantAttendu($inscription, $category),
                'is_active' => true,
                'subscribed_at' => now(),
                'created_by' => $userId,
                'notes' => 'Souscription créée au dépôt en nature',
            ]);
        } elseif (! $subscription->is_active) {
            $subscription->update(['is_active' => true]);
        }

        return $this->markDeposited($subscription, $userId);
    }

    public function canMarkCategory(
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        ?ESBTPFraisSubscription $subscription
    ): bool {
        if (! $category->accepts_in_kind) {
            return false;
        }

        if ($subscription) {
            return $this->canMarkDeposited($subscription);
        }

        if ($this->hasValidatedPayment((int) $inscription->id, (int) $category->id)) {
            return false;
        }

        return app(ApplicableFraisResolver::class)->categoryAppliesToStudent(
            $category,
            $inscription->statut_etablissement,
        );
    }

    public function markDeposited(ESBTPFraisSubscription $subscription, int $userId): ESBTPFraisSubscription
    {
        if ($subscription->satisfied_in_kind) {
            return $subscription;
        }

        $subscription->loadMissing('fraisCategory');

        if (! $subscription->fraisCategory?->accepts_in_kind) {
            throw new InKindDepositForbiddenException('Cette catégorie n\'accepte pas de dépôt en nature.');
        }

        if ($this->hasValidatedPayment((int) $subscription->inscription_id, (int) $subscription->frais_category_id)) {
            throw new InKindDepositForbiddenException('Impossible de marquer un dépôt : un paiement validé existe déjà pour cette catégorie.');
        }

        $this->stampDeposited($subscription, $userId);

        return $subscription->fresh();
    }

    public function hasValidatedPayment(int $inscriptionId, int $categoryId): bool
    {
        return ESBTPPaiement::query()
            ->where('inscription_id', $inscriptionId)
            ->where('frais_category_id', $categoryId)
            ->valides()
            ->encaissements()
            ->exists();
    }

    public function canMarkDeposited(ESBTPFraisSubscription $subscription): bool
    {
        $subscription->loadMissing('fraisCategory');

        return ! $subscription->satisfied_in_kind
            && (bool) $subscription->fraisCategory?->accepts_in_kind
            && ! $this->hasValidatedPayment(
                (int) $subscription->inscription_id,
                (int) $subscription->frais_category_id,
            );
    }

    public static function isDepositedFlag(array $inKindDeposits, int $categoryId): bool
    {
        $value = $inKindDeposits[$categoryId] ?? $inKindDeposits[(string) $categoryId] ?? 0;

        return $value === 1 || $value === '1' || $value === true;
    }

    private function montantAttendu(ESBTPInscription $inscription, ESBTPFraisCategory $category): float
    {
        $fee = app(ApplicableFraisResolver::class)
            ->resolveMandatoryFeesForInscription($inscription)
            ->first(fn (array $row) => (int) $row['category']->id === (int) $category->id);

        return $fee ? (float) $fee['amount'] : (float) ($category->default_amount ?? 0);
    }

    private function stampDeposited(ESBTPFraisSubscription $subscription, int $userId): void
    {
        if ($subscription->satisfied_in_kind) {
            return;
        }

        $subscription->update([
            'satisfied_in_kind' => true,
            'deposited_at' => now(),
            'deposited_by' => $userId,
        ]);
    }
}
