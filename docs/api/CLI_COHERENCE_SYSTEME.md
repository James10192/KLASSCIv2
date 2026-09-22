# API CLI — Cohérence BTS / LMD des matières notées

Recenser et réparer les matières posées dans le **mauvais système académique** :
une ECUE du LMD évaluée dans une classe BTS, ou l'inverse.

| | |
|---|---|
| Base | `/api/cli` |
| Authentification | Bearer Sanctum |
| Abilities | `cli:read` en lecture, `cli:admin` en écriture |
| Contrôleur | `App\Http\Controllers\API\CLI\CLIMaintenanceController` |
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

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"matiere_id": 42, "dry_run": true}' \
  "$BASE/api/cli/evaluations/4117/matiere"
```

**La moyenne enregistrée suit** (septembre 2026). Les notes changent de matière
par un `update()` qui ne réveille aucun observateur ; l'endpoint recalcule donc
lui-même `esbtp_resultats` pour chaque élève concerné — sans quoi le bulletin BTS,
qui donne la priorité à la moyenne enregistrée, garderait l'ancienne. La réponse
porte deux clés de plus :

| clé | sens |
|---|---|
| `recalculs_lances` | recalculs **programmés**. Sur une file asynchrone, ils ne sont pas encore faits quand la réponse revient. |
| `lignes_sans_note` | moyennes enregistrées laissées sans aucune note (en général celle de la matière quittée). **Jamais remises à zéro** — recalculer une ligne vide y écrirait 0/20. À trancher par l'école. |

⚠️ **Le déplacement « par l'écran » de l'option 2 ci-dessous, lui, ne recalcule
pas.** Après l'avoir utilisé, relancer le calcul sur la classe depuis le terminal
du serveur (terminal cPanel : il n'y a pas d'accès SSH, et cette commande n'a pas
d'équivalent `/api/cli`) :

```bash
php artisan notes:recompute --classe=<id> --dry-run   # puis sans --dry-run
```

`notes:recompute` part des évaluations : il ne touche que les coordonnées qui en
portent encore une, donc il n'écrira jamais de zéro sur la matière quittée. Les
déplaceurs de notes et leur état sont recensés en tête de
`app/Domain/Notes/RecalculApresDeplacement.php`.

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

- **Septembre 2026 (bis)** — la rebascule recalcule `esbtp_resultats` ; la réponse
  porte `recalculs_lances` et `lignes_sans_note`. Ajout non cassant.
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
