# Centre de pilotage académique

Ce runbook décrit l'exploitation du centre de pilotage académique et opérationnel KLASSCI. Les commandes ci-dessous ne remplacent pas les workflows BTS/LMD existants, elles rendent les fiches, alertes et snapshots exploitables par la direction.

## Statuts métier

Les fiches suivent le cycle `expected`, `submitted`, `received`, `in_entry`, `entered`, `controlled`, `validated`, `rejected`, `correction_requested`, `cancelled`.

Les entrées étudiant restent séparées de la note réelle et utilisent `expected`, `entered`, `absent`, `exempt`, `not_applicable`.

Une note existante peut donc marquer une entrée comme `entered` pendant un backfill, mais la fiche n'est jamais validée automatiquement.

## Backfill initial

Toujours lancer un dry-run avant l'exécution réelle :

```bash
php artisan academic-pilotage:backfill --dry-run --year-id=<ID_ANNEE> --json
```

Exécution réelle après contrôle :

```bash
php artisan academic-pilotage:backfill --year-id=<ID_ANNEE> --actor-id=<ID_USER>
```

`--actor-id` est obligatoire pour toute exécution réelle. Sans acteur explicite, la commande échoue afin de préserver une piste d'audit fiable.

Options utiles :

- `--class-id=<ID_CLASSE>` limite le traitement à une classe.
- `--period=semestre1` limite le traitement à une période.
- `--limit=500` borne le nombre d'évaluations traitées.
- `--json` produit une sortie machine-readable.

Le backfill crée une fiche par évaluation éligible non annulée, en mode saisie directe. Les fiches existantes reliées à une évaluation ne sont pas recréées.

## Rafraîchissement

Rafraîchir les snapshots obsolètes et les alertes globales :

```bash
php artisan academic-pilotage:refresh --limit=100
```

Cette commande orchestre :

- `academic-pilotage:refresh-snapshots`, pour recalculer les snapshots marqués obsolètes.
- `academic-pilotage:refresh-alerts`, pour recalculer les alertes idempotentes.

Les tâches planifiées exécutent déjà ces rafraîchissements toutes les quinze minutes avec `withoutOverlapping()` et `onOneServer()`.

Pour un rafraîchissement ciblé des alertes uniquement :

```bash
php artisan academic-pilotage:refresh-alerts --year-id=<ID_ANNEE> --period=semestre1 --class-id=<ID_CLASSE>
```

## Diagnostic

Diagnostic lisible :

```bash
php artisan academic-pilotage:diagnose --year-id=<ID_ANNEE> --period=semestre1
```

Diagnostic JSON pour `klassci-cli` :

```bash
php artisan academic-pilotage:diagnose --year-id=<ID_ANNEE> --json
```

Le diagnostic couvre :

- évaluations éligibles au backfill ;
- fiches existantes et distribution par statut ;
- alertes ouvertes et blocantes ;
- snapshots obsolètes et couverture de score insuffisante.

## Déploiement recommandé

1. Merger la PR vers `presentation`.
2. `klassci pull presentation`.
3. `klassci cache:clear presentation`.
4. `klassci migrate presentation`.
5. `klassci permissions:fix presentation`.
6. `php artisan academic-pilotage:backfill --dry-run --json`.
7. Exécuter le backfill réel après validation du dry-run.
8. `php artisan academic-pilotage:refresh`.
9. `php artisan academic-pilotage:diagnose --json`.
10. Valider l'interface `/esbtp/pilotage-academique` sans rechargement de page.

## Rollback

Le backfill n'efface pas les notes, bulletins, évaluations ou inscriptions. En cas de rollback applicatif, conserver les tables de pilotage ou restaurer le dump tenant pris avant migration.

Si une série de fiches doit être neutralisée sans suppression, utiliser le workflow métier d'annulation avec motif audité plutôt qu'une suppression SQL.
