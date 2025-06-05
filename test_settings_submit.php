<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Submit Settings - Recherche Erreur Redis ===\n\n";

try {
    // Simuler une requête de mise à jour des settings
    echo "1. Test de simulation du submit settings:\n";

    // Créer une instance du contrôleur
    $controller = new \App\Http\Controllers\ESBTP\ESBTPSettingsController();
    echo "   ✅ Contrôleur créé\n";

    // Simuler les données de formulaire
    $formData = [
        'setting_school_name' => 'Test School',
        'setting_school_acronym' => 'TS',
        'setting_director_name' => 'Test Director',
        '_token' => 'test_token',
        '_method' => 'PUT'
    ];

    echo "   ✅ Données de formulaire préparées\n";

    // Tester les méthodes individuellement
    echo "\n2. Test des méthodes utilisées dans le submit:\n";

    // Test Setting::set()
    try {
        $result = \App\Models\Setting::set('test_key', 'test_value');
        echo "   ✅ Setting::set() fonctionne\n";
    } catch (\Exception $e) {
        echo "   ❌ Erreur Setting::set(): " . $e->getMessage() . "\n";
    }

    // Test Setting::clearCache()
    try {
        \App\Models\Setting::clearCache();
        echo "   ✅ Setting::clearCache() fonctionne\n";
    } catch (\Exception $e) {
        echo "   ❌ Erreur Setting::clearCache(): " . $e->getMessage() . "\n";
    }

    // Test CheckRequiredSettings::clearCache()
    try {
        \App\Http\Middleware\CheckRequiredSettings::clearCache();
        echo "   ✅ CheckRequiredSettings::clearCache() fonctionne\n";
    } catch (\Exception $e) {
        echo "   ❌ Erreur CheckRequiredSettings::clearCache(): " . $e->getMessage() . "\n";
    }

    echo "\n3. Test des opérations de cache:\n";

    // Test Cache::remember
    try {
        $test = \Illuminate\Support\Facades\Cache::remember('test_cache_key', 60, function() {
            return 'test_value';
        });
        echo "   ✅ Cache::remember() fonctionne\n";
    } catch (\Exception $e) {
        echo "   ❌ Erreur Cache::remember(): " . $e->getMessage() . "\n";
    }

    // Test Cache::forget
    try {
        \Illuminate\Support\Facades\Cache::forget('test_cache_key');
        echo "   ✅ Cache::forget() fonctionne\n";
    } catch (\Exception $e) {
        echo "   ❌ Erreur Cache::forget(): " . $e->getMessage() . "\n";
    }

    echo "\n4. Test de la configuration cache:\n";

    $cacheConfig = config('cache');
    echo "   📋 Driver par défaut: " . $cacheConfig['default'] . "\n";
    echo "   📋 Stores disponibles: " . implode(', ', array_keys($cacheConfig['stores'])) . "\n";

    $currentStore = \Illuminate\Support\Facades\Cache::getStore();
    echo "   📋 Store actuel: " . get_class($currentStore) . "\n";

    // Vérifier si le store a des méthodes Redis
    if (method_exists($currentStore, 'getRedis')) {
        echo "   ⚠️  Store a une méthode getRedis() - PROBLÈME POTENTIEL\n";
    } else {
        echo "   ✅ Store n'a pas de méthode getRedis() - OK\n";
    }

    echo "\n5. Test spécifique de l'erreur Redis:\n";

    // Tenter d'appeler getRedis() pour reproduire l'erreur
    try {
        if (method_exists($currentStore, 'getRedis')) {
            $redis = $currentStore->getRedis();
            echo "   ❌ getRedis() accessible - C'est le problème!\n";
        } else {
            echo "   ✅ getRedis() non accessible - Pas de problème\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Erreur getRedis(): " . $e->getMessage() . "\n";
        echo "   🎯 C'EST L'ERREUR QUE NOUS CHERCHONS!\n";
    }

    echo "\n✅ Test terminé!\n";

} catch (\Exception $e) {
    echo "\n❌ Erreur générale: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
