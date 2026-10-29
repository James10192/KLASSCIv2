<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Rechercher les super administrateurs
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

echo "=== RECHERCHE DE SUPER ADMINISTRATEURS ===\n\n";

// Chercher par le rôle 'superAdmin'
$superAdmins = User::where('role', 'superAdmin')->get();
$foundSuperAdmin = false;

if ($superAdmins->count() > 0) {
    echo "Super administrateurs trouvés par rôle 'superAdmin':\n";
    foreach ($superAdmins as $admin) {
        echo "- {$admin->name} ({$admin->email})\n";
        $foundSuperAdmin = true;
    }
    echo "\n";
}

// Vérifier si le package spatie/laravel-permission est utilisé
try {
    $superAdminRole = Role::where('name', 'superAdmin')->first();
    if ($superAdminRole) {
        $spatieAdmins = User::role('superAdmin')->get();
        
        if ($spatieAdmins->count() > 0) {
            echo "Super administrateurs trouvés via le package Spatie Permissions:\n";
            foreach ($spatieAdmins as $admin) {
                echo "- {$admin->name} ({$admin->email})\n";
                $foundSuperAdmin = true;
            }
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "Note: Le package Spatie Permissions n'est pas configuré ou n'est pas utilisé.\n\n";
}

if (!$foundSuperAdmin) {
    echo "Aucun super administrateur trouvé dans le système!\n\n";
    
    // Créer un super administrateur
    echo "=== CRÉATION D'UN SUPER ADMINISTRATEUR ===\n\n";
    
    $admin = new User();
    $admin->name = 'Super Admin';
    $admin->email = 'admin@esbtp.com';
    $admin->role = 'superAdmin';
    $admin->password = Hash::make('admin123');
    $admin->save();
    
    echo "Un nouveau super administrateur a été créé avec succès!\n";
    echo "Identifiants de connexion:\n";
    echo "- Email: admin@esbtp.com\n";
    echo "- Mot de passe: admin123\n\n";
    
    try {
        // Si Spatie Permissions est utilisé, assigner également le rôle
        if (isset($superAdminRole)) {
            $admin->assignRole('superAdmin');
            echo "Le rôle 'superAdmin' a également été assigné via Spatie Permissions.\n";
        }
    } catch (\Exception $e) {
        // Ignorer l'erreur si Spatie n'est pas utilisé
    }
} else {
    // Réinitialiser le mot de passe d'un super administrateur existant
    $adminToReset = $superAdmins->first() ?? $spatieAdmins->first();
    
    $newPassword = 'admin123';
    $adminToReset->password = Hash::make($newPassword);
    $adminToReset->save();
    
    echo "=== MOT DE PASSE RÉINITIALISÉ ===\n";
    echo "Le mot de passe du super administrateur {$adminToReset->name} a été réinitialisé.\n";
    echo "Nouveaux identifiants de connexion:\n";
    echo "- Email: {$adminToReset->email}\n";
    echo "- Mot de passe: {$newPassword}\n";
}

// Afficher également tous les utilisateurs qui semblent être des administrateurs
echo "\n=== AUTRES UTILISATEURS ADMINISTRATIFS ===\n";
$otherAdmins = User::where('role', 'like', '%admin%')
                   ->orWhere('role', 'secretaire')
                   ->orWhere('name', 'like', '%admin%')
                   ->orWhere('email', 'like', '%admin%')
                   ->get();

if ($otherAdmins->count() > 0) {
    foreach ($otherAdmins as $admin) {
        // Ne pas afficher les super admins déjà listés
        if ($admin->role !== 'superAdmin') {
            echo "- {$admin->name} ({$admin->email}), Rôle: {$admin->role}\n";
        }
    }
} else {
    echo "Aucun autre utilisateur administratif trouvé.\n";
} 