<?php
/**
 * Script pour générer des placeholders HTML pour les images manquantes
 * 
 * Ce script crée des fichiers HTML qui serviront de placeholders pour les images manquantes
 * et vérifie leur existence. Il écrit également un fichier CSS qui définit les styles pour ces placeholders.
 */

// Configuration
$imageDirectory = __DIR__ . '/../images/';
$placeholderDirectory = __DIR__ . '/../placeholders/';
$cssFile = $placeholderDirectory . 'placeholders.css';

// Liste des images à vérifier/créer
$imagesToCheck = [
    'admin-image.png' => [
        'width' => 800,
        'height' => 600,
        'text' => 'Admin & Directeurs',
        'bg_color' => '#6366f1',
        'text_color' => '#ffffff'
    ],
    'teacher-image.png' => [
        'width' => 800,
        'height' => 600,
        'text' => 'Enseignants',
        'bg_color' => '#ec4899',
        'text_color' => '#ffffff'
    ],
    'devices-mockup.png' => [
        'width' => 800,
        'height' => 600,
        'text' => 'KLASSCI sur tous vos appareils',
        'bg_color' => '#0ea5e9',
        'text_color' => '#ffffff'
    ],
    'LOGO-KLASSCI-PNG.png' => [
        'width' => 200,
        'height' => 70,
        'text' => 'KLASSCI',
        'bg_color' => '#ffffff',
        'text_color' => '#6366f1'
    ],
    'login_bg.jpg' => [
        'width' => 1920,
        'height' => 1080,
        'text' => '',
        'bg_color' => '#0f172a',
        'text_color' => '#ffffff'
    ]
];

// S'assurer que les répertoires existent
if (!is_dir($imageDirectory)) {
    mkdir($imageDirectory, 0755, true);
    echo "Création du répertoire d'images: $imageDirectory\n";
}

if (!is_dir($placeholderDirectory)) {
    mkdir($placeholderDirectory, 0755, true);
    echo "Création du répertoire de placeholders: $placeholderDirectory\n";
}

// Générer la feuille de style CSS
$css = <<<CSS
.placeholder-image {
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    font-family: 'Inter', sans-serif;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.placeholder-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: radial-gradient(circle at 10px 10px, rgba(255,255,255,0.1) 1px, transparent 0);
    background-size: 20px 20px;
}

.placeholder-image-text {
    font-weight: bold;
    letter-spacing: 0.5px;
    padding: 20px;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
}

.logo-placeholder {
    border-radius: 50%;
    position: relative;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
    letter-spacing: 1px;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}
CSS;

file_put_contents($cssFile, $css);
echo "Fichier CSS généré: $cssFile\n";

// Vérifier les images et générer des placeholders si nécessaire
$generatedPlaceholders = 0;
$missingImages = [];

foreach ($imagesToCheck as $filename => $config) {
    $imagePath = $imageDirectory . $filename;
    
    if (!file_exists($imagePath)) {
        $missingImages[] = $filename;
        $placeholderPath = $placeholderDirectory . pathinfo($filename, PATHINFO_FILENAME) . '.html';
        
        // Créer le HTML du placeholder
        $html = generatePlaceholderHTML($filename, $config);
        
        file_put_contents($placeholderPath, $html);
        echo "Placeholder créé pour: $filename\n";
        $generatedPlaceholders++;
    } else {
        echo "L'image $filename existe déjà\n";
    }
}

// Créer un fichier JS pour remplacer les images manquantes
if (!empty($missingImages)) {
    $jsContent = generatePlaceholderJS($missingImages, $placeholderDirectory);
    file_put_contents($placeholderDirectory . 'placeholder-loader.js', $jsContent);
    echo "Script JavaScript de remplacement généré\n";
}

