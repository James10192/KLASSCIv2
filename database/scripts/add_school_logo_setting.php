<?php

/**
 * Script pour ajouter le paramètre school_logo manquant
 *
 * Ce script ajoute le paramètre de logo de l'école s'il n'existe pas
 * Usage: php add_school_logo_setting.php
 */

echo "=== AJOUT DU PARAMÈTRE SCHOOL_LOGO ===\n";

// Inclure l'autoloader Laravel
require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrapper l'application Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Vérifier si le paramètre existe déjà
    $setting = App\Models\Setting::where('key', 'school_logo')->first();

    if ($setting) {
        echo "✅ Le paramètre 'school_logo' existe déjà (ID: {$setting->id})\n";
        echo "📋 Valeur actuelle: " . ($setting->value ?: '(vide)') . "\n";
    } else {
        echo "➕ Création du paramètre 'school_logo'...\n";

        $setting = App\Models\Setting::create([
            'key' => 'school_logo',
            'value' => '',
            'type' => 'file',
            'group' => 'establishment',
            'category' => 'establishment',
            'description' => 'Logo de l\'établissement',
            'is_required' => false,
            'default_value' => '',
            'validation_rules' => json_encode(['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048']),
            'is_active' => true,
            'requires_restart' => false,
            'sort_order' => 10,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        echo "✅ Paramètre 'school_logo' créé avec succès (ID: {$setting->id})\n";

        // Vider le cache des paramètres
        if (method_exists(App\Models\Setting::class, 'clearCache')) {
            App\Models\Setting::clearCache();
            echo "🗑️  Cache des paramètres vidé\n";
        }
    }

    // Vérifier les permissions du dossier de stockage
    echo "\n🔍 VÉRIFICATION DU STOCKAGE\n";

    $logoPath = storage_path('app/public/logos');
    if (is_dir($logoPath)) {
        echo "✅ Dossier logos existe: {$logoPath}\n";

        if (is_writable($logoPath)) {
            echo "✅ Dossier logos accessible en écriture\n";
        } else {
            echo "⚠️  Dossier logos non accessible en écriture\n";
            echo "💡 Exécuter: chmod 755 {$logoPath}\n";
        }
    } else {
        echo "❌ Dossier logos n'existe pas: {$logoPath}\n";
        echo "💡 Exécuter le script init_storage.php\n";
    }

    // Vérifier le lien symbolique
    $publicStoragePath = public_path('storage');
    if (file_exists($publicStoragePath)) {
        echo "✅ Lien symbolique storage existe\n";

        if (file_exists($publicStoragePath . '/logos')) {
            echo "✅ Dossier logos accessible via web\n";
        } else {
            echo "⚠️  Dossier logos non accessible via web\n";
        }
    } else {
        echo "❌ Lien symbolique storage manquant\n";
        echo "💡 Exécuter: php artisan storage:link\n";
    }

    echo "\n📋 RÉSUMÉ\n";
    echo "• Paramètre school_logo: ✅ Configuré\n";
    echo "• Type: file\n";
    echo "• Catégorie: establishment\n";
    echo "• Validation: image|mimes:jpeg,png,jpg,gif|max:2048\n";
    echo "• Stockage: storage/app/public/logos/\n";
    echo "• URL d'accès: /storage/logos/\n";

    echo "\n🎯 PROCHAINES ÉTAPES\n";
    echo "1. Aller sur http://localhost:8000/esbtp/settings\n";
    echo "2. Télécharger une image dans 'Logo de l'établissement'\n";
    echo "3. Cliquer sur 'Sauvegarder les Paramètres'\n";
    echo "4. Vérifier que l'image s'affiche\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📜 Trace: " . $e->getTraceAsString() . "\n";

    echo "\n🆘 SOLUTIONS ALTERNATIVES:\n";
    echo "1. Relancer le seeder complet:\n";
    echo "   php artisan db:seed --class=SettingsSeeder\n\n";

    echo "2. Ajouter manuellement en base:\n";
    echo "   INSERT INTO settings (key, value, type, category, description, is_required, validation_rules, is_active, created_by, updated_by, created_at, updated_at)\n";
    echo "   VALUES ('school_logo', '', 'file', 'establishment', 'Logo de l\\'établissement', 0, '{\"nullable\",\"image\",\"mimes:jpeg,png,jpg,gif\",\"max:2048\"}', 1, 1, 1, NOW(), NOW());\n";
}

echo "\n=== SCRIPT TERMINÉ ===\n";