<?php

namespace App\Services;

use App\Exceptions\InKindDepositForbiddenException;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;

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
     * L'etudiant a-t-il DEJA apporte l'article qui solde ce frais ?
     *
     * L'image inverse de {@see self::hasValidatedPayment()} : celle-la interdit
     * de marquer un depot quand l'argent est deja entre, celle-ci interdit de
     * faire entrer l'argent quand l'article est deja arrive. Le lui faire payer
     * en especes apres qu'il a apporte sa ramette, c'est le faire payer deux
     * fois.
     *
     * Elle vit ici parce qu'un frais depose en nature doit etre refuse a TOUS
     * les chemins qui dirigent de l'argent vers lui — l'encaissement comme la
     * correction d'un versement en attente. La regle vivait au depart dans un
     * gabarit d'affichage : le tableau de la fiche d'inscription masquait le
     * bouton « Payer », la vue en cartes servie sur les ecrans etroits ne le
     * faisait pas, et le serveur ne verifiait rien. Une garde qui ne vit que
     * dans une vue n'est pas une garde ; une garde recopiee dans un seul des
     * deux chemins d'ecriture non plus.
     *
     * `satisfied_in_kind` ne compte que sur une souscription ACTIVE : un frais
     * sans souscription, ou dont la souscription a ete desactivee, n'a rien
     * depose et reste encaissable.
     */
    public function estDeposeEnNature(int $inscriptionId, ?int $categoryId): bool
    {
        if (! $categoryId) {
            return false;
        }

        return ESBTPFraisSubscription::query()
            ->forInscription($inscriptionId)
            ->forCategory($categoryId)
            ->active()
            ->where('satisfied_in_kind', true)
            ->exists();
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
