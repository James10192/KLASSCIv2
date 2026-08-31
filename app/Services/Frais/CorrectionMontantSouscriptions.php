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
