#!/usr/bin/env php
<?php

/**
 * Script pour ajouter les permissions manquantes au rôle de secrétaire
 *
 * Ce script doit être exécuté à la racine du projet avec la commande :
 * php fix_permissions.php
 */

// Autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Script de réparation des permissions ===\n";

try {
    // Réinitialiser les caches des permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    echo "✅ Cache des permissions réinitialisé.\n\n";

    // Fonction pour s'assurer qu'une permission existe
    function ensurePermissionExists($permissionName) {
        try {
            $permission = Permission::where('name', $permissionName)->first();
            if (!$permission) {
                $permission = Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
                echo "✅ Permission '{$permissionName}' créée.\n";
            } else {
                echo "ℹ️ Permission '{$permissionName}' existe déjà.\n";
            }
            return $permission;
        } catch (\Exception $e) {
            echo "❌ ERREUR lors de la création de la permission '{$permissionName}': " . $e->getMessage() . "\n";
            return null;
        }
    }

    // Définir toutes les permissions nécessaires
    $allPermissions = [
        // Filières
        'view_filieres', 'create_filieres', 'edit_filieres', 'delete_filieres',

        // Formations
        'view_formations', 'create_formations', 'edit_formations', 'delete_formations',

        // Niveaux d'études
        'view_niveaux_etudes', 'create_niveaux_etudes', 'edit_niveaux_etudes', 'delete_niveaux_etudes',

        // Classes
        'view_classes', 'create_classe', 'edit_classes', 'delete_classes',

        // Étudiants
        'view_students', 'create_student', 'edit_students', 'delete_students',
        'view_own_profile',

        // Examens
        'view_exams', 'create_exam', 'edit_exams', 'delete_exams',
        'view_own_exams',

        // Matières
        'view_matieres', 'create_matieres', 'edit_matieres', 'delete_matieres',

        // Notes
        'view_grades', 'create_grade', 'edit_grades', 'delete_grades',
        'view_own_grades',

        // Bulletins
        'view_bulletins', 'generate_bulletin', 'edit_bulletins', 'delete_bulletins',
        'view_own_bulletin',

        // Emplois du temps
        'view_timetables', 'create_timetable', 'edit_timetables', 'delete_timetables',
        'view_own_timetable',

        // Messages
        'send_messages', 'receive_messages',

        // Présences
        'view_attendances', 'create_attendance', 'edit_attendances', 'delete_attendances',
        'view_own_attendances','edit attendances',

        // Inscriptions
        'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.delete', 'inscriptions.validate',

        // Frais ESBTP
        'frais.view', 'frais.create', 'frais.edit', 'frais.delete', 'frais.configure',

        // Paiements - Ajout des permissions pour les paiements (ancien format)
        'view-paiements', 'create-paiements', 'edit-paiements', 'delete-paiements', 'validate-paiements',
        
        // Paiements - Nouveau format utilisé par ESBTPPaiementController
        'paiements.view', 'paiements.create', 'paiements.edit', 'paiements.delete', 'paiements.validate',

        //Comptabilité - Permissions de base
        'access_comptabilite_module',
        'view_paiements',
        'create_paiements',
        'edit_paiements',
        'delete_paiements',
        'view_frais_scolarite',
        'create_frais_scolarite',
        'edit_frais_scolarite',
        'delete_frais_scolarite',
        'view_depenses',
        'create_depenses',
        'edit_depenses',
        'delete_depenses',
        'view_salaires',
        'create_salaires',
        'edit_salaires',
        'delete_salaires',
        'view_bourses',
        'create_bourses',
        'edit_bourses',
        'delete_bourses',
        'view_reporting_financier',
        'export_reporting_financier',
        'view_teacher_dashboard',

        // Comptabilité - Permissions Tâche #1 KLASSCI
        'comptabilite.dashboard.view',
        'comptabilite.bons.approve',
        'comptabilite.config.manage',
        'comptabilite.reports.export',
        'comptabilite.relances.send',

        // Sécurité et Audit - Tâche #10 KLASSCI (8 permissions)
        'security.audit.view',
        'security.audit.export',
        'security.audit.delete',
        'security.users.monitor',
        'security.events.view',
        'security.backup.view',
        'security.backup.create',
        'security.backup.restore',

        // Comptabilité Granulaire - Tâche #10 KLASSCI (8 permissions)
        'comptabilite.audit.view',
        'comptabilite.audit.export',
        'comptabilite.security.manage',
        'comptabilite.permissions.manage',
        'comptabilite.data.encrypt',
        'comptabilite.data.decrypt',
        'comptabilite.sensitive.access',
        'comptabilite.transactions.monitor',

        // Workflow Avancé - Tâche #10 KLASSCI (6 permissions)
        'workflow.approve.level1',
        'workflow.approve.level2',
        'workflow.approve.level3',
        'workflow.reject.any',
        'workflow.bypass.approval',
        'workflow.audit.view',

        // Validation et Reporting - Tâche #10 KLASSCI (9 permissions)
        'validation.financial.basic',
        'validation.financial.advanced',
        'validation.bulk.operations',
        'validation.emergency.override',
        'reports.financial.confidential',
        'reports.audit.complete',
        'reports.security.incidents',
        'reports.compliance.klassci',

        // Permissions bons de sortie - Tâche #5 KLASSCI
        'comptabilite.bons.create',
        'comptabilite.bons.edit',
        'comptabilite.bons.view',
        'comptabilite.bons.pay',
    ];

    echo "Vérification et création des permissions...\n";
    $createdPermissions = [];
    foreach ($allPermissions as $permissionName) {
        $permission = ensurePermissionExists($permissionName);
        if ($permission) {
            $createdPermissions[] = $permission;
        }
    }

    echo "\nCréation/Vérification des rôles...\n";
    // Récupérer ou créer le rôle superAdmin
    $superAdmin = Role::where('name', 'superAdmin')->first();
    if (!$superAdmin) {
        $superAdmin = Role::create(['name' => 'superAdmin', 'guard_name' => 'web']);
        echo "✅ Rôle 'superAdmin' créé.\n";
    } else {
        echo "ℹ️ Rôle 'superAdmin' existe déjà.\n";
    }

    // Récupérer ou créer le rôle secretaire
    $secretaire = Role::where('name', 'secretaire')->first();
    if (!$secretaire) {
        $secretaire = Role::create(['name' => 'secretaire', 'guard_name' => 'web']);
        echo "✅ Rôle 'secretaire' créé.\n";
    } else {
        echo "ℹ️ Rôle 'secretaire' existe déjà.\n";
    }

    echo "\nAssignation des permissions au rôle superAdmin...\n";
    foreach ($createdPermissions as $permission) {
        try {
            if (!$superAdmin->hasPermissionTo($permission)) {
                $superAdmin->givePermissionTo($permission);
                echo "✅ Permission '{$permission->name}' assignée au rôle 'superAdmin'.\n";
            } else {
                echo "ℹ️ Le rôle 'superAdmin' a déjà la permission '{$permission->name}'.\n";
            }
        } catch (\Exception $e) {
            echo "❌ ERREUR lors de l'assignation de la permission '{$permission->name}': " . $e->getMessage() . "\n";
        }
    }

    echo "\nAssignation des permissions KLASSCI comptabilité au rôle secretaire...\n";

    // Permissions pour secrétaires selon Tâche #10 KLASSCI : Permissions limitées (lecture, approbation niveau 1)
    $secretaireComptabilitePermissions = [
        // Permissions de base comptabilité
        'access_comptabilite_module',
        'view_paiements', 'create_paiements', 'edit_paiements', 'validate_paiements',
        'view_depenses', 'create_depenses', 'edit_depenses',
        'view_frais_scolarite', 'create_frais_scolarite', 'edit_frais_scolarite',

        // Permissions Tâche #1 - accès limité
        'comptabilite.dashboard.view',
        'comptabilite.relances.send',

        // Permissions audit - lecture seule
        'security.audit.view',
        'comptabilite.audit.view',

        // Workflow - niveau 1 seulement
        'workflow.approve.level1',
        'workflow.audit.view',

        // Validation de base
        'validation.financial.basic',

        // Bons de sortie - création et édition
        'comptabilite.bons.create',
        'comptabilite.bons.edit',
        'comptabilite.bons.view',

        // Frais ESBTP pour secrétaire
        'frais.view', 'frais.create', 'frais.edit', 'frais.configure',

        // Permissions anciennes format
        'view-paiements', 'create-paiements', 'edit-paiements', 'validate-paiements',
        
        // Permissions nouveau format pour ESBTPPaiementController
        'paiements.view', 'paiements.create', 'paiements.edit', 'paiements.delete', 'paiements.validate'
    ];

    foreach ($secretaireComptabilitePermissions as $permissionName) {
        try {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission && !$secretaire->hasPermissionTo($permission)) {
                $secretaire->givePermissionTo($permission);
                echo "✅ Permission comptabilité '{$permission->name}' assignée au rôle 'secretaire'.\n";
            } else if ($permission) {
                echo "ℹ️ Le rôle 'secretaire' a déjà la permission '{$permission->name}'.\n";
            } else {
                echo "⚠️ Permission '{$permissionName}' non trouvée.\n";
            }
        } catch (\Exception $e) {
            echo "❌ ERREUR lors de l'assignation de la permission '{$permissionName}': " . $e->getMessage() . "\n";
        }
    }

    // Vérifier les utilisateurs avec le rôle superAdmin
    echo "\nUtilisateurs avec le rôle superAdmin :\n";
    $users = User::role('superAdmin')->get();
    if ($users->count() > 0) {
        foreach ($users as $user) {
            echo "- {$user->name} ({$user->email})\n";
            // Réassigner le rôle pour être sûr
            if (!$user->hasRole('superAdmin')) {
                $user->assignRole('superAdmin');
                echo "  ✅ Rôle 'superAdmin' réassigné.\n";
            }
        }
    } else {
        echo "⚠️ Aucun utilisateur n'a le rôle superAdmin.\n";
    }

    echo "\nVérification finale des permissions du rôle superAdmin :\n";
    $permissions = $superAdmin->permissions;
    foreach ($permissions as $permission) {
        echo "- {$permission->name}\n";
    }

    echo "\nVérification finale des permissions du rôle secretaire :\n";
    $permissions = $secretaire->permissions;
    foreach ($permissions as $permission) {
        echo "- {$permission->name}\n";
    }

    echo "\nNettoyage des caches...\n";
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    \Artisan::call('permission:cache-reset');

    // Créer la permission si elle n'existe pas
    $permission = Permission::firstOrCreate(['name' => 'edit_timetables']);

    // Récupérer le rôle superAdmin
    $superAdminRole = Role::where('name', 'superAdmin')->first();

    if ($superAdminRole) {
        // Assigner la permission au rôle superAdmin
        $superAdminRole->givePermissionTo($permission);
        echo "Permission 'edit_timetables' créée et assignée au rôle superAdmin.\n";
    } else {
        echo "Le rôle superAdmin n'existe pas.\n";
    }

    // Récupérer le rôle de secrétaire
    $secretaireRole = Role::findByName('secretaire');

    if (!$secretaireRole) {
        echo "Erreur : Le rôle 'secretaire' n'existe pas.\n";
        exit(1);
    }

    // Liste des permissions à ajouter
    $permissionsToAdd = [


        // matieres
        'view_matieres',
        // Emplois du temps
        'view_timetables',
        'create_timetable',
        'edit_timetables',

        // Bulletins
        'view_bulletins',
        'generate_bulletin',

        // Présences
        'edit_attendances',
        'edit attendances',


        // Étudiants
        'edit_students','view_students', 'create_student',

         // Messages
         'send_messages', 'receive_messages',

         // Présences
         'view_attendances', 'create_attendance', 'edit_attendances',
    ];

    // Vérifier les permissions existantes
    $existingPermissions = $secretaireRole->permissions->pluck('name')->toArray();
    echo "Permissions existantes pour le rôle 'secretaire' :\n";
    foreach ($existingPermissions as $permission) {
        echo "- $permission\n";
    }

    // Ajouter les permissions manquantes
    $addedPermissions = [];
    foreach ($permissionsToAdd as $permissionName) {
        if (!in_array($permissionName, $existingPermissions)) {
            $permission = Permission::findByName($permissionName);
            if ($permission) {
                $secretaireRole->givePermissionTo($permission);
                $addedPermissions[] = $permissionName;
            } else {
                echo "Avertissement : La permission '$permissionName' n'existe pas dans la base de données.\n";
            }
        } else {
            echo "La permission '$permissionName' est déjà attribuée au rôle 'secretaire'.\n";
        }
    }

    // Afficher les permissions ajoutées
    if (count($addedPermissions) > 0) {
        echo "\nPermissions ajoutées au rôle 'secretaire' :\n";
        foreach ($addedPermissions as $permission) {
            echo "- $permission\n";
        }
    } else {
        echo "\nAucune nouvelle permission n'a été ajoutée.\n";
    }

    // Vérifier les permissions après mise à jour
    $secretaireRole->refresh();
    $updatedPermissions = $secretaireRole->permissions->pluck('name')->toArray();
    echo "\nPermissions actuelles pour le rôle 'secretaire' :\n";
    foreach ($updatedPermissions as $permission) {
        echo "- $permission\n";
    }

    echo "\nMise à jour des permissions terminée avec succès.\n";

    // Permissions teacher
    $teacherPermissions = [
        'view_teacher_dashboard',
        'access_teacher_attendance',
        'access_teacher_grades',
        'access_teacher_timetable',
        'view_attendances',
        'create_attendance',
        'edit_attendances',
        'view_grades',
        'create_grade',
        'edit_grades',
        'view_timetables',
        'view_matieres',
        'send_messages',
        'receive_messages',
        // Ajoute ici toutes les permissions nécessaires à l'enseignant
    ];

    // Créer le rôle teacher s'il n'existe pas
    $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

    // Vérifier les permissions existantes
    $existingTeacherPermissions = $teacherRole->permissions->pluck('name')->toArray();
    echo "Permissions existantes pour le rôle 'teacher' :\n";
    foreach ($existingTeacherPermissions as $permission) {
        echo "- $permission\n";
    }

    // Ajouter les permissions manquantes
    $addedTeacherPermissions = [];
    foreach ($teacherPermissions as $permissionName) {
        if (!in_array($permissionName, $existingTeacherPermissions)) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            $teacherRole->givePermissionTo($permission);
            $addedTeacherPermissions[] = $permissionName;
        } else {
            echo "La permission '$permissionName' est déjà attribuée au rôle 'teacher'.\n";
        }
    }

    // Afficher les permissions ajoutées
    if (count($addedTeacherPermissions) > 0) {
        echo "\nPermissions ajoutées au rôle 'teacher' :\n";
        foreach ($addedTeacherPermissions as $permission) {
            echo "- $permission\n";
        }
    } else {
        echo "\nAucune nouvelle permission n'a été ajoutée au rôle 'teacher'.\n";
    }

    // Vérifier les permissions après mise à jour
    $teacherRole->refresh();
    $updatedTeacherPermissions = $teacherRole->permissions->pluck('name')->toArray();
    echo "\nPermissions actuelles pour le rôle 'teacher' :\n";
    foreach ($updatedTeacherPermissions as $permission) {
        echo "- $permission\n";
    }

    // Permission pour la génération de codes d'émargement
    $generateAttendanceCodes = 'generate-attendance-codes';
    if (!Permission::where('name', $generateAttendanceCodes)->exists()) {
        Permission::create(['name' => $generateAttendanceCodes]);
        echo "Permission 'generate-attendance-codes' créée.\n";
    }

    // Attribution au superAdmin
    $superAdminRole = Role::where('name', 'superAdmin')->first();
    if ($superAdminRole && !$superAdminRole->hasPermissionTo($generateAttendanceCodes)) {
        $superAdminRole->givePermissionTo($generateAttendanceCodes);
        echo "Permission 'generate-attendance-codes' attribuée à superAdmin.\n";
    }

    // (Optionnel) Attribution au secrétaire
    $secretaireRole = Role::where('name', 'secretaire')->first();
    if ($secretaireRole && !$secretaireRole->hasPermissionTo($generateAttendanceCodes)) {
        $secretaireRole->givePermissionTo($generateAttendanceCodes);
        echo "Permission 'generate-attendance-codes' attribuée à secretaire.\n";
    }

    // Permissions pour le rôle étudiant
    $etudiantPermissions = [
        'view_own_bulletin',
        'view_own_profile',
        'view_own_grades',
        'view_own_timetable',
        'view_own_attendances',
        'view_own_exams',
        'view_own_attendance',
    ];

    // Créer le rôle etudiant s'il n'existe pas
    $etudiantRole = Role::firstOrCreate(['name' => 'etudiant', 'guard_name' => 'web']);

    // Vérifier les permissions existantes
    $existingEtudiantPermissions = $etudiantRole->permissions->pluck('name')->toArray();
    echo "Permissions existantes pour le rôle 'etudiant' :\n";
    foreach ($existingEtudiantPermissions as $permission) {
        echo "- $permission\n";
    }

    // Ajouter les permissions manquantes
    $addedEtudiantPermissions = [];
    foreach ($etudiantPermissions as $permissionName) {
        $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        if (!in_array($permissionName, $existingEtudiantPermissions)) {
            $etudiantRole->givePermissionTo($permission);
            $addedEtudiantPermissions[] = $permissionName;
        } else {
            echo "La permission '$permissionName' est déjà attribuée au rôle 'etudiant'.\n";
        }
    }

    // Afficher les permissions ajoutées
    if (count($addedEtudiantPermissions) > 0) {
        echo "\nPermissions ajoutées au rôle 'etudiant' :\n";
        foreach ($addedEtudiantPermissions as $permission) {
            echo "- $permission\n";
        }
    } else {
        echo "\nAucune nouvelle permission n'a été ajoutée au rôle 'etudiant'.\n";
    }

    // Vérifier les permissions après mise à jour
    $etudiantRole->refresh();
    $updatedEtudiantPermissions = $etudiantRole->permissions->pluck('name')->toArray();
    echo "\nPermissions actuelles pour le rôle 'etudiant' :\n";
    foreach ($updatedEtudiantPermissions as $permission) {
        echo "- $permission\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}



echo "\n=== Fin du script ===\n";
