# API CLI — Saisie des moyennes de matière

`POST /api/cli/resultats/moyennes` — jeton `cli:admin`.

Enregistre ou retire les moyennes de matière d'**un** élève, pour **une** classe
BTS et **un** semestre. C'est l'écran « Modifier les moyennes » sans l'écran :
il sert à traiter à distance une réclamation validée par l'école.

## Ce qu'il faut savoir avant de l'utiliser

- **Une moyenne enregistrée l'emporte sur les notes** au bulletin comme à
  l'écran. La retirer rend la main aux notes de la matière, s'il y en a.
- **Simulation par défaut.** Sans `dry_run: false`, rien n'est écrit : la
  réponse annonce ce qui serait fait, ligne par ligne.
- **Motif obligatoire** (10 caractères au moins). Il est écrit au journal
  (`Log::warning`, « CLI: moyennes enregistrees ») avec l'avant et l'après.
  Chaque ligne passe aussi par l'audit du modèle `ESBTPResultat`.
- **Tout ou rien.** Une ligne refusée annule toute la demande.
- **Doublons refusés.** Si une matière porte déjà deux moyennes vivantes pour ce
  semestre, la saisie est refusée : modifier l'une laisserait le bulletin lire
  l'autre. À dédoublonner d'abord.
- **Le bulletin ne bouge pas tout seul.** Il fige sa moyenne à la génération :
  la réponse liste les bulletins à régénérer (`bulletins_a_regenerer`).
- **Un recalcul depuis les notes écrase ces moyennes** (`notes:recompute`, ou la
  saisie d'une nouvelle note dans la matière). C'est aussi vrai des moyennes
  posées à l'écran.

## Corps

```json
{
  "etudiant_id": 3443,
  "classe_id": 40,
  "periode": "semestre1",
  "annee_universitaire_id": 4,
  "motif": "Réclamation validée par la direction des études le 24/09/2026",
  "dry_run": true,
  "moyennes": [
    { "matiere_id": 20, "moyenne": 15 },
    { "matiere_id": 21, "moyenne": null }
  ]
}
```

| Champ | Règle |
|---|---|
| `periode` | `semestre1` ou `semestre2` |
| `annee_universitaire_id` | facultatif, année courante par défaut |
| `moyennes[].moyenne` | 0 à 20, **`null` pour retirer** la moyenne enregistrée |

## Refus

| Code | Cas |
|---|---|
| 403 | jeton sans `cli:admin` |
| 422 | classe LMD (ses relevés passent par `/esbtp/lmd/bulletins`) |
| 422 | élève non inscrit dans cette classe pour cette année |
| 422 | matière LMD (ECUE) dans une classe BTS — refusé dès la simulation |
| 422 | deux moyennes vivantes sur la même matière (à dédoublonner)                 |
| 422 | validation (motif trop court, moyenne hors de 0 à 20, matière en double) |

## Réponse

```json
{
  "dry_run": false,
  "classe": "1BTS GTP C",
  "lignes": [
    { "matiere_id": 20, "matiere": "Anglais technique", "avant": 0, "apres": 15, "action": "modifiee", "coefficient": 2 },
    { "matiere_id": 21, "matiere": "Physique", "avant": 0, "apres": null, "action": "retiree" }
  ],
  "bulletins_a_regenerer": [3842]
}
```

`action` vaut `creee`, `modifiee`, `inchangee`, `retiree` ou `absente` (retrait
demandé sans moyenne enregistrée).

**Écarts avec l'écran, voulus :**
- **Retrait :** il se fait en suppression douce (l'écran supprime en dur). Une réclamation se conteste, et la ligne d'avant doit pouvoir revenir.
- **Transaction :** la demande s'exécute dans une seule transaction.

## Historique

- **Septembre 2026** — création, pour traiter la réclamation d'un élève de 1BTS GTP C
  à l'ESBTP Abidjan.

---

# Corriger des notes existantes

`POST /api/cli/notes/corriger` — jeton `cli:admin`.

À préférer à la saisie d'une moyenne quand la matière n'a **qu'une note** ce
semestre-là : la note et la moyenne restent d'accord, et un recalcul ultérieur
ne défait pas la correction, puisqu'il repart des notes corrigées. Quand une
matière a plusieurs notes, la moyenne est leur moyenne pondérée, pas la
dernière saisie.

```json
{
  "etudiant_id": 3443,
  "motif": "Réclamation validée par la direction des études le 24/09/2026",
  "dry_run": true,
  "notes": [ { "note_id": 55907, "note": 15 } ]
}
```

- **Simulation par défaut**, motif obligatoire et journalisé (« CLI: notes corrigees »).
- **Recalcul synchrone** des moyennes touchées, sans passer par la file : une
  moyenne enregistrée d'avant, qui l'emporterait au bulletin, est remplacée
  tout de suite. La réponse rend les moyennes obtenues.
- Le commentaire de la note, trace de l'enseignant, n'est pas modifié. Une
  note « absent » devient une note chiffrée.
- Refus (422) : note d'un autre élève, note inexistante, note au-dessus du
  barème de son évaluation. Tout ou rien.
- Le bulletin reste à régénérer.
