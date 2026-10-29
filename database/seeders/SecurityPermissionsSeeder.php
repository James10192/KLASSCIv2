<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SecurityPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les permissions granulaires pour la sécurité et l'audit
        $securityPermissions = [
            // Permissions d'audit et sécurité
            'security.audit.view' => 'Consulter les logs d\'audit',
            'security.audit.export' => 'Exporter les logs d\'audit',
            'security.audit.delete' => 'Supprimer les logs d\'audit anciens',
            'security.users.monitor' => 'Surveiller l\'activité des utilisateurs',
            'security.events.view' => 'Consulter les événements de sécurité',
            'security.backup.view' => 'Consulter les backups',
            'security.backup.create' => 'Créer des backups',
            'security.backup.restore' => 'Restaurer des backups',

            // Permissions comptabilité granulaires supplémentaires
            'comptabilite.audit.view' => 'Consulter l\'audit comptabilité',
            'comptabilite.audit.export' => 'Exporter l\'audit comptabilité',
            'comptabilite.security.manage' => 'Gérer la sécurité comptabilité',
            'comptabilite.permissions.manage' => 'Gérer les permissions comptabilité',
            'comptabilite.data.encrypt' => 'Chiffrer les données comptabilité',
            'comptabilite.data.decrypt' => 'Déchiffrer les données comptabilité',
            'comptabilite.sensitive.access' => 'Accéder aux données sensibles',
            'comptabilite.transactions.monitor' => 'Surveiller les transactions',

            // Permissions workflow granulaires
            'workflow.approve.level1' => 'Approuver niveau 1 (jusqu\'à 100k FCFA)',
            'workflow.approve.level2' => 'Approuver niveau 2 (jusqu\'à 500k FCFA)',
            'workflow.approve.level3' => 'Approuver niveau 3 (montant illimité)',
            'workflow.reject.any' => 'Rejeter tout workflow',
            'workflow.bypass.approval' => 'Contourner l\'approbation workflow',
            'workflow.audit.view' => 'Consulter l\'audit workflow',

            // Permissions de validation avancées
            'validation.financial.basic' => 'Validation financière basique',
            'validation.financial.advanced' => 'Validation financière avancée',
            'validation.bulk.operations' => 'Validation d\'opérations en lot',
            'validation.emergency.override' => 'Validation d\'urgence (override)',

            // Permissions de reporting sécurisé
            'reports.financial.confidential' => 'Rapports financiers confidentiels',
            'reports.audit.complete' => 'Rapports d\'audit complets',
            'reports.security.incidents' => 'Rapports d\'incidents de sécurité',
            'reports.compliance.klassci' => 'Rapports de conformité KLASSCI',

            // Permissions d'administration système
            'admin.system.security' => 'Administration sécurité système',
            'admin.users.security' => 'Administration sécurité utilisateurs',
            'admin.logs.management' => 'Gestion des logs système',
            'admin.config.security' => 'Configuration sécurité système',
        ];

        // Créer les permissions
        foreach ($securityPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Assigner les permissions aux rôles existants
        $this->assignPermissionsToRoles();

        $this->command->info('Permissions de sécurité et d\'audit créées avec succès!');
    }

    /**
     * Assigner les permissions aux rôles existants selon la hiérarchie
     */
    private function assignPermissionsToRoles(): void
    {
        // Récupérer les rôles existants
        $superAdmin = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $secretaire = Role::firstOrCreate(['name' => 'secretaire', 'guard_name' => 'web']);
        $enseignant = Role::firstOrCreate(['name' => 'enseignant', 'guard_name' => 'web']);
        $comptable = Role::firstOrCreate(['name' => 'comptable', 'guard_name' => 'web']);

        // SuperAdmin : toutes les permissions
        $superAdmin->givePermissionTo(Permission::all());

        // Comptable : permissions comptabilité et audit
        $comptablePermissions = [
            'security.audit.view',
            'security.audit.export',
            'security.users.monitor',
            'comptabilite.audit.view',
            'comptabilite.audit.export',
            'comptabilite.security.manage',
            'comptabilite.data.encrypt',
            'comptabilite.data.decrypt',
            'comptabilite.sensitive.access',
            'comptabilite.transactions.monitor',
            'workflow.approve.level1',
            'workflow.approve.level2',
            'workflow.audit.view',
            'validation.financial.basic',
            'validation.financial.advanced',
            'reports.financial.confidential',
            'reports.audit.complete',
            'reports.compliance.klassci',
        ];

        foreach ($comptablePermissions as $permission) {
            if (Permission::where('name', $permission)->exists()) {
                $comptable->givePermissionTo($permission);
            }
        }

        // Secrétaire : permissions limitées
        $secretairePermissions = [
            'security.audit.view',
            'comptabilite.audit.view',
            'workflow.approve.level1',
            'validation.financial.basic',
        ];

        foreach ($secretairePermissions as $permission) {
            if (Permission::where('name', $permission)->exists()) {
                $secretaire->givePermissionTo($permission);
            }
        }

        // Enseignant : permissions très limitées
        $enseignantPermissions = [
            'security.audit.view', // Lecture seule
        ];

        foreach ($enseignantPermissions as $permission) {
            if (Permission::where('name', $permission)->exists()) {
                $enseignant->givePermissionTo($permission);
            }
        }

        $this->command->info('Permissions assignées aux rôles avec succès!');
    }
}
