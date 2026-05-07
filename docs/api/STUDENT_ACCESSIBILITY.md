# API — Suivi accessibilité étudiants

> Endpoints liés au profil d'accessibilité (handicap, aménagements pédagogiques) d'un étudiant et au dashboard cohorte. Toutes les routes sont sous `auth + permission:admin.access + paywall`.

## Modèle

`App\Models\ESBTPStudentAccessibilityProfile` — 1↔1 avec `ESBTPEtudiant`. Auditable via OwenIt.

Champs principaux :

| Champ | Type | Description |
|---|---|---|
| `etudiant_id` | FK unique | Étudiant concerné |
| `has_official_recognition` | bool | Reconnaissance officielle (CDPH ou équivalent) |
| `recognition_reference` | string(100) | Référence du document officiel |
| `categories` | JSON array | Une ou plusieurs parmi : `motrice`, `visuelle`, `auditive`, `cognitive`, `psychique`, `dys`, `chronique`, `autre` |
| `short_description` | string(200) | Résumé visible aux enseignants |
| `full_description` | text | Description médicale complète — restreinte à `students.accessibility.view_full` |
| `accommodations` | JSON array | Une ou plusieurs parmi : `tiers_temps`, `salle_adaptee`, `support_agrandi`, `interprete_lsf`, `prise_de_notes`, `ordinateur_autorise`, `repos_examen`, `autre` |
| `accommodations_notes` | text | Notes restreintes à `view_full` |
| `requires_third_time` | bool (indexé) | Tiers-temps actif |
| `third_time_percentage` | int 0-100 | Défaut 33% |
| `assistant_required` | bool (indexé) | Assistant en classe nécessaire |
| `effective_from` / `effective_to` | dates | Période de validité du profil |

## Permissions

| Permission | Effet |
|---|---|
| `students.accessibility.view` | Voir le résumé (badge, chips, cohort dashboard) |
| `students.accessibility.view_full` | Voir la description médicale complète et les notes restreintes |
| `students.accessibility.edit` | Créer / mettre à jour / supprimer le profil |
| `students.accessibility.export` | Inclure les aménagements dans les PDF/Excel existants + accéder à l'export cohort |
| `students.accessibility.view_own` | Pour l'étudiant : voir son propre profil |

## Endpoints

### `GET /esbtp/accessibility`

Cohort dashboard. Permission : `students.accessibility.view`.

Query params (filtres optionnels) :
- `category` — clé de catégorie
- `accommodation` — clé d'aménagement
- `classe`, `filiere`, `niveau` — IDs
- `third_time_only=1`, `assistant_only=1`, `recognition_only=1` — toggles binaires

Renvoie : vue HTML `esbtp.accessibility.index` avec `$rows`, `$kpis`, `$appliedFilters`.

### `GET /esbtp/accessibility/preview-pdf`

Aperçu PDF inline. Permission : `students.accessibility.export`. Throttle : `60/min`.

### `GET /esbtp/accessibility/export-pdf`

Téléchargement PDF (attachment). Permission : `students.accessibility.export`. Throttle : `10/min`. Inclut une annexe « Détail médical » uniquement si l'utilisateur a `view_full`.

### `GET /esbtp/accessibility/export-excel`

Téléchargement Excel xlsx. Permission : `students.accessibility.export`. Throttle : `10/min`. Colonnes additionnelles `Description médicale` + `Notes aménagements` uniquement si `view_full`.

### `GET /esbtp/etudiants/{etudiant}/accessibility`

JSON détail du profil. Permission : `students.accessibility.view`.

Réponse :
```json
{
  "exists": true,
  "profile": {
    "id": 12,
    "has_official_recognition": true,
    "recognition_reference": "CDPH-2025-00834",
    "categories": ["visuelle"],
    "category_labels": ["Visuelle"],
    "short_description": "Déficience visuelle partielle",
    "full_description": null,
    "accommodations": ["tiers_temps", "support_agrandi"],
    "accommodation_labels": ["Tiers-temps aux examens", "Supports agrandis"],
    "accommodations_notes": null,
    "requires_third_time": true,
    "third_time_percentage": 33,
    "assistant_required": false,
    "effective_from": "2025-09-01",
    "effective_to": "2026-08-31",
    "currently_effective": true,
    "updated_at": "2026-05-07 14:32:00",
    "updated_by": "Marie K."
  }
}
```

`full_description` et `accommodations_notes` sont `null` si l'utilisateur n'a pas `view_full`.

### `POST /esbtp/etudiants/{etudiant}/accessibility`

Upsert du profil. Permission : `students.accessibility.edit`. Form data validé via `App\Http\Requests\StoreAccessibilityProfileRequest`.

### `DELETE /esbtp/etudiants/{etudiant}/accessibility`

Soft delete. Permission : `students.accessibility.edit`.

## Privacy

Les données d'accessibilité sont des données de santé (catégorie spéciale RGPD). Garanties techniques :

1. **Split de visibilité** : résumé court (`short_description`) accessible à `view`, détail médical (`full_description`) restreint à `view_full` — par défaut seuls coordinateur/secrétaire l'ont. L'enseignant ne voit jamais le diagnostic.
2. **Audit** : modèle `Auditable`. Toute modification (création, update, soft delete) est tracée dans `audits` avec user, timestamp, valeurs avant/après.
3. **Export gated** : les exports PDF/Excel n'incluent les colonnes accessibilité que si l'utilisateur a `students.accessibility.export`.
4. **Throttle** : 10 exports/min pour limiter les fuites en cas de compte compromis.

## Tests manuels recommandés

1. Créer un profil minimal sur un étudiant via la fiche edit, vérifier l'apparition du picto sur l'index étudiants et dans `liste-appel-pdf`.
2. Tester le rôle `enseignant` : il doit voir le résumé sans le diagnostic complet ; le bouton « Modifier » du profil ne doit pas apparaître.
3. Tester le rôle `etudiant` : il doit pouvoir consulter son propre profil (à connecter via la page de profil étudiant ; route `view_own` n'expose qu'un endpoint informel pour l'instant).
4. Filtrer la cohorte par classe + tiers-temps actif, vérifier que les KPIs reflètent la sous-cohorte.
5. Exporter PDF avec puis sans la permission `view_full`, vérifier que la section « Détail médical (annexe) » apparaît / disparaît.
