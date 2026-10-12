<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use Illuminate\Support\Collection;

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

        // Même choix que SituationFinanciereEtudiant : une créance reportée
        // reste unique, même lorsque des doublons historiques subsistent.
        $reports = ESBTPReliquatDetail::query()
            ->whereIn('inscription_source_id', $ids)
            ->whereIn('frais_subscription_id', $souscriptions->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('frais_subscription_id')
            ->map(fn ($group) => $group->last());

        $souscriptionsParId = $souscriptions->keyBy('id');

        $lignesSources = $souscriptions
            ->map(function (ESBTPFraisSubscription $souscription) use ($paye, $parInscription, $reports) {
                $inscription = $parInscription->get($souscription->inscription_id);
                $frais = $souscription->fraisCategory;

                if (! $inscription || ! $frais) {
                    return null;
                }

                $montantPaye = (float) ($paye[$souscription->inscription_id][$souscription->frais_category_id] ?? 0);
                $report = $reports->get($souscription->id);
                $montantReporte = (float) ($report?->montant_reliquat ?? 0);
                // La source ne doit plus réclamer la part confiée au reliquat.
                $du = max(0.0, round((float) $souscription->chargedAmount() - $montantReporte, 2));
                $statut = $this->statutSource($souscription, $montantPaye, $du, $montantReporte);

                $etudiant = $inscription->etudiant;

                return [
                    'inscription_id' => $inscription->id,
                    'matricule' => $etudiant->matricule ?? '—',
                    'etudiant' => trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) ?: '—',
                    'classe' => $inscription->classe->name ?? '—',
                    'annee' => $inscription->anneeUniversitaire?->name ?? '—',
                    'frais_id' => $frais->id,
                    'frais' => $frais->name,
                    'ordre_frais' => $frais->sort_order ?? 9999,
                    'du' => $du,
                    'paye' => $montantPaye,
                    'reste' => max(0.0, $du - $montantPaye),
                    'statut' => $statut,
                ];
            })
            ->filter();

        // Les reliquats ne sont pas de nouveaux frais annuels. Leur ligne
        // retrace seulement où se trouve maintenant la créance d'origine.
        // Source (du diminué) + report (du transféré) = montant initial.
        $lignesReports = $reports->map(function (ESBTPReliquatDetail $report) use ($parInscription, $souscriptionsParId) {
            $inscription = $parInscription->get($report->inscription_source_id);
            $frais = $souscriptionsParId->get($report->frais_subscription_id)?->fraisCategory;
            if (! $inscription || ! $frais) {
                return null;
            }

            $du = (float) $report->montant_reliquat;
            $paye = min($du, max(0.0, (float) $report->montant_regle));
            $reste = max(0.0, round($du - $paye, 2));

            return [
                'inscription_id' => $inscription->id,
                'matricule' => $inscription->etudiant?->matricule ?? '—',
                'etudiant' => trim(($inscription->etudiant?->nom ?? '').' '.($inscription->etudiant?->prenoms ?? '')) ?: '—',
                'classe' => $inscription->classe?->name ?? '—',
                'annee' => $inscription->anneeUniversitaire?->name ?? '—',
                'frais_id' => $frais->id,
                'frais' => $frais->name.' (reliquat reporté)',
                'ordre_frais' => $frais->sort_order ?? 9999,
                'du' => $du,
                'paye' => $paye,
                'reste' => $reste,
                'statut' => $reste <= 0 ? 'Soldé' : ($paye > 0 ? 'Partiel' : 'Aucun paiement'),
            ];
        })->filter();

        return $lignesSources->concat($lignesReports)
            ->sortBy([['etudiant', 'asc'], ['ordre_frais', 'asc']])
            ->values();
    }

    /** Statut de la partie qui reste sur l'inscription d'origine. */
    private function statutSource(
        ESBTPFraisSubscription $souscription,
        float $paye,
        float $du,
        float $reporte
    ): string {
        if ($reporte <= 0) {
            return $this->statut($souscription, $paye);
        }
        if ($souscription->satisfied_in_kind) {
            return 'Déposé en nature';
        }
        if ($souscription->montantNonDefini()) {
            return 'Montant non défini';
        }
        if ($du <= 0) {
            return 'Reporté';
        }

        return $paye >= $du ? 'Soldé' : ($paye > 0 ? 'Partiel' : 'Aucun paiement');
    }

    /**
     * Ou en est CE frais pour CET etudiant.
     *
     * A part, et sans base de donnees, parce que c'est la phrase que
     * l'etablissement lira : un montant nul veut dire INCONNU, pas « rien a
     * payer ». Annoncer solde un frais que personne n'a jamais configure
     * ferait croire une dette eteinte.
     */
    public function statut(ESBTPFraisSubscription $souscription, float $montantPaye): string
    {
        return match (true) {
            (bool) $souscription->satisfied_in_kind => 'Déposé en nature',
            $souscription->montantNonDefini() => 'Montant non défini',
            $souscription->estSolde($montantPaye) => 'Soldé',
            $montantPaye > 0 => 'Partiel',
            default => 'Aucun paiement',
        };
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
        // Les versements en attente de validation sont comptes, comme sur la
        // situation financiere de l'etudiant : sans quoi le document repondrait
        // autre chose que ce que l'etudiant lit sur sa propre fiche.
        return app(MontantsParFrais::class)->netParInscriptionEtFrais($inscriptionIds, inclurePending: true);
    }
}
