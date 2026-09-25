# API CLI — Activité du personnel

## Endpoint

`GET /api/cli/personnel-activite`

L'activité du personnel d'une instance, en **faits prévus / réalisés**, sans
note. Même calcul que la page `/esbtp/personnel/performance`
(`App\Services\Personnel\ActiviteDuPersonnel`).

Remplace `GET /api/cli/personnel-scores`, qui répond désormais **410 Gone**.

## Authentification

```http
Authorization: Bearer <token-cli>
Accept: application/json
```

Ability requise : `cli:read`.

## Paramètres

| Paramètre | Type | Défaut | Description |
| --- | --- | --- | --- |
| `periode` | string | `annee` | `annee` (année universitaire en cours, jusqu'à aujourd'hui), `mois` (mois en cours), `mois_precedent` |
| `user_id` | int | null | Une seule personne |

## Réponse

```json
{
  "success": true,
  "data": {
    "periode": { "cle": "annee", "libelle": "Année 2025-2026", "du": "2025-09-01", "au": "2026-07-31" },
    "synthese": {
      "seances_prevues": 1640, "seances_tenues": 1390,
      "enseignants_notes_en_retard": 5, "evaluations_en_retard": 58,
      "paiements_en_attente": { "nombre": 9, "montant": 4425000, "jours": 3 },
      "personnes": 9
    },
    "personnes": [
      {
        "id": 235, "nom": "Aminata KONE", "role": "enseignant", "telephone": "+225 …",
        "seances_prevues": 245, "seances_tenues": 239, "seances_non_emargees": 6, "retards": 6,
        "evaluations": 24, "notes_attendues": 600, "notes_recues": 600, "evaluations_en_retard": 0,
        "paiements_saisis": 0, "montant_saisi": 0, "paiements_valides": 0, "paiements_en_attente": 0,
        "inscriptions": 0
      }
    ]
  }
}
```

## Définitions

- **Séances prévues** : séances datées de l'emploi du temps (hors pauses et
  déjeuners) sur la période, jusqu'à aujourd'hui. **Tenues** : celles qui ont un
  émargement de début de cours à un statut « réalisé » (présent, retard…). Le lien
  se fait par la séance (`course_id`), jamais par un identifiant de personne.
- **Notes attendues** : inscrits actifs de la classe pour chaque évaluation passée
  dont la personne est l'enseignant désigné. **Évaluations en retard** : celles à
  qui il manque des notes au-delà du délai de relance de l'école
  (`pilotage.relance_notes_apres_jours`).
- **Paiements en attente** : saisis il y a plus de `personnel.paiement_attente_jours`
  jours et toujours `en_attente`.

## Historique

- 2026-09-25 : création. **Breaking change** — remplace `GET /api/cli/personnel-scores`
  (410 Gone) et la commande `personnel-scores:recalculate` (supprimée). Plus de
  score sur 100 ni de niveaux.
