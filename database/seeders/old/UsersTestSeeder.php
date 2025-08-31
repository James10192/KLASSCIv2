<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UsersTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer ou récupérer les rôles
        $superAdminRole = Role::firstOrCreate(['name' => 'superAdmin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);

        // Générer un timestamp unique pour éviter les collisions
        $timestamp = now()->format('His');

        // Créer le compte super admin s'il n'existe pas déjà
        if (!User::where('email', 'superadmin@klassci.edu')->exists()) {
            $superAdmin = User::create([
                'name' => 'Super Admin',
                'username' => 'superadmin_test_'.$timestamp,
                'email' => 'superadmin@klassci.edu',
                'password' => Hash::make('Admin@2023'),
                'email_verified_at' => now(),
            ]);
            $superAdmin->assignRole($superAdminRole);
            $this->command->info('Compte super admin créé avec succès!');
        } else {
            $this->command->info('Le compte super admin existe déjà.');
        }

        // Créer le compte enseignant s'il n'existe pas déjà
        if (!User::where('email', 'enseignant@klassci.edu')->exists()) {
            $enseignant = User::create([
                'name' => 'Enseignant Test',
                'username' => 'enseignant_test_'.$timestamp,
                'email' => 'enseignant@klassci.edu',
                'password' => Hash::make('Teach@2023'),
                'email_verified_at' => now(),
            ]);
            $enseignant->assignRole($teacherRole);
            $this->command->info('Compte enseignant créé avec succès!');
        } else {
            $this->command->info('Le compte enseignant existe déjà.');
        }

        $this->command->info('Opération terminée.');
    }
}
