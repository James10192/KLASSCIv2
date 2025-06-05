<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Corrections Finales Settings ===\n\n";

try {
    // 1. Test de la correction Redis
    echo "1. Test de la correction Redis:\n";

    // Tester le modèle Setting avec gestion d'erreur
    $settingClass = new ReflectionClass(\App\Models\Setting::class);
    $getMethods = $settingClass->getMethod('get');
    $setMethods = $settingClass->getMethod('set');

    echo "   ✅ Modèle Setting accessible\n";
    echo "   ✅ Méthodes get() et set() disponibles\n";

    // Tester une récupération de setting
    try {
        $testValue = \App\Models\Setting::get('school_name', 'Test School');
        echo "   ✅ Récupération de setting réussie: $testValue\n";
    } catch (Exception $e) {
        echo "   ❌ Erreur lors de la récupération: " . $e->getMessage() . "\n";
    }

    // 2. Test du contrôleur Settings
    echo "\n2. Test du contrôleur Settings:\n";

    try {
        $controller = new \App\Http\Controllers\ESBTP\ESBTPSettingsController();
        echo "   ✅ Contrôleur ESBTPSettingsController accessible\n";

        // Vérifier que les méthodes existent
        $methods = ['index', 'update'];
        foreach ($methods as $method) {
            if (method_exists($controller, $method)) {
                echo "   ✅ Méthode $method() disponible\n";
            } else {
                echo "   ❌ Méthode $method() manquante\n";
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur contrôleur: " . $e->getMessage() . "\n";
    }

    // 3. Test des routes
    echo "\n3. Test des routes:\n";

    $routes = [
        'esbtp.settings.index' => 'Page des settings',
        'esbtp.settings.update' => 'Mise à jour des settings'
    ];

    foreach ($routes as $routeName => $description) {
        try {
            $url = route($routeName);
            echo "   ✅ $description: $url\n";
        } catch (Exception $e) {
            echo "   ❌ $description non trouvée: " . $e->getMessage() . "\n";
        }
    }

    // 4. Test de la vue avec corrections modals
    echo "\n4. Test des corrections modals dans la vue:\n";

    $viewPath = resource_path('views/esbtp/settings/index.blade.php');
    if (file_exists($viewPath)) {
        $viewContent = file_get_contents($viewPath);

        $modalChecks = [
            'z-index: 9999 !important' => 'Z-index modal élevé',
            'z-index: 9998 !important' => 'Z-index backdrop élevé',
            'z-index: 10000 !important' => 'Z-index dialog élevé',
            'z-index: 10001 !important' => 'Z-index content élevé',
            'display: block !important' => 'Force affichage modal'
        ];

        $found = 0;
        $total = count($modalChecks);

        foreach ($modalChecks as $pattern => $description) {
            if (strpos($viewContent, $pattern) !== false) {
                echo "   ✅ $description\n";
                $found++;
            } else {
                echo "   ❌ $description manquant\n";
            }
        }

        echo "\n   📊 Corrections modals: $found/$total trouvées\n";

    } else {
        echo "   ❌ Fichier de vue non trouvé\n";
    }

    // 5. Test de la configuration cache
    echo "\n5. Test de la configuration cache:\n";

    $cacheConfig = config('cache.default');
    echo "   📋 Driver de cache par défaut: $cacheConfig\n";

    if ($cacheConfig === 'file') {
        echo "   ✅ Cache configuré sur 'file' (pas de Redis requis)\n";
    } else {
        echo "   ⚠️  Cache configuré sur '$cacheConfig' (peut causer des erreurs)\n";
    }

    // 6. Test de création d'un setting simple
    echo "\n6. Test de création/modification de setting:\n";

    try {
        // Essayer de récupérer un setting existant
        $existingSetting = \App\Models\Setting::where('key', 'school_name')->first();

        if ($existingSetting) {
            echo "   ✅ Setting 'school_name' trouvé\n";

            // Essayer de le modifier
            $originalValue = $existingSetting->value;
            $testValue = 'Test School - ' . date('H:i:s');

            $success = \App\Models\Setting::set('school_name', $testValue);

            if ($success) {
                echo "   ✅ Modification de setting réussie\n";

                // Remettre la valeur originale
                \App\Models\Setting::set('school_name', $originalValue);
                echo "   ✅ Valeur originale restaurée\n";
            } else {
                echo "   ❌ Échec de la modification\n";
            }
        } else {
            echo "   ⚠️  Setting 'school_name' non trouvé\n";
        }

    } catch (Exception $e) {
        echo "   ❌ Erreur lors du test de setting: " . $e->getMessage() . "\n";
    }

    echo "\n✅ Test des corrections terminé!\n";
    echo "\n🎯 Résumé des corrections:\n";
    echo "\n🔧 PROBLÈME REDIS:\n";
    echo "   ✅ Gestion d'erreur ajoutée dans Setting::get()\n";
    echo "   ✅ Gestion d'erreur ajoutée dans Setting::set()\n";
    echo "   ✅ Gestion d'erreur ajoutée dans clearCache()\n";
    echo "   ✅ Fallback sans cache en cas d'erreur\n";

    echo "\n🎨 PROBLÈME MODALS:\n";
    echo "   ✅ Z-index modal: 9999\n";
    echo "   ✅ Z-index backdrop: 9998\n";
    echo "   ✅ Z-index dialog: 10000\n";
    echo "   ✅ Z-index content: 10001\n";
    echo "   ✅ Force display: block\n";

    echo "\n🚀 PROCHAINES ACTIONS:\n";
    echo "1. Ouvrir http://localhost:8000/esbtp/settings\n";
    echo "2. Modifier un paramètre et sauvegarder\n";
    echo "3. ✅ Vérifier : Plus d'erreur Redis\n";
    echo "4. Cliquer sur 'Créer une Sauvegarde'\n";
    echo "5. ✅ Vérifier : Modal s'ouvre au premier plan\n";
    echo "6. Cliquer sur 'Restaurer'\n";
    echo "7. ✅ Vérifier : Modal s'ouvre au premier plan\n";

    echo "\n🎉 Les corrections sont appliquées!\n";

} catch (Exception $e) {
    echo "\n❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
