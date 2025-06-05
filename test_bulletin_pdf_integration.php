<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Http\Controllers\ESBTPBulletinController;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\Setting;
use App\Helpers\SettingsHelper;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test de Génération de Bulletin PDF avec Settings ===\n\n";

try {
    // 1. Modifier temporairement quelques settings pour le test
    echo "1. Modification temporaire des settings pour le test:\n";

    $originalSchoolName = SettingsHelper::get('establishment.school_name');
    $originalDirectorName = SettingsHelper::get('establishment.director_name');
    $originalFontSize = SettingsHelper::get('pdf.font_size');

    // Modifier les settings
    Setting::updateOrCreate(
        ['key' => 'establishment.school_name'],
        ['value' => 'ESBTP - École Test', 'category' => 'establishment']
    );

    Setting::updateOrCreate(
        ['key' => 'establishment.director_name'],
        ['value' => 'Dr. Jean KOUAME', 'category' => 'establishment']
    );

    Setting::updateOrCreate(
        ['key' => 'pdf.font_size'],
        ['value' => '14', 'category' => 'pdf']
    );

    echo "   ✅ Settings modifiés temporairement\n";
    echo "   - Nom école: " . SettingsHelper::get('establishment.school_name') . "\n";
    echo "   - Directeur: " . SettingsHelper::get('establishment.director_name') . "\n";
    echo "   - Taille police: " . SettingsHelper::get('pdf.font_size') . "\n";

    // 2. Récupérer un étudiant pour le test
    echo "\n2. Recherche d'un étudiant pour le test:\n";

    $etudiant = ESBTPEtudiant::with(['classe', 'classe.filiere', 'classe.niveauEtude'])
                             ->first();

    if (!$etudiant) {
        echo "   ❌ Aucun étudiant trouvé dans la base de données\n";
        echo "   Veuillez d'abord créer des étudiants pour tester\n";
        return;
    }

    echo "   ✅ Étudiant trouvé: {$etudiant->prenom} {$etudiant->nom}\n";
    echo "   - Classe: " . ($etudiant->classe ? $etudiant->classe->nom : 'Non définie') . "\n";

    // 3. Récupérer l'année universitaire
    $anneeUniversitaire = ESBTPAnneeUniversitaire::first();

    if (!$anneeUniversitaire) {
        echo "   ❌ Aucune année universitaire trouvée\n";
        return;
    }

    echo "   ✅ Année universitaire: {$anneeUniversitaire->nom}\n";

    // 4. Tester la génération du PDF
    echo "\n3. Test de génération du bulletin PDF:\n";

    $controller = new ESBTPBulletinController(app('App\Services\ESBTP\ESBTPAbsenceService'));

    // Créer une requête simulée
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'etudiant_id' => $etudiant->id,
        'classe_id' => $etudiant->classe_id,
        'annee_universitaire_id' => $anneeUniversitaire->id,
        'semestre' => 1
    ]);

    echo "   - Tentative de génération du PDF...\n";

    // Utiliser la réflexion pour accéder à la méthode
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getPDFConfig');
    $method->setAccessible(true);
    $config = $method->invoke($controller);

    echo "   ✅ Configuration PDF récupérée avec les nouveaux settings:\n";
    echo "     * Nom école: {$config['school_name']}\n";
    echo "     * Directeur: {$config['director_name']}\n";
    echo "     * Taille police: {$config['pdf_font_size']}\n";
    echo "     * Logo: {$config['school_logo']}\n";

    // 5. Vérifier que le template PDF utilise bien les settings
    echo "\n4. Vérification du template PDF:\n";

    $templatePath = resource_path('views/esbtp/bulletins/bulletin-pdf.blade.php');
    if (file_exists($templatePath)) {
        $templateContent = file_get_contents($templatePath);

        $checks = [
            '$config[\'school_name\']' => 'Nom de l\'école',
            '$config[\'director_name\']' => 'Nom du directeur',
            '$config[\'pdf_font_size\']' => 'Taille de police',
            '$config[\'school_logo\']' => 'Logo de l\'école'
        ];

        foreach ($checks as $pattern => $description) {
            if (strpos($templateContent, $pattern) !== false) {
                echo "   ✅ $description utilisé dans le template\n";
            } else {
                echo "   ⚠️  $description non trouvé dans le template\n";
            }
        }
    } else {
        echo "   ❌ Template PDF non trouvé\n";
    }

    // 6. Restaurer les settings originaux
    echo "\n5. Restauration des settings originaux:\n";

    if ($originalSchoolName) {
        Setting::where('key', 'establishment.school_name')->update(['value' => $originalSchoolName]);
    }
    if ($originalDirectorName) {
        Setting::where('key', 'establishment.director_name')->update(['value' => $originalDirectorName]);
    }
    if ($originalFontSize) {
        Setting::where('key', 'pdf.font_size')->update(['value' => $originalFontSize]);
    }

    echo "   ✅ Settings restaurés\n";

    echo "\n✅ Test de génération de bulletin PDF terminé avec succès!\n";
    echo "\nProchaines étapes recommandées:\n";
    echo "1. Tester l'interface web des settings\n";
    echo "2. Générer un vrai bulletin PDF via l'interface\n";
    echo "3. Vérifier que les modifications apparaissent dans le PDF\n";
    echo "4. Tester l'upload du logo\n";

} catch (Exception $e) {
    echo "\n❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
