<?php
/**
 * Script pour générer des images thématiques illustratives
 * 
 * Ce script crée des images de haute qualité avec des illustrations 
 * correspondant aux différents thèmes de l'application KLASSCI
 * (administration, enseignants, appareils, etc.)
 */

// Configuration
$imageDirectory = __DIR__ . '/../images/';
$iconsDirectory = __DIR__ . '/../icons/';

// S'assurer que les répertoires existent
if (!is_dir($imageDirectory)) {
    mkdir($imageDirectory, 0755, true);
    echo "Création du répertoire d'images: $imageDirectory\n";
}

if (!is_dir($iconsDirectory)) {
    mkdir($iconsDirectory, 0755, true);
    echo "Création du répertoire d'icônes: $iconsDirectory\n";
}

// Liste des images thématiques à créer
$thematicImages = [
    'admin-image.png' => [
        'width' => 800,
        'height' => 600,
        'theme' => 'administration',
        'title' => 'Administration & Direction',
        'elements' => [
            ['type' => 'gradient', 'colors' => ['#e9f1ff', '#cad9ff']],
            ['type' => 'icons', 'icons' => ['chart-line', 'users-cog', 'school', 'clipboard-check']],
            ['type' => 'abstract', 'count' => 5, 'color' => '#6366f1'],
        ]
    ],
    'teacher-image.png' => [
        'width' => 800,
        'height' => 600,
        'theme' => 'education',
        'title' => 'Enseignants',
        'elements' => [
            ['type' => 'gradient', 'colors' => ['#ffeff8', '#ffe1ed']],
            ['type' => 'icons', 'icons' => ['chalkboard-teacher', 'book', 'graduation-cap', 'apple-alt']],
            ['type' => 'abstract', 'count' => 5, 'color' => '#ec4899'],
        ]
    ],
    'devices-mockup.png' => [
        'width' => 800,
        'height' => 600,
        'theme' => 'technology',
        'title' => 'KLASSCI sur tous vos appareils',
        'elements' => [
            ['type' => 'gradient', 'colors' => ['#e8f7ff', '#d1eeff']],
            ['type' => 'icons', 'icons' => ['laptop', 'mobile-alt', 'tablet', 'desktop']],
            ['type' => 'abstract', 'count' => 5, 'color' => '#0ea5e9'],
        ]
    ],
    'LOGO-KLASSCI-PNG.png' => [
        'width' => 200,
        'height' => 70,
        'theme' => 'logo',
        'title' => 'KLASSCI',
        'elements' => [
            ['type' => 'gradient', 'colors' => ['#ffffff', '#f8fafc']],
            ['type' => 'text', 'font_size' => 24, 'font_weight' => 'bold', 'color' => '#6366f1'],
        ]
    ],
    'login_bg.jpg' => [
        'width' => 1920,
        'height' => 1080,
        'theme' => 'abstract',
        'title' => '',
        'elements' => [
            ['type' => 'gradient', 'colors' => ['#0f172a', '#1e293b']],
            ['type' => 'pattern', 'pattern' => 'dots', 'color' => '#ffffff', 'opacity' => 0.05],
            ['type' => 'wave', 'count' => 3, 'colors' => ['#6c5ce7', '#fc6736'], 'opacity' => 0.1],
        ]
    ],
    // Icônes pour flottantes dans les sections
    'chart-icon.png' => [
        'width' => 80,
        'height' => 80,
        'theme' => 'icon',
        'title' => '',
        'elements' => [
            ['type' => 'circle', 'color' => '#6366f1', 'opacity' => 0.2],
            ['type' => 'icon', 'icon' => 'chart-pie', 'color' => '#6366f1', 'size' => 40],
        ]
    ],
    'user-icon.png' => [
        'width' => 80,
        'height' => 80,
        'theme' => 'icon',
        'title' => '',
        'elements' => [
            ['type' => 'circle', 'color' => '#ec4899', 'opacity' => 0.2],
            ['type' => 'icon', 'icon' => 'user-graduate', 'color' => '#ec4899', 'size' => 40],
        ]
    ],
    'cog-icon.png' => [
        'width' => 80,
        'height' => 80,
        'theme' => 'icon',
        'title' => '',
        'elements' => [
            ['type' => 'circle', 'color' => '#0ea5e9', 'opacity' => 0.2],
            ['type' => 'icon', 'icon' => 'cog', 'color' => '#0ea5e9', 'size' => 40],
        ]
    ],
    'badge-klassci.png' => [
        'width' => 80,
        'height' => 80,
        'theme' => 'badge',
        'title' => 'K',
        'elements' => [
            ['type' => 'circle', 'color' => '#ffffff', 'border' => '#6366f1'],
            ['type' => 'text', 'font_size' => 36, 'font_weight' => 'bold', 'color' => '#6366f1'],
        ]
    ]
];

