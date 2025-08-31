<?php

require_once 'vendor/autoload.php';

use App\Models\Setting;

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CONFIGURATION DES PARAMÈTRES DE L'ÉCOLE ===\n\n";

try {
    // Paramètres de l'école selon les demandes de l'utilisateur
    $schoolSettings = [
        'school_name' => 'Ecole Spéciale du Bâtiment et des Travaux Publics',
        'school_acronym' => 'ESBTP',
        'school_country' => 'Côte d\'Ivoire',
        'school_address' => 'BP 2541 Yamoussoukro',
        'school_city' => 'Yamoussoukro',
        'school_phone' => '30 64 39 93',
        'school_email' => 'esbtpabidjan@esbtp-ci.net',
        'director_name' => 'Directeur Général',
        'director_title' => 'Directeur Général'
    ];

    echo "1. Configuration des paramètres de l'établissement:\n";

    foreach ($schoolSettings as $key => $value) {
        $setting = Setting::where('key', $key)->first();

        if ($setting) {
            $oldValue = $setting->value;
            $setting->update(['value' => $value]);
            echo "   ✅ $key: '$oldValue' → '$value'\n";
        } else {
            // Créer le paramètre s'il n'existe pas
            Setting::create([
                'key' => $key,
                'value' => $value,
                'type' => 'string',
                'category' => 'establishment',
                'description' => ucfirst(str_replace('_', ' ', $key)),
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1
            ]);
            echo "   ✅ $key: Créé avec la valeur '$value'\n";
        }
    }

    // Paramètres PDF pour le bulletin
    $pdfSettings = [
        'pdf_margin_top' => '15',
        'pdf_margin_right' => '15',
        'pdf_margin_bottom' => '15',
        'pdf_margin_left' => '15',
        'pdf_font_size' => '12',
        'pdf_show_logo' => '1',
        'pdf_header_text' => '',
        'pdf_footer_text' => 'Bulletin informatisé, aucun duplicata n\'est délivré',
        'pdf_watermark' => '',
        'pdf_signature_director' => '1'
    ];

    echo "\n2. Configuration des paramètres PDF:\n";

    foreach ($pdfSettings as $key => $value) {
        $setting = Setting::where('key', $key)->first();

        if ($setting) {
            $oldValue = $setting->value;
            $setting->update(['value' => $value]);
            echo "   ✅ $key: '$oldValue' → '$value'\n";
        } else {
            // Créer le paramètre s'il n'existe pas
            $type = in_array($key, ['pdf_show_logo', 'pdf_signature_director']) ? 'boolean' :
                   (in_array($key, ['pdf_margin_top', 'pdf_margin_right', 'pdf_margin_bottom', 'pdf_margin_left', 'pdf_font_size']) ? 'integer' : 'string');

            Setting::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'category' => 'pdf',
                'description' => ucfirst(str_replace(['pdf_', '_'], ['', ' '], $key)),
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2
            ]);
            echo "   ✅ $key: Créé avec la valeur '$value'\n";
        }
    }

    // Créer le paramètre pour le logo s'il n'existe pas
    $logoSetting = Setting::where('key', 'school_logo')->first();
    if (!$logoSetting) {
        Setting::create([
            'key' => 'school_logo',
            'value' => '',
            'type' => 'file',
            'category' => 'establishment',
            'description' => 'Logo de l\'établissement (format: JPG, PNG, GIF - max 2MB)',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 8
        ]);
        echo "\n   ✅ school_logo: Paramètre créé pour l'upload du logo\n";
    }

    // Vider les caches
    Setting::clearCache();

    echo "\n✅ CONFIGURATION TERMINÉE!\n\n";
    echo "📋 RÉSUMÉ DES PARAMÈTRES CONFIGURÉS:\n";
    echo "   • Nom de l'école: Ecole Spéciale du Bâtiment et des Travaux Publics\n";
    echo "   • Adresse: BP 2541 Yamoussoukro\n";
    echo "   • Téléphone: 30 64 39 93\n";
    echo "   • Email: esbtpabidjan@esbtp-ci.net\n";
    echo "   • Logo: Configurable via l'interface\n\n";

    echo "🚀 PROCHAINES ÉTAPES:\n";
    echo "   1. Accédez à: http://localhost:8000/esbtp/settings\n";
    echo "   2. Uploadez le logo de votre école dans la section Établissement\n";
    echo "   3. Modifiez les autres paramètres selon vos besoins\n";
    echo "   4. Testez la génération d'un bulletin PDF\n\n";

} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
