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

- 2026-10-03 · #PR · Retirer un ECUE de sa dernière maquette demande s'il faut le supprimer, l'archiver dans le LMD ou en faire une matière BTS, au lieu de le renvoyer d'office dans les listes BTS (/esbtp/lmd/ue)
