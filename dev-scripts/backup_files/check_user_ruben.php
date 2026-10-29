<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Rechercher l'utilisateur
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Vérifier l'utilisateur
$user = User::where('email', 'ruben@gmail.com')->first();

if ($user) {
    echo "=== INFORMATIONS UTILISATEUR ===\n";
    echo "ID: {$user->id}\n";
    echo "Nom: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Rôle: " . ($user->role ?? 'Non défini') . "\n";
    echo "Actif: " . (isset($user->is_active) && $user->is_active ? 'Oui' : 'Non') . "\n";
    echo "Date de création: " . $user->created_at . "\n\n";
    
    // Réinitialiser le mot de passe
    $newPassword = 'password123';
    $user->password = Hash::make($newPassword);
    $user->save();
    
    echo "=== MOT DE PASSE RÉINITIALISÉ ===\n";
    echo "Le mot de passe a été réinitialisé à: {$newPassword}\n";
    echo "Vous pouvez maintenant vous connecter avec:\n";
    echo "Email: {$user->email}\n";
    echo "Mot de passe: {$newPassword}\n";
} else {
    echo "=== UTILISATEUR NON TROUVÉ ===\n";
    echo "Aucun utilisateur avec l'email 'ruben@gmail.com' n'a été trouvé.\n";
    
    // Rechercher des utilisateurs aux rôles élevés
    echo "\n=== UTILISATEURS DISPONIBLES ===\n";
    $admins = User::whereIn('role', ['superAdmin', 'admin', 'secretaire'])
                    ->orWhere('name', 'like', '%admin%')
                    ->orWhere('email', 'like', '%admin%')
                    ->get();
                    
    if ($admins->isEmpty()) {
        echo "Aucun administrateur trouvé dans le système.\n";
    } else {
        foreach ($admins as $index => $admin) {
            echo "\nUtilisateur #" . ($index + 1) . "\n";
            echo "Nom: {$admin->name}\n";
            echo "Email: {$admin->email}\n";
            echo "Rôle: " . ($admin->role ?? 'Non défini') . "\n";
        }
    }
} 