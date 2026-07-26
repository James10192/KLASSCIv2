<?php

return [
    'bypass_permission' => 'sod.bypass',

    'rules' => [
        'lmd.jury.publish' => [
            'enabled' => env('KLASSCI_SOD_LMD_JURY_PUBLISH', true),
            'setting' => 'lmd.sod.jury_publish_requires_distinct_pv_issuer',
            'message' => 'Separation des devoirs : l utilisateur qui a emis le PV ne peut pas publier ce jury.',
        ],
        'lmd.jury.rectify_pv' => [
            'enabled' => env('KLASSCI_SOD_LMD_JURY_RECTIFY_PV', true),
            'setting' => 'lmd.sod.pv_rectification_requires_distinct_issuer',
            'message' => 'Separation des devoirs : l utilisateur qui a emis le PV precedent ne peut pas emettre le PV rectificatif.',
        ],
        'lmd.jury.generate_pv_after_publication' => [
            'enabled' => env('KLASSCI_SOD_LMD_JURY_REISSUE_AFTER_PUBLICATION', true),
            'setting' => 'lmd.sod.pv_reissue_after_publication_requires_distinct_publisher',
            'message' => 'Separation des devoirs : l utilisateur qui a publie le jury ne peut pas reemettre le PV.',
        ],
    ],
];