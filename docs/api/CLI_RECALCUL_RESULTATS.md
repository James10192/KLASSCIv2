# API CLI — Recalcul des moyennes par matière

Rafraîchir `esbtp_resultats` depuis les notes réellement en base, sur un
périmètre explicite.

| | |
|---|---|
| Base | `/api/cli` |
| Authentification | Bearer Sanctum |
| Ability | `cli:admin` |
| Contrôleur | `App\Http\Controllers\API\CLI\CLINotesRecomputeController` |
| Sélection des couples | `App\Domain\Notes\PerimetreDeRecalcul` — partagée avec `notes:recompute` |
| Calcul | `App\Jobs\RecomputeStudentResultatJob` — le même que l'observateur |

## Pourquoi ce point d'entrée existe

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

Les endroits qui déplacent une évaluation propagent les colonnes dénormalisées
de `esbtp_notes` ainsi :

```php
ESBTPNote::where('evaluation_id', $id)->update(['matiere_id' => $cible->id]);
```

Un `update()` de **query builder** ne passe pas par Eloquent. `ESBTPNoteObserver`
ne tourne pas, `RecomputeStudentResultatJob` n'est jamais dispatché, et les deux
coordonnées — celle qu'on quitte comme celle qu'on rejoint — restent figées.

**Ils sont CINQ, et ce compte a été faux deux fois.** La première version de ce
correctif en couvrait deux tout en publiant « les deux chemins sont corrigés » ;
la deuxième en a annoncé quatre, et c'était encore incomplet. Publier un
inventaire comme exhaustif ferme l'enquête suivante — c'est plus cher que le
défaut lui-même.

| chemin | ce qu'il déplace | recalcul |
|---|---|---|
| `ESBTPEvaluationController::update()` (écran) | classe, matière, période | à l'unité |
| `POST /api/cli/evaluations/{id}/matiere` | matière | à l'unité |
| `POST /api/cli/evaluations/deplacer-periode` | période, jusqu'à 200 évaluations | en lot, plafonné |
| `POST /api/cli/diagnostics/evaluations-periode/repair` | période, en masse | en lot, plafonné |
| `MergeDuplicateEcue` (LMD, sous `force`) | matière, en masse | **aucun** |

⚠️ **Ce tableau compte les endroits qui changent les coordonnées d'une
ÉVALUATION, et il liste ceux trouvés à ce jour — pas ceux qui existent.** La
nuance n'est pas rhétorique : deux commandes écrivent les mêmes colonnes
dénormalisées sans déplacer d'évaluation, et sans recalcul —
`evaluations:sync-notes` (qui réaligne `classe_id`, `matiere_id` et `semestre`
d'un seul `update()`, et qui est l'outil recommandé plus bas pour le ménage) et
`notes:sync-periodes`. Elles ne sont pas branchées à dessein : `sync-notes`
tourne sans bornes sur l'école entière, et y ajouter un recalcul synchrone par
note est exactement ce que les deux plafonds cherchent à éviter.

Le cinquième, `app/Domain/LMD/Actions/MergeDuplicateEcue.php`, est atteignable
par `POST /esbtp/lmd/reconciliation/merge` avec `type=ecue&force=true`. Il
reparente `esbtp_evaluations.matiere_id` **et** `esbtp_notes.matiere_id` vers
l'ECUE canonique, puis met l'absorbée de côté — sans rien recalculer.

**Il n'est volontairement pas corrigé ici**, et la raison n'est pas qu'il serait
sans danger : c'est un autre domaine (la réconciliation LMD, dont les agrégats
sont `esbtp_lmd_resultat_ecue`), il est gardé par un drapeau `force`, et sur une
instance saine `ESBTPEvaluation::booted()` refuse déjà qu'une ECUE soit évaluée
dans une classe BTS — donc il ne devrait pas croiser `esbtp_resultats`. « Ne
devrait pas » n'est pas « ne peut pas » : sur une instance portant des lignes
héritées (la « famille 2 » de `.claude/rules/lmd-ecue-leak-bts-picker.md`), il
laisserait le même agrégat périmé. C'est un chantier à lui, pas une ligne à
glisser dans celui-ci.

`periode` est une coordonnée de la clé d'`esbtp_resultats` au même titre que
`matiere_id` : un changement de semestre laisse exactement le même agrégat
périmé.

### Deux bornes, et il faut les deux

`App\Domain\Notes\RecalculApresDeplacement` plafonne à **400 notes par classe et
par année**, *et* à **1200 notes par requête**, tous périmètres confondus.

