<?php

/*
 |--------------------------------------------------------------------------
 | Documentation publique KLASSCI
 |--------------------------------------------------------------------------
 |
 | Source-of-truth pour la table des matières publique de /docs.
 | Chaque entrée pointe vers un fichier markdown dans resources/docs/.
 | Le controller `Public\DocsController` expose ces articles à
 | l'URL /docs/{slug}, en parsant le markdown via league/commonmark.
 |
 | Structure :
 |   - sections : groupes principaux (Mise en route, Par rôle, Par module)
 |   - articles : indexés par slug pour résolution O(1)
 |   - chaque article : title, description, file (chemin relatif depuis
 |     resources/docs/), section, hero_image (optionnel), available
 |     (true = livré, false = placeholder grisé non cliquable)
 |
 */

return [

    'sections' => [
        'getting-started' => [
            'title' => 'Mise en route',
            'description' => "Premiers pas avec KLASSCI : présentation, installation, comptes initiaux, premier déploiement.",
        ],
        'roles' => [
            'title' => 'Par rôle',
            'description' => "Guides dédiés à chaque profil utilisateur. Choisissez votre rôle pour voir les écrans qui vous concernent.",
        ],
        'modules' => [
            'title' => 'Par module',
            'description' => "Documentation fonctionnelle par grand domaine : académique, financier, présences, communication.",
        ],
    ],

    'articles' => [

        // ─── Getting started ───
        'getting-started' => [
            'title' => 'Bienvenue sur KLASSCI',
            'description' => "Présentation de la plateforme, premiers pas, vocabulaire essentiel.",
            'file' => 'getting-started.md',
            'section' => 'getting-started',
            'hero_image' => 'images/landing/hero_section.png',
            'available' => true,
            'order' => 1,
        ],

        // ─── Par rôle ───
        'superadmin/onboarding' => [
            'title' => 'Super-administrateur — installation initiale',
            'description' => "Premier paramétrage : année universitaire, filières, niveaux, classes, frais, comptes du personnel.",
            'file' => 'superadmin/onboarding.md',
            'section' => 'roles',
            'role_label' => 'Super-administrateur',
            'available' => true,
            'order' => 1,
        ],
        'secretaire/inscriptions' => [
            'title' => 'Secrétaire — gérer les inscriptions',
            'description' => "Pré-inscription, validation, paiement initial, ré-inscription et gestion des classes pleines.",
            'file' => 'secretaire/inscriptions.md',
            'section' => 'roles',
            'role_label' => 'Secrétaire',
            'available' => true,
            'order' => 2,
        ],
        'enseignant/notes' => [
            'title' => 'Enseignant — saisir les notes',
            'description' => "Saisie en classe ou à distance, calcul automatique des moyennes, génération des bulletins.",
            'file' => 'enseignant/notes.md',
            'section' => 'roles',
            'role_label' => 'Enseignant',
            'available' => false,
            'order' => 3,
        ],
        'comptable/relances' => [
            'title' => 'Comptable — pilotage financier',
            'description' => "Suivi des paiements, génération des relances, situations financières, exports comptables.",
            'file' => 'comptable/relances.md',
            'section' => 'roles',
            'role_label' => 'Comptable',
            'available' => false,
            'order' => 4,
        ],
        'etudiant/bulletin' => [
            'title' => 'Étudiant — accéder à ses notes et bulletins',
            'description' => "Connexion étudiant, consultation des notes, téléchargement des bulletins, situation financière.",
            'file' => 'etudiant/bulletin.md',
            'section' => 'roles',
            'role_label' => 'Étudiant',
            'available' => false,
            'order' => 5,
        ],

        // ─── Par module ───
        'modules/lmd' => [
            'title' => 'Système LMD',
            'description' => "UE, ECUE, crédits ECTS, parcours, bulletins LMD conformes UEMOA, formules AQ/NAQ/APC.",
            'file' => 'modules/lmd.md',
            'section' => 'modules',
            'available' => false,
            'order' => 1,
        ],
        'modules/emploi-temps' => [
            'title' => 'Emploi du temps',
            'description' => "Planning général, séances de cours, émargement, vue par classe / par enseignant.",
            'file' => 'modules/emploi-temps.md',
            'section' => 'modules',
            'available' => false,
            'order' => 2,
        ],
        'modules/notes-bulletins' => [
            'title' => 'Notes et bulletins',
            'description' => "Évaluations, saisie, coefficients, moyennes, rangs, génération PDF, archivage.",
            'file' => 'modules/notes-bulletins.md',
            'section' => 'modules',
            'available' => false,
            'order' => 3,
        ],
        'modules/comptabilite' => [
            'title' => 'Comptabilité',
            'description' => "Frais, paiements, relances, dashboard financier, exports, intégration mobile money.",
            'file' => 'modules/comptabilite.md',
            'section' => 'modules',
            'available' => false,
            'order' => 4,
        ],
        'modules/presences' => [
            'title' => 'Présences',
            'description' => "Appel numérique, codes d'émargement, suivi par étudiant et par enseignant, rapports.",
            'file' => 'modules/presences.md',
            'section' => 'modules',
            'available' => false,
            'order' => 5,
        ],
        'modules/inscriptions' => [
            'title' => 'Inscriptions et ré-inscriptions',
            'description' => "Workflow complet, validation, paiement, blocage classes pleines, tronc commun, transferts.",
            'file' => 'modules/inscriptions.md',
            'section' => 'modules',
            'available' => false,
            'order' => 6,
        ],
    ],

    /*
     | Navigation prev/next : ordre canonique de lecture
     | (utilisé par DocsController::neighbours).
     */
    'reading_order' => [
        'getting-started',
        'superadmin/onboarding',
        'secretaire/inscriptions',
        'enseignant/notes',
        'comptable/relances',
        'etudiant/bulletin',
        'modules/lmd',
        'modules/emploi-temps',
        'modules/notes-bulletins',
        'modules/comptabilite',
        'modules/presences',
        'modules/inscriptions',
    ],

];
