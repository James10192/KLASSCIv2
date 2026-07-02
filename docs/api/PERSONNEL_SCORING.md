# API Performance Personnel

## Vue d'ensemble

Le module de scoring personnel expose des endpoints internes pour consulter les snapshots de performance calcules a partir des roles et permissions effectives.

Le score est automatique, sur 100, et seules les dimensions correspondant aux permissions de l'utilisateur sont prises en compte.

## Permissions

- `performance.view` : voir les scores accessibles.
- `performance.view_all` : voir les scores de tout le personnel.
- `performance.recalculate` : recalculer un score individuel depuis l'UI.
- `performance.configure` : reserve pour une future configuration des regles.

## Endpoints Web JSON

### GET `/esbtp/personnel/performance/data`

Retourne la liste des derniers snapshots par utilisateur pour une periode.

Parametres:

- `period` optionnel : `month`, `quarter`, `year`. Defaut : `month`.

Reponse:

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 12,
      "name": "Nom Personnel",
      "role": "enseignant",
      "score": 84,
      "level": "good",
      "level_label": "Bon",
      "dimensions": 4,
      "period": "01/07/2026 - 31/07/2026"
    }
  ]
}
```

### POST `/esbtp/personnel/performance/recalculate`

Recalcule le score d'un utilisateur donne.

Payload:

```json
{
  "user_id": 12,
  "period": "month"
}
```

## Commande Artisan

```bash
php artisan personnel-scores:recalculate --period=month
php artisan personnel-scores:recalculate --period=quarter --role=enseignant
php artisan personnel-scores:recalculate --period=year --user=12
```

## Historique

- 2026-07-02 : creation de la V1 automatique basee sur les permissions effectives.
