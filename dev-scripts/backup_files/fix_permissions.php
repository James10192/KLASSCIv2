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
    function ensurePermissionExists($permissionName, $description = null) {
        try {
            $permission = Permission::where('name', $permissionName)->first();
            if (!$permission) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'description' => $description
                ]);
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

        // Paiements - Ajout des permissions pour les paiements
        'view-paiements', 'create-paiements', 'edit-paiements', 'delete-paiements', 'validate-paiements',

        //Comptabilité
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

        // Permissions pour l'émargement des enseignants
        'view_own_attendance' => 'Voir ses propres émargements',
        'sign_attendance' => 'Signer sa présence',
        'manage_attendance_codes' => 'Gérer les codes d\'émargement',
        'validate_attendance' => 'Valider les émargements',
        'view_all_attendance' => 'Voir tous les émargements',
    ];

    echo "Vérification et création des permissions...\n";
    $createdPermissions = [];
    foreach ($allPermissions as $permissionName => $description) {
        if (is_numeric($permissionName)) {
            $permissionName = $description;
            $description = null;
        }
        $permission = ensurePermissionExists($permissionName, $description);
        if ($permission) {
            $createdPermissions[] = $permission;
        }
    }

    echo "\nCréation/Vérification des rôles...\n";

    // Récupérer ou créer le rôle enseignant
    $enseignant = Role::where('name', 'enseignant')->first();
    if (!$enseignant) {
        $enseignant = Role::create(['name' => 'enseignant', 'guard_name' => 'web']);
        echo "✅ Rôle 'enseignant' créé.\n";
    } else {
        echo "ℹ️ Rôle 'enseignant' existe déjà.\n";
    }

    // Permissions spécifiques pour l'enseignant
    $enseignantPermissions = [
        'view_own_attendance',
        'sign_attendance',
        'view_own_timetable',
        'view_own_classes',
        'manage_own_notes',
        'view_own_students'
    ];

    echo "\nAssignation des permissions au rôle enseignant...\n";
    foreach ($enseignantPermissions as $permissionName) {
        try {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission && !$enseignant->hasPermissionTo($permission)) {
                $enseignant->givePermissionTo($permission);
                echo "✅ Permission '{$permission->name}' assignée au rôle 'enseignant'.\n";
            } else if ($permission) {
                echo "ℹ️ Le rôle 'enseignant' a déjà la permission '{$permission->name}'.\n";
            }
        } catch (\Exception $e) {
            echo "❌ ERREUR lors de l'assignation de la permission '{$permissionName}': " . $e->getMessage() . "\n";
        }
    }

    // Permissions pour le secrétaire concernant l'émargement
    $secretaireAttendancePermissions = [
        'manage_attendance_codes',
        'validate_attendance',
        'view_all_attendance'
    ];

    $secretaire = Role::where('name', 'secretaire')->first();
    if ($secretaire) {
        echo "\nAssignation des permissions d'émargement au rôle secrétaire...\n";
        foreach ($secretaireAttendancePermissions as $permissionName) {
            try {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && !$secretaire->hasPermissionTo($permission)) {
                    $secretaire->givePermissionTo($permission);
                    echo "✅ Permission '{$permission->name}' assignée au rôle 'secretaire'.\n";
                } else if ($permission) {
                    echo "ℹ️ Le rôle 'secretaire' a déjà la permission '{$permission->name}'.\n";
                }
            } catch (\Exception $e) {
                echo "❌ ERREUR lors de l'assignation de la permission '{$permissionName}': " . $e->getMessage() . "\n";
            }
        }
    }

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

    echo "\nAssignation des permissions de paiement au rôle secretaire...\n";
    $paiementPermissions = [
        'view-paiements', 'create-paiements', 'edit-paiements', 'validate-paiements'
    ];

    foreach ($paiementPermissions as $permissionName) {
        try {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission && !$secretaire->hasPermissionTo($permission)) {
                $secretaire->givePermissionTo($permission);
                echo "✅ Permission '{$permission->name}' assignée au rôle 'secretaire'.\n";
            } else if ($permission) {
                echo "ℹ️ Le rôle 'secretaire' a déjà la permission '{$permission->name}'.\n";
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

} catch (\Exception $e) {
    echo "\n❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du script ===\n";
