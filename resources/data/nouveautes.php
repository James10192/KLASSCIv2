<?php

/*
 * Contenu de la fenêtre « Nouveautés » affichée à la connexion.
 *
 * Une entrée par changement VISIBLE. Chaque entrée n'apparaît qu'aux comptes
 * qui ont au moins une des permissions listées (aucune liste = tout le monde) :
 * une caissière ne lit pas les nouveautés du jury, un étudiant ne lit pas
 * celles de la caisse. Un compte à qui aucune entrée ne s'adresse ne voit pas
 * la fenêtre du tout.
 *
 * Les captures se prennent avant le déploiement pour « avant », après pour
 * « après », au même cadrage (voir .claude/rules/changelog.md). Un fichier
 * modifié change de nom : les images sont servies avec un long cache.
 *
 * La version (clé whatsNew.vAAAA_MM_JJ) reste écrite dans le layout, où la
 * lit bin/verifier-fraicheur-nouveautes.php ; changer de version fait
 * réapparaître la fenêtre à tout le monde.
 */

return [
    'titre' => 'Septembre 2026',
    'entrees' => [
        [
            'titre' => 'Un écran d’encaissement refait',
            'icone' => 'fa-cash-register',
            'texte' => 'Au bureau, quatre étapes numérotées à gauche et, à droite, un panneau qui reste visible avec le reste à payer frais par frais et le bouton d’enregistrement. Sur téléphone, le récapitulatif s’affiche avant le choix du mode, et le premier chiffre tapé remplace le montant proposé.',
            'permissions' => ['paiements.create', 'paiements.create.non_cash'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-09/encaissement-bureau-avant.webp',
                'apres' => 'images/nouveautes/2026-09/encaissement-bureau-apres.webp',
                'format' => 'bureau',
                'legende' => 'Le reste à payer et le bouton d’enregistrement restent toujours visibles.',
            ],
        ],
        [
            'titre' => 'La liste des étudiants sur téléphone',
            'icone' => 'fa-mobile-screen',
            'texte' => 'Recherche en haut, quatre onglets (Tous, Inscrits, En cours, Sans inscription) et des lignes compactes. Une inscription en cours se valide depuis la ligne, et la liste se charge au fil du défilement.',
            'permissions' => ['students.view'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-09/etudiants-telephone-avant.webp',
                'apres' => 'images/nouveautes/2026-09/etudiants-telephone-apres.webp',
                'format' => 'telephone',
                'legende' => 'Des fiches en grille deviennent des lignes compactes.',
            ],
        ],
        [
            'titre' => 'Les listes se chargent au fil du défilement',
            'icone' => 'fa-arrows-down-to-line',
            'texte' => 'Inscriptions, paiements, relances, bulletins, journal de caisse : plus de pages à tourner. La suite arrive quand vous descendez, et vos filtres restent en place.',
            'permissions' => ['students.view', 'inscriptions.view', 'paiements.view'],
        ],
        [
            'titre' => 'Retrouver le rendez-vous d’une famille',
            'icone' => 'fa-calendar-check',
            'texte' => 'Une famille qui appelle sans connaître sa date se retrouve par son nom, son téléphone, la référence du dossier ou le matricule. Le résultat donne le jour, l’heure et les rendez-vous déjà manqués.',
            'permissions' => ['inscriptions.rdv.view'],
        ],
        [
            'titre' => 'Un accueil qui dit quoi faire',
            'icone' => 'fa-list-check',
            'texte' => 'L’accueil de la caisse et celui de la comptabilité montrent ce qui vous attend (paiements à valider, saisies encore annulables, reste à percevoir) et la tendance des derniers jours. Chaque chiffre ouvre la liste correspondante.',
            'permissions' => ['module.caisse.access', 'comptabilite.dashboard.view'],
        ],
        [
            'titre' => 'Annuler un versement sans le faire disparaître',
            'icone' => 'fa-rotate-left',
            'texte' => 'Le bouton « Annuler le versement » émet un avoir : le versement reste visible, compensé, avec votre motif. Juste après une erreur, l’agent qui a saisi peut aussi annuler lui-même sa saisie, selon les droits donnés par l’école.',
            'permissions' => ['paiements.create', 'paiements.validate'],
        ],
        [
            'titre' => 'Corriger une inscription déjà validée',
            'icone' => 'fa-pen-to-square',
            'texte' => 'Filière, niveau et classe se modifient encore après validation pour qui a le droit correspondant. Les frais sont recalculés, et chaque versement de la fiche d’inscription a ses propres boutons.',
            'permissions' => ['inscriptions.edit_validated'],
        ],
    ],
];
