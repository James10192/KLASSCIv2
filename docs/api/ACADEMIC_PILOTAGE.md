# API du pilotage académique

Ces routes JSON alimentent le centre `/esbtp/pilotage-academique`. Elles utilisent la session Laravel, le jeton CSRF et les permissions académiques. Elles ne constituent pas une API publique.

## Lecture

`GET /esbtp/pilotage-academique/data`

Filtres : `year_id`, `period`, `system`, `class_id`.

La réponse contient notamment `summary`, `classes`, `alerts`, `sheets`, `students`, `actor_activity`, `scope` et `freshness`. `actor_activity.actors` expose les compteurs `notes_entered`, `notes_updated`, `notes_touched`, `subjects_count`, `classes_count` et `sheets_completed` calculés depuis les notes et fiches réelles.

## Synchronisation

`POST /esbtp/pilotage-academique/synchronize`

- Avec `class_id` : recalcule les alertes, les snapshots étudiants et le snapshot de la classe.
- Sans `class_id` et avec un périmètre global : traite un lot borné de snapshots obsolètes.
- Sans `class_id` et avec un périmètre restreint : retourne `422` et demande une classe autorisée.

La réponse contient `ok`, `message`, `filters`, `sync` et `failures`. Les réponses partielles utilisent le statut `207`.

## Alertes

`POST /esbtp/academic-alerts/{alert}/transition`

Body :

```json
{
  "status": "acknowledged",
  "reason": "Contrôle lancé avec le responsable de saisie."
}
```

Transitions autorisées :

- `open -> acknowledged|dismissed`
- `acknowledged -> in_progress|resolved|dismissed`
- `in_progress -> resolved|dismissed`

Une transition interdite retourne `422` avec le code `academic_pilotage.invalid_transition` et `details.allowed_transitions`. Le détecteur peut résoudre automatiquement une alerte devenue absente. Cette résolution système crée un événement `auto_resolved` sans acteur humain.

## Historique

- 2026-07-14 : ajout de la synchronisation globale bornée, du graphe de transitions, de la réconciliation automatique des alertes et des métriques d'activité des correcteurs.
