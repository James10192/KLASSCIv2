<?php

namespace App\Console\Commands;

use App\Services\FraisVariantBackupService;
use Illuminate\Console\Command;

class FraisVariantBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'frais:variant-backup 
                            {action : Action à effectuer: backup, migrate, check, restore}
                            {--backup-path= : Chemin du fichier de backup pour la restoration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestion des variants de frais: backup, migration vers options, vérification et restoration';

    protected FraisVariantBackupService $backupService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FraisVariantBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'backup':
                return $this->handleBackup();
                
            case 'migrate':
                return $this->handleMigrate();
                
            case 'check':
                return $this->handleCheck();
                
            case 'restore':
                return $this->handleRestore();
                
            default:
                $this->error("Action non reconnue: {$action}");
                $this->info("Actions disponibles: backup, migrate, check, restore");
                return 1;
        }
    }
    
    private function handleBackup(): int
    {
        $this->info('🔄 Création du backup des variants...');
        
        $result = $this->backupService->createBackup();
        
        if ($result['success']) {
            $this->info("✅ Backup créé avec succès!");
            $this->line("📁 Chemin: {$result['backup_path']}");
            $this->line("📊 Variants sauvegardés: {$result['variants_count']}");
        } else {
            $this->error("❌ Échec du backup: {$result['message']}");
            return 1;
        }
        
        return 0;
    }
    
    private function handleMigrate(): int
    {
        $this->info('🔄 Vérification de la compatibilité...');
        
        $compatibility = $this->backupService->checkCompatibility();
        
        if (!$compatibility['compatible']) {
            $this->error('❌ Migration impossible:');
            foreach ($compatibility['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
            return 1;
        }
        
        $this->info("✅ Compatibilité OK - {$compatibility['variants_count']} variants à migrer");
        
        if (!$this->confirm('Voulez-vous procéder à la migration des variants vers le système d\'options?')) {
            $this->info('Migration annulée.');
            return 0;
        }
        
        $this->info('🔄 Migration en cours...');
        
        $result = $this->backupService->migrateToOptions();
        
        if ($result['success']) {
            $this->info("✅ Migration terminée!");
            $this->line("📊 Migrés: {$result['migrated_count']}/{$result['total_variants']}");
            
            if (!empty($result['errors'])) {
                $this->warn("⚠️  Erreurs rencontrées:");
                foreach ($result['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }
        } else {
            $this->error("❌ Échec de la migration: {$result['message']}");
            return 1;
        }
        
        return 0;
    }
    
    private function handleCheck(): int
    {
        $this->info('🔍 Vérification de la compatibilité...');
        
        $compatibility = $this->backupService->checkCompatibility();
        
        if ($compatibility['compatible']) {
            $this->info('✅ Le système est compatible pour la migration');
            $this->line("📊 Variants trouvés: {$compatibility['variants_count']}");
        } else {
            $this->warn('⚠️  Problèmes de compatibilité détectés:');
            foreach ($compatibility['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }
        
        return 0;
    }
    
    private function handleRestore(): int
    {
        $backupPath = $this->option('backup-path');
        
        if (!$backupPath) {
            $this->error('❌ Chemin du backup requis. Utilisez --backup-path=/chemin/vers/backup.json');
            return 1;
        }
        
        if (!$this->confirm("Voulez-vous restaurer les variants depuis {$backupPath}?")) {
            $this->info('Restoration annulée.');
            return 0;
        }
        
        $this->info('🔄 Restoration en cours...');
        
        $result = $this->backupService->restoreFromBackup($backupPath);
        
        if ($result['success']) {
            $this->info("✅ Restoration terminée!");
            $this->line("📊 Variants restaurés: {$result['restored_count']}");
        } else {
            $this->error("❌ Échec de la restoration: {$result['message']}");
            return 1;
        }
        
        return 0;
    }
}
