# API CLI — Déplacer des évaluations d'un semestre à l'autre

Deux endpoints déplacent des évaluations entre semestres. Ils diffèrent par qui
décide de la liste : un humain, ou une détection.

| endpoint | ability | qui décide |
|---|---|---|
| `POST /api/cli/evaluations/deplacer-periode` | `cli:admin` | un humain, qui fournit la liste |
| `GET  /api/cli/diagnostics/evaluations-periode` | `cli:read` | détection seule, lecture |
| `POST /api/cli/diagnostics/evaluations-periode/repair` | `cli:admin` | la détection : évaluations posées avant l'ouverture de leur classe de spécialité |

Les deux écritures **simulent par défaut** : rien n'est écrit tant que
`dry_run` ne vaut pas explicitement `false`.

## `POST /api/cli/evaluations/deplacer-periode`

La période d'une évaluation est saisie à la main, et rien dans la donnée ne
permet de deviner la bonne (à l'ESBTP Abidjan, les deux semestres couvrent tous
deux octobre à août). L'endpoint ne devine donc rien : il déplace la liste
qu'on lui donne.

```json
{ "evaluation_ids": [4117, 4118], "periode": "semestre2", "dry_run": false }
```

`evaluation_ids` : 1 à 200 identifiants. Au-delà, découper l'appel.

## `POST /api/cli/diagnostics/evaluations-periode/repair`

Déplace vers leur semestre d'ouverture les évaluations d'une classe de
spécialité posées avant ce semestre. Paramètre facultatif : `annee_id`.

## Ce que la réponse porte en plus d'une écriture réelle

Les notes changent de semestre par un `update()` qui ne réveille aucun
observateur. Chaque endpoint remet donc lui-même d'accord les moyennes
enregistrées (`esbtp_resultats`) de chaque élève concerné :

| clé | sens |
|---|---|
| `recalculs_lances` | recalculs **programmés** de la moyenne d'arrivée. Sur une file asynchrone, ils ne sont pas encore faits quand la réponse revient. |
| `lignes_retirees` | moyennes du semestre de départ que le déplacement a privées de **toutes** leurs notes : **mises de côté** (suppression réversible, tracée par l'audit). Laissées en place, elles seraient lues par le bulletin et le certificat, et les notes compteraient deux fois. |
| `lignes_sans_note` | moyennes sans note que le déplacement n'a pas vidées : **jamais modifiées**, à vérifier par l'école. |

Une évaluation **annulée** n'est pas prise en compte : ses notes ne comptent
nulle part, la déplacer ne change aucune moyenne.

Le recalcul lui-même vit dans `app/Domain/Notes/RecalculApresDeplacement.php`,
qui recense aussi les autres déplaceurs de notes.

## Historique

- **Septembre 2026** — les deux écritures recalculent les moyennes enregistrées ;
  la réponse porte `recalculs_lances`, `lignes_retirees` et `lignes_sans_note`.
  Ajout non cassant pour la forme de la réponse. **Changement de comportement** :
  une moyenne vidée par le déplacement est désormais mise de côté.
- Première version documentée ici ; les deux endpoints existaient sans document.