// Fonction pour créer une image thématique
function createThematicImage($config, $outputPath) {
    $width = $config['width'];
    $height = $config['height'];
    $title = $config['title'];
    $elements = $config['elements'];
    
    // Créer une image avec canal alpha
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, true);
    imagesavealpha($image, true);
    
    // Remplir l'image avec une couleur transparente
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);
    
    // Appliquer les éléments selon leur type
    foreach ($elements as $element) {
        switch ($element['type']) {
            case 'gradient':
                createGradientBackground($image, $width, $height, $element['colors']);
                break;
                
            case 'icons':
                createIconsLayout($image, $width, $height, $element['icons']);
                break;
                
            case 'abstract':
                createAbstractShapes($image, $width, $height, $element['count'], $element['color']);
                break;
                
            case 'text':
                $fontSize = $element['font_size'] ?? 20;
                $fontWeight = $element['font_weight'] ?? 'normal';
                $color = $element['color'] ?? '#000000';
                addCenteredText($image, $width, $height, $title, $fontSize, $fontWeight, hexToRgb($color));
                break;
                
            case 'pattern':
                addPattern($image, $width, $height, $element['pattern'], $element['color'], $element['opacity'] ?? 1);
                break;
                
            case 'wave':
                addWaves($image, $width, $height, $element['count'], $element['colors'], $element['opacity'] ?? 1);
                break;
                
            case 'circle':
                addCircle($image, $width, $height, $element['color'], $element['opacity'] ?? 1, $element['border'] ?? null);
                break;
                
            case 'icon':
                addCenteredIcon($image, $width, $height, $element['icon'], $element['color'], $element['size'] ?? 40);
                break;
        }
    }
    
    // Ajouter le titre si ce n'est pas déjà fait et que le titre n'est pas vide
    if (!empty($title) && !in_array('text', array_column($elements, 'type'))) {
        $titleColor = hexToRgb('#ffffff');  // Blanc par défaut
        $fontSize = $width / 20;  // Taille de police proportionnelle à la largeur
        
        // Assombrir légèrement le bas de l'image pour le texte
        $overlay = imagecreatetruecolor($width, $height / 3);
        $black = imagecolorallocatealpha($overlay, 0, 0, 0, 80);  // Noir semi-transparent
        imagefill($overlay, 0, 0, $black);
        
        // Copier l'overlay au bas de l'image principale
        imagecopy($image, $overlay, 0, $height - ($height / 3), 0, 0, $width, $height / 3);
        imageDestroy($overlay);
        
        // Ajouter le texte centré en bas
        addText($image, $width / 2, $height - ($height / 6), $title, $fontSize, 'bold', $titleColor, true);
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

// Fonction pour créer un dégradé
function createGradientBackground($image, $width, $height, $colors) {
    $startColor = hexToRgb($colors[0]);
    $endColor = hexToRgb($colors[1]);
    
    for ($y = 0; $y < $height; $y++) {
        // Calculer le ratio de progression
        $ratio = $y / $height;
        
        // Interpoler entre les couleurs
        $r = $startColor[0] * (1 - $ratio) + $endColor[0] * $ratio;
        $g = $startColor[1] * (1 - $ratio) + $endColor[1] * $ratio;
        $b = $startColor[2] * (1 - $ratio) + $endColor[2] * $ratio;
        
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $width, $y, $color);
    }
}

