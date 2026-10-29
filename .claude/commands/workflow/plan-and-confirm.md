---
description: Workflow "comprendre → expliquer → attendre OKAY → coder". À utiliser pour toute modification non triviale : lire le code concerné, présenter une explication claire de ce qui est compris et du plan d'action, puis ATTENDRE l'approbation explicite avant de toucher au code. Usage : /workflow:plan-and-confirm <description de la tâche>
disable-model-invocation: true
---

# Workflow : Plan and Confirm

**Objectif** : Ne jamais coder avant que l'utilisateur ait dit OKAY.

La tâche à traiter est : $ARGUMENTS

---

## Phase 1 — Explorer & comprendre (PAS de modification)

1. Explorer la codebase liée à la tâche : fichiers controllers, models, views, services, routes concernés.
2. Lire attentivement chaque fichier impliqué — ne pas deviner, ne pas supposer.
3. Identifier les dépendances, les relations entre entités, les flux de données.
4. Repérer les contraintes existantes (validations, relations FK, logiques métier).

**Règle** : À cette phase, aucune modification de fichier. Uniquement lecture et exploration.

---

## Phase 2 — Présenter la compréhension et le plan

Présenter à l'utilisateur sous cette structure exacte :

### Ce que j'ai compris
- Décrire le problème ou la demande tel qu'il est compris, avec des références précises vers le code (`fichier:ligne`).
- Mentionner les points qui ne sont pas évidents ou qui pourraient être mal interprétés.
- Si plusieurs interprétations sont possibles, les lister.

### Ce que je vais faire
- Lister les modifications prévues fichier par fichier, avec la logique derrière chaque changement.
- Préciser ce qui **ne sera PAS** modifié et pourquoi.
- Si une modification touche plusieurs fichiers ou des logiques enchaînées, expliquer l'ordre et les raisons.

### Points d'attention
- Signaler tout ce qui pourrait casser d'ailleurs dans l'application.
- Signaler si une hypothèse a été faite et pourquoi.

---

## Phase 3 — ATTENDRE

**⛔ STOP. Ne pas toucher au code.**

Afficher literalement :

> Merci de confirmer avec **OKAY** si la compréhension et le plan sont corrects.
> Sinon, dites-moi ce qui est inexact et je vais ajuster avant de coder.

**Règle absolue** : Si l'utilisateur ne dit pas explicitement OKAY (ou un mot équivalent comme "oui", "c'est bon", "go", "lance"), ne pas passer à la Phase 4. Rester en attente.

---

## Phase 4 — Coder (uniquement après OKAY)

Une fois l'OKAY reçu :

1. Implémenter les modifications exactement comme décrit dans le plan approuvé.
2. Si pendant l'implémentation on découvre quelque chose qui change le plan, **arrêter et re-présenter** avant de continuer.
3. Vérifier la syntaxe des fichiers PHP après modification (`php -l`).
4. Résumer les modifications effectuées avec les références fichier:ligne.

**Ne pas commiter** à moins que l'utilisateur ne le demande explicitement.

---

## Règles importantes

- **Jamais coder sans OKAY** — c'est la règle #1 de ce workflow.
- Si le plan est rejeté, revenir à la Phase 1 pour re-explorer si nécessaire, puis Phase 2 avec une nouvelle présentation.
- Les explications doivent être précises et basées sur le code réellement lu, pas sur des suppositions.
- Si la tâche touche à la logique métier complexe (passages, inscriptions, calculs), être particulièrement détaillé dans "Ce que j'ai compris".
- Ne pas sur-expliquer des parties triviales — se concentrer sur ce qui pourrait être mal compris.
