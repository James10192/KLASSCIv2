# Rapport des effectifs par classe

## Pages et endpoints

- `GET /esbtp/rapports/effectifs` — écran HTML initial (`reports.view`).
- `GET /esbtp/rapports/effectifs/data` — actualisation AJAX JSON (`reports.view`, 60 requêtes/minute).
- `GET /esbtp/rapports/effectifs/pdf/preview` — PDF dans un onglet (`reports.view` + `reports.generate`, 30 requêtes/minute).
- `GET /esbtp/rapports/effectifs/pdf` — PDF téléchargé (`reports.view` + `reports.generate`, 10 requêtes/minute).

Tous les endpoints sont derrière l'authentification existante de KLASSCI, sur la base du tenant courant. Aucun identifiant de tenant n'est accepté. Aucune écriture métier n'est effectuée.

## Paramètres de requête

| Nom | Type / valeurs | Défaut | Rôle |
| --- | --- | --- | --- |
| `annee_universitaire_id` | Identifiant d'année existante | Année `is_current` | Filtrer les **inscriptions** de cette année (jamais la colonne année des classes) |
| `min_inscrits` | Entier `0..10000` | `15` | Nombre minimal d'élèves distincts dans une classe |
| `nombre_classes` | Entier `1..500` ou `all` | `20` | Limiter les classes **après** tri et seuil ; l'interface propose les valeurs courantes |
| `sort` | `effectif_desc`, `nom_asc` | `effectif_desc` | Tri décroissant par effectif ou alphabétique (puis ID) |
| `filiere_id` | ID existant (optionnel) | Aucun | Filière ou mention de la classe (selon cursus) |
| `niveau_id` | ID existant (optionnel) | Aucun | Niveau de la classe |
| `page` | Entier `1..100000` | `1` | Pagination de 20 classes de l'écran, ignorée pour les lignes du PDF |

## Réponse AJAX

```json
{
  "success": true,
  "html": "<section class=\"eff-results\">…</section>",
  "eligible_count": 26,
  "retained_count": 20,
  "unique_students": 450,
  "sum_class_enrollments": 463
}
```

Les nombres ci-dessus illustrent seulement le **format**. `eligible_count` couvre toutes les classes respectant les filtres avant plafond ; `retained_count` est le nombre retenu après plafond, sur toutes les pages ; `unique_students` compte les élèves uniques parmi ces classes ; `sum_class_enrollments` additionne les effectifs par classe, avec doubles appartenances possibles. Le fragment HTML ne contient que des agrégats, des noms de classes, filières/mentions et niveaux — jamais de données d'identification des élèves.

Les validations invalides rendent une erreur 422 (`errors` Laravel), une absence de permission 403. Le client empêche les réponses AJAX anciennes d'écraser les nouvelles, utilise un délai pour les champs numériques et garde les filtres dans l'URL.

## Définition d'un inscrit

Source unique `StudentCountService::requeteInscrits($anneeId)` : `esbtp_inscriptions.annee_universitaire_id`, `status=active`, `workflow_step=etudiant_cree`, `deleted_at IS NULL`. Une classe compte `COUNT(DISTINCT etudiant_id)` ; l'élève présent dans deux classes figure une fois dans chacune mais une seule fois dans `unique_students`. Les classes actives vides sont affichables avec `min_inscrits=0`, et les classes devenues inactives conservent leurs effectifs historiques valides. Les inscriptions sans classe sont exclues.

## Export PDF

Le PDF A4 reprend tous les rangs des classes retenues par les filtres, indépendamment de la page d'écran ; le maximum est de 1 000 classes (422 si dépassé). En-têtes de table répétés, nom, logo, couleurs et pied de page proviennent des paramètres du tenant via `<x-pdf-document>`, avec fallback visuel si le logo manque. Une empreinte du contenu et des paramètres tenant dans la clé de cache évite les exports périmés après évolution des inscriptions.
