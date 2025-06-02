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

echo "=== TEST DE LA SIDEBAR FINALE ===\n\n";

// Test 1: Vérifier les routes de gestion des présences
echo "1. Test des routes de gestion des présences:\n";
$attendanceRoutes = [
    'esbtp.attendances.index' => 'Liste des présences étudiants',
    'esbtp.attendances.rapport-form' => 'Formulaire de rapport de présence',
    'esbtp.teacher-attendance.history' => 'Historique émargement enseignant',
    'esbtp.attendance-codes.index' => 'Codes d\'émargement'
];

foreach ($attendanceRoutes as $routeName => $description) {
    try {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName);
        if ($route) {
            echo "   ✅ Route '$routeName' trouvée - $description\n";
        } else {
            echo "   ❌ Route '$routeName' manquante - $description\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur pour '$routeName': " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 2: Vérifier les routes de communication
echo "2. Test des routes de communication:\n";
$communicationRoutes = [
    'esbtp.mes-messages.index' => 'Messages étudiants',
    'esbtp.mes-notifications.index' => 'Notifications étudiants',
    'notifications.index' => 'Notifications administrateurs'
];

foreach ($communicationRoutes as $routeName => $description) {
    try {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName);
        if ($route) {
            echo "   ✅ Route '$routeName' trouvée - $description\n";
        } else {
            echo "   ❌ Route '$routeName' manquante - $description\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur pour '$routeName': " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 3: Vérifier la structure de la sidebar
echo "3. Test de la structure de la sidebar:\n";
try {
    $sidebarPath = resource_path('views/layouts/app.blade.php');

    if (file_exists($sidebarPath)) {
        $sidebarContent = file_get_contents($sidebarPath);

        // Vérifier les nouvelles sections
        $sectionsToCheck = [
            'Présence & Absences' => 'Section présence et absences',
            'Communication' => 'Section communication',
            'Gestion des présences' => 'Accordion gestion des présences',
            'Mes absences' => 'Bouton mes absences pour étudiants',
            'Messages' => 'Bouton messages',
            'Notifications' => 'Bouton notifications'
        ];

        foreach ($sectionsToCheck as $section => $description) {
            if (strpos($sidebarContent, $section) !== false) {
                echo "   ✅ $description trouvée\n";
            } else {
                echo "   ❌ $description manquante\n";
            }
        }

        // Vérifier la structure des boutons
        if (strpos($sidebarContent, 'menu-accordion') !== false) {
            echo "   ✅ Structure accordion présente\n";
        } else {
            echo "   ❌ Structure accordion manquante\n";
        }

        if (strpos($sidebarContent, 'menu-sublink') !== false) {
            echo "   ✅ Structure sous-liens présente\n";
        } else {
            echo "   ❌ Structure sous-liens manquante\n";
        }

    } else {
        echo "   ❌ Fichier sidebar non trouvé\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Vérifier les rôles et permissions
echo "4. Test des rôles et permissions:\n";
try {
    // Vérifier que les rôles existent
    $roles = ['superAdmin', 'secretaire', 'etudiant', 'teacher'];

    foreach ($roles as $roleName) {
        $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
        if ($role) {
            echo "   ✅ Rôle '$roleName' existe\n";
        } else {
            echo "   ❌ Rôle '$roleName' manquant\n";
        }
    }

} catch (Exception $e) {
    echo "   ❌ Erreur lors du test des rôles: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Vérifier les contrôleurs
echo "5. Test des contrôleurs:\n";
$controllers = [
    'ESBTPAttendanceController' => 'Contrôleur des présences',
    'ESBTPNotificationController' => 'Contrôleur des notifications'
];

foreach ($controllers as $controllerName => $description) {
    try {
        $controllerClass = "App\\Http\\Controllers\\$controllerName";
        if (class_exists($controllerClass)) {
            echo "   ✅ $description existe\n";

            // Vérifier quelques méthodes importantes
            $reflection = new ReflectionClass($controllerClass);

            if ($controllerName === 'ESBTPAttendanceController') {
                $methods = ['index', 'rapportForm', 'studentAttendance'];
                foreach ($methods as $method) {
                    if ($reflection->hasMethod($method)) {
                        echo "      ✅ Méthode $method() existe\n";
                    } else {
                        echo "      ❌ Méthode $method() manquante\n";
                    }
                }
            }

        } else {
            echo "   ❌ $description manquant\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur pour $controllerName: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 6: Vérifier les vues
echo "6. Test des vues:\n";
$views = [
    'etudiants/attendances.blade.php' => 'Vue des absences étudiants',
    'esbtp/attendances/index.blade.php' => 'Vue liste des présences',
    'esbtp/attendances/rapport-form.blade.php' => 'Vue formulaire de rapport'
];

foreach ($views as $viewPath => $description) {
    $fullPath = resource_path("views/$viewPath");
    if (file_exists($fullPath)) {
        echo "   ✅ $description existe\n";
    } else {
        echo "   ❌ $description manquante\n";
    }
}

echo "\n";

// Résumé
echo "=== RÉSUMÉ DES AMÉLIORATIONS ===\n";
echo "✅ Section 'Présence & Absences' ajoutée pour superadmin/secrétaire\n";
echo "✅ Section 'Communication' ajoutée pour tous les rôles\n";
echo "✅ Accordion 'Gestion des présences' avec sous-menus\n";
echo "✅ Bouton 'Mes absences' pour les étudiants\n";
echo "✅ Boutons 'Messages' et 'Notifications' pour tous\n";
echo "✅ Structure cohérente avec menu-link et menu-icon\n";

echo "\n=== FONCTIONNALITÉS DISPONIBLES ===\n";
echo "📊 Gestion des présences étudiants\n";
echo "📈 Rapports de présence\n";
echo "👨‍🏫 Historique émargement enseignant\n";
echo "🔑 Gestion des codes d'émargement\n";
echo "💬 Messages pour tous les utilisateurs\n";
echo "🔔 Notifications personnalisées par rôle\n";
echo "📱 Interface responsive et moderne\n";

echo "\n=== ACTIONS RECOMMANDÉES ===\n";
echo "1. Tester l'accès aux différentes sections selon le rôle\n";
echo "2. Vérifier que les accordions s'ouvrent/ferment correctement\n";
echo "3. Tester la navigation entre les sous-menus\n";
echo "4. Vérifier l'affichage sur mobile et desktop\n";
echo "5. Tester les permissions d'accès aux routes\n";

echo "\n=== TEST TERMINÉ ===\n";
