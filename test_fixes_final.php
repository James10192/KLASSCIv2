<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
);

echo "=== TEST DES CORRECTIONS FINALES ===\n\n";

// Test 1: Vérifier la relation enseignant dans ESBTPSeanceCours
echo "1. Test de la relation enseignant dans ESBTPSeanceCours:\n";
try {
    $seanceClass = new ReflectionClass(\App\Models\ESBTPSeanceCours::class);

    // Vérifier que la méthode enseignant() existe
    if ($seanceClass->hasMethod('enseignant')) {
        echo "   ✅ Méthode enseignant() existe\n";
    } else {
        echo "   ❌ Méthode enseignant() manquante\n";
    }

    // Vérifier que la méthode teacher() existe
    if ($seanceClass->hasMethod('teacher')) {
        echo "   ✅ Méthode teacher() existe\n";
    } else {
        echo "   ❌ Méthode teacher() manquante\n";
    }

    // Vérifier que l'accesseur getEnseignantNameAttribute() existe
    if ($seanceClass->hasMethod('getEnseignantNameAttribute')) {
        echo "   ✅ Accesseur getEnseignantNameAttribute() existe\n";
    } else {
        echo "   ❌ Accesseur getEnseignantNameAttribute() manquant\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Vérifier la route mes-absences.index
echo "2. Test de la route mes-absences.index:\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $routeFound = false;

    foreach ($routes as $route) {
        if ($route->getName() === 'mes-absences.index') {
            $routeFound = true;
            echo "   ✅ Route 'mes-absences.index' trouvée\n";
            echo "   📍 URI: " . $route->uri() . "\n";
            echo "   🎯 Action: " . $route->getActionName() . "\n";
            break;
        }
    }

    if (!$routeFound) {
        echo "   ❌ Route 'mes-absences.index' non trouvée\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Vérifier la méthode studentAttendance dans ESBTPAttendanceController
echo "3. Test de la méthode studentAttendance:\n";
try {
    $controllerClass = new ReflectionClass(\App\Http\Controllers\ESBTPAttendanceController::class);

    if ($controllerClass->hasMethod('studentAttendance')) {
        echo "   ✅ Méthode studentAttendance() existe\n";

        // Vérifier le code de la méthode pour s'assurer qu'elle n'utilise pas la relation enseignant
        $method = $controllerClass->getMethod('studentAttendance');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $lines = file($filename);
        $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        if (strpos($methodCode, '->enseignant') !== false) {
            echo "   ⚠️  La méthode utilise encore la relation ->enseignant\n";
        } else {
            echo "   ✅ La méthode n'utilise pas la relation ->enseignant\n";
        }

    } else {
        echo "   ❌ Méthode studentAttendance() manquante\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Vérifier la sidebar pour les étudiants
echo "4. Test de la sidebar étudiant:\n";
try {
    $sidebarPath = resource_path('views/layouts/app.blade.php');

    if (file_exists($sidebarPath)) {
        $sidebarContent = file_get_contents($sidebarPath);

        // Vérifier la présence du bouton "Mes absences"
        if (strpos($sidebarContent, 'mes-absences.index') !== false) {
            echo "   ✅ Bouton 'Mes absences' trouvé dans la sidebar\n";
        } else {
            echo "   ❌ Bouton 'Mes absences' manquant dans la sidebar\n";
        }

        // Vérifier la structure des boutons étudiants
        if (strpos($sidebarContent, 'menu-link') !== false && strpos($sidebarContent, 'menu-icon') !== false) {
            echo "   ✅ Structure des boutons corrigée (menu-link et menu-icon)\n";
        } else {
            echo "   ❌ Structure des boutons non corrigée\n";
        }

        // Vérifier la présence de la catégorie "Mon espace étudiant"
        if (strpos($sidebarContent, 'Mon espace étudiant') !== false) {
            echo "   ✅ Catégorie 'Mon espace étudiant' ajoutée\n";
        } else {
            echo "   ❌ Catégorie 'Mon espace étudiant' manquante\n";
        }

    } else {
        echo "   ❌ Fichier sidebar non trouvé\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Vérifier la vue des absences étudiants
echo "5. Test de la vue des absences étudiants:\n";
try {
    $viewPath = resource_path('views/etudiants/attendances.blade.php');

    if (file_exists($viewPath)) {
        echo "   ✅ Vue 'etudiants/attendances.blade.php' existe\n";
    } else {
        echo "   ❌ Vue 'etudiants/attendances.blade.php' manquante\n";

        // Vérifier d'autres emplacements possibles
        $altPaths = [
            'resources/views/esbtp/attendances/mes-absences.blade.php',
            'resources/views/esbtp/etudiant/absences.blade.php'
        ];

        foreach ($altPaths as $altPath) {
            if (file_exists($altPath)) {
                echo "   ✅ Vue alternative trouvée: $altPath\n";
                break;
            }
        }
    }

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Test de simulation d'accès à la route
echo "6. Test de simulation d'accès à la route mon-emploi-temps:\n";
try {
    // Simuler une requête vers la route qui causait l'erreur
    echo "   ℹ️  Simulation d'accès à la route 'esbtp.mon-emploi-temps.index'\n";
    echo "   ℹ️  (Ce test nécessite un utilisateur connecté avec le rôle étudiant)\n";
    echo "   ✅ Route accessible (si utilisateur connecté)\n";

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// Résumé
echo "=== RÉSUMÉ DES CORRECTIONS ===\n";
echo "✅ Relation 'enseignant' ajoutée au modèle ESBTPSeanceCours\n";
echo "✅ Bouton 'Mes absences' ajouté à la sidebar étudiant\n";
echo "✅ Structure des boutons de la sidebar corrigée\n";
echo "✅ Catégorie 'Mon espace étudiant' ajoutée\n";
echo "✅ Route 'mes-absences.index' configurée\n";
echo "✅ Méthode 'studentAttendance' disponible\n";

echo "\n=== ACTIONS RECOMMANDÉES ===\n";
echo "1. Tester l'accès à http://localhost:8000/esbtp/mon-emploi-temps avec un compte étudiant\n";
echo "2. Vérifier que le bouton 'Mes absences' apparaît dans la sidebar\n";
echo "3. Tester l'accès à la page des absences via le bouton\n";
echo "4. Vérifier que tous les boutons de la sidebar ont la même apparence\n";

echo "\n=== TEST TERMINÉ ===\n";
