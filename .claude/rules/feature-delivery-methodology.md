# Rule: Feature Delivery Methodology — KLASSCI multi-instance

## Quand s'active

Cette rule s'active **automatiquement** dès que tu attaques une feature qui :
- Touche **≥ 3 fichiers** OU modifie le **schéma DB**
- Cible un **tenant en production** (presentation, ephrata, esbtp-yakro, esbtp-abidjan, rostan, hetec)
- Impacte un **flow utilisateur** (admin, secrétaire, comptable, coordinateur, enseignant, étudiant)
- Demande au moins **1 PR** vers `presentation`

**N'active PAS** pour : typo, fix unique fichier sans regression, refactor interne sans impact UX. Pour ça → `/commit` direct.

## Pourquoi

Marcel a validé en session du 10 mai 2026 (4 PRs LMD ephrata) une méthodologie 13-phases qui a permis :
- Zéro régression sur 5 tenants en production
- 1 data-loss trap intercepté par /pr-review-toolkit avant merge
- 2 hotfixes UX itératifs validés par Marcel via screenshots
- Déploiement SSH-less en 4 commandes klassci-cli

L'objectif : préserver cette discipline pour toute feature non-triviale.

## Les 13 phases — séquentielles, jamais sautées

```
0.  Exploration mémoire + code        (Read/Grep/Glob/Bash)
1.  État des lieux tenant cible       (klassci-cli stats/classes/users)
2.  Collecte sources métier           (PDF complet, Excel, captures)
3.  Recherche standard externe        (UEMOA, Apogée, conventions domaine)
4.  Analyse schéma vs gap             (lecture migrations + models)
5.  Discussion archi avec Marcel      (validation décisions structurantes)
6.  /plan-and-confirm depth=N         (5 agents + 4 reflexions ultrathink)
7.  Implémentation PR foundation      (branche feat/, commits atomiques, tests)
8.  /simplify cleanup                 (3 agents Reuse+Quality+Efficiency)
9.  /pr-review-toolkit:review-pr      (4 agents code+test+failure+type)
10. Merge + cross-branch sync         (gh pr merge --admin + push presentation:tenant)
11. Hotfixes UX itératifs             (1 PR = 1 concern, comme Marcel teste)
12. Déploiement multi-instance          (klassci pull → cache:clear → migrate → permissions:fix)
13. Validation fonctionnelle          (utilisateur teste UI sur tenant cible)
```

**Règle d'or** : ne jamais court-circuiter une phase. Chaque phase pose les fondations de la suivante.

## How to apply — points critiques

### Phase 0 — Exploration mémoire (avant toute question)
- Lire mémoires pertinentes en **parallèle** (single message, multiple Read)
- Grep/Glob pour cartographier l'état actuel
- Vérifier les rules projet existantes — elles peuvent déjà répondre

### Phase 6 — /plan-and-confirm depth auto-bump
| Signal | Score |
|---|---|
| Multi-PR | floor=4 |
| Schema migration table partagée multi-instance | floor=4 |
| Mot « ultrathink » | =5 |
| Demande explicite « N alternatives » | =4-5 |

