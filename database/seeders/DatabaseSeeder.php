<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // Note: SuperAdminSeeder n'est pas appelé ici car il sera exécuté
        // pendant le processus d'installation via l'interface utilisateur

        // Seeders du système et base de l'application
        $this->call([
            ESBTPRoleSeeder::class,           // Création des rôles et permissions
            ESBTPFiliereSeeder::class,
            ESBTPMatiereSeeder::class,
        ]);

        // Seeders pour les données de référence ESBTP
        $this->call([
            ESBTPAnneeUniversitaireSeeder::class,  // Années universitaires
            ESBTPNiveauEtudeSeeder::class,         // Niveaux d'études (BTS 1, BTS 2)
            ESBTPFiliereNiveauSeeder::class,       // Relations filières-niveaux
            ESBTPMatiereNiveauSeeder::class,       // Relations matières-niveaux
        ]);

        // Commented out missing seeder
        // $this->call(ESBTPTestDataSeeder::class);

        // Commented out seeders that might be causing issues
        // $this->call(ESBTPEmploiTempsSeeder::class);

        // Nouveaux seeders pour les évaluations et notes
        // $this->call([
        //     ESBTPEvaluationSeeder::class,
        //     ESBTPNoteSeeder::class,
        //     ESBTPBulletinSeeder::class,
        //     ESBTPBulletinDetailsSeeder::class,   // Migration des données bulletin vers le nouveau format
        // ]);

        // Add the expense categories seeder
        $this->call(ESBTPCategorieDepenseSeeder::class);

        // Add test users with different roles
        if (app()->environment('local', 'development', 'testing')) {
            $this->call(UsersTestSeeder::class);

            // New Test Users Seeder with student, secretary, and admin accounts
            $this->call(TestUsersSeeder::class);
        }

        $this->call([
            ESBTPDepartmentSeeder::class,
            ESBTPLaboratorySeeder::class,
        ]);
    }
}
