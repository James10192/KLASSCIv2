# Multi-Agent Git Safety — Discipline orchestration parallèle

## Quand s'active

Cette rule s'active dès que tu :
- Lances **2+ agents en parallèle** (background ou non) qui font des écritures git
- Utilises `isolation: "worktree"` sur un agent
- Travailles sur une session multi-PR avec plusieurs branches en cours
- Fais une opération git destructive (`git checkout HEAD --`, `git reset --hard`, `git stash drop`, force push)
- Pull/push pendant qu'un agent background tourne
- Demandes à un agent de modifier des fichiers via `Edit`/`Write` avec un chemin absolu

## Pourquoi cette rule existe

**Incident fondateur** : session du 2-3 mai 2026 — orchestration de 6 PRs en parallèle (`presentation`/notes), 4 agents Vague 1 + 2 agents Vague 2 + 1 agent /simplify post-merge. Bilan :

- **4 agents sur 6 ont accidentellement committé dans le main repo** au lieu de leur worktree (paths absolus + junction `vendor/` partagée)
- **1 agent (PR #7 Excel) a force-pushé directement sur `presentation`** sans PR
- **1 agent (PR #5) a fait `git checkout HEAD --` qui a écrasé les WIP de PR #7** sur 5 fichiers du main
- **5 conflits de merge** (CHANGELOG.md, ESBTPNoteController, routes/web.php, index.blade.php) à résoudre manuellement
- **PR #319 a dû être fermée comme obsolète** car son contenu était déjà sur presentation
- ~30 minutes à nettoyer les résidus + résoudre conflits

Toutes ces frictions étaient évitables. Cette rule capture la discipline qu'on aurait dû avoir.

## Les 12 commandements de l'orchestration multi-agent

### 1. UN SEUL agent peut écrire sur une branche à la fois

Si 2 agents bossent sur la même branche (ou 2 agents bossent sur des branches qui partagent des fichiers communs), tu **DOIS** :
- Séquencer (Vague 1 mergée AVANT de lancer Vague 2)
- OU partitionner les fichiers (agent A touche `controller`, agent B touche `view`, agent C touche `routes`)
- OU briefer chaque agent avec une section **NE TOUCHE PAS aux fichiers X, Y, Z**

### 2. Worktree isolation = `isolation: "worktree"` OBLIGATOIRE pour parallélisme

Pas de "je vais juste lancer 2 agents en parallèle sur la même branche". Le 2e va écraser le 1er. **Toujours** worktree isolé pour 2+ agents simultanés sur le même repo.

### 3. Le worktree DOIT avoir son propre `vendor/`

**JAMAIS de junction `vendor/` partagée** entre worktree et main repo. Pourquoi : Composer + Laravel + IDE résolvent des paths absolus qui peuvent traverser la junction et écrire dans le main repo. Vu sur PR #5 + PR #6 cette session.

Solutions par ordre de préférence :
1. **Accepter l'absence de tests dans le worktree** (briefer l'agent : "lint OK = suffisant, tests en CI")
2. **Copier `vendor/`** depuis le main vers le worktree (`xcopy /e /q vendor agent-xxx\vendor` Windows / `cp -a vendor agent-xxx/` Unix) — lourd mais safe
3. **`composer install`** dans le worktree — lent (1-2 min) mais propre

### 4. JAMAIS de force-push direct sur la branche principale

Aucun agent (et toi non plus) ne fait `git push origin presentation` sans PR. **TOUJOURS** :
- Worktree branch dédiée
- PR ouverte
- Merge via `gh pr merge` (avec ou sans `--admin`)

Exception unique : amendments à une PR déjà ouverte (commits add-on sur la worktree branch, push avec `--force-with-lease`, jamais `--force` simple).

### 5. JAMAIS `git checkout HEAD --` ni `git reset --hard` sans `git status` préalable

Ces commandes écrasent silencieusement les WIP. Si un autre agent a écrit dans le main pendant que tu travailles, tu détruis son boulot.

**Workflow safe pour cleanup résidus** :
```bash
git status --short                                  # 1. lister
git stash push -u -m "session-cleanup-$(date +%s)"  # 2. sauvegarder (pas drop)
git pull origin presentation                        # 3. sync
git stash list                                      # 4. vérifier que stash existe
# Décider après : git stash drop si déchet, git stash pop si à garder
```

### 6. Path absolu dans `Edit`/`Write` = TOUJOURS le chemin du worktree

Quand un agent travaille dans worktree `/repo/.claude/worktrees/agent-xxx/`, ses Edit doivent **TOUS** être préfixés par ce chemin :

```
✅ Edit /repo/.claude/worktrees/agent-xxx/app/Http/Controllers/Foo.php
❌ Edit /repo/app/Http/Controllers/Foo.php   (= écrit dans le main !)
```

À briefer **explicitement** dans chaque prompt d'agent worktree :
> "Tous les Edit/Write doivent utiliser le chemin absolu commençant par `/repo/.claude/worktrees/agent-xxx/`. Vérifie avec `pwd` au début. Si tu vois un fichier untracked apparaître dans le main repo, tu as écrit au mauvais endroit — patch + revert + retry dans le worktree."

### 7. Pull AVANT push, fetch souvent

Toujours `git fetch origin --prune` avant un merge ou push. La situation a pu évoluer :
- Un autre agent a mergé entre-temps → ta PR doit être rebasée
- L'utilisateur a poussé directement sur master/presentation → conflit potentiel

### 8. Conflits prévisibles sur `CHANGELOG.md` = stratégie de fusion explicite

`CHANGELOG.md` est touché par **toutes** les PRs user-visible. Conflits garantis sur le merge groupé. Stratégies :

- **Préventif** : chaque PR ajoute son entrée dans une section dédiée par mois (Ajouts / Améliorations / Corrections / Sécurité). Le merge va concaténer naturellement si pas de chevauchement.
- **Curatif** : à la résolution conflit, **garder les deux côtés** (HEAD + branche) au lieu de choisir. Ne jamais "résoudre" en gardant l'un et perdant l'autre.

### 9. Rebase plutôt que merge pour PRs en conflit

Quand `gh pr merge` retourne `Pull Request has merge conflicts`, **NE PAS** créer un merge commit qui ajoute du bruit. Plutôt :

```bash
cd /repo/.claude/worktrees/agent-xxx/
git fetch origin
git rebase origin/presentation               # rebase sur la branche cible
# résoudre conflits manuellement (Edit, vérifier 0 marker <<<<<<< / >>>>>>>)
git add <fichiers résolus>
git rebase --continue
git push origin <branch> --force-with-lease  # PAS --force
gh pr merge XXX --merge --admin              # depuis le main repo
```

### 10. `--force-with-lease` JAMAIS `--force`

`--force` écrase aveuglément. `--force-with-lease` refuse si quelqu'un d'autre a poussé entre-temps. Toujours `--force-with-lease` pour les rebases.

### 11. PRs dont le contenu est déjà sur la branche cible = FERMER, pas merger

Si un agent a accidentellement push direct sur `presentation` ET ouvert une PR pour le même contenu : **fermer la PR** avec un commentaire explicatif. NE PAS tenter de merger (ça créerait des conflits massifs avec le travail subséquent).

```bash
gh pr close XXX --comment "Fermée car le contenu (commit ABCD) a été intégré directement sur presentation pendant la phase parallèle. Aucun diff utile restant."
```

### 12. Pendant qu'un agent background tourne, tu ne touches PAS à sa branche cible

Si l'agent /simplify tourne sur `presentation`, tu ne fais pas `git push origin presentation` en parallèle. Sinon conflit garanti à son `git push` final.

**Workflow** :
1. Lance l'agent background
2. Attends la notification de complétion
3. Sync (`git pull --rebase`)
4. Tu peux à nouveau modifier la branche

## Checklist AVANT de lancer N agents en parallèle

- [ ] Chaque agent a son worktree isolé (`isolation: "worktree"`)
- [ ] Les fichiers touchés par chaque agent sont **disjoints** (ou bien sectionnés)
- [ ] Chaque brief contient une section explicite "NE TOUCHE PAS aux fichiers X, Y, Z" avec les fichiers des autres agents
- [ ] Chaque brief inclut "tous tes Edit doivent commencer par `/path/to/worktree/agent-xxx/`"
- [ ] Pas plus de 4 agents en parallèle (rule `parallel-agents.md`)
- [ ] Pas d'écriture parallèle prévue sur `CHANGELOG.md`, `routes/web.php`, ou autre fichier transverse — si inévitable, accepte les conflits et planifie la résolution

## Checklist APRÈS chaque vague (pré-merge)

- [ ] `git status --short` sur le main repo : aucun résidu d'agent inattendu
- [ ] `git diff origin/<pr-branch> origin/presentation --stat` : voir l'ampleur du conflit potentiel
- [ ] Lancer les tests sur la PR avant merge si possible
- [ ] Merger dans l'ordre **du plus fondamental au plus dérivé** (foundation first)
- [ ] Sync `git pull` après chaque merge
- [ ] Si conflit : rebase + résoudre + force-with-lease + retry merge

## Anti-patterns à BLOQUER en review

1. ❌ Lancer 2 agents en parallèle sans `isolation: "worktree"`
2. ❌ Junction `vendor/` du worktree vers le main repo
3. ❌ Agent qui fait `git push origin presentation` direct sans PR
4. ❌ Agent qui fait `git checkout HEAD --` sans avoir d'abord `git status`
5. ❌ Agent qui résout un conflit en supprimant le côté HEAD au lieu de fusionner intelligemment
6. ❌ `git push --force` (utiliser `--force-with-lease`)
7. ❌ Brief d'agent sans section "NE TOUCHE PAS"
8. ❌ Lancer un nouvel agent sur `presentation` pendant qu'un agent /simplify ou /visual-check y tourne
9. ❌ Merger une PR dont le contenu est déjà sur la branche cible (créer doublons + conflits)
10. ❌ Stash drop sans avoir vérifié le contenu du stash

## Voir aussi

- `.claude/rules/parallel-agents.md` — limite 4 agents en parallèle, conventions de launch
- `.claude/rules/feedback_brief_agents_boundary_check.md` (mémoire) — section "NE FAIS PAS" obligatoire
- `.claude/rules/feedback_commit_only_my_changes.md` (mémoire) — ne committer QUE ses propres changes
- Mémoire projet : `feedback_worktree_for_parallel_agents` — historique du choix worktree

## Note pour future session

Si une session orchestre 5+ PRs en parallèle, **toujours** :
1. Documenter l'ordre de merge prévu **AVANT** de lancer les agents
2. Identifier les fichiers de chevauchement et planifier qui touche quoi
3. Accepter qu'on aura **du temps de résolution conflit** au merge final (compter ~5-10 min par PR conflictée)
4. Ne JAMAIS promettre "tout sera mergeable sans intervention" — c'est faux dès qu'on a 3+ PRs sur les mêmes fichiers