// Fonction pour ajouter des icônes de façon organisée
function createIconsLayout($image, $width, $height, $icons) {
    $iconCount = count($icons);
    $radius = min($width, $height) * 0.3;  // Rayon pour placer les icônes
    $centerX = $width / 2;
    $centerY = $height / 2;
    $iconSize = min($width, $height) * 0.15;  // Taille proportionnelle
    
    $colorBase = imagecolorallocatealpha($image, 255, 255, 255, 40);  // Blanc semi-transparent
    
    for ($i = 0; $i < $iconCount; $i++) {
        $angle = 2 * M_PI * $i / $iconCount;
        $x = $centerX + $radius * cos($angle);
        $y = $centerY + $radius * sin($angle);
        
        // Dessiner un cercle pour l'icône
        imagefilledellipse($image, $x, $y, $iconSize, $iconSize, $colorBase);
        
        // Ici on simulerait l'ajout de l'icône, mais cette version simplifiée 
        // n'inclut pas le rendu réel d'icônes FontAwesome
    }
}

// Fonction pour créer des formes abstraites
function createAbstractShapes($image, $width, $height, $count, $color) {
    $rgbColor = hexToRgb($color);
    
    for ($i = 0; $i < $count; $i++) {
        // Créer des couleurs avec différentes opacités
        $shapeColor = imagecolorallocatealpha(
            $image, 
            $rgbColor[0], 
            $rgbColor[1], 
            $rgbColor[2], 
            rand(40, 110)  // Opacité variable
        );
        
        $shape = rand(0, 2);  // 0 = cercle, 1 = rectangle arrondi, 2 = ligne ondulée
        
        switch ($shape) {
            case 0:  // Cercle
                $size = rand($width * 0.05, $width * 0.2);
                $x = rand(0, $width);
                $y = rand(0, $height);
                imagefilledellipse($image, $x, $y, $size, $size, $shapeColor);
                break;
                
            case 1:  // Rectangle arrondi (simulé avec un rectangle simple)
                $w = rand($width * 0.05, $width * 0.25);
                $h = rand($height * 0.05, $height * 0.25);
                $x = rand(0, $width - $w);
                $y = rand(0, $height - $h);
                imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $shapeColor);
                break;
                
            case 2:  // Ligne ondulée (simplifiée)
                $x1 = rand(0, $width);
                $y1 = rand(0, $height);
                $x2 = rand(0, $width);
                $y2 = rand(0, $height);
                imagesetthickness($image, rand(1, 10));
                imageline($image, $x1, $y1, $x2, $y2, $shapeColor);
                break;
        }
    }
}

// Fonction pour ajouter du texte centré
function addCenteredText($image, $width, $height, $text, $fontSize, $fontWeight, $colorRgb) {
    $color = imagecolorallocate($image, $colorRgb[0], $colorRgb[1], $colorRgb[2]);
    
    // On utilise ici les polices par défaut car l'utilisation de TTF nécessiterait
    // d'avoir des polices installées sur le serveur
    $fontIndex = 5;  // 1 à 5, 5 est le plus grand
    
    // Calculer la largeur et la hauteur approximatives du texte
    $textWidth = strlen($text) * imagefontwidth($fontIndex);
    $textHeight = imagefontheight($fontIndex);
    
    // Centrer le texte
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    // Dessiner le texte
    imagestring($image, $fontIndex, $x, $y, $text, $color);
}

