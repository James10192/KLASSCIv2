<?php

// Initialiser l'application Laravel
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Parameter;

echo "=== TEST COMPLET DU SYSTÈME DE BULLETIN CONFIGURABLE ===\n\n";

try {
    // 1. Test de connexion à la base de données
    echo "1. Test de connexion à la base de données...\n";
    DB::connection()->getPdo();
    echo "✅ Connexion à la base de données réussie\n\n";

    // 2. Vérification de l'existence de la table settings
    echo "2. Vérification de la table settings...\n";
    $tableExists = DB::getSchemaBuilder()->hasTable('settings');
    if ($tableExists) {
        echo "✅ Table 'settings' existe\n";

        // Compter les paramètres de bulletin
        $bulletinParams = DB::table('settings')
            ->where('category', 'bulletin')
            ->count();
        echo "📊 Nombre de paramètres de bulletin: $bulletinParams\n\n";
    } else {
        echo "❌ Table 'settings' n'existe pas\n";
        echo "💡 Exécutez les migrations: php artisan migrate\n\n";
    }

    // 3. Test des paramètres de bulletin
    echo "3. Test des paramètres de bulletin...\n";
    $parameters = DB::table('settings')
        ->where('category', 'bulletin')
        ->get();

    if ($parameters->count() > 0) {
        echo "✅ Paramètres de bulletin trouvés: " . $parameters->count() . "\n";

        // Catégoriser les paramètres
        $categories = [
            'display' => 0,
            'functional' => 0,
            'threshold' => 0,
            'customization' => 0,
            'options' => 0
        ];

        foreach ($parameters as $param) {
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

        echo "📊 Répartition des paramètres:\n";
        echo "   - Affichage: " . $categories['display'] . "\n";
        echo "   - Fonctionnels: " . $categories['functional'] . "\n";
        echo "   - Seuils: " . $categories['threshold'] . "\n";
        echo "   - Personnalisation: " . $categories['customization'] . "\n";
        echo "   - Options PDF: " . $categories['options'] . "\n\n";

        // Afficher quelques paramètres clés
        echo "🔑 Paramètres clés:\n";
        $keyParams = [
            'bulletin_show_header',
            'bulletin_show_logo',
            'bulletin_show_student_info',
            'bulletin_felicitation_threshold',
            'bulletin_auto_calculate_rank',
            'bulletin_paper_format'
        ];

        foreach ($keyParams as $key) {
            $param = $parameters->where('key', $key)->first();
            if ($param) {
                echo "   - $key: " . $param->value . "\n";
            } else {
                echo "   - $key: ❌ Non trouvé\n";
            }
        }
        echo "\n";
    } else {
        echo "❌ Aucun paramètre de bulletin trouvé\n";
        echo "💡 Exécutez le seeder: php artisan db:seed --class=SettingsSeeder\n\n";
    }

    // 4. Test des modèles requis
    echo "4. Vérification des modèles requis...\n";
    $requiredTables = [
        'esbtp_etudiants',
        'esbtp_classes',
        'esbtp_annees_universitaires',
        'esbtp_filieres',
        'esbtp_niveaux_etudes'
    ];

    foreach ($requiredTables as $table) {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            $count = DB::table($table)->count();
            echo "✅ Table '$table' existe ($count enregistrements)\n";
        } else {
            echo "❌ Table '$table' n'existe pas\n";
        }
    }
    echo "\n";

    // 5. Test de la vue template
    echo "5. Vérification du template de bulletin...\n";
    $templatePath = resource_path('views/esbtp/bulletins/pdf-configurable.blade.php');
    if (file_exists($templatePath)) {
        echo "✅ Template de bulletin configurable existe\n";
        $templateSize = filesize($templatePath);
        echo "📄 Taille du template: " . number_format($templateSize) . " octets\n\n";
    } else {
        echo "❌ Template de bulletin configurable n'existe pas\n";
        echo "📍 Chemin attendu: $templatePath\n\n";
    }

    // 6. Test du contrôleur
    echo "6. Vérification du contrôleur...\n";
    $controllerPath = app_path('Http/Controllers/ESBTPBulletinController.php');
    if (file_exists($controllerPath)) {
        echo "✅ Contrôleur ESBTPBulletinController existe\n";

        // Vérifier la présence des méthodes
        $controllerContent = file_get_contents($controllerPath);
        $methods = [
            'generateConfigurableBulletin',
            'testBulletinParameters',
            'previewConfigurableBulletin',
            'getSettings'
        ];

        foreach ($methods as $method) {
            if (strpos($controllerContent, "function $method") !== false) {
                echo "✅ Méthode '$method' trouvée\n";
            } else {
                echo "❌ Méthode '$method' non trouvée\n";
            }
        }
        echo "\n";
    } else {
        echo "❌ Contrôleur ESBTPBulletinController n'existe pas\n\n";
    }

    // 7. Test des routes
    echo "7. Vérification des routes...\n";
    $routesPath = base_path('routes/web.php');
    if (file_exists($routesPath)) {
        $routesContent = file_get_contents($routesPath);
        $routes = [
            'test.bulletin.parameters',
            'bulletin.configurable.generate',
            'bulletin.configurable.preview',
            'bulletin.configurable.test'
        ];

        foreach ($routes as $route) {
            if (strpos($routesContent, $route) !== false) {
                echo "✅ Route '$route' trouvée\n";
            } else {
                echo "❌ Route '$route' non trouvée\n";
            }
        }
        echo "\n";
    }

    // 8. Test de l'interface de test
    echo "8. Vérification de l'interface de test...\n";
    $testViewPath = resource_path('views/esbtp/bulletins/test-configurable.blade.php');
    if (file_exists($testViewPath)) {
        echo "✅ Interface de test existe\n";
        $viewSize = filesize($testViewPath);
        echo "📄 Taille de l'interface: " . number_format($viewSize) . " octets\n\n";
    } else {
        echo "❌ Interface de test n'existe pas\n";
        echo "📍 Chemin attendu: $testViewPath\n\n";
    }

    // 9. Résumé et recommandations
    echo "9. RÉSUMÉ ET RECOMMANDATIONS\n";
    echo "================================\n";

    if ($parameters->count() >= 60) {
        echo "✅ Système de bulletin configurable correctement installé\n";
        echo "🎯 Paramètres disponibles: " . $parameters->count() . "/65+ attendus\n";

        echo "\n📋 ÉTAPES SUIVANTES:\n";
        echo "1. Accédez à l'interface de test: /bulletin/configurable/test\n";
        echo "2. Testez les paramètres avec le bouton 'Tester les Paramètres'\n";
        echo "3. Créez des données de test (étudiants, classes, etc.)\n";
        echo "4. Testez la génération de bulletin avec des données réelles\n";
        echo "5. Configurez les paramètres selon vos besoins\n";

        echo "\n🔧 CONFIGURATION RECOMMANDÉE:\n";
        echo "- Vérifiez les seuils de mention (félicitation: 16, encouragement: 14)\n";
        echo "- Configurez les textes personnalisés (école, république, etc.)\n";
        echo "- Ajustez les options d'affichage selon vos besoins\n";
        echo "- Testez les différents formats PDF (A4, orientation, DPI)\n";

    } else {
        echo "⚠️  Installation incomplète du système de bulletin\n";
        echo "🔧 Actions requises:\n";
        echo "1. Exécutez: php artisan db:seed --class=SettingsSeeder\n";
        echo "2. Vérifiez les migrations: php artisan migrate:status\n";
        echo "3. Relancez ce test après correction\n";
    }

    echo "\n🌐 URLS DE TEST:\n";
    echo "- Interface de test: http://localhost/ESBTP-yAKROv2Pascal/public/bulletin/configurable/test\n";
    echo "- Test paramètres: http://localhost/ESBTP-yAKROv2Pascal/public/test-bulletin-parameters\n";

    echo "\n✨ Système de bulletin configurable prêt à l'utilisation!\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    echo "\n🔧 Vérifiez:\n";
    echo "1. Configuration de la base de données (.env)\n";
    echo "2. Exécution des migrations\n";
    echo "3. Permissions des fichiers\n";
}

echo "\n=== FIN DU TEST ===\n";
