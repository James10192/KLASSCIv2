<?php

require_once 'vendor/autoload.php';

// Configuration de la base de données
$config = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'esbtp_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];

// Connexion à la base de données
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== TEST DES PARAMÈTRES DE BULLETIN ESBTP ===\n\n";

try {
    // Vérifier les paramètres de bulletin
    $bulletinParams = $capsule->table('parameters')
        ->where('category', 'bulletin')
        ->orderBy('sort_order')
        ->get();

    echo "📊 PARAMÈTRES DE BULLETIN TROUVÉS: " . $bulletinParams->count() . "\n\n";

    // Grouper par type de fonctionnalité
    $groups = [
        'En-tête' => ['bulletin_show_header', 'bulletin_show_logo', 'bulletin_show_school_info', 'bulletin_show_republic_info', 'bulletin_show_ministry_info', 'bulletin_show_edition_date', 'bulletin_show_cycle_info'],
        'Informations étudiant' => ['bulletin_show_student_info', 'bulletin_show_matricule', 'bulletin_show_birth_date', 'bulletin_show_redoublant', 'bulletin_show_class_info', 'bulletin_show_effectif'],
        'Tableau des matières' => ['bulletin_show_subjects_table', 'bulletin_show_general_subjects', 'bulletin_show_technical_subjects', 'bulletin_show_subject_average', 'bulletin_show_coefficient', 'bulletin_show_weighted_average', 'bulletin_show_rank_per_subject', 'bulletin_show_teachers', 'bulletin_show_appreciations', 'bulletin_show_section_averages'],
        'Absences' => ['bulletin_show_absences', 'bulletin_show_justified_absences', 'bulletin_show_unjustified_absences'],
        'Résultats' => ['bulletin_show_results_section', 'bulletin_show_raw_average', 'bulletin_show_attendance_note', 'bulletin_show_semester_average', 'bulletin_show_student_rank'],
        'Mentions' => ['bulletin_show_mentions', 'bulletin_show_felicitation', 'bulletin_show_encouragement', 'bulletin_show_honor_roll', 'bulletin_show_work_warning', 'bulletin_show_conduct_blame'],
        'Statistiques' => ['bulletin_show_statistics', 'bulletin_show_highest_average', 'bulletin_show_lowest_average', 'bulletin_show_class_average'],
        'Signatures' => ['bulletin_show_council_decision', 'bulletin_show_signature', 'bulletin_show_director_signature'],
        'Fonctionnalités avancées' => ['bulletin_auto_calculate_rank', 'bulletin_auto_calculate_mention', 'bulletin_auto_calculate_attendance', 'bulletin_require_teacher_assignment', 'bulletin_require_subject_config', 'bulletin_validate_averages'],
        'Seuils' => ['bulletin_felicitation_threshold', 'bulletin_encouragement_threshold', 'bulletin_honor_roll_threshold', 'bulletin_work_warning_threshold'],
        'Personnalisation' => ['bulletin_school_name_custom', 'bulletin_republic_text', 'bulletin_union_text', 'bulletin_ministry_text', 'bulletin_cycle_text', 'bulletin_cycle_abbreviation'],
        'Options d\'affichage' => ['bulletin_show_print_button', 'bulletin_paper_format', 'bulletin_orientation', 'bulletin_font_size', 'bulletin_dpi']
    ];

    foreach ($groups as $groupName => $expectedKeys) {
        echo "🔹 $groupName:\n";
        $found = 0;
        foreach ($expectedKeys as $key) {
            $param = $bulletinParams->where('key', $key)->first();
            if ($param) {
                echo "  ✅ $key = '{$param->value}'\n";
                $found++;
            } else {
                echo "  ❌ $key (MANQUANT)\n";
            }
        }
        echo "  📈 $found/" . count($expectedKeys) . " paramètres trouvés\n\n";
    }

    // Vérifier les valeurs par défaut importantes
    echo "🎯 VÉRIFICATION DES VALEURS PAR DÉFAUT:\n";
    $importantDefaults = [
        'bulletin_felicitation_threshold' => '16',
        'bulletin_encouragement_threshold' => '14',
        'bulletin_honor_roll_threshold' => '12',
        'bulletin_work_warning_threshold' => '8',
        'bulletin_paper_format' => 'A4',
        'bulletin_orientation' => 'portrait',
        'bulletin_font_size' => '11',
        'bulletin_dpi' => '150'
    ];

    foreach ($importantDefaults as $key => $expectedValue) {
        $param = $bulletinParams->where('key', $key)->first();
        if ($param && $param->value === $expectedValue) {
            echo "  ✅ $key = '$expectedValue' (CORRECT)\n";
        } elseif ($param) {
            echo "  ⚠️  $key = '{$param->value}' (attendu: '$expectedValue')\n";
        } else {
            echo "  ❌ $key (MANQUANT)\n";
        }
    }

    // Statistiques finales
    echo "\n📈 STATISTIQUES FINALES:\n";
    $totalExpected = array_sum(array_map('count', $groups));
    $totalFound = $bulletinParams->count();
    $percentage = $totalFound > 0 ? round(($totalFound / $totalExpected) * 100, 1) : 0;

    echo "  • Paramètres attendus: $totalExpected\n";
    echo "  • Paramètres trouvés: $totalFound\n";
    echo "  • Pourcentage: $percentage%\n";

    if ($percentage >= 100) {
        echo "  🎉 TOUS LES PARAMÈTRES SONT PRÉSENTS!\n";
    } elseif ($percentage >= 80) {
        echo "  ✅ La plupart des paramètres sont présents\n";
    } else {
        echo "  ⚠️  Plusieurs paramètres manquent\n";
    }

    // Test de la structure des paramètres
    echo "\n🔍 VÉRIFICATION DE LA STRUCTURE:\n";
    $requiredFields = ['key', 'value', 'category', 'description'];
    $sampleParam = $bulletinParams->first();

    if ($sampleParam) {
        foreach ($requiredFields as $field) {
            if (property_exists($sampleParam, $field)) {
                echo "  ✅ Champ '$field' présent\n";
            } else {
                echo "  ❌ Champ '$field' manquant\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DU TEST ===\n";
