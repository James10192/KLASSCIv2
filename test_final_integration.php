<?php

/**
 * Test Final d'Intégration - ESBTP Notifications et Améliorations
 * Ce script teste l'ensemble des améliorations apportées
 */

echo "🚀 TEST FINAL D'INTÉGRATION ESBTP\n";
echo "=================================\n\n";

// Configuration
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
        $testResults[$description] = 'PASS';
        return true;
    } else {
        echo "   ❌ Non accessible\n";
        $testResults[$description] = 'FAIL';
        return false;
    }
}

// Fonction pour vérifier un fichier
function checkFile($path, $description) {
    global $testResults;

    echo "📁 Vérification: $description\n";
    echo "   Fichier: $path\n";

    if (file_exists($path)) {
        echo "   ✅ Présent\n";
        $testResults[$description] = 'PASS';
        return true;
    } else {
        echo "   ❌ Manquant\n";
        $testResults[$description] = 'FAIL';
        return false;
    }
}

// Fonction pour vérifier le contenu d'un fichier
function checkFileContent($path, $searchTerms, $description) {
    global $testResults;

    echo "🔍 Analyse: $description\n";
    echo "   Fichier: $path\n";

    if (!file_exists($path)) {
        echo "   ❌ Fichier non trouvé\n";
        $testResults[$description] = 'FAIL';
        return false;
    }

    $content = file_get_contents($path);
    $foundTerms = 0;
    $totalTerms = count($searchTerms);

    foreach ($searchTerms as $term) {
        if (strpos($content, $term) !== false) {
            $foundTerms++;
            echo "   ✅ '$term' trouvé\n";
        } else {
            echo "   ❌ '$term' manquant\n";
        }
    }

    $percentage = ($foundTerms / $totalTerms) * 100;
    echo "   📊 Score: $foundTerms/$totalTerms ($percentage%)\n";

    if ($percentage >= 80) {
        $testResults[$description] = 'PASS';
        return true;
    } else {
        $testResults[$description] = 'FAIL';
        return false;
    }
}

echo "1️⃣ TEST DES PAGES PRINCIPALES\n";
echo "==============================\n";

// Test des pages principales
$pages = [
    '/esbtp/annonces/create' => 'Page de création d\'annonces',
    '/esbtp/attendances' => 'Page des présences',
    '/dashboard' => 'Dashboard principal',
    '/notifications' => 'Page des notifications'
];

foreach ($pages as $url => $description) {
    testUrl($baseUrl . $url, $description);
    echo "\n";
}

echo "2️⃣ VÉRIFICATION DES FICHIERS CRITIQUES\n";
echo "=======================================\n";

// Test des fichiers critiques
$files = [
    'app/Services/NotificationService.php' => 'Service de notifications',
    'resources/views/esbtp/annonces/create.blade.php' => 'Vue création annonces',
    'app/Http/Controllers/ESBTPAnnonceController.php' => 'Contrôleur annonces',
    'app/Http/Controllers/ESBTPAttendanceController.php' => 'Contrôleur présences',
    'public/css/nextadmin.css' => 'Styles CSS'
];

foreach ($files as $path => $description) {
    checkFile($path, $description);
    echo "\n";
}

echo "3️⃣ ANALYSE DU CONTENU DES FICHIERS\n";
echo "===================================\n";

// Test du service de notifications
$notificationServiceTerms = [
    'notifyNewAnnouncement',
    'notifyAbsenceJustificationSubmitted',
    'notifyAbsenceJustificationApproved',
    'notifyAbsenceJustificationRejected',
    'notifyNewAbsence'
];

checkFileContent(
    'app/Services/NotificationService.php',
    $notificationServiceTerms,
    'Service de notifications'
);
echo "\n";

// Test de la vue création d'annonces
$createAnnonceTerms = [
    'choices.js',
    'choices-multiple',
    'initializeChoices',
    'choicesInstances',
    'defaultChoicesConfig'
];

checkFileContent(
    'resources/views/esbtp/annonces/create.blade.php',
    $createAnnonceTerms,
    'Vue création d\'annonces'
);
echo "\n";

// Test des styles CSS
$cssTerms = [
    'choices__inner',
    'choices__list--dropdown',
    'dropdown-menu',
    'notification-item',
    'glassmorphism'
];

