<?php

/*
 * La connexion que Laravel ouvrira pour les tests est-elle bien l'instance
 * MariaDB isolee demarree par scripts/ci-local.sh ? Appele juste avant tout
 * `migrate:fresh` : si la configuration en cache, un .env.testing (port 3306)
 * ou une variable oubliee pointait ailleurs, la base partagee du poste serait
 * videe.
 *
 *   php bin/verifier-base-isolee.php <port attendu> <datadir attendu> <base attendue>
 *
 * Code de sortie 0 seulement si le port, le repertoire de donnees ET le nom de
 * la base correspondent, lus sur le serveur lui-meme (pas dans la config).
 */

[$port, $datadir, $base] = array_slice($argv, 1) + [null, null, null];
if ($port === null || $datadir === null || $base === null) {
    fwrite(STDERR, "usage : verifier-base-isolee.php <port> <datadir> <base>\n");
    exit(2);
}

$normaliser = static function (string $chemin): string {
    $reel = realpath($chemin);
    $chemin = str_replace('\\', '/', $reel === false ? $chemin : $reel);
    $chemin = rtrim($chemin, '/');

    return PHP_OS_FAMILY === 'Windows' ? strtolower($chemin) : $chemin;
};

// Toute exception (demarrage de Laravel compris) est un refus : sans reponse
// certaine, aucune migration ne part.
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $ligne = $app['db']->connection()->selectOne('SELECT @@port AS port, @@datadir AS datadir, DATABASE() AS base');
} catch (Throwable $e) {
    fwrite(STDERR, 'VERIFICATION IMPOSSIBLE : '.get_class($e).' : '.$e->getMessage()."\n");
    exit(1);
}

$ecarts = [];
if ((string) $ligne->port !== (string) $port) {
    $ecarts[] = "port {$ligne->port} au lieu de $port";
}
if ($normaliser((string) $ligne->datadir) !== $normaliser($datadir)) {
    $ecarts[] = "datadir {$ligne->datadir} au lieu de $datadir";
}
if ((string) $ligne->base !== $base) {
    $ecarts[] = "base {$ligne->base} au lieu de $base";
}

if ($ecarts !== []) {
    fwrite(STDERR, 'CONNEXION HORS DE LA BASE ISOLEE : '.implode(' ; ', $ecarts)."\n");
    exit(1);
}

echo "  connexion verifiee : port {$ligne->port}, base {$ligne->base}, datadir isole\n";
exit(0);
