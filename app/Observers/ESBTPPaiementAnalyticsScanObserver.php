<?php

namespace App\Observers;

use App\Models\ESBTPPaiement;
use App\Services\Analytics\AnalyticsScanCache;

/**
 * Déréférence les balayages analytiques mémorisés dès qu'un paiement validé
 * apparaît, change ou disparaît.
 *
 * Pourquoi cela ne peut pas attendre l'expiration : l'allocation FIFO impute un
 * encaissement à la tranche la plus ancienne encore due. Un paiement saisi
 * aujourd'hui réduit donc l'écart de recouvrement d'un mois déjà clos — le passé
 * bouge, contrairement à ce que le mot « clos » laisse croire.
 *
 * Pourquoi seulement le statut « validé » : un paiement en attente ou rejeté
 * n'est jamais alloué, il ne déplace aucun montant. Purger sur sa création
 * refroidirait le cache sans qu'aucun chiffre n'ait changé.
 */
class ESBTPPaiementAnalyticsScanObserver
{
    private const STATUT_ALLOUE = 'validé';

    public function __construct(
        private readonly AnalyticsScanCache $scanCache,
    ) {}

    public function created(ESBTPPaiement $paiement): void
    {
        if ($this->estAlloue($paiement->status)) {
            $this->scanCache->invalidate();
        }
    }

    public function updated(ESBTPPaiement $paiement): void
    {
        // Le montant ou la date peuvent changer sans que le statut bouge : il
        // faut aussi purger quand un paiement DÉJÀ validé est corrigé.
        $ancienStatut = $paiement->getOriginal('status');

        if ($this->estAlloue($paiement->status) || $this->estAlloue($ancienStatut)) {
            $this->scanCache->invalidate();
        }
    }

    public function deleted(ESBTPPaiement $paiement): void
    {
        if ($this->estAlloue($paiement->status)) {
            $this->scanCache->invalidate();
        }
    }

    public function restored(ESBTPPaiement $paiement): void
    {
        if ($this->estAlloue($paiement->status)) {
            $this->scanCache->invalidate();
        }
    }

    private function estAlloue(mixed $statut): bool
    {
        return is_string($statut) && $statut === self::STATUT_ALLOUE;
    }
}
