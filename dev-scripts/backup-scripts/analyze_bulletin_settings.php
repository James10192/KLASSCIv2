<?php

echo "=== ANALYSE DES PARAMÈTRES UTILISÉS DANS LE BULLETIN ===\n\n";

// Lire le contenu du template bulletin
$bulletinPath = __DIR__ . '/resources/views/esbtp/bulletins/bulletin-pdf.blade.php';
$settingsPath = __DIR__ . '/app/Helpers/SettingsHelper.php';

if (!file_exists($bulletinPath)) {
    echo "❌ Fichier bulletin non trouvé: $bulletinPath\n";
    exit(1);
}

if (!file_exists($settingsPath)) {
    echo "❌ Fichier SettingsHelper non trouvé: $settingsPath\n";
    exit(1);
}

$bulletinContent = file_get_contents($bulletinPath);
$settingsContent = file_get_contents($settingsPath);

echo "1. PARAMÈTRES UTILISÉS DANS LE BULLETIN PDF:\n";
echo "==========================================\n\n";

// Analyser les paramètres utilisés dans le bulletin
$usedSettings = [];

// Paramètres de l'établissement utilisés
$schoolParams = [
    'country' => 'school_country',
    'name' => 'school_name',
    'acronym' => 'school_acronym',
    'address' => 'school_address',
    'city' => 'school_city',
    'phone' => 'school_phone',
    'email' => 'school_email',
    'logo' => 'school_logo',
    'director_name' => 'director_name',
    'director_title' => 'director_title'
];

echo "📋 PARAMÈTRES ÉTABLISSEMENT UTILISÉS:\n";
foreach ($schoolParams as $key => $setting) {
    if (strpos($bulletinContent, "\$schoolInfo['$key']") !== false) {
        echo "   ✅ $setting (utilisé comme \$schoolInfo['$key'])\n";
        $usedSettings[] = $setting;
    } else {
        echo "   ❌ $setting (NON utilisé)\n";
    }
}

// Paramètres PDF utilisés
$pdfParams = [
    'margin_top' => 'pdf_margin_top',
    'margin_right' => 'pdf_margin_right',
    'margin_bottom' => 'pdf_margin_bottom',
    'margin_left' => 'pdf_margin_left',
    'font_size' => 'pdf_font_size',
    'watermark' => 'pdf_watermark',
    'header_text' => 'pdf_header_text',
    'footer_text' => 'pdf_footer_text',
    'show_logo' => 'pdf_show_logo',
    'signature_director' => 'pdf_signature_director'
];

echo "\n📄 PARAMÈTRES PDF UTILISÉS:\n";
foreach ($pdfParams as $key => $setting) {
    if (strpos($bulletinContent, "\$pdfSettings['$key']") !== false) {
        echo "   ✅ $setting (utilisé comme \$pdfSettings['$key'])\n";
        $usedSettings[] = $setting;
    } else {
        echo "   ❌ $setting (NON utilisé)\n";
    }
}

echo "\n2. PARAMÈTRES NON UTILISÉS DANS LE BULLETIN:\n";
echo "==========================================\n\n";

// Analyser tous les paramètres définis dans SettingsHelper
$allSettings = [];

// Extraire les paramètres de getSchoolInfo() et getPdfSettings()
preg_match_all("/'(\w+)' => self::get\('([^']+)'/", $settingsContent, $matches);
for ($i = 0; $i < count($matches[1]); $i++) {
    $allSettings[] = $matches[2][$i];
}

// Paramètres non utilisés
$unusedSettings = array_diff($allSettings, $usedSettings);

echo "🗑️ PARAMÈTRES DÉFINIS MAIS NON UTILISÉS:\n";
foreach ($unusedSettings as $setting) {
    echo "   ❌ $setting\n";
}

echo "\n3. ANALYSE DES FORMULAIRES SETTINGS:\n";
echo "===================================\n\n";

// Lire le fichier de vue settings
$settingsViewPath = __DIR__ . '/resources/views/esbtp/settings/index.blade.php';
if (file_exists($settingsViewPath)) {
    $settingsViewContent = file_get_contents($settingsViewPath);

    // Compter les champs de formulaire
    preg_match_all('/name="setting_([^"]+)"/', $settingsViewContent, $formFields);
    $totalFormFields = count($formFields[1]);

    echo "📝 TOTAL CHAMPS FORMULAIRE: $totalFormFields\n";
    echo "📋 CHAMPS UTILISÉS DANS BULLETIN: " . count($usedSettings) . "\n";
    echo "🗑️ CHAMPS NON UTILISÉS: " . ($totalFormFields - count($usedSettings)) . "\n";

    $unusedFormFields = array_diff($formFields[1], $usedSettings);

    echo "\n🔍 CHAMPS FORMULAIRE NON UTILISÉS DANS BULLETIN:\n";
    foreach ($unusedFormFields as $field) {
        echo "   ❌ $field\n";
    }
} else {
    echo "❌ Fichier vue settings non trouvé\n";
}

echo "\n4. RECOMMANDATIONS:\n";
echo "==================\n\n";

echo "✅ PARAMÈTRES À CONSERVER (utilisés dans le bulletin):\n";
foreach ($usedSettings as $setting) {
    echo "   • $setting\n";
}

echo "\n🤔 PARAMÈTRES À ÉVALUER (non utilisés dans le bulletin):\n";
foreach ($unusedSettings as $setting) {
    echo "   • $setting (peut être utilisé ailleurs ou supprimé)\n";
}

echo "\n💡 SUGGESTIONS D'OPTIMISATION:\n";
echo "1. Supprimer les paramètres non utilisés pour simplifier l'interface\n";
echo "2. Regrouper les paramètres par utilisation réelle\n";
echo "3. Créer des onglets basés sur l'utilisation effective\n";
echo "4. Ajouter des descriptions pour clarifier l'usage de chaque paramètre\n";

echo "\n🎯 RÉSUMÉ:\n";
echo "• Paramètres utilisés: " . count($usedSettings) . "\n";
echo "• Paramètres non utilisés: " . count($unusedSettings) . "\n";
if (count($allSettings) > 0) {
    echo "• Taux d'utilisation: " . round((count($usedSettings) / count($allSettings)) * 100, 1) . "%\n";
}

echo "\n✅ Analyse terminée!\n";
