# Centre de pilotage académique

Ce runbook décrit l'exploitation du centre de pilotage académique et opérationnel KLASSCI. Les commandes ci-dessous ne remplacent pas les workflows BTS/LMD existants, elles rendent les fiches, alertes et snapshots exploitables par la direction.

## Statuts métier

Les fiches suivent le cycle `expected`, `submitted`, `received`, `in_entry`, `entered`, `controlled`, `validated`, `rejected`, `correction_requested`, `cancelled`.

Les entrées étudiant restent séparées de la note réelle et utilisent `expected`, `entered`, `absent`, `exempt`, `not_applicable`.

Une note existante peut donc marquer une entrée comme `entered` pendant un backfill, mais la fiche n'est jamais validée automatiquement.

## Piloter une fiche depuis l'interface

Ouvrir `/esbtp/pilotage-academique`, puis l'onglet **Notes et fiches**. Le bouton **Consulter** ouvre le détail sans recharger la page : étudiants attendus, notes déjà saisies, avancement, documents et historique complet.

Les actions visibles dépendent à la fois du statut de la fiche, du mode papier ou direct, des permissions de l'utilisateur et de son périmètre. L'interface ne propose jamais une transition interdite.

- Saisie directe : **Commencer la saisie**, **Terminer la saisie**, **Contrôler**, puis **Valider**.
- Fiche papier : **Transmettre**, **Recevoir**, **Commencer la saisie**, **Terminer la saisie**, **Contrôler**, puis **Valider**.
- Correction : **Demander une correction**, puis **Rouvrir**. Le motif est obligatoire et conservé dans l'historique.
- Rejet, annulation et réouverture d'une fiche validée exigent également un motif audité.

Un verrou de version empêche deux personnes de modifier silencieusement la même fiche. En cas de conflit, l'interface recharge uniquement le détail concerné et demande de confirmer l'action à partir de la version actuelle.

## Périmètre et traçabilité des acteurs

Le périmètre ne dépend pas d'une seule configuration préalable. KLASSCI reconnaît les classes d'un acteur à partir de plusieurs preuves réelles :

- affectation explicite par classe et année universitaire ;
- cours réellement assurés ;
- évaluations créées ou attribuées ;
- actions réalisées sur une fiche ;
- notes réellement saisies.

La Direction voit dans **Acteurs** le nombre de notes saisies, les corrections, les matières, les classes et les fiches finalisées par personne. La source canonique de l'attribution est `esbtp_notes.created_by` pour la saisie et `esbtp_notes.updated_by` pour la correction. Les fiches complètent cette preuve avec les étapes de remise, réception, contrôle et validation. L'onglet **Mon suivi** présente les mêmes indicateurs pour l'utilisateur connecté et explique les sources qui ont permis de reconnaître son périmètre.

## Cycle des alertes

Une alerte ouverte doit être prise en charge avant de passer en traitement. Les transitions manuelles autorisées sont :

- `open -> acknowledged` ou `open -> dismissed` ;
- `acknowledged -> in_progress`, `acknowledged -> resolved` ou `acknowledged -> dismissed` ;
- `in_progress -> resolved` ou `in_progress -> dismissed`.

Une alerte résolue ou classée ne peut pas être rouverte manuellement. Le moteur la rouvre automatiquement si la même anomalie est détectée à nouveau. Chaque changement exige un motif saisi dans la modale et conservé dans l'historique.

Des accès directs vers le centre sont disponibles depuis la fiche classe, l'évaluation, la saisie des notes et la préparation des bulletins. Ils conservent l'année, la période, le système et la classe dans l'URL, puis ouvrent directement l'onglet pertinent sans rechargement complet.

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

Dans l'interface, **Synchroniser la vue** effectue un recalcul complet lorsque la classe est sélectionnée. Sans classe, un utilisateur disposant du périmètre global rafraîchit uniquement le lot borné de snapshots obsolètes, afin de ne pas bloquer les tenants volumineux. Un utilisateur à périmètre restreint doit sélectionner l'une de ses classes.

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
