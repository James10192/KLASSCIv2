<?php
/**
 * Script de correction des URLs dans les routes Laravel
 * 
 * Ce script vérifie et corrige les problèmes d'URL dans les routes Laravel.
 * Il s'assure que les URLs sont correctement configurées pour fonctionner
 * à la fois avec le serveur de développement et en production.
 */

// Fonction pour afficher un message
function displayMessage($message, $type = 'info') {
    $colors = [
        'info' => 'blue',
        'success' => 'green',
        'warning' => 'orange',
        'error' => 'red'
    ];
    
    $color = isset($colors[$type]) ? $colors[$type] : 'black';
    echo "<p style='color: {$color};'>{$message}</p>";
}

// Fonction pour vérifier si un fichier est accessible en écriture
function isWritable($file) {
    return file_exists($file) && is_writable($file);
}

echo "<h1>Correction des URLs dans les routes Laravel</h1>";

// Vérifier si le fichier .env est accessible
$envFile = __DIR__ . '/.env';
if (!isWritable($envFile)) {
    displayMessage("Le fichier .env n'est pas accessible en écriture!", 'error');
    exit;
}

// Lire le contenu du fichier .env
$envContent = file_get_contents($envFile);

// Vérifier l'URL de l'application
preg_match('/APP_URL=(.*)/', $envContent, $matches);
$appUrl = isset($matches[1]) ? $matches[1] : '';

displayMessage("URL actuelle de l'application: " . $appUrl);

// Déterminer l'URL correcte
$correctUrl = 'http://127.0.0.1:8000';
$productionUrl = 'http://localhost/smart_school_new/public';

// Demander à l'utilisateur quelle URL utiliser
echo "<form method='post'>";
echo "<h2>Choisissez l'URL à utiliser:</h2>";
echo "<input type='radio' name='url_choice' value='dev' id='dev_url' checked>";
echo "<label for='dev_url'>{$correctUrl} (Serveur de développement Laravel)</label><br>";
echo "<input type='radio' name='url_choice' value='prod' id='prod_url'>";
echo "<label for='prod_url'>{$productionUrl} (Serveur Apache/WAMP)</label><br>";
echo "<input type='submit' value='Appliquer'>";
echo "</form>";

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url_choice'])) {
    $urlChoice = $_POST['url_choice'];
    $newUrl = ($urlChoice === 'dev') ? $correctUrl : $productionUrl;
    
    // Mettre à jour le fichier .env
    $newEnvContent = preg_replace('/APP_URL=.*/', "APP_URL={$newUrl}", $envContent);
    
    if (file_put_contents($envFile, $newEnvContent)) {
        displayMessage("L'URL de l'application a été mise à jour avec succès: {$newUrl}", 'success');
    } else {
        displayMessage("Erreur lors de la mise à jour de l'URL de l'application!", 'error');
    }
    
    // Vérifier les routes Laravel
    $routesDir = __DIR__ . '/routes';
    if (is_dir($routesDir)) {
        displayMessage("Vérification des routes Laravel...");
        
        // Parcourir tous les fichiers de routes
        $routeFiles = glob($routesDir . '/*.php');
        foreach ($routeFiles as $routeFile) {
            displayMessage("Vérification du fichier: " . basename($routeFile));
            
            // Lire le contenu du fichier
            $routeContent = file_get_contents($routeFile);
            
            // Rechercher les URLs codées en dur
            $hardcodedUrls = [];
            preg_match_all('/[\'"]https?:\/\/[^\'"]+[\'"]/', $routeContent, $matches);
            
            if (!empty($matches[0])) {
                displayMessage("URLs codées en dur trouvées dans " . basename($routeFile) . ":", 'warning');
                foreach ($matches[0] as $match) {
                    displayMessage("- " . $match);
                    $hardcodedUrls[] = $match;
                }
                
                // Suggérer des corrections
                displayMessage("Suggestion: Remplacez les URLs codées en dur par des appels à la fonction url() ou route().");
            } else {
                displayMessage("Aucune URL codée en dur trouvée dans " . basename($routeFile), 'success');
            }
        }
    } else {
        displayMessage("Le répertoire des routes n'existe pas!", 'error');
    }
    
    // Vérifier le cache des routes
    displayMessage("Effacement du cache des routes...");
    
    // Simuler l'exécution de la commande Artisan
    echo "<pre>";
    echo "Commande à exécuter: php artisan route:clear\n";
    echo "Commande à exécuter: php artisan config:clear\n";
    echo "Commande à exécuter: php artisan cache:clear\n";
    echo "</pre>";
    
    displayMessage("Pour effacer le cache des routes, exécutez les commandes ci-dessus dans un terminal.", 'info');
    
    // Instructions finales
    echo "<h2>Instructions finales</h2>";
    echo "<ol>";
    echo "<li>Redémarrez le serveur Laravel si vous l'utilisez.</li>";
    echo "<li>Videz le cache de votre navigateur.</li>";
    echo "<li>Accédez à l'application via l'URL: <a href='{$newUrl}'>{$newUrl}</a></li>";
    echo "</ol>";
} 