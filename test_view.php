<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Test de rendu de la vue dashboard-avance...\n";
    $view = view('esbtp.comptabilite.dashboard-avance');
    $rendered = $view->render();
    echo "✅ SUCCESS: Vue rendue sans erreur ParseError!\n";
    echo "Longueur du contenu rendu: " . strlen($rendered) . " caractères\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Type d'erreur: " . get_class($e) . "\n";
}
?>
