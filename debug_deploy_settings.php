<?php

/**
 * Script de debug pour deploy_settings.php
 *
 * Ce script teste chaque étape individuellement pour identifier le problème
 */

echo "=== DEBUG DEPLOY_SETTINGS ===\n\n";

// Test 1: Vérifier autoloader
echo "1️⃣  Test Autoloader...\n";
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ ERREUR: vendor/autoload.php introuvable\n";
    echo "💡 Exécutez: composer install\n";
    exit(1);
}
require_once __DIR__ . '/vendor/autoload.php';
echo "✅ Autoloader chargé\n\n";

// Test 2: Vérifier bootstrap Laravel
echo "2️⃣  Test Bootstrap Laravel...\n";
if (!file_exists(__DIR__ . '/bootstrap/app.php')) {
    echo "❌ ERREUR: bootstrap/app.php introuvable\n";
    exit(1);
}
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "✅ Application Laravel créée\n";
} catch (Exception $e) {
    echo "❌ ERREUR lors du bootstrap: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Vérifier kernel
echo "3️⃣  Test Kernel...\n";
try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    echo "✅ Kernel créé\n";
} catch (Exception $e) {
    echo "❌ ERREUR lors de la création du kernel: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Bootstrap kernel
echo "4️⃣  Test Bootstrap Kernel...\n";
try {
    $kernel->bootstrap();
    echo "✅ Kernel bootstrappé\n\n";
} catch (Exception $e) {
    echo "❌ ERREUR lors du bootstrap du kernel: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 5: Vérifier connexion DB
echo "5️⃣  Test Connexion Base de Données...\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "✅ Connexion DB établie\n";

    $dbName = DB::connection()->getDatabaseName();
    echo "📊 Base de données: {$dbName}\n\n";
} catch (Exception $e) {
    echo "❌ ERREUR de connexion DB: " . $e->getMessage() . "\n";
    echo "\n💡 Vérifications à faire:\n";
    echo "1. Vérifier .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)\n";
    echo "2. Vérifier que la base de données existe\n";
    echo "3. Vérifier les permissions MySQL\n";
    exit(1);
}

// Test 6: Vérifier table settings
echo "6️⃣  Test Table 'settings'...\n";
try {
    $tableExists = DB::select("SHOW TABLES LIKE 'settings'");

    if (empty($tableExists)) {
        echo "❌ ERREUR: Table 'settings' n'existe pas\n";
        echo "\n💡 Solutions:\n";
        echo "1. Exécuter les migrations: php artisan migrate\n";
        echo "2. Vérifier que la migration de settings existe\n";
        exit(1);
    }

    echo "✅ Table 'settings' existe\n";

    $count = DB::table('settings')->count();
    echo "📊 Nombre de paramètres existants: {$count}\n\n";

} catch (Exception $e) {
    echo "❌ ERREUR lors de la vérification de la table: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Vérifier modèle Setting
echo "7️⃣  Test Modèle Setting...\n";
try {
    if (!class_exists(App\Models\Setting::class)) {
        echo "❌ ERREUR: Classe App\Models\Setting introuvable\n";
        echo "💡 Vérifier que le fichier app/Models/Setting.php existe\n";
        exit(1);
    }

    echo "✅ Classe Setting existe\n";

    // Tester une requête simple
    $testSetting = App\Models\Setting::first();

    if ($testSetting) {
        echo "✅ Requête test réussie (paramètre: {$testSetting->key})\n\n";
    } else {
        echo "⚠️  Aucun paramètre trouvé (normal si nouveau tenant)\n\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR avec le modèle Setting: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 8: Tester création d'un paramètre
echo "8️⃣  Test Création Paramètre (test)...\n";
try {
    // Supprimer paramètre de test s'il existe
    App\Models\Setting::where('key', 'test_debug')->delete();

    $testSetting = App\Models\Setting::create([
        'key' => 'test_debug',
        'value' => 'test_value',
        'type' => 'string',
        'group' => 'test',
        'category' => 'test',
        'description' => 'Paramètre de test debug',
        'is_required' => false,
        'is_active' => true,
        'requires_restart' => false,
        'default_value' => 'test_value',
        'validation_rules' => json_encode(['nullable', 'string']),
        'sort_order' => 999,
        'created_by' => 1,
        'updated_by' => 1
    ]);

    echo "✅ Création test réussie (ID: {$testSetting->id})\n";

    // Nettoyer
    $testSetting->delete();
    echo "✅ Suppression test réussie\n\n";

} catch (Exception $e) {
    echo "❌ ERREUR lors de la création test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    echo "\n💡 Problèmes possibles:\n";
    echo "1. Colonnes manquantes dans la table 'settings'\n";
    echo "2. Contraintes de clé étrangère (created_by, updated_by)\n";
    echo "3. Permissions insuffisantes sur la base de données\n";

    exit(1);
}

echo "🎉 TOUS LES TESTS SONT PASSÉS !\n";
echo "\n💡 deploy_settings.php devrait fonctionner maintenant.\n";
echo "💡 Si le problème persiste, exécutez ce script sur le serveur distant:\n";
echo "   php debug_deploy_settings.php\n";
