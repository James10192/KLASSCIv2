<?php

namespace App\Console\Commands;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Corrige en masse un montant de souscription saisi par erreur.
 *
 * Une souscription fige le tarif au moment de l'inscription. Corriger le montant
 * d'une CATEGORIE ne rattrape donc jamais les etudiants deja inscrits : ils
 * gardent l'ancienne valeur, et c'est elle que la caisse leur reclame.
 *
 * Cas fondateur (ISLG, 31 aout 2026) : la molette de la souris avait decremente
 * deux champs de montant a la saisie — 3 000 devenus 2 991, 150 000 devenus
 * 149 999. Les categories ont ete corrigees, mais les souscriptions creees entre
 * temps portaient toujours l'erreur.
 *
 * Les montants ne sont PAS codes ici : ils se passent en arguments. Six ecoles
 * tournent sur cette base de code, et aucune n'a les memes tarifs.
 */
class CorrigerMontantSouscriptions extends Command
{
    protected $signature = 'frais:corriger-souscriptions
        {--from= : Le montant errone a remplacer, au centime pres}
        {--to= : Le montant correct}
        {--categorie= : Restreindre a une categorie de frais (id)}
        {--annee= : Restreindre a une annee universitaire (id)}
        {--apply : Ecrire reellement. Sans ce drapeau, la commande ne fait que montrer}';

    protected $description = "Remplace un montant de souscription errone par le bon, apres l'avoir montre";

    public function handle(): int
    {
        $depuis = $this->option('from');
        $vers = $this->option('to');

        if ($depuis === null || $vers === null) {
            $this->error('--from et --to sont obligatoires. Rien n\'est devine ici : ce sont des montants.');

            return self::FAILURE;
        }

        $depuis = (float) $depuis;
        $vers = (float) $vers;

        if ($depuis === $vers) {
            $this->error('--from et --to sont identiques. Il n\'y a rien a corriger.');

            return self::FAILURE;
        }

        // Egalite EXACTE sur l'ancien montant, et sur lui seul.
        //
        // Une correction en masse qui ratisse large abime plus qu'elle ne repare :
        // on ne touche que les lignes qui portent trait pour trait la valeur
        // erronee, jamais une valeur voisine qu'une ecole aurait choisie.
        $requete = ESBTPFraisSubscription::query()
            ->where('amount', $depuis)
            ->with(['inscription.etudiant', 'fraisCategory']);

        if ($this->option('categorie')) {
            $requete->where('frais_category_id', (int) $this->option('categorie'));
        }

        if ($this->option('annee')) {
            $requete->whereHas('inscription', fn ($q) => $q->where('annee_universitaire_id', (int) $this->option('annee')));
        }

        $souscriptions = $requete->get();

        if ($souscriptions->isEmpty()) {
            $this->info(sprintf('Aucune souscription a %s. Rien a faire.', $this->somme($depuis)));

            return self::SUCCESS;
        }

        $this->line('');
        $this->info(sprintf(
            '%d souscription(s) a %s -> %s',
            $souscriptions->count(),
            $this->somme($depuis),
            $this->somme($vers)
        ));
        $this->line('');

        $creeraientUneDette = [];
        $lignes = [];

        foreach ($souscriptions as $sub) {
            $paye = (float) (ESBTPPaiement::netPaidByCategory((int) $sub->inscription_id)[$sub->frais_category_id] ?? 0);

            // Relever ceux qui avaient deja solde l'ancien montant : les faire
            // monter leur cree une dette qu'ils n'ont pas contractee. On ne
            // decide pas a leur place, on le signale.
            $soldeAvant = $depuis - $paye;
            $soldeApres = $vers - $paye;

            if ($soldeAvant <= 0.009 && $soldeApres > 0.009) {
                $creeraientUneDette[] = $sub;
            }

            $etudiant = $sub->inscription?->etudiant;

            $lignes[] = [
                $sub->id,
                $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : '(etudiant introuvable)',
                $sub->fraisCategory->name ?? '(categorie introuvable)',
                $this->somme($paye),
                $soldeApres > 0.009 ? $this->somme($soldeApres) : 'solde',
            ];
        }

        $this->table(['id', 'Etudiant', 'Categorie', 'Deja paye', 'Restera du'], $lignes);

        if ($creeraientUneDette !== []) {
            $this->line('');
            $this->warn(sprintf(
                '%d etudiant(s) avaient solde l\'ancien montant. La correction leur cree une dette de %s.',
                count($creeraientUneDette),
                $this->somme($vers - $depuis)
            ));
            $this->warn('Verifiez que c\'est bien ce que l\'ecole veut avant d\'appliquer.');
        }

        if (! $this->option('apply')) {
            $this->line('');
            $this->info('Rien n\'a ete ecrit. Relancez avec --apply pour appliquer.');

            return self::SUCCESS;
        }

        $corrigees = DB::transaction(function () use ($souscriptions, $vers): int {
            $n = 0;

            foreach ($souscriptions as $sub) {
                // Par le MODELE, pas en requete de masse : le modele est audite,
                // et une correction de montant doit laisser une trace de qui l'a
                // faite et quand.
                $sub->update(['amount' => $vers]);
                $n++;
            }

            return $n;
        });

        Log::warning('[frais] correction en masse de montants de souscription', [
            'de' => $depuis,
            'vers' => $vers,
            'lignes' => $corrigees,
            'categorie' => $this->option('categorie'),
            'annee' => $this->option('annee'),
            'par' => 'console',
        ]);

        $this->line('');
        $this->info(sprintf('%d souscription(s) corrigee(s).', $corrigees));

        return self::SUCCESS;
    }

    private function somme(float $montant): string
    {
        return number_format($montant, 0, ',', ' ').' FCFA';
    }
}
