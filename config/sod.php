<?php

/**
 * Separation des devoirs (OHADA) : qui ne peut pas enchainer deux gestes.
 *
 * Chaque regle est independante et porte SON reglage d'instance. L'ecran des
 * reglages (onglet LMD, section « Separation des devoirs ») lit ce tableau pour
 * s'afficher : ajouter une regle ici l'expose, sans toucher a la vue. Les trois
 * cles sont semees par migration, faute de quoi le reglage n'existerait dans
 * aucune instance et l'ecran l'ignorerait en silence.
 *
 * `mode` n'est le defaut que tant que le reglage n'est pas pose en base. Ses
 * valeurs sont celles de `App\Enums\ModeSeparationDesDevoirs` : bloquant,
 * observation, inactif.
 *
 * Le defaut est OBSERVATION, et non bloquant. Ces trois regles n'ont jamais
 * rien empeche depuis leur ecriture — un chemin de configuration pointe les
 * rendait muettes. Les livrer bloquantes ferait basculer d'un coup six
 * instances en service d'« aucun controle » a « 403 » sur un geste quotidien,
 * sans que personne ait pu s'y preparer. En observation, l'ecole voit dans son
 * journal ce que la regle bloquerait, puis durcit quand elle peut.
 */
return [
    'bypass_permission' => 'sod.bypass',

    'rules' => [
        'lmd.jury.publish' => [
            'mode' => env('KLASSCI_SOD_LMD_JURY_PUBLISH', 'observation'),
            'setting' => 'lmd.sod.jury_publish_requires_distinct_pv_issuer',
            'message' => 'Separation des devoirs : l utilisateur qui a emis le PV ne peut pas publier ce jury.',
            'label' => 'Publier un jury : personne distincte de l’émetteur du PV',
            'hint' => 'Celui qui a émis le procès-verbal ne peut pas le publier lui-même.',
        ],
        'lmd.jury.rectify_pv' => [
            'mode' => env('KLASSCI_SOD_LMD_JURY_RECTIFY_PV', 'observation'),
            'setting' => 'lmd.sod.pv_rectification_requires_distinct_issuer',
            'message' => 'Separation des devoirs : l utilisateur qui a emis le PV precedent ne peut pas emettre le PV rectificatif.',
            'label' => 'Rectifier un PV : personne distincte de l’émetteur précédent',
            'hint' => 'Celui qui a émis le PV d’origine ne peut pas signer sa rectification.',
        ],
        'lmd.jury.generate_pv_after_publication' => [
            'mode' => env('KLASSCI_SOD_LMD_JURY_REISSUE_AFTER_PUBLICATION', 'observation'),
            'setting' => 'lmd.sod.pv_reissue_after_publication_requires_distinct_publisher',
            'message' => 'Separation des devoirs : l utilisateur qui a publie le jury ne peut pas reemettre le PV.',
            'label' => 'Réémettre un PV publié : personne distincte du publieur',
            'hint' => 'Celui qui a publié le jury ne peut pas réémettre son procès-verbal.',
        ],
    ],
];
