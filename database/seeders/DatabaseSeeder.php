<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\ESBTPDepartment;

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

        // Créer directement les rôles de base ici (remplace les anciens seeders)
        $this->createBasicRoles();

        // Nouveau seeder basé sur les données Excel réelles (remplace les anciens)
        $this->call([
            ExcelBasedRealDataSeeder::class,       // Import des vraies données Excel (2451 étudiants)
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

        // Add test users with different roles (only if seeders exist)
        if (app()->environment('local', 'development', 'testing')) {
            // Skip test user seeders for now as they're not essential
            // $this->call(UsersTestSeeder::class);
            // $this->call(TestUsersSeeder::class);
        }

        // Create basic departments and laboratories directly
        $this->createBasicDepartments();
    }
    
    private function createBasicRoles(): void
    {
        $this->command->info('👤 Création des rôles de base...');
        
        // Créer permissions de base
        $permissions = [
            'manage_users',
            'manage_students', 
            'manage_classes',
            'manage_teachers',
            'view_dashboard',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Créer rôles de base
        $superAdmin = Role::firstOrCreate(['name' => 'superAdmin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $teacher = Role::firstOrCreate(['name' => 'enseignant']);
        $student = Role::firstOrCreate(['name' => 'etudiant']);
        $secretary = Role::firstOrCreate(['name' => 'secretaire']);
        
        // Assigner toutes les permissions au superAdmin
        $superAdmin->syncPermissions($permissions);
        $admin->givePermissionTo(['manage_students', 'manage_classes', 'view_dashboard']);
    }
    
    private function createBasicDepartments(): void
    {
        $this->command->info('🏢 Création des départements de base...');
        
        $departments = [
            [
                'name' => 'Bâtiment',
                'code' => 'BAT',
                'description' => 'Département Bâtiment et Construction',
                'is_active' => true,
            ],
            [
                'name' => 'Travaux Publics',
                'code' => 'TP',
                'description' => 'Département Travaux Publics',
                'is_active' => true,
            ],
            [
                'name' => 'Transport',
                'code' => 'TRANS',
                'description' => 'Département Transport et Logistique',
                'is_active' => true,
            ],
        ];
        
        foreach ($departments as $dept) {
            ESBTPDepartment::firstOrCreate(['code' => $dept['code']], $dept);
        }
    }
}