La première seule ne suffisait pas — c'était le plafond par lot, et un appel
touchant cinq classes dont une seule est lourde ne recalculait **aucune** des
quatre autres. Mais **la seconde seule ne bornait plus rien** : le nombre de
classes n'est limité nulle part (`deplacer` accepte 200 évaluations réparties
sur autant de classes, `evaluations-periode/repair` n'a aucun `LIMIT`), donc
vingt classes à 399 notes passaient toutes sous le plafond — huit mille notes
recalculées sur place dans une seule requête HTTP.

Et le mode d'échec était le plus mauvais des deux. Le recalcul est hors
transaction à dessein : les évaluations sont **déjà enregistrées**. Si la requête
meurt sur le délai d'attente, on garde des évaluations déplacées, des agrégats
rafraîchis à moitié, et surtout `perimetres_reportes` — tout l'objet du
mécanisme — **n'arrive jamais**, puisque la réponse n'arrive pas. Le plafond par
lot, lui, refusait proprement et le disait.

Tout périmètre non traité part donc dans `perimetres_reportes` avec sa `raison`
(`plafond_classe` ou `budget_de_la_requete_epuise`) et les paramètres exacts à
rejouer : `classe_id`, `annee_universitaire_id`, les `periodes` sous leur forme
canonique `semestreN`, et les `matiere_ids` pour découper si le rattrapage bute
à son tour sur son propre plafond de couples.

### Ce que le recalcul après déplacement ne fait PAS, et pourquoi

`NoteCalculationService::studentMatiereAverage([])` rend **0.0**, pas `null`.
Rejouer le calcul sur une coordonnée que le déplacement a vidée de toutes ses
notes n'effacerait donc pas la ligne : il y **écrirait un 0/20**, sur une matière
que l'élève n'a plus. C'est strictement pire que la valeur périmée.

L'ancienne coordonnée n'est donc recalculée que s'il y reste au moins une note.
Sinon la ligne est **signalée, jamais touchée** — son sort est une décision
d'école (`.claude/rules/rien-en-dur.md`, « le cas particulier du zéro »). Le
ménage se fait sciemment, avec `evaluations:sync-notes --clean-resultats` borné.

⚠️ Cela ne garantit pas une valeur non nulle du côté qu'on rejoint : si toutes
les notes déplacées sont des absences, le calcul les écarte et rend 0, comme
partout ailleurs dans le recalcul.

## `POST /api/cli/notes/recompute`

| Champ | Requis | Détail |
|---|---|---|
| `classe_id` | oui | |
| `periode` | oui | `semestre1` \| `semestre2` |
| `annee_universitaire_id` | oui | |
| `matiere_id` | non | restreint davantage |
| `etudiant_id` | non | restreint davantage |
| `dry_run` | non | défaut `false` |

**Le périmètre est obligatoire, et c'est le point.** La commande artisan
`notes:recompute` accepte de tourner sans aucun filtre et balaie alors l'école
entière. Un recalcul **écrase** `esbtp_resultats.moyenne` : lâché sans bornes sur
une instance Élite, il effacerait d'un coup toutes les moyennes saisies à la main
par l'école. D'où le refus de tourner à l'aveugle, et le plafond de **500 couples
(étudiant, matière)** par appel — une classe de 40 élèves sur 12 matières tient
dessous ; au-delà, le périmètre se découpe par matière.

Ce plafond valait 600, sans raison derrière le chiffre. Ce qui se compte, lui, se
lit dans le code : chaque couple coûte une lecture, l'exécution du job sur place
(ses requêtes, plus un `touch()` de bulletin) puis une seconde lecture — soit, à
600, de l'ordre de 1200 lectures et 600 exécutions de job dans une seule requête
HTTP. Qu'un tel appel dépasse le délai d'attente du serveur mutualisé est
**probable mais non mesuré** : le chronométrage sur une instance Élite reste à
faire. 500 et non 200, parce que le geste légitime de cet endpoint est le
recalcul d'une classe entière — 40 élèves sur 12 matières, 480 couples — et qu'un
plafond sous ce chiffre refuserait le cas normal.

