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
    'titre' => 'Novembre 2026',
    'entrees' => [
        [
            'titre' => 'Corriger plusieurs notes d’un étudiant d’un coup',
            'icone' => 'fa-table-cells',
            'texte' => 'Sur la fiche étudiant, partie académique, « Saisie multiple » ouvre la saisie directement dans le tableau des notes : changez plusieurs notes, une absence ou un commentaire, puis enregistrez tout en une fois, sans fenêtre successive. Chaque ligne indique si la note est validée ou encore en brouillon. Une note refusée garde votre saisie avec la raison, et une note modifiée entre-temps par un collègue vous est signalée au lieu d’être écrasée. Enregistrer ne valide pas officiellement les notes.',
            'permissions' => ['notes.create', 'notes.edit'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-11/notes-saisie-multiple-apres.webp',
                'format' => 'bureau',
                'legende' => 'Deux notes changées dans le tableau, un seul bouton pour tout enregistrer.',
            ],
        ],
        [
            'titre' => 'L’emploi du temps, un jour à la fois sur téléphone',
            'icone' => 'fa-mobile-screen',
            'texte' => 'Sur téléphone, l’emploi du temps ne défile plus en largeur : choisissez le jour, voyez ses séances avec l’heure, l’enseignant et la salle, et ajoutez une séance ce jour-là. Le jour courant s’ouvre d’office. L’ordinateur garde la grille complète.',
            'permissions' => ['timetables.view', 'timetables.view_all', 'timetables.edit'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-11/emploi-temps-jour-telephone-apres.webp',
                'format' => 'telephone',
                'legende' => 'Le lundi ouvert : une séance, son enseignant, sa salle, et l’ajout d’une séance ce jour-là.',
            ],
        ],
        [
            'titre' => 'Vos heures de cours, cours du soir compris',
            'icone' => 'fa-clock',
            'texte' => 'Paramètres, onglet Général, « Plage horaire des cours » : indiquez l’heure du premier créneau et la fin du dernier, jusqu’à 23 h. L’emploi du temps, la saisie des séances et les disponibilités des enseignants suivent. Une nouvelle évaluation propose aussi votre premier créneau.',
            'permissions' => ['system.manage'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-11/plage-horaire-reglages-apres.webp',
                'format' => 'bureau',
                'legende' => 'Deux heures suffisent ; une fin avant le début est refusée.',
            ],
        ],
        [
            'titre' => 'Planning et évaluations plus confortables',
            'icone' => 'fa-hand-pointer',
            'texte' => 'Sur téléphone, les cartes du planning général sont plus compactes. Sur ordinateur, le bouton « Enregistrer » d’une nouvelle évaluation reste en bas de l’écran. Annuler une évaluation se confirme dans une fenêtre claire, qui dit ce qui change pour les moyennes. Le bandeau de l’emploi du temps écrit « 1 séance », « 1 510 h » et le volume restant exact.',
            'permissions' => ['planning.manage', 'timetables.view', 'evaluations.create', 'evaluations.edit'],
        ],
        [
            'titre' => 'Une seule fenêtre à la fois',
            'icone' => 'fa-window-restore',
            'texte' => 'À l’ouverture d’une page, l’avis d’abonnement et les rappels ne s’empilent plus : un seul s’affiche, le suivant attend le prochain chargement.',
        ],
        [
            'titre' => 'Prévenir les étudiants par e-mail à chaque annonce',
            'icone' => 'fa-envelope-open-text',
            'texte' => 'En publiant une annonce, cochez l’envoi par e-mail : chaque étudiant ciblé est prévenu une seule fois, et vous pouvez limiter l’envoi aux inscrits de l’année. La fiche de l’annonce montre l’aperçu du courriel et le bilan de diffusion, adresse par adresse. Recherche, filtres et listes se mettent à jour sans recharger la page. En cas de doute sur un envoi, KLASSCI le met en attente au lieu de le renvoyer : personne ne reçoit le message deux fois.',
            'permissions' => ['annonces.create', 'annonces.edit', 'annonces.view'],
        ],
        [
            'titre' => 'Des reçus sur une seule feuille, avec la vraie heure',
            'icone' => 'fa-receipt',
            'texte' => 'Les exemplaires élève et caisse du reçu tiennent sur une seule feuille A4, avec la place pour la signature et le cachet. Reçus, liste des paiements et journal de caisse affichent l’heure réelle de saisie et de validation ; un ancien paiement indique « heure non conservée » au lieu de 00:00. Dans Comptabilité, Reçus et encaissements, choisissez d’imprimer le nom complet ou les initiales du caissier.',
            'permissions' => ['paiements.create', 'paiements.view', 'paiements.view_own'],
        ],
        [
            'titre' => 'Encaisser une dette d’une année passée',
            'icone' => 'fa-history',
            'texte' => 'Un étudiant qui doit encore une ancienne année se retrouve à la caisse, même sans nouvelle inscription : la recherche guide vers ses anciennes inscriptions. Le reliquat se règle depuis la fiche financière, et les montants déjà reportés ne sont plus comptés deux fois. Le suivi et les exports distinguent « Frais de cette année » et « Dettes anciennes », et chaque ligne exportée indique l’année d’inscription.',
            'permissions' => ['paiements.create', 'paiements.view', 'paiements.export'],
        ],
        [
            'titre' => 'Paiements : corriger, valider et filtrer sur place',
            'icone' => 'fa-money-check-alt',
            'texte' => 'La date réelle d’un versement se modifie à l’encaissement, sur ordinateur comme sur téléphone. Un paiement en attente se valide directement depuis la fiche d’inscription, et le mode de règlement se corrige depuis la fiche du versement. La liste des paiements gagne un filtre « Mode de règlement » et charge toutes les lignes en défilant ; l’export détaillé s’ouvre en aperçu jusqu’à 400 lignes.',
            'permissions' => ['paiements.create', 'paiements.validate', 'paiements.correct_mode', 'paiements.view'],
        ],
        [
            'titre' => 'Exonérations et réductions motivées',
            'icone' => 'fa-hand-holding-usd',
            'texte' => 'Une inscription gratuite se valide sans faux paiement dès que l’exonération est explicite, avec son motif et son auteur ; un tarif réellement configuré à 0 FCFA n’en demande pas. Les anciennes exonérations se reconfirment depuis la fiche. « Accorder une réduction » est disponible par inscription, avec son droit propre, distinct de la modification d’inscription.',
            'permissions' => ['inscriptions.validate', 'frais.souscriptions.ajuster'],
        ],
        [
            'titre' => 'Donner un accès à une seule personne',
            'icone' => 'fa-user-lock',
            'texte' => 'Personnel, Accès individuels : accordez une permission à une personne précise, pour une durée limitée ou sans limite, sans changer son rôle, puis retirez-la quand vous voulez. Les droits sur les classes sont plus fins : voir les étudiants, les listes d’appel ou complètes, exporter, imprimer une fiche de notes. Le menu n’affiche que ce que la personne peut ouvrir.',
            'permissions' => ['permissions.temporaires.manage', 'permissions.individuels.permanents.manage', 'personnel.manage'],
        ],
        [
            'titre' => 'Les notes d’un étudiant, regroupées par matière',
            'icone' => 'fa-book-open',
            'texte' => 'Sur la fiche étudiant et la fiche d’inscription, partie académique : évaluations et notes se saisissent et se corrigent dans des fenêtres centrées, sans quitter la fiche. Les notes sont regroupées par matière, ou par semestre, avec une recherche. Les résultats se consultent par inscription et par semestre, et le bulletin BTS ou LMD se régénère depuis la fiche.',
            'permissions' => ['students.view', 'notes.create', 'notes.edit', 'bulletins.generate'],
        ],
        [
            'titre' => 'Feuille de notes vierge à votre format',
            'icone' => 'fa-print',
            'texte' => 'Depuis la fenêtre des notes BTS, imprimez une feuille vierge avec jusqu’à 4 colonnes en A4 portrait ou 8 en paysage. Les réglages tiennent dans une barre compacte au-dessus de la grille. Les notes LMD reçoivent les mêmes filtres que le BTS (année, recherche, filière, niveau, statut), et une ancienne année peut être choisie pour terminer une saisie sans changer l’année courante.',
            'permissions' => ['notes.create', 'notes.edit', 'lmd.notes.manage'],
        ],
        [
            'titre' => 'Créer une évaluation pour un enseignant',
            'icone' => 'fa-chalkboard-teacher',
            'texte' => 'À la création d’une évaluation, la direction choisit l’enseignant concerné, y compris ceux qui ont un rôle personnalisé. Pour une classe LMD, KLASSCI renvoie vers les notes LMD. Activer ou publier plusieurs évaluations à la fois s’arrête à la première erreur et dit lesquelles ont réussi.',
            'permissions' => ['evaluations.create', 'evaluations.edit'],
        ],
        [
            'titre' => 'BTS tronc commun : résultats et rangs alignés',
            'icone' => 'fa-code-branch',
            'texte' => 'Résultats, fiche étudiant et bulletins montrent le semestre 1 dans la classe de tronc commun et le semestre 2 dans la spécialité. Les rangs imprimés concordent partout. Dans les paramètres des bulletins, l’école choisit la règle annuelle (S1 et S2, ou S2 seul) et le critère du conseil, dont « Les deux moyennes ». Un relevé provisoire est consultable avant la publication des bulletins.',
            'permissions' => ['bulletins.view', 'resultats.view', 'bulletins.configure'],
        ],
        [
            'titre' => 'Bulletins LMD : impression groupée et mise en page réglable',
            'icone' => 'fa-file-pdf',
            'texte' => 'Sélectionnez des bulletins LMD, ou tous ceux du filtre, et obtenez un seul PDF, jusqu’à 400. L’en-tête est plus compact, les polices, la hauteur du logo et les espacements se règlent, et les rubriques vides disparaissent. Nouveau réglage « Minimum UE pour APC », et le professeur de chaque élément vient du planning, avec plusieurs enseignants possibles par classe.',
            'permissions' => ['lmd.bulletins.view', 'lmd.bulletins.generate', 'lmd.bulletins.export.bulk'],
        ],
        [
            'titre' => 'Exporter la liste des enseignants',
            'icone' => 'fa-file-export',
            'texte' => 'Enseignants, Exporter : une seule fenêtre prépare le PDF ou l’Excel. Choisissez BTS, LMD ou les deux, les classes, filières, niveaux, matières et colonnes, puis l’un des documents : annuaire, saisies des bulletins, affectations par classe et semestre, répertoire par spécialisation ou matières regroupées.',
            'permissions' => ['teachers.view'],
        ],
        [
            'titre' => 'Invitations et connexion des étudiants et parents',
            'icone' => 'fa-key',
            'texte' => 'L’étudiant et ses parents reçoivent un lien sécurisé pour créer leur mot de passe, sans code temporaire, et la confirmation d’inscription ou de réinscription. Le dossier montre l’état et l’expiration du lien ainsi que l’historique des envois, sans jamais afficher le lien lui-même.',
            'permissions' => ['inscriptions.view', 'inscriptions.candidatures.view', 'students.view'],
        ],
        [
            'titre' => 'Effectifs, places et années mieux suivis',
            'icone' => 'fa-chart-bar',
            'texte' => 'Nouveau rapport des effectifs par classe, filtrable et exportable en PDF. Les places affichées correspondent à l’année du dossier ou de destination. La fiche étudiant propose la réinscription vers l’année suivante dès que l’année courante est échue. Le planning général peut reprendre les affectations d’enseignants de l’année précédente, sans écraser l’existant.',
            'permissions' => ['classes.view', 'inscriptions.edit', 'planning.manage'],
        ],
        [
            'titre' => 'Plus d’étudiants à l’écran pendant la saisie des notes',
            'icone' => 'fa-table',
            'texte' => 'La fenêtre des notes laisse la place aux étudiants : en BTS, l’en-tête d’une évaluation est plus court et les lignes sont plus compactes. Le suivi et les documents se déplient à la demande, et le suivi se referme quand il ouvre une saisie. Sur téléphone, les champs de note restent accessibles, « Fiche » et « Relevé » tiennent sur une ligne, et Nanan s’efface tant que la fenêtre est ouverte. Le suivi distingue aussi les notes complètes des matières sans évaluation.',
            'permissions' => ['notes.create', 'notes.edit', 'lmd.notes.manage'],
            'captures' => [
                'apres' => 'images/nouveautes/2026-10/notes-bts-bureau-apres.webp',
                'format' => 'bureau',
                'legende' => 'Sur un petit écran d’ordinateur, les cinq étudiants de la classe sont visibles sans défiler.',
            ],
        ],
        [
            'titre' => 'Des régularisations qui étaient des notes d’examen',
            'si' => 'requalification_examen',
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
