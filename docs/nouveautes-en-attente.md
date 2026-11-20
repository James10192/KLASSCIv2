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



- Relevés de notes détaillés pour le jury (LMD depuis le jury, BTS depuis les résultats) : entrée Nouveautés + captures à faire après déploiement.
- Fiche de notes Excel par classe, BTS et LMD (télécharger, remplir, réimporter) : entrée Nouveautés + captures à faire après déploiement.
- Encaissement et reçu : nom avant prénoms, recherche triée par ordre alphabétique : entrée Nouveautés + captures à faire après déploiement.
