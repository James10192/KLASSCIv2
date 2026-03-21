<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $matiereIds = $filieresByMatiere->keys()->merge($niveauxByMatiere->keys())->unique();

        foreach ($matiereIds as $matiereId) {
            $matiere = DB::table('esbtp_matieres')->find($matiereId);
            if (!$matiere) continue;

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

        return Command::SUCCESS;
    }
}
