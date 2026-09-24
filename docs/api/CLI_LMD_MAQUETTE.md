# CLI — Lire la maquette LMD vue par chaque parcours

`GET /api/cli/lmd/maquette` — lecture seule, jeton `cli:read`.

Sert à diagnostiquer à distance une plainte du type « je vois dans ce parcours
un élément que j'avais réservé à l'autre », sans session web sur l'instance.

## Paramètres

| Paramètre | Type | Effet |
|---|---|---|
| `parcours_id` | entier, optionnel | un seul parcours |
| `code_ue` | texte, optionnel | une seule unité (par son code) |

## Réponse

```jsonc
{
  "success": true,
  "data": {
    "parcours": [{
      "id": 7, "code": "LPA", "name": "…", "filiere_id": 8,
      "unites": [{
        "id": 41, "code": "UE31", "name": "…", "semestre": 3, "niveau": "Licence 2",
        "credit": 6, "partagee_avec": ["LPV"],
        "ecues": [{
          "id": 310, "code": "ECUE311", "name": "…",
          "origine": "reserve",          // commun | reserve | cle_etrangere
          "credit": 3,
          "heures": {"cm": 20, "td": 10, "tp": 0, "projet": 0, "tpe": 0, "total": 30}  // null si non planifié
        }]
      }]
    }],
    "unites_partagees": [{ "code": "UE31", "parcours": ["LPA","LPV"],
      "lignes": [{"ecue": "ECUE311 — …", "maquette": "LPA"}] }],
    "anomalies": [
      {"type": "reserve_ailleurs_mais_visible", "parcours": "LPV", "ue": "UE31",
       "ecue": "…", "origine": "commun"},
      {"type": "cle_etrangere_dans_unite_partagee", "…": "…"},
      {"type": "doublon_d_intitule", "…": "…"},
      {"type": "sans_masse_horaire", "…": "…"},
      {"type": "parcours_sans_filiere", "parcours": "…"}
    ]  }
}
```

## Les anomalies

| Type | Sens |
|---|---|
| `reserve_ailleurs_mais_visible` | l'élément est réservé à un autre parcours, **et** porte une ligne commune (ou seulement la clé étrangère) : il apparaît donc ici. C'est la « fuite » que l'école signale. |
| `cle_etrangere_dans_unite_partagee` | élément sans ligne de pivot dans une unité partagée : visible par tous ses parcours. |
| `doublon_d_intitule` | deux éléments de même intitulé dans une même maquette. |
| `sans_masse_horaire` | aucune planification pour l'année courante. |
| `parcours_sans_filiere` | le parcours n'a pas de filière : aucune heure ne peut être lue. |

Un élément simplement commun dans une unité partagée n'est **pas** une anomalie :
c'est le fonctionnement normal.

## Lire `origine`

| Valeur | Sens | Visible par |
|---|---|---|
| `commun` | ligne de pivot sans parcours | **tous** les parcours de l'unité |
| `reserve` | ligne de pivot réservée à ce parcours | ce parcours seulement |
| `cle_etrangere` | aucune ligne de pivot, seule `esbtp_matieres.unite_enseignement_id` | **tous** les parcours de l'unité |

Un élément `commun` ou `cle_etrangere` dans une unité partagée apparaît dans
chaque maquette : c'est ce que l'école prend pour une fuite. La correction se
fait à l'écran `/esbtp/lmd/ue`, filtré sur le parcours : réserver l'élément au
parcours qui le porte, puis le retirer de la composition commune.

La composition par parcours passe par `ESBTPUniteEnseignement::getEcuesEffectifs()`,
la même méthode que le planning et les bulletins.

`heures` vient de `esbtp_planifications_academiques` (**année courante seulement**,
filière du parcours, semestre de l'unité dans ce parcours — pas le niveau de la fiche, qui
reste celui du premier parcours importé) : c'est la masse horaire
saisie dans le Planning LMD (`/esbtp/lmd/planning`).

## Réparer les crédits laissés à 0

`POST /api/cli/lmd/planifications/reparer-credits` — jeton `cli:admin`.

Avant septembre 2026, une ligne de la maquette horaire créée en tapant des heures
recevait 0 crédit au lieu de celui de l'ECUE, et faussait le total CECT du
parcours. Cette route recense ces lignes et, sur demande, leur pose le crédit de
la maquette du parcours de leur filière (ligne réservée, sinon commune, sinon
crédit de la matière).

| Paramètre | Défaut | Effet |
|---|---|---|
| `dry_run` | `true` | `false` écrit ; sinon liste seulement |
| `ids` | — | **obligatoire pour écrire** : les identifiants relus, pris dans la simulation |

L'écriture ne se fait jamais d'office. Un 0 saisi à la **création** d'une ligne
(édition en masse, cellule CECT d'un ECUE pas encore planifié) ne laissait aucune
trace avant septembre 2026 : la création n'était pas auditée. Il ne se distingue
donc pas d'un 0 laissé par l'ancienne saisie d'heures, et seule l'école peut
trancher. La simulation liste ; l'école relit ; on renvoie les identifiants
retenus. Sans `ids`, l'écriture est refusée (422).

Ne sont jamais candidates :
- une ligne dont la **création** est au journal d'audit (auditée depuis
  septembre 2026 : elle porte son vrai crédit, ou un 0 choisi) ;
- une ligne dont le crédit a été **modifié** à la main (événement `updated`
  portant `credits_ects`) ;
- une ligne dont la maquette ne donne pas plus de 0.

Si l'audit est désactivé sur l'instance, l'écriture est refusée (409) ; la
simulation reste possible. `reparees` compte les lignes réellement écrites.

```jsonc
{ "success": true, "dry_run": true,
  "data": { "candidates": 12, "reparees": 0,
    "lignes": [{ "id": 518, "matiere": "AGR21033 Génétique animale", "filiere_id": 8,
                 "semestre": 3, "annee_universitaire_id": 4, "credit_attendu": 2 }] } }
```

## Historique

- Septembre 2026 — création (diagnostic maquette USAT).
- Septembre 2026 — ajout de `POST /api/cli/lmd/planifications/reparer-credits`.
