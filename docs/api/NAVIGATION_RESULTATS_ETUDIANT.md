# Navigation contextuelle fiche étudiant ↔ résultats

## Routes existantes étendues

Ces routes renvoient une page HTML. La fiche BTS remplace son contenu via `fetch`
et la fiche LMD filtre les semestres visibles côté client, sans rechargement.
Les permissions des routes restent applicables côté serveur.

### BTS

`GET /esbtp/resultats/etudiant/{etudiant}`

| Paramètre | Format | Usage |
|---|---|---|
| `inscription_id` | entier, optionnel | Inscription exacte de l'étudiant, contrôlée contre l'étudiant et l'année |
| `annee_universitaire_id` | entier | Année propre à l'inscription (jamais substituer l'année courante) |
| `classe_id` | entier | Classe académique S1/S2 du parcours ; contrôlée si l'inscription est fournie |
| `periode` | `annuel`, `semestre1`, `semestre2` | Onglet initial, également compatible avec `1`/`2` |
| `include_all_statuses` | `0` ou `1` | Permet le contexte historique des inscriptions archivées |

Le contexte d'inscription est résolu par `BtsAnnualAggregationService` et
`BtsAnnualClassMapResolver` (tronc commun S1 / spécialisation S2). Un
`inscription_id` étranger à l'étudiant/année renvoie `404`. Une classe hors
du parcours déclaré renvoie `422`.

### LMD

`GET /esbtp/lmd/resultats/etudiant/{etudiant}`

| Paramètre | Format | Usage |
|---|---|---|
| `inscription_id` | entier, optionnel | Dossier LMD exact, même lorsqu'il concerne une ancienne année |
| `annee_universitaire_id` | entier | Année du dossier |
| `classe_id` | entier, optionnel | Restreint la sélection aux classes LMD de l'étudiant |
| `semestre` | entier (S1 à S10 selon niveau) | Filtre visible initial du semestre, validé via `getSemestresLMD()` |

Le changement de semestre modifie l'URL et les cartes visibles avec
`history.pushState` ; précédent/suivant restaure le filtre. Le changement
d'année utilise une requête HTML AJAX et abandonne l'ancien
`inscription_id`/`classe_id` pour éviter de mélanger les dossiers.

## Retour vers la fiche étudiant

Les deux écrans affichent « Fiche étudiant » uniquement si le droit
`students.view` est présent, avec `?tab=academique&inscription_id={id}`.
La fiche ouvre l'onglet Académique et repère la carte d'inscription visée.

## Historique

- 2026-10-16 : liens contextualisés depuis les inscriptions, conservation
  année/classe/période, filtre LMD sans rechargement, validation des liens
  profonds et navigation bidirectionnelle. Les anciens liens sans contexte
  restent acceptés (aucune rupture d'API).
