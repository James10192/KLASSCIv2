<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Création des rôles principaux pour ESBTP
        $roles = [
            'superAdmin',
            'secretaire',
            'etudiant'
        ];

        foreach ($roles as $roleName) {
            Role::create(['name' => $roleName]);
        }

        // Création des permissions de base
        // Pour les filières
        Permission::create(['name' => 'view_filieres']);
        Permission::create(['name' => 'create_filieres']);
        Permission::create(['name' => 'edit_filieres']);
        Permission::create(['name' => 'delete_filieres']);

        // Pour les niveaux d'études
        Permission::create(['name' => 'view_niveaux_etudes']);
        Permission::create(['name' => 'create_niveaux_etudes']);
        Permission::create(['name' => 'edit_niveaux_etudes']);
        Permission::create(['name' => 'delete_niveaux_etudes']);

        // Pour les formations
        Permission::create(['name' => 'view_formations']);
        Permission::create(['name' => 'create_formations']);
        Permission::create(['name' => 'edit_formations']);
        Permission::create(['name' => 'delete_formations']);

        // Pour les matières
        Permission::create(['name' => 'view_matieres']);
        Permission::create(['name' => 'create_matieres']);
        Permission::create(['name' => 'edit_matieres']);
        Permission::create(['name' => 'delete_matieres']);

        // Pour les classes
        Permission::create(['name' => 'view_classes']);
        Permission::create(['name' => 'create_classes']);
        Permission::create(['name' => 'edit_classes']);
        Permission::create(['name' => 'delete_classes']);

        // Pour les étudiants
        Permission::create(['name' => 'view_students']);
        Permission::create(['name' => 'create_students']);
        Permission::create(['name' => 'edit_students']);
        Permission::create(['name' => 'delete_students']);
        Permission::create(['name' => 'view_own_profile']);

        // Pour les examens
        Permission::create(['name' => 'view_exams']);
        Permission::create(['name' => 'create_exams']);
        Permission::create(['name' => 'edit_exams']);
        Permission::create(['name' => 'delete_exams']);
        Permission::create(['name' => 'view_own_exams']);

        // Pour les notes
        Permission::create(['name' => 'view_grades']);
        Permission::create(['name' => 'create_grades']);
        Permission::create(['name' => 'edit_grades']);
        Permission::create(['name' => 'delete_grades']);
        Permission::create(['name' => 'view_own_grades']);

        // Pour les bulletins
        Permission::create(['name' => 'view_bulletins']);
        Permission::create(['name' => 'generate_bulletin']);
        Permission::create(['name' => 'edit_bulletins']);
        Permission::create(['name' => 'delete_bulletins']);
        Permission::create(['name' => 'view_own_bulletin']);

        // Pour les emplois du temps
        Permission::create(['name' => 'view_timetables']);
        Permission::create(['name' => 'create_timetable']);
        Permission::create(['name' => 'edit_timetables']);
        Permission::create(['name' => 'delete_timetables']);
        Permission::create(['name' => 'view_own_timetable']);

        // Pour les messages
        Permission::create(['name' => 'send_messages']);
        Permission::create(['name' => 'receive_messages']);

        // Pour les présences
        Permission::create(['name' => 'view_attendances']);
        Permission::create(['name' => 'create_attendance']);
        Permission::create(['name' => 'edit_attendances']);
        Permission::create(['name' => 'delete_attendances']);
        Permission::create(['name' => 'view_own_attendances']);

        // Attribution des permissions aux rôles
        // Le superAdmin a toutes les permissions
        $superadminRole = Role::findByName('superAdmin');
        $superadminRole->givePermissionTo(Permission::all());

        // Le secrétaire a des permissions spécifiques
        $secretaireRole = Role::findByName('secretaire');
        $secretaireRole->givePermissionTo([
            'view_filieres', 'view_formations', 'view_niveaux_etudes', 'view_classes',
            'create_students', 'view_students', 'view_exams', 'view_matieres',
            'create_grades', 'view_grades', 'generate_bulletin', 'view_bulletins',
            'create_timetable', 'view_timetables', 'send_messages',
            'create_attendance', 'view_attendances'
        ]);

        // L'étudiant ne peut voir que ses propres données
        $etudiantRole = Role::findByName('etudiant');
        $etudiantRole->givePermissionTo([
            'view_own_profile', 'view_own_exams', 'view_own_grades', 'view_own_bulletin',
            'view_own_timetable', 'receive_messages', 'view_own_attendances'
        ]);

        // Permissions pour le rôle enseignant
        $enseignant = Role::create(['name' => 'enseignant']);
        $enseignant->givePermissionTo([
            'view_own_attendance',
            'sign_attendance',
            'view_own_timetable',
            'view_own_classes',
            'manage_own_notes',
            'view_own_students'
        ]);
    }
}
