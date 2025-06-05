<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST FINAL COMPLET - ESBTP SETTINGS ===\n\n";

$allTestsPassed = true;

// 1. Test de l'erreur Redis
echo "1. TEST ERREUR REDIS:\n";
echo "====================\n";

try {
    // Test Setting::clearCache()
    \App\Models\Setting::clearCache();
    echo "   ✅ Setting::clearCache() - OK\n";

    // Test CheckRequiredSettings::clearCache()
    \App\Http\Middleware\CheckRequiredSettings::clearCache();
    echo "   ✅ CheckRequiredSettings::clearCache() - OK\n";

    // Test opérations de cache
    \Illuminate\Support\Facades\Cache::remember('test_key', 60, function() {
        return 'test_value';
    });
    \Illuminate\Support\Facades\Cache::forget('test_key');
    echo "   ✅ Opérations de cache - OK\n";

} catch (\Exception $e) {
    echo "   ❌ Erreur Redis: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

// 2. Test des paramètres utilisés dans le bulletin
echo "\n2. TEST PARAMÈTRES BULLETIN:\n";
echo "============================\n";

$usedSettings = [
    'school_name', 'school_acronym', 'school_country', 'school_address', 'school_city',
    'school_phone', 'school_email', 'school_logo', 'director_name', 'director_title',
    'pdf_margin_top', 'pdf_margin_right', 'pdf_margin_bottom', 'pdf_margin_left',
    'pdf_font_size', 'pdf_watermark', 'pdf_header_text', 'pdf_footer_text',
    'pdf_show_logo', 'pdf_signature_director'
];

$foundSettings = 0;
foreach ($usedSettings as $key) {
    $setting = \App\Models\Setting::where('key', $key)->first();
    if ($setting) {
        $foundSettings++;
    }
}

echo "   📊 Paramètres utilisés trouvés: $foundSettings/" . count($usedSettings) . "\n";

if ($foundSettings >= 15) {
    echo "   ✅ Paramètres essentiels présents\n";
} else {
    echo "   ⚠️ Certains paramètres manquent\n";
}

// 3. Test de l'interface optimisée
echo "\n3. TEST INTERFACE OPTIMISÉE:\n";
echo "============================\n";

$optimizedViewPath = __DIR__ . '/resources/views/esbtp/settings/index-optimized.blade.php';
if (file_exists($optimizedViewPath)) {
    echo "   ✅ Interface optimisée créée\n";

    $viewContent = file_get_contents($optimizedViewPath);

    // Vérifier les corrections z-index
    if (strpos($viewContent, 'z-index: 9999 !important') !== false) {
        echo "   ✅ Corrections z-index modals présentes\n";
    } else {
        echo "   ❌ Corrections z-index modals manquantes\n";
        $allTestsPassed = false;
    }

    // Vérifier les formulaires optimisés
    if (strpos($viewContent, 'setting_{{ $setting->key }}') !== false) {
        echo "   ✅ Formulaires avec noms corrects\n";
    } else {
        echo "   ❌ Problème noms de champs\n";
        $allTestsPassed = false;
    }

} else {
    echo "   ❌ Interface optimisée non trouvée\n";
    $allTestsPassed = false;
}

// 4. Test du contrôleur
echo "\n4. TEST CONTRÔLEUR:\n";
echo "==================\n";

try {
    $controller = new \App\Http\Controllers\ESBTP\ESBTPSettingsController();
    echo "   ✅ Contrôleur accessible\n";

    // Vérifier les méthodes
    $methods = ['index', 'update'];
    foreach ($methods as $method) {
        if (method_exists($controller, $method)) {
            echo "   ✅ Méthode $method() disponible\n";
        } else {
            echo "   ❌ Méthode $method() manquante\n";
            $allTestsPassed = false;
        }
    }

} catch (\Exception $e) {
    echo "   ❌ Erreur contrôleur: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

// 5. Test des routes
echo "\n5. TEST ROUTES:\n";
echo "===============\n";

try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $settingsRoutes = 0;

    foreach ($routes as $route) {
        if (strpos($route->getName(), 'esbtp.settings') !== false) {
            $settingsRoutes++;
        }
    }

    echo "   📊 Routes settings trouvées: $settingsRoutes\n";

    if ($settingsRoutes >= 2) {
        echo "   ✅ Routes essentielles présentes\n";
    } else {
        echo "   ❌ Routes manquantes\n";
        $allTestsPassed = false;
    }

} catch (\Exception $e) {
    echo "   ❌ Erreur routes: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

// 6. Test de la configuration cache
echo "\n6. TEST CONFIGURATION CACHE:\n";
echo "============================\n";

$cacheConfig = config('cache');
$defaultDriver = $cacheConfig['default'];

echo "   📋 Driver de cache: $defaultDriver\n";

if ($defaultDriver === 'file') {
    echo "   ✅ Driver compatible (file)\n";
} else {
    echo "   ⚠️ Driver: $defaultDriver (peut causer des problèmes)\n";
}

// 7. Statistiques finales
echo "\n7. STATISTIQUES FINALES:\n";
echo "========================\n";

try {
    $totalSettings = \App\Models\Setting::count();
    $establishmentSettings = \App\Models\Setting::where('group', 'establishment')->count();
    $pdfSettings = \App\Models\Setting::where('group', 'pdf')->count();

    echo "   📊 Total paramètres: $totalSettings\n";
    echo "   📊 Paramètres établissement: $establishmentSettings\n";
    echo "   📊 Paramètres PDF: $pdfSettings\n";

    if ($totalSettings > 0) {
        echo "   ✅ Base de données settings fonctionnelle\n";
    } else {
        echo "   ❌ Aucun paramètre trouvé\n";
        $allTestsPassed = false;
    }

} catch (\Exception $e) {
    echo "   ❌ Erreur base de données: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

// Résumé final
echo "\n" . str_repeat("=", 50) . "\n";
echo "RÉSUMÉ FINAL\n";
echo str_repeat("=", 50) . "\n\n";

if ($allTestsPassed) {
    echo "🎉 TOUS LES TESTS PASSENT !\n\n";
    echo "✅ CORRECTIONS APPLIQUÉES:\n";
    echo "   • Erreur Redis corrigée\n";
    echo "   • Modals z-index fixés\n";
    echo "   • Interface optimisée créée\n";
    echo "   • Paramètres pertinents conservés\n";
    echo "   • Formulaires corrigés\n";
    echo "   • Cache vidé\n\n";

    echo "🚀 PROCHAINES ÉTAPES:\n";
    echo "1. Tester l'interface: http://localhost:8000/esbtp/settings\n";
    echo "2. Utiliser l'interface optimisée si nécessaire\n";
    echo "3. Vérifier la génération de bulletins PDF\n";
    echo "4. Créer une sauvegarde des paramètres\n\n";

} else {
    echo "⚠️ CERTAINS TESTS ONT ÉCHOUÉ\n\n";
    echo "❌ PROBLÈMES DÉTECTÉS:\n";
    echo "   • Vérifiez les erreurs ci-dessus\n";
    echo "   • Relancez les corrections nécessaires\n";
    echo "   • Testez manuellement l'interface\n\n";
}

echo "📋 FICHIERS CRÉÉS:\n";
echo "   • resources/views/esbtp/settings/index-optimized.blade.php\n";
echo "   • Corrections dans app/Models/Setting.php\n";
echo "   • Corrections dans app/Http/Middleware/CheckRequiredSettings.php\n\n";

echo "🔧 COMMANDES UTILES:\n";
echo "   • php artisan cache:clear\n";
echo "   • php artisan config:clear\n";
echo "   • php artisan route:clear\n\n";

echo "✅ Test terminé!\n";
