<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Démarrer l'application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Vérifier la connexion à la base de données
    echo "Vérification de la connexion à la base de données...\n";
    $connection = DB::connection()->getPdo();
    echo "Connexion à la base de données établie. Nom de la base de données: " . DB::connection()->getDatabaseName() . "\n";

    // Vérifier si l'emploi du temps avec ID 1 existe
    echo "Vérification de l'emploi du temps avec ID 1...\n";
    $emploiTemps = DB::table('esbtp_emploi_temps')->where('id', 1)->first();

    if ($emploiTemps) {
        echo "Emploi du temps trouvé:\n";
        echo "ID: " . $emploiTemps->id . "\n";
        echo "Titre: " . $emploiTemps->titre . "\n";
        echo "Classe ID: " . $emploiTemps->classe_id . "\n";
        echo "Année universitaire ID: " . $emploiTemps->annee_universitaire_id . "\n";

        // Vérifier si l'année universitaire existe
        echo "Vérification de l'année universitaire avec ID " . $emploiTemps->annee_universitaire_id . "...\n";
        $annee = DB::table('esbtp_annee_universitaires')->where('id', $emploiTemps->annee_universitaire_id)->first();

        if ($annee) {
            echo "Année universitaire trouvée:\n";
            echo "ID: " . $annee->id . "\n";
            echo "Nom: " . $annee->name . "\n";
        } else {
            echo "Année universitaire non trouvée!\n";
        }
    } else {
        echo "Emploi du temps non trouvé!\n";
    }

} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
