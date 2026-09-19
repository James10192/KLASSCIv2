<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconstruit le pivot canonique depuis le produit des deux pivots plats.
 *
 * ATTENTION : cette commande ECRIT `esbtp_matiere_filiere_niveau` en `insert()`
 * brut, donc SANS passer par `LiaisonsDeMatiere::poser()` et sans son garde.
 * Elle est le contre-exemple de la phrase « poser() est le goulot unique »,
 * qui a ete ecrite et crue.
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
    protected $signature = 'sync:matiere-filiere-niveau {--dry-run : Afficher sans insérer}';

    protected $description = 'Synchronise esbtp_matiere_filiere_niveau depuis les pivots existantes (intersection filière×niveau par matière)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

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

        // Un ecart qu'on ne dit pas ne se cherche pas. Le chiffre importe :
        // s'il est non nul, des elements LMD gardent des lignes dans les
        // pivots plats, et ce sont elles que cette commande recreerait.
        if ($ecuesEcartees > 0) {
            $this->warn("{$ecuesEcartees} matière(s) écartée(s) : éléments constitutifs LMD, hors maquette BTS.");
        }

        return Command::SUCCESS;
    }
}
