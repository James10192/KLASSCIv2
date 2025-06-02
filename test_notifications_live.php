<?php

/**
 * Script de test en direct pour les notifications et destinataires ESBTP
 * Ce script teste les fonctionnalités de notifications et la sélection des destinataires
 */

echo "🔔 TEST EN DIRECT DES NOTIFICATIONS ESBTP\n";
echo "==========================================\n\n";

// Configuration de base
$baseUrl = 'http://localhost:8000';
$testResults = [];

// Fonction pour tester une URL
function testUrl($url, $description) {
    global $testResults;

    echo "🌐 Test: $description\n";
    echo "   URL: $url\n";

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response !== false) {
        echo "   ✅ Accessible\n";
        $testResults[$description] = 'SUCCESS';
        return $response;
    } else {
        echo "   ❌ Non accessible\n";
        $testResults[$description] = 'FAILED';
        return false;
    }
}

// Test 1: Vérifier les pages principales
echo "1️⃣ TEST DES PAGES PRINCIPALES\n";
echo "==============================\n";

$pages = [
    '/esbtp/annonces/create' => 'Page de création d\'annonces',
    '/esbtp/attendances' => 'Page des présences',
    '/dashboard' => 'Dashboard principal',
    '/notifications' => 'Page des notifications'
];

foreach ($pages as $path => $description) {
    testUrl($baseUrl . $path, $description);
    echo "\n";
}

// Test 2: Vérifier les fichiers critiques
echo "2️⃣ VÉRIFICATION DES FICHIERS CRITIQUES\n";
echo "=======================================\n";

$criticalFiles = [
    'app/Services/NotificationService.php' => 'Service de notifications',
    'resources/views/esbtp/annonces/create.blade.php' => 'Vue création annonces',
    'app/Http/Controllers/ESBTPAnnonceController.php' => 'Contrôleur annonces',
    'app/Http/Controllers/ESBTPAttendanceController.php' => 'Contrôleur présences',
    'public/css/nextadmin.css' => 'Styles CSS'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description: Présent\n";
        $testResults[$description] = 'SUCCESS';
    } else {
        echo "❌ $description: Manquant\n";
        $testResults[$description] = 'FAILED';
    }
}

echo "\n";

// Test 3: Analyser le contenu des fichiers
echo "3️⃣ ANALYSE DU CONTENU DES FICHIERS\n";
echo "===================================\n";

// Analyser le service de notifications
if (file_exists('app/Services/NotificationService.php')) {
    $serviceContent = file_get_contents('app/Services/NotificationService.php');

    $requiredMethods = [
        'notifyNewAnnouncement' => 'Notification nouvelle annonce',
        'notifyAbsenceJustificationSubmitted' => 'Notification justification soumise',
        'notifyAbsenceJustificationApproved' => 'Notification justification approuvée',
        'notifyAbsenceJustificationRejected' => 'Notification justification rejetée',
        'notifyNewAbsence' => 'Notification nouvelle absence'
    ];

    echo "📋 Service de notifications:\n";
    foreach ($requiredMethods as $method => $description) {
        if (strpos($serviceContent, "function $method") !== false) {
            echo "   ✅ $description\n";
        } else {
            echo "   ❌ $description\n";
        }
    }
    echo "\n";
}

// Analyser la vue de création d'annonces
if (file_exists('resources/views/esbtp/annonces/create.blade.php')) {
    $viewContent = file_get_contents('resources/views/esbtp/annonces/create.blade.php');

    echo "📋 Vue création d'annonces:\n";

    // Vérifier Choices.js
    if (strpos($viewContent, 'choices.js') !== false) {
        echo "   ✅ Choices.js intégré\n";
    } else {
        echo "   ❌ Choices.js manquant\n";
    }

    // Vérifier les sélecteurs de destinataires
    $selectors = [
        'classes' => 'Sélecteur de classes',
        'etudiants' => 'Sélecteur d\'étudiants',
        'type_globale' => 'Option globale',
        'type_classe' => 'Option par classe',
        'type_etudiant' => 'Option par étudiant'
    ];

    foreach ($selectors as $selector => $description) {
        if (strpos($viewContent, $selector) !== false) {
            echo "   ✅ $description\n";
        } else {
            echo "   ❌ $description\n";
        }
    }

    // Vérifier que Select2 a été supprimé
    if (strpos($viewContent, 'select2') === false) {
        echo "   ✅ Select2 supprimé\n";
    } else {
        echo "   ⚠️ Select2 encore présent\n";
    }

    echo "\n";
}

// Test 4: Vérifier les styles CSS
echo "4️⃣ VÉRIFICATION DES STYLES CSS\n";
echo "===============================\n";

if (file_exists('public/css/nextadmin.css')) {
    $cssContent = file_get_contents('public/css/nextadmin.css');

    $cssFeatures = [
        'dropdown-menu' => 'Styles dropdown de base',
        'notifications-dropdown' => 'Dropdown notifications',
        'choices__inner' => 'Styles Choices.js',
        'notification-item' => 'Items de notification',
        'glassmorphism' => 'Effet glassmorphism',
        'backdrop-filter' => 'Filtres backdrop'
    ];

    echo "🎨 Styles CSS:\n";
    foreach ($cssFeatures as $feature => $description) {
        if (strpos($cssContent, $feature) !== false) {
            echo "   ✅ $description\n";
        } else {
            echo "   ❌ $description\n";
        }
    }
    echo "\n";
}

// Test 5: Simuler des données de test
echo "5️⃣ SIMULATION DE DONNÉES DE TEST\n";
echo "=================================\n";

