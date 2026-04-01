#!/usr/bin/env php
<?php

/**
 * Script pour ajouter les permissions manquantes au rôle de secrétaire
 *
 * Ce script doit être exécuté à la racine du projet avec la commande :
 * php bin/deploy/fix_permissions.php
 */

// Définir la racine du projet (2 niveaux au-dessus de bin/deploy/)
define('PROJECT_ROOT', dirname(__DIR__, 2));

// Vérifier que PROJECT_ROOT est correct
if (! file_exists(PROJECT_ROOT.'/artisan')) {
    exit("❌ ERREUR: PROJECT_ROOT incorrecte. Artisan non trouvé.\n");
}

// Autoloader de Composer
require PROJECT_ROOT.'/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once PROJECT_ROOT.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "=== Script de réparation des permissions ===\n";

try {
    // Réinitialiser les caches des permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    echo "✅ Cache des permissions réinitialisé\n";

    // Définir les permissions à ajouter/vérifier
    $permissions = [
        // Permissions générales
        'view_dashboard',
        'access_admin',

        // Étudiants
        'view_students',
        'create_students',
        'edit_students',
        'delete_students',
        'view_own_students',

        // Inscriptions
        'view_inscriptions',
        'create_inscriptions',
        'edit_inscriptions',
        'approve_inscriptions',
        'reject_inscriptions',
        // Inscriptions avec syntaxe point
        'inscriptions.view',
        'inscriptions.create',
        'inscriptions.edit',
        'inscriptions.delete',
        'inscriptions.validate',
        // Inscriptions avec syntaxe espace (utilisées dans les vues)
        'edit inscriptions',
        'valider inscriptions',
        'annuler inscriptions',
        'delete inscriptions',

        // Paiements avec syntaxe point
        'paiements.view',
        'paiements.create',
        'paiements.edit',
        'paiements.delete',
        'paiements.validate',

        // Frais avec syntaxe point
        'frais.view',
        'frais.create',
        'frais.edit',
        'frais.delete',
        'frais.configure',

        // Sécurité et audit
        'security.audit.view',
        'security.audit.export',
        'comptabilite.audit.view',
        'security.users.monitor',

        // Codes d'émargement
        'generate-attendance-codes',

        // Planning et emplois du temps
        'manage-planning',
        'view-all-timetables',
        'view_timetables',
        'create_timetable',
        'edit_timetables',
        'delete_timetables',
        'view_own_timetable',

        // Cycles avec espaces
        'view cycles',
        'create cycles',
        'edit cycles',
        'delete cycles',
        'restore cycles',
        'force delete cycles',

        // Classes et filières
        'view_classes',
        'create_classes',
        'edit_classes',
        'delete_classes',
        'view_filieres',
        'create_filieres',
        'edit_filieres',

        // Niveaux d'études
        'view_niveaux_etudes',
        'create_niveaux_etudes',
        'edit_niveaux_etudes',
        'delete_niveaux_etudes',

        // Matières
        'view_matieres',
        'create_matieres',
        'edit_matieres',
        'delete_matieres',

        // Notes et évaluations
        'view_notes',
        'create_notes',
        'edit_notes',
        'edit_existing_notes',
        'view_own_notes',
        'manage_own_notes',

        // Grades (alias for notes)
        'view_grades',
        'view_own_grades',
        'create_grade',
        'edit_grades',
        'delete_grades',
        'view_evaluations',
        'view_own_exams',
        'create_evaluations',
        'edit_evaluations',

        // Bulletins
        'view_bulletins',
        'generate_bulletins',
        'edit_bulletins',
        'view_own_bulletin',

        // Présences
        'view_attendances',
        'create_attendance',
        'create_attendances',
        'edit_attendances',
        'delete_attendances',
        'view_own_attendances',
        'sign_attendance',
        'view_own_attendance',

        // Paiements et comptabilité
        'view_payments',
        'create_payments',
        'edit_payments',
        'view_comptabilite',
        'manage_comptabilite',

        // Personnel et enseignants
        'view_teachers',
        'create_teachers',
        'edit_teachers',
        'view_personnel',
        'manage_personnel',
        'view_own_profile',

        // Coordinateurs
        'view_coordinateurs',
        'create_coordinateurs',
        'edit_coordinateurs',
        'delete_coordinateurs',

        // Emplois du temps
        'view_schedules',
        'create_schedules',
        'edit_schedules',
        'view_own_schedule',

        // Messages et communication
        'send_messages',
        'receive_messages',
        'view_annonces',
        'create_annonces',
        'edit_annonces',

        // Rapports
        'view_reports',
        'generate_reports',

        // Paramètres système
        'view_settings',
        'edit_settings',
        'manage_system',

        // Permissions spécifiques ESBTP
        'view_planning_general',
        'edit_planning_general',
        'view_resultats',
        'edit_resultats',

        // Permissions modules (toggle par rôle via page Rôles & Permissions)
        'module.enseignants.access',
        'module.notes_evaluations.access',
        'module.emploi_temps.access',
        'module.presences.access',
        'module.lmd.access',
        'module.academique.access',
        'module.etudiants.access',
        'module.comptabilite.access',
        'module.communication.access',

        // Permissions manquantes utilisées dans le code
        'manage-users',
        'edit_enseignants',
        'edit_bulletins',

        // Permissions Service Technique (African Digit Consulting)
        'paywall.configure',
        'paywall.manage_subscriptions',
        'paywall.extend_subscriptions',
        'paywall.view_all_stats',
        'system.technical_access',
        'system.emergency_override',

        // Permissions Comptabilité (rôle comptable)
        'comptabilite.access',
        'comptabilite.dashboard.view',
        'comptabilite.relances.send',
        'comptabilite.reports.export',
        'comptabilite.config.manage',
        'comptabilite.paiements.view',
        'comptabilite.paiements.validate',
        'comptabilite.frais.view',
        'comptabilite.frais.configure',
    ];

    echo "Création/vérification des permissions...\n";

    foreach ($permissions as $permissionName) {
        $permission = Permission::firstOrCreate(['name' => $permissionName]);
        echo "✓ Permission: $permissionName\n";
    }

    // Vérifier/créer les rôles
    $roles = [
        'superAdmin' => 'Super Administrateur',
        'admin' => 'Administrateur',
        'secretaire' => 'Secrétaire',
        'coordinateur' => 'Coordinateur',
        'enseignant' => 'Enseignant',
        'etudiant' => 'Étudiant',
        'parent' => 'Parent',
        'serviceTechnique' => 'Service Technique (African Digit Consulting)',
        'teacher' => 'Teacher (alias de enseignant)',
        'comptable' => 'Comptable',
        'caissier' => 'Caissier',
    ];

    echo "\nCréation/vérification des rôles...\n";

    foreach ($roles as $roleName => $roleLabel) {
        $role = Role::firstOrCreate(['name' => $roleName]);
        echo "✓ Rôle: $roleName ($roleLabel)\n";
    }

    // Attribution des permissions aux rôles
    echo "\nAttribution des permissions aux rôles...\n";

    // SuperAdmin - Toutes les permissions
    $superAdminRole = Role::findByName('superAdmin');
    $superAdminRole->syncPermissions($permissions);
    echo "✓ SuperAdmin: Toutes les permissions accordées\n";

    // Secrétaire - Permissions principales
    $secretaireRole = Role::findByName('secretaire');
    $secretairePermissions = [
        'view_dashboard',
        'access_admin',
        'view_students', 'create_students', 'edit_students',
        'view_inscriptions', 'create_inscriptions', 'edit_inscriptions',
        'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.validate',
        'edit inscriptions', 'valider inscriptions',
        'approve_inscriptions',
        'paiements.view', 'paiements.create', 'paiements.validate',
        'frais.view', 'frais.create', 'frais.edit',
        'view cycles', 'create cycles', 'edit cycles', 'delete cycles',
        'view_classes', 'create_classes', 'edit_classes',
        'view_filieres', 'create_filieres', 'edit_filieres',
        'view_niveaux_etudes', 'create_niveaux_etudes', 'edit_niveaux_etudes',
        'view_matieres',
        'view_notes', 'view_evaluations',
        'view_bulletins', 'generate_bulletins',
        'view_attendances', 'create_attendance', 'create_attendances', 'edit_attendances', 'delete_attendances',
        'generate-attendance-codes',
        'view_payments', 'create_payments', 'edit_payments',
        'view_comptabilite',
        'view_teachers', 'create_teachers', 'edit_teachers',
        'view_personnel', 'manage_personnel',
        'view_coordinateurs', 'create_coordinateurs', 'edit_coordinateurs', 'delete_coordinateurs',
        'view_schedules', 'create_schedules', 'edit_schedules',
        'manage-planning', 'view-all-timetables', 'view_timetables', 'create_timetable', 'edit_timetables', 'delete_timetables',
        'send_messages', 'receive_messages',
        'view_annonces', 'create_annonces', 'edit_annonces',
        'view_reports',
        'view_planning_general',
        'view_resultats',
        // Modules toggle
        'module.enseignants.access',
        'module.notes_evaluations.access',
        'module.emploi_temps.access',
        'module.presences.access',
        'module.lmd.access',
        'module.academique.access',
        'module.etudiants.access',
        'module.comptabilite.access',
        'module.communication.access',
        'manage-users',
        'edit_enseignants',
        'edit_bulletins',
    ];
    $secretaireRole->syncPermissions($secretairePermissions);
    echo '✓ Secrétaire: '.count($secretairePermissions)." permissions accordées\n";

    // Coordinateur - Permissions de coordination
    $coordinateurRole = Role::findByName('coordinateur');
    $coordinateurPermissions = [
        'view_dashboard',
        'access_admin',
        'view_students', 'edit_students',
        'view_inscriptions', 'create_inscriptions', 'approve_inscriptions', 'reject_inscriptions',
        'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.validate',
        'paiements.view',
        'frais.view',
        'view cycles', 'edit cycles',
        'view_classes', // REMOVED: 'edit_classes', 'create_classes' - coordinateurs can only view, not edit/create
        'view_matieres', 'edit_matieres',
        'view_notes', 'create_notes', 'edit_notes',
        'view_grades', 'create_grade', 'edit_grades', 'delete_grades',
        'view_evaluations', 'create_evaluations', 'edit_evaluations',
        'view_bulletins', 'generate_bulletins',
        'view_attendances', 'create_attendance', 'create_attendances', 'edit_attendances', 'delete_attendances',
        'generate-attendance-codes', 'manage-planning', 'view-all-timetables', 'view_timetables', 'create_timetable', 'edit_timetables',
        'view_payments',
        'view_teachers', 'create_teachers',
        'view_coordinateurs', 'create_coordinateurs', 'edit_coordinateurs', 'delete_coordinateurs',
        'view_schedules', 'create_schedules', 'edit_schedules',
        'send_messages', 'receive_messages',
        'view_annonces', 'create_annonces', 'edit_annonces',
        'view_reports', 'generate_reports',
        'view_planning_general', 'edit_planning_general',
        'view_resultats', 'edit_resultats',
        // Modules toggle
        'module.enseignants.access',
        'module.notes_evaluations.access',
        'module.emploi_temps.access',
        'module.presences.access',
        'module.lmd.access',
        'module.academique.access',
        'module.etudiants.access',
        'module.communication.access',
    ];
    $coordinateurRole->syncPermissions($coordinateurPermissions);
    echo '✓ Coordinateur: '.count($coordinateurPermissions)." permissions accordées\n";

    // Enseignant - Permissions d'enseignement
    $enseignantRole = Role::findByName('enseignant');
    $enseignantPermissions = [
        'view_dashboard',
        'view_own_students',
        'view_classes',
        'view_notes', 'create_notes', 'edit_notes', 'view_own_notes', 'manage_own_notes',
        'view_grades', 'create_grade', 'edit_grades',
        'view_evaluations', 'create_evaluations', 'edit_evaluations',
        'view_bulletins',
        'view_attendances', 'create_attendance', 'create_attendances', 'edit_attendances', 'view_own_attendances', 'view_own_attendance', 'sign_attendance',
        'view_own_schedule',
        'send_messages', 'receive_messages',
        'view_annonces',
        // Modules toggle
        'module.notes_evaluations.access',
        'module.presences.access',
        'module.communication.access',
    ];
    $enseignantRole->syncPermissions($enseignantPermissions);

    // Teacher (alias anglais de enseignant) - Mêmes permissions qu'enseignant
    $teacherRole = Role::findByName('teacher');
    $teacherRole->syncPermissions($enseignantPermissions);
    echo '✓ Teacher: '.count($enseignantPermissions)." permissions accordées (alias de enseignant)\n";
    echo '✓ Enseignant: '.count($enseignantPermissions)." permissions accordées\n";

    // Étudiant - Permissions de consultation
    $etudiantRole = Role::findByName('etudiant');
    $etudiantPermissions = [
        'view_dashboard',
        'view_own_notes',
        'view_own_grades',
        'view_own_bulletin',
        'view_own_attendances',
        'view_own_schedule',
        'view_own_timetable',
        'view_own_profile',
        'view_own_exams',
        'receive_messages',
        'view_annonces',
    ];
    $etudiantRole->syncPermissions($etudiantPermissions);
    echo '✓ Étudiant: '.count($etudiantPermissions)." permissions accordées\n";

    // Parent - Permissions parentales
    $parentRole = Role::findByName('parent');
    $parentPermissions = [
        'view_dashboard',
        'view_own_students',
        'view_own_notes',
        'view_own_bulletin',
        'view_own_attendances',
        'view_own_schedule',
        'receive_messages',
        'view_annonces',
    ];
    $parentRole->syncPermissions($parentPermissions);
    echo '✓ Parent: '.count($parentPermissions)." permissions accordées\n";

    // Service Technique - Toutes les permissions + permissions spéciales paywall
    $serviceTechniqueRole = Role::findByName('serviceTechnique');
    $serviceTechniquePermissions = array_merge($permissions, [
        // Toutes les permissions existantes + permissions spéciales
    ]);
    $serviceTechniqueRole->syncPermissions($serviceTechniquePermissions);
    echo '✓ Service Technique: '.count($serviceTechniquePermissions)." permissions accordées (TOUTES + spéciales)\n";

    // Comptable - Permissions comptabilité
    $comptableRole = Role::findByName('comptable');
    $comptablePermissions = [
        'view_dashboard',
        'access_admin',
        // Comptabilité (permissions propres au rôle)
        'comptabilite.access',
        'comptabilite.dashboard.view',
        'comptabilite.relances.send',
        'comptabilite.reports.export',
        'comptabilite.config.manage',
        'comptabilite.paiements.view',
        'comptabilite.paiements.validate',
        'comptabilite.frais.view',
        'comptabilite.frais.configure',
        // Paiements & frais (permissions partagées)
        'paiements.view',
        'paiements.create',
        'paiements.edit',
        'paiements.validate',
        'frais.view',
        'frais.create',
        'frais.edit',
        'frais.configure',
        // Vue des étudiants et inscriptions (lecture seule)
        'view_students',
        'view_inscriptions',
        'inscriptions.view',
        'view_payments', 'create_payments', 'edit_payments',
        'view_comptabilite', 'manage_comptabilite',
        // Rapports
        'view_reports', 'generate_reports',
        // Communication
        'send_messages', 'receive_messages',
        'view_annonces',
        // Modules toggle
        'module.comptabilite.access',
        'module.communication.access',
    ];
    $comptableRole->syncPermissions($comptablePermissions);
    echo '✓ Comptable: '.count($comptablePermissions)." permissions accordées\n";

    // Caissier - Pré-inscription simplifiée + paiements + consultation
    $caissierRole = Role::findByName('caissier');
    $caissierPermissions = [
        'view_dashboard',
        // Consultation étudiants & inscriptions
        'view_students', 'view_inscriptions', 'inscriptions.view',
        // Création pré-inscription (inscription prospect)
        'create_inscriptions', 'inscriptions.create',
        // Paiements
        'paiements.view', 'paiements.create', 'paiements.edit', 'paiements.validate',
        'view_payments', 'create_payments', 'edit_payments',
        // Comptabilité (consultation + relances, pas de config)
        'comptabilite.access', 'comptabilite.dashboard.view',
        'comptabilite.relances.send',
        'comptabilite.paiements.view', 'comptabilite.paiements.validate',
        'frais.view',
        // Communication
        'send_messages', 'receive_messages',
        'view_annonces',
    ];
    $caissierRole->syncPermissions($caissierPermissions);
    echo '✓ Caissier: '.count($caissierPermissions)." permissions accordées\n";

    // Vérifier les utilisateurs sans rôle et leur attribuer un rôle par défaut
    echo "\nVérification des utilisateurs sans rôle...\n";
    $usersWithoutRole = User::doesntHave('roles')->get();

    foreach ($usersWithoutRole as $user) {
        // Attribuer un rôle basé sur l'email ou d'autres critères
        if (str_contains($user->email, 'admin') || str_contains($user->email, 'superadmin')) {
            $user->assignRole('superAdmin');
            echo "✓ {$user->name} ({$user->email}) -> superAdmin\n";
        } elseif (str_contains($user->email, 'secretaire')) {
            $user->assignRole('secretaire');
            echo "✓ {$user->name} ({$user->email}) -> secretaire\n";
        } elseif (str_contains($user->email, 'enseignant') || str_contains($user->email, 'teacher')) {
            $user->assignRole('enseignant');
            echo "✓ {$user->name} ({$user->email}) -> enseignant\n";
        } else {
            // Par défaut, attribuer le rôle étudiant
            $user->assignRole('etudiant');
            echo "✓ {$user->name} ({$user->email}) -> etudiant (par défaut)\n";
        }
    }

    // Réinitialiser le cache à nouveau
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    echo "\n=== Récapitulatif ===\n";
    echo '✅ '.count($permissions)." permissions créées/vérifiées\n";
    echo '✅ '.count($roles)." rôles créés/vérifiés\n";
    echo "✅ Permissions attribuées à tous les rôles\n";
    echo '✅ '.$usersWithoutRole->count()." utilisateurs sans rôle traités\n";
    echo "✅ Cache des permissions réinitialisé\n";

    echo "\n=== Test des permissions ===\n";

    // Tester quelques permissions importantes
    $testUsers = User::with('roles')->limit(3)->get();
    foreach ($testUsers as $user) {
        $roles = $user->roles->pluck('name')->join(', ');
        echo "👤 {$user->name} ({$user->email})\n";
        echo "   Rôles: $roles\n";
        echo '   Peut voir dashboard: '.($user->can('view_dashboard') ? '✅' : '❌')."\n";
        echo '   Peut voir annonces: '.($user->can('view_annonces') ? '✅' : '❌')."\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo '❌ ERREUR: '.$e->getMessage()."\n";
    echo 'Stack trace: '.$e->getTraceAsString()."\n";

    echo "\n=== Solutions alternatives ===\n";
    echo "1. Vérifiez que la base de données est accessible\n";
    echo "2. Assurez-vous que les tables Spatie Permission existent\n";
    echo "3. Exécutez: php artisan migrate\n";
    echo "4. Puis réexécutez ce script\n";
}

echo "\n=== Fin du script ===\n";
