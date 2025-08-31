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
use Illuminate\Support\Facades\Artisan;

echo "=== DIAGNOSTIC ET CORRECTION DES PROBLÈMES DE REDIRECTION ===\n\n";

// 1. Vérifier les middlewares appliqués aux routes principales
echo "=== VÉRIFICATION DES MIDDLEWARES ===\n";

// Obtenir les routes
$routes = Route::getRoutes();
$dashboardRoute = null;
$teachersRoute = null;
$comptabiliteRoute = null;

foreach ($routes as $route) {
    $uri = $route->uri();
    
    if ($uri === 'dashboard') {
        $dashboardRoute = $route;
    }
    
    if ($uri === 'esbtp/teachers') {
        $teachersRoute = $route;
    }
    
    if ($uri === 'esbtp/comptabilite') {
        $comptabiliteRoute = $route;
    }
}

if ($dashboardRoute) {
    echo "Route 'dashboard' trouvée. Middlewares: " . implode(', ', $dashboardRoute->middleware()) . "\n";
} else {
    echo "Route 'dashboard' non trouvée.\n";
}

if ($teachersRoute) {
    echo "Route 'esbtp/teachers' trouvée. Middlewares: " . implode(', ', $teachersRoute->middleware()) . "\n";
} else {
    echo "Route 'esbtp/teachers' non trouvée.\n";
}

if ($comptabiliteRoute) {
    echo "Route 'esbtp/comptabilite' trouvée. Middlewares: " . implode(', ', $comptabiliteRoute->middleware()) . "\n";
} else {
    echo "Route 'esbtp/comptabilite' non trouvée.\n";
}

// 2. Simuler une connexion et vérifier la redirection
echo "\n=== SIMULATION DE CONNEXION ===\n";

// Utilisateur à tester
$email = 'ruben@gmail.com';
$password = 'admin123';

if (Auth::attempt(['email' => $email, 'password' => $password])) {
    $user = Auth::user();
    echo "✅ Connexion réussie avec {$email}\n";
    echo "Utilisateur: {$user->name} (ID: {$user->id})\n";
    echo "Rôle: {$user->role}\n";
    
    // Vérifier le chemin de redirection
    $redirectTo = app('App\Providers\RouteServiceProvider')::HOME;
    echo "Chemin de redirection après connexion: {$redirectTo}\n";
    
    // Vérifier si l'utilisateur peut accéder au tableau de bord
    $middleware = app('Illuminate\Contracts\Http\Kernel');
    echo "Accès au tableau de bord: " . ($user->can('view_dashboard') ? "Autorisé" : "Non autorisé") . "\n";
    
    // Déconnexion
    Auth::logout();
    echo "✅ Déconnexion effectuée\n";
} else {
    echo "❌ Échec de connexion avec {$email}.\n";
}

// 3. Vérifier et corriger le fichier .env
echo "\n=== VÉRIFICATION DU FICHIER .ENV ===\n";

$envFile = base_path('.env');
$envContent = file_get_contents($envFile);

// Vérifier APP_INSTALLED
if (strpos($envContent, 'APP_INSTALLED=true') !== false) {
    echo "✓ APP_INSTALLED est défini à 'true'\n";
} else {
    // Ajouter ou modifier la variable
    if (strpos($envContent, 'APP_INSTALLED=') !== false) {
        $envContent = preg_replace('/APP_INSTALLED=.*/', 'APP_INSTALLED=true', $envContent);
    } else {
        $envContent .= "\nAPP_INSTALLED=true\n";
    }
    
    // Sauvegarder le fichier
    file_put_contents($envFile, $envContent);
    echo "✅ APP_INSTALLED défini à 'true' dans le fichier .env\n";
}

// 4. Effacer le cache
echo "\n=== EFFACEMENT DU CACHE ===\n";

try {
    Artisan::call('config:clear');
    echo "✅ Config cache effacé\n";
    
    Artisan::call('route:clear');
    echo "✅ Route cache effacé\n";
    
    Artisan::call('cache:clear');
    echo "✅ Application cache effacé\n";
    
    Artisan::call('view:clear');
    echo "✅ Vue cache effacé\n";
    
    // Générer une nouvelle clé d'application si nécessaire
    if (!env('APP_KEY')) {
        Artisan::call('key:generate');
        echo "✅ Nouvelle clé d'application générée\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors de l'effacement du cache: " . $e->getMessage() . "\n";
}

echo "\n=== RECOMMANDATIONS ===\n";
echo "1. Assurez-vous que le serveur web a les permissions d'accès aux fichiers.\n";
echo "2. Redémarrez le serveur web après ces modifications.\n";
echo "3. Effacez les cookies et le cache du navigateur avant de vous reconnecter.\n";
echo "4. Si les problèmes persistent, vérifiez les journaux d'erreurs dans 'storage/logs/laravel.log'.\n";

echo "\n=== DIAGNOSTICS TERMINÉS ===\n"; 