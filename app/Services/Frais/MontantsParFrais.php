<?php

namespace App\Services\Frais;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ou est alle l'argent d'un versement.
 *
 * UNE SEULE regle, et elle vit ici : un versement qui porte des allocations est
 * lu PAR ses allocations ; un versement qui n'en porte pas est lu par sa propre
 * `frais_category_id`. Les deux ensembles sont disjoints, donc rien n'est
 * jamais compte deux fois.
 *
 * Cette regle s'etait retrouvee recopiee en cinq endroits — le total par
 * categorie, le filtre de la liste, la part d'un versement, les indicateurs,
 * l'etat financier — chacun accompagne d'un commentaire affirmant qu'il fallait
 * qu'ils restent identiques. Un commentaire n'est pas un mecanisme, et il
 * s'agit de dire a une ecole qui lui doit combien : si l'un des cinq derivait,
 * quatre ecrans se mettraient a repondre quatre choses differentes sans que
 * rien ne le signale. Il n'y a donc plus qu'une implementation, et les
 * anciennes portes d'entree ne font que la reexposer.
 */
class MontantsParFrais
{
    /**
     * Restreint une requete de versements a ceux qui concernent CE frais.
     *
     * Filtrer naivement sur `frais_category_id` ne montrerait que les
     * versements etiquetes ainsi au guichet, en cachant tous ceux qui ont paye
     * ce frais en passant.
     */
    public function filtrer(Builder $query, int $categoryId): Builder
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->whereHas(
                'allocations',
                fn ($allocation) => $allocation->where('frais_category_id', $categoryId)
            )->orWhere(
                fn ($sansAllocation) => $sansAllocation
                    ->where('frais_category_id', $categoryId)
                    ->whereDoesntHave('allocations')
            );
        });
    }

    /**
     * Ce que chaque frais a recu, sur un ensemble de versements deja borne.
     *
     * @return Collection<int, float> [frais_id => montant]
     */
    public function parFrais(Builder $query): Collection
    {
        return collect($this->agreger($query, false));
    }

    /**
     * Idem, ventile aussi par inscription — pour traiter toute une promotion
     * sans repartir en base une fois par etudiant.
     *
     * @return array<int, array<int, float>> [inscription_id][frais_id] => montant
     */
    public function parInscriptionEtFrais(Builder $query): array
    {
        return $this->agreger($query, true);
    }

    /**
     * Ce qu'UN versement a porte sur CE frais.
     *
     * Le montant du versement n'est pas ce que le frais a recu des lors qu'il a
     * ete reparti : afficher 255 000 F sur une ligne filtree « Tenue » ferait
     * croire que la tenue a encaisse 255 000 F.
     */
    public function part(ESBTPPaiement $paiement, int $categoryId): float
    {
        $lignes = $this->ventilation($paiement);

        return (float) $lignes->where('frais_id', $categoryId)->sum('montant');
    }

    /**
     * La ventilation d'un versement, sous une forme affichable.
     *
     * Rend TOUJOURS au moins une ligne : un versement non reparti vaut sa
     * propre categorie. Les surfaces qui l'affichent (ligne de liste, PDF,
     * Excel, recu) n'ont donc plus a redecouvrir le repli elles-memes — c'est
     * ce repli, recopie partout, qui pouvait diverger.
     *
     * @return Collection<int, array{frais_id: int|null, nom: string, montant: float, type: string}>
     */
    public function ventilation(ESBTPPaiement $paiement): Collection
    {
        $allocations = $paiement->relationLoaded('allocations')
            ? $paiement->allocations
            : $paiement->allocations()->with('fraisCategory:id,name,category_type')->get();

        if ($allocations->isNotEmpty()) {
            return $allocations->map(fn ($ligne) => [
                'frais_id' => (int) $ligne->frais_category_id,
                'nom' => $ligne->fraisCategory->name ?? 'Frais supprimé',
                'montant' => (float) $ligne->montant,
                'type' => $ligne->fraisCategory->category_type ?? 'academic',
            ])->values();
        }

        return collect([[
            'frais_id' => $paiement->frais_category_id ? (int) $paiement->frais_category_id : null,
            'nom' => $paiement->fraisCategory->name
                ?? $paiement->categorie->nom
                ?? ($paiement->motif ?: 'Paiement'),
            'montant' => (float) $paiement->montant,
            'type' => $paiement->fraisCategory->category_type ?? 'academic',
        ]]);
    }

    /**
     * Le coeur de la regle, ecrit une fois.
     *
     * @return array<int, mixed>
     */
    private function agreger(Builder $query, bool $parInscription): array
    {
        $totaux = [];

        // 1. Les versements repartis : leurs allocations disent ou l'argent est alle.
        $allocations = ESBTPPaiementAllocation::query()
            ->join('esbtp_paiements', 'esbtp_paiements.id', '=', 'esbtp_paiement_allocations.paiement_id')
            ->whereIn('esbtp_paiement_allocations.paiement_id', (clone $query)->select('esbtp_paiements.id'))
            ->when(
                $parInscription,
                fn ($q) => $q->groupBy('esbtp_paiements.inscription_id', 'esbtp_paiement_allocations.frais_category_id')
                    ->selectRaw('esbtp_paiements.inscription_id as inscription_id, esbtp_paiement_allocations.frais_category_id as frais_category_id, SUM(esbtp_paiement_allocations.montant) as total'),
                fn ($q) => $q->groupBy('esbtp_paiement_allocations.frais_category_id')
                    ->selectRaw('esbtp_paiement_allocations.frais_category_id as frais_category_id, SUM(esbtp_paiement_allocations.montant) as total')
            )
            ->get();

        // 2. Les versements non repartis : leur propre categorie fait foi.
        $propres = (clone $query)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('esbtp_paiement_allocations')
                    ->whereColumn('esbtp_paiement_allocations.paiement_id', 'esbtp_paiements.id');
            })
            ->whereNotNull('frais_category_id')
            ->when(
                $parInscription,
                fn ($q) => $q->groupBy('inscription_id', 'frais_category_id')
                    ->selectRaw('inscription_id, frais_category_id, SUM(montant) as total'),
                fn ($q) => $q->groupBy('frais_category_id')
                    ->selectRaw('frais_category_id, SUM(montant) as total')
            )
            ->get();

        foreach ([$allocations, $propres] as $lot) {
            foreach ($lot as $ligne) {
                $fraisId = (int) $ligne->frais_category_id;

                if ($parInscription) {
                    $inscriptionId = (int) $ligne->inscription_id;
                    $totaux[$inscriptionId][$fraisId] = (float) ($totaux[$inscriptionId][$fraisId] ?? 0)
                        + (float) $ligne->total;

                    continue;
                }

                $totaux[$fraisId] = (float) ($totaux[$fraisId] ?? 0) + (float) $ligne->total;
            }
        }

        return $totaux;
    }
}
