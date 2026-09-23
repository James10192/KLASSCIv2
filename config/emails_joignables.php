<?php

/*
 * Reglages de la verification des adresses e-mail.
 *
 * Les LISTES (fautes connues, extensions fautives, messageries de reference,
 * noms reels voisins, domaines factices, extensions reservees, distance
 * maximale) vivent dans resources/data/domaines-suspects.json, copie octet
 * pour octet de `lib/email/domaines-suspects.json` du site vitrine
 * klassci-landing. Un test verifie son empreinte : les deux copies ne peuvent
 * pas diverger en silence.
 *
 * Ce fichier ne garde que ce qui est propre a KLASSCIv2.
 */

return [

    'fichier_domaines' => resource_path('data/domaines-suspects.json'),

    /*
     * Verification MX. Tolerante : si la resolution DNS elle-meme echoue
     * (serveur hors ligne), l'adresse n'est pas refusee pour autant.
     */
    'mx' => [
        'actif' => env('EMAILS_VERIFIER_MX', true),
        'cache_secondes' => 86400,
        // Domaine de controle : s'il ne se resout pas, c'est le reseau qui manque.
        'domaine_temoin' => 'gmail.com',
    ],
];
