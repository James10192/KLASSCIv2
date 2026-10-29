<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importer les classes nécessaires
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

echo "=== TEST DE CONNEXION ET VÉRIFICATION DES PERMISSIONS ===\n\n";

// Tableau des comptes à tester
$accounts = [
    [
        'name' => 'Compte principal (ruben)',
        'email' => 'ruben@gmail.com',
        'password' => 'admin123'
    ],
    [
        'name' => 'Compte alternatif (admin)',
        'email' => 'admin@esbtp.com',
        'password' => 'admin123'
    ]
];

// Fonctionnalités à tester
$features = [
    'superAdmin' => 'Accès au tableau de bord Super Admin',
    'view_teachers' => 'Gestion des enseignants',
    'view_accounting' => 'Gestion de la comptabilité',
    'view_departments' => 'Gestion des départements',
    'view_classes' => 'Gestion des classes',
    'view_students' => 'Gestion des étudiants'
];

foreach ($accounts as $account) {
    echo "Tester le compte : {$account['name']}\n";
    echo "Email: {$account['email']}\n";
    echo "Mot de passe: {$account['password']}\n\n";
    
    // Tentative de connexion
    $loginSuccess = Auth::attempt([
        'email' => $account['email'],
        'password' => $account['password']
    ]);
    
    if ($loginSuccess) {
        echo "✅ Connexion réussie !\n";
        $user = Auth::user();
        echo "Utilisateur connecté: {$user->name} (ID: {$user->id})\n";
        echo "Rôle dans la base de données: {$user->role}\n";
        
        // Vérifier si Spatie Permissions est utilisé
        try {
            if (method_exists($user, 'getRoleNames')) {
                echo "Rôles Spatie: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
            }
        } catch (\Exception $e) {
            echo "Erreur lors de la récupération des rôles Spatie: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== VÉRIFICATION DES PERMISSIONS ===\n";
        
        foreach ($features as $permission => $description) {
            // Vérifier les permissions de différentes manières
            $hasPermissionByRole = $user->role === 'superAdmin';
            $hasPermissionByMethod = false;
            
            // Vérifier avec la méthode hasRole si disponible
            if (method_exists($user, 'hasRole')) {
                $hasPermissionByMethod = $user->hasRole('superAdmin');
            }
            
            // Vérifier les permissions spécifiques si available
            $hasSpecificPermission = false;
            if (method_exists($user, 'can')) {
                // Convertir la clé de permission en format Laravel
                $permissionKey = str_replace('_', ' ', $permission);
                $hasSpecificPermission = $user->can($permissionKey);
            }
            
            $checkResult = $hasPermissionByRole || $hasPermissionByMethod || $hasSpecificPermission;
            echo ($checkResult ? "✅" : "❌") . " {$description} - " . 
                 ($checkResult ? "Autorisé" : "Non autorisé") . "\n";
        }
        
        // Vérifier des routes spécifiques
        echo "\n=== VÉRIFICATION DES ROUTES CLÉS ===\n";
        
        $routes = [
            '/dashboard' => 'Tableau de bord',
            '/esbtp/teachers' => 'Gestion des enseignants',
            '/comptabilite' => 'Gestion de la comptabilité',
            '/esbtp/classes' => 'Gestion des classes'
        ];
        
        foreach ($routes as $route => $name) {
            // En Laravel, toutes les routes sont autorisées pour un superAdmin par défaut
            $isAuthorized = $user->role === 'superAdmin';
            echo ($isAuthorized ? "✅" : "❌") . " {$name} ({$route}) - " . 
                 ($isAuthorized ? "Autorisé" : "Non autorisé") . "\n";
        }
        
        // Déconnexion
        Auth::logout();
        echo "\n✅ Déconnexion effectuée.\n";
    } else {
        echo "❌ Échec de la connexion avec {$account['email']}.\n";
        
        // Vérifier si l'utilisateur existe
        $user = User::where('email', $account['email'])->first();
        if ($user) {
            echo "L'utilisateur existe mais le mot de passe est incorrect ou le compte est désactivé.\n";
            echo "ID: {$user->id}, Nom: {$user->name}, Rôle: {$user->role}\n";
            
            // Réinitialisation du mot de passe
            echo "\nRéinitialisation du mot de passe...\n";
            $user->password = \Illuminate\Support\Facades\Hash::make($account['password']);
            $user->save();
            echo "Mot de passe réinitialisé. Veuillez réessayer de vous connecter.\n";
        } else {
            echo "Aucun utilisateur trouvé avec cet email. L'utilisateur n'existe pas dans la base de données.\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Vérifier les middlewares et la configuration de l'application
echo "=== VÉRIFICATION DE LA CONFIGURATION DE L'APPLICATION ===\n\n";

// Vérifier l'existence de middlewares pertinents
$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
$middlewares = $kernel->getMiddleware();
$middlewareGroups = property_exists($kernel, 'middlewareGroups') ? $kernel->middlewareGroups : [];

echo "Middlewares globaux:\n";
foreach ($middlewares as $key => $middleware) {
    echo "- {$key}: {$middleware}\n";
}

echo "\nGroupes de middlewares:\n";
foreach ($middlewareGroups as $group => $groupMiddlewares) {
    echo "Groupe '{$group}':\n";
    foreach ($groupMiddlewares as $middleware) {
        echo "- {$middleware}\n";
    }
}

// Vérifier les provider d'authentification
echo "\nProviders d'authentification:\n";
$authProviders = config('auth.providers');
foreach ($authProviders as $provider => $config) {
    echo "- {$provider}: " . json_encode($config) . "\n";
}

echo "\n=== TEST TERMINÉ ===\n"; 