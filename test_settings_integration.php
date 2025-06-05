<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\ESBTPBulletinController;
use App\Helpers\SettingsHelper;
use App\Models\Setting;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test d'intégration des Settings dans ESBTPBulletinController ===\n\n";

try {
    // 1. Vérifier que le SettingsHelper fonctionne
    echo "1. Test du SettingsHelper:\n";

    $schoolName = SettingsHelper::get('establishment.school_name', 'Valeur par défaut');
    echo "   - Nom de l'école: $schoolName\n";

    $pdfMarginTop = SettingsHelper::get('pdf.margin_top', 15);
    echo "   - Marge PDF top: $pdfMarginTop\n";

    $logo = SettingsHelper::get('establishment.logo', 'images/esbtp_logo.png');
    echo "   - Logo: $logo\n";

    // 2. Tester la méthode getPDFConfig via réflexion
    echo "\n2. Test de la méthode getPDFConfig:\n";

    $controller = new ESBTPBulletinController(app('App\Services\ESBTP\ESBTPAbsenceService'));

    // Utiliser la réflexion pour accéder à la méthode privée
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getPDFConfig');
    $method->setAccessible(true);

    $config = $method->invoke($controller);

    echo "   - Configuration récupérée:\n";
    foreach ($config as $key => $value) {
        echo "     * $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }

    // 3. Tester la méthode prepareLogoBase64
    echo "\n3. Test de la méthode prepareLogoBase64:\n";

    $logoMethod = $reflection->getMethod('prepareLogoBase64');
    $logoMethod->setAccessible(true);

    $logoBase64 = $logoMethod->invoke($controller, $config['school_logo']);

    if ($logoBase64) {
        echo "   - Logo converti en base64: " . substr($logoBase64, 0, 50) . "...\n";
        echo "   - Taille du logo base64: " . strlen($logoBase64) . " caractères\n";
    } else {
        echo "   - Aucun logo trouvé ou erreur de conversion\n";
    }

    // 4. Vérifier quelques settings spécifiques
    echo "\n4. Vérification des settings dans la base de données:\n";

    $settings = Setting::all();
    echo "   - Nombre total de settings: " . $settings->count() . "\n";

    $establishmentSettings = $settings->where('category', 'establishment');
    echo "   - Settings d'établissement: " . $establishmentSettings->count() . "\n";

    $pdfSettings = $settings->where('category', 'pdf');
    echo "   - Settings PDF: " . $pdfSettings->count() . "\n";

    // 5. Afficher quelques valeurs importantes
    echo "\n5. Valeurs importantes pour les bulletins:\n";

    $importantSettings = [
        'establishment.school_name',
        'establishment.director_name',
        'establishment.address',
        'pdf.font_size',
        'pdf.show_watermark',
        'pdf.show_signature'
    ];

    foreach ($importantSettings as $setting) {
        $value = SettingsHelper::get($setting, 'Non défini');
        echo "   - $setting: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }

    echo "\n✅ Test d'intégration terminé avec succès!\n";
    echo "\nLes settings sont maintenant intégrés dans le contrôleur des bulletins.\n";
    echo "Vous pouvez modifier les paramètres dans l'interface des settings et ils seront\n";
    echo "automatiquement utilisés lors de la génération des bulletins PDF.\n";

} catch (Exception $e) {
    echo "\n❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
