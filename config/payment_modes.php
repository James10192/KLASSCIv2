<?php

/**
 * Catalogue des modes de paiement supportés (Côte d'Ivoire 2026).
 *
 * Source de vérité pour le mapping `mode_paiement` (DB) → libellé FR + icône.
 *
 * Le champ `mode_paiement` de `esbtp_paiements` est un VARCHAR libre.
 * Selon les flux d'écriture, on y trouve à la fois :
 *  - des valeurs snake_case venant des <select> du formulaire
 *    (ex: "especes", "cheque", "virement", "mobile_money", "carte")
 *  - des libellés FR mappés côté contrôleur
 *    (ex: "Espèces", "Chèque", "Virement bancaire", "Mobile Money")
 *  - parfois des variantes anciennes ("espece", "transfert")
 *
 * Le widget `paiements.by_mode` (Lot 14) normalise la valeur via
 * Str::slug(value, '_') et la cherche dans `aliases`. Si rien ne matche,
 * on retombe sur la valeur brute.
 *
 * Pour ajouter un nouveau mode :
 *  1. Ajouter une entrée dans `labels` (clé canonique en snake_case)
 *  2. Lister TOUS les variants connus en DB dans `aliases`
 *     (les valeurs sont les clés canoniques de `labels`)
 */

return [

    /**
     * Mapping clé canonique → métadonnées d'affichage.
     */
    'labels' => [
        'especes' => [
            'label' => 'Espèces',
            'icon' => 'fa-money-bill-wave',
        ],
        'cheque' => [
            'label' => 'Chèque',
            'icon' => 'fa-money-check',
        ],
        'virement' => [
            'label' => 'Virement bancaire',
            'icon' => 'fa-university',
        ],
        'carte' => [
            'label' => 'Carte bancaire',
            'icon' => 'fa-credit-card',
        ],
        'mobile_money' => [
            'label' => 'Mobile Money',
            'icon' => 'fa-mobile-alt',
        ],
        'orange_money' => [
            'label' => 'Orange Money',
            'icon' => 'fa-mobile-alt',
        ],
        'mtn_money' => [
            'label' => 'MTN Money',
            'icon' => 'fa-mobile-alt',
        ],
        'moov_money' => [
            'label' => 'Moov Money',
            'icon' => 'fa-mobile-alt',
        ],
        'wave' => [
            'label' => 'Wave',
            'icon' => 'fa-mobile-alt',
        ],
        'djamo' => [
            'label' => 'Djamo',
            'icon' => 'fa-mobile-alt',
        ],
        'autre' => [
            'label' => 'Autre',
            'icon' => 'fa-question-circle',
        ],
    ],

    /**
     * Alias : valeur brute (slugifiée snake_case) → clé canonique de `labels`.
     *
     * Couvre les variantes historiques rencontrées en DB :
     *  - libellés FR avec accents → "especes" (Str::slug normalise)
     *  - "espece" singulier (legacy ComptabiliteService)
     *  - "transfert" (legacy InscriptionPaiementController)
     *  - "virement_bancaire" / "carte_bancaire" (formes verbeuses)
     */
    'aliases' => [
        // Espèces
        'especes' => 'especes',
        'espece' => 'especes',
        'cash' => 'especes',
        'liquide' => 'especes',

        // Chèque
        'cheque' => 'cheque',

        // Virement
        'virement' => 'virement',
        'virement_bancaire' => 'virement',
        'transfert' => 'virement',
        'transfert_bancaire' => 'virement',

        // Carte
        'carte' => 'carte',
        'carte_bancaire' => 'carte',
        'cb' => 'carte',

        // Mobile Money (générique)
        'mobile_money' => 'mobile_money',
        'mobile' => 'mobile_money',
        'momo' => 'mobile_money',

        // Opérateurs spécifiques
        'orange_money' => 'orange_money',
        'orange' => 'orange_money',

        'mtn_money' => 'mtn_money',
        'mtn' => 'mtn_money',
        'mtn_momo' => 'mtn_money',

        'moov_money' => 'moov_money',
        'moov' => 'moov_money',

        'wave' => 'wave',
        'wave_money' => 'wave',

        'djamo' => 'djamo',

        // Catch-all
        'autre' => 'autre',
        'autres' => 'autre',
    ],

];
