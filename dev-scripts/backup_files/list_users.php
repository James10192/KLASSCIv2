<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Liste de tous les utilisateurs
$users = User::all();

echo "=== LISTE DE TOUS LES UTILISATEURS ===\n";
if ($users->isEmpty()) {
    echo "Aucun utilisateur trouvé dans la base de données.\n";
} else {
    echo "Nombre total d'utilisateurs: " . $users->count() . "\n\n";
    
    foreach ($users as $index => $user) {
        echo "Utilisateur #" . ($index + 1) . "\n";
        echo "ID: " . $user->id . "\n";
        echo "Nom: " . $user->name . "\n";
        echo "Nom d'utilisateur: " . $user->username . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Actif: " . ($user->is_active ? 'Oui' : 'Non') . "\n";
        echo "Rôles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
        echo "Créé le: " . $user->created_at . "\n";
        echo "-----------------------------------\n";
    }
}

// Liste des utilisateurs avec le rôle superAdmin
$superAdmins = User::role('superAdmin')->get();

echo "\n=== UTILISATEURS AVEC RÔLE SUPERADMIN ===\n";
if ($superAdmins->isEmpty()) {
    echo "Aucun utilisateur avec le rôle superAdmin n'a été trouvé.\n";
} else {
    echo "Nombre total de superAdmins: " . $superAdmins->count() . "\n\n";
    
    foreach ($superAdmins as $index => $admin) {
        echo "SuperAdmin #" . ($index + 1) . "\n";
        echo "ID: " . $admin->id . "\n";
        echo "Nom: " . $admin->name . "\n";
        echo "Nom d'utilisateur: " . $admin->username . "\n";
        echo "Email: " . $admin->email . "\n";
        echo "Actif: " . ($admin->is_active ? 'Oui' : 'Non') . "\n";
        echo "Créé le: " . $admin->created_at . "\n";
        echo "-----------------------------------\n";
    }
}

// Identifiants de connexion du superAdmin créé précédemment
$superAdmin = User::where('username', 'superadmin')->orWhere('email', 'superadmin@esbtp.com')->first();

echo "\n=== IDENTIFIANTS POUR LA CONNEXION SUPER ADMIN ===\n";
if ($superAdmin) {
    echo "Nom: " . $superAdmin->name . "\n";
    echo "Nom d'utilisateur: " . $superAdmin->username . "\n";
    echo "Email: " . $superAdmin->email . "\n";
    echo "Mot de passe: SuperAdmin@2024 (si c'est l'utilisateur créé précédemment)\n";
} else {
    echo "L'utilisateur superAdmin créé précédemment n'a pas été trouvé dans la base de données.\n";
}

echo "\nUtilisez ces identifiants pour vous connecter en tant que super administrateur.\n"; 