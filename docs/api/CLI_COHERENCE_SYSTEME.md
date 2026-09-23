# API CLI — Cohérence BTS / LMD des matières notées

Recenser et réparer les matières posées dans le **mauvais système académique** :
une ECUE du LMD évaluée dans une classe BTS, ou l'inverse.

| | |
|---|---|
| Base | `/api/cli` |
| Authentification | Bearer Sanctum |
| Abilities | `cli:read` en lecture, `cli:admin` en écriture |
| Contrôleurs | `CLIMaintenanceController` (diagnostic), `CLIEvaluationMatiereController` (rebascule) — `App\Http\Controllers\API\CLI` |
| Prédicat partagé | `App\Domain\Academique\CoherenceSystemeAcademique` |

## Ce qui rend ce diagnostic nécessaire

`esbtp_matieres` est partagée par les deux cursus : une matière portant une
`unite_enseignement_id` est un élément constitutif (ECUE) du LMD, les autres sont
des matières BTS. Une classe BTS attend les secondes.

**Le bulletin BTS est piloté par les NOTES.** Une matière notée y figure, que la
maquette la connaisse ou non — `BulletinSubjectRowsCompleter` le dit lui-même :
« la maquette ajoute des lignes, elle n'en retire jamais ». Une ECUE évaluée dans
une classe BTS entre donc dans la moyenne.

Mesuré en septembre 2026 sur `esbtp-abidjan` : l'ECUE `TPGC641` « OGC » évaluée
dans la classe BTS **2BTS GBAT E** portait **34 notes**. Rejoué en test, un 14/20
en matière BTS (coef 2) accompagné d'un 4/20 sur l'ECUE (coef 1) rendait une
moyenne générale de **10,67 au lieu de 14,00**, persistée dans `esbtp_resultats`,
`esbtp_resultats_matieres` et `esbtp_bulletins.moyenne_generale`.

## Deux familles, et la seconde a longtemps échappé au relevé

| Famille | Table | Depuis quand elle est refusée à l'écriture |
|---|---|---|
| Évaluation sur une matière du mauvais système | `esbtp_evaluations` | août 2026 (`ESBTPEvaluation::booted()`) |
| **Moyenne « manuelle »** — saisie à la main, **ou écrite par une génération antérieure** | `esbtp_resultats` | **septembre 2026** (`ESBTPResultat::booted()`) |

La seconde atteint la **même ligne de bulletin** sans passer par aucune
évaluation : `ESBTPResultatController` y écrit depuis trois endroits, dont
`bulkUpdateMoyennes` en AJAX. Ce diagnostic ne voyait que la première **et a été
pris pour l'inventaire complet** ; c'est ce qui a laissé la seconde hors de tout
recensement pendant un mois.

## `GET /api/cli/diagnostics/evaluation-system-mismatch`

Ability `cli:read`. Lecture seule, aucun paramètre. Chaque famille est plafonnée à
500 lignes, signalées par son propre `tronque`.

```bash
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE/api/cli/diagnostics/evaluation-system-mismatch"
```

```jsonc
{
  "success": true,
  "message": "1 evaluation(s) et 0 moyenne(s) manuelle(s) incoherente(s).",
  "data": {
    "total": 1,                    // évaluations seulement (nom conservé pour compat)
    "sans_note": 0,
    "avec_notes": 1,
    "tronque": false,
    "details": [{
      "evaluation_id": 4117, "titre": "Devoir 1",
      "classe": "2BTS GBAT E", "classe_id": 88, "systeme_classe": "BTS",
      "matiere": "OGC", "matiere_id": 185, "code_matiere": "TPGC641",
      "nature_matiere": "ECUE LMD", "notes_saisies": 34
    }],
    "moyennes_manuelles": {
      "total": 0, "tronque": false, "details": []
    },
    "total_toutes_familles": 1
  }
}
```

⚠️ **`total` ne compte que les évaluations.** Le nom est conservé pour ne pas
casser les appelants existants ; le décompte des deux familles est
`total_toutes_familles`.

## Réparer — `POST /api/cli/evaluations/{id}/matiere`

Ability `cli:admin`. **Simule par défaut** (`dry_run`). Le mouvement n'est
autorisé que s'il **rétablit** la cohérence, jamais s'il la rompt, et déplace en
même temps la copie dénormalisée `esbtp_notes.matiere_id` — sans quoi les notes
resteraient rattachées à l'ancienne matière et le bulletin continuerait de
l'afficher.

⚠️ **Cette description a été incomplète jusqu'en septembre 2026, et elle a coûté
cher.** Déplacer les notes ne suffit pas : cet `update()` est un update de
**query builder**, il n'émet aucun événement Eloquent, donc `ESBTPNoteObserver`
ne tourne pas et aucun recalcul n'est déclenché. Les deux coordonnées — celle
qu'on quitte comme celle qu'on rejoint — gardaient la moyenne d'avant dans
`esbtp_resultats`, et **cette moyenne périmée l'emporte sur les notes**. Mesuré
le 20 septembre 2026 sur `esbtp-abidjan` après un déplacement : élève 149,
matière 14, l'agrégat disait 15, les cinq notes disent 15,6.

