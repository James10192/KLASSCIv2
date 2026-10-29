<?php

namespace App\Services\Frais;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;

/**
 * Un avoir annule un versement LA OU CE VERSEMENT EST ALLE.
 *
 * L'avoir n'avait jamais d'allocation : seuls les encaissements en recoivent.
 * Il passait donc par la branche « sans allocation » du calcul par categorie,
 * qui l'impute EN ENTIER a sa propre `frais_category_id` — celle heritee du
 * versement d'origine.
 *
 * Versement de 250 000 sur A, reparti A:150 000 / B:100 000. Avoir de 250 000
 * sur A, annulation totale. Le calcul fait `max(0, 150 000 - 250 000)` pour A,
 * soit zero, et le `max` avale l'excedent au lieu de le reporter sur B. B reste
 * a 100 000 : 100 000 F de paiement fantome survivent a un remboursement
 * integral. C'est exactement la classe de defaut que la repartition existe pour
 * corriger, retournee contre elle.
 *
 * DEUX REMEDES ETAIENT POSSIBLES, ON A CHOISI CELUI-CI.
 *
 * L'autre consistait a refuser de repartir un versement qui porte des avoirs.
 * Il ne protege que dans un sens : un avoir s'emet SOUVENT apres la
 * repartition — c'est meme le cas normal, la repartition rattrape des
 * versements anciens et les remboursements viennent ensuite. Le versement
 * porte alors des allocations, l'avoir n'en a pas, et le defaut est identique.
 * Pour le fermer vraiment, il faudrait interdire d'emettre un avoir sur un
 * versement reparti, c'est-a-dire interdire de rembourser un trop-percu :
 * precisement la situation ou les remboursements arrivent. Inacceptable a une
 * caisse.
 *
 * Refleter dit la verite dans les deux sens, quel que soit l'ordre des
 * operations. Un avoir PARTIEL est reparti au prorata : rien dans la donnee ne
 * dit quel frais l'ecole entend rembourser d'abord, et en decider ici
 * imposerait la reponse d'un etablissement a tous les autres.
 *
 * L'ecriture est integralement derivee du parent : elle se rejoue autant de
 * fois qu'on veut et donne toujours le meme etat.
 */
class RefletAllocationsSurAvoirs
{
    /**
     * Remet les allocations de tous les avoirs d'un versement en phase avec les
     * siennes.
     */
    public function refleterSurLesAvoirsDe(ESBTPPaiement $parent): void
    {
        $avoirs = ESBTPPaiement::query()
            ->where('parent_paiement_id', $parent->id)
            ->avoires()
            ->whereIn('status', ['validé', 'en_attente'])
            ->get();

        foreach ($avoirs as $avoir) {
            $this->refleter($avoir, $parent);
        }

        // Un avoir rejete ne compte nulle part : ses allocations n'auraient
        // aucun lecteur, elles ne feraient que du bruit dans les diagnostics.
        ESBTPPaiementAllocation::query()
            ->whereIn('paiement_id', ESBTPPaiement::query()
                ->where('parent_paiement_id', $parent->id)
                ->avoires()
                ->whereNotIn('status', ['validé', 'en_attente'])
                ->select('id'))
            ->delete();
    }

    /**
     * Calque la repartition du versement d'origine sur cet avoir.
     *
     * Sans parent, ou parent non reparti : l'avoir n'a rien a refleter et
     * retrouve son comportement d'avant — sa categorie propre fait foi.
     */
    public function refleter(ESBTPPaiement $avoir, ?ESBTPPaiement $parent = null): void
    {
        $parent ??= $avoir->parentPaiement;

        $partsParent = $parent
            ? ESBTPPaiementAllocation::query()
                ->where('paiement_id', $parent->id)
                ->pluck('montant', 'frais_category_id')
                ->map(fn ($m) => (float) $m)
                ->all()
            : [];

        $parts = $this->auProrata($partsParent, round((float) $avoir->montant, 2));

        ESBTPPaiementAllocation::query()
            ->where('paiement_id', $avoir->id)
            ->when($parts !== [], fn ($q) => $q->whereNotIn('frais_category_id', array_keys($parts)))
            ->delete();

        foreach ($parts as $categoryId => $montant) {
            ESBTPPaiementAllocation::updateOrCreate(
                ['paiement_id' => $avoir->id, 'frais_category_id' => $categoryId],
                ['montant' => $montant]
            );
        }
    }

    /**
     * Repartit un montant au prorata de parts existantes, au centime pres.
     *
     * La somme rendue vaut EXACTEMENT le montant demande : c'est l'invariant sur
     * lequel repose la lecture par allocations, une part perdue a l'arrondi
     * sortirait des totaux sans le dire. Les centimes restants vont aux plus
     * fortes decimales, dans l'ordre des plus grosses parts a egalite.
     *
     * @param  array<int, float>  $parts
     * @return array<int, float>
     */
    private function auProrata(array $parts, float $montant): array
    {
        $total = round(array_sum($parts), 2);

        if ($parts === [] || $total <= 0.009 || $montant <= 0.009) {
            return [];
        }

        $planchers = [];
        $decimales = [];

        foreach ($parts as $categoryId => $part) {
            $exact = $montant * $part / $total;
            $planchers[$categoryId] = floor($exact * 100) / 100;
            $decimales[$categoryId] = $exact * 100 - floor($exact * 100);
        }

        $centimesRestants = (int) round(($montant - array_sum($planchers)) * 100);

        arsort($decimales);

        foreach (array_keys($decimales) as $categoryId) {
            if ($centimesRestants <= 0) {
                break;
            }

            $planchers[$categoryId] = round($planchers[$categoryId] + 0.01, 2);
            $centimesRestants--;
        }

        return array_filter($planchers, fn ($m) => $m > 0.0);
    }
}
