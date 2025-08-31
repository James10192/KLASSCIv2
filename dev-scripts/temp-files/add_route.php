<?php
// Script pour ajouter la route available-places dans web.php

$file = 'C:\xampp\htdocs\ESBTP-yAKROv2Pascal\routes\web.php';
$content = file_get_contents($file);

$newRoute = "            
            // Route pour vérifier les places disponibles dans une classe
            Route::get('classes/{id}/available-places', [ESBTPEtudiantController::class, 'getAvailablePlaces'])
                ->name('classes.available-places')
                ->middleware(['permission:view_classes|view classes']);
";

// Chercher la ligne "// Routes pour les matières" et insérer avant
$search = '            // Routes pour les matières';
$replacement = $newRoute . $search;

$newContent = str_replace($search, $replacement, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "Route ajoutée avec succès!\n";
} else {
    echo "Aucune modification apportée. Ligne de recherche non trouvée.\n";
}
?>