<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Définition des identifiants du nouveau superAdmin
$name = 'Super Administrator';
$username = 'superadmin';
$email = 'superadmin@esbtp.com';
$password = 'SuperAdmin@2024';

// Vérifier si le rôle superAdmin existe
$superAdminRole = Role::where('name', 'superAdmin')->first();
if (!$superAdminRole) {
    echo "⚠️ Le rôle 'superAdmin' n'existe pas. Création du rôle...\n";
    $superAdminRole = Role::create(['name' => 'superAdmin']);
    echo "✅ Rôle 'superAdmin' créé avec succès.\n";
}

// Vérifier si l'utilisateur existe déjà
$userExists = User::where('username', $username)->orWhere('email', $email)->first();

if ($userExists) {
    echo "⚠️ Un utilisateur avec le nom d'utilisateur ou l'email spécifié existe déjà.\n";
    echo "Mise à jour du mot de passe et attribution du rôle superAdmin...\n";
    
    // Mise à jour des informations
    $userExists->name = $name;
    $userExists->password = Hash::make($password);
    $userExists->is_active = true;
    $userExists->save();
    
    // Attribution du rôle superAdmin s'il ne l'a pas déjà
    if (!$userExists->hasRole('superAdmin')) {
        $userExists->assignRole('superAdmin');
    }
    
    echo "✅ Utilisateur mis à jour et rôle attribué.\n";
    
    $user = $userExists;
} else {
    // Création d'un nouvel utilisateur
    $user = User::create([
        'name' => $name,
        'username' => $username,
        'email' => $email,
        'password' => Hash::make($password),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    
    // Attribution du rôle superAdmin
    $user->assignRole('superAdmin');
    
    echo "✅ Nouvel utilisateur superAdmin créé avec succès.\n";
}

echo "\n=== IDENTIFIANTS DE CONNEXION SUPERADMIN ===\n";
echo "Nom: $name\n";
echo "Nom d'utilisateur: $username\n";
echo "Email: $email\n";
echo "Mot de passe: $password\n";
echo "\nCes identifiants vous permettront de vous connecter en tant que super administrateur.\n"; 