<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Qui a solde quel frais, et qui doit encore.
 *
 * La question se posait deja categorie par categorie sur
 * `/esbtp/paiements/suivi-categories`, mais elle s'y calcule avec un simple
 * `where('frais_category_id', ...)` — donc en IGNORANT les allocations. Un
 * versement reparti n'y compte que pour le frais que le caissier avait
 * designe, et tous les autres paraissent impayes. Ce service repond a la meme
 * question sans cette erreur, et sur toutes les categories a la fois.
 *
 * Tout se fait en quatre requetes quelle que soit la taille de la promotion :
 * interroger une inscription a la fois en demanderait une dizaine chacune,
 * soit plus de dix mille sur une instance comme Abidjan.
 */
class EtatFinancierParFrais
{
    /**
     * Une ligne par couple (inscription, frais).
     *
     * @param  Collection<int, \App\Models\ESBTPInscription>  $inscriptions
     * @return Collection<int, array>
     */
    public function construire(Collection $inscriptions, ?int $categoryId = null): Collection
    {
        if ($inscriptions->isEmpty()) {
            return collect();
        }

        $ids = $inscriptions->pluck('id')->all();
        $paye = $this->payeParInscriptionEtFrais($ids);
        $parInscription = $inscriptions->keyBy('id');

        $souscriptions = ESBTPFraisSubscription::query()
            ->whereIn('inscription_id', $ids)
            ->where('is_active', true)
            ->when($categoryId, fn ($q) => $q->where('frais_category_id', $categoryId))
            ->with('fraisCategory:id,name,sort_order,category_type')
            ->get();

        return $souscriptions
            ->map(function (ESBTPFraisSubscription $souscription) use ($paye, $parInscription) {
                $inscription = $parInscription->get($souscription->inscription_id);
                $frais = $souscription->fraisCategory;

                if (! $inscription || ! $frais) {
                    return null;
                }

                $montantPaye = (float) ($paye[$souscription->inscription_id][$souscription->frais_category_id] ?? 0);
                $du = (float) $souscription->chargedAmount();

                // Un montant nul veut dire INCONNU, pas « rien a payer ». Le
                // dire, plutot que d'annoncer solde un frais que personne n'a
                // jamais configure.
                $statut = match (true) {
                    (bool) $souscription->satisfied_in_kind => 'Déposé en nature',
                    $souscription->montantNonDefini() => 'Montant non défini',
                    $souscription->estSolde($montantPaye) => 'Soldé',
                    $montantPaye > 0 => 'Partiel',
                    default => 'Aucun paiement',
                };

                $etudiant = $inscription->etudiant;

                return [
                    'inscription_id' => $inscription->id,
                    'matricule' => $etudiant->matricule ?? '—',
                    'etudiant' => trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) ?: '—',
                    'classe' => $inscription->classe->name ?? '—',
                    'frais_id' => $frais->id,
                    'frais' => $frais->name,
                    'ordre_frais' => $frais->sort_order ?? 9999,
                    'du' => $du,
                    'paye' => $montantPaye,
                    'reste' => max(0.0, $du - $montantPaye),
                    'statut' => $statut,
                ];
            })
            ->filter()
            ->sortBy([
                ['etudiant', 'asc'],
                ['ordre_frais', 'asc'],
            ])
            ->values();
    }

    /**
     * Ce que chaque frais a recu, pour chaque inscription.
     *
     * Meme regle que {@see ESBTPPaiement::netPaidByCategory()} — les
     * allocations quand il y en a, la categorie propre sinon, moins les
     * remboursements — mais pour tout un lot d'inscriptions d'un coup.
     *
     * Les versements en attente de validation sont comptes, comme sur la
     * situation financiere de l'etudiant : sans quoi le document repondrait
     * autre chose que ce que l'etudiant lit sur sa propre fiche.
     *
     * @param  array<int, int>  $inscriptionIds
     * @return array<int, array<int, float>>
     */
    private function payeParInscriptionEtFrais(array $inscriptionIds): array
    {
        $encaisse = $this->totauxParNature($inscriptionIds, 'encaissements', ['validé', 'en_attente']);
        // Un remboursement pas encore valide n'a pas quitte la caisse.
        $rembourse = $this->totauxParNature($inscriptionIds, 'avoires', ['validé']);

        foreach ($rembourse as $inscriptionId => $parFrais) {
            foreach ($parFrais as $fraisId => $montant) {
                $encaisse[$inscriptionId][$fraisId] = max(
                    0.0,
                    (float) ($encaisse[$inscriptionId][$fraisId] ?? 0) - (float) $montant
                );
            }
        }

        return $encaisse;
    }

    /**
     * @param  array<int, int>  $inscriptionIds
     * @param  array<int, string>  $statuts
     * @return array<int, array<int, float>>
     */
    private function totauxParNature(array $inscriptionIds, string $nature, array $statuts): array
    {
        $base = fn () => ESBTPPaiement::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->whereIn('status', $statuts)
            ->{$nature}()
            ->horsReliquat();

        $totaux = [];

        // 1. Les versements repartis : ce sont leurs allocations qui disent ou
        //    l'argent est alle.
        $parAllocation = ESBTPPaiementAllocation::query()
            ->join('esbtp_paiements', 'esbtp_paiements.id', '=', 'esbtp_paiement_allocations.paiement_id')
            ->whereIn('esbtp_paiement_allocations.paiement_id', $base()->select('esbtp_paiements.id'))
            ->groupBy('esbtp_paiements.inscription_id', 'esbtp_paiement_allocations.frais_category_id')
            ->selectRaw('esbtp_paiements.inscription_id as inscription_id, esbtp_paiement_allocations.frais_category_id as frais_category_id, SUM(esbtp_paiement_allocations.montant) as total')
            ->get();

        foreach ($parAllocation as $ligne) {
            $totaux[(int) $ligne->inscription_id][(int) $ligne->frais_category_id] = (float) $ligne->total;
        }

        // 2. Les versements non repartis : leur propre categorie fait foi. Les
        //    deux ensembles sont disjoints, donc rien n'est compte deux fois.
        $sansAllocation = $base()
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('esbtp_paiement_allocations')
                    ->whereColumn('esbtp_paiement_allocations.paiement_id', 'esbtp_paiements.id');
            })
            ->whereNotNull('frais_category_id')
            ->groupBy('inscription_id', 'frais_category_id')
            ->selectRaw('inscription_id, frais_category_id, SUM(montant) as total')
            ->get();

        foreach ($sansAllocation as $ligne) {
            $inscriptionId = (int) $ligne->inscription_id;
            $fraisId = (int) $ligne->frais_category_id;
            $totaux[$inscriptionId][$fraisId] = (float) ($totaux[$inscriptionId][$fraisId] ?? 0)
                + (float) $ligne->total;
        }

        return $totaux;
    }
}