⚠️ **`periode` désigne un semestre, pas une écriture.** `esbtp_evaluations.periode`
porte historiquement `'1'` et `'2'` autant que `'semestre1'` et `'semestre2'` ; le
périmètre accepte `semestre1` / `semestre2` et retient **les deux écritures**
(`ESBTPEvaluation::aliasDePeriode()`). Ça n'a pas toujours été le cas, et la
correction a d'abord ouvert pire que ce qu'elle fermait : le périmètre voyait
l'évaluation encodée `'1'`, mais le recalcul relisait ses notes sur la seule forme
canonique, n'en trouvait aucune, et **écrivait un 0/20 par-dessus une moyenne
réelle** — en annonçant « 0 moyenne modifiée ». Mesuré : 14,00 devenu 0,00. La
conversion vit désormais en un seul endroit, sur le modèle.

⚠️ `annuel` n'est **pas** accepté, et c'est délibéré : une évaluation ne porte
jamais cette période, donc le périmètre serait toujours vide et l'appel rendrait
un succès rassurant sans avoir rien recalculé. Les lignes annuelles
d'`esbtp_resultats` existent pourtant, écrites par la branche `annuel` de
`BulletinService::calculateStudentAverageForPeriode()` — les rafraîchir demande
un autre chemin, qui n'est pas celui-ci.

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
      { "etudiant_id": 149, "matiere_id": 14, "periode": "semestre2",
        "moyenne_avant": 15, "moyenne_apres": 15.6, "change": true }
    ],
    "total": 5, "modifies": 1, "echecs": 0
  }
}
```

### Le job tourne sur place, pas sur la file

`dispatchSync`, jamais `dispatch` : rien ne prouve qu'un worker tourne sur les
instances mutualisées — `config/queue.php` vaut `database` par défaut et
`app/Console/Kernel.php` ne planifie aucun `queue:work`. Dispatcher aurait donné
un correctif qui a l'air posé et ne s'exécute jamais. D'où le plafond, qui
protège le temps de réponse plutôt que la file.

⚠️ **Ce que ce choix laisse ouvert, et qui dépasse ce correctif** :
`ESBTPNoteObserver` dispatche, lui, de façon **asynchrone**, à chaque saisie de
note. Si aucun worker ne tourne sur les huit instances, alors aucune saisie n'a
jamais rafraîchi `esbtp_resultats`. Le contrôle qui tranche, sur une instance :

```sql
SELECT COUNT(*), MAX(recomputed_at) FROM esbtp_resultats_recompute_log WHERE source = 'observer';
SELECT COUNT(*) FROM jobs;
```

Un compte nul ou figé veut dire que le mécanisme n'a jamais tourné — ce qui
serait un défaut d'un ordre de grandeur au-dessus de celui corrigé ici.

### La trace d'audit

Chaque recalcul écrit une ligne dans `esbtp_resultats_recompute_log`
(moyenne avant, après, source, déclencheur). C'est ce qui rend l'écrasement
réversible à la lecture.

⚠️ **Cette garantie a été fausse pendant toute la première version du
correctif.** La colonne `source` était un `enum('observer','command','manual')` ;
les sources `deplacement` et `cli` la faisaient **lever** sous
`STRICT_TRANS_TABLES`, et `writeAuditLog()` avalait l'exception dans un `catch`
muet. Mesuré : 53 « audit log write failed » dans une seule suite de tests, pour
94 recalculs — zéro ligne écrite, aucune erreur visible. La colonne est passée en
`string(30)` (migration `elargir_source_du_journal_de_recalcul`), sur le
précédent de `cash_counts.mode_paiement`, et le rattrapage nomme désormais ce
qu'il rattrape.

## Ce qui ne régénère PAS les bulletins

Une moyenne recalculée ne régénère pas les bulletins déjà produits.
`esbtp_bulletins.moyenne_generale` est un instantané figé (piège #6 de
`.claude/rules/klassci-debugging-discipline.md`) ; le job se contente de
« toucher » le bulletin pour le marquer à régénérer. La régénération reste un
geste explicite, **à faire après** le recalcul, sinon c'est la valeur périmée
qu'on fige.

## Historique

- **Septembre 2026** — création. Déclenchée par un agrégat laissé périmé après un
  déplacement d'évaluation fait par `POST /api/cli/evaluations/{id}/matiere`, qui
  déplaçait bien les notes mais ne rafraîchissait rien.

## Voir aussi

- `docs/api/CLI_COHERENCE_SYSTEME.md` — le déplacement d'une évaluation
- `app/Domain/Notes/RecalculApresDeplacement.php` — le recalcul des deux côtés
- `app/Domain/Notes/PerimetreDeRecalcul.php` — la sélection partagée
- `.claude/rules/klassci-debugging-discipline.md` — pièges #6, #7 et #13
