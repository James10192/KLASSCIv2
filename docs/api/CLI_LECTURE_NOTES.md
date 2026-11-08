# API CLI — Lecture des notes par évaluation

## `GET /api/cli/evaluations/notes`

Lecture seule. Aucune écriture.

- Authentification : jeton Sanctum avec l'aptitude `cli:read`.
- Paramètres : `annee_id` (obligatoire), `classe_id` (optionnel), `evaluation_id` (optionnel).
- Les évaluations annulées sont exclues. Les notes supprimées ne sont pas rendues.
- Au-delà de 300 évaluations, la requête est refusée (422) : préciser `classe_id` ou `evaluation_id`.

Pour chaque note : `etudiant_id`, `matricule`, `nom`, `prenoms`, `note` (null si vide), `is_absent`, `modifiee_le`.

## Historique

- **8 novembre 2026** — création.
