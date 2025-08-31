<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mettre à jour le rôle de l'utilisateur
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== MISE À JOUR DU RÔLE DE RUBEN ===\n\n";

$user = User::where('email', 'ruben@gmail.com')->first();

if (!$user) {
    echo "Utilisateur ruben@gmail.com non trouvé.\n";
    exit(1);
}

echo "Utilisateur trouvé: {$user->name}\n";
echo "Rôle actuel: {$user->role}\n\n";

// Mettre à jour le rôle
$user->role = 'superAdmin';
$user->save();

echo "Rôle mis à jour à: {$user->role}\n";

// Réinitialiser le mot de passe également
$newPassword = 'admin123';
$user->password = Hash::make($newPassword);
$user->save();

echo "Mot de passe réinitialisé à: {$newPassword}\n\n";

echo "=== IDENTIFIANTS DE CONNEXION ===\n";
echo "Email: {$user->email}\n";
echo "Mot de passe: {$newPassword}\n";

// Vérifier si le package Spatie Permissions est utilisé
try {
    if (class_exists('\\Spatie\\Permission\\Models\\Role')) {
        $user->syncRoles(['superAdmin']);
        echo "\nRôle Spatie 'superAdmin' synchronisé.\n";
    }
} catch (\Exception $e) {
    echo "\nNote: Erreur lors de la synchronisation des rôles Spatie: " . $e->getMessage() . "\n";
} 