<?php

/**
 * Chaque opération d'écriture de la CLI est-elle déclarée pour Nanan ?
 *
 *   php bin/verifier-couverture-nanan.php            # contrôle, code 1 si un écart
 *   php bin/verifier-couverture-nanan.php --a-apprendre   # liste ce qui reste à apprendre
 *
 * Pourquoi : chaque fois qu'une école demande une opération qu'on fait à la
 * main (une classe de plus, une UE à délier, une dette à effacer), Nanan doit
 * pouvoir la refaire seul la fois suivante. Une route d'écriture ajoutée à la
 * CLI sans être déclarée dans resources/data/nanan-couverture.php, c'est une
 * opération que personne n'a pensé à lui apprendre.
 *
 * Ne touche pas la base : la liste des routes et la configuration suffisent.
 * Il tourne donc en CI sans MySQL.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$registre = require __DIR__.'/../resources/data/nanan-couverture.php';
$outils = array_keys((array) config('chatbot.tools', []));
$types = ['nanan', 'hors_nanan', 'a_apprendre'];

$routes = [];
foreach (app('router')->getRoutes() as $route) {
    $uri = $route->uri();
    if (! str_starts_with($uri, 'api/cli/')) {
        continue;
    }
    foreach (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) as $methode) {
        $routes[$methode.' '.$uri] = true;
    }
}

$erreurs = [];
foreach (array_keys($routes) as $cle) {
    if (! isset($registre[$cle])) {
        $erreurs[] = "Route d'écriture non déclarée : {$cle}";
    }
}
foreach ($registre as $cle => $entree) {
    if (! isset($routes[$cle])) {
        $erreurs[] = "Entrée sans route (retirée ou renommée ?) : {$cle}";
        continue;
    }
    $type = is_array($entree) ? array_key_first($entree) : null;
    $valeur = is_array($entree) ? trim((string) reset($entree)) : '';
    if (! in_array($type, $types, true) || $valeur === '') {
        $erreurs[] = "Entrée mal formée : {$cle} (attendu 'nanan', 'hors_nanan' ou 'a_apprendre', avec une valeur)";
    } elseif ($type === 'nanan' && ! in_array($valeur, $outils, true)) {
        $erreurs[] = "Outil inconnu pour {$cle} : {$valeur} (absent de config/chatbot.php)";
    }
}

$compte = array_count_values(array_map(fn ($e) => is_array($e) ? array_key_first($e) : '?', $registre));
$resume = sprintf('%d routes d\'écriture : %d faites par Nanan, %d à lui apprendre, %d hors de sa portée.',
    count($routes), $compte['nanan'] ?? 0, $compte['a_apprendre'] ?? 0, $compte['hors_nanan'] ?? 0);

if (in_array('--a-apprendre', $argv, true)) {
    foreach ($registre as $cle => $entree) {
        if (is_array($entree) && array_key_first($entree) === 'a_apprendre') {
            echo "  {$cle}\n";
        }
    }
}

if ($erreurs !== []) {
    fwrite(STDERR, "\n  COUVERTURE NANAN — ".count($erreurs)." écart(s)\n\n");
    foreach ($erreurs as $e) {
        fwrite(STDERR, "    - {$e}\n");
    }
    fwrite(STDERR, "\n  Déclare chaque route dans resources/data/nanan-couverture.php.\n");
    fwrite(STDERR, "  Une opération d'école se termine par une action de Nanan : skill .claude/skills/nanan-autonomie.\n\n");
    exit(1);
}

echo $resume."\n";
exit(0);
