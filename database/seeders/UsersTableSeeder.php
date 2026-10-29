<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Guardian;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Création d'un super administrateur
        // Comme un roi qui règne sur tout le royaume de l'école
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => 'password', // Sera automatiquement haché grâce au mutator dans le modèle User
            'role' => 'superadmin',
            'is_active' => true,
            'phone' => '1234567890',
        ]);

        $this->command->info('Super Admin créé avec succès!');

        // Création d'un administrateur
        // Comme un ministre qui gère les affaires quotidiennes
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
            'phone' => '2345678901',
        ]);

        $this->command->info('Admin créé avec succès!');

        // Création d'un enseignant
        // Comme un sage qui partage son savoir
        $teacher = User::create([
            'name' => 'Enseignant Test',
            'email' => 'teacher@example.com',
            'password' => 'password',
            'role' => 'teacher',
            'is_active' => true,
            'phone' => '3456789012',
        ]);

        // Création du profil enseignant associé
        Teacher::create([
            'user_id' => $teacher->id,
            'department_id' => 1, // Assurez-vous que ce département existe
            'designation_id' => 1, // Assurez-vous que cette désignation existe
            'qualification' => 'Doctorat en Éducation',
            'date_of_joining' => now(),
            'address' => '123 Rue des Professeurs',
            'gender' => 'Homme',
            'date_of_birth' => '1980-01-01',
        ]);

        $this->command->info('Enseignant créé avec succès!');

        // Création d'un parent
        // Comme un gardien qui veille sur ses enfants
        $parent = User::create([
            'name' => 'Parent Test',
            'email' => 'parent@example.com',
            'password' => 'password',
            'role' => 'parent',
            'is_active' => true,
            'phone' => '4567890123',
        ]);

        // Création du profil parent associé
        Guardian::create([
            'user_id' => $parent->id,
            'occupation' => 'Ingénieur',
            'address' => '456 Avenue des Familles',
            'relationship' => 'Père',
        ]);

        $this->command->info('Parent créé avec succès!');

        // Création d'un étudiant
        // Comme un apprenti qui absorbe les connaissances
        $student = User::create([
            'name' => 'Étudiant Test',
            'email' => 'student@example.com',
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
            'phone' => '5678901234',
        ]);

        // Création du profil étudiant associé
        Student::create([
            'user_id' => $student->id,
            'admission_number' => 'STU'.date('Y').'001',
            'roll_number' => 'R001',
            'class_id' => 1, // Assurez-vous que cette classe existe
            'section_id' => 1, // Assurez-vous que cette section existe
            'guardian_id' => 1, // Lié au parent créé ci-dessus
            'gender' => 'Homme',
            'date_of_birth' => '2005-05-15',
            'address' => '789 Boulevard des Étudiants',
            'admission_date' => now(),
        ]);

        $this->command->info('Étudiant créé avec succès!');

        $this->command->info('Tous les utilisateurs ont été créés avec succès!');
    }
}
