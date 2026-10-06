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

- 2026-10-03 · #1397 · suivi des notes des classes LMD par semestre : panneau dans la fenêtre des notes LMD et sur la génération des bulletins LMD, Nanan dit ce qui manque
- 2026-10-03 · #1399 · enseignants LMD : évaluations et bulletins reprennent le professeur du planning, avec affectation rapide et écran de configuration par semestre
- 2026-10-04 · #1400 · professeurs LMD par classe : plusieurs enseignants possibles dans le planning, résolution automatique par classe, conflit détecté puis confirmation/harmonisation sur évaluations, séances et bulletins
- 2026-10-04 · #1401 · bulletin LMD : le nom de l'établissement, le titre du document et les métadonnées d'en-tête reprennent exactement la couleur de texte choisie dans les paramètres PDF
- 2026-10-05 · #1407 · Nanan gère les enseignants et professeurs LMD ainsi que les rendez-vous ciblés : programmer, reprogrammer, annuler, fermer ou rouvrir un créneau, renvoyer les convocations et activer/désactiver la fermeture automatique du jour à minuit
- 2026-10-05 · #1415 · configuration des frais : l’audience Tous / Nouveaux / Anciens est indépendante par filière ou parcours et niveau ; Nanan peut aussi régler l’audience et l’échéance d’une combinaison sans ressaisir son montant
- 2026-10-06 · #1417 · encaissement : la date réelle du versement est modifiable sur ordinateur, tablette et mobile, avec protections contre dates futures, périodes verrouillées et caisses espèces déjà clôturées
- 2026-10-06 · #1422 · fiche d'inscription : valider un paiement en attente directement sur place, sans rechargement, avec mise à jour immédiate de la situation financière et animation de confirmation