// Simuler une annonce
$testAnnonce = [
    'titre' => 'Test Notification Automatique',
    'contenu' => 'Ceci est un test automatique du système de notifications.',
    'type' => 'general',
    'priorite' => 1,
    'is_published' => true,
    'date_publication' => date('Y-m-d H:i:s'),
    'date_expiration' => date('Y-m-d H:i:s', strtotime('+1 month'))
];

echo "📝 Données d'annonce de test:\n";
foreach ($testAnnonce as $key => $value) {
    echo "   $key: $value\n";
}

// Simuler des destinataires
$testDestinataires = [
    'general' => [
        'description' => 'Tous les étudiants',
        'count' => 'Tous'
    ],
    'classe' => [
        'description' => 'Classes spécifiques',
        'count' => '3 classes sélectionnées'
    ],
    'etudiant' => [
        'description' => 'Étudiants spécifiques',
        'count' => '15 étudiants sélectionnés'
    ]
];

echo "\n👥 Types de destinataires:\n";
foreach ($testDestinataires as $type => $info) {
    echo "   $type: {$info['description']} ({$info['count']})\n";
}

echo "\n";

// Test 6: Vérifier la configuration JavaScript
echo "6️⃣ CONFIGURATION JAVASCRIPT\n";
echo "============================\n";

if (file_exists('resources/views/esbtp/annonces/create.blade.php')) {
    $viewContent = file_get_contents('resources/views/esbtp/annonces/create.blade.php');

    $jsFeatures = [
        'choicesInstances' => 'Instances Choices.js',
        'defaultChoicesConfig' => 'Configuration par défaut',
        'initializeChoices' => 'Fonction d\'initialisation',
        'multipleSelectConfig' => 'Configuration sélection multiple',
        'filterClasses' => 'Filtrage des classes',
        'filterEtudiants' => 'Filtrage des étudiants'
    ];

    echo "⚙️ Configuration JavaScript:\n";
    foreach ($jsFeatures as $feature => $description) {
        if (strpos($viewContent, $feature) !== false) {
            echo "   ✅ $description\n";
        } else {
            echo "   ❌ $description\n";
        }
    }
    echo "\n";
}

// Test 7: Résumé des résultats
echo "7️⃣ RÉSUMÉ DES RÉSULTATS\n";
echo "=======================\n";

$successCount = array_count_values($testResults)['SUCCESS'] ?? 0;
$failedCount = array_count_values($testResults)['FAILED'] ?? 0;
$totalTests = count($testResults);

echo "📊 Statistiques des tests:\n";
echo "   ✅ Réussis: $successCount/$totalTests\n";
echo "   ❌ Échoués: $failedCount/$totalTests\n";
echo "   📈 Taux de réussite: " . round(($successCount / $totalTests) * 100, 1) . "%\n\n";

if ($failedCount > 0) {
    echo "⚠️ Tests échoués:\n";
    foreach ($testResults as $test => $result) {
        if ($result === 'FAILED') {
            echo "   - $test\n";
        }
    }
    echo "\n";
}

// Test 8: Instructions de test manuel
echo "8️⃣ INSTRUCTIONS DE TEST MANUEL\n";
echo "===============================\n";

echo "🔧 Pour tester manuellement:\n\n";

echo "1. 📝 Test de création d'annonce:\n";
echo "   - Accédez à: $baseUrl/esbtp/annonces/create\n";
echo "   - Testez les 3 types de destinataires\n";
echo "   - Vérifiez les sélecteurs Choices.js\n";
echo "   - Créez une annonce et vérifiez les notifications\n\n";

echo "2. 🔔 Test des notifications:\n";
echo "   - Connectez-vous en tant qu'étudiant\n";
echo "   - Vérifiez les notifications dans la navbar\n";
echo "   - Testez le dropdown des notifications\n";
echo "   - Marquez des notifications comme lues\n\n";

echo "3. 📋 Test des présences:\n";
echo "   - Accédez à la gestion des présences\n";
echo "   - Soumettez une justification d'absence\n";
echo "   - Vérifiez les notifications côté admin\n";
echo "   - Approuvez/rejetez une justification\n\n";

echo "4. 🎨 Test des styles:\n";
echo "   - Vérifiez l'apparence des dropdowns\n";
echo "   - Testez les effets de hover\n";
echo "   - Vérifiez la responsivité mobile\n";
echo "   - Testez les animations\n\n";

// Test 9: Commandes utiles
echo "9️⃣ COMMANDES UTILES\n";
echo "====================\n";

echo "🛠️ Commandes de développement:\n\n";

echo "# Vider le cache\n";
echo "php artisan cache:clear\n";
echo "php artisan config:clear\n";
echo "php artisan view:clear\n\n";

echo "# Régénérer les assets\n";
echo "npm run dev\n";
echo "# ou\n";
echo "npm run build\n\n";

echo "# Vérifier les logs\n";
echo "tail -f storage/logs/laravel.log\n\n";

echo "# Tester les notifications en console\n";
echo "php artisan tinker\n";
echo "# Puis dans tinker:\n";
echo "# \$service = app(App\\Services\\NotificationService::class);\n";
echo "# \$user = App\\Models\\User::first();\n";
echo "# \$service->createNotification(\$user, 'Test', 'Message de test');\n\n";

echo "🎉 Test terminé!\n";
echo "================\n\n";

echo "📋 Prochaines étapes recommandées:\n";
echo "1. Corriger les tests échoués identifiés\n";
echo "2. Effectuer les tests manuels\n";
echo "3. Vérifier les notifications en temps réel\n";
echo "4. Tester la responsivité sur différents appareils\n";
echo "5. Valider les performances\n\n";

echo "💡 Conseils:\n";
echo "- Utilisez les outils de développement du navigateur\n";
echo "- Vérifiez la console JavaScript pour les erreurs\n";
echo "- Testez avec différents rôles d'utilisateur\n";
echo "- Vérifiez les logs Laravel pour les erreurs backend\n";
