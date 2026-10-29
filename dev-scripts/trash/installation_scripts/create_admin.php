<?php
/**
 * Script pour créer un utilisateur administrateur
 * 
 * Ce script crée un utilisateur administrateur dans la base de données
 * Utile quand l'application est marquée comme installée mais qu'il n'y a pas d'utilisateurs
 */

// Charger l'environnement Laravel
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=================================================================\n";
echo "      CRÉATION D'UN UTILISATEUR ADMINISTRATEUR\n";
echo "=================================================================\n";

// Vérifier si un administrateur existe déjà
$adminExists = User::where('role', 'admin')->exists();

if ($adminExists) {
    echo "❌ Un administrateur existe déjà dans la base de données.\n";
    echo "Si vous ne pouvez pas vous connecter, vérifiez les identifiants.\n";
    exit;
}

// Créer un nouvel administrateur
try {
    $admin = User::create([
        'name' => 'Administrateur',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    echo "✅ Administrateur créé avec succès!\n";
    echo "Identifiants de connexion:\n";
    echo "Email: admin@example.com\n";
    echo "Mot de passe: password\n";
    echo "\nVous pouvez maintenant vous connecter à l'application.\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors de la création de l'administrateur: " . $e->getMessage() . "\n";
}

echo "=================================================================\n"; 