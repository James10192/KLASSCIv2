<?php
// Test de l'appel AJAX réel avec cURL

echo "=== TEST APPEL AJAX RÉEL ===\n";

$url = 'http://127.0.0.1:8000/esbtp/planning-general/get-matieres-configuration';

// Données à envoyer (BTS1 BATIMENT - Première année - Année 6)
$data = [
    'filiere_id' => 2,
    'niveau_id' => 1,  
    'annee_id' => 6
];

echo "URL: $url\n";
echo "Données: " . json_encode($data) . "\n\n";

// Configuration cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest'  // Simuler AJAX
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== RÉPONSE ===\n";
echo "Code HTTP: $httpCode\n";

if ($error) {
    echo "❌ Erreur cURL: $error\n";
    exit;
}

if ($httpCode !== 200) {
    echo "❌ Erreur HTTP $httpCode\n";
    echo "Réponse: $response\n";
    exit;
}

// Décoder la réponse JSON
$responseData = json_decode($response, true);

if (!$responseData) {
    echo "❌ Erreur JSON decode\n";
    echo "Réponse brute: $response\n";
    exit;
}

echo "✅ Réponse JSON décodée avec succès\n";
echo "Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";

if (!$responseData['success']) {
    echo "❌ Erreur API: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    exit;
}

// Analyser le HTML retourné
$html = $responseData['html'] ?? '';
echo "Longueur HTML: " . strlen($html) . " caractères\n\n";

// Chercher les champs avec valeurs pré-remplies
echo "=== ANALYSE DES CHAMPS PRÉ-REMPLIS ===\n";

$patterns = [
    '/name="volumes\[(\d+)\]"[^>]*value="(\d+)"/i',
    '/<input[^>]*name="volumes\[(\d+)\]"[^>]*value="([^"]*)"[^>]*>/i'
];

$foundFields = [];

foreach ($patterns as $pattern) {
    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $matiereId = $match[1];
            $value = $match[2];
            if ($value !== '0') {
                $foundFields[$matiereId] = $value;
            }
        }
    }
}

if (empty($foundFields)) {
    echo "❌ AUCUN champ pré-rempli trouvé dans le HTML!\n";
    echo "Ceci explique pourquoi vous voyez tout à 0h.\n\n";
    
    // Montrer un extrait du HTML pour déboguer
    echo "=== EXTRAIT HTML (500 premiers caractères) ===\n";
    echo substr($html, 0, 500) . "...\n";
    
} else {
    echo "✅ Champs pré-remplis trouvés:\n";
    foreach ($foundFields as $matiereId => $value) {
        echo "- Matière ID $matiereId: {$value}h\n";
    }
}