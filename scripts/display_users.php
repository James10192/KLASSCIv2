<?php

// Charger l'application Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Importer les modèles nécessaires
use App\Models\User;
use Spatie\Permission\Models\Role;

// Récupérer tous les utilisateurs avec leurs rôles
$users = User::all();

// Afficher les informations
echo "=== UTILISATEURS ET LEURS RÔLES ===\n\n";

foreach ($users as $user) {
    echo "Nom: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Rôles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    echo "Mot de passe: password123 (pour tous les utilisateurs)\n";
    echo "-----------------------------------\n";
}

// Afficher un résumé des rôles
echo "\n=== RÉSUMÉ DES RÔLES ===\n\n";

$roles = Role::all();
foreach ($roles as $role) {
    $count = User::role($role->name)->count();
    echo "Rôle: " . $role->name . " - " . $count . " utilisateur(s)\n";
}

echo "\n=== FIN DU RAPPORT ===\n"; 