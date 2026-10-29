<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importer les classes
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Vérification de l'existence du rôle superAdmin
$superAdminRole = Role::where('name', 'superAdmin')->first();

if (!$superAdminRole) {
    echo "⚠️ Le rôle 'superAdmin' n'existe pas. Création du rôle...\n";
    $superAdminRole = Role::create(['name' => 'superAdmin']);
    echo "✅ Rôle 'superAdmin' créé avec succès.\n";
} else {
    echo "✅ Le rôle 'superAdmin' existe déjà.\n";
}

// Vérification des utilisateurs ayant le rôle superAdmin
$superAdmins = User::role('superAdmin')->get();

echo "\n=== UTILISATEURS SUPER ADMIN EXISTANTS ===\n";
if ($superAdmins->count() > 0) {
    foreach ($superAdmins as $admin) {
        echo "- {$admin->name} (username: {$admin->username}, email: {$admin->email})\n";
    }
} else {
    echo "Aucun utilisateur avec le rôle superAdmin n'a été trouvé.\n";
}

// Création d'un nouvel utilisateur superAdmin
$username = 'superadmin';
$email = 'admin@esbtp.com';
$password = 'Admin@2024';

// Vérifier si un utilisateur avec ce nom d'utilisateur ou email existe déjà
$existingUser = User::where('username', $username)->orWhere('email', $email)->first();

if ($existingUser) {
    echo "\n⚠️ Un utilisateur avec ce nom d'utilisateur ou cet email existe déjà.\n";
    echo "Utilisateur: {$existingUser->name} (username: {$existingUser->username}, email: {$existingUser->email})\n";
    
    // Vérifier si cet utilisateur a déjà le rôle superAdmin
    if (!$existingUser->hasRole('superAdmin')) {
        echo "Cet utilisateur n'a pas le rôle superAdmin. Attribution du rôle...\n";
        $existingUser->assignRole('superAdmin');
        echo "✅ Rôle 'superAdmin' attribué à l'utilisateur {$existingUser->name}.\n";
    } else {
        echo "Cet utilisateur a déjà le rôle superAdmin.\n";
    }
} else {
    // Créer un nouvel utilisateur superAdmin
    $newUser = User::create([
        'name' => 'Super Admin',
        'username' => $username,
        'email' => $email,
        'password' => Hash::make($password),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    // Attribuer le rôle superAdmin
    $newUser->assignRole('superAdmin');

    echo "\n✅ Nouvel utilisateur superAdmin créé avec succès:\n";
    echo "Nom: Super Admin\n";
    echo "Nom d'utilisateur: $username\n";
    echo "Email: $email\n";
    echo "Mot de passe: $password\n";
}

echo "\n=== IDENTIFIANTS DE CONNEXION SUPERADMIN ===\n";
if ($existingUser && $existingUser->hasRole('superAdmin')) {
    echo "Nom d'utilisateur: {$existingUser->username}\n";
    echo "Email: {$existingUser->email}\n";
    echo "Mot de passe: Utilisez votre mot de passe actuel ou réinitialisez-le via la page de connexion.\n";
} else {
    echo "Nom d'utilisateur: $username\n";
    echo "Email: $email\n";
    echo "Mot de passe: $password\n";
}

echo "\nUtilisez ces identifiants pour vous connecter à l'application.\n"; 