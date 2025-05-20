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
    echo "Vérification de l'emploi du temps avec ID 1 en utilisant le modèle...\n";
    $emploiTemps = App\Models\ESBTPEmploiTemps::find(1);

    if ($emploiTemps) {
        echo "Emploi du temps trouvé:\n";
        echo "ID: " . $emploiTemps->id . "\n";
        echo "Titre: " . $emploiTemps->titre . "\n";
        echo "Classe ID: " . $emploiTemps->classe_id . "\n";
        echo "Année universitaire ID: " . $emploiTemps->annee_universitaire_id . "\n";

        // Vérifier si la relation annee fonctionne
        echo "Vérification de la relation annee...\n";
        $annee = $emploiTemps->annee;

        if ($annee) {
            echo "Relation annee fonctionne. Année universitaire trouvée:\n";
            echo "ID: " . $annee->id . "\n";
            echo "Nom: " . $annee->name . "\n";
        } else {
            echo "Relation annee ne fonctionne pas. Année universitaire non trouvée!\n";

            // Vérifier si l'année universitaire existe directement
            echo "Vérification directe de l'année universitaire avec ID " . $emploiTemps->annee_universitaire_id . "...\n";
            $anneeDirecte = App\Models\ESBTPAnneeUniversitaire::find($emploiTemps->annee_universitaire_id);

            if ($anneeDirecte) {
                echo "Année universitaire trouvée directement:\n";
                echo "ID: " . $anneeDirecte->id . "\n";
                echo "Nom: " . $anneeDirecte->name . "\n";
            } else {
                echo "Année universitaire non trouvée directement!\n";
            }
        }

        // Vérifier si la relation classe fonctionne
        echo "Vérification de la relation classe...\n";
        $classe = $emploiTemps->classe;

        if ($classe) {
            echo "Relation classe fonctionne. Classe trouvée:\n";
            echo "ID: " . $classe->id . "\n";
            echo "Nom: " . $classe->name . "\n";

            // Vérifier si la relation filiere fonctionne
            echo "Vérification de la relation filiere...\n";
            $filiere = $classe->filiere;

            if ($filiere) {
                echo "Relation filiere fonctionne. Filière trouvée:\n";
                echo "ID: " . $filiere->id . "\n";
                echo "Nom: " . $filiere->name . "\n";
            } else {
                echo "Relation filiere ne fonctionne pas. Filière non trouvée!\n";
            }

            // Vérifier si la relation niveau fonctionne
            echo "Vérification de la relation niveau...\n";
            $niveau = $classe->niveau;

            if ($niveau) {
                echo "Relation niveau fonctionne. Niveau trouvé:\n";
                echo "ID: " . $niveau->id . "\n";
                echo "Nom: " . $niveau->name . "\n";
            } else {
                echo "Relation niveau ne fonctionne pas. Niveau non trouvé!\n";
            }
        } else {
            echo "Relation classe ne fonctionne pas. Classe non trouvée!\n";
        }
    } else {
        echo "Emploi du temps non trouvé!\n";
    }

} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
