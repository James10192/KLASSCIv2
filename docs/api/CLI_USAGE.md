# API CLI — Usage réel de l'application

## `GET /api/cli/usage/pages`

Où agit-on le plus dans l'application ? Agrège le journal d'audit (créations,
modifications, suppressions) par page d'origine.

- Authentification : jeton Sanctum avec l'aptitude `cli:read`.
- Paramètre : `jours` (1 à 365, défaut 30).
- Lecture seule.

### Réponse

```json
{
  "success": true,
  "data": {
    "periode_jours": 30,
    "pages": [
      { "page": "/esbtp/inscriptions/{id}/edit", "actions": 412, "utilisateurs_max": 3 }
    ],
    "entites": [
      { "entite": "Inscription", "evenement": "updated", "actions": 380 }
    ]
  }
}
```

- `page` : chemin sans domaine ni paramètres ; les identifiants numériques ou
  UUID sont remplacés par `{id}`.
- `actions` : nombre d'écritures parties de cette page.
- `utilisateurs_max` : plus grand nombre d'utilisateurs distincts observé sur
  une même URL de cette page.

### Limite connue

Les simples consultations ne sont pas journalisées : une page qu'on lit sans
jamais rien y modifier n'apparaît pas. C'est voulu, cet outil sert à trouver les
pages de travail à soigner en priorité.

## Historique

- Septembre 2026 : création.
