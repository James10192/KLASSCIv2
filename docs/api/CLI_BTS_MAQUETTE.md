# API CLI — Maquette BTS (filière × niveau)

Lire, charger et corriger la **maquette BTS** d'un couple filière × niveau : quelles
matières composent le bulletin, dans quel ordre, et à quel semestre.

| | |
|---|---|
| Base | `/api/cli/bts/maquette` |
| Authentification | Bearer Sanctum |
| Abilities | `cli:read` en lecture, `cli:admin` en écriture |
| Contrôleur | `App\Http\Controllers\API\CLI\CLIBtsMaquetteController` |
| Portée | **BTS uniquement à l'écriture** — le chargement refuse une ECUE LMD ; la lecture les montre et le retrait les accepte (voir « ECUE LMD » plus bas) |

## ECUE LMD : refusées à l'entrée, visibles, retirables

Un élément constitutif LMD (`unite_enseignement_id` non nul) n'a rien à faire dans
une maquette BTS : la ligne le fait sortir sur les bulletins du niveau. Les trois
verbes ne se comportent donc **pas** de la même façon, et c'est délibéré :

| Verbe | Comportement | Pourquoi |
|---|---|---|
| `POST /` (chargement) | **refusée**, par identifiant comme par libellé | c'est le geste qui contamine |
| `GET /` (lecture) | **listée** comme les autres | c'est le seul endroit où on peut la *voir* |
| `POST /retirer` | **acceptée** | c'est le geste qui corrige |

Les refuser aux trois endroits est ce qui avait rendu le défaut incorrigible : sur
esbtp-abidjan, `TPOH243` « Alimentation en eau et QTE » portait
(TRAVAUX_PUBLICS, 2A), sortait sur les bulletins, était listée ici — et refusée au
retrait avec « ne désigne pas une matière unique ».

Côté écran, `/esbtp/matieres/classification` affiche ces lignes dans un bloc
distinct « éléments LMD dans cette maquette BTS », avec leur croix de retrait.
Elles restent hors des décomptes et de l'enregistrement : la seule action qui ait
du sens sur elles est le retrait.

## Ce qu'est la maquette, et ce qu'elle n'est pas

La maquette vit sur le pivot `esbtp_matiere_filiere_niveau`, au grain
(matière, filière, niveau), plus `esbtp_maquette_places_semestre` pour la place au
bulletin **par semestre**. Elle est **sans année** : c'est un référentiel de cursus,
pas une planification. Le planning général de l'année, lui, est annuel et peut
l'alimenter par un import explicite depuis l'écran `/esbtp/matieres/classification`.

Trois états, et non deux. Un couple n'est « renseigné » que lorsque quelqu'un a
**validé** ses semestres (`semestre_renseigne`). Tant que ce n'est pas fait, le
bulletin ignore les semestres et rend la liste entière : c'est la première chose à
regarder quand une matière apparaît là où on ne l'attend pas.

## `GET /api/cli/bts/maquette` — relire

Ability `cli:read`.

| Paramètre | Requis | Description |
|---|---|---|
| `filiere` | oui | identifiant, `code` ou `name` |
| `niveau` | oui | identifiant ou `name` |

```bash
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE/api/cli/bts/maquette?filiere=BAT&niveau=2%C3%A8me%20ann%C3%A9e"
```

```jsonc
{
  "success": true,
  "data": {
    "filiere": "BATIMENT", "filiere_id": 3,
    "niveau": "2ème année", "niveau_id": 2,
    "semestres_renseignes": false,   // ← tant que c'est faux, le bulletin ignore les semestres
    "totaux": { "matieres": 18, "semestre_1": 0, "semestre_2": 10, "les_deux": 8 },
    "matieres": [
      {
        "matiere_id": 41, "matiere": "Mathématiques générales", "code": "MATH",
        "active": true,
        "semestre": 2, "semestre_libelle": "semestre 2", "semestre_renseigne": false,
        "classification": "tronc_commun",
        "ordre_bulletin": 1,
        "places_par_semestre": { "2": 1 }   // place au bulletin, par semestre
      }
    ]
  }
}
```

Erreurs : `403` ability manquante, `404` filière ou niveau introuvable.

## `POST /api/cli/bts/maquette` — charger

Ability `cli:admin`. **Simule par défaut** : rien n'est écrit sans `appliquer=true`.

| Champ | Requis | Description |
|---|---|---|
| `filiere` | oui | identifiant, `code` ou `name` |
| `niveau` | oui | identifiant ou `name` |
| `semestre` | oui | `1`, `2` ou `"les_deux"` — défaut du lot |
| `matieres` | oui | liste ordonnée ; la **position dans la liste est la place au bulletin** |
| `matieres[].nom` / `matieres[].id` | — | libellé ou identifiant |
| `matieres[].semestre` | non | `1`, `2` ou `"les_deux"` — **prime sur celui du lot** |
| `valider` | non | pose `semestre_renseigne` sur le couple (défaut `false`) |
| `appliquer` | non | écrit réellement (défaut `false`) |

```jsonc
{
  "filiere": "BAT", "niveau": "2ème année", "semestre": 1,
  "matieres": [
    "Mathématiques générales",
    { "nom": "Anglais technique", "semestre": "les_deux" },
    { "id": 57 }
  ],
  "valider": true,
  "appliquer": true
}
```

Le chargement **crée la liaison manquante** : une 2ᵉ année vierge se remplit d'un
coup, là où l'écran ne sait que modifier ce qui existe déjà. Il est rejouable : un
second envoi identique ne duplique rien.

