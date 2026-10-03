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
            'titre' => 'Des régularisations qui étaient des notes d’examen',
            'icone' => 'fa-exchange-alt',
            'texte' => 'Dans la fenêtre des notes LMD d’une classe, un bandeau signale les évaluations saisies en « Régularisation ». Si c’étaient les notes d’examen d’un semestre, choisissez ce semestre, regardez ce qui change, puis « Requalifier en examen » : elles s’appellent désormais « Examen … », aucune note ne bouge, et le contrôle continu reste distinct.',
            'permissions' => ['lmd.notes.manage'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-10/requalification-examen-avant.webp',
                'apres' => 'images/nouveautes/2026-10/requalification-examen-apres.webp',
                'format' => 'bureau',
                'legende' => 'Le bandeau dit combien d’évaluations sont concernées par semestre et montre chaque titre avant et après.',
            ],
        ],
        [
            'titre' => 'Le rang d’une UE au bulletin, parcours par parcours',
            'icone' => 'fa-sort-numeric-down',
            'texte' => 'Dans « Lier à des parcours » (Unités d’enseignement LMD), chaque parcours coché a son champ « Rang au bulletin ». Numérotez les UE du semestre dans l’ordre du bulletin officiel : une même UE peut avoir un rang différent dans deux parcours.',
            'permissions' => ['lmd.structure.manage'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-10/rang-ue-bulletin-avant.webp',
                'apres' => 'images/nouveautes/2026-10/rang-ue-bulletin-apres.webp',
                'format' => 'bureau',
                'legende' => 'Le rang se règle à côté des semestres de chaque parcours.',
            ],
        ],
        [
            'titre' => 'La pondération contrôle continu / examen, si vous la choisissez',
            'icone' => 'fa-balance-scale',
            'texte' => 'Paramètres, onglet LMD : cochez « Appliquer la pondération à la moyenne des ECUE » pour que la moyenne d’un élément soit, par exemple, 40 % du contrôle continu et 60 % de l’examen. Une absence à l’examen compte 0. Décochée, rien ne change. Après l’avoir cochée, régénérez les bulletins.',
            'permissions' => ['system.manage'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-10/ponderation-cc-examen-apres.webp',
                'format' => 'bureau',
                'legende' => 'La case active la répartition saisie juste en dessous.',
            ],
        ],
        [
            'titre' => 'Retirer un ECUE ne le fait plus passer en BTS',
            'icone' => 'fa-layer-group',
            'texte' => 'Quand vous retirez un élément de la dernière maquette qui le contient (Unités d’enseignement LMD), KLASSCI vous demande ce qu’il devient : le supprimer s’il n’a jamais servi, l’archiver dans le LMD avec son historique, ou, seulement si vous le choisissez, en faire une matière BTS. Il ne retombe plus de lui-même dans les listes de notes et de bulletins BTS.',
            'permissions' => ['lmd.structure.delete'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-10/retrait-ecue-apres.webp',
                'format' => 'bureau',
                'legende' => 'Un élément qui a déjà servi ne peut pas être supprimé : KLASSCI conseille de l’archiver.',
            ],
        ],
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
            'titre' => 'Le statut redoublant, confirmé par une personne',
            'icone' => 'fa-redo-alt',
            'texte' => 'KLASSCI déduit toujours si un élève redouble, d’après son niveau de l’an dernier, mais une personne le confirme désormais : à la réinscription, sur la fiche d’inscription, ou en masse depuis la liste filtrée « Statut redoublant à confirmer ». Changer la valeur proposée demande un motif, et une contradiction avec la décision de réinscription est signalée.',
            'permissions' => ['inscriptions.redoublant.confirm'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-10/redoublant-fiche-avant.webp',
                'apres' => 'images/nouveautes/2026-10/redoublant-fiche-apres.webp',
                'format' => 'bureau',
                'legende' => 'La fiche dit d’où vient le statut, signale la contradiction, et se confirme ou se corrige en un clic.',
            ],
        ],
        [
            'titre' => '« Redoublant ? » à chaque inscription',
            'icone' => 'fa-user-check',
            'texte' => 'La question se pose aussi au formulaire « Nouvelle inscription », dans « Accepter et inscrire » d’une candidature et dans « Réinscrire » d’une demande en ligne. Pour un nouvel élève, KLASSCI propose « Non » : répondez « Oui » s’il redouble en venant d’un autre établissement, en disant pourquoi.',
            'permissions' => ['inscriptions.redoublant.confirm'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-10/redoublant-creation-avant.webp',
                'apres' => 'images/nouveautes/2026-10/redoublant-creation-apres.webp',
                'format' => 'bureau',
                'legende' => 'La nouvelle inscription demande si l’élève redouble, et le motif seulement quand vous changez la réponse proposée.',
            ],
        ],
        [
            'titre' => 'Modifier les classes à la chaîne, sans attendre',
            'icone' => 'fa-chalkboard',
            'texte' => 'La fenêtre de modification d’une classe s’ouvre aussitôt et se présente plus clairement. « Enregistrer et modifier la suivante » passe directement à la classe d’après, et Ctrl+Entrée enregistre.',
            'permissions' => ['classes.edit'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-10/classes-fenetre-avant.webp',
                'apres' => 'images/nouveautes/2026-10/classes-fenetre-apres.webp',
                'format' => 'bureau',
                'legende' => 'Une fenêtre plus lisible, et un bouton pour enchaîner sur la classe suivante.',
            ],
        ],
        [
            'titre' => 'L’étape suivante ne se confond plus avec une confirmation',
            'icone' => 'fa-route',
            'texte' => 'Après une action, KLASSCI vous indique l’étape suivante sans vous laisser croire qu’elle est faite. Si elle se fait sur la page où vous êtes, un bandeau discret vous le dit. Sinon, une fenêtre « Et maintenant ? » propose d’ouvrir la page. Les messages ne gardent plus les étapes déjà faites par un collègue.',
            'permissions' => ['paiements.create', 'paiements.validate', 'inscriptions.validate'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-10/etape-suivante-apres.webp',
                'format' => 'bureau',
                'legende' => 'Après la validation du paiement : l’étape suivante attend sur sa page, rien n’est fait à votre place.',
            ],
        ],
        [
            'titre' => 'Deux corrections d’affichage dans les inscriptions',
            'icone' => 'fa-wand-magic-sparkles',
            'texte' => 'Dans le panneau d’une demande en ligne, le bouton « J’ai joint la famille : confirmer le contact » n’est plus coupé : il passe à la ligne. Sur la fiche d’une inscription, le type s’écrit « Première inscription » au lieu de « Première_inscription ».',
            'permissions' => ['inscriptions.candidatures.view', 'inscriptions.view'],
            'captures' => [
                'avant' => 'images/nouveautes/2026-10/demandes-encart-avant-v2.webp',
                'apres' => 'images/nouveautes/2026-10/demandes-encart-apres-v2.webp',
                'format' => 'telephone',
                'legende' => 'Le bouton de confirmation du contact se lit en entier.',
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
            'texte' => 'Sur simple demande, Nanan prépare et vous validez : valider des inscriptions, annuler un versement par un avoir, corriger une note, créer une évaluation, générer des bulletins, ajouter ou modifier des classes, préparer une année universitaire, requalifier des régularisations en examen, modifier ou retirer un élément d’une maquette LMD. Rien ne change sans votre clic sur « Valider ».',
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
