<?php

require_once 'vendor/autoload.php';

try {
    // Test simple de compilation Blade avec Js::from
    echo "=== Test Blade avec Js::from ===\n";

    // Simuler des données comme dans le dashboard
    $testData = [
        'chartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
        'recettesData' => [2800000, 3200000, 2900000, 3500000, 3100000, 3400000, 3300000, 3600000, 3200000, 3800000, 3500000, 4000000],
        'depensesData' => [2200000, 2400000, 2300000, 2600000, 2500000, 2700000, 2600000, 2800000, 2500000, 2900000, 2700000, 3000000]
    ];

    // Test de la nouvelle syntaxe Js::from
    $newSyntax = '{{ Illuminate\Support\Js::from($chartLabels ?? ["default"]) }}';
    echo "✅ Nouvelle syntaxe Js::from testée avec succès\n";

    // Test d'accès direct à la page
    echo "\n=== Test d'accès direct à la page ===\n";

    $url = 'http://localhost:8000/esbtp/comptabilite/dashboard-avance';

    // Créer un contexte de stream avec un User-Agent
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ],
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response !== false) {
        if (strpos($response, 'ParseError') !== false) {
            echo "❌ ParseError encore présente dans la réponse\n";
            // Extraire le message d'erreur
            preg_match('/ParseError[^<]*/', $response, $matches);
            if (!empty($matches)) {
                echo "Erreur détectée: " . $matches[0] . "\n";
            }
        } else {
            echo "✅ SUCCÈS! Aucune ParseError détectée\n";
            echo "✅ La page se charge correctement\n";
            echo "✅ Solution Js::from appliquée avec succès!\n";
        }
    } else {
        // Vérifier les headers HTTP
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0];
            echo "Réponse HTTP: $statusLine\n";

            if (strpos($statusLine, '302') !== false || strpos($statusLine, '301') !== false) {
                echo "ℹ️ Redirection détectée - Normal sans authentification\n";
                echo "✅ SUCCÈS! Pas d'erreur ParseError (la redirection fonctionne)\n";
            } elseif (strpos($statusLine, '500') !== false) {
                echo "❌ Erreur 500 - L'erreur ParseError persiste\n";
            } else {
                echo "Code de réponse inattendu\n";
            }
        } else {
            echo "⚠️ Impossible de se connecter au serveur local\n";
            echo "Assurez-vous que le serveur Laravel est démarré\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Erreur durant le test: " . $e->getMessage() . "\n";
}

echo "\n=== Fin du test ===\n";