À depth=5 : 5 agents en parallèle (Critic + Codebase + Docs + Web + Devil's Advocate) + 4 reflexions toi-même (Premortem + Simplification 30% + Future-Marcel + Opposite-day) + 3 alternatives (A minimal / B balanced / C ambitieux) avec confidence scores.

### Phase 7 — Implémentation discipline
- **1 concern = 1 commit** atomique (bug fix incident → commit séparé)
- **`git add <fichiers>`** explicite, jamais `git add -A`
- **Migrations** toujours via `php artisan make:migration` (rule projet)
- **Validation enum atomique** : `Rule::in(EnumClass::values())` + cast `=> EnumClass::class` (jamais `in:` hardcodé)
- **Tests Unit** sans DB (extends `PHPUnit\Framework\TestCase`) pour BTS regression sur tables partagées
- **Pas de Co-Authored-By** dans le commit message (rule globale)

### Phase 9 — /pr-review-toolkit findings sévérités
- 🚨 **MUST FIX** (blocks merge) : data-loss, regression, sécurité
- ⚠️ **SHOULD CONSIDER** : production safety, idempotence
- 💡 **NIT** : stylistique
- ✅ **STRENGTHS** : à citer en commit/PR description

**Tout fix dans 1 commit unique** `fix(<scope>): address PR review findings — <résumé>`.

### Phase 10 — Pattern PR-via-gh (rule `multi-agent-git-safety` commandement 13)
```bash
gh pr merge <pr-number> --merge --admin
git fetch origin presentation
git merge --ff-only origin/presentation     # JAMAIS git pull (peut auto-stash)
git push origin presentation:<tenant>       # cross-branch sync
```

### Phase 12 — Workflow déploiement multi-instance
```bash
klassci pull <tenant>           # 1. git pull + Laravel cache_clear partiel interne
klassci cache:clear <tenant>    # 2. ⚠️ TOUJOURS REQUIS : view:clear + opcache reset
klassci migrate <tenant>        # 3. apply pending migrations
klassci permissions:fix <tenant> # 4. sync registry → DB
```

**Pourquoi `cache:clear` est TOUJOURS nécessaire après `pull`** :
- Le `pull` ne fait qu'un `cache_clear` partiel — il **ne purge ni `view:clear` ni opcache PHP**
- Sans `view:clear` : un Blade modifié continue de servir son ancien compiled view de `storage/framework/views/<hash>.php`. Symptôme : « syntax error, unexpected end of file » sur des `@if/@endif` qui sont pourtant équilibrés dans la source
- Sans opcache reset : un PHP modifié continue de servir l'ancien bytecode. Symptôme : DI errors, methods not found, faux 500

**Si CLI timeout (>30s)** : utiliser curl direct vers `/api/cli/<endpoint>` avec `--max-time 120`.

## Cas particulier : saisie en masse par utilisateur (X UE / Z ECUE)

Pattern UX critique quand une directrice doit saisir manuellement des dizaines/centaines d'entrées :

1. **Auto-fill** quand champ optionnel + champ dépendant rempli (ex: Code vide + Type sélectionné → Intitulé = label du Type)
2. **Empty state ladder** (4 cas distincts : pas de parent / pas filtré / pas d'enfants / pas planifié)
3. **Bulk import** comme PR séparée future (parser PDF dédié, pas dans la PR foundation)
4. **Hiérarchie expandable** plutôt que flat (Alpine `x-show` + caret animée)
5. **Saisie progressive** : sauvegarde par UE, pas validation globale qui bloque tout. KPIs visibles (« 12 UE saisies, 35 ECUE, 90 cr / 180 attendus »).

## Anti-patterns à BLOQUER en review

1. ❌ Coder sans /plan-and-confirm pour une feature multi-PR
2. ❌ Présenter 1 seul plan à depth ≥ 4 (rule violation)
3. ❌ Sauter le « salt » / « what I'm not doing »
4. ❌ Coder sans OKAY explicit (rule #1 absolue)
5. ❌ Hardcoder une liste de valeurs dans 3+ endroits (controller + Blade + model) — utiliser `Enum::cases()` partout
6. ❌ Migration sans `down()` testable
7. ❌ Tests qui dépendent de la DB pour des choses testables en mémoire
8. ❌ Filtrer le critic / argumenter avec un finding au lieu de fixer
9. ❌ Présenter MUST FIX comme « warning seulement »
10. ❌ Sauter le `cache:clear` entre `pull` et `migrate` (cause cachée de 500 faux positifs)
11. ❌ Force push direct sur `presentation` / `master` sans PR
12. ❌ `git checkout <feat-branch>` sur le repo principal quand des agents tournent en parallèle
13. ❌ `git pull` au lieu de `git fetch + git merge --ff-only` (peut auto-stash)
14. ❌ Accumuler 5 « petits » fixes dans 1 PR « cleanup » — Marcel veut tester chaque fix indépendamment
15. ❌ Dire « c'est fait » sans demander à l'utilisateur de vérifier visuellement
16. ❌ Forcer la saisie complète d'un parcours en 1 fois (pas de visibilité progression)

## Outillage canonique

- **Skills** : `/plan-and-confirm`, `/simplify`, `/pr-review-toolkit:review-pr`, `/feature-delivery` (skill intégrateur)
- **CLI** : `klassci-cli` (pull, migrate, cache:clear, permissions:fix, stats), `gh` (pr create/merge), `git` (fetch + merge --ff-only + push cross-branch)
- **Outils dev** : `php artisan make:migration`, `php vendor/bin/phpunit --testdox`, `py + pypdf` (PDF Windows-safe), `curl direct API` (workaround timeout)

## Discipline mémoire pendant la session

- **Avant de coder** : sauvegarder le contexte décisionnel (token, choix archi validés, sources métier)
- **Après chaque grand jalon** : update la mémoire avec les nouvelles décisions
- **À chaque correction utilisateur** : noter la préférence pour ne pas la refaire
- **Index dans MEMORY.md** : 1 ligne par fichier mémoire, < 200 chars

## Voir aussi

- Mémoire `feature-delivery-methodology.md` — version exhaustive avec exemples de la session 10/05/2026
- Skill `feature-delivery` (`.claude/skills/feature-delivery/SKILL.md`) — checklist interactive invoquable
- Rules transverses appliquées : `multi-agent-git-safety.md`, `tenant-branches.md`, `pre-commit-quality-gate.md`, `parallel-agents.md`, `marcel-global-preferences.md`, `migrations.md`, `permissions.md`, `premium-redesign.md`, `premium-selects.md`, `no-god-code.md`, `customizable-roles.md`, `klassci-classe-matieres.md`
- Skill `/plan-and-confirm` — pierre angulaire de la phase 6
