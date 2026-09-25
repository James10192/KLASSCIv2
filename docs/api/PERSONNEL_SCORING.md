# API Performance Personnel

> **Retiré le 2026-09-25.** Le score du personnel (sur 100, niveaux, dimensions par permission) est supprimé, ainsi que la commande `personnel-scores:recalculate`. L’activité se lit en faits prévus / réalisés : voir [CLI_PERSONNEL_ACTIVITE.md](CLI_PERSONNEL_ACTIVITE.md). Ce document est conservé pour l’historique.


## Vue d'ensemble

Le module de scoring personnel expose des endpoints internes pour consulter les snapshots de performance calculés à partir des rôles, des permissions effectives et des obligations académiques explicitement attribuées.

Le score est automatique, sur 100. Une dimension historique reste mesurable selon son comportement antérieur. Toute dimension `non_applicable`, `insufficient_data` ou sans poids effectif reste explicite dans le détail, mais ne contribue pas au score total. Un score mesurable de zéro est classé `critical`; `insufficient_data` est réservé à l'absence de poids applicable.

## États et preuves

Chaque dimension du `breakdown` expose:

- `state`: `measurable`, `non_applicable` ou `insufficient_data`;
- `numerator` et `denominator` pour les dimensions d'obligation;
- `coverage` et `confidence`, valeurs de `0` à `1` lorsqu'elles sont mesurables;
- `evidence_hash`, empreinte SHA-256 déterministe des lignes sources.

Le snapshot persiste aussi `coverage`, `confidence`, `engine_version` et `evidence_hash`. La couverture et la confiance agrégées utilisent les mêmes poids de dimensions que le score total. Une dimension historique mesurable sans métadonnées de preuve contribue avec une couverture et une confiance de `1`. Ces colonnes sont nullables pour conserver la compatibilité avec les snapshots antérieurs et les tenants dont le schéma est en cours de mise à niveau.

### Obligations académiques

- `grades_activity`: le dénominateur contient uniquement les fiches actives, non annulées, dont `teacher_id` correspond au profil enseignant et dont l'échéance appartient à la période. Une fiche papier est remplie par `submitted_at`, une fiche directe par `entered_at`, à condition que cet horodatage ne dépasse pas la fin de période.
- `academic_workflow`: le dénominateur contient uniquement les fiches papier actives, non annulées, dont `assigned_processor_id` correspond à l'utilisateur et dont `received_at` appartient à la période. La saisie n'est remplie que si `entered_at` ne dépasse pas la fin de période. Les affectations générales de classe ne constituent pas une propriété de fiche.
- Si la table source ou une colonne requise manque, la dimension retourne `insufficient_data`. Les capacités du schéma source sont mémorisées pendant la durée de vie du service.

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

- 2026-07-11 : exclusion des obligations annulées, restriction des saisies déléguées au papier, bornage temporel des réalisations, agrégation pondérée des preuves et migration compatible avec les schémas partiels.
- 2026-07-11 : ajout des obligations académiques, des états de mesure et des preuves versionnées, sans rupture des dimensions historiques.
- 2026-07-02 : création de la V1 automatique basée sur les permissions effectives.