// Fonction pour ajouter du texte à une position précise
function addText($image, $x, $y, $text, $fontSize, $fontWeight, $colorRgb, $centered = false) {
    $color = imagecolorallocate($image, $colorRgb[0], $colorRgb[1], $colorRgb[2]);
    
    // Pour simplifier, on utilise les polices intégrées
    $fontIndex = 5;
    
    if ($centered) {
        $textWidth = strlen($text) * imagefontwidth($fontIndex);
        $x = $x - ($textWidth / 2);
    }
    
    imagestring($image, $fontIndex, $x, $y, $text, $color);
}

// Fonction pour ajouter un motif
function addPattern($image, $width, $height, $pattern, $color, $opacity) {
    $rgbColor = hexToRgb($color);
    $patternColor = imagecolorallocatealpha(
        $image, 
        $rgbColor[0], 
        $rgbColor[1], 
        $rgbColor[2], 
        127 - (127 * $opacity)  // Convertir l'opacité (0-1) en alpha (0-127)
    );
    
    switch ($pattern) {
        case 'dots':
            // Créer un motif de points
            $spacing = 20;
            for ($x = 0; $x < $width; $x += $spacing) {
                for ($y = 0; $y < $height; $y += $spacing) {
                    imagefilledellipse($image, $x, $y, 2, 2, $patternColor);
                }
            }
            break;
            
        case 'lines':
            // Créer un motif de lignes
            $spacing = 30;
            for ($y = 0; $y < $height; $y += $spacing) {
                imageline($image, 0, $y, $width, $y, $patternColor);
            }
            break;
            
        case 'grid':
            // Créer un motif de grille
            $spacing = 40;
            for ($x = 0; $x < $width; $x += $spacing) {
                imageline($image, $x, 0, $x, $height, $patternColor);
            }
            for ($y = 0; $y < $height; $y += $spacing) {
                imageline($image, 0, $y, $width, $y, $patternColor);
            }
            break;
    }
}

// Fonction pour ajouter des vagues
function addWaves($image, $width, $height, $count, $colors, $opacity) {
    $amplitudes = [0.1, 0.15, 0.2];  // Différentes amplitudes pour chaque vague
    $frequencies = [0.5, 1, 1.5];    // Différentes fréquences
    
    for ($i = 0; $i < $count && $i < count($colors) && $i < 3; $i++) {
        $rgbColor = hexToRgb($colors[$i]);
        $waveColor = imagecolorallocatealpha(
            $image, 
            $rgbColor[0], 
            $rgbColor[1], 
            $rgbColor[2], 
            127 - (127 * $opacity)
        );
        
        $amplitude = $height * $amplitudes[$i % 3];
        $frequency = $frequencies[$i % 3];
        $phase = rand(0, 100) / 100;  // Phase aléatoire
        
        $points = [];
        $steps = 100;  // Plus de points = plus lisse
        
        for ($j = 0; $j <= $steps; $j++) {
            $x = $j * $width / $steps;
            $y = ($height * 0.5) + $amplitude * sin(2 * M_PI * $frequency * ($j / $steps) + $phase);
            $points[] = $x;
            $points[] = $y;
        }
        
        // Fermer la forme pour le remplissage
        $points[] = $width;
        $points[] = $height;
        $points[] = 0;
        $points[] = $height;
        
        // Dessiner la vague remplie
        imagefilledpolygon($image, $points, count($points) / 2, $waveColor);
    }
}

