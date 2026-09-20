# Contrat du compte rendu

Fichier : `.claude/completude.md` (gitignored). Gabarit : skill `fin-de-tache`.

## Pourquoi le hook existe

Le 14 septembre 2026, un compte rendu entièrement coché citait des méthodes, rules et tailles de fichiers **qui n'existaient pas**. Cocher n'est pas prouver. Le tableau « Vérifié, et comment » est la seule ligne qui rend une case opposable.

`.claude/hooks/completude-check.sh` :

- ne fait rien s'il n'y a pas de fichier ;
- bloque (exit 2) s'il reste des `- [ ]` ou `À COMPLÉTER` ;
- bloque si ≥ 3 cases cochées et trop peu de lignes de preuve ;
- se tait au second passage (`stop_hook_active`) pour ne pas boucler ;
- sans `jq` (Git Bash Windows) : se désactive en le **disant** sur stderr.

OpenCode n'exécute pas ce Stop hook. Le plugin `completude-idle.js` relit le même fichier à `session.idle` et réinjecte les cases restantes à la compaction pour que le modèle ne « oublie » pas la liste.

## Ce que tu dois écrire

1. **Demandé, mot pour mot** — citation, points, pas de résumé.
2. **À livrer** — une case par point, ordre de la demande.
3. **Vérifié, et comment** — fichier:ligne, URL+date, commande+sortie. Pas « lu dans ma tête ».
4. **Non fait, et pourquoi** — obstacle nommé.
5. **Moyens employés** — internet, rules, critique-transversale, thermo-review, preuve d'écran. Cocher **employé ou écarté avec raison**.

## Interdits

- Cocher avant d'avoir la preuve.
- Inventer un fichier, une méthode, un chiffre.
- Laisser « À COMPLÉTER ».
- Fermer la session avec des `[ ]` encore là et un message « on pourra ensuite ».
