# API CLI Personnel Scores

## Endpoint

`GET /api/cli/personnel-scores`

Inspecte les snapshots stockes dans `esbtp_personnel_score_snapshots` pour un tenant.

## Authentification

Header requis:

```http
Authorization: Bearer <token-cli>
Accept: application/json
```

Le token doit avoir l'ability `cli:read`.

## Parametres

| Parametre | Type | Defaut | Description |
| --- | --- | --- | --- |
| `period` | string | `month` | `month`, `quarter` ou `year` |
| `role` | string | null | Filtre par role snapshot, ex: `enseignant` |
| `level` | string | null | Filtre par niveau, ex: `critical`, `watch` |
| `user_id` | int | null | Filtre un utilisateur |
| `teacher_id` | int | null | Filtre un enseignant |
| `limit` | int | `50` | Nombre de lignes retournees, max `500` |

## Exemple CLI

```powershell
.\klassci-cli.ps1 personnel-scores presentation period=year role=enseignant limit=10
.\klassci-cli.ps1 personnel-scores presentation month enseignant 20
.\klassci-cli.ps1 personnel-scores presentation period=year teacher_id=2
```

## Exemple HTTP

```http
GET /api/cli/personnel-scores?period=year&role=enseignant&limit=10
```

## Reponse

```json
{
  "success": true,
  "data": {
    "filters": {
      "period": "year",
      "role": "enseignant",
      "level": null,
      "user_id": null,
      "teacher_id": null,
      "limit": 10
    },
    "summary": {
      "count": 123,
      "average": 1,
      "watch_count": 7,
      "by_role": {
        "enseignant": 123
      },
      "by_level": {
        "critical": 6,
        "insufficient_data": 117
      }
    },
    "scores": [
      {
        "id": 274,
        "user_id": 26,
        "teacher_id": 5,
        "name": "MARC DJO TEST",
        "role": "enseignant",
        "period_type": "year",
        "period": "2026-01-01 - 2026-12-31",
        "total_score": 21,
        "level": "critical",
        "level_label": "Critique",
        "applicable_dimensions_count": 7,
        "excluded_dimensions_count": 4,
        "calculated_at": "2026-07-02T14:10:00+00:00"
      }
    ]
  },
  "message": "Personnel score snapshots"
}
```

## Historique

- 2026-07-02: Creation de l'endpoint et de la commande `klassci-cli.ps1 personnel-scores`.
