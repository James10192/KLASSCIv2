# OpenCode lit KLASSCI comme Claude

Claude lit `.claude/` (rules, hooks, skills).
OpenCode lit `AGENTS.md` à la racine **et** ce dossier `.opencode/`.

Les deux doivent dire la même chose. On ne recopie pas les rules.

## Ce que tu lis, dans cet ordre

1. `AGENTS.md` (racine) — même entrée que Claude via `CLAUDE.md` / archive
2. `.opencode/rules` → junction vers `.claude/rules` (rien en dur, permissions, rôles custom, selects premium…)
3. `.opencode/hooks` → junction vers `.claude/hooks`
4. `.opencode/skills` → junction vers `.claude/skills`
5. `.opencode/research/` — notes de recherche internet (datées, sourcées)
6. `.opencode/structuration/` — briefs d’organisation d’instance (configurable, jamais un rôle en dur)

## Recréer les junctions (Windows)

Les junctions ne se committent pas. Après clone :

```powershell
powershell -File scripts/link-opencode-claude.ps1
```

Sans junction, lis directement `.claude/rules/` — c’est la source unique.

## Interdit

- Dupliquer une rule dans `.opencode/`
- Inventer un rôle `serviceInformatique`, `chargeDepenses`, `gestionnaireStock` dans `config/permissions.php`
- Écrire « 3 ans », « ESMEA », « UCAO » comme constante métier
