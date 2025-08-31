<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== MISE À JOUR DU RÔLE DE L'UTILISATEUR ENSEIGNANT ===\n\n";

// Trouver l'utilisateur avec l'email enseignant@test.com
$user = User::where('email', 'enseignant@test.com')->first();

if (!$user) {
    echo "❌ Utilisateur avec l'email 'enseignant@test.com' non trouvé.\n";
    exit(1);
}

echo "✅ Utilisateur trouvé: {$user->name} (ID: {$user->id})\n";
echo "Rôle actuel: {$user->role}\n";

// Mettre à jour le rôle
$oldRole = $user->role;
$user->role = 'teacher';
$user->save();

echo "✅ Rôle mis à jour de '{$oldRole}' à '{$user->role}'.\n";

// Vérifier que l'utilisateur a le bon rôle
$updatedUser = User::find($user->id);
echo "Vérification: Le rôle de l'utilisateur est maintenant '{$updatedUser->role}'.\n";

// Vérifier si l'utilisateur a le rôle d'enseignant
if ($updatedUser->role === 'teacher') {
    echo "✅ L'utilisateur a bien le rôle 'teacher'.\n";
} else {
    echo "❌ L'utilisateur n'a PAS le rôle 'teacher'. Quelque chose a échoué.\n";
}

echo "\n=== MISE À JOUR TERMINÉE ===\n"; 