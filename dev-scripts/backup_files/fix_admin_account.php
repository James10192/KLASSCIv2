<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importer les classes nécessaires
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

echo "=== CORRECTION DU COMPTE ADMIN ALTERNATIF ===\n\n";

// Utilisateur à corriger
$email = 'admin@esbtp.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ Utilisateur {$email} introuvable.\n";
    exit(1);
}

echo "Utilisateur trouvé: {$user->name} (ID: {$user->id})\n";
echo "Rôle actuel: {$user->role}\n\n";

// S'assurer que le rôle est bien superAdmin
if ($user->role !== 'superAdmin') {
    $user->role = 'superAdmin';
    $user->save();
    echo "✅ Rôle mis à jour à 'superAdmin'\n";
} else {
    echo "✓ Rôle déjà configuré comme 'superAdmin'\n";
}

// Vérifier et corriger les rôles Spatie
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

// Réinitialiser le mot de passe pour s'assurer qu'il est correct
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