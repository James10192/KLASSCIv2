# API CLI — Recalcul des moyennes par matière

Rafraîchir `esbtp_resultats` depuis les notes réellement en base, sur un
périmètre explicite, et savoir si un job dispatché a une chance de tourner.

| | |
|---|---|
| Base | `/api/cli` |
| Authentification | Bearer Sanctum |
| Abilities | `cli:read` pour le diagnostic de file, `cli:admin` pour le recalcul |
| Contrôleur | `App\Http\Controllers\API\CLI\CLIMaintenanceController` |
| Calcul | `App\Jobs\RecomputeStudentResultatJob` (le même que l'observateur) |

## Pourquoi ces deux points d'entrée existent

### L'agrégat périmé l'emporte sur les notes

`esbtp_resultats` n'est pas un cache d'affichage : c'est la valeur qui **gagne**.
`BtsCurrentResultSnapshotService` et `MoyennesDeLAperçu` donnent la préséance à
la ligne enregistrée — quand elle existe, elle écrase la moyenne calculée depuis
les notes. Une ligne qui ne suit plus ses notes ne provoque donc ni erreur ni
page cassée : elle affiche, et persiste, un chiffre faux.

Mesuré le 20 septembre 2026 sur `esbtp-abidjan`, après qu'une évaluation eut été
déplacée vers sa bonne matière : élève 149, matière 14, semestre 2 — l'agrégat
disait **15**, les cinq notes (10, 20, 10, 18, 20) disent **15,6**. Le 18 était
en base, au bon endroit, et avalé.

### La cause : un `update()` de query builder n'émet aucun événement

Les deux endroits qui déplacent une évaluation propagent la colonne dénormalisée
`esbtp_notes.matiere_id` ainsi :

```php
ESBTPNote::where('evaluation_id', $id)->update(['matiere_id' => $cible->id]);
```

Un `update()` de **query builder** ne passe pas par Eloquent. `ESBTPNoteObserver`
ne tourne pas, `RecomputeStudentResultatJob` n'est jamais dispatché, et les deux
coordonnées — celle qu'on quitte comme celle qu'on rejoint — restent figées.

C'est corrigé depuis septembre 2026 par `App\Domain\Notes\RecalculApresDeplacement`,
appelé par l'endpoint CLI **et** par l'écran web (`ESBTPEvaluationController::update()`,
qui peut bouger trois dimensions d'un coup : classe, matière, période).

### Ce que le recalcul après déplacement ne fait PAS, et pourquoi

`NoteCalculationService::studentMatiereAverage([])` rend **0.0**, pas `null`.
Rejouer le calcul sur une coordonnée que le déplacement a vidée de toutes ses
notes n'effacerait donc pas la ligne : il y **écrirait un 0/20**, sur une matière
que l'élève n'a plus. C'est strictement pire que la valeur périmée.

L'ancienne coordonnée n'est donc recalculée que s'il y reste au moins une note.
Sinon la ligne est **signalée, jamais touchée** — son sort est une décision
d'école (`.claude/rules/rien-en-dur.md`, « le cas particulier du zéro »). Le
ménage se fait sciemment, avec `evaluations:sync-notes --clean-resultats` borné.

## `POST /api/cli/notes/recompute`

Ability `cli:admin`.

| Champ | Requis | Détail |
|---|---|---|
| `classe_id` | oui | |
| `periode` | oui | `semestre1` \| `semestre2` \| `annuel` |
| `annee_universitaire_id` | oui | |
| `matiere_id` | non | restreint davantage |
| `etudiant_id` | non | restreint davantage |
| `dry_run` | non | défaut `false` |

**Le périmètre est obligatoire, et c'est le point.** La commande artisan
`notes:recompute` accepte de tourner sans aucun filtre et balaie alors l'école
entière. Un recalcul **écrase** `esbtp_resultats.moyenne` : lâché sans bornes sur
une instance Élite, il effacerait d'un coup toutes les moyennes saisies à la main
par l'école, sans que rien ne le signale. Cet endpoint refuse donc de tourner à
l'aveugle, et plafonne à **600 couples (étudiant, matière)** par appel.

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"classe_id":30,"matiere_id":14,"periode":"semestre2","annee_universitaire_id":4,"dry_run":true}' \
  "$BASE/api/cli/notes/recompute"
```

`dry_run` liste les couples visés avec leur moyenne enregistrée. Il ne **prédit**
pas la valeur d'après : la prédire demanderait de réécrire la sélection des notes
à côté de celle du job, et deux formules qui divergent sont exactement le défaut
que cette famille de bugs illustre.

L'exécution réelle rend `moyenne_avant` et `moyenne_apres` pour chaque couple —
elle se vérifie donc d'elle-même :

```jsonc
{
  "success": true,
  "message": "5 couple(s) recalcule(s), 1 moyenne(s) modifiee(s), 0 echec(s).",
  "data": {
    "perimetre": { "classe_id": 30, "matiere_id": 14, "periode": "semestre2",
                   "annee_universitaire_id": 4 },
    "couples": [
      { "etudiant_id": 149, "matiere_id": 14,
        "moyenne_avant": 15, "moyenne_apres": 15.6, "change": true }
    ],
    "total": 5, "modifies": 1, "echecs": 0
  }
}
```

Le job tourne **sur place** (`dispatchSync`), jamais sur la file : rien ne prouve
qu'un worker tourne sur les instances mutualisées. D'où le plafond, qui protège
le temps de réponse plutôt que la file.

⚠️ **Une moyenne recalculée ne régénère pas les bulletins déjà produits.**
`esbtp_bulletins.moyenne_generale` est un instantané figé (piège #6 de
`.claude/rules/klassci-debugging-discipline.md`) ; le job se contente de
« toucher » le bulletin pour le marquer à régénérer. La régénération reste un
geste explicite, **à faire après** le recalcul, sinon c'est la valeur périmée
qu'on fige.

## `GET /api/cli/diagnostics/queue`

Ability `cli:read`. Lecture seule, aucun paramètre.

Dit si un job dispatché a une chance d'être exécuté un jour. `config/queue.php`
vaut `database` par défaut et `app/Console/Kernel.php` ne planifie **aucun**
`queue:work` : sur une instance où aucun worker ne tourne, tout ce qui est
dispatché s'empile dans `jobs` sans jamais être traité. C'est invisible — aucune
erreur, aucune page cassée, juste des agrégats qui ne se rafraîchissent plus.
`ESBTPNoteObserver` en dépend à **chaque saisie de note**, donc la réponse de ce
diagnostic décide si les moyennes d'une instance sont tenues à jour ou figées.

```jsonc
{
  "success": true,
  "message": "Le plus ancien job attend depuis 37 h : aucun worker ne traite la file. Les agregats de notes ne se rafraichissent plus.",
  "data": {
    "driver": "database", "synchrone": false,
    "en_attente": 1204, "plus_ancien_created_at": 1758300000,
    "plus_ancien_age_heures": 37, "echoues": 3
  }
}
```

## Historique

- **Septembre 2026** — création. Déclenchée par un agrégat laissé périmé après un
  déplacement d'évaluation fait par l'endpoint `POST /api/cli/evaluations/{id}/matiere`,
  qui déplaçait bien les notes mais ne rafraîchissait rien.

## Voir aussi

- `docs/api/CLI_COHERENCE_SYSTEME.md` — le déplacement d'une évaluation
- `app/Domain/Notes/RecalculApresDeplacement.php` — le recalcul des deux côtés
- `.claude/rules/klassci-debugging-discipline.md` — pièges #6, #7 et #13 : instantanés
  figés, dénormalisation périmée, et deux sources de vérité qui divergent sur un même écran