// Fonction pour ajouter un cercle centré
function addCircle($image, $width, $height, $color, $opacity, $borderColor = null) {
    $centerX = $width / 2;
    $centerY = $height / 2;
    $diameter = min($width, $height) * 0.9;  // 90% de la dimension la plus petite
    
    $rgbColor = hexToRgb($color);
    $circleColor = imagecolorallocatealpha(
        $image, 
        $rgbColor[0], 
        $rgbColor[1], 
        $rgbColor[2], 
        127 - (127 * $opacity)
    );
    
    // Dessiner le cercle plein
    imagefilledellipse($image, $centerX, $centerY, $diameter, $diameter, $circleColor);
    
    // Ajouter une bordure si spécifiée
    if ($borderColor) {
        $rgbBorder = hexToRgb($borderColor);
        $borderCol = imagecolorallocate($image, $rgbBorder[0], $rgbBorder[1], $rgbBorder[2]);
        imageellipse($image, $centerX, $centerY, $diameter, $diameter, $borderCol);
    }
}

// Fonction pour ajouter une icône centrée
function addCenteredIcon($image, $width, $height, $icon, $color, $size) {
    // Cette fonction simule l'ajout d'une icône
    // Dans un environnement de production, on utiliserait une bibliothèque pour
    // dessiner de vraies icônes FontAwesome ou on inclurait des images d'icônes
    
    $rgbColor = hexToRgb($color);
    $iconColor = imagecolorallocate($image, $rgbColor[0], $rgbColor[1], $rgbColor[2]);
    
    $centerX = $width / 2;
    $centerY = $height / 2;
    
    // Simulation simplifiée de l'icône avec une forme de base
    switch ($icon) {
        case 'chart-pie':
            // Simuler un graphique circulaire
            imagefilledellipse($image, $centerX, $centerY, $size, $size, $iconColor);
            imagefilledarc($image, $centerX, $centerY, $size, $size, 0, 120, imagecolorallocate($image, 255, 255, 255), IMG_ARC_PIE);
            break;
            
        case 'user-graduate':
            // Simuler une icône d'utilisateur
            imagefilledellipse($image, $centerX, $centerY - $size/4, $size/2, $size/2, $iconColor);  // Tête
            imagefilledpolygon($image, [
                $centerX - $size/2, $centerY + $size/4,
                $centerX + $size/2, $centerY + $size/4,
                $centerX, $centerY + $size/2
            ], 3, $iconColor);  // Corps
            break;
            
        case 'cog':
            // Simuler une roue dentée
            imagefilledellipse($image, $centerX, $centerY, $size * 0.8, $size * 0.8, $iconColor);
            for ($i = 0; $i < 8; $i++) {
                $angle = 2 * M_PI * $i / 8;
                $x1 = $centerX + cos($angle) * $size * 0.4;
                $y1 = $centerY + sin($angle) * $size * 0.4;
                $x2 = $centerX + cos($angle) * $size * 0.7;
                $y2 = $centerY + sin($angle) * $size * 0.7;
                imagesetthickness($image, $size / 10);
                imageline($image, $x1, $y1, $x2, $y2, imagecolorallocate($image, 255, 255, 255));
            }
            break;
            
        default:
            // Forme générique
            imagefilledellipse($image, $centerX, $centerY, $size, $size, $iconColor);
            break;
    }
}

// Fonction utilitaire pour convertir couleur hexadécimale en RGB
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return [$r, $g, $b];
}

// Créer les images thématiques
$imagesCreated = 0;

foreach ($thematicImages as $filename => $config) {
    $imagePath = ($config['theme'] === 'icon') ? 
                 $iconsDirectory . $filename : 
                 $imageDirectory . $filename;
    
    if (!file_exists($imagePath) || isset($_GET['force'])) {
        createThematicImage($config, $imagePath);
        echo "Image thématique créée: $filename\n";
        $imagesCreated++;
    } else {
        echo "L'image $filename existe déjà\n";
    }
}

if ($imagesCreated > 0) {
    echo "\n$imagesCreated images thématiques ont été créées avec succès.\n";
} else {
    echo "\nAucune nouvelle image n'a été créée. Toutes les images existent déjà.\n";
    echo "Utilisez ?force=1 pour forcer la recréation des images.\n";
}

echo "\nTerminé. Les images thématiques illustratives ont été générées dans les dossiers public/images et public/icons.\n"; 