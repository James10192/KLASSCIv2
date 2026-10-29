<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Créer les permissions
        $permissions = [
            'view_students',
            'create_students',
            'edit_students',
            'delete_students'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Permission pour accéder au module de comptabilité
        Permission::create(['name' => 'access_comptabilite_module']);

        // Permissions pour les fonctionnalités de comptabilité
        Permission::create(['name' => 'view_paiements']);
        Permission::create(['name' => 'create_paiements']);
        Permission::create(['name' => 'edit_paiements']);
        Permission::create(['name' => 'delete_paiements']);

        Permission::create(['name' => 'view_frais_scolarite']);
        Permission::create(['name' => 'create_frais_scolarite']);
        Permission::create(['name' => 'edit_frais_scolarite']);
        Permission::create(['name' => 'delete_frais_scolarite']);

        Permission::create(['name' => 'view_depenses']);
        Permission::create(['name' => 'create_depenses']);
        Permission::create(['name' => 'edit_depenses']);
        Permission::create(['name' => 'delete_depenses']);

        Permission::create(['name' => 'view_salaires']);
        Permission::create(['name' => 'create_salaires']);
        Permission::create(['name' => 'edit_salaires']);
        Permission::create(['name' => 'delete_salaires']);

        Permission::create(['name' => 'view_bourses']);
        Permission::create(['name' => 'create_bourses']);
        Permission::create(['name' => 'edit_bourses']);
        Permission::create(['name' => 'delete_bourses']);

        Permission::create(['name' => 'view_reporting_financier']);
        Permission::create(['name' => 'export_reporting_financier']);

        // Permissions pour l'émargement des enseignants
        Permission::create(['name' => 'view_own_attendance', 'description' => 'Voir ses propres émargements']);
        Permission::create(['name' => 'sign_attendance', 'description' => 'Signer sa présence']);
        Permission::create(['name' => 'manage_attendance_codes', 'description' => 'Gérer les codes d\'émargement']);
        Permission::create(['name' => 'validate_attendance', 'description' => 'Valider les émargements']);
        Permission::create(['name' => 'view_all_attendance', 'description' => 'Voir tous les émargements']);

        // Assigner toutes les permissions au superAdmin
        $superAdmin = Role::findByName('superAdmin');
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::all());
        }

        // Vider le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
