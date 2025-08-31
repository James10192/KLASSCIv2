<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ESBTPRoleSeeder extends Seeder
{
    /**
     * Seeder pour créer les rôles et permissions de l'ESBTP.
     *
     * @return void
     */
    public function run()
    {
        // Création des rôles
        $superAdmin = Role::firstOrCreate(['name' => 'superAdmin']);
        $secretaire = Role::firstOrCreate(['name' => 'secretaire']);
        $etudiant = Role::firstOrCreate(['name' => 'etudiant']);
        $enseignant = Role::firstOrCreate(['name' => 'enseignant']);

        // Permissions pour les filières
        $filierePermissions = [
            'create filieres', 'view filieres', 'edit filieres', 'delete filieres'
        ];

        // Permissions pour les niveaux d'études
        $niveauPermissions = [
            'create niveau etudes', 'view niveau etudes', 'edit niveau etudes', 'delete niveau etudes'
        ];

        // Permissions pour les classes
        $classePermissions = [
            'create classes', 'view classes', 'edit classes', 'delete classes'
        ];

        // Permissions pour les étudiants
        $studentPermissions = [
            'create students', 'view students', 'edit students', 'delete students',
            'view own profile', 'view own grades', 'view own timetable', 'view own bulletin',
            'view own attendances', 'view own exams', 'receive own messages'
        ];

        // Permissions pour les examens
        $examPermissions = [
            'create exams', 'view exams', 'edit exams', 'delete exams'
        ];

        // Permissions pour les matières
        $matierePermissions = [
            'create matieres', 'view matieres', 'edit matieres', 'delete matieres'
        ];

        // Permissions pour les notes
        $gradePermissions = [
            'create grades', 'view grades', 'edit grades', 'delete grades'
        ];

        // Permissions pour les bulletins
        $bulletinPermissions = [
            'generate bulletin', 'view bulletins', 'edit bulletins', 'delete bulletins'
        ];

        // Permissions pour les emplois du temps
        $timetablePermissions = [
            'create timetable', 'view timetables', 'edit timetables', 'delete timetables'
        ];

        // Permissions pour les messages
        $messagePermissions = [
            'send messages', 'receive messages'
        ];

        // Permissions pour les présences
        $attendancePermissions = [
            'create attendance', 'view attendances', 'edit attendances', 'delete attendances'
        ];
        // Inscriptions
        $inscriptionPermissions = [
            'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.delete', 'inscriptions.validate'
        ];

        // Création de toutes les permissions
        $allPermissions = array_merge(
            $filierePermissions,
            $niveauPermissions,
            $classePermissions,
            $studentPermissions,
            $examPermissions,
            $matierePermissions,
            $gradePermissions,
            $bulletinPermissions,
            $timetablePermissions,
            $messagePermissions,
            $attendancePermissions,
            $inscriptionPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Attribution des permissions au superAdmin
        $superAdmin->givePermissionTo($allPermissions);

        // Attribution des permissions au secrétaire
        $secretaire->givePermissionTo([
            'view filieres',
            'view classes',
            'create students',
            'view students',
            'view exams',
            'view matieres',
            'create grades',
            'view grades',
            'generate bulletin',
            'view bulletins',
            'create timetable',
            'view timetables',
            'send messages',
            'create attendance',
            'view attendances',
            'inscriptions.view',
            'inscriptions.create',
            'inscriptions.edit',
            'inscriptions.validate'
        ]);

        // Attribution des permissions à l'étudiant
        $etudiant->givePermissionTo([
            'view own profile',
            'view own grades',
            'view own timetable',
            'view own bulletin',
            'view own attendances',
            'view own exams',
            'receive own messages',
        ]);

        // Attribution des permissions à l'enseignant
        $enseignant->givePermissionTo([
            'view classes',
            'view students',
            'view matieres',
            'create grades',
            'view grades',
            'view timetables',
            'send messages',
            'create attendance',
            'view attendances',
        ]);

        $this->command->info('Tous les rôles et permissions ESBTP ont été créés avec succès.');
    }
}
