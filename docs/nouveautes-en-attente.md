# Annonces dues aux écoles


Chaque ligne commençant par `- ` est un changement visible déjà fusionné, dont
l'entrée de la fenêtre « Nouveautés » (`resources/data/nouveautes.php`) et le
changelog public (klassci-landing, FR + EN) restent à écrire.

On ajoute la ligne dans la PR de la fonctionnalité ; on la retire dans celle qui
écrit l'entrée, captures avant / après prises sur presentation. Tant qu'une
ligne reste ouverte, `git push origin presentation:<école>` est refusé
(`.githooks/pre-push`, via `bin/garde-nouveautes.sh registre`).

Format : `- AAAA-MM-JJ · #PR · ce que l'école voit de changé`

## Ouvertes
- 2026-10-15 · #1600 · Paiements : heure de saisie réelle sur le détail, réglage « Encaissé par » nom complet ou initiales dans Comptabilité > Reçus et encaissements et sur les PDF. Audit complémentaire de l'heure de validation et des autres écrans dans #1599.
- 2026-10-15 · #1596 · Détail d'un paiement : le menu « Reçu PDF » et ses actions restent visibles devant les cartes en dessous, sur ordinateur comme sur tablette. Vérification de présentation nécessaire avant propagation.
- 2026-10-14 · #1595 · Fiches étudiants/inscriptions : notes regroupées par matière, consultables également par semestre ou en liste, avec recherche et affichage de la matière dans la modale ; vue conservée après saisie sans rechargement. Recette visuelle requise avant propagation.
- 2026-10-14 · #1594 · Fiches étudiant et inscription : les fenêtres de saisie d'évaluation et de consultation de documents s'ouvrent au centre de l'écran, avec taille adaptée au mobile et sans changer les actions des formulaires. Capture de recette nécessaire avant propagation.
- 2026-10-14 · #1592 · Maquettes et emplois du temps BTS/LMD : changements S1/S2/Annuel, filtres rapides et destination de copie cohérents ; programmation LMD avec message de reprise en cas d'erreur, conflits de génération affichés sans altérer les libellés. Capture et recette de présentation requises avant propagation.
- 2026-10-14 · #1589 · Fiches étudiant et inscription : évaluations et notes accessibles dans la partie académique sans grand bandeau, chargement à l'ouverture ; résultats et modales plus compacts sur écran large. Recette visuelle nécessaire avant propagation.
- 2026-10-14 · #1590 · Fiche étudiant > Académique et fiche inscription : le gestionnaire des notes s'intègre sans grand bandeau secondaire et se charge à l'ouverture de l'onglet ; suppression empêchée pour les examens ou fiches de notes verrouillés.
- 2026-10-14 · #1591 · Fiche inscription : le bandeau des informations du compte étudiant reste affichable avec le parcours sécurisé récent, sans exiger ni exposer de mot de passe temporaire.
- 2026-10-13 · #1585 · Fiche étudiant > Académique et fiche inscription : gestion des évaluations et notes en fenêtres AJAX (saisie, correction, suppression, publication), accès aux bulletins/relevés, permissions adaptées au rôle.
- 2026-10-13 · #1577 · Étudiants et parents : confirmation de première inscription et de réinscription distribuée à chacun ; les liens de réinitialisation restent communs même si seul le parent a une adresse mail ou si plusieurs enfants partagent le même contact. Recette d'envoi sur présentation avant propagation.
- 2026-10-13 · #1575 · Connexion et fiche étudiant : invitation par lien sécurisé reçue par l'étudiant et ses parents liés, réinitialisation sans code temporaire, nouvelle connexion par e-mail + OTP et écrans de récupération premium. Vérifier les captures et les envois réels sur présentation avant propagation.
- 2026-10-12 · #1573 · Enseignants > Exporter : choisissez BTS, LMD ou les deux, puis les classes, filières, niveaux, matières typées, UE et parcours LMD. Les noms et matières homonymes sont séparés par système/maquette jusque dans les PDF et Excel.
- 2026-10-12 · #1572 · Paiements : correction du mode de règlement accessible depuis la fiche du versement avec droit dédié ; modal et fond Bootstrap stabilisés dans la liste pendant les rafraîchissements AJAX.
- 2026-10-12 · #1570 · Enseignants > Exporter : choix des colonnes, rubriques par matière/classe/niveau/filière, nouvelle liste épurée et orientation A4 portrait/paysage automatique ; Excel reprend exactement les colonnes et les rubriques choisies.
- 2026-10-12 · #1569 · Bulletins BTS : les rangs annuels et semestriels affichés dans les PDF concordent avec Résultats et la fiche étudiant, même si d'anciens bulletins de camarades ont des moyennes périmées.
- 2026-10-12 · #1568 · Enseignants > Exporter : choisir plusieurs classes, filières, niveaux d'études et matières avec recherche, sans rechargement ; les PDF/Excel respectent le périmètre croisé demandé.
- 2026-10-12 · #1567 · Enseignants > Exporter : deux nouveaux documents « Répertoire par spécialisation » et « Matières regroupées » en PDF/Excel, avec professeurs, classes et semestres, et annexe des noms à classer.
- 2026-10-12 · #1566 · BTS tronc commun : dans Résultats et sur les bulletins, S1 origine + S2 spécialisation, rang annuel de la classe actuelle (ou classe d'origine si choisi dans Paramètres), cohorte annuelle exclusive et liens de bulletins exacts.
- 2026-10-12 · #1565 · Fiche étudiant > Académique : affichage du S1 en tronc commun et du S2 en spécialité avec la classe d'origine, le bon bilan annuel et le statut « moyenne partielle » si incomplet.
- 2026-10-12 · #1564 · Paramètres > Bulletins > Conseil BTS 1 : nouveau critère « Les deux moyennes » (S2 et annuelle au seuil), décision auto et réinscription cohérentes, résultat incomplet à vérifier.
- 2026-10-12 · #1561 · État financier toutes années : les inscriptions terminées restent dans le suivi et les reliquats reportés sont ventilés sans double compter les créances.
- 2026-10-12 · #1561 · Recette caisse : test un paiement d'aujourd'hui sur une ancienne inscription (sans inclure le paiement d'hier sur l'année courante) ; raccourci frais seulement quand la section existe.
- 2026-10-12 · #1561 · Tests d'encaissement sur dossier achevé alignés sur la valeur ENUM officielle `terminée` ; la validation accepte aussi l'orthographe legacy `terminee` sans ouvrir les inscriptions annulées.
- 2026-10-12 · #1561 · Tests HTTP : une ancienne inscription au montant entièrement reporté ne propose plus de frais direct dans les fenêtres de paiement ; l'ancien flux de validation ne peut pas rouvrir son workflow.
- 2026-10-12 · #1561 · Fiche étudiant : les cartes d'anciennes inscriptions distinguent le solde directement encaissable de la part transférée en reliquat, sans la présenter comme une inscription soldée.
- 2026-10-12 · #1561 · Fiche étudiant : le paiement d'une ancienne inscription utilise en AJAX paiements.store sans modifier le workflow académique ; une réponse serveur 422 ne produit plus un faux toast de succès et les montants inhabituels demandent confirmation.
- 2026-10-12 · #1561 · Fenêtre de paiement des inscriptions : même calcul de frais restants que la caisse (reports déduits), interdiction de remettre une ancienne inscription en validation par un paiement, et vérification serveur du mode autorisé.
- 2026-10-12 · #1561 · CI élargie aux scénarios de répartition avec dette reportée et à l'ordre d'affichage/confidentialité des finances de la fiche étudiant.
- 2026-10-12 · #1561 · Garde serveur anti-double règlement : sur une ancienne inscription avec report, un versement supérieur au solde non reporté ou une ventilation sur le montant transféré est refusé ; seuls les frais restant à l'origine sont encaissables.
- 2026-10-12 · #1561 · Répartition de caisse : les montants déjà transférés en reliquat ne sont plus comptés comme encore dus sur le frais de l'inscription source.
- 2026-10-12 · #1561 · Protection comptable côté serveur : la clôture administrative d'une ancienne inscription ne bloque pas son règlement, mais une inscription annulée refuse tout encaissement (HTTP 422) même via URL directe.
- 2026-10-12 · #1561 · Caisse : une inscription d'année achevée (« terminée ») reste retrouvable pour solder son impayé sans réinscription ; les inscriptions annulées restent exclues.
- 2026-10-12 · #1561 · Tableau PDF des paiements : l'année de l'inscription est visible sur chaque ligne, y compris pour une dette ancienne encaissée aujourd'hui.
- 2026-10-12 · #1561 · Export détaillé PDF/Excel : une colonne indique l’année d’inscription sur chaque paiement et distingue ainsi une dette ancienne encaissée aujourd’hui des frais de l’année.
- 2026-10-12 · #1561 · Export détaillé des paiements : sélectionner toutes les années, l’année courante ou les anciennes dettes ; dates d’encaissement indépendantes et même filtre que le journal de caisse.
- 2026-10-12 · #1561 · Encaissement : lorsqu'aucune inscription courante n'est trouvée, recherche guidée vers les anciennes inscriptions, nom d'utilisateur inclus (bureau/mobile), sans inscription fictive.
- 2026-10-12 · #1561 · Exports des versements PDF, Excel/CSV et SAARI : affichage explicite de l'origine des inscriptions sélectionnées et de la période de paiement, identique à la liste.
- 2026-10-12 · #1561 · État financier PDF : choisir une situation cumulée de l'année courante ou de toutes les inscriptions, sans appliquer silencieusement les dates du journal de caisse (bureau et mobile).
- 2026-10-12 · #1561 · Paiements > Suivi : raccourcis Aujourd'hui, 30 jours, toutes dates sur toutes années, sans confondre date de caisse et année de la dette (bureau et mobile).
- 2026-10-12 · #1561 · Fiche étudiant > Finances : année de référence et frais de l'inscription affichés en premier, situation multi-années consultable ensuite sans perdre les anciennes inscriptions.
- 2026-10-12 · Caisse et suivi des paiements : les dettes anciennes peuvent être payées même sans nouvelle inscription. Séparez « Tous les encaissements », « Frais de cette année » et « Dettes anciennes » pour éviter de confondre paiements et nouveaux inscrits.

