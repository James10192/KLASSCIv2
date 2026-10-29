<?php

/*
 * Les fichiers PHP donnes (chemins tels que git les enregistre) declarent-ils
 * un namespace et un nom de classe qui correspondent EXACTEMENT, casse
 * comprise, a leur chemin ?
 *
 * Sous Windows et macOS, le systeme de fichiers ignore la casse : un
 * `App\Services\Cli\X` range dans `app/Services/CLI/` se charge, et tous les
 * tests passent. En production (Linux), l'autoload PSR-4 ne le trouve pas et
 * la route rend 500. Ce controle voit l'ecart avant la production.
 *
 *   php bin/verifier-casse-psr4.php app/Foo/Bar.php tests/Baz/QuxTest.php
 */

$racines = ['app/' => 'App\\', 'tests/' => 'Tests\\', 'database/factories/' => 'Database\\Factories\\', 'database/seeders/' => 'Database\\Seeders\\'];
$ecarts = 0;

foreach (array_slice($argv, 1) as $fichier) {
    $fichier = str_replace('\\', '/', $fichier);
    $prefixe = null;
    foreach ($racines as $dossier => $espace) {
        if (str_starts_with($fichier, $dossier)) {
            $prefixe = [$dossier, $espace];
            break;
        }
    }
    if ($prefixe === null || ! str_ends_with($fichier, '.php') || ! is_file($fichier)) {
        continue;
    }

    $source = (string) file_get_contents($fichier);
    if (! preg_match('/^namespace\s+([^;]+);/m', $source, $m)) {
        continue;
    }

    $relatif = substr(dirname($fichier), strlen(rtrim($prefixe[0], '/')));
    $attendu = rtrim($prefixe[1].str_replace('/', '\\', ltrim($relatif, '/')), '\\');
    if ($m[1] !== $attendu) {
        echo "ECART DE CASSE : $fichier declare « {$m[1]} », son chemin impose « $attendu »\n";
        $ecarts++;
    }

    $classe = basename($fichier, '.php');
    if (preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*(class|trait|interface|enum)\s+(\w+)/m', $source, $c) && $c[2] !== $classe) {
        echo "ECART DE CASSE : $fichier declare « {$c[2]} », son nom de fichier impose « $classe »\n";
        $ecarts++;
    }
}

exit($ecarts === 0 ? 0 : 1);
