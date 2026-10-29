<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Informations de l'école
    |--------------------------------------------------------------------------
    */

    'name' => 'ÉCOLE SPÉCIALE DU BÂTIMENT ET DES TRAVAUX PUBLICS',
    'short_name' => 'ESBTP',
    'type' => 'Établissement d\'Enseignement Technique Supérieur Privé',
    'authorization' => '0123456789/MESRS/DGES/DESP',
    'address' => 'BP 2541 Yamoussoukro',
    'phone' => '+225 27 22 44 52 14',
    'email' => 'esbtp@aviso.ci',
    'website' => 'www.esbtp-ci.net',
    'logo' => 'images/LOGO-KLASSCI-PNG.png',

    /*
    |--------------------------------------------------------------------------
    | Paramètres des bulletins
    |--------------------------------------------------------------------------
    */
    'mentions' => [
        'Très Bien' => 16,
        'Bien' => 14,
        'Assez Bien' => 12,
        'Passable' => 10,
        'Insuffisant' => 0
    ],

    /*
    |--------------------------------------------------------------------------
    | Signatures requises
    |--------------------------------------------------------------------------
    */
    'signatures' => [
        'directeur des Etudes' => [
            'title' => 'Le Directeur des Etudes',
            'required' => true
        ]
    ]
];
