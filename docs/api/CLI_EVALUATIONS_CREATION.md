# API CLI — Création d'évaluations

## `POST /api/cli/evaluations/creer`

Crée un lot d'évaluations (examens, devoirs, rattrapages) dans une année
universitaire donnée.

- Authentification : jeton Sanctum avec l'aptitude `cli:admin`.
- Simulation par défaut (`dry_run` vaut `true`). Aucune écriture tant que
  `dry_run=false` et `confirmation=CREER_LES_EVALUATIONS` ne sont pas envoyés.
- `motif` obligatoire (10 à 500 caractères), tracé dans le journal.
- Atomique : une ligne invalide ou un doublon annule tout le lot.

### Corps de la requête

| Champ | Obligatoire | Description |
| --- | --- | --- |
| `annee_universitaire_id` | oui | Année cible (`esbtp_annee_universitaires.id`). |
| `motif` | oui | Raison de la création, 10 à 500 caractères. |
| `entries` | oui | Liste de 1 à 500 évaluations (voir ci-dessous). |
| `dry_run` | non | `true` par défaut. |
| `confirmation` | si `dry_run=false` | Doit valoir `CREER_LES_EVALUATIONS`. |

Chaque entrée de `entries` :

| Champ | Règle |
| --- | --- |
| `classe_id` | Classe existante. |
| `matiere_id` | Matière existante. |
| `titre` | 1 à 255 caractères. |
| `description` | Optionnel, 1000 caractères au plus. |
| `type` | Un des types saisissables (`devoir`, `examen`, `rattrapage`…). |
| `periode` | Une période propre à la classe (`semestre1`, `semestre2`… pour une classe LMD). |
| `date_evaluation` | Date `AAAA-MM-JJ`. |
| `heure_debut`, `heure_fin` | `HH:MM`, la fin doit suivre le début. |
| `bareme` | Entre 0,1 et 100. |
| `coefficient` | Entre 0,1 et 10. |
| `is_published` | `true` pour activer l'évaluation (publication). Par défaut `false` : brouillon. |

### Garde-fous

- Une évaluation déjà existante, non annulée, pour le même
  `(année, classe, matière, type, période, titre)` le MÊME JOUR fait refuser la
  ligne. Rien n'est recréé. Deux devoirs de même titre à des dates différentes
  sont acceptés.
- Deux lignes identiques dans le même lot sont refusées.
- Les bornes (barème, coefficient), les types et les périodes sont ceux de
  l'écran de création. Les gardes du modèle (cohérence LMD/BTS, ECUE, période de
  spécialité) s'appliquent à chaque ligne ; la simulation les exécute réellement
  puis annule la transaction, donc elle prédit exactement l'exécution.
- Le CLI n'assigne pas d'enseignant (`enseignant_id` n'est pas accepté) : les
  évaluations créées restent sans enseignant assigné, à compléter dans l'écran.

### Réponse (simulation)

```json
{
  "success": true,
  "data": {
    "dry_run": true,
    "annee": { "id": 4, "name": "2025-2026" },
    "lignes": 2,
    "publiees": 2,
    "plan": [
      {
        "ligne": 0, "classe_id": 98, "matiere_id": 10, "titre": "Examen session normale",
        "type": "examen", "periode": "semestre3", "debut": "2026-04-20 08:00",
        "duree_minutes": 120, "bareme": 20, "coefficient": 1, "is_published": true
      }
    ]
  }
}
```

### Réponse (exécution)

Même structure, avec `dry_run: false` et `evaluations_creees` : la liste des
identifiants créés.

## Historique

- **8 novembre 2026** — création de la route. Pas de modification ni de
  suppression d'évaluation : ces opérations restent dans l'interface web.