- 2026-10-11 · Caisse : régler un reliquat depuis la fiche financière ou « Encaisser un paiement » ; modal premium avec montant disponible, paiements en attente, choix du mode autorisé et confirmation AJAX, sans mélanger la nouvelle scolarité.
- 2026-10-11 · Réinscription : permission d'établissement pour autoriser un report de dette, avec contrôle serveur et choix explicite, sans droit d'abandon implicite.
- 2026-10-11 · #1556 · Enseignants : export PDF/Excel préparé dans une seule fenêtre premium avec aperçu, téléchargement et validation visuelle des paramètres obligatoires, sans filtres dispersés sur la liste.
- 2026-10-11 · #1555 · Enseignants : filtres et préparation des documents en sélecteurs premium KLASSCI, avec recherche de classe/spécialité et conservation des choix pour le PDF et Excel.
- 2026-10-11 · #1554 · Enseignants : quatre documents PDF distincts pour retrouver les enseignants enregistrés, les saisies des bulletins, les affectations par classe/semestre et une synthèse de pilotage ; suppression des coordonnées factices à l'impression.
- 2026-10-11 · #1552 · Enseignants : aperçu et téléchargement PDF sans retour silencieux à la liste en cas de données volumineuses ; détail imprimable par classe et semestre, message d'erreur explicite si les paramètres sont invalides.
- 2026-10-11 · #1551 · Enseignants : menu d'export visible, choix de l'année universitaire et PDF/Excel intégrant les professeurs ajoutés manuellement sur les bulletins BTS, avec détail par classe, matière et semestre et couleurs lisibles.
- 2026-10-09 · #1539 · Familles : espace personnel limité et registre de vérification des habilitations, sans accès implicite aux notes ni aux finances. Nécessite activation par settings et compte parent distinct déjà créé.
- 2026-10-09 · #1540 · Inscriptions > Invitations : file sécurisée persistante (à activer dans les settings après vérification du cron), reprise des envois MailPulse sans régénérer de lien et protection des liens expirés.
- 2026-10-09 · #1536 · Comptes étudiants : identifiants temporaires individuels renforcés pour le parcours historique, changement imposé à la première connexion ; expiration du lien contrôlée dans la transaction avant activation.
- 2026-10-09 · #1540 · Communication > MailPulse : ajouter au centre des envois la synthèse des invitations d'activation étudiantes (7 jours), sans exposer les identifiants et liens des étudiants.
- 2026-10-09 · #1540 · Dossier de candidature > Espace étudiant : historique des tentatives d'invitation e-mail et WhatsApp, états acceptés/en attente/échec, sans exposer les liens d'activation.
- 2026-10-09 · #1540 · Communication > MailPulse : synthèse en lecture seule de l'outbox des notifications parents des sept derniers jours, avec distinction entre acceptation fournisseur, livraison enregistrée et échecs.
- 2026-10-09 · #1536 · Dossier de candidature > Espace étudiant : statut et expiration du lien d'activation visibles, avec messages de renvoi précisant que la réception n'est pas encore confirmée.
- 2026-10-09 · #1535 · Enseignants : export de l'annuaire des professeurs en PDF (aperçu et téléchargement) ou Excel, avec filtres de recherche, statut et spécialisation et présentation officielle de l'établissement.
- 2026-10-09 · #1535 · Planning général BTS : reprendre les affectations d'enseignants et volumes horaires d'une année précédente dans les configurations manquantes de la nouvelle année, avec aperçu et confirmation, sans écraser les données existantes.
- 2026-10-08 · Fiche étudiant / Inscriptions : action « Accorder une réduction » par inscription, avec motif prérempli, récapitulatif, vérification des paiements et permission financière frais.souscriptions.ajuster (distincte de inscriptions.edit).
- 2026-10-08 · Fiche étudiant > Académique : les liens résultats sont intégrés aux blocs académiques existants (année de référence et archives BTS/LMD), sans nouvelle carte.


