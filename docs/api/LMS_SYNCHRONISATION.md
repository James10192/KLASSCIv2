# API LMS — Synchronisation « ce qui a changé depuis »

Lot 2 du plan « Liaison LMS–KLASSCI » (septembre 2026). Remplace la série
« liste des classes, puis chaque classe, puis ses élèves » par **un seul appel**
qui rend tout ce qui a changé, tous types confondus.

## Appel

```
GET /api/lms/v2/sync?since=<curseur>&types=classes,etudiants&limit=500
Authorization: Bearer <jeton serveur>
If-None-Match: "<ETag de la réponse précédente>"
```

| Paramètre | Défaut | Rôle |
|---|---|---|
| `since` | absent | Le `curseur` rendu par l'appel précédent. Absent : tout, depuis le début. |
| `types` | les six | Sous-ensemble de `classes, matieres, etudiants, enseignants, inscriptions, seances`. |
| `limit` | 500 | Nombre de changements par page (1 à 500). |
| `annee_universitaire_id` | l'année courante | Année des inscriptions et des séances. Ne se change pas en cours de route : un curseur suit une année. |

**Droit exigé** : jeton serveur avec `lms:lecture` (voir
[LMS_JETON_SERVEUR.md](LMS_JETON_SERVEUR.md)). Tout autre jeton : `403`.

## Réponse

```json
{
  "success": true,
  "data": {
    "changements": [
      { "type": "classe", "id": 12, "supprime": false, "modifie_le": "2026-09-24 10:02:11",
        "donnees": { "code": "1TP-C", "nom": "1TP C", "actif": true, "...": "..." } },
      { "type": "inscription", "id": 842, "supprime": true, "modifie_le": "2026-09-24 10:05:40" },
      { "type": "seance", "id": 9031, "supprime": true, "definitif": true, "modifie_le": "2026-09-24 10:06:02" }
    ],
    "curseur": "eyJ2IjoxLCJhIjo0LC...",
    "a_suivre": false,
    "nombre": 3
  },
  "meta": { "annee_universitaire_id": 4, "...": "..." }
}
```

- **`a_suivre: true`** : la page est pleine, rappeler tout de suite avec le
  nouveau `curseur`. `false` : le LMS est à jour.
- **`supprime: true`** : l'objet a été retiré (suppression douce), il n'a pas de
  `donnees`. Le LMS doit le retirer de son cache.
- **`definitif: true`** : l'objet a été purgé de la corbeille. Même traitement.
- Un objet supprimé puis restauré revient ensuite avec `supprime: false` : il
  suffit d'appliquer les changements **dans l'ordre**.
- **`304` sans corps** si `If-None-Match` porte l'ETag de la réponse précédente
  et que rien n'a changé.

### Contenu de `donnees`

| Type | Champs |
|---|---|
| `classe` | `code`, `nom`, `libelle`, `systeme_academique`, `filiere_id`, `niveau_etude_id`, `parcours_id`, `places_totales`, `actif` |
| `matiere` | `code`, `nom`, `unite_enseignement_id` (non nul : ECUE du LMD), `niveau_etude_id`, `coefficient`, `credit_ecue`, `actif` |
| `etudiant` | `user_id`, `matricule`, `nom`, `prenoms`, `sexe`, `statut`, `identifiant`, `email`, `actif` (compte présent et actif) |
| `enseignant` | `user_id`, `matricule`, `nom`, `specialite`, `identifiant`, `email`, `actif` |
| `inscription` | `etudiant_id`, `classe_id`, `annee_universitaire_id`, `statut`, `etape`, `date_inscription`, `validee` (statut actif **et** dossier terminé) |
| `seance` | `classe_id`, `matiere_id`, `enseignant_id`, `date`, `jour`, `heure_debut`, `heure_fin` (« 08:00 »), `salle`, `type_seance`, `actif` |

Les classes, matières, élèves et enseignants ne sont pas filtrés par année (une
classe KLASSCI est universelle). Les inscriptions et les séances le sont.

## Ce que le LMS doit faire

1. Garder **un curseur par école**.
2. Appliquer les changements à son cache, **puis** enregistrer le nouveau
   curseur — jamais avant : une coupure entre les deux ferait perdre la page.
3. Tant que `a_suivre` vaut `true`, rappeler aussitôt.
4. Envoyer `If-None-Match` avec l'ETag précédent.
5. Fréquence : toutes les 5 minutes tant que le lot 3 (KLASSCI prévient le LMS)
   n'est pas livré ; toutes les 15 à 30 minutes ensuite.
6. Sur `422` « Curseur illisible » ou changement d'année : recommencer sans
   `since`.

## Garanties et limites

- **Aucune ligne perdue à égalité** : le curseur porte, pour chaque type, la date
  de modification **et** l'identifiant de la dernière ligne rendue.
- **Les 5 dernières secondes attendent l'appel suivant** : une transaction encore
  ouverte peut écrire une date antérieure à une ligne déjà lue.
- **Un élève ou un enseignant repart quand sa fiche OU son compte change** :
  une désactivation du compte suffit à le renvoyer avec `actif: false`.
- **Pas vu** : une écriture faite en SQL direct (`DB::table(...)->update()` ou
  `->delete()`), qui ne touche pas `updated_at` et n'émet aucun événement. Aucun
  écran de KLASSCI ne modifie ces six tables ainsi aujourd'hui, hormis le
  peuplement de démonstration de la paie.
- Une première synchronisation ignore les purges antérieures : le LMS n'a rien à
  retirer.

## Architecture

- `app/Http/Controllers/API/LMSSyncController.php` — contrôle d'accès, paramètres, ETag.
- `app/Domain/Lms/Synchronisation/SynchronisationLms.php` — assemblage d'une page.
- `app/Domain/Lms/Synchronisation/FluxDeSynchronisation.php` — requête et forme de chaque type.
- `app/Domain/Lms/Synchronisation/CurseurDeSynchronisation.php` — le curseur opaque.
- `app/Domain/Lms/Synchronisation/SuppressionsDefinitives.php` — trace des purges (table `lms_suppressions`).

## Historique

- **Septembre 2026** — création (lot 2).
