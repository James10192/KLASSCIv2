<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconstruit le pivot canonique depuis le produit des deux pivots plats.
 *
 * ELLE SIMULE PAR DEFAUT depuis septembre 2026 : ecrire demande `--ecrire`.
 * Ce qu'elle poserait n'est pas la maquette mais le PRODUIT de deux listes, et
 * elle ne sait qu'ajouter — voir le commentaire de `handle()`.
 *
 * ATTENTION : quand on l'y autorise, elle ECRIT `esbtp_matiere_filiere_niveau`
 * en `insert()` brut, donc SANS passer par `LiaisonsDeMatiere::poser()` et sans
 * son garde. Elle est le contre-exemple de la phrase « poser() est le goulot
 * unique », qui a ete ecrite et crue.
 *
 * Elle recree ce qu'un retrait vient d'enlever, et ce n'est pas theorique :
 * `LiaisonsDeMatiere::retirer()` ne touche VOLONTAIREMENT pas les pivots plats
 * (leur charge utile — coefficient, heures — ne se retrouve nulle part
 * ailleurs). Une ECUE retiree de la maquette garde donc ses lignes plates, et
 * un passage de cette commande la remettrait sur le bulletin. D'ou le garde
 * ci-dessous, qui doit rester aligne sur celui de `poser()`.
 *
 * @see \App\Domain\BtsTroncCommun\LiaisonsDeMatiere::poser()
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 */
class SyncMatiereFilireNiveau extends Command
{
    protected $signature = 'sync:matiere-filiere-niveau
        {--ecrire : Ecrire reellement — sans cette option, la commande simule}
        {--dry-run : Conserve pour les scripts existants ; c\'est deja le defaut}';

    protected $description = 'Simule (ou ecrit avec --ecrire) esbtp_matiere_filiere_niveau depuis le PRODUIT des deux pivots plats';

    public function handle()
    {
        // SIMULATION PAR DEFAUT, ET CE N'EST PAS UNE PRECAUTION DE PRINCIPE.
        //
        // Ce que cette commande ecrit n'est PAS la maquette : c'est le produit
        // cartesien de deux listes independantes (les filieres d'une matiere x
        // ses niveaux). Deux listes ne peuvent decrire qu'un rectangle plein,
        // donc le produit SUR-DECLARE des qu'une matiere est enseignee en
        // (filiere A, niveau 1) et (filiere B, niveau 2) : il fabrique aussi
        // (A,2) et (B,1), que personne n'a demandes.
        //
        // Et elle ne fait qu'AJOUTER — un `insert()` conditionne a l'absence de
        // la ligne, jamais de retrait. C'est un cliquet : chaque passage peut
        // gonfler la maquette, aucun ne peut la degonfler. Combine au fait que
        // `LiaisonsDeMatiere::retirer()` ne nettoie volontairement pas les
        // pivots plats, un passage recree ce qu'un retrait vient d'enlever.
        //
        // Le contraire de ce que fait le reste du depot : `cleanup` LMD et
        // `POST /bts/maquette/retirer` simulent par defaut. Cette commande
        // ecrivait, elle, sans rien demander a personne.
        $dryRun = ! $this->option('ecrire');

        $filieresByMatiere = DB::table('esbtp_matiere_filiere')->get()->groupBy('matiere_id');
        $niveauxByMatiere = DB::table('esbtp_matiere_niveau')->get()->groupBy('matiere_id');

        $inserted = 0;
        $skipped = 0;
        $ecuesEcartees = 0;
        $matiereIds = $filieresByMatiere->keys()->merge($niveauxByMatiere->keys())->unique();

        foreach ($matiereIds as $matiereId) {
            $matiere = DB::table('esbtp_matieres')->find($matiereId);
            if (!$matiere) continue;

            // Une ECUE LMD n'a rien a faire dans le pivot canonique BTS : la
            // ligne la fait sortir sur les bulletins du niveau. Le produit
            // cartesien ci-dessous en fabriquerait une par couple.
            if ($matiere->unite_enseignement_id !== null) {
                $ecuesEcartees++;
                continue;
            }

            $filieres = $filieresByMatiere->get($matiereId, collect())->pluck('filiere_id');
            $niveaux = $niveauxByMatiere->get($matiereId, collect())->pluck('niveau_etude_id');

            foreach ($filieres as $filiereId) {
                foreach ($niveaux as $niveauId) {
                    $exists = DB::table('esbtp_matiere_filiere_niveau')
                        ->where('matiere_id', $matiereId)
                        ->where('filiere_id', $filiereId)
                        ->where('niveau_etude_id', $niveauId)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    if ($dryRun) {
                        $filiere = DB::table('esbtp_filieres')->find($filiereId);
                        $niveau = DB::table('esbtp_niveau_etudes')->find($niveauId);
                        $this->line("  [DRY] {$matiere->name} → {$filiere->name} + {$niveau->name}");
                    } else {
                        DB::table('esbtp_matiere_filiere_niveau')->insert([
                            'matiere_id' => $matiereId,
                            'filiere_id' => $filiereId,
                            'niveau_etude_id' => $niveauId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $inserted++;
                }
            }
        }

        $action = $dryRun ? 'à insérer' : 'insérées';
        $this->info("Terminé : {$inserted} entrées {$action}, {$skipped} déjà existantes.");

        if ($dryRun) {
            $this->warn('SIMULATION — rien n\'a été écrit. Relancez avec --ecrire pour appliquer.');
            $this->line('  Rappel : ces lignes viennent du PRODUIT de deux listes, pas de la maquette.');
            $this->line('  Elles peuvent donc sur-déclarer, et cette commande n\'en retire jamais aucune.');
        }

        // Un ecart qu'on ne dit pas ne se cherche pas. Le chiffre importe :
        // s'il est non nul, des elements LMD gardent des lignes dans les
        // pivots plats, et ce sont elles que cette commande recreerait.
        if ($ecuesEcartees > 0) {
            $this->warn("{$ecuesEcartees} matière(s) écartée(s) : éléments constitutifs LMD, hors maquette BTS.");
        }

        return Command::SUCCESS;
    }
}
