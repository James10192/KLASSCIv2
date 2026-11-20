# API CLI — Publication des évaluations d'une classe

## `GET /api/cli/evaluations/classe`

Ability : `cli:read`. Paramètres : `classe_id`, `annee_universitaire_id`, `periode` (facultatif).
Rend chaque évaluation avec son statut, sa publication, la publication de ses notes et son nombre de notes.

## `POST /api/cli/evaluations/publier`

Ability : `cli:admin`. Simulation par défaut (`dry_run: false` pour écrire), `motif` obligatoire.

```json
{ "classe_id": 95, "annee_universitaire_id": 4, "motif": "Publication demandée par la direction des études", "dry_run": true }
```

- Passe en `completed`, publiée et notes publiées chaque évaluation du périmètre qui ne l'est pas déjà.
- Les évaluations **annulées ne sont jamais réactivées** : elles sont seulement comptées (`annulees_ignorees`).
- Le changement de statut passe par `App\Domain\Notes\ChangementDeStatut` : les moyennes touchées sont recalculées.
- Les bulletins restent à régénérer.

## Historique

- Novembre 2026 : création (ESBTP Abidjan, L1C Bâtiment et Urbanisme 2025-2026).
