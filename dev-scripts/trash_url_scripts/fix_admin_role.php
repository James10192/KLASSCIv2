<?php
/**
 * Script pour corriger le rôle de l'administrateur
 * 
 * Ce script modifie le rôle d'un utilisateur existant pour le définir comme administrateur
 */

// Charger l'environnement Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=================================================================\n";
echo "      CORRECTION DU RÔLE ADMINISTRATEUR\n";
echo "=================================================================\n";

// Trouver l'utilisateur à modifier (celui avec l'email admin@example.com)
$user = User::where('email', 'admin@example.com')->first();

if (!$user) {
    echo "❌ Aucun utilisateur avec l'email 'admin@example.com' n'a été trouvé.\n";
    exit;
}

// Afficher les informations actuelles
echo "Informations actuelles de l'utilisateur:\n";
echo "ID: " . $user->id . "\n";
echo "Nom: " . $user->name . "\n";
echo "Email: " . $user->email . "\n";
echo "Rôle actuel: " . $user->role . "\n\n";

// Modifier le rôle
try {
    $user->role = 'admin';
    $user->save();

    echo "✅ Rôle modifié avec succès!\n";
    echo "Nouveau rôle: " . $user->role . "\n";
    echo "\nVous pouvez maintenant vous connecter à l'application avec les identifiants:\n";
    echo "Email: admin@example.com\n";
    echo "Mot de passe: password\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors de la modification du rôle: " . $e->getMessage() . "\n";
}

echo "=================================================================\n"; 