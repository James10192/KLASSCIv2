<?php
/**
 * Script pour vérifier l'état de l'installation
 * 
 * Ce script vérifie si l'application est installée et si un administrateur existe
 */

// Charger l'environnement Laravel
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=================================================================\n";
echo "      VÉRIFICATION DE L'INSTALLATION SMART SCHOOL\n";
echo "=================================================================\n";

// Vérifier si le fichier d'installation existe
$installFile = storage_path('app/installed');
$fileExists = file_exists($installFile);

if ($fileExists) {
    $installDate = file_get_contents($installFile);
    echo "✅ Fichier d'installation trouvé (date: $installDate)\n";
} else {
    echo "❌ Fichier d'installation non trouvé\n";
}

// Vérifier si la table users existe
if (Schema::hasTable('users')) {
    echo "✅ Table 'users' trouvée dans la base de données\n";
    
    // Vérifier si un administrateur existe
    $adminExists = User::where('role', 'admin')->exists();
    
    if ($adminExists) {
        $admin = User::where('role', 'admin')->first();
        echo "✅ Utilisateur administrateur trouvé (ID: {$admin->id}, Email: {$admin->email})\n";
    } else {
        echo "❌ Aucun utilisateur administrateur trouvé\n";
    }
    
    // Compter les utilisateurs par rôle
    $usersByRole = User::select('role', DB::raw('count(*) as total'))
                       ->groupBy('role')
                       ->get();
    
    echo "\nUtilisateurs par rôle:\n";
    if ($usersByRole->isEmpty()) {
        echo "Aucun utilisateur trouvé\n";
    } else {
        foreach ($usersByRole as $role) {
            echo "- {$role->role}: {$role->total}\n";
        }
    }
} else {
    echo "❌ Table 'users' non trouvée dans la base de données\n";
}

echo "\n=================================================================\n";
echo "Pour toute assistance, consultez la documentation ou contactez le support.\n";
echo "=================================================================\n"; 