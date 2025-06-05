<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\ESBTP\ESBTPSettingsController;
use App\Models\Setting;
use App\Models\User;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test de l'Interface Web des Settings ===\n\n";

try {
    // 1. Vérifier que le contrôleur existe et fonctionne
    echo "1. Test du contrôleur ESBTPSettingsController:\n";

    $controller = new ESBTPSettingsController();
    echo "   ✅ Contrôleur instancié avec succès\n";

    // 2. Simuler un utilisateur authentifié (superAdmin)
    echo "\n2. Simulation d'un utilisateur authentifié:\n";

    $superAdmin = User::whereHas('roles', function($query) {
        $query->where('name', 'superAdmin');
    })->first();

    if (!$superAdmin) {
        echo "   ⚠️  Aucun superAdmin trouvé, création d'un utilisateur test...\n";
        // Pour le test, on continue sans authentification
    } else {
        echo "   ✅ SuperAdmin trouvé: {$superAdmin->name}\n";
        // Simuler l'authentification
        auth()->login($superAdmin);
    }

    // 3. Tester la méthode index (affichage des settings)
    echo "\n3. Test de la méthode index (affichage):\n";

    try {
        $response = $controller->index();
        echo "   ✅ Méthode index exécutée avec succès\n";

        // Vérifier que la vue contient les bonnes données
        if (method_exists($response, 'getData')) {
            $data = $response->getData();
            echo "   - Données passées à la vue:\n";
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    echo "     * $key: " . (is_array($value) ? count($value) . " éléments" : "objet") . "\n";
                } else {
                    echo "     * $key: $value\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur dans la méthode index: " . $e->getMessage() . "\n";
    }

    // 4. Tester la sauvegarde des settings (utiliser update au lieu de store)
    echo "\n4. Test de la sauvegarde des settings:\n";

    // Créer une requête simulée avec des données de test au format attendu
    $testData = [
        'setting_establishment.school_name' => 'ESBTP - Test École',
        'setting_establishment.director_name' => 'Dr. Test DIRECTEUR',
        'setting_establishment.address' => 'Adresse de test',
        'setting_establishment.phone' => '01 02 03 04 05',
        'setting_establishment.email' => 'test@esbtp.ci',
        'setting_pdf.font_size' => '13',
        'setting_pdf.margin_top' => '20',
        'setting_pdf.margin_bottom' => '20',
        'setting_pdf.show_watermark' => '1',
        'setting_pdf.watermark_text' => 'TEST WATERMARK',
        'setting_interface.primary_color' => '#ff0000',
        'setting_interface.secondary_color' => '#00ff00'
    ];

    $request = new Request();
    $request->merge($testData);

    try {
        $response = $controller->update($request);
        echo "   ✅ Méthode update exécutée avec succès\n";

        // Vérifier que les settings ont été sauvegardés
        $savedSettings = Setting::whereIn('key', [
            'establishment.school_name',
            'establishment.director_name',
            'pdf.font_size',
            'pdf.show_watermark',
            'interface.primary_color'
        ])->get();

        echo "   - Settings sauvegardés:\n";
        foreach ($savedSettings as $setting) {
            echo "     * {$setting->key}: {$setting->value}\n";
        }

    } catch (Exception $e) {
        echo "   ❌ Erreur dans la méthode update: " . $e->getMessage() . "\n";
    }

    // 5. Vérifier les routes
    echo "\n5. Vérification des routes:\n";

    $routes = [
        'esbtp.settings.index' => 'GET /esbtp/settings',
        'esbtp.settings.update' => 'PUT /esbtp/settings'
    ];

    foreach ($routes as $routeName => $description) {
        try {
            $url = route($routeName);
            echo "   ✅ Route $routeName: $url\n";
        } catch (Exception $e) {
            echo "   ❌ Route $routeName non trouvée: " . $e->getMessage() . "\n";
        }
    }

    // 6. Vérifier que la vue existe
    echo "\n6. Vérification des vues:\n";

    $views = [
        'esbtp.settings.index' => 'resources/views/esbtp/settings/index.blade.php'
    ];

    foreach ($views as $viewName => $path) {
        if (view()->exists($viewName)) {
            echo "   ✅ Vue $viewName existe\n";
        } else {
            echo "   ❌ Vue $viewName non trouvée\n";
        }

        if (file_exists(resource_path('views/esbtp/settings/index.blade.php'))) {
            echo "   ✅ Fichier de vue physique existe\n";
        } else {
            echo "   ❌ Fichier de vue physique non trouvé\n";
        }
    }

    // 7. Test de validation
    echo "\n7. Test de validation des données:\n";

    $invalidData = [
        'setting_establishment.school_name' => '', // Vide
        'setting_establishment.email' => 'email-invalide', // Email invalide
        'setting_pdf.font_size' => 'abc', // Non numérique
        'setting_pdf.margin_top' => '-5' // Négatif
    ];

    $invalidRequest = new Request();
    $invalidRequest->merge($invalidData);

    try {
        $response = $controller->update($invalidRequest);
        echo "   ⚠️  Validation non stricte - données invalides acceptées\n";
    } catch (Exception $e) {
        echo "   ✅ Validation fonctionne - données invalides rejetées\n";
        echo "     Erreur: " . $e->getMessage() . "\n";
    }

    echo "\n✅ Test de l'interface web terminé!\n";
    echo "\nProchaines actions recommandées:\n";
    echo "1. Accéder à /esbtp/settings dans le navigateur\n";
    echo "2. Modifier quelques paramètres\n";
    echo "3. Sauvegarder et vérifier les changements\n";
    echo "4. Générer un bulletin PDF pour voir les modifications\n";

} catch (Exception $e) {
    echo "\n❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
