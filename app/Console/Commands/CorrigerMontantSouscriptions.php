<?php

namespace App\Console\Commands;

use App\Services\Frais\CorrectionMontantSouscriptions;
use Illuminate\Console\Command;

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

        // La regle vit dans le service : la console et l'API CLI l'appellent
        // toutes deux, et deux copies d'une regle qui touche a des montants
        // finiraient par diverger — sans que rien ne le dise, sinon l'argent de
        // quelqu'un.
        $resultat = app(CorrectionMontantSouscriptions::class)->executer(
            $depuis,
            $vers,
            (bool) $this->option('apply'),
            $this->option('categorie') ? (int) $this->option('categorie') : null,
            $this->option('annee') ? (int) $this->option('annee') : null,
        );

        if ($resultat['total'] === 0) {
            $this->info(sprintf('Aucune souscription a %s. Rien a faire.', $this->somme($depuis)));

            return self::SUCCESS;
        }

        $this->line('');
        $this->info(sprintf(
            '%d souscription(s) a %s -> %s',
            $resultat['total'],
            $this->somme($depuis),
            $this->somme($vers)
        ));
        $this->line('');

        $this->table(
            ['id', 'Matricule', 'Etudiant', 'Categorie', 'Deja paye', 'Restera du'],
            array_map(fn (array $l) => [
                $l['souscription_id'],
                $l['matricule'] ?? '—',
                $l['etudiant'] ?? '(introuvable)',
                $l['categorie'] ?? '(introuvable)',
                $this->somme((float) $l['deja_paye']),
                $l['restera_du'] > 0.009 ? $this->somme((float) $l['restera_du']) : 'solde',
            ], $resultat['lignes'])
        );

        if ($resultat['dettes_creees'] > 0) {
            $this->line('');
            $this->warn(sprintf(
                "%d etudiant(s) avaient solde l'ancien montant. La correction leur cree une dette de %s.",
                $resultat['dettes_creees'],
                $this->somme($vers - $depuis)
            ));
            $this->warn("Verifiez que c'est bien ce que l'ecole veut avant d'appliquer.");
        }

        if (! $resultat['applique']) {
            $this->line('');
            $this->info("Rien n'a ete ecrit. Relancez avec --apply pour appliquer.");

            return self::SUCCESS;
        }

        $this->line('');
        $this->info(sprintf('%d souscription(s) corrigee(s).', $resultat['total']));

        return self::SUCCESS;
    }

    private function somme(float $montant): string
    {
        return number_format($montant, 0, ',', ' ').' FCFA';
    }
}
