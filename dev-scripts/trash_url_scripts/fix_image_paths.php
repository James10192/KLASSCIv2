<?php
/**
 * Script de correction des chemins d'accès aux images
 * 
 * Ce script vérifie et corrige les problèmes de chemins d'accès aux images
 * dans l'application ESBTP.
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

// Fonction pour vérifier si un fichier existe
function fileExists($path) {
    return file_exists($path);
}

// Fonction pour copier un fichier
function copyFile($source, $destination) {
    // Créer le répertoire de destination s'il n'existe pas
    $destinationDir = dirname($destination);
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }
    
    return copy($source, $destination);
}

echo "<h1>Correction des chemins d'accès aux images</h1>";

// Chemin de base du projet
$basePath = __DIR__ . '/../';

// Vérifier les images du logo
$logoSources = [
    'images/esbtp_logo.png' => 'img/esbtp_logo.png',
    'images/esbtp_logo_white.png' => 'img/esbtp_logo_white.png'
];

echo "<h2>Vérification des logos</h2>";

foreach ($logoSources as $source => $destination) {
    $sourcePath = $basePath . 'public/' . $source;
    $destinationPath = $basePath . 'public/' . $destination;
    
    if (fileExists($sourcePath)) {
        displayMessage("Le fichier source {$source} existe.", 'success');
        
        if (fileExists($destinationPath)) {
            displayMessage("Le fichier destination {$destination} existe déjà.", 'info');
        } else {
            displayMessage("Le fichier destination {$destination} n'existe pas.", 'warning');
            
            // Copier le fichier
            if (copyFile($sourcePath, $destinationPath)) {
                displayMessage("Le fichier {$source} a été copié vers {$destination}.", 'success');
            } else {
                displayMessage("Erreur lors de la copie du fichier {$source} vers {$destination}.", 'error');
            }
        }
    } else {
        displayMessage("Le fichier source {$source} n'existe pas!", 'error');
    }
}

// Vérifier le fichier CSS
$cssFile = 'css/esbtp-colors.css';
$cssPath = $basePath . 'public/' . $cssFile;

echo "<h2>Vérification des fichiers CSS</h2>";

if (fileExists($cssPath)) {
    displayMessage("Le fichier CSS {$cssFile} existe.", 'success');
} else {
    displayMessage("Le fichier CSS {$cssFile} n'existe pas!", 'error');
    
    // Créer un fichier CSS de base
    $cssContent = <<<CSS
:root {
    --primary: #1e88e5;
    --primary-light: #6ab7ff;
    --primary-dark: #005cb2;
    --secondary: #ff6f00;
    --secondary-light: #ffa040;
    --secondary-dark: #c43e00;
    --success: #43a047;
    --info: #039be5;
    --warning: #ffb300;
    --danger: #e53935;
    --light: #f5f5f5;
    --dark: #212121;
    --gray: #757575;
    --gray-light: #bdbdbd;
    --gray-dark: #424242;
    --white: #ffffff;
    --black: #000000;
}

/* Couleurs de texte */
.text-primary { color: var(--primary) !important; }
.text-secondary { color: var(--secondary) !important; }
.text-success { color: var(--success) !important; }
.text-info { color: var(--info) !important; }
.text-warning { color: var(--warning) !important; }
.text-danger { color: var(--danger) !important; }
.text-light { color: var(--light) !important; }
.text-dark { color: var(--dark) !important; }
.text-white { color: var(--white) !important; }
.text-black { color: var(--black) !important; }

/* Couleurs de fond */
.bg-primary { background-color: var(--primary) !important; }
.bg-secondary { background-color: var(--secondary) !important; }
.bg-success { background-color: var(--success) !important; }
.bg-info { background-color: var(--info) !important; }
.bg-warning { background-color: var(--warning) !important; }
.bg-danger { background-color: var(--danger) !important; }
.bg-light { background-color: var(--light) !important; }
.bg-dark { background-color: var(--dark) !important; }
.bg-white { background-color: var(--white) !important; }
.bg-black { background-color: var(--black) !important; }

/* Boutons */
.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
}
.btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
}

.btn-secondary {
    background-color: var(--secondary);
    border-color: var(--secondary);
}
.btn-secondary:hover {
    background-color: var(--secondary-dark);
    border-color: var(--secondary-dark);
}

.btn-outline-white {
    color: var(--white);
    border-color: var(--white);
}
.btn-outline-white:hover {
    background-color: var(--white);
    color: var(--primary);
}

/* Liens */
a {
    color: var(--primary);
}
a:hover {
    color: var(--primary-dark);
}

/* Styles spécifiques à ESBTP */
.navbar .nav-link {
    color: var(--white) !important;
}
.navbar-scrolled .nav-link {
    color: var(--dark) !important;
}
.navbar-scrolled .nav-link:hover {
    color: var(--primary) !important;
}

.section {
    padding: 80px 0;
}

.section-title h2 {
    color: var(--primary);
    margin-bottom: 20px;
    position: relative;
    display: inline-block;
}

.section-title h2:after {
    content: '';
    position: absolute;
    width: 50px;
    height: 3px;
    background-color: var(--secondary);
    bottom: -10px;
    left: 0;
}

.section-title.text-center h2:after {
    left: 50%;
    transform: translateX(-50%);
}
CSS;
    
    // Créer le répertoire CSS s'il n'existe pas
    $cssDir = dirname($cssPath);
    if (!is_dir($cssDir)) {
        mkdir($cssDir, 0755, true);
    }
    
    // Écrire le contenu dans le fichier
    if (file_put_contents($cssPath, $cssContent)) {
        displayMessage("Un fichier CSS de base a été créé.", 'success');
    } else {
        displayMessage("Erreur lors de la création du fichier CSS.", 'error');
    }
}

// Instructions finales
echo "<h2>Instructions finales</h2>";
echo "<ol>";
echo "<li>Redémarrez le serveur Laravel si vous l'utilisez.</li>";
echo "<li>Videz le cache de votre navigateur.</li>";
echo "<li>Accédez à l'application via <a href='http://127.0.0.1:8000'>http://127.0.0.1:8000</a> pour vérifier que les images s'affichent correctement.</li>";
echo "</ol>";

// Bouton pour effacer le cache Laravel
echo "<form method='post'>";
echo "<input type='hidden' name='clear_cache' value='1'>";
echo "<button type='submit' style='background-color: #1e88e5; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;'>Effacer le cache Laravel</button>";
echo "</form>";

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
    // Simuler l'exécution des commandes Artisan
    echo "<h3>Effacement du cache...</h3>";
    echo "<pre>";
    
    // Commandes à exécuter
    $commands = [
        'php artisan cache:clear',
        'php artisan config:clear',
        'php artisan route:clear',
        'php artisan view:clear'
    ];
    
    // Exécuter les commandes
    foreach ($commands as $command) {
        echo "Exécution de: {$command}\n";
        
        // Changer de répertoire pour être à la racine du projet
        chdir($basePath);
        
        // Exécuter la commande
        $output = [];
        exec($command, $output, $returnCode);
        
        // Afficher le résultat
        echo implode("\n", $output) . "\n";
        
        if ($returnCode === 0) {
            echo "Commande exécutée avec succès.\n";
        } else {
            echo "Erreur lors de l'exécution de la commande.\n";
        }
        
        echo "\n";
    }
    
    echo "</pre>";
    
    displayMessage("Le cache a été effacé avec succès.", 'success');
} 