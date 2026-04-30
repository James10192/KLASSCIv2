---
name: Memory updates — quand sauvegarder, quand pas
description: Discipline de mise à jour de la mémoire long-terme pendant les sessions KLASSCI
type: project
---

# Memory updates — KLASSCI

## Quand s'active

Cette rule s'active à chaque turn de session — vérifier régulièrement (toutes les 3-4 actions importantes) si quelque chose mérite d'être sauvegardé en mémoire long-terme.

## OBLIGATOIRE de sauvegarder en mémoire

- **Préférence utilisateur explicite** : "ne fais jamais X", "toujours Y", "j'aime Z"
- **Préférence découverte par confirmation/correction** : l'utilisateur a accepté ou corrigé une approche
- **Décision architecturale prise** : choix de pattern, conventions de nommage adoptées
- **Rôles & responsabilités utilisateur** : "je suis senior dev", "je gère le frontend"
- **Référence externe importante** : Linear/Slack/Grafana/dashboards spécifiques
- **Convention projet apprise pas encore dans CLAUDE.md** : ex namespace CSS, pattern d'API
- **Vision long-terme du produit** : "on va vers X", "on veut éviter Y"

## NE PAS sauvegarder en mémoire

- Code patterns dérivables en lisant les fichiers actuels
- Git history / qui a fait quoi (les commits sont la source authoritative)
- Solutions de bug ponctuel (déjà dans le code committé)
- État éphémère de la session courante (utiliser `TaskCreate`)
- Ce qui est déjà dans `CLAUDE.md` ou les autres rules

## Comment sauvegarder

**Path** : `C:\Users\PAVILION\.claude\projects\C--Users-PAVILION-Downloads-ASSIGNMENT-DEV-KLASSCIv2\memory\`

**Frontmatter YAML obligatoire** :

```yaml
---
name: Titre court explicite
description: Une phrase qui résume l'essentiel
type: user | feedback | project | reference
---
```

**Body** : pour `feedback` et `project`, structurer en deux blocs :

- `**Why:**` — la raison (préférence exprimée, contrainte projet, contexte business)
- `**How to apply:**` — quand et comment ça s'applique en pratique

**Index `MEMORY.md`** : une ligne par memory dans le dossier, format :

```md
- [Titre](fichier.md) — hook 1 ligne expliquant l'utilité
```

## Mise à jour fréquente (pendant le travail)

- Pendant les longues sessions/jobs, sauvegarder dès qu'une info utile est apprise
- Ne pas attendre la fin du PR/feature pour persister
- Si une mémoire devient incorrecte → la mettre à jour ou la supprimer (ne jamais accumuler de fausses)
- Les memories anciennes qui ne servent plus → les supprimer plutôt que les laisser pourrir

## Vérification avant utilisation

- **Mémoire qui nomme un fichier/fonction/permission spécifique** → vérifier qu'il existe encore avant d'agir dessus (`Read`, `Grep`, ou `Glob`)
- **Mémoire de snapshot** (architecture, activity log) → préférer relire le code ou `git log` plutôt que se fier au snapshot
- **Mémoire `feedback` (préférence)** → fiable, applicable directement

## Règles absolues

1. **Frontmatter YAML** sur tous les fichiers memory — pas de body brut
2. **Indexer dans `MEMORY.md`** sinon la mémoire est invisible aux futures sessions
3. **`type: feedback`** pour les préférences utilisateur, **`type: project`** pour les décisions/visions architecturales
4. **Ne jamais sauvegarder un secret** (token, password, clé API) en mémoire
5. **Updater pendant le travail**, pas à la fin
