#!/usr/bin/env php
<?php

/**
 * Le modal « Nouveautés » a-t-il pris du retard sur le CHANGELOG ?
 *
 * Il est resté figé sur mai 2026 pendant quatre mois. Personne ne l'a vu :
 * rien ne le signalait, et un modal périmé ne casse rien — il ment
 * simplement à l'école sur ce qu'elle vient de recevoir.
 *
 * Ce contrôle compare la version portée par le modal au mois le plus récent
 * du CHANGELOG. Il AVERTIT, il ne bloque pas : rafraîchir le modal est une
 * décision de communication, pas une contrainte technique, et bloquer chaque
 * livraison là-dessus serait vite contourné.
 *
 *   php bin/verifier-fraicheur-nouveautes.php
 *
 * Sortie 0 toujours en local ; --strict le fait sortir en 1 (utilisé nulle
 * part aujourd'hui, prévu si l'équipe veut durcir).
 */

$racine = dirname(__DIR__);
$strict = in_array('--strict', $argv, true);

$layout = $racine.'/resources/views/layouts/app.blade.php';
$changelog = $racine.'/CHANGELOG.md';

foreach ([$layout, $changelog] as $fichier) {
    if (! is_file($fichier)) {
        fwrite(STDERR, "Fichier introuvable : {$fichier}\n");
        exit(1);
    }
}

// La version du modal vit dans sa clé de préférence : whatsNew.vAAAA_MM_JJ
if (! preg_match('/whatsNew\.v(\d{4})_(\d{2})_(\d{2})/', file_get_contents($layout), $m)) {
    fwrite(STDERR, "Aucune clé de version trouvée dans le modal Nouveautés.\n");
    exit(1);
}
$versionModal = sprintf('%s-%s', $m[1], $m[2]);

$mois = [
    'janvier' => '01', 'février' => '02', 'fevrier' => '02', 'mars' => '03',
    'avril' => '04', 'mai' => '05', 'juin' => '06', 'juillet' => '07',
    'août' => '08', 'aout' => '08', 'septembre' => '09', 'octobre' => '10',
    'novembre' => '11', 'décembre' => '12', 'decembre' => '12',
];

if (! preg_match('/^##\s+([A-Za-zÀ-ÿ]+)\s+(\d{4})\s*$/mu', file_get_contents($changelog), $c)) {
    fwrite(STDERR, "Aucun mois trouvé dans le CHANGELOG.\n");
    exit(1);
}
$moisChangelog = $mois[mb_strtolower($c[1], 'UTF-8')] ?? null;
if ($moisChangelog === null) {
    fwrite(STDERR, "Mois non reconnu dans le CHANGELOG : {$c[1]}\n");
    exit(1);
}
$versionChangelog = sprintf('%s-%s', $c[2], $moisChangelog);

$ecart = (int) ((strtotime($versionChangelog.'-01') - strtotime($versionModal.'-01')) / 2629800);

if ($ecart <= 1) {
    echo "Modal Nouveautés à jour (modal {$versionModal}, changelog {$versionChangelog}).\n";
    exit(0);
}

$message = "Le modal Nouveautés date de {$versionModal} alors que le CHANGELOG va jusqu'à "
    ."{$versionChangelog} — {$ecart} mois d'écart. Les écoles ne voient pas ce qu'elles ont reçu depuis.";

// Format reconnu par GitHub Actions ; inoffensif ailleurs.
echo "::warning file=resources/views/layouts/app.blade.php::{$message}\n";

exit($strict ? 1 : 0);
