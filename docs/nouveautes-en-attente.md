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

- 2026-11-20 · #1820 · Nanan répond sur les jurys LMD : étapes avant le PV, membres sans signature, liens des PV

- 2026-11-19 · #1816 · Jury LMD : boutons « PV détaillé du semestre » (PDF et Excel), au format des PV tenus sous Excel par les universités
- 2026-11-19 · #1815 · Jury LMD : la préparation du PV en quatre étapes, chacune avec un bouton « Corriger » vers le bon onglet