### Refus — un libellé ambigu (422)

Le catalogue d'ESBTP Abidjan compte quatre « Anglais ». Résoudre au plus proche
poserait un bulletin faux que personne ne saurait relire : **le lot entier est refusé**,
avec ses candidats.

```jsonc
{ "success": false, "data": { "ambigus": [{ "libelle": "Anglais", "place": 3,
  "candidats": [{ "id": 12, "name": "Anglais", "code": "ANG1" }, …] }],
  "introuvables": [], "resolus": 17 } }
```

### Refus — un semestre déjà écrit (422)

« Déjà au semestre 2, chargée au semestre 1 » veut dire « elle est aux deux » aussi
souvent que « elle a changé de semestre », et les deux ne donnent pas le même
bulletin. On ne devine pas : le lot est refusé et l'appelant tranche **ligne par
ligne** avec `matieres[].semestre`.

Le refus vaut que la ligne ait été validée **ou non**. Un chargement sans `valider`
écrit bien le semestre : ne regarder que les lignes validées laissait passer
exactement le défaut que cette garde doit arrêter — charger S1 puis S2 basculait en
silence toute matière commune aux deux.

```jsonc
{ "success": false, "data": { "conflits": [{ "matiere_id": 41,
  "matiere": "Mathématiques générales", "place": 1,
  "declare": "semestre 2", "demande": "semestre 1" }],
  "comment_trancher": "Posez le semestre sur la ligne : {\"nom\": \"...\", \"semestre\": 1 | 2 | \"les_deux\"}." } }
```

### Réponse d'un chargement appliqué

```jsonc
{ "success": true, "message": "Maquette chargee pour 18 matiere(s).",
  "data": { "semestre": 1, "semestre_libelle": "semestre 1",
    "semestres_valides": true, "ecrit": true,
    "liaisons_a_creer": 6, "liaisons_existantes": 12,
    "lignes": [{ "place": 1, "matiere_id": 41, "matiere": "…",
      "liaison": "existante", "ordre_avant": 4,
      "semestre_avant": null, "semestre_apres": 1,
      "places_semestre_avant": { "2": 1 } }] } }
```

## `POST /api/cli/bts/maquette/retirer` — retirer

Ability `cli:admin`. **Simule par défaut.**

| Champ | Requis | Description |
|---|---|---|
| `filiere`, `niveau` | oui | comme ci-dessus |
| `matieres` | oui | libellés ou identifiants |
| `malgre_les_notes` | non | confirme un retrait malgré des évaluations existantes |
| `appliquer` | non | écrit réellement (défaut `false`) |

Refuse (422) une matière qui porte des **évaluations sur ce couple** tant que
`malgre_les_notes=true` n'est pas envoyé : la note resterait en base sans plus
apparaître nulle part.

Le retrait supprime la ligne canonique et ses places par semestre. Il **ne touche
pas** aux pivots plats `esbtp_matiere_filiere` / `esbtp_matiere_niveau` : ceux-ci ne
savent pas de quel couple vient une filière, et en retirer « Bâtiment » parce qu'on
quitte (Bâtiment, 2ᵉ année) retirerait aussi la matière de (Bâtiment, 1ʳᵉ année), que
personne n'a nommée.

Une matière absente de la maquette n'est pas une erreur : elle est comptée dans
`absentes_de_la_maquette` et n'est pas décomptée comme retirée.

```jsonc
{ "success": true, "message": "2 matiere(s) retiree(s) de la maquette sur 3 demandee(s).",
  "data": { "ecrit": true, "absentes_de_la_maquette": 1,
    "lignes": [{ "matiere_id": 41, "matiere": "…", "dans_la_maquette": true,
      "evaluations_sur_ce_couple": 0,
      "retire": { "canonique": 1, "places_semestre": 2 } }] } }
```

## L'écran équivalent

Tout ceci se fait aussi à la main sur `/esbtp/matieres/classification` (permission
`matieres.edit`) : ajouter, retirer, poser la place et le semestre, valider les
semestres, et reprendre les semestres du planning général de l'année.

## Historique

- **Septembre 2026** — `POST /retirer` accepte désormais une ECUE LMD, alors que le
  chargement continue de la refuser. Les refuser des deux côtés rendait une ligne
  posée par erreur impossible à enlever autrement qu'en base. Cette ligne du tableau
  d'en-tête annonçait « BTS uniquement — une ECUE LMD est refusée » : c'était faux
  pour `GET`, qui les listait déjà, et c'est maintenant faux pour `/retirer`.
- **Septembre 2026** — `GET` (relire) et `POST /retirer` ajoutés. `semestre` accepte
  `"les_deux"` et se pose ligne par ligne ; un chargement qui contredit un semestre
  déjà écrit est refusé au lieu d'être appliqué en silence. La place au bulletin est
  retenue **par semestre**. Une ECUE LMD passée par son identifiant est refusée, comme
  elle l'était déjà par libellé.
- **Septembre 2026** — première version : `POST` (charger), simulation par défaut,
  refus des libellés ambigus.

## Voir aussi

- `.claude/rules/lmd-bts-matieres-single-source.md` — source canonique des matières
- `.claude/rules/lmd-bts-bulletin-separation.md` — séparation stricte BTS / LMD
- `app/Domain/BtsTroncCommun/BtsMaquette.php` — ce que la maquette décide au bulletin
- `app/Domain/BtsTroncCommun/SemestreDeMaquette.php` — la règle du semestre
