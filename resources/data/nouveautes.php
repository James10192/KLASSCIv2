<?php

/*
 * Contenu de la fenêtre « Nouveautés » affichée à la connexion.
 *
 * Une entrée par changement VISIBLE. `si` (facultatif) réserve une entrée aux
 * écoles où l'écran annoncé est ouvert : voir Nouveautes::disponible(). Chaque entrée n'apparaît qu'aux comptes
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
    'titre' => 'Octobre 2026',
    'entrees' => [
        [
            'titre' => 'Les résultats refaits, plus rapides et en couleur',
            'icone' => 'fa-chart-column',
            'texte' => 'La page Résultats se charge plus vite et la liste continue d’elle-même quand vous descendez, 50 élèves à la fois. La moyenne générale et le taux de réussite passent au vert, à l’orange ou au rouge selon leur état ; les repères se règlent dans les paramètres.',
            'permissions' => ['bulletins.view'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-10/resultats-bureau-avant.webp',
                'apres' => 'images/nouveautes/2026-10/resultats-bureau-apres.webp',
                'format' => 'bureau',
                'legende' => 'Les chiffres clés passent dans le bandeau, chacun avec son repère, et la moyenne comme la réussite disent d’un coup d’œil si tout va bien.',
            ],
        ],
        [
            'titre' => 'Aide : Nanan vous guide',
            'icone' => 'fa-life-ring',
            'si' => 'aide',
            'texte' => 'Le bouton « Aide » ouvre une conversation avec Nanan : un problème, une question « comment faire », une idée, ou le suivi de vos demandes. Si Nanan ne trouve pas, elle prépare la demande au support, capture de la page comprise, et vous êtes averti quand le support répond.',
            'captures' => [
                'apres' => 'images/nouveautes/2026-10/aide-nanan.webp',
                'format' => 'bureau',
                'legende' => 'Quatre choix, ou une question tapée directement.',
            ],
        ],
        [
            'titre' => 'Les réclamations de notes',
            'si' => 'reclamations',
            'icone' => 'fa-scale-balanced',
            'texte' => 'Un élève peut contester une note, avec la photo de sa copie. L’enseignant donne son avis, l’école tranche, et la note est corrigée ou maintenue avec un motif. Tout se suit sur une seule page.',
            'permissions' => ['notes.reclamations.traiter', 'identity.teach'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-10/reclamations-notes.webp',
                'format' => 'bureau',
                'legende' => 'Ce qui attend votre décision, ce qui attend l’enseignant, et ce qui a été tranché.',
            ],
        ],
        [
            'titre' => 'Contester une note',
            'si' => 'reclamations',
            'icone' => 'fa-scale-balanced',
            'texte' => 'Depuis « Mes notes » ou « Mes réclamations », vous pouvez contester une note en expliquant pourquoi, avec la photo de votre copie. Vous suivez la réponse au même endroit.',
            'permissions' => ['notes.reclamations.create_own'],
        ],
        [
            'titre' => 'Des notifications plus claires',
            'icone' => 'fa-bell',
            'texte' => 'La page des notifications montre d’abord ce qui vous attend, puis vos notifications regroupées par jour, avec des filtres en un clic. Marquer comme lu ou supprimer ne recharge plus la page.',
            'captures' => [
                'apres' => 'images/nouveautes/2026-10/notifications.webp',
                'format' => 'bureau',
                'legende' => 'Les compteurs du haut ouvrent la liste filtrée.',
            ],
        ],
        [
            'titre' => 'Nanan fait davantage pour vous',
            'icone' => 'fa-wand-magic-sparkles',
            'texte' => 'Sur simple demande, Nanan prépare et vous validez : valider des inscriptions, annuler un versement par un avoir, corriger une note, créer une évaluation, générer des bulletins, ajouter ou modifier des classes, préparer une année universitaire. Rien ne change sans votre clic sur « Valider ».',
            'permissions' => ['admin.access', 'identity.school_manager', 'identity.registrar', 'identity.direct_studies'],
        ],
        [
            'titre' => 'Les bulletins se génèrent même si vous quittez la page',
            'icone' => 'fa-file-pdf',
            'texte' => 'La génération des bulletins d’une classe et le PDF groupé continuent en arrière-plan. La progression dit combien d’élèves restent et le temps estimé ; une génération interrompue se relance depuis l’écran déjà rempli.',
            'permissions' => ['admin.access', 'identity.direct_studies', 'identity.registrar', 'identity.registrar_clerk'],
        ],
        [
            'titre' => 'Une fiche de réinscription qui dit quoi faire',
            'icone' => 'fa-user-check',
            'texte' => 'La fiche commence par le verdict (autorisée, bloquée, possible par dérogation, déjà inscrit) et les actions qui vont avec. La décision de passage se prend sur la moyenne annuelle du bulletin.',
            'permissions' => ['admin.access', 'identity.direct_studies', 'identity.registrar', 'identity.registrar_clerk', 'identity.enrollment_officer'],
        ],
        [
            'titre' => 'Choisir l’année d’une inscription',
            'icone' => 'fa-calendar-days',
            'texte' => '« Accepter et inscrire » et « Réinscrire » proposent l’année de l’inscription, places recomptées pour l’année choisie. L’année en cours s’affiche aussi dans la barre du haut, en orange si elle est terminée.',
            'permissions' => ['inscriptions.candidatures.view', 'reinscriptions.demandes.view'],
        ],
        [
            'titre' => 'Le lieu du rendez-vous est annoncé',
            'icone' => 'fa-location-dot',
            'texte' => 'Un nouveau réglage « Lieu du rendez-vous » s’imprime sur la convocation et part dans l’e-mail et le WhatsApp envoyés aux familles.',
            'permissions' => ['inscriptions.rdv.manage'],
        ],
        [
            'titre' => 'Des pages plus rapides',
            'icone' => 'fa-gauge-high',
            'texte' => 'Feuilles de style gardées par le navigateur, listes des étudiants, classes, relances et fiche étudiant allégées : la plupart des pages s’ouvrent nettement plus vite, sans rien changer à ce qu’elles affichent.',
        ],
    ],
];
