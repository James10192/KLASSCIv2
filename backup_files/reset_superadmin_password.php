<?php
/**
 * Script pour réinitialiser le mot de passe du super administrateur
 * 
 * Ce script est conçu pour être exécuté via la commande:
 * php artisan tinker --execute="require('reset_superadmin_password.php');"
 */

try {
    // Recherche de l'utilisateur super administrateur
    $superAdmin = \App\Models\User::where('role', 'superAdmin')->first();

    if (!$superAdmin) {
        echo "❌ Erreur: Aucun utilisateur super administrateur trouvé dans le système.\n";
        return;
    }

    // Définition du nouveau mot de passe
    $newPassword = 'esbtp@admin2024';
    
    // Mise à jour du mot de passe
    $superAdmin->password = \Illuminate\Support\Facades\Hash::make($newPassword);
    $superAdmin->save();

    echo "✅ Le mot de passe du super administrateur (" . $superAdmin->email . ") a été réinitialisé avec succès.\n";
    echo "📝 Nouveau mot de passe: " . $newPassword . "\n";
    echo "🔐 Veillez à changer ce mot de passe après votre première connexion.\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage() . "\n";
} 