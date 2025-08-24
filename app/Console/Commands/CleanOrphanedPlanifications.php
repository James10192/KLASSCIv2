<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPMatiere;

class CleanOrphanedPlanifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'planning:clean-orphaned 
                            {--dry-run : Afficher les planifications orphelines sans les supprimer}
                            {--backup : Créer un backup avant suppression}
                            {--force : Supprimer sans confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les planifications orphelines (matières non liées aux bonnes filières/niveaux)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧹 Nettoyage des planifications orphelines');
        $this->line('═══════════════════════════════════════════');

        $dryRun = $this->option('dry-run');
        $backup = $this->option('backup');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🔍 Mode DRY-RUN activé - Aucune suppression ne sera effectuée');
        }

        // Analyser les planifications
        $this->line('📊 Analyse des planifications...');
        
        $allPlanifications = ESBTPPlanificationAcademique::with(['matiere', 'filiere', 'niveauEtude'])->get();
        $orphanedPlanifications = collect();
        $validPlanifications = collect();

        foreach ($allPlanifications as $planif) {
            if (!$planif->matiere) {
                $orphanedPlanifications->push($planif);
                continue;
            }

            $isLinked = ESBTPMatiere::where('id', $planif->matiere->id)
                ->where('is_active', true)
                ->whereHas('filieres', function($query) use ($planif) {
                    $query->where('esbtp_filieres.id', $planif->filiere_id);
                })
                ->whereHas('niveaux', function($query) use ($planif) {
                    $query->where('esbtp_niveau_etudes.id', $planif->niveau_etude_id);
                })
                ->exists();

            if ($isLinked) {
                $validPlanifications->push($planif);
            } else {
                $orphanedPlanifications->push($planif);
            }
        }

        // Afficher les résultats
        $totalOrphanedHours = $orphanedPlanifications->sum('volume_horaire_total');
        
        $this->table(
            ['Statut', 'Nombre', 'Heures totales'],
            [
                ['Planifications valides', $validPlanifications->count(), $validPlanifications->sum('volume_horaire_total') . 'h'],
                ['Planifications orphelines', $orphanedPlanifications->count(), $totalOrphanedHours . 'h'],
                ['TOTAL', $allPlanifications->count(), $allPlanifications->sum('volume_horaire_total') . 'h']
            ]
        );

        if ($orphanedPlanifications->isEmpty()) {
            $this->info('✅ Aucune planification orpheline trouvée !');
            return 0;
        }

        // Afficher les détails des planifications orphelines
        $this->warn("🗑️  Planifications orphelines détectées ({$orphanedPlanifications->count()}):");
        
        $grouped = $orphanedPlanifications->groupBy(function($planif) {
            return ($planif->filiere->name ?? "Filière ID {$planif->filiere_id}") . 
                   ' + ' . 
                   ($planif->niveauEtude->name ?? "Niveau ID {$planif->niveau_etude_id}");
        });

        foreach ($grouped as $combo => $planifs) {
            $this->line("  📋 <comment>{$combo}</comment>:");
            foreach ($planifs as $planif) {
                $matiereName = $planif->matiere->name ?? 'MATIÈRE SUPPRIMÉE';
                $this->line("    - {$matiereName}: {$planif->volume_horaire_total}h (ID: {$planif->id})");
            }
        }

        if ($dryRun) {
            $this->info('🔍 Mode DRY-RUN: Aucune suppression effectuée');
            return 0;
        }

        // Backup si demandé
        if ($backup) {
            $this->line('📥 Création du backup...');
            $backupFile = storage_path('app/backups/planifications_backup_' . date('Y-m-d_H-i-s') . '.json');
            
            if (!is_dir(dirname($backupFile))) {
                mkdir(dirname($backupFile), 0755, true);
            }
            
            file_put_contents($backupFile, $orphanedPlanifications->toJson(JSON_PRETTY_PRINT));
            $this->info("✅ Backup créé: {$backupFile}");
        }

        // Confirmation
        if (!$force) {
            if (!$this->confirm("⚠️  Voulez-vous supprimer ces {$orphanedPlanifications->count()} planifications orphelines ? Cette action est irréversible.")) {
                $this->info('❌ Suppression annulée');
                return 0;
            }
        }

        // Suppression
        $this->line('🗑️  Suppression en cours...');
        $deletedCount = 0;
        $progressBar = $this->output->createProgressBar($orphanedPlanifications->count());

        foreach ($orphanedPlanifications as $planif) {
            try {
                $planif->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Erreur lors de la suppression de la planification {$planif->id}: " . $e->getMessage());
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✅ Nettoyage terminé !');
        $this->table(
            ['Résultat', 'Valeur'],
            [
                ['Planifications supprimées', $deletedCount],
                ['Heures fantômes éliminées', $totalOrphanedHours . 'h'],
                ['Backup créé', $backup ? 'Oui' : 'Non']
            ]
        );

        $this->line('🎯 Les statistiques des cartes sont maintenant correctes !');
        return 0;
    }
}
