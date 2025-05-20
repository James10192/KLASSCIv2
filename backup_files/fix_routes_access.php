<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importer les classes nécessaires
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== CORRECTION DES PERMISSIONS ET ROUTES POUR LE SUPER ADMIN ===\n\n";

// Utilisateur à corriger
$email = 'ruben@gmail.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ Utilisateur {$email} introuvable.\n";
    exit(1);
}

echo "Utilisateur trouvé: {$user->name} (ID: {$user->id})\n";
echo "Rôle actuel: {$user->role}\n\n";

// 1. S'assurer que le rôle est bien superAdmin
if ($user->role !== 'superAdmin') {
    $user->role = 'superAdmin';
    $user->save();
    echo "✅ Rôle mis à jour à 'superAdmin'\n";
} else {
    echo "✓ Rôle déjà configuré comme 'superAdmin'\n";
}

// 2. Vérifier et corriger les rôles Spatie
try {
    // Vérifier si le rôle superAdmin existe dans Spatie
    $superAdminRole = Role::where('name', 'superAdmin')->first();
    
    if (!$superAdminRole) {
        // Créer le rôle s'il n'existe pas
        $superAdminRole = Role::create([
            'name' => 'superAdmin',
            'guard_name' => 'web'
        ]);
        echo "✅ Rôle 'superAdmin' créé dans Spatie Permissions\n";
    } else {
        echo "✓ Rôle 'superAdmin' existe déjà dans Spatie Permissions\n";
    }
    
    // Synchroniser l'utilisateur avec le rôle superAdmin
    $user->syncRoles(['superAdmin']);
    echo "✅ Utilisateur synchronisé avec le rôle 'superAdmin'\n";
    
    // Vérifier si l'utilisateur a le rôle
    if ($user->hasRole('superAdmin')) {
        echo "✓ L'utilisateur a bien le rôle 'superAdmin' dans Spatie\n";
    } else {
        echo "❌ Échec de l'attribution du rôle 'superAdmin'. Tentative avec assignRole...\n";
        $user->assignRole('superAdmin');
        
        if ($user->hasRole('superAdmin')) {
            echo "✅ Rôle attribué avec assignRole()\n";
        } else {
            echo "❌ Impossible d'attribuer le rôle avec assignRole()\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors de la gestion des rôles Spatie: " . $e->getMessage() . "\n";
}

// 3. Vérifier et créer les permissions nécessaires
try {
    $neededPermissions = [
        'view teachers',
        'create teachers',
        'edit teachers',
        'delete teachers',
        'view finances',
        'manage finances',
        'view classes',
        'create classes',
        'edit classes',
        'delete classes',
        'view students',
        'view_dashboard'
    ];
    
    echo "\n=== CRÉATION/VÉRIFICATION DES PERMISSIONS ===\n";
    
    foreach ($neededPermissions as $permName) {
        $permission = Permission::where('name', $permName)->first();
        
        if (!$permission) {
            Permission::create(['name' => $permName, 'guard_name' => 'web']);
            echo "✅ Permission '{$permName}' créée\n";
        } else {
            echo "✓ Permission '{$permName}' existe déjà\n";
        }
    }
    
    // Donner toutes les permissions au rôle superAdmin
    $allPermissions = Permission::all();
    $superAdminRole->syncPermissions($allPermissions);
    echo "✅ Toutes les permissions ont été attribuées au rôle 'superAdmin'\n";
    
    // Rafraîchir l'utilisateur pour être sûr que les permissions sont chargées
    $user = User::find($user->id);
    
    // Vérifier si l'utilisateur a les permissions
    echo "\n=== VÉRIFICATION DES PERMISSIONS DE L'UTILISATEUR ===\n";
    foreach ($neededPermissions as $permName) {
        if ($user->can($permName)) {
            echo "✓ L'utilisateur peut {$permName}\n";
        } else {
            echo "❌ L'utilisateur ne peut pas {$permName}\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors de la gestion des permissions: " . $e->getMessage() . "\n";
}

// 4. Réinitialiser le cache des routes et permissions
try {
    echo "\n=== RÉINITIALISATION DU CACHE ===\n";
    
    // Effacer le cache des routes
    $exitCode = Artisan::call('route:clear');
    echo "Route:clear: " . ($exitCode === 0 ? "✅ Succès" : "❌ Échec") . "\n";
    
    // Effacer le cache de configuration
    $exitCode = Artisan::call('config:clear');
    echo "Config:clear: " . ($exitCode === 0 ? "✅ Succès" : "❌ Échec") . "\n";
    
    // Effacer le cache de l'application
    $exitCode = Artisan::call('cache:clear');
    echo "Cache:clear: " . ($exitCode === 0 ? "✅ Succès" : "❌ Échec") . "\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors de la réinitialisation du cache: " . $e->getMessage() . "\n";
}

// 5. Réinitialiser le mot de passe pour s'assurer qu'il est correct
try {
    $password = 'admin123';
    $user->password = Hash::make($password);
    $user->save();
    
    echo "\n=== IDENTIFIANTS DE CONNEXION ===\n";
    echo "Email: {$user->email}\n";
    echo "Mot de passe: {$password}\n";
    echo "Rôle: {$user->role}\n";
    echo "Rôles Spatie: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage() . "\n";
}

echo "\n=== CORRECTION TERMINÉE ===\n";
echo "Vous pouvez maintenant vous connecter avec les identifiants ci-dessus.\n";
echo "Si les problèmes persistent, redémarrez le serveur et effacez les cookies de votre navigateur.\n"; 