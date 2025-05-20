<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importer les classes nécessaires
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

echo "=== DIAGNOSTIC DES PROBLÈMES D'ACCÈS AUX ROUTES ===\n\n";

// Connexion avec le compte ruben
$email = 'ruben@gmail.com';
$password = 'admin123';

$loginSuccess = Auth::attempt([
    'email' => $email,
    'password' => $password
]);

if ($loginSuccess) {
    $user = Auth::user();
    echo "✅ Connexion réussie avec {$email}\n";
    echo "Utilisateur: {$user->name} (ID: {$user->id})\n";
    echo "Rôle: {$user->role}\n";
    
    // Vérifier si Spatie Permissions est utilisé
    if (method_exists($user, 'getRoleNames')) {
        echo "Rôles Spatie: " . implode(', ', $user->getRoleNames()->toArray()) . "\n\n";
    }
    
    // Vérifier les contrôleurs pertinents
    echo "=== VÉRIFICATION DES CONTRÔLEURS ===\n";
    
    $controllersToCheck = [
        'App\Http\Controllers\SuperAdminTeacherController',
        'App\Http\Controllers\SuperAdmin\SuperAdminTeacherController',
        'App\Http\Controllers\TeacherAdminController',
        'App\Http\Controllers\DepenseController',
        'App\Http\Controllers\CategorieDepenseController',
        'App\Http\Controllers\ESBTPComptabiliteController',
        'App\Http\Controllers\DashboardController'
    ];
    
    foreach ($controllersToCheck as $controller) {
        echo "Contrôleur: {$controller} - " . (class_exists($controller) ? "✅ Existe" : "❌ N'existe pas") . "\n";
    }
    
    // Vérifier les routes
    echo "\n=== ROUTES ENREGISTRÉES POUR LA GESTION DES ENSEIGNANTS ===\n";
    
    $routes = Route::getRoutes();
    $teacherRoutes = [];
    $comptabiliteRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        $name = $route->getName() ?? 'Sans nom';
        $methods = implode(', ', $route->methods());
        $action = $route->getActionName();
        
        if (strpos($uri, 'teachers') !== false || strpos($action, 'TeacherController') !== false || strpos($name, 'teachers') !== false) {
            $teacherRoutes[] = [
                'uri' => $uri,
                'name' => $name,
                'methods' => $methods,
                'action' => $action
            ];
        }
        
        if (strpos($uri, 'compt') !== false || strpos($uri, 'depense') !== false || strpos($action, 'Depense') !== false || strpos($action, 'Comptabilite') !== false) {
            $comptabiliteRoutes[] = [
                'uri' => $uri,
                'name' => $name,
                'methods' => $methods,
                'action' => $action
            ];
        }
    }
    
    if (count($teacherRoutes) > 0) {
        foreach ($teacherRoutes as $route) {
            echo "URI: {$route['uri']}, Méthodes: {$route['methods']}, Action: {$route['action']}\n";
        }
    } else {
        echo "Aucune route trouvée pour la gestion des enseignants.\n";
    }
    
    echo "\n=== ROUTES ENREGISTRÉES POUR LA COMPTABILITÉ ===\n";
    
    if (count($comptabiliteRoutes) > 0) {
        foreach ($comptabiliteRoutes as $route) {
            echo "URI: {$route['uri']}, Méthodes: {$route['methods']}, Action: {$route['action']}\n";
        }
    } else {
        echo "Aucune route trouvée pour la comptabilité.\n";
    }
    
    // Vérifier la présence des vues
    echo "\n=== VÉRIFICATION DES VUES ===\n";
    
    $viewsToCheck = [
        'esbtp/teachers/index.blade.php',
        'esbtp/teachers/create.blade.php',
        'esbtp/teachers/edit.blade.php',
        'esbtp/comptabilite/index.blade.php',
        'esbtp/comptabilite/depenses/index.blade.php'
    ];
    
    foreach ($viewsToCheck as $view) {
        $viewPath = base_path('resources/views/' . $view);
        echo "Vue: {$view} - " . (file_exists($viewPath) ? "✅ Existe" : "❌ N'existe pas") . "\n";
    }
    
    // Vérifier les middlewares
    echo "\n=== MIDDLEWARES APPLIQUÉS AUX ROUTES ===\n";
    
    // Sélectionner quelques routes importantes pour vérifier leurs middlewares
    $routesToCheck = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if ($uri === 'dashboard' || $uri === 'esbtp/teachers' || $uri === 'comptabilite') {
            $routesToCheck[] = $route;
        }
    }
    
    foreach ($routesToCheck as $route) {
        $middlewares = $route->middleware();
        echo "Route: {$route->uri()}, Middlewares: " . implode(', ', $middlewares) . "\n";
        
        foreach ($middlewares as $middleware) {
            if ($middleware === 'auth' || $middleware === 'role:superAdmin' || strpos($middleware, 'permission:') === 0) {
                echo "  - {$middleware}: Ce middleware peut bloquer l'accès\n";
            }
        }
    }
    
    // Déconnexion
    Auth::logout();
    echo "\n✅ Déconnexion effectuée.\n";
} else {
    echo "❌ Échec de connexion avec {$email}.\n";
    
    // Vérifier si l'utilisateur existe
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "L'utilisateur existe mais le mot de passe est incorrect.\n";
        echo "Informations: ID: {$user->id}, Nom: {$user->name}, Rôle: {$user->role}\n";
        
        // Réinitialiser encore une fois le mot de passe
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->save();
        echo "Le mot de passe a été réinitialisé. Veuillez réessayer de vous connecter.\n";
    } else {
        echo "L'utilisateur avec l'email {$email} n'existe pas.\n";
    }
}

echo "\n=== DIAGNOSTIC TERMINÉ ===\n"; 