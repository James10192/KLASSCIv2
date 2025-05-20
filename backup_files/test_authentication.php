<?php

// Script pour tester l'authentification et l'accès au tableau de bord
// À exécuter avec: php artisan tinker --execute="require('test_authentication.php');"

echo "=== TEST D'AUTHENTIFICATION ===\n\n";

// Rechercher l'utilisateur super admin
$email = 'ruben@gmail.com';
$password = 'password'; // Remplacez par le mot de passe réel si nécessaire

try {
    $user = \App\Models\User::where('email', $email)->first();
    
    if (!$user) {
        echo "❌ Utilisateur avec l'email '{$email}' non trouvé.\n";
        exit;
    }
    
    echo "✅ Utilisateur trouvé: {$user->name} (ID: {$user->id})\n";
    echo "Rôles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n\n";
    
    // Tester l'authentification
    $credentials = [
        'email' => $email,
        'password' => $password
    ];
    
    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
        echo "✅ Authentification réussie avec les identifiants fournis.\n";
        
        // Vérifier les permissions de l'utilisateur
        $permissionsToCheck = [
            'view filieres',
            'create filieres',
            'edit filieres',
            'delete filieres',
            'view classes',
            'create classes',
            'edit classes',
            'delete classes'
        ];
        
        echo "\n=== VÉRIFICATION DES PERMISSIONS ===\n";
        foreach ($permissionsToCheck as $permission) {
            $hasPermission = $user->hasPermissionTo($permission);
            echo ($hasPermission ? "✅" : "❌") . " Permission '{$permission}': " . 
                 ($hasPermission ? "Accordée" : "Non accordée") . "\n";
        }
        
        // Se déconnecter après le test
        \Illuminate\Support\Facades\Auth::logout();
        echo "\n✅ Déconnexion réussie.\n";
    } else {
        echo "❌ Échec de l'authentification. Vérifiez le mot de passe.\n";
        echo "Note: Le mot de passe utilisé pour ce test est '{$password}'.\n";
        echo "Si ce n'est pas le bon mot de passe, modifiez-le dans le script test_authentication.php.\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors du test d'authentification: {$e->getMessage()}\n";
}

echo "\n=== INSTRUCTIONS DE CONNEXION ===\n";
echo "Pour se connecter à l'application via le navigateur:\n";
echo "1. Accédez à http://localhost:8000/login\n";
echo "2. Entrez les identifiants du super admin:\n";
echo "   - Email: {$email}\n";
echo "   - Mot de passe: [Le mot de passe défini lors de l'installation]\n";
echo "3. Après la connexion, vous devriez être redirigé vers le tableau de bord\n";
echo "4. Vérifiez que vous pouvez accéder à:\n";
echo "   - Gestion des enseignants: http://localhost:8000/esbtp/teachers\n";
echo "   - Comptabilité: http://localhost:8000/comptabilite\n";

echo "\n=== TEST TERMINÉ ===\n"; 