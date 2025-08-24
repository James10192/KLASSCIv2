<?php
// Test avec GET au lieu de POST

echo "=== TEST APPEL AJAX GET ===\n";

$baseUrl = 'http://127.0.0.1:8000/esbtp/planning-general/get-matieres-configuration';
$params = [
    'filiere_id' => 2,
    'niveau_id' => 1,
    'annee_id' => 6
];

$url = $baseUrl . '?' . http_build_query($params);
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";

if ($error) {
    echo "❌ Erreur cURL: $error\n";
    exit;
}

if ($httpCode !== 200) {
    echo "❌ Erreur HTTP $httpCode\n";
    echo "Réponse: " . substr($response, 0, 500) . "\n";
    exit;
}

$responseData = json_decode($response, true);

if (!$responseData) {
    echo "❌ Erreur JSON decode\n";
    echo "Réponse brute (500 chars): " . substr($response, 0, 500) . "\n";
    exit;
}

echo "✅ Réponse JSON décodée\n";
echo "Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";

if (!$responseData['success']) {
    echo "❌ Erreur: " . ($responseData['message'] ?? 'Unknown') . "\n";
    exit;
}

$html = $responseData['html'] ?? '';
echo "HTML length: " . strlen($html) . " chars\n\n";

// Chercher les valeurs pré-remplies
echo "=== RECHERCHE CHAMPS PRÉ-REMPLIS ===\n";

// Pattern pour trouver les inputs avec values
if (preg_match_all('/name="volumes\[(\d+)\]"[^>]*value="([^"]*)"/', $html, $matches, PREG_SET_ORDER)) {
    $preremplis = 0;
    $vides = 0;
    
    foreach ($matches as $match) {
        $matiereId = $match[1];
        $value = $match[2];
        
        if ($value !== '0' && $value !== '') {
            echo "✅ Matière ID $matiereId: {$value}h (PRÉ-REMPLI)\n";
            $preremplis++;
        } else {
            echo "❌ Matière ID $matiereId: {$value}h (VIDE)\n";
            $vides++;
        }
    }
    
    echo "\n📊 RÉSUMÉ:\n";
    echo "- Champs pré-remplis: $preremplis\n";
    echo "- Champs vides: $vides\n";
    
    if ($preremplis > 0) {
        echo "\n🎯 CONCLUSION: Le modal fonctionne ! Il y a $preremplis matières avec volumes.\n";
        echo "Si vous ne les voyez pas à l'écran, vérifiez:\n";
        echo "1. Que vous regardez la bonne combinaison (BTS1 BATIMENT - Première année)\n";
        echo "2. Que l'année sélectionnée est bien 6\n";
        echo "3. Cache du navigateur\n";
    } else {
        echo "\n⚠️ PROBLÈME: Aucun champ pré-rempli trouvé.\n";
    }
    
} else {
    echo "❌ Aucun champ de volume trouvé dans le HTML\n";
    echo "\nExtrait HTML pour déboguer:\n";
    echo substr($html, 0, 1000) . "\n";
}