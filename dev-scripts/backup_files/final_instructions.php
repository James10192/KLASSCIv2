<?php

// Initialiser l'application Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importer les classes nécessaires
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

echo "=== INSTRUCTIONS FINALES POUR RÉSOUDRE LES PROBLÈMES DE CONNEXION ===\n\n";

// 1. Afficher les comptes super administrateur disponibles
echo "Les comptes super administrateur suivants sont disponibles:\n\n";

try {
    $superAdmins = User::where('role', 'superAdmin')->get();
    
    if ($superAdmins->isEmpty()) {
        echo "❌ Aucun compte super administrateur trouvé avec role='superAdmin'.\n";
    } else {
        foreach ($superAdmins as $admin) {
            echo "- {$admin->name} (Email: {$admin->email})\n";
            echo "  Rôle: {$admin->role}\n";
            echo "  Mot de passe: admin123 (réinitialisé)\n\n";
            
            // Réinitialiser le mot de passe
            $admin->password = Hash::make('admin123');
            $admin->save();
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors de la récupération des super administrateurs: " . $e->getMessage() . "\n";
}

// 2. Nettoyer le cache
echo "=== ACTIONS DE NETTOYAGE EFFECTUÉES ===\n";

try {
    Artisan::call('config:clear');
    echo "✅ Cache de configuration effacé\n";
    
    Artisan::call('route:clear');
    echo "✅ Cache des routes effacé\n";
    
    Artisan::call('cache:clear');
    echo "✅ Cache de l'application effacé\n";
    
    Artisan::call('view:clear');
    echo "✅ Cache des vues effacé\n";
} catch (\Exception $e) {
    echo "❌ Erreur lors du nettoyage du cache: " . $e->getMessage() . "\n";
}

// 3. Vérifier et mettre à jour APP_INSTALLED dans .env
$envFile = base_path('.env');
$envContent = file_get_contents($envFile);

if (strpos($envContent, 'APP_INSTALLED=true') === false) {
    // Ajouter ou mettre à jour APP_INSTALLED
    if (strpos($envContent, 'APP_INSTALLED=') !== false) {
        $envContent = preg_replace('/APP_INSTALLED=.*/', 'APP_INSTALLED=true', $envContent);
    } else {
        $envContent .= "\nAPP_INSTALLED=true\n";
    }
    
    file_put_contents($envFile, $envContent);
    echo "✅ APP_INSTALLED=true ajouté au fichier .env\n";
}

echo "\n=== RÉSOLUTION DES PROBLÈMES DE CONNEXION ===\n";
echo "Si vous rencontrez encore des problèmes, suivez ces étapes:\n\n";
echo "1. Redémarrez votre serveur web (Apache/XAMPP):\n";
echo "   sudo systemctl restart apache2   # Pour Ubuntu/Debian\n";
echo "   sudo systemctl restart httpd     # Pour CentOS/RHEL\n";
echo "   ou redémarrez XAMPP via le panneau de contrôle\n\n";

echo "2. Effacez les cookies et le cache de votre navigateur:\n";
echo "   - Chrome: Ctrl+Shift+Delete\n";
echo "   - Firefox: Ctrl+Shift+Delete\n";
echo "   - Safari: Command+Shift+E\n\n";

echo "3. Essayez de vous connecter avec les identifiants suivants:\n";
echo "   Email: ruben@gmail.com\n";
echo "   Mot de passe: admin123\n\n";

echo "4. Si le problème persiste, vérifiez les journaux d'erreur:\n";
echo "   - Journaux Laravel: /opt/lampp/htdocs/ESBTP-yAKROv2/storage/logs/laravel.log\n";
echo "   - Journaux Apache: /opt/lampp/logs/error_log\n\n";

echo "5. Assurez-vous que les permissions des fichiers sont correctes:\n";
echo "   sudo chown -R www-data:www-data /opt/lampp/htdocs/ESBTP-yAKROv2/storage\n";
echo "   sudo chmod -R 775 /opt/lampp/htdocs/ESBTP-yAKROv2/storage\n\n";

echo "=== INSTRUCTIONS TERMINÉES ===\n";
echo "Bonne chance avec votre connexion! Si vous avez encore des problèmes, n'hésitez pas à demander de l'aide.\n"; 