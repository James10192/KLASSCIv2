<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== OPTIMISATION DE L'INTERFACE SETTINGS ===\n\n";

// Paramètres utilisés dans le bulletin (à conserver)
$usedSettings = [
    // Établissement
    'school_country',
    'school_name',
    'school_acronym',
    'school_address',
    'school_city',
    'school_phone',
    'school_email',
    'school_logo',
    'director_name',
    'director_title',

    // PDF
    'pdf_margin_top',
    'pdf_margin_right',
    'pdf_margin_bottom',
    'pdf_margin_left',
    'pdf_font_size',
    'pdf_watermark',
    'pdf_header_text',
    'pdf_footer_text',
    'pdf_show_logo',
    'pdf_signature_director'
];

// Paramètres non utilisés (à supprimer ou masquer)
$unusedSettings = [
    'school_postal_code',
    'school_website',
    'pdf_logo_position',
    'pdf_signature_secretary',
    'theme_primary_color',
    'theme_secondary_color',
    'theme_success_color',
    'theme_danger_color',
    'theme_warning_color',
    'theme_info_color',
    'sidebar_color',
    'navbar_color',
    'background_color',
    'text_color',
    'link_color',
    'current_academic_year',
    'semester_system',
    'email_notifications',
    'sms_notifications',
    'parent_notifications',
    'teacher_notifications',
    'admin_notifications'
];

echo "1. SUPPRESSION DES PARAMÈTRES NON UTILISÉS:\n";
echo "==========================================\n\n";

try {
    $deletedCount = 0;

    foreach ($unusedSettings as $settingKey) {
        $setting = \App\Models\Setting::where('key', $settingKey)->first();

        if ($setting) {
            echo "   🗑️ Suppression: $settingKey\n";
            $setting->delete();
            $deletedCount++;
        } else {
            echo "   ⚠️ Non trouvé: $settingKey\n";
        }
    }

    echo "\n✅ $deletedCount paramètres supprimés\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors de la suppression: " . $e->getMessage() . "\n";
}

echo "\n2. VÉRIFICATION DES PARAMÈTRES CONSERVÉS:\n";
echo "========================================\n\n";

try {
    $keptCount = 0;

    foreach ($usedSettings as $settingKey) {
        $setting = \App\Models\Setting::where('key', $settingKey)->first();

        if ($setting) {
            echo "   ✅ Conservé: $settingKey\n";
            $keptCount++;
        } else {
            echo "   ❌ Manquant: $settingKey\n";
        }
    }

    echo "\n✅ $keptCount paramètres conservés\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors de la vérification: " . $e->getMessage() . "\n";
}

echo "\n3. MISE À JOUR DES GROUPES ET CATÉGORIES:\n";
echo "========================================\n\n";

try {
    // Réorganiser les paramètres par groupes logiques
    $groups = [
        'establishment' => [
            'school_name', 'school_acronym', 'school_country', 'school_address',
            'school_city', 'school_phone', 'school_email', 'school_logo',
            'director_name', 'director_title'
        ],
        'pdf' => [
            'pdf_margin_top', 'pdf_margin_right', 'pdf_margin_bottom', 'pdf_margin_left',
            'pdf_font_size', 'pdf_watermark', 'pdf_header_text', 'pdf_footer_text',
            'pdf_show_logo', 'pdf_signature_director'
        ]
    ];

    foreach ($groups as $group => $settings) {
        foreach ($settings as $settingKey) {
            $setting = \App\Models\Setting::where('key', $settingKey)->first();

            if ($setting && $setting->group !== $group) {
                $setting->group = $group;
                $setting->save();
                echo "   📝 Mis à jour: $settingKey -> groupe '$group'\n";
            }
        }
    }

    echo "\n✅ Groupes mis à jour\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors de la mise à jour: " . $e->getMessage() . "\n";
}

echo "\n4. STATISTIQUES FINALES:\n";
echo "========================\n\n";

try {
    $totalSettings = \App\Models\Setting::count();
    $establishmentSettings = \App\Models\Setting::where('group', 'establishment')->count();
    $pdfSettings = \App\Models\Setting::where('group', 'pdf')->count();

    echo "📊 STATISTIQUES:\n";
    echo "   • Total paramètres: $totalSettings\n";
    echo "   • Paramètres établissement: $establishmentSettings\n";
    echo "   • Paramètres PDF: $pdfSettings\n";
    echo "   • Autres paramètres: " . ($totalSettings - $establishmentSettings - $pdfSettings) . "\n";

    echo "\n🎯 OPTIMISATION RÉUSSIE:\n";
    echo "   ✅ Interface simplifiée\n";
    echo "   ✅ Paramètres pertinents conservés\n";
    echo "   ✅ Paramètres inutiles supprimés\n";
    echo "   ✅ Groupes réorganisés\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors des statistiques: " . $e->getMessage() . "\n";
}

echo "\n5. PROCHAINES ÉTAPES:\n";
echo "====================\n\n";

echo "🚀 ACTIONS RECOMMANDÉES:\n";
echo "1. Tester l'interface settings optimisée\n";
echo "2. Vérifier que le bulletin PDF fonctionne toujours\n";
echo "3. Mettre à jour la documentation\n";
echo "4. Créer une sauvegarde des paramètres\n";

echo "\n✅ Optimisation terminée!\n";