// Fonctions utilitaires
function generatePlaceholderHTML($filename, $config) {
    $width = $config['width'];
    $height = $config['height'];
    $text = $config['text'];
    $bg_color = $config['bg_color'];
    $text_color = $config['text_color'];
    
    if (pathinfo($filename, PATHINFO_FILENAME) === 'LOGO-KLASSCI-PNG') {
        // Créer un placeholder spécial pour le logo
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Logo Placeholder</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
        }
        .logo-container {
            width: {$width}px;
            height: {$height}px;
            background-color: {$bg_color};
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .text {
            font-family: 'Arial', sans-serif;
            font-size: 24px;
            font-weight: bold;
            color: {$text_color};
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <div class="text">{$text}</div>
    </div>
</body>
</html>
HTML;
    } else {
        // Créer un placeholder standard pour les autres images
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$filename} Placeholder</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
        }
        .placeholder {
            width: {$width}px;
            height: {$height}px;
            background-color: {$bg_color};
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            border-radius: 10px;
        }
        .placeholder::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 10px 10px, rgba(255,255,255,0.1) 1px, transparent 0);
            background-size: 20px 20px;
        }
        .placeholder::after {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 2s infinite;
        }
        @keyframes shine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        .text {
            font-family: 'Arial', sans-serif;
            font-size: 20px;
            color: {$text_color};
            z-index: 1;
            text-align: center;
            padding: 20px;
            max-width: 80%;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="placeholder">
        <div class="text">{$text}</div>
    </div>
</body>
</html>
HTML;
    }
    
    return $html;
}

function generatePlaceholderJS($missingImages, $placeholderDirectory) {
    $jsContent = <<<JS
/**
 * Script pour remplacer les images manquantes par des placeholders
 */
document.addEventListener('DOMContentLoaded', function() {
    // Liste des images manquantes
    const missingImages = [
JS;
    
    foreach ($missingImages as $image) {
        $jsContent .= "        '" . $image . "',\n";
    }
    
    $jsContent .= <<<JS
    ];
    
    // Fonction pour remplacer une image
    function replaceMissingImage(img) {
        const src = img.getAttribute('src');
        
        // Vérifier si cette image est dans notre liste d'images manquantes
        const filename = src.substring(src.lastIndexOf('/') + 1);
        if (missingImages.includes(filename)) {
            // Créer un iframe pour afficher le placeholder
            const iframe = document.createElement('iframe');
            const placeholderPath = '/placeholders/' + filename.replace(/\.[^/.]+$/, "") + '.html';
            
            iframe.src = placeholderPath;
            iframe.style.border = 'none';
            iframe.style.width = img.width + 'px';
            iframe.style.height = img.height + 'px';
            iframe.style.display = 'inline-block';
            iframe.style.borderRadius = '10px';
            iframe.style.overflow = 'hidden';
            
            // Remplacer l'image par l'iframe
            img.parentNode.replaceChild(iframe, img);
        }
    }
    
    // Parcourir toutes les images de la page
    document.querySelectorAll('img').forEach(replaceMissingImage);
    
    // Observer les nouvelles images qui pourraient être ajoutées dynamiquement
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.tagName === 'IMG') {
                    replaceMissingImage(node);
                } else if (node.querySelectorAll) {
                    node.querySelectorAll('img').forEach(replaceMissingImage);
                }
            });
        });
    });
    
    // Observer tout le document pour les changements
    observer.observe(document.body, { childList: true, subtree: true });
});
JS;
    
    return $jsContent;
}

if ($generatedPlaceholders > 0) {
    echo "\n$generatedPlaceholders placeholders ont été créés avec succès.\n";
    echo "N'oubliez pas d'inclure les fichiers suivants dans votre template principal:\n";
    echo "- <link rel=\"stylesheet\" href=\"/placeholders/placeholders.css\">\n";
    echo "- <script src=\"/placeholders/placeholder-loader.js\"></script>\n";
} else {
    echo "\nAucun nouveau placeholder n'a été créé. Toutes les images existent déjà.\n";
}

echo "\nTerminé. Les placeholders pour les images manquantes ont été générés dans le dossier public/placeholders.\n"; 