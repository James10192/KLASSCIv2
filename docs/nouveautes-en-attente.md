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
- 2026-11-03 · Une seule fenêtre à la fois au chargement : plus de rappels empilés sur l'avis d'abonnement
- 2026-11-03 · Emploi du temps et évaluations : chiffres du bandeau lisibles, confirmations dans une fenêtre KLASSCI
- 2026-11-03 · #1769 · Réglages : l'école choisit la plage horaire de ses cours (cours du soir possibles)
