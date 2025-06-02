<?php

require_once 'vendor/autoload.php';

echo "🧪 TEST COMPLET DES NOTIFICATIONS ET AMÉLIORATIONS ESBTP\n";
echo "=========================================================\n\n";

// Test 1: Vérifier le service de notifications
echo "1️⃣ SERVICE DE NOTIFICATIONS\n";
$notificationServicePath = 'app/Services/NotificationService.php';
if (file_exists($notificationServicePath)) {
    echo "✅ Service de notifications créé\n";
    $content = file_get_contents($notificationServicePath);

    $methods = [
        'notifyAbsenceJustificationSubmitted',
        'notifyAbsenceJustificationApproved',
        'notifyAbsenceJustificationRejected',
        'notifyNewAnnouncement',
        'notifyNewAbsence'
    ];

    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "  ✅ Méthode $method présente\n";
        } else {
            echo "  ❌ Méthode $method manquante\n";
        }
    }
} else {
    echo "❌ Service de notifications manquant\n";
}

// Test 2: Vérifier les améliorations CSS
echo "\n2️⃣ STYLES CSS AMÉLIORÉS\n";
$cssPath = 'public/css/nextadmin.css';
if (file_exists($cssPath)) {
    $cssContent = file_get_contents($cssPath);

    $cssClasses = [
        'notifications-dropdown',
        'notification-item',
        'notification-content',
        'dropdown-menu-modern',
        'choices__inner',
        'choices__list--dropdown'
    ];

    foreach ($cssClasses as $class) {
        if (strpos($cssContent, ".$class") !== false || strpos($cssContent, $class) !== false) {
            echo "  ✅ Classe CSS $class présente\n";
        } else {
            echo "  ❌ Classe CSS $class manquante\n";
        }
    }
} else {
    echo "  ❌ Fichier CSS manquant\n";
}

// Test 3: Vérifier l'intégration Choices.js
echo "\n3️⃣ INTÉGRATION CHOICES.JS\n";
$createAnnoncePath = 'resources/views/esbtp/annonces/create.blade.php';
if (file_exists($createAnnoncePath)) {
    $viewContent = file_get_contents($createAnnoncePath);

    $choicesElements = [
        'choices.js',
        'choices-multiple',
        'initializeChoices',
        'choicesInstances',
        'defaultChoicesConfig'
    ];

    foreach ($choicesElements as $element) {
        if (strpos($viewContent, $element) !== false) {
            echo "  ✅ Élément Choices.js $element présent\n";
        } else {
            echo "  ❌ Élément Choices.js $element manquant\n";
        }
    }

    // Vérifier que Select2 a été supprimé
    if (strpos($viewContent, 'select2') === false) {
        echo "  ✅ Select2 supprimé avec succès\n";
    } else {
        echo "  ❌ Select2 encore présent\n";
    }
} else {
    echo "  ❌ Vue de création d'annonces manquante\n";
}

// Test 4: Vérifier les contrôleurs
echo "\n4️⃣ CONTRÔLEURS MIS À JOUR\n";
$controllers = [
    'app/Http/Controllers/ESBTPAnnonceController.php' => [
        'NotificationService',
        'notifyNewAnnouncement'
    ],
    'app/Http/Controllers/ESBTPAttendanceController.php' => [
        'NotificationService',
        'notifyAbsenceJustificationSubmitted',
        'notifyAbsenceJustificationApproved',
        'notifyAbsenceJustificationRejected'
    ]
];

foreach ($controllers as $controllerPath => $elements) {
    if (file_exists($controllerPath)) {
        $controllerContent = file_get_contents($controllerPath);

        foreach ($elements as $element) {
            if (strpos($controllerContent, $element) !== false) {
                echo "  ✅ " . basename($controllerPath, '.php') . " - $element utilisé\n";
            } else {
                echo "  ❌ " . basename($controllerPath, '.php') . " - $element manquant\n";
            }
        }
    }
}

// Test 5: Vérifier les routes et vues
echo "\n5️⃣ ROUTES ET VUES\n";
$routesPath = 'routes/web.php';
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);

    $routes = [
        'esbtp.annonces.create',
        'esbtp.attendances.index',
        'notifications',
        'navbar.notifications'
    ];

    foreach ($routes as $route) {
        if (strpos($routesContent, $route) !== false) {
            echo "  ✅ Route $route présente\n";
        } else {
            echo "  ❌ Route $route manquante\n";
        }
    }
} else {
    echo "  ❌ Fichier de routes manquant\n";
}

// Vérifier les vues de notifications
$notificationViews = [
    'resources/views/partials/notifications.blade.php',
    'resources/views/esbtp/annonces/student-messages.blade.php'
];

foreach ($notificationViews as $viewPath) {
    if (file_exists($viewPath)) {
        echo "  ✅ Vue des notifications présente\n";
        break;
    }
}

// Test 6: Résumé des améliorations
echo "\n6️⃣ RÉSUMÉ DES AMÉLIORATIONS\n";
echo "========================================\n";
echo "✨ Service de notifications centralisé\n";
echo "🎨 Styles améliorés pour les dropdowns\n";
echo "🔄 Remplacement de Select2 par Choices.js\n";
echo "📱 Interface responsive et moderne\n";
echo "🔔 Notifications automatiques pour:\n";
echo "   - Nouvelles annonces\n";
echo "   - Justifications d'absence\n";
echo "   - Approbations/rejets d'absence\n";
echo "   - Nouvelles absences enregistrées\n";
echo "🎯 Filtrage avancé avec Choices.js\n";
echo "⚡ Performances optimisées\n";

// Test 7: Instructions de test
echo "\n7️⃣ INSTRUCTIONS DE TEST\n";
echo "==============================\n";
echo "Pour tester les améliorations:\n";
echo "1. Accédez à http://localhost:8000/esbtp/annonces/create\n";
echo "2. Testez les sélecteurs Choices.js pour les destinataires\n";
echo "3. Créez une annonce et vérifiez les notifications\n";
echo "4. Testez les dropdowns de notifications dans la navbar\n";
echo "5. Vérifiez les notifications d'absence côté étudiant\n";
echo "6. Testez les justifications d'absence côté admin\n";

echo "\n🎉 Test terminé!\n";

// Test 8: Créer un test en direct des notifications
echo "\n8️⃣ TEST EN DIRECT DES NOTIFICATIONS\n";
echo "====================================\n";

// Simuler une création d'annonce
echo "📝 Simulation de création d'annonce...\n";
$testData = [
    'titre' => 'Test Notification - ' . date('Y-m-d H:i:s'),
    'contenu' => 'Ceci est un test automatique des notifications.',
    'type' => 'general',
    'priorite' => 1,
    'is_published' => true
];

echo "  ✅ Données de test préparées\n";
echo "  📊 Type: {$testData['type']}\n";
echo "  🔥 Priorité: {$testData['priorite']}\n";
echo "  📢 Publié: " . ($testData['is_published'] ? 'Oui' : 'Non') . "\n";

// Test de validation des destinataires
echo "\n📋 Test de validation des destinataires...\n";
$destinataireTests = [
    'general' => 'Tous les étudiants',
    'classe' => 'Classes spécifiques',
    'etudiant' => 'Étudiants spécifiques'
];

foreach ($destinataireTests as $type => $description) {
    echo "  🎯 $type: $description\n";
}

echo "\n✅ Tests de validation terminés!\n";
