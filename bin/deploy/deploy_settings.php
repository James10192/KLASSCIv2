<?php

/**
 * Script de déploiement des paramètres ESBTP
 *
 * Ce script s'assure que tous les paramètres requis sont présents
 * Usage: php deploy_settings.php
 */

echo "=== DÉPLOIEMENT DES PARAMÈTRES ESBTP ===\n";

// Définir la racine du projet (2 niveaux au-dessus de bin/deploy/)
define('PROJECT_ROOT', dirname(__DIR__, 2));

// Vérifier que PROJECT_ROOT est correct
if (!file_exists(PROJECT_ROOT . '/artisan')) {
    die("❌ ERREUR: PROJECT_ROOT incorrecte. Artisan non trouvé.\n");
}

// Inclure l'autoloader Laravel
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Bootstrapper l'application Laravel
$app = require_once PROJECT_ROOT . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "🔍 Vérification des paramètres essentiels...\n";

    // Liste des paramètres critiques qui doivent exister
    $criticalSettings = [
        [
            'key' => 'school_logo',
            'value' => '',
            'type' => 'file',
            'group' => 'establishment',
            'category' => 'establishment',
            'description' => 'Logo de l\'établissement',
            'is_required' => false,
            'default_value' => '',
            'validation_rules' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'sort_order' => 10
        ],
        [
            'key' => 'school_name',
            'value' => 'ESBTP-yAKRO',
            'type' => 'string',
            'group' => 'establishment',
            'category' => 'establishment',
            'description' => 'Nom de l\'établissement',
            'is_required' => true,
            'default_value' => 'ESBTP-yAKRO',
            'validation_rules' => ['required', 'string', 'max:255'],
            'sort_order' => 1
        ],
        [
            'key' => 'bulletin_show_logo',
            'value' => '1',
            'type' => 'string',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'description' => 'Afficher le logo sur les bulletins',
            'is_required' => false,
            'default_value' => '1',
            'validation_rules' => ['string'],
            'sort_order' => 2
        ]
    ];

    // Vérifier si un utilisateur existe, sinon utiliser NULL pour created_by/updated_by
    $firstUser = DB::table('users')->first();
    $userId = $firstUser ? $firstUser->id : null;

    if (!$userId) {
        echo "⚠️  ATTENTION: Aucun utilisateur trouvé dans la base de données\n";
        echo "💡 Les paramètres seront créés avec created_by/updated_by = NULL\n";
        echo "💡 Exécutez d'abord: php artisan db:seed --class=ServiceTechniqueSeeder\n\n";
    } else {
        echo "✅ Utilisateur trouvé (ID: {$userId})\n\n";
    }

    $created = 0;
    $existing = 0;

    foreach ($criticalSettings as $settingData) {
        $setting = App\Models\Setting::where('key', $settingData['key'])->first();

        if ($setting) {
            echo "✅ {$settingData['key']} existe déjà\n";
            $existing++;
        } else {
            echo "➕ Création de {$settingData['key']}...\n";

            App\Models\Setting::create(array_merge($settingData, [
                'is_active' => true,
                'requires_restart' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
                'validation_rules' => json_encode($settingData['validation_rules'])
            ]));

            echo "✅ {$settingData['key']} créé\n";
            $created++;
        }
    }

    echo "\n📊 STATISTIQUES\n";
    echo "• Paramètres existants: {$existing}\n";
    echo "• Paramètres créés: {$created}\n";
    echo "• Total vérifié: " . count($criticalSettings) . "\n";

    if ($created > 0) {
        // Vider le cache des paramètres
        if (method_exists(App\Models\Setting::class, 'clearCache')) {
            App\Models\Setting::clearCache();
            echo "🗑️  Cache des paramètres vidé\n";
        }
    }

    echo "\n🏗️  VÉRIFICATION DU STOCKAGE\n";

    // Vérifier la structure de stockage
    $storageChecks = [
        'storage/app/public' => 'Dossier de stockage principal',
        'storage/app/public/logos' => 'Dossier des logos',
        'storage/app/public/photos' => 'Dossier des photos',
        'storage/app/public/documents' => 'Dossier des documents',
        'public/storage' => 'Lien symbolique'
    ];

    $storageOk = true;
    foreach ($storageChecks as $path => $description) {
        $fullPath = base_path($path);

        if (file_exists($fullPath)) {
            echo "✅ {$description}: {$path}\n";
        } else {
            echo "❌ {$description}: {$path}\n";
            $storageOk = false;
        }
    }

    if (!$storageOk) {
        echo "\n⚠️  PROBLÈME DE STOCKAGE DÉTECTÉ\n";
        echo "💡 Exécuter: php init_storage.php\n";
    }

    echo "\n🌐 URLS DE TEST\n";
    echo "• Paramètres: http://localhost:8000/esbtp/settings\n";
    echo "• Logo par défaut: http://localhost:8000/storage/logos/esbtp-logo.svg\n";

    echo "\n✅ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR LORS DU DÉPLOIEMENT: " . $e->getMessage() . "\n";

    echo "\n🆘 COMMANDES DE RÉCUPÉRATION:\n";
    echo "1. Réinitialiser les paramètres:\n";
    echo "   php artisan db:seed --class=SettingsSeeder\n\n";

    echo "2. Recréer le lien de stockage:\n";
    echo "   php artisan storage:link\n\n";

    echo "3. Vérifier les permissions:\n";
    echo "   chmod -R 755 storage/\n";
    echo "   chmod -R 755 public/storage/\n";

    exit(1);
}

echo "\n=== DÉPLOIEMENT TERMINÉ ===\n";