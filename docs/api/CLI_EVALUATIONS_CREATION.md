# API CLI — Création d'évaluations

## `POST /api/cli/evaluations/creer`

Crée un lot d'évaluations (examens, devoirs…) dans une année
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
| `type` | Un des types saisissables (`devoir`, `examen`, `controle`…, liste de `ESBTPEvaluation::getTypes()`). |
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
- Limite de concurrence : le verrou sur la classe ne vaut que pour les créations
  faites par ce CLI. La création depuis l'écran (`store`) ne prend pas ce verrou :
  une création simultanée sur la même classe peut passer entre la vérification et
  l'écriture. Sans contrainte unique en base, le risque est résiduel.
- Les bornes (barème, coefficient), les types et les périodes sont ceux de
  l'écran de création. Les gardes du modèle (cohérence LMD/BTS, ECUE, période de
  spécialité) s'appliquent à chaque ligne ; la simulation les exécute réellement
  puis annule la transaction, donc elle prédit exactement l'exécution.
  Précision : la simulation n'écrit rien et n'invalide aucun cache (les invalidations
  sont planifiées après commit, et la transaction de simulation est annulée). Seule
  l'exécution réelle invalide les caches d'indicateurs académiques.
- Les titres commençant par « régularisation » suivi d’un espace (casse indifférente) sont refusés : ils désactivent le
  contrôle enseignant de l'observateur LMD, que le CLI ne doit pas contourner.
- Le CLI n'accepte pas `enseignant_id`. Pour une classe LMD, l'observateur
  `ESBTPEvaluationLmdTeacherObserver` valide l'enseignant : si le planning n'en
  résout pas un seul, la création est refusée (422) ; le pool ambigu aussi.
  Pour une classe BTS, l'évaluation est créée sans enseignant assigné.

### Réponse (simulation)

```json
{
  "success": true,
  "data": {
    "dry_run": true,
    "annee": { "id": 4, "name": "2025-2026" },
    "lignes": 1,
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
