# API CLI du pilotage académique

Ces endpoints sont réservés aux jetons Sanctum CLI. Ils permettent de diagnostiquer et d'initialiser le centre de pilotage sans accès au terminal du serveur.

## Endpoints

- `GET /api/cli/academic-pilotage/diagnose` nécessite `cli:admin`.
- `POST /api/cli/academic-pilotage/backfill` nécessite `cli:admin`.
- `POST /api/cli/academic-pilotage/refresh` nécessite `cli:admin`.

Le diagnostic retourne `cohorte` : la cohorte active (inscriptions actives, étudiant créé, classes actives, année et période données), réparties en `sans_ligne` (aucun instantané pour le lecteur), `ligne_sale` (instantané invalidé seulement), `exploitable` et `systeme_non_classable`. `sans_instantane_exploitable` additionne les deux premières. Le bloc porte `perimetre` (classes actives, sans filtre d'année sur la classe) et `couples_cohorte`, le total des couples comptés. Sans année courante, les compteurs valent `null` et `motif` explique pourquoi. Côté commande (CLI), une période non reconnue ou un identifiant non valide donne aussi un motif ; côté HTTP, l'API refuse ces valeurs en 422 avant d'appeler la commande. Une option vide est traitée comme absente. Défauts de la cohorte : période `semestre1` et année courante si elles sont omises ; les autres blocs du diagnostic ne filtrent pas par défaut.

Le diagnostic retourne aussi `snapshots.failed_samples`, limité aux dix derniers échecs. Chaque élément expose le scope, le nombre de tentatives et une empreinte de corrélation. Le message technique reste exclusivement dans les logs serveur.

Les filtres facultatifs sont `year_id`, `class_id` et `period`. La période accepte `semestre1`, `semestre2` ou `annuel`.

Le backfill est toujours un dry-run si `dry_run` est omis. Une écriture exige explicitement :

```json
{
  "dry_run": false,
  "confirm": true,
  "limit": 500
}
```

Les fiches créées depuis les évaluations existantes conservent leur historique. Les notes déjà présentes alimentent les entrées, mais aucune fiche n'est automatiquement marquée comme validée.

## Historique

- 2026-07-14 : ajout du diagnostic, du backfill protégé et du recalcul distant.
- 2026-07-14 : ajout des détails de recalcul en échec dans le diagnostic.
- Octobre 2026 : `cohorte` ajouté au diagnostic. Changement additif, non cassant : aucun champ retiré ni renommé.
