<?php
/**
 * Script pour générer les images manquantes
 * 
 * Ce script crée des images de remplacement pour les images manquantes
 * référencées dans les fichiers de vue de l'application KLASSCI
 */

// Configuration
$imageDirectory = __DIR__ . '/../images/';
$imagesToCreate = [
    'admin-image.png' => [
        'width' => 800,
        'height' => 600,
        'background' => [245, 248, 255],
        'text' => 'Administration',
        'textColor' => [99, 102, 241]
    ],
    'teacher-image.png' => [
        'width' => 800,
        'height' => 600,
        'background' => [245, 248, 255],
        'text' => 'Enseignants',
        'textColor' => [99, 102, 241]
    ],
    'devices-mockup.png' => [
        'width' => 800,
        'height' => 600,
        'background' => [245, 248, 255],
        'text' => 'KLASSCI sur tous vos appareils',
        'textColor' => [99, 102, 241]
    ],
    'LOGO-KLASSCI-PNG.png' => [
        'width' => 200,
        'height' => 70,
        'background' => [255, 255, 255, 127],
        'text' => 'KLASSCI',
        'textColor' => [99, 102, 241]
    ],
    'login_bg.jpg' => [
        'width' => 1920,
        'height' => 1080,
        'background' => [15, 23, 42],
        'text' => '',
        'textColor' => [255, 255, 255]
    ]
];

// S'assurer que le répertoire d'images existe
if (!is_dir($imageDirectory)) {
    mkdir($imageDirectory, 0755, true);
    echo "Création du répertoire d'images: $imageDirectory\n";
}

// Fonction pour créer une image avec du texte
function createImageWithText($config, $outputPath) {
    $width = $config['width'];
    $height = $config['height'];
    $background = $config['background'];
    $text = $config['text'];
    $textColor = $config['textColor'];
    
    // Créer une image vide
    $image = imagecreatetruecolor($width, $height);
    
    // Définir la couleur de fond
    $bgColor = imagecolorallocate($image, $background[0], $background[1], $background[2]);
    imagefill($image, 0, 0, $bgColor);
    
    // Ajouter des formes pour rendre l'image plus attrayante
    $accentColor1 = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
    $accentColor2 = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2], 50);
    
    // Dessiner quelques formes géométriques
    for ($i = 0; $i < 10; $i++) {
        $x1 = rand(0, $width);
        $y1 = rand(0, $height);
        $x2 = rand(0, $width);
        $y2 = rand(0, $height);
        
        imageline($image, $x1, $y1, $x2, $y2, $accentColor2);
    }
    
    for ($i = 0; $i < 5; $i++) {
        $x = rand(0, $width);
        $y = rand(0, $height);
        $size = rand(20, 100);
        
        imagefilledellipse($image, $x, $y, $size, $size, $accentColor2);
    }
    
    // Ajouter du texte si spécifié
    if (!empty($text)) {
        $textColor = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
        
        // Déterminer la taille de police en fonction de la longueur du texte et de la taille de l'image
        $fontSize = min($width, $height) / (strlen($text) * 1.2);
        $fontSize = max(10, min(60, $fontSize)); // Entre 10 et 60
        
        // Charger une police
        $fontPath = __DIR__ . '/../../resources/fonts/arial.ttf';
        if (!file_exists($fontPath)) {
            // Utiliser une police de substitution si arial.ttf n'existe pas
            $fontPath = 5; // police par défaut
        }
        
        // Obtenir les dimensions du texte
        if (is_string($fontPath)) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            $textX = ($width - $textWidth) / 2;
            $textY = ($height + $textHeight) / 2;
            
            // Ajouter le texte
            imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $text);
        } else {
            // Centrer le texte (version simple avec police par défaut)
            $textWidth = strlen($text) * 5;
            $textX = ($width - $textWidth) / 2;
            $textY = $height / 2;
            
            imagestring($image, $fontPath, $textX, $textY, $text, $textColor);
        }
    }
    
    // Déterminer le format d'image et l'enregistrer
    $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($image, $outputPath, 90);
            break;
        case 'png':
            imagepng($image, $outputPath, 9);
            break;
        case 'gif':
            imagegif($image, $outputPath);
            break;
        default:
            imagepng($image, $outputPath, 9);
    }
    
    // Libérer la mémoire
    imagedestroy($image);
}

// Créer les images manquantes
$imagesCreated = 0;

foreach ($imagesToCreate as $filename => $config) {
    $imagePath = $imageDirectory . $filename;
    
    if (!file_exists($imagePath)) {
        createImageWithText($config, $imagePath);
        echo "Image créée: $filename\n";
        $imagesCreated++;
    } else {
        echo "L'image $filename existe déjà\n";
    }
}

if ($imagesCreated > 0) {
    echo "\n$imagesCreated images ont été créées avec succès.\n";
} else {
    echo "\nAucune nouvelle image n'a été créée. Toutes les images existent déjà.\n";
}

echo "\nTerminé. Les images manquantes ont été générées dans le dossier public/images.\n"; 