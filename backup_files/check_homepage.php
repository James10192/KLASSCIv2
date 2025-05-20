<?php
/**
 * Script de vérification de la page d'accueil
 * Ce script vérifie si la page d'accueil est accessible et affiche son contenu
 */

echo "Vérification de l'accès à la page d'accueil...\n";

// URL à vérifier
$url = 'http://127.0.0.1:8000';

// Initialisation de cURL
$ch = curl_init();

// Configuration des options cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Désactivation de la vérification SSL (uniquement pour le test local)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

// Exécution de la requête
$response = curl_exec($ch);

// Récupération des informations
$info = curl_getinfo($ch);
$header_size = $info['header_size'];
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Vérification des erreurs
if (curl_errno($ch)) {
    echo "Erreur cURL : " . curl_error($ch) . "\n";
    exit(1);
}

// Affichage des informations de base
echo "Statut HTTP : " . $info['http_code'] . "\n";
echo "Temps de réponse : " . $info['total_time'] . " secondes\n";
echo "Type de contenu : " . $info['content_type'] . "\n";
echo "Taille du contenu : " . $info['size_download'] . " octets\n";

// Fermeture de la session cURL
curl_close($ch);

// Vérification du code de statut HTTP
if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
    echo "La page d'accueil est accessible.\n";
    
    // Vérification du contenu pour déterminer si c'est la bonne page
    if (strpos($body, 'KLASSCI') !== false) {
        echo "Le contenu contient 'KLASSCI', c'est probablement la bonne page.\n";
    } else {
        echo "Le contenu ne contient pas 'KLASSCI', ce n'est peut-être pas la bonne page.\n";
    }
    
    // Écriture du contenu dans un fichier HTML pour inspection
    file_put_contents('homepage_content.html', $body);
    echo "Le contenu de la page a été enregistré dans 'homepage_content.html' pour inspection.\n";
} else {
    echo "La page d'accueil n'est pas accessible (code HTTP " . $info['http_code'] . ").\n";
    echo "En-têtes de réponse :\n" . $header . "\n";
}

echo "Vérification terminée.\n"; 