<?php

// Script pour tester l'authentification avec le nouveau mot de passe
// À exécuter avec: php artisan tinker --execute="require('test_login.php');"

echo "=== TEST D'AUTHENTIFICATION AVEC LE NOUVEAU MOT DE PASSE ===\n\n";

// Informations de connexion du super admin
$email = 'ruben@gmail.com';
$password = 'admin123'; // Nouveau mot de passe défini

try {
    // Tentative d'authentification
    if (\Illuminate\Support\Facades\Auth::attempt(['email' => $email, 'password' => $password])) {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        echo "✅ Authentification réussie !\n";
        echo "Utilisateur: {$user->name} (ID: {$user->id})\n";
        echo "Rôles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n\n";
        
        // Tester l'accès à des routes spécifiques
        echo "=== VÉRIFICATION DES AUTORISATIONS ===\n";
        
        // Tableau des fonctionnalités à vérifier
        $features = [
            'Gestion des enseignants' => $user->can('view teachers') || $user->hasRole('superAdmin'),
            'Gestion de la comptabilité' => $user->can('view finances') || $user->hasRole('superAdmin'),
            'Gestion des filières' => $user->can('view filieres') || $user->hasRole('superAdmin'),
            'Gestion des classes' => $user->can('view classes') || $user->hasRole('superAdmin')
        ];
        
        foreach ($features as $feature => $hasAccess) {
            echo ($hasAccess ? "✅" : "❌") . " Accès à: {$feature}\n";
        }
        
        // Se déconnecter
        \Illuminate\Support\Facades\Auth::logout();
        echo "\n✅ Déconnexion réussie.\n";
    } else {
        echo "❌ Échec de l'authentification. Les identifiants ne sont pas valides.\n";
        echo "Email utilisé: {$email}\n";
        echo "Mot de passe utilisé: {$password}\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors du test d'authentification: {$e->getMessage()}\n";
}

echo "\n=== INSTRUCTIONS POUR ACCÉDER AU TABLEAU DE BORD ===\n";
echo "Pour accéder au tableau de bord super admin:\n";
echo "1. Accédez à http://localhost:8000/login\n";
echo "2. Entrez les identifiants suivants:\n";
echo "   - Email: {$email}\n";
echo "   - Mot de passe: {$password}\n";
echo "3. Après la connexion, vous devriez pouvoir accéder à:\n";
echo "   - Gestion des enseignants: http://localhost:8000/esbtp/teachers\n";
echo "   - Comptabilité: http://localhost:8000/comptabilite\n";

echo "\n=== TEST TERMINÉ ===\n"; 