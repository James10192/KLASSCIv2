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
      {"type": "element_commun_dans_unite_partagee", "parcours": "LPV", "ue": "UE31",
       "ecue": "…", "origine": "commun", "aussi_vu_par": ["LPA"]},
      {"type": "doublon_d_intitule", "…": "…"},
      {"type": "sans_masse_horaire", "…": "…"}
    ]
  }
}
```

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

`heures` vient de `esbtp_planifications_academiques` (année courante,
filière du parcours, niveau et semestre de l'unité) : c'est la masse horaire
saisie dans le Planning LMD (`/esbtp/lmd/planning`).

## Historique

- Septembre 2026 — création (diagnostic maquette USAT).
