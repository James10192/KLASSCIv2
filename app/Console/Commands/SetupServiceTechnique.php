<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupServiceTechnique extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:service-technique
                            {--reset : Réinitialiser complètement les permissions}
                            {--user-only : Créer seulement les utilisateurs service technique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure le système de permissions et crée les comptes Service Technique ADC';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configuration du Service Technique African Digit Consulting');
        $this->line('');

        try {
            if ($this->option('reset')) {
                $this->warn('⚠️  Réinitialisation complète des permissions...');

                if ($this->confirm('Êtes-vous sûr de vouloir réinitialiser toutes les permissions ?')) {
                    $this->call('permission:cache-reset');
                    $this->info('✅ Cache des permissions réinitialisé');
                }
            }

            if (!$this->option('user-only')) {
                $this->info('🔧 Exécution du script de permissions...');

                // Exécuter le script PHP de permissions
                $scriptPath = base_path('fix_permissions.php');
                if (file_exists($scriptPath)) {
                    $this->line('Exécution de fix_permissions.php...');
                    exec("php {$scriptPath}", $output, $returnCode);

                    if ($returnCode === 0) {
                        $this->info('✅ Permissions configurées avec succès');
                        foreach ($output as $line) {
                            $this->line($line);
                        }
                    } else {
                        $this->error('❌ Erreur lors de l\'exécution du script de permissions');
                        return 1;
                    }
                } else {
                    $this->error('❌ Script fix_permissions.php non trouvé');
                    return 1;
                }
            }

            $this->info('👤 Création des comptes Service Technique...');
            $this->call('db:seed', ['--class' => 'ServiceTechniqueSeeder']);

            $this->line('');
            $this->info('🎉 Configuration terminée avec succès !');
            $this->line('');

            $this->table(
                ['Type', 'Email', 'Mot de passe', 'Rôle'],
                [
                    ['Principal', 'technique@africandigitconsulting.com', 'ADC2024Tech!SecurePass', 'serviceTechnique'],
                    ['Backup', 'support@africandigitconsulting.com', 'ADCSupport2024!Backup', 'serviceTechnique']
                ]
            );

            $this->warn('⚠️  IMPORTANT : Changez ces mots de passe en production !');
            $this->info('🔒 Les comptes Service Technique ont accès à la configuration paywall');

        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}