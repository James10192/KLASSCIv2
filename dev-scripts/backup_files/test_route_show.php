<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Démarrer l'application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Obtenir un utilisateur admin pour le test
    $user = \App\Models\User::whereHas('roles', function($query) {
        $query->where('name', 'superAdmin');
    })->first();

    if (!$user) {
        echo "Erreur: Aucun utilisateur admin trouvé pour le test\n";
        exit(1);
    }

    // Connecter l'utilisateur
    \Illuminate\Support\Facades\Auth::login($user);
    echo "Utilisateur connecté: " . $user->name . " (ID: " . $user->id . ")\n";

    // Créer une requête pour la route emploi-temps.show
    $request = \Illuminate\Http\Request::create('/esbtp/emploi-temps/1', 'GET');

    // Définir l'utilisateur authentifié pour la requête
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // Traiter la requête
    echo "Traitement de la requête...\n";
    $response = $kernel->handle($request);

    // Vérifier si la réponse est réussie
    $statusCode = $response->getStatusCode();
    echo "Code de statut: " . $statusCode . "\n";

    // Si la réponse est réussie, vérifier si elle contient le contenu attendu
    if ($statusCode === 200) {
        $content = $response->getContent();

        // Vérifier si le contenu contient le message d'erreur concernant la variable $timeSlots non définie
        if (strpos($content, 'Undefined variable $timeSlots') !== false) {
            echo "Erreur: Variable \$timeSlots non définie\n";
        } else {
            echo "Succès: Pas d'erreur concernant la variable \$timeSlots\n";
        }

        // Vérifier si le contenu contient le conteneur de l'emploi du temps
        if (strpos($content, 'timetable-container') !== false) {
            echo "Succès: Conteneur de l'emploi du temps trouvé\n";
        } else {
            echo "Erreur: Conteneur de l'emploi du temps non trouvé\n";
        }

        // Vérifier si le contenu contient une erreur concernant la propriété "name" sur null
        if (strpos($content, 'Attempt to read property "name" on null') !== false) {
            echo "Erreur: Tentative de lecture de la propriété \"name\" sur null\n";
        } else {
            echo "Succès: Pas d'erreur concernant la lecture de propriété sur null\n";
        }
    } else {
        echo "Erreur: Échec du chargement de la page\n";

        // Afficher les en-têtes de la réponse
        echo "En-têtes de la réponse:\n";
        foreach ($response->headers->all() as $name => $values) {
            echo $name . ': ' . implode(', ', $values) . "\n";
        }

        // Afficher le contenu de la réponse
        echo "Contenu de la réponse:\n";
        echo $response->getContent() . "\n";
    }

} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
