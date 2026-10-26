# API CLI du pilotage académique

Ces endpoints sont réservés aux jetons Sanctum CLI. Ils permettent de diagnostiquer et d'initialiser le centre de pilotage sans accès au terminal du serveur.

## Endpoints

- `GET /api/cli/academic-pilotage/diagnose` nécessite `cli:admin`.
- `POST /api/cli/academic-pilotage/backfill` nécessite `cli:admin`.
- `POST /api/cli/academic-pilotage/refresh` nécessite `cli:admin`.

Le diagnostic retourne aussi `snapshots.failed_samples`, limité aux dix derniers échecs. Chaque élément expose le scope, le nombre de tentatives et une empreinte de corrélation. Le message technique reste exclusivement dans les logs serveur.

Les filtres facultatifs sont `year_id`, `class_id` et `period`. La période accepte `semestre1`, `semestre2` ou `annuel`.

Le backfill est toujours un dry-run si `dry_run` est omis. Une écriture exige explicitement :

```json
{
  "dry_run": false,
  "confirm": true,
  "limit": 500
}
```

Les fiches créées depuis les évaluations existantes conservent leur historique. Les notes déjà présentes alimentent les entrées, mais aucune fiche n'est automatiquement marquée comme validée.

## Historique

- 2026-07-14 : ajout du diagnostic, du backfill protégé et du recalcul distant.
- 2026-07-14 : ajout des détails de recalcul en échec dans le diagnostic.