- 2026-10-08 · #1499 · Bulletin LMD : statut établissement masquable ; le bandeau Code / Statut / Direction disparaît complètement lorsqu’il ne contient aucun élément visible.

- 2026-10-07 · #1444 · LMD : nouveau réglage « Minimum UE pour APC » ; une UE sous ce plancher reste NAQ même si la moyenne générale permet la compensation
- 2026-10-07 · #1441 · bulletin LMD : pied de page duplicata / identité établissement élargi et largeur configurable ; le pays imprimé vient désormais des paramètres de l'école
- 2026-10-07 · #1439 · fiche étudiant : si l'année courante est déjà échue, la bannière et la carte Inscriptions proposent la réinscription vers l'année suivante sans attendre la bascule de l'année courante ni un rendez-vous
- 2026-10-07 · #1431 · fiche étudiant > Finances : « Enregistrer un paiement » ouvre aussi la modale sur une ancienne inscription qui reste à solder, même sans inscription courante
- 2026-10-06 · #1429 · BTS : regle annuelle configurable apres tronc commun (S1+S2 ou S2 specialite), Resultats/bulletin/certificat/reinscription alignes, saisie historique et PDFs de la fiche etudiant attaches au bon eleve
- 2026-10-06 · #1427 · notes BTS/LMD et évaluations : choisir localement une ancienne année pour terminer la saisie et la clôture, sans changer l’année courante de KLASSCI
- 2026-10-03 · #1397 · suivi des notes des classes LMD par semestre : panneau dans la fenêtre des notes LMD et sur la génération des bulletins LMD, Nanan dit ce qui manque
- 2026-10-03 · #1399 · enseignants LMD : évaluations et bulletins reprennent le professeur du planning, avec affectation rapide et écran de configuration par semestre
- 2026-10-04 · #1400 · professeurs LMD par classe : plusieurs enseignants possibles dans le planning, résolution automatique par classe, conflit détecté puis confirmation/harmonisation sur évaluations, séances et bulletins
- 2026-10-04 · #1401 · bulletin LMD : le nom de l'établissement, le titre du document et les métadonnées d'en-tête reprennent exactement la couleur de texte choisie dans les paramètres PDF
- 2026-10-05 · #1407 · Nanan gère les enseignants et professeurs LMD ainsi que les rendez-vous ciblés : programmer, reprogrammer, annuler, fermer ou rouvrir un créneau, renvoyer les convocations et activer/désactiver la fermeture automatique du jour à minuit
- 2026-10-05 · #1415 · configuration des frais : l’audience Tous / Nouveaux / Anciens est indépendante par filière ou parcours et niveau ; Nanan peut aussi régler l’audience et l’échéance d’une combinaison sans ressaisir son montant
- 2026-10-06 · #1417 · encaissement : la date réelle du versement est modifiable sur ordinateur, tablette et mobile, avec protections contre dates futures, périodes verrouillées et caisses espèces déjà clôturées
- 2026-10-06 · #1422 · fiche d'inscription : valider un paiement en attente directement sur place, sans rechargement, avec mise à jour immédiate de la situation financière et animation de confirmation
- 2026-10-06 · bulletin LMD · en-tête compact 50/50 (logo établissement agrandi, Année/Édition/Niveau/Semestre sur deux demi-lignes) et zone de signature nettoyée : titre du Directeur des Études en haut, espace de signature au centre, nom en bas.
- 2026-10-06 · configuration LMD complète · polices par zone + hauteur logo + espacements en-tête/signature, sauvegarde AJAX sans redirection vers /esbtp/settings ; les codes courts internes de filière comme BU ne sont plus imprimés dans Parcours.
- 2026-10-06 · bulletin LMD · le bandeau Code établissement / Statut / Direction masque maintenant complètement les rubriques sans valeur et redistribue automatiquement l'espace entre celles qui restent.

- 2026-10-09 · Notes BTS et LMD : changer l'année universitaire sans recharger la page ; classes, indicateurs, saisie des évaluations et suivi des notes actualisés.