La réponse porte désormais `recalculs_tentes`, `agregats_orphelins` et
`recalculs_en_echec` ; le détail du recalcul, et ce qu'il refuse délibérément de
faire, est dans [CLI_RECALCUL_RESULTATS.md](CLI_RECALCUL_RESULTATS.md).

**Cinq chemins déplacent une évaluation, à ce jour** — c'est un relevé, pas un
inventaire garanti. Les cinq sont branchés sur le recalcul, y compris
`MergeDuplicateEcue` sous `force` depuis septembre 2026 ;
[CLI_RECALCUL_RESULTATS.md](CLI_RECALCUL_RESULTATS.md) dit ce que ce dernier
rapporte, et ce qui n'est pas établi. Un nouveau
chemin qui écrit `esbtp_notes` par un `update()` de query builder doit appeler
`RecalculApresDeplacement` : aucun observateur ne le fera à sa place.

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"matiere_id": 42, "dry_run": true}' \
  "$BASE/api/cli/evaluations/4117/matiere"
```

## Le sort des notes trouvées n'est pas une décision de code

Ces notes ont été saisies par quelqu'un : elles sont mal rangées, pas
illégitimes. ⚠️ Sur une instance déjà touchée, une bonne part des lignes
`esbtp_resultats` incohérentes n'ont **pas** été tapées par une personne : elles
ont été écrites par `persistResultats()` lors d'une génération antérieure. Ne
lisez donc pas « moyenne manuelle » comme « quelqu'un l'a voulue » au moment
d'arbitrer. **Ne les effacez jamais d'office** — c'est une politique d'école
(`.claude/rules/rien-en-dur.md`) :

1. **Rebasculer vers la bonne matière BTS** (endpoint ci-dessus) — si le travail
   évalué a un équivalent au référentiel BTS, il doit compter.
2. **Déplacer l'évaluation vers sa classe LMD** — le garde l'accepte dès que
   classe et matière redeviennent du même côté. L'endpoint ci-dessus ne change
   que la matière : ce déplacement se fait par l'écran.
3. **Annuler l'évaluation** (`status = cancelled`) — exclue du bulletin **par le
   chemin de génération**, les notes restent en base et tracées.

   ⚠️ **Ce n'est pas vrai de tous les chemins.** La moyenne **annuelle** de
   secours (`BulletinService::calculateStudentAverageForPeriode()`, branche
   `annuel`, celle qu'emprunte le rattrapage des bulletins sans moyenne) ne
   filtre ni le statut `cancelled`, ni l'année, ni la classe — et son résultat
   est **écrit** dans `esbtp_bulletins.moyenne_generale`. Annuler l'évaluation
   ne la retire donc pas de cette valeur-là. Le cadrage de cette requête est un
   changement de comportement sur une donnée écrite : il est identifié,
   volontairement reporté, et commenté sur place dans le code.

Côté génération, la ligne est de toute façon **écartée du bulletin et journalisée**
(`Log::warning`, dédoublonnée par couple classe × matière) depuis septembre 2026 :
l'élève n'est pas puni d'une erreur de saisie, mais l'écart ne se fait pas en
silence.

**Les bulletins déjà générés gardent leur moyenne figée** : `moyenne_generale`
n'est jamais recalculée automatiquement (piège #6 de
`.claude/rules/klassci-debugging-discipline.md`). Une régénération explicite est
nécessaire.

## Historique

- **Septembre 2026 (bis)** — le cinquième chemin, la fusion d'ECUE en double
  (`MergeDuplicateEcue` sous `force`), recalcule à son tour, par
  `RecalculApresDeplacement::pourUnLot()`, et reporte les lignes de bulletin LMD
  avec leur note de rattrapage. Pas de changement de contrat pour les endpoints
  CLI de ce document.
- **Septembre 2026** — **quatre des cinq** chemins trouvés qui déplacent une
  évaluation recalculent les agrégats des deux côtés. Ils déplaçaient les notes
  sans rien rafraîchir, et l'agrégat périmé gagne sur les notes : le déplacement
  avait l'air fait et ne l'était qu'à moitié. Le cinquième, `MergeDuplicateEcue`
  sous `force`, reste à traiter. La rebascule de matière vit désormais dans
  `CLIEvaluationMatiereController` ; la route et son nom sont inchangés.
- **Septembre 2026** — la réponse porte un second bloc `moyennes_manuelles` et un
  `total_toutes_familles`. La version antérieure ne relevait que les évaluations
  et a été prise pour l'inventaire complet. Un garde de cohérence est posé sur
  `ESBTPResultat`, et la génération de bulletin écarte désormais les lignes
  incohérentes en les journalisant.
- **Août 2026** — première version : relevé des évaluations incohérentes, et
  rebascule d'une évaluation vers la bonne matière.

## Voir aussi

- `.claude/rules/lmd-ecue-leak-bts-picker.md` — les deux familles, et pourquoi les
  filtres de lecture ne suffisaient pas
- `.claude/rules/lmd-bts-bulletin-separation.md` — séparation stricte BTS / LMD
- `app/Domain/Academique/CoherenceSystemeAcademique.php` — le prédicat, et les
  trois conduites qui en découlent
- [CLI_RECALCUL_RESULTATS.md](CLI_RECALCUL_RESULTATS.md) — rafraîchir un agrégat
  périmé, et savoir si un job dispatché tourne
