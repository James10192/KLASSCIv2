---
name: CHANGELOG — discipline de mise à jour (interne + public)
description: Quand et comment mettre à jour les deux changelogs (KLASSCI interne + klassci-landing public Vercel)
type: project
---

# CHANGELOG — KLASSCI

## Quand s'active

À chaque commit qui change le **comportement utilisateur**, la **structure data** ou un **contrat API**.

## Deux fichiers à maintenir en parallèle

| Fichier | Rôle | Audience | Style |
|---|---|---|---|
| `CHANGELOG.md` (racine KLASSCIv2) | Source dev — historique technique | Devs / mainteneurs | Concis, peut citer fichiers/PR |
| `klassci-landing/content/docs/changelog.mdx` (FR) | Page publique `klassci.com/docs/changelog` | Clients, prospects | Vendeur, orienté valeur, pas de jargon |
| `klassci-landing/content/docs/changelog.en.mdx` (EN) | Version anglaise miroir du FR | Clients EN | Idem mais en anglais |

**Workflow** : on update les **deux** en même temps lors d'un changement user-visible. Le commit dans `klassci-landing` déclenche l'auto-deploy Vercel (production immédiate sur klassci.com).

## OBLIGATOIRE d'updater (les 2 fichiers)

- Nouvelle feature visible utilisateur
- Breaking change (migration data, suppression endpoint, renaming permission)
- Refactor structurel impactant l'UX
- Migration DB importante
- Suppression code mort significative
- Bug fix qui change le comportement attendu (sécurité, calcul financier, paiements)
- Update de dépendance majeure

## PAS besoin d'updater

- Refactor interne sans impact utilisateur (cleanup, renames internes)
- Tests ajoutés
- Fix de typos
- Mise à jour docs/README/CLAUDE.md
- WIP / drafts / branches non mergées

## Format `CHANGELOG.md` (interne, racine KLASSCIv2)

