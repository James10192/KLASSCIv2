<?php

// Initialiser l'application Laravel
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Setting;

echo "=== TEST SIMPLE DU SYSTÈME DE BULLETIN CONFIGURABLE ===\n\n";

try {
    // 1. Compter les paramètres de bulletin
    $count = Setting::where('category', 'bulletin')->count();
    echo "✅ Nombre de paramètres de bulletin: $count\n\n";

    // 2. Tester quelques paramètres clés
    echo "🔑 Paramètres clés:\n";
    $keyParams = [
        'bulletin_show_header',
        'bulletin_show_logo',
        'bulletin_felicitation_threshold',
        'bulletin_paper_format',
        'bulletin_auto_calculate_rank'
    ];

    foreach ($keyParams as $key) {
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            echo "   ✅ $key: " . $setting->value . "\n";
        } else {
            echo "   ❌ $key: Non trouvé\n";
        }
    }

    // 3. Catégoriser les paramètres
    echo "\n📊 Répartition des paramètres:\n";
    $allParams = Setting::where('category', 'bulletin')->get();

    $categories = [
        'display' => 0,
        'functional' => 0,
        'threshold' => 0,
        'customization' => 0,
        'options' => 0
    ];

    foreach ($allParams as $param) {
        if (strpos($param->key, 'show_') !== false) {
            $categories['display']++;
        } elseif (strpos($param->key, 'auto_') !== false ||
                 strpos($param->key, 'require_') !== false ||
                 strpos($param->key, 'validate_') !== false) {
            $categories['functional']++;
        } elseif (strpos($param->key, 'threshold') !== false) {
            $categories['threshold']++;
        } elseif (strpos($param->key, 'custom') !== false ||
                 strpos($param->key, 'text') !== false) {
            $categories['customization']++;
        } else {
            $categories['options']++;
        }
    }

    echo "   - Affichage: " . $categories['display'] . "\n";
    echo "   - Fonctionnels: " . $categories['functional'] . "\n";
    echo "   - Seuils: " . $categories['threshold'] . "\n";
    echo "   - Personnalisation: " . $categories['customization'] . "\n";
    echo "   - Options PDF: " . $categories['options'] . "\n";

    // 4. Vérifier les fichiers
    echo "\n📁 Vérification des fichiers:\n";

    $files = [
        'Template' => resource_path('views/esbtp/bulletins/pdf-configurable.blade.php'),
        'Interface de test' => resource_path('views/esbtp/bulletins/test-configurable.blade.php'),
        'Contrôleur' => app_path('Http/Controllers/ESBTPBulletinController.php')
    ];

    foreach ($files as $name => $path) {
        if (file_exists($path)) {
            echo "   ✅ $name: Existe\n";
        } else {
            echo "   ❌ $name: Manquant\n";
        }
    }

    // 5. Résumé
    echo "\n🎯 RÉSUMÉ:\n";
    if ($count >= 60) {
        echo "✅ Système de bulletin configurable installé avec succès!\n";
        echo "📊 $count paramètres disponibles\n";
        echo "🌐 Interface de test: http://localhost/ESBTP-yAKROv2Pascal/public/bulletin/configurable/test\n";
        echo "🔧 Test API: http://localhost/ESBTP-yAKROv2Pascal/public/test-bulletin-parameters\n";
    } else {
        echo "⚠️ Installation incomplète ($count paramètres trouvés)\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU TEST ===\n";
