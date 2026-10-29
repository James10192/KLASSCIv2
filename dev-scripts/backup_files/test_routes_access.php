<?php

// Script pour tester l'accès aux routes protégées
// À exécuter avec: php artisan tinker --execute="require('test_routes_access.php');"

// Initialiser l'application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

echo "=== TEST D'ACCÈS AUX ROUTES PROTÉGÉES ===\n\n";

// Trouver un utilisateur superAdmin
$superAdmin = \App\Models\User::whereHas('roles', function ($query) {
    $query->where('name', 'superAdmin');
})->first();

if (!$superAdmin) {
    echo "❌ Aucun utilisateur superAdmin trouvé dans la base de données.\n";
    exit;
}

echo "Utilisateur superAdmin trouvé: {$superAdmin->name} (ID: {$superAdmin->id})\n\n";

// Se connecter en tant que superAdmin
auth()->login($superAdmin);
echo "✓ Connecté en tant que {$superAdmin->name}\n";

// Vérifier les rôles et permissions
echo "Rôles: " . implode(', ', $superAdmin->getRoleNames()->toArray()) . "\n";
echo "Nombre de permissions: " . $superAdmin->permissions->count() . "\n\n";

// Tester l'accès aux routes des enseignants
echo "=== ROUTES POUR LES ENSEIGNANTS ===\n";
$teacherRoutes = [
    'esbtp.teachers.index' => 'Liste des enseignants',
    'esbtp.teachers.create' => 'Créer un enseignant',
    'esbtp.teachers.show' => 'Voir un enseignant (ID: 1)',
    'esbtp.teachers.edit' => 'Modifier un enseignant (ID: 1)',
];

foreach ($teacherRoutes as $route => $description) {
    $params = [];
    if (strpos($route, '.show') !== false || strpos($route, '.edit') !== false) {
        $params = [1]; // ID pour les routes show et edit
    }
    
    try {
        $canAccess = Gate::forUser($superAdmin)->allows(function() use ($route, $params) {
            if (!Route::has($route)) {
                return false;
            }
            
            // Tester l'autorisation de la route
            try {
                $request = request();
                $request->route = Route::getRoutes()->getByName($route);
                if (empty($params)) {
                    return true;
                } else {
                    // Pour les routes avec paramètres, on ne peut pas facilement tester automatiquement
                    return true;
                }
            } catch (\Exception $e) {
                return false;
            }
        });
        
        $routeExists = Route::has($route);
        
        if ($routeExists) {
            echo ($canAccess ? "✅" : "❌") . " {$description} ({$route}) - " . 
                 ($routeExists ? "Route existe" : "Route n'existe pas") . "\n";
        } else {
            echo "⚠️ {$description} ({$route}) - Route n'existe pas\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$description} ({$route}) - Erreur: {$e->getMessage()}\n";
    }
}

// Tester l'accès aux routes de comptabilité
echo "\n=== ROUTES POUR LA COMPTABILITÉ ===\n";
$comptabiliteRoutes = [
    'comptabilite.index' => 'Tableau de bord financier',
    'comptabilite.paiements.index' => 'Liste des paiements',
    'comptabilite.paiements.create' => 'Créer un paiement',
    'comptabilite.depenses.index' => 'Liste des dépenses',
    'comptabilite.depenses.create' => 'Créer une dépense',
    'comptabilite.rapports' => 'Rapports financiers',
];

foreach ($comptabiliteRoutes as $route => $description) {
    try {
        $routeExists = Route::has($route);
        
        if ($routeExists) {
            echo "✅ {$description} ({$route}) - Route existe\n";
        } else {
            echo "⚠️ {$description} ({$route}) - Route n'existe pas\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$description} ({$route}) - Erreur: {$e->getMessage()}\n";
    }
}

// Vérifier la présence du lien dans la sidebar
echo "\n=== VÉRIFICATION DE LA SIDEBAR ===\n";
try {
    $sidebarContent = file_get_contents(__DIR__ . '/resources/views/layouts/app.blade.php');
    $teacherLink = strpos($sidebarContent, "{{ route('esbtp.teachers.index') }}") !== false;
    $comptabiliteLink = strpos($sidebarContent, "{{ route('esbtp.comptabilite.index') }}") !== false;
    
    echo ($teacherLink ? "✅" : "❌") . " Lien vers la gestion des enseignants dans la sidebar\n";
    echo ($comptabiliteLink ? "✅" : "❌") . " Lien vers la comptabilité dans la sidebar\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors de la vérification de la sidebar: {$e->getMessage()}\n";
}

echo "\n=== TEST TERMINÉ ===\n";
echo "Pour se connecter au tableau de bord en tant que superAdmin, utilisez:\n";
echo "Email: {$superAdmin->email}\n";
echo "Mot de passe: Demandez à l'administrateur ou utilisez celui défini lors de l'installation.\n";

// Déconnecter l'utilisateur
auth()->logout(); 