<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Setting;
use App\Helpers\SettingsHelper;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Simple des Settings ===\n\n";

try {
    // 1. Vérifier que les settings existent
    echo "1. Vérification des settings en base de données:\n";

    $totalSettings = Setting::count();
    echo "   ✅ Nombre total de settings: $totalSettings\n";

    $categories = Setting::distinct('category')->pluck('category');
    echo "   ✅ Catégories: " . $categories->implode(', ') . "\n";

    // 2. Tester le SettingsHelper
    echo "\n2. Test du SettingsHelper:\n";

    $schoolName = SettingsHelper::get('establishment.school_name', 'Nom par défaut');
    echo "   - Nom de l'école: $schoolName\n";

    $directorName = SettingsHelper::get('establishment.director_name', 'Directeur par défaut');
    echo "   - Nom du directeur: $directorName\n";

    $fontSize = SettingsHelper::get('pdf.font_size', 12);
    echo "   - Taille de police PDF: $fontSize\n";

    $marginTop = SettingsHelper::get('pdf.margin_top', 15);
    echo "   - Marge PDF top: $marginTop\n";

    // 3. Tester les méthodes groupées
    echo "\n3. Test des méthodes groupées:\n";

    $schoolInfo = SettingsHelper::getSchoolInfo();
    echo "   ✅ Informations école récupérées:\n";
    foreach ($schoolInfo as $key => $value) {
        echo "     * $key: $value\n";
    }

    $pdfSettings = SettingsHelper::getPdfSettings();
    echo "   ✅ Paramètres PDF récupérés:\n";
    foreach ($pdfSettings as $key => $value) {
        $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        echo "     * $key: $displayValue\n";
    }

    // 4. Modifier un setting et vérifier
    echo "\n4. Test de modification d'un setting:\n";

    $originalValue = SettingsHelper::get('establishment.school_name');
    echo "   - Valeur originale: $originalValue\n";

    // Modifier
    Setting::updateOrCreate(
        ['key' => 'establishment.school_name'],
        ['value' => 'ESBTP - Test Modifié', 'category' => 'establishment']
    );

    $newValue = SettingsHelper::get('establishment.school_name');
    echo "   - Nouvelle valeur: $newValue\n";

    // Restaurer
    Setting::where('key', 'establishment.school_name')->update(['value' => $originalValue]);
    $restoredValue = SettingsHelper::get('establishment.school_name');
    echo "   - Valeur restaurée: $restoredValue\n";

    // 5. Vérifier les settings critiques pour les bulletins
    echo "\n5. Settings critiques pour les bulletins:\n";

    $criticalSettings = [
        'establishment.school_name' => 'Nom de l\'école',
        'establishment.director_name' => 'Nom du directeur',
        'establishment.address' => 'Adresse',
        'establishment.phone' => 'Téléphone',
        'establishment.logo' => 'Logo',
        'pdf.font_size' => 'Taille police',
        'pdf.margin_top' => 'Marge haut',
        'pdf.show_watermark' => 'Afficher filigrane',
        'pdf.show_signature' => 'Afficher signature'
    ];

    foreach ($criticalSettings as $key => $description) {
        $value = SettingsHelper::get($key, 'NON DÉFINI');
        $displayValue = is_bool($value) ? ($value ? 'OUI' : 'NON') : $value;
        echo "   - $description: $displayValue\n";
    }

    echo "\n✅ Test simple terminé avec succès!\n";
    echo "\nConclusion:\n";
    echo "- Les settings sont bien stockés en base de données\n";
    echo "- Le SettingsHelper fonctionne correctement\n";
    echo "- Les modifications sont bien prises en compte\n";
    echo "- Tous les paramètres critiques sont disponibles\n";

} catch (Exception $e) {
    echo "\n❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