checkFileContent(
    'public/css/nextadmin.css',
    $cssTerms,
    'Styles CSS'
);
echo "\n";

echo "4️⃣ VÉRIFICATION DES CONTRÔLEURS\n";
echo "================================\n";

// Test du contrôleur d'annonces
$annonceControllerTerms = [
    'NotificationService',
    'notifyNewAnnouncement'
];

checkFileContent(
    'app/Http/Controllers/ESBTPAnnonceController.php',
    $annonceControllerTerms,
    'Contrôleur annonces'
);
echo "\n";

// Test du contrôleur de présences
$attendanceControllerTerms = [
    'NotificationService',
    'notifyAbsenceJustificationSubmitted',
    'notifyAbsenceJustificationApproved',
    'notifyAbsenceJustificationRejected'
];

checkFileContent(
    'app/Http/Controllers/ESBTPAttendanceController.php',
    $attendanceControllerTerms,
    'Contrôleur présences'
);
echo "\n";

echo "5️⃣ TEST DE CONFIGURATION JAVASCRIPT\n";
echo "====================================\n";

// Vérifier la configuration JavaScript dans la vue
$jsConfigTerms = [
    'choicesInstances',
    'defaultChoicesConfig',
    'initializeChoices',
    'searchEnabled: true',
    'removeItemButton: true'
];

checkFileContent(
    'resources/views/esbtp/annonces/create.blade.php',
    $jsConfigTerms,
    'Configuration JavaScript'
);
echo "\n";

echo "6️⃣ RÉSUMÉ DES RÉSULTATS\n";
echo "=======================\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($result) {
    return $result === 'PASS';
}));
$failedTests = $totalTests - $passedTests;
$successRate = ($passedTests / $totalTests) * 100;

echo "📊 Statistiques des tests:\n";
echo "   ✅ Réussis: $passedTests/$totalTests\n";
echo "   ❌ Échoués: $failedTests/$totalTests\n";
echo "   📈 Taux de réussite: " . round($successRate, 2) . "%\n\n";

// Afficher les détails des tests échoués
if ($failedTests > 0) {
    echo "❌ Tests échoués:\n";
    foreach ($testResults as $test => $result) {
        if ($result === 'FAIL') {
            echo "   - $test\n";
        }
    }
    echo "\n";
}

echo "7️⃣ INSTRUCTIONS DE TEST MANUEL\n";
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

echo "8️⃣ COMMANDES UTILES\n";
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

echo "9️⃣ PROCHAINES ÉTAPES\n";
echo "=====================\n";

if ($successRate >= 90) {
    echo "🎉 Excellent! Toutes les améliorations sont en place.\n";
    echo "✨ L'application est prête pour les tests utilisateur.\n\n";

    echo "📋 Actions recommandées:\n";
    echo "1. Effectuer les tests manuels\n";
    echo "2. Tester avec différents rôles d'utilisateur\n";
    echo "3. Vérifier les notifications en temps réel\n";
    echo "4. Valider les performances\n";
    echo "5. Tester la responsivité sur différents appareils\n";
} elseif ($successRate >= 70) {
    echo "⚠️ Bon progrès, quelques ajustements nécessaires.\n";
    echo "🔧 Corrigez les tests échoués identifiés ci-dessus.\n\n";

    echo "📋 Actions prioritaires:\n";
    echo "1. Corriger les tests échoués\n";
    echo "2. Vérifier les fichiers manquants\n";
    echo "3. Relancer ce test après corrections\n";
} else {
    echo "🚨 Attention! Plusieurs problèmes détectés.\n";
    echo "🛠️ Révision complète nécessaire.\n\n";

    echo "📋 Actions urgentes:\n";
    echo "1. Vérifier l'installation des dépendances\n";
    echo "2. Corriger les erreurs de configuration\n";
    echo "3. Relancer l'installation si nécessaire\n";
}

echo "\n💡 Conseils:\n";
echo "- Utilisez les outils de développement du navigateur\n";
echo "- Vérifiez la console JavaScript pour les erreurs\n";
echo "- Testez avec différents rôles d'utilisateur\n";
echo "- Vérifiez les logs Laravel pour les erreurs backend\n";

echo "\n🎉 Test terminé!\n";
echo "================\n";

// Retourner le code de sortie approprié
exit($successRate >= 80 ? 0 : 1);