Style [keepachangelog](https://keepachangelog.com/fr/1.1.0/), groupement par mois (cohérent avec klassci-landing) :

```md
## Mai 2026

### Ajouts
- Description courte feature (#N)

### Améliorations
- Description courte change

### Suppressions
- Description courte suppression

### Corrections
- Description courte bug fix

### Sécurité
- Description courte fix sécu
```

**Sections autorisées** : `Ajouts`, `Améliorations`, `Suppressions`, `Corrections`, `Sécurité`. Ordre cohérent avec klassci-landing.

## Format `klassci-landing/content/docs/changelog.mdx` (public)

Headers identiques. Une entrée plus longue, complètement orientée valeur utilisateur — **pas de noms de fichiers, pas de noms de classes, pas de PR/SHA**.

```mdx
## Mai 2026

### Ajouts

- **Refonte premium des pages enseignant** — formulaire en une seule page avec hero
  KLASSCI, trois champs requis pour démarrer, section régime d'engagement avec radio
  cards (Vacataire / Permanent / Consultant), panneau profil détaillé pliable.
```

**Style public** :
- 1 phrase contexte + bénéfice + paramètres concrets visibles
- **Pas** de `(#287)`, `app/Http/Controllers/...`, `git commit hash`
- **Pas** de "PR mergée", "refactor avec service extraction", "Lot N"
- **Oui** : "ce que voit/peut faire l'utilisateur maintenant"

**Sections FR** : `### Ajouts`, `### Améliorations`, `### Suppressions`, `### Corrections`, `### Sécurité`
**Sections EN** : `### Added`, `### Improved`, `### Removed`, `### Fixed`, `### Security`

## Workflow de mise à jour

### Pendant le développement (commit normal)

1. Faire le commit code dans KLASSCI repo
2. Si user-visible → ajouter entrée dans `CHANGELOG.md` (interne) sous le mois courant
3. Commit séparé : `docs(changelog): ajoute <feature>` ou groupé avec le commit de feature

### Mise à jour public (klassci-landing) — au minimum hebdomadaire

À faire à la fin d'un chantier (= un PR significatif mergé) ou en sweep périodique :

```bash
# 1. Synchronise ton repo klassci-landing
cd /c/Users/yabla/Downloads/dev/klassci-landing
git pull --rebase origin master

# 2. Édite content/docs/changelog.mdx (FR) — ajoute ou complète le mois courant
# 3. Édite content/docs/changelog.en.mdx (EN) — miroir EN du FR

# 4. Commit + push direct master (pattern repo : direct push, pas de PR)
git add content/docs/changelog.mdx content/docs/changelog.en.mdx
git commit -m "docs(changelog): <mois> — <thème>"
git push origin master
# → Vercel auto-deploy klassci.com/docs/changelog en ~2 min
```

### À chaque demande de "/commit" sur KLASSCI

Si la session a livré du user-visible non encore changelogué dans `klassci-landing` :
1. Update `CHANGELOG.md` interne avant le commit code
2. **Avant la fin de la session**, mettre aussi à jour klassci-landing (les 2 langues)
3. Commit + push klassci-landing master

## Style des entrées

### Internes (`CHANGELOG.md`)

- 1 ligne par entrée
- **Mentionner la PR/issue Github** : `(#123)`
- **Préfixer pour multi-tenant** : `[ESBTP only]`
- **Verbe à l'impératif** ou nom : "Ajout de X", "Suppression de Y", "Fix Z"
- Détails techniques OK ici (noms de fichiers, services, classes)

### Public (`klassci-landing`)

- 1-3 phrases par entrée
- **Aucune référence interne** : ni PR, ni nom de fichier, ni nom de classe
- Verbe d'action utilisateur : "permet de…", "affiche…", "désactive automatiquement…"
- Paramètres concrets : « 60 requêtes par minute », « trois champs requis », « gradient bleu »
- Mentionner les routes user-facing : `(/esbtp/enseignants)` OK car c'est un repère

## Règles absolues

1. **Sections cohérentes** entre les 3 fichiers : Ajouts/Améliorations/Suppressions/Corrections/Sécurité (FR) / Added/Improved/Removed/Fixed/Security (EN)
2. **Updater au commit, pas à la PR-merge** pour le `CHANGELOG.md` interne — sinon on oublie
3. **Updater klassci-landing à la fin d'un chantier** (groupement OK), pas à chaque commit interne
4. **Pas de Co-Authored-By** dans les changelogs (comme dans les commits)
5. **Si le commit ne touche que docs/tests/refactor interne → pas d'entrée**
6. **Les 2 langues** (FR + EN) doivent rester miroir — toujours updater les deux ensemble
7. **klassci-landing déploie sur master direct** (pattern repo, pas de PR pour ce projet)
8. **Vérifier via grep** avant de pousser : `grep "^## " content/docs/changelog.mdx` doit retourner les mois antéchronologiques sans trou ni doublon

## Exemples corrects

### Entrée interne (`CHANGELOG.md`)

```md
### Ajouts
- Refonte premium pages enseignant create/edit (PR #287, #290, #291) — wizard 4 étapes → formulaire single-page namespace ec-* / ee-*
- Throttle 60/min sur routes enseignants + 5/min sur reset-password (PR #294)

### Suppressions
- Tables `esbtp_enseignant_profiles`, `esbtp_enseignant_disponibilites`, `esbtp_enseignant_affectations` (PR #291)

### Corrections
- `RelationNotFoundException [enseignantProfile]` sur `/esbtp/planning-general` et plusieurs endpoints — alias User->teacher / User->enseignant ajoutés (PR #300)
```

### Entrée publique FR (`klassci-landing/content/docs/changelog.mdx`)

```mdx
### Ajouts

- **Refonte premium des pages enseignant (création + modification)** — formulaire en une seule page avec hero gradient KLASSCI, trois champs requis pour démarrer (nom, téléphone, spécialisation), section « Régime d'engagement » avec radio cards (Vacataire / Permanent / Consultant) et champs conditionnels selon le régime, panneau « Profil détaillé » pliable pour diplômes et grade.

### Sécurité

- **Throttle sur les routes enseignants** — 60 requêtes par minute sur le groupe enseignants et 5 requêtes par minute sur la réinitialisation de mot de passe.
```

### Entrée publique EN (`klassci-landing/content/docs/changelog.en.mdx`)

Mirror of FR, same length and structure.

## Voir aussi

- Rule sœur : [`memory-updates.md`](memory-updates.md) — discipline de la mémoire long-terme
- Repo public : `C:\Users\yabla\Downloads\dev\klassci-landing` (master, push direct)
- Site live : `https://klassci.com/docs/changelog`
