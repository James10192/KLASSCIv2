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

    /**
     * Defait un depot en nature.
     *
     * Le marquage etait a sens unique : une case cochee par erreur — ou par
     * quelqu'un qui s'est trompe d'etudiant — ne se decochait plus, et le frais
     * restait a zero pour toujours. Il fallait alors reprendre la souscription
     * a la main en base.
     *
     * La condition est la meme qu'a l'aller, et pour la meme raison : tant
     * qu'aucun versement n'a ete encaisse sur ce frais, rien n'est fige. Des
     * qu'un paiement valide existe, la situation comptable est etablie et ne se
     * reecrit pas depuis cet ecran.
     *
     * `deposited_at` et `deposited_by` sont effaces : les laisser sur une ligne
     * qui n'est plus deposee ferait mentir la fiche. Qui a depose, quand, et qui
     * a defait, reste inscrit dans le journal d'audit — c'est la sa place.
     */
    public function unmarkDeposited(ESBTPFraisSubscription $subscription, int $userId): ESBTPFraisSubscription
    {
        if (! $subscription->satisfied_in_kind) {
            return $subscription;
        }

        if ($this->hasValidatedPayment((int) $subscription->inscription_id, (int) $subscription->frais_category_id)) {
            throw new InKindDepositForbiddenException(
                'Impossible d\'annuler ce dépôt : un paiement validé existe déjà pour cette catégorie.'
            );
        }

        $subscription->update([
            'satisfied_in_kind' => false,
            'deposited_at' => null,
            'deposited_by' => null,
        ]);

        return $subscription->fresh();
    }

    public function canUnmarkDeposited(ESBTPFraisSubscription $subscription): bool
    {
        return (bool) $subscription->satisfied_in_kind
            && ! $this->hasValidatedPayment(
                (int) $subscription->inscription_id,
                (int) $subscription->frais_category_id,
            );
    }

    public function hasValidatedPayment(int $inscriptionId, int $categoryId): bool
    {
        return $this->paiementsValides($inscriptionId, $categoryId)->exists();
    }

    /**
     * Le versement validé qui empêche de marquer ce frais déposé, s'il y en a un.
     *
     * L'écran le nomme (numéro de reçu, date) au lieu de laisser un « Non
     * déposé » muet : la personne sait ce qui bloque, et ce qu'il faut défaire.
     */
    public function paiementBloquant(int $inscriptionId, int $categoryId): ?ESBTPPaiement
    {
        return $this->paiementsValides($inscriptionId, $categoryId)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->first();
    }

    private function paiementsValides(int $inscriptionId, int $categoryId)
    {
        return ESBTPPaiement::query()
            ->where('inscription_id', $inscriptionId)
            ->where('frais_category_id', $categoryId)
            ->valides()
            ->encaissements();
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
