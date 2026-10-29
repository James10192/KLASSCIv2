<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Création des utilisateurs avec différents rôles

        // Super Admin
        $this->createUserWithRole(
            'Super Admin',
            'superadmin@school.com',
            'password123',
            'super-admin'
        );

        // Admin
        $this->createUserWithRole(
            'Admin',
            'admin@school.com',
            'password123',
            'admin'
        );

        // Directeur
        $this->createUserWithRole(
            'Jean Dupont',
            'directeur@school.com',
            'password123',
            'directeur'
        );

        // Enseignants
        $this->createUserWithRole(
            'Marie Martin',
            'enseignant1@school.com',
            'password123',
            'enseignant'
        );

        $this->createUserWithRole(
            'Pierre Dubois',
            'enseignant2@school.com',
            'password123',
            'enseignant'
        );

        // Étudiants
        $this->createUserWithRole(
            'Sophie Bernard',
            'etudiant1@school.com',
            'password123',
            'etudiant'
        );

        $this->createUserWithRole(
            'Lucas Petit',
            'etudiant2@school.com',
            'password123',
            'etudiant'
        );

        $this->createUserWithRole(
            'Emma Leroy',
            'etudiant3@school.com',
            'password123',
            'etudiant'
        );

        // Parents
        $this->createUserWithRole(
            'Thomas Bernard',
            'parent1@school.com',
            'password123',
            'parent'
        );

        $this->createUserWithRole(
            'Nathalie Petit',
            'parent2@school.com',
            'password123',
            'parent'
        );

        // Secrétaire
        $this->createUserWithRole(
            'Isabelle Moreau',
            'secretaire@school.com',
            'password123',
            'secretaire'
        );

        // Comptable
        $this->createUserWithRole(
            'Robert Fournier',
            'comptable@school.com',
            'password123',
            'comptable'
        );

        // Bibliothécaire
        $this->createUserWithRole(
            'Sylvie Girard',
            'bibliothecaire@school.com',
            'password123',
            'bibliothecaire'
        );
    }

    /**
     * Créer un utilisateur avec un rôle spécifique s'il n'existe pas déjà.
     *
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $roleName
     * @return User
     */
    private function createUserWithRole($name, $email, $password, $roleName)
    {
        // Vérifier si l'utilisateur existe déjà
        if (!User::where('email', $email)->exists()) {
            // Générer un username à partir de l'email (partie avant @)
            $username = explode('@', $email)[0];

            $user = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            // Assigner le rôle à l'utilisateur
            $user->assignRole($roleName);

            return $user;
        }

        return User::where('email', $email)->first();
    }
}
