<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ServiceTechniqueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Création du compte Service Technique African Digit Consulting...');

        // Vérifier si le rôle serviceTechnique existe
        $serviceTechniqueRole = Role::firstOrCreate([
            'name' => 'serviceTechnique'
        ]);

        // Créer les permissions spéciales si elles n'existent pas
        $specialPermissions = [
            'paywall.configure',
            'paywall.manage_subscriptions',
            'paywall.extend_subscriptions',
            'paywall.view_all_stats',
            'system.technical_access',
            'system.emergency_override',
        ];

        foreach ($specialPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Attribuer TOUTES les permissions au rôle serviceTechnique
        $allPermissions = Permission::all();
        $serviceTechniqueRole->syncPermissions($allPermissions);

        // Informations du compte service technique
        $serviceTechniqueData = [
            'name' => 'Service Technique ADC',
            'username' => 'service.technique.adc',
            'email' => 'technique@africandigitconsulting.com',
            'password' => Hash::make('ADC2024Tech!SecurePass'), // Mot de passe sécurisé
            'email_verified_at' => now(),
        ];

        // Créer ou mettre à jour le compte
        $serviceTechniqueUser = User::firstOrCreate(
            ['email' => $serviceTechniqueData['email']],
            $serviceTechniqueData
        );

        // Attribuer le rôle serviceTechnique
        if (!$serviceTechniqueUser->hasRole('serviceTechnique')) {
            $serviceTechniqueUser->assignRole('serviceTechnique');
        }

        // Créer un compte backup/secondaire
        $backupServiceTechniqueData = [
            'name' => 'Service Technique ADC (Backup)',
            'username' => 'support.technique.adc',
            'email' => 'support@africandigitconsulting.com',
            'password' => Hash::make('ADCSupport2024!Backup'),
            'email_verified_at' => now(),
        ];

        $backupServiceTechniqueUser = User::firstOrCreate(
            ['email' => $backupServiceTechniqueData['email']],
            $backupServiceTechniqueData
        );

        if (!$backupServiceTechniqueUser->hasRole('serviceTechnique')) {
            $backupServiceTechniqueUser->assignRole('serviceTechnique');
        }

        $this->command->info('✅ Compte Service Technique principal créé :');
        $this->command->line("   📧 Email: {$serviceTechniqueUser->email}");
        $this->command->line("   🔑 Mot de passe: ADC2024Tech!SecurePass");
        $this->command->line("   🎭 Rôle: serviceTechnique");

        $this->command->info('✅ Compte Service Technique backup créé :');
        $this->command->line("   📧 Email: {$backupServiceTechniqueUser->email}");
        $this->command->line("   🔑 Mot de passe: ADCSupport2024!Backup");
        $this->command->line("   🎭 Rôle: serviceTechnique");

        $this->command->info('🔐 Permissions spéciales attribuées :');
        foreach ($specialPermissions as $permission) {
            $this->command->line("   ✓ {$permission}");
        }

        $this->command->warn('⚠️  IMPORTANT : Ces comptes sont réservés exclusivement au Service Technique d\'African Digit Consulting');
        $this->command->warn('⚠️  Changez les mots de passe en production !');
    }
}