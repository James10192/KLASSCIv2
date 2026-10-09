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
