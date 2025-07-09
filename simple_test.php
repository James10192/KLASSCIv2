<?php
echo "Test simple de connexion...\n";

// Test si cURL est disponible
if (!function_exists('curl_init')) {
    die("cURL n'est pas disponible\n");
}

echo "cURL est disponible\n";

// Test de connexion simple
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "Code HTTP: $httpCode\n";
if ($error) {
    echo "Erreur cURL: $error\n";
} else {
    echo "Connexion réussie\n";
    echo "Taille de réponse: " . strlen($response) . " caractères\n";
}

curl_close($ch);
?>
