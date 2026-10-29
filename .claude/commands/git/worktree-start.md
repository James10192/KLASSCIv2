---
description: Créer une branche + worktree pour travailler dessus isolément, puis pusher et donner le lien de PR vers presentation. Usage: /git:worktree-start nom-de-la-branche
---

Workflow worktree — phase démarrage.

Le nom de branche demandé est : $ARGUMENTS

## Étapes à suivre strictement dans cet ordre

1. **Créer la branche depuis presentation**
   ```bash
   cd <repo root>
   git branch $ARGUMENTS
   ```

2. **Créer le worktree**
   Le worktree doit être créé UN niveau au-dessus du repo (sibling), pas à l'intérieur.
   ```bash
   git worktree add ..\KLASSCIv2-$ARGUMENTS $ARGUMENTS
   ```
   Vérifier avec `git worktree list` que ça a marché.

3. **Faire le travail dans le worktree**
   Tous les fichiers à modifier sont dans le chemin du worktree.
   Lire, éditer, vérifier syntaxe PHP avec `php -l`.

4. **Commit dans le worktree**
   ```bash
   cd <chemin worktree>
   git add <fichiers spécifiques, jamais git add -A>
   git commit -m "message explicite"
   ```
   - Le message doit être concis et décrire le pourquoi
   - Ajouter `Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>` à la fin du message SEULEMENT si l'utilisateur ne s'y oppose pas
   - PAS de "Generated with Claude Code" ni lien vers claude.com dans le body de la PR

5. **Pusher la branche**
   ```bash
   cd <chemin worktree>
   git push origin $ARGUMENTS
   ```

6. **Donner le lien de création de PR**
   Le lien est toujours ce format :
   ```
   https://github.com/James10192/KLASSCIv2/compare/presentation...$ARGUMENTS
   ```
   Afficher ce lien à l'utilisateur. Ne PAS essayer d'appeler `gh` depuis le terminal (il n'est pas dans le PATH du shell bash) — l'utilisateur crée la PR manuellement via ce lien ou depuis son PowerShell.

## Règles importantes
- Ne jamais utiliser `git add .` ou `git add -A`
- Ne jamais mettre "Generated with Claude Code" dans la PR
- Le worktree est un répertoire sibling, chemin : `<parent du repo>\KLASSCIv2-<nom-branche>`
- Après avoir donné le lien, dire à l'utilisateur : "Mergez la PR puis lancez `/git:worktree-finish $ARGUMENTS`"
