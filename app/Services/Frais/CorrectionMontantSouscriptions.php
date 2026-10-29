<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remplace un montant de souscription saisi par erreur.
 *
 * Une souscription fige le tarif au moment de l'inscription. Corriger le montant
 * d'une CATEGORIE ne rattrape donc jamais les etudiants deja inscrits : ils
 * gardent l'ancienne valeur, et c'est elle que la caisse leur reclame.
 *
 * La regle vit ici, et pas dans la commande artisan, parce qu'elle a deux
 * appelants : la console et l'API CLI. Deux copies d'une regle qui touche a des
 * montants finiraient par diverger, et la divergence ne se verrait que sur
 * l'argent de quelqu'un.
 */
class CorrectionMontantSouscriptions
{
    /**
     * Les montants distincts portes par les souscriptions, et lesquels detonnent.
     *
     * On ne peut corriger que ce qu'on a d'abord vu. Chercher une valeur precise
     * suppose de la connaitre ; ce releve, lui, montre TOUT ce qui existe et
     * signale ce qui ne ressemble pas a un tarif.
     *
     * Le critere de suspicion est celui que l'ecole a donne : un tarif se pose en
     * chiffres ronds. 149 999 et 2 991 ne sont pas des prix, ce sont des prix
     * abimes — par la molette de la souris, en l'occurrence. On signale donc ce
     * qui n'est pas un multiple de 500, seuil au-dessous duquel aucun frais de
     * scolarite ne se negocie, tout en laissant passer les petits montants ronds
     * comme 500 ou 100.
     *
     * @return array{montants: array, suspects: array}
     */
    public function releverLesMontants(): array
    {
        $lignes = ESBTPFraisSubscription::query()
            ->selectRaw('amount, COUNT(*) as total')
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->groupBy('amount')
            ->orderByDesc('amount')
            ->get();

        $montants = [];
        $suspects = [];

        foreach ($lignes as $ligne) {
            $montant = (float) $ligne->amount;
            $entree = ['montant' => $montant, 'souscriptions' => (int) $ligne->total];
            $montants[] = $entree;

            // Multiple de 500, ou petit montant rond : plausible.
            $rond = fmod($montant, 500.0) === 0.0 || ($montant < 1000 && fmod($montant, 100.0) === 0.0);

            if (! $rond) {
                $entree['proche'] = round($montant / 500.0) * 500.0;
                $suspects[] = $entree;
            }
        }

        return ['montants' => $montants, 'suspects' => $suspects];
    }

    /**
     * Ce que la correction ferait, ou ce qu'elle a fait.
     *
     * @return array{montants: array, total: int, dettes_creees: int, lignes: array, applique: bool}
     */
    public function executer(
        float $depuis,
        float $vers,
        bool $appliquer = false,
        ?int $categorieId = null,
        ?int $anneeId = null
    ): array {
        // Egalite EXACTE, et sur elle seule. Une correction en masse qui ratisse
        // large abime plus qu'elle ne repare : on ne touche que les lignes qui
        // portent trait pour trait la valeur erronee, jamais une valeur voisine
        // qu'une ecole aurait choisie.
        $requete = ESBTPFraisSubscription::query()
            ->where('amount', $depuis)
            ->with(['inscription.etudiant', 'fraisCategory']);

        if ($categorieId) {
            $requete->where('frais_category_id', $categorieId);
        }

        if ($anneeId) {
            $requete->whereHas('inscription', fn ($q) => $q->where('annee_universitaire_id', $anneeId));
        }

        $souscriptions = $requete->get();

        $lignes = [];
        $dettes = 0;

        foreach ($souscriptions as $sub) {
            $paye = (float) (ESBTPPaiement::netPaidByCategory((int) $sub->inscription_id)[$sub->frais_category_id] ?? 0);
            $soldeAvant = $depuis - $paye;
            $soldeApres = $vers - $paye;

            // Ceux qui avaient SOLDE l'ancien montant : les faire monter leur
            // cree une dette qu'ils n'ont pas contractee. On ne decide pas a leur
            // place, on le signale.
            $creeUneDette = $soldeAvant <= 0.009 && $soldeApres > 0.009;

            if ($creeUneDette) {
                $dettes++;
            }

            $etudiant = $sub->inscription?->etudiant;

            $lignes[] = [
                'souscription_id' => $sub->id,
                'inscription_id' => $sub->inscription_id,
                'etudiant' => $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : null,
                'matricule' => $etudiant->matricule ?? null,
                'categorie' => $sub->fraisCategory->name ?? null,
                'deja_paye' => $paye,
                'restera_du' => max(0.0, $soldeApres),
                'cree_une_dette' => $creeUneDette,
            ];
        }

        if (! $appliquer || $souscriptions->isEmpty()) {
            return [
                'montants' => ['de' => $depuis, 'vers' => $vers],
                'total' => $souscriptions->count(),
                'dettes_creees' => $dettes,
                'lignes' => $lignes,
                'applique' => false,
            ];
        }

        $corrigees = DB::transaction(function () use ($souscriptions, $vers): int {
            $n = 0;

            foreach ($souscriptions as $sub) {
                // Par le MODELE, pas en requete de masse : il est audite, et une
                // correction de montant doit laisser une trace de qui l'a faite
                // et quand.
                $sub->update(['amount' => $vers]);
                $n++;
            }

            return $n;
        });

        Log::warning('[frais] correction en masse de montants de souscription', [
            'de' => $depuis,
            'vers' => $vers,
            'lignes' => $corrigees,
            'categorie_id' => $categorieId,
            'annee_id' => $anneeId,
        ]);

        return [
            'montants' => ['de' => $depuis, 'vers' => $vers],
            'total' => $corrigees,
            'dettes_creees' => $dettes,
            'lignes' => $lignes,
            'applique' => true,
        ];
    }
}
