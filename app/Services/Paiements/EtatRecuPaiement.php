<?php

namespace App\Services\Paiements;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use Illuminate\Support\Collection;

class EtatRecuPaiement
{
    /**
     * @return array{
     *     lignes: Collection,
     *     reste: float,
     *     versementsAvant: Collection,
     *     versementsApres: Collection,
     *     affectationLabel: string
     * }
     */
    public function construire(ESBTPPaiement $paiement): array
    {
        $inscriptionId = (int) $paiement->inscription_id;

        $subscriptions = ESBTPFraisSubscription::where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get();

        $payeParCategorie = ESBTPPaiement::netPaidByCategory($inscriptionId);

        $categoriesDeCeVersement = $paiement->relationLoaded('allocations')
            ? $paiement->allocations->pluck('frais_category_id')
            : $paiement->allocations()->pluck('frais_category_id');

        $categoriesDeCeVersement = $categoriesDeCeVersement
            ->push($paiement->frais_category_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $lignes = $subscriptions->map(function ($sub) use ($payeParCategorie, $categoriesDeCeVersement) {
            $due = $sub->chargedAmount();
            $paye = (float) ($payeParCategorie[$sub->frais_category_id] ?? 0);
            $inKind = (bool) $sub->satisfied_in_kind;

            return [
                'name' => $sub->fraisCategory->name ?? 'N/A',
                'restant' => max(0.0, $due - $paye),
                'in_kind' => $inKind,
                'non_configure' => $sub->montantNonDefini(),
                'checked' => $sub->estSolde($paye),
                'current' => $categoriesDeCeVersement->contains((int) $sub->frais_category_id),
            ];
        })->values();

        $reste = (float) $lignes->sum('restant');
        $reste += (float) ESBTPReliquatDetail::where('inscription_destination_id', $inscriptionId)
            ->actifs()
            ->get()
            ->sum(fn ($r) => $r->solde_restant);

        $autres = ESBTPPaiement::encaissements()
            ->where('inscription_id', $inscriptionId)
            ->where('id', '!=', $paiement->id)
            ->whereIn('status', ['validé', 'en_attente'])
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get(['id', 'date_paiement', 'montant', 'numero_recu', 'status']);

        $classes = $this->classerVersements($autres, $paiement);

        return [
            'lignes' => $lignes,
            'reste' => $reste,
            'versementsAvant' => $classes['avant'],
            'versementsApres' => $classes['apres'],
            'affectationLabel' => $paiement->inscription?->affectationStatusLabel() ?? '—',
        ];
    }

    /**
     * @param  Collection<int, ESBTPPaiement>  $autres
     * @return array{avant: Collection, apres: Collection}
     */
    public function classerVersements(Collection $autres, ESBTPPaiement $courant): array
    {
        $date = $courant->date_paiement;
        $id = (int) $courant->id;

        $estAvant = function (ESBTPPaiement $autre) use ($date, $id): bool {
            if ($date === null || $autre->date_paiement === null) {
                return $autre->id < $id;
            }

            if ($autre->date_paiement->lt($date)) {
                return true;
            }

            return $autre->date_paiement->equalTo($date) && $autre->id < $id;
        };

        return [
            'avant' => $autres->filter($estAvant)->values(),
            'apres' => $autres->reject($estAvant)->values(),
        ];
    }
}
