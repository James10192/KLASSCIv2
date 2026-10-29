<?php

/**
 * Script pour créer un super administrateur
 * 
 * Ce script crée un nouvel utilisateur avec le rôle de super administrateur
 * et les informations d'identification spécifiées.
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Démarrage du script de création de super administrateur...\n";

try {
    // Charger l'environnement Laravel
    require __DIR__ . '/../vendor/autoload.php';
    echo "Autoloader chargé.\n";
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "Application Laravel chargée.\n";
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "Kernel Laravel initialisé.\n";

    use App\Models\User;
    use App\Models\SuperAdmin;
    use Illuminate\Support\Facades\Hash;
    use Spatie\Permission\Models\Role;

    // Informations du super administrateur à créer
    $email = "djedjelipatrick@gmail.com";
    $password = "Marcel@123";
    $firstName = "Patrick";
    $lastName = "Djedjeli";

    echo "Vérification de l'existence de l'utilisateur avec l'email: $email\n";
    
    // Vérifier si l'utilisateur existe déjà
    $existingUser = User::where('email', $email)->first();

    if ($existingUser) {
        echo "Un utilisateur avec l'email '$email' existe déjà.\n";
        
        // Vérifier si l'utilisateur est déjà un super administrateur
        if ($existingUser->isSuperAdmin()) {
            echo "Cet utilisateur est déjà un super administrateur.\n";
        } else {
            // Mettre à jour le type d'utilisateur
            $existingUser->user_type = 'superadmin';
            $existingUser->save();
            echo "Type d'utilisateur mis à jour en 'superadmin'.\n";
            
            // Créer l'entrée SuperAdmin si elle n'existe pas
            if (!SuperAdmin::where('user_id', $existingUser->id)->exists()) {
                SuperAdmin::create([
                    'user_id' => $existingUser->id,
                    'access_level' => 'full',
                    'dashboard_preferences' => json_encode(['theme' => 'light']),
                    'last_system_check' => now(),
                    'created_by' => $existingUser->id,
                    'updated_by' => $existingUser->id,
                ]);
                echo "Entrée SuperAdmin créée.\n";
            }
            
            // Assigner le rôle super-admin si nécessaire
            if (!$existingUser->hasRole('super-admin')) {
                $superAdminRole = Role::where('name', 'super-admin')->first();
                if ($superAdminRole) {
                    $existingUser->assignRole($superAdminRole);
                    echo "Rôle 'super-admin' assigné.\n";
                } else {
                    echo "Le rôle 'super-admin' n'existe pas. Veuillez exécuter le seeder de rôles d'abord.\n";
                }
            }
            
            echo "L'utilisateur a été mis à jour en super administrateur avec succès.\n";
        }
    } else {
        echo "Création d'un nouvel utilisateur...\n";
        
        // Créer un nouvel utilisateur
        $user = User::create([
            'name' => "$firstName $lastName",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make($password),
            'user_type' => 'superadmin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        echo "Utilisateur créé avec l'ID: " . $user->id . "\n";
        
        // Créer l'entrée SuperAdmin
        SuperAdmin::create([
            'user_id' => $user->id,
            'access_level' => 'full',
            'dashboard_preferences' => json_encode(['theme' => 'light']),
            'last_system_check' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        echo "Entrée SuperAdmin créée.\n";
        
        // Assigner le rôle super-admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
            echo "Rôle 'super-admin' assigné.\n";
        } else {
            echo "Le rôle 'super-admin' n'existe pas. Veuillez exécuter le seeder de rôles d'abord.\n";
        }
        
        echo "Super administrateur créé avec succès!\n";
        echo "Email: $email\n";
        echo "Mot de passe: $password\n";
    }

    // Afficher un message de confirmation
    echo "Opération terminée.\n";
    
} catch (Exception $e) {
    echo "Une erreur s'est produite: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 