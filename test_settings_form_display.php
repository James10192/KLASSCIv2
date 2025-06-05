<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\ESBTP\ESBTPSettingsController;
use App\Models\Setting;

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Affichage Formulaires Settings ===\n\n";

try {
    // 1. Vérifier les paramètres en base
    echo "1. Vérification des paramètres en base:\n";
    $totalSettings = Setting::count();
    echo "   ✅ Total paramètres: $totalSettings\n";

    // Vérifier les paramètres spécifiques
    $establishmentKeys = ['school_name', 'school_acronym', 'director_name'];
    $pdfKeys = ['pdf_margin_top', 'pdf_font_size', 'pdf_show_logo'];

    echo "\n2. Vérification paramètres établissement:\n";
    foreach ($establishmentKeys as $key) {
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            echo "   ✅ $key: {$setting->value}\n";
        } else {
            echo "   ❌ $key: Non trouvé\n";
        }
    }

    echo "\n3. Vérification paramètres PDF:\n";
    foreach ($pdfKeys as $key) {
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            echo "   ✅ $key: {$setting->value}\n";
        } else {
            echo "   ❌ $key: Non trouvé\n";
        }
    }

    // 2. Tester le contrôleur
    echo "\n4. Test du contrôleur:\n";

    // Simuler un utilisateur authentifié
    $user = \App\Models\User::first();
    if ($user) {
        auth()->login($user);
        echo "   ✅ Utilisateur authentifié: {$user->name}\n";
    }

    $controller = new ESBTPSettingsController();
    $response = $controller->index();

    if ($response instanceof \Illuminate\View\View) {
        $data = $response->getData();
        echo "   ✅ Vue retournée avec succès\n";
        echo "   ✅ Variables passées: " . implode(', ', array_keys($data)) . "\n";

        // Vérifier les données
        if (isset($data['flatSettings'])) {
            $flatCount = $data['flatSettings']->count();
            echo "   ✅ flatSettings: $flatCount paramètres\n";

            // Tester l'accès aux paramètres
            $schoolName = $data['flatSettings']->where('key', 'school_name')->first();
            if ($schoolName) {
                echo "   ✅ Accès school_name: {$schoolName->value}\n";
            } else {
                echo "   ❌ Impossible d'accéder à school_name\n";
            }
        }

        if (isset($data['settings'])) {
            $groupedCount = $data['settings']->count();
            echo "   ✅ settings groupés: $groupedCount catégories\n";
            echo "   ✅ Catégories: " . implode(', ', $data['settings']->keys()->toArray()) . "\n";
        }
    }

    echo "\n✅ Test terminé avec succès!\n";
    echo "\n🚀 Prochaine étape: Tester l'interface web\n";
    echo "   URL: http://localhost:8000/esbtp/settings\n";

} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
