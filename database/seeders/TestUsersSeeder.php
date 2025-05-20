<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create roles if they don't exist
        $roles = ['superAdmin', 'secretaire', 'etudiant', 'teacher'];
        foreach ($roles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create(['name' => $roleName]);
            }
        }

        // Create SuperAdmin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@esbtp.ci'],
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'superadmin',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );

        $superAdmin->assignRole('superAdmin');

        // Create Secretary user
        $secretaire = User::firstOrCreate(
            ['email' => 'secretaire@esbtp.ci'],
            [
                'name' => 'Secretaire Test',
                'first_name' => 'Secretaire',
                'last_name' => 'Test',
                'username' => 'secretaire',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );

        $secretaire->assignRole('secretaire');

        // Create Student user
        $etudiant = User::firstOrCreate(
            ['email' => 'etudiant@esbtp.ci'],
            [
                'name' => 'Etudiant Test',
                'first_name' => 'Etudiant',
                'last_name' => 'Test',
                'username' => 'etudiant',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );

        $etudiant->assignRole('etudiant');

        // Create Teacher user
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@esbtp.ci'],
            [
                'name' => 'Teacher Test',
                'first_name' => 'Teacher',
                'last_name' => 'Test',
                'username' => 'teacher',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );

        $teacher->assignRole('teacher');

        // Create student profile for the student user if it doesn't exist
        $etudiantProfile = ESBTPEtudiant::where('user_id', $etudiant->id)->first();
        if (!$etudiantProfile) {
            // Try to find a class for the student
            $classe = ESBTPClasse::first();

            ESBTPEtudiant::create([
                'user_id' => $etudiant->id,
                'matricule' => 'ETU' . date('Y') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'nom' => 'Test',
                'prenoms' => 'Etudiant',
                'date_naissance' => '2000-01-01',
                'lieu_naissance' => 'Abidjan',
                'sexe' => 'M',
                'adresse' => 'Cocody',
                'telephone' => '0700000000',
                'email' => 'etudiant@esbtp.ci',
                'classe_id' => $classe ? $classe->id : null,
                'created_by' => $superAdmin->id,
            ]);
        }

        $this->command->info('Test users created successfully:');
        $this->command->info('SuperAdmin - Email: superadmin@esbtp.ci, Password: password123');
        $this->command->info('Secretary - Email: secretaire@esbtp.ci, Password: password123');
        $this->command->info('Student - Email: etudiant@esbtp.ci, Password: password123');
        $this->command->info('Teacher - Email: teacher@esbtp.ci, Password: password123');
    }
}
