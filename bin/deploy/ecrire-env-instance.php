<?php

/**
 * Au démarrage du conteneur : recopie dans le .env de l'école les variables
 * facultatives que Dokploy fournit sous le préfixe ECOLE_.
 *
 * Pourquoi un préfixe et pas les noms directs : une variable de compose
 * absente devient une chaîne VIDE dans le conteneur, et une chaîne vide
 * masque la ligne de .env (Laravel ne remplace jamais une variable déjà
 * définie). Une APP_KEY vide figée par un `config:cache` tapé dans le
 * terminal mettait toute l'école en 500 ; un DB_TIMEZONE vide aurait émis
 * `SET time_zone=''`. Ici, une valeur renseignée est écrite, une valeur vide
 * retire la ligne : Dokploy reste la source, et rien de vide n'est transmis.
 *
 * Usage : php bin/deploy/ecrire-env-instance.php <chemin du .env>
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Support\Installation\FichierEnv;

$chemin = $argv[1] ?? null;
if (! $chemin || ! is_file($chemin)) {
    fwrite(STDERR, "ecrire-env-instance : fichier .env introuvable ({$chemin}).\n");
    exit(1);
}

// Fuseau : posé au provisionnement, jamais changé ensuite (voir la rule
// adminklassci-tenant-management). Un fuseau à heure d'été figerait un
// décalage juste la moitié de l'année : refusé, comme tenant:provision.
$fuseau = getenv('APP_TIMEZONE') ?: 'UTC';
try {
    $zone = new DateTimeZone($fuseau);
} catch (Exception $e) {
    fwrite(STDERR, "FUSEAU « {$fuseau} » inconnu. Exemple : Africa/Abidjan, Africa/Porto-Novo.\n");
    exit(1);
}
$transitions = $zone->getTransitions(strtotime('-1 year'), strtotime('+1 year'));
if (count($transitions) > 1) {
    fwrite(STDERR, "FUSEAU « {$fuseau} » change d'heure en été : refusé. Choisissez un fuseau à décalage fixe.\n");
    exit(1);
}

$ecrire = [];
$retirer = [];
foreach (getenv() as $nom => $valeur) {
    if (strpos($nom, 'ECOLE_') !== 0) {
        continue;
    }
    $cle = substr($nom, strlen('ECOLE_'));
    if ($valeur === '') {
        $retirer[] = $cle;
    } else {
        $ecrire[$cle] = $valeur;
    }
}

$contenu = (string) file_get_contents($chemin);
foreach ($retirer as $cle) {
    $contenu = preg_replace('/^' . preg_quote($cle, '/') . '=.*\R?/m', '', $contenu);
}
file_put_contents($chemin, FichierEnv::ecrire($contenu, $ecrire));

echo 'Configuration de l\'école : ' . count($ecrire) . ' valeur(s) recopiée(s) depuis Dokploy.' . PHP_EOL;
