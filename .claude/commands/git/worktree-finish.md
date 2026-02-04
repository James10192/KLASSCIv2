---
description: Après merge d'une PR créée via worktree-start : supprimer le worktree, supprimer la branche locale, et puller presentation à jour. Usage: /git:worktree-finish nom-de-la-branche
---

Workflow worktree — phase nettoyage après merge.

La branche mergée est : $ARGUMENTS

## Étapes à suivre strictement dans cet ordre

1. **Supprimer le worktree**
   D'abord récupérer le chemin exact avec `git worktree list`, puis supprimer avec le chemin complet retourné :
   ```bash
   cd <repo root>
   git worktree list
   git worktree remove <chemin exact du worktree pour $ARGUMENTS> --force
   ```
   Le `--force` est nécessaire parce que la branche locale n'a pas encore été mergée localement.

2. **Supprimer la branche locale**
   La branche n'est mergée que sur GitHub (merge commit côté remote), donc `git branch -d` va refuser. On utilise `-D` :
   ```bash
   git branch -D $ARGUMENTS
   ```

3. **Puller presentation à jour**
   ```bash
   git checkout presentation
   git pull origin presentation
   ```
   Le pull va récupérer le merge commit de la PR.

4. **Vérifier**
   ```bash
   git worktree list   # doit montrer uniquement le repo principal
   git log --oneline -3  # doit montrer le merge de la PR en tête
   ```

## Règles importantes
- Toujours utiliser `git worktree list` AVANT de supprimer pour avoir le chemin exact
- Toujours utiliser `--force` sur `worktree remove`
- Toujours utiliser `-D` (majuscule) sur `branch` pour supprimer une branche non-mergée localement
- On reste sur `presentation` à la fin
