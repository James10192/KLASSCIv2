---
name: CHANGELOG.md — discipline de mise à jour
description: Quand et comment mettre à jour CHANGELOG.md pendant le travail (style keepachangelog)
type: project
---

# CHANGELOG.md — KLASSCI

## Quand s'active

À chaque commit qui change le **comportement utilisateur**, la **structure data** ou un **contrat API**.

## OBLIGATOIRE d'updater CHANGELOG.md

- Nouvelle feature visible utilisateur
- Breaking change (migration data, suppression endpoint, renaming permission)
- Refactor structurel (ex : harmonisation permissions Lots 0-7)
- Migration DB importante
- Suppression code mort significative (ex : rôle parent retiré)
- Bug fix qui change le comportement attendu (sécurité, calcul financier, paiements)
- Update de dépendance majeure

## PAS besoin d'updater

- Refactor interne sans impact utilisateur (cleanup, renames internes)
- Tests ajoutés
- Fix de typos
- Mise à jour docs/README/CLAUDE.md
- WIP / drafts / branches non mergées

## Format CHANGELOG.md (style [keepachangelog](https://keepachangelog.com/fr/1.1.0/))

```md
## [Unreleased]

### Added
- Description courte feature (PR #N)

### Changed
- Description courte change

### Removed
- Description courte suppression

### Fixed
- Description courte bug fix

### Security
- Description courte fix sécu
```

**Sections autorisées** : `Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`. Pas d'autres.

## Workflow de mise à jour

1. **Au moment du commit** (ou peu après), ajouter une entrée sous `## [Unreleased]`
2. **Ne pas attendre la fin d'un gros chantier** — updater à chaque commit significatif
3. **Une release** = bump de version → renommer `## [Unreleased]` en `## [X.Y.Z] - YYYY-MM-DD` et recréer `## [Unreleased]` vide au-dessus
4. Si plusieurs commits forment une seule unité logique pour l'utilisateur (ex : Lot 8a + 8b + 8c), grouper en une entrée

## Style des entrées

- **1 ligne par entrée**, claire et orientée utilisateur final ou intégrateur
- **Mentionner la PR/issue Github** si dispo : `(#123)`
- **Préfixer pour multi-tenant** si le change ne concerne qu'un tenant : `[ESBTP only]`
- **Verbe à l'impératif** ou nom : "Ajout de X", "Suppression de Y", "Fix Z"
- **Pas de détails techniques bas-niveau** — ça va dans le commit message, pas le changelog

## Exemples corrects

```md
### Added
- Registry centralisé des rôles & permissions (`config/permissions.php`) avec 154 permissions canoniques (#150)
- Commande `php artisan permissions:audit` pour détecter permissions cassées et orphelines

### Removed
- Rôle `parent` (jamais utilisé : les parents utilisent le compte étudiant de leur enfant)

### Security
- Suppression de l'auto-assignment de rôle par email dans `fix_permissions.php`
```

## Règles absolues

1. **Sections autorisées uniquement** : Added/Changed/Deprecated/Removed/Fixed/Security
2. **Updater au commit, pas à la PR-merge** — sinon on oublie
3. **`[Unreleased]` toujours en haut** — l'ordre antichronologique se fait via les versions datées en-dessous
4. **Pas de Co-Authored-By dans le CHANGELOG** — comme dans les commits
5. **Si le commit ne touche que docs/tests/refactor interne → pas d'entrée**
