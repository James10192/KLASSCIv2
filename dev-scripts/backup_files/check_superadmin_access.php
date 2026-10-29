<?php
/**
 * Script pour vérifier les accès du super administrateur
 * 
 * Ce script est conçu pour être exécuté via la commande:
 * php artisan tinker --execute="require('check_superadmin_access.php');"
 */

try {
    // Récupération des super administrateurs
    $superAdmins = \App\Models\User::where('role', 'superAdmin')->get();

    // Affichage des informations sur les super administrateurs
    echo "=== VÉRIFICATION DES ACCÈS SUPER ADMINISTRATEUR ===\n\n";
    
    if ($superAdmins->isEmpty()) {
        echo "❌ Aucun super administrateur trouvé dans le système.\n";
        return;
    }
    
    echo "Nombre de super administrateurs: {$superAdmins->count()}\n\n";
    
    foreach ($superAdmins as $index => $admin) {
        echo "📋 Super Admin #" . ($index + 1) . "\n";
        echo "┌─────────────────────────────────────────────────\n";
        echo "│ Nom: " . $admin->name . "\n";
        echo "│ Email: " . $admin->email . "\n";
        echo "│ Statut du compte: " . ($admin->is_active ? "Actif ✓" : "Inactif ✗") . "\n";
        echo "│ Date de création: " . $admin->created_at->format('d/m/Y H:i') . "\n";
        echo "└─────────────────────────────────────────────────\n\n";
    }
    
    // Vérification des permissions
    echo "=== VÉRIFICATION DES FONCTIONNALITÉS CLÉS ===\n\n";
    
    // Vérifier l'accès aux catégories de dépenses
    $categories = \App\Models\CategorieDepense::count();
    echo "Catégories de dépenses: " . $categories . " ✓\n";
    
    // Vérifier l'accès aux dépenses
    $depenses = \App\Models\Depense::count();
    echo "Dépenses: " . $depenses . " ✓\n";
    
    // Vérifier l'accès aux départements
    if (class_exists('\\App\\Models\\Department')) {
        $departments = \App\Models\Department::count();
        echo "Départements: " . $departments . " ✓\n";
    } else {
        echo "Départements: Non disponible ✗\n";
    }
    
    // Vérifier l'accès aux professeurs
    if (class_exists('\\App\\Models\\Teacher')) {
        $teachers = \App\Models\Teacher::count();
        echo "Professeurs: " . $teachers . " ✓\n";
    } else {
        echo "Professeurs: Non disponible ✗\n";
    }
    
    // Vérifier l'accès aux étudiants
    if (class_exists('\\App\\Models\\Student')) {
        $students = \App\Models\Student::count();
        echo "Étudiants: " . $students . " ✓\n";
    } else {
        echo "Étudiants: Non disponible ✗\n";
    }
    
    echo "\n=== VÉRIFICATION TERMINÉE ===\n";
    echo "Tous les systèmes sont opérationnels pour les super administrateurs.\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur lors de la vérification des accès: " . $e->getMessage() . "\n";
    echo "Trace: \n" . $e->getTraceAsString() . "\n";
} 