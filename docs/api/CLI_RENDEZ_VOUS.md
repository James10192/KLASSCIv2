# API CLI — Rendez-vous d'inscription

Préfixe `/api/cli`, authentification Sanctum (`Authorization: Bearer <jeton>`).

| Méthode | Chemin | Capacité | Rôle |
|---|---|---|---|
| POST | `/rendez-vous/generer` | `cli:admin` | Génère les créneaux depuis les réglages |
| POST | `/rendez-vous/placer` | `cli:admin` | Place les dossiers en attente, pose leur convocation en attente |
| GET | `/rendez-vous/diagnostic` | `cli:read` | État de chaque maillon, comptes de convocations |
| POST | `/rendez-vous/convocations/envoyer` | `cli:admin` | Envoie un paquet de convocations en attente (50 au plus, 25 s) |
| POST | `/rendez-vous/convocations/remettre` | `cli:admin` | Remet en attente `quoi: inconnues` (réservations d'avant le suivi) ou `quoi: echecs`, `limite` facultative, sans rien envoyer |

## `GET /rendez-vous/diagnostic`

```json
{
  "success": true,
  "data": {
    "tout_en_ordre": false,
    "maillons": [
      { "cle": "canal", "ok": false, "titre": "Prise de rendez-vous fermée", "detail": "…" },
      { "cle": "reglages", "ok": true, "titre": "Réglages complets", "detail": "…" },
      { "cle": "places", "ok": true, "titre": "257 places libres à venir", "detail": "…" },
      { "cle": "portail", "ok": true, "titre": "…", "detail": "…" },
      { "cle": "messagerie", "ok": true, "titre": "…", "detail": "…" }
    ],
    "convocations": { "en_attente": 0, "envoyee": 12, "echec": 0, "sans_email": 3, "inconnu": 420 }
  }
}
```

`inconnu` : réservations actives créées avant le suivi des convocations (22/09/2026),
dont on ne sait pas si le courriel est parti. Elles ne sont **jamais** envoyées
automatiquement : l'écran propose de le faire.

Même mesure en local sur le serveur : `php artisan inscriptions:diagnostiquer-rdv [--json]`.

## `GET /rendez-vous/recherche`

Retrouver le rendez-vous d'une famille sans connaître le jour. Ability `cli:read`.
Mêmes règles que l'écran « Retrouver un rendez-vous » (`App\Services\RendezVous\RechercheRdv`).

| Paramètre | Valeurs | Défaut |
|---|---|---|
| `q` | nom, prénoms, courriel, référence du dossier, matricule ; une saisie de chiffres est lue comme un téléphone | — |
| `quand` | `a_venir`, `passes`, `tous` | `a_venir` sans `q`, `tous` avec |
| `statut` | une valeur de `App\Enums\StatutReservationRdv` | tous |
| `type` | `candidature`, `reinscription` | tous |
| `per_page` / `page` | 1 à 100 | 25 / 1 |

```json
{ "total": 1, "page": 1, "derniere_page": 1,
  "rendez_vous": [{ "id": 12, "nom": "KOUASSI Ama", "dossier": "candidature",
    "reference": "AB12-CD34", "matricule": null, "telephone": "+2250707123456",
    "date": "2026-10-05", "heure": "10:00-10:30", "statut": "confirmee",
    "etat_accueil": "attendu", "absences": 0, "recue_le": null }] }
```

`etat_accueil` (`attendu`, `recu`, `non_venue`, `traite`) n'est renseigné que pour une
réservation qui tient son créneau ; `null` pour une réservation libérée ou annulée.

## `POST /rendez-vous/placer`

- 422 avec `message` si rien n'a été tenté : canal fermé, ou aucune place libre.
- 200 sinon : `{ places, a_prevenir, sans_creneau, deja, refus: null }`. `a_prevenir`
  compte les dossiers placés **sans e-mail** (inclus dans `places`) : leur
  convocation est posée « sans e-mail » et ils apparaissent dans la liste d'appel
  « Familles à prévenir ».

Les convocations sont **posées en attente**, pas envoyées. L'envoi passe par
`/rendez-vous/convocations/envoyer` ou par la tâche planifiée (toutes les 5 min).

## `POST /rendez-vous/convocations/envoyer`

- 200 : `{ envoyees, echecs, restantes, bloque: null, en_cours: false }`. Rappeler tant que `restantes > 0`.
- 409 : un autre envoi (tâche planifiée, écran) tient le verrou ; rien n'a été tenté. Rappeler dans une minute.
- 503 : `bloque` porte la raison qui arrêterait aussi les suivantes (MailPulse
  désactivé, clé absente, service injoignable). Rappeler ne sert à rien tant
  qu'elle tient.

## `POST /rendez-vous/convocations/remettre`

Corps : `{ "quoi": "inconnues" }` ou `{ "quoi": "echecs" }`, et `"limite": N`
facultative (entier ≥ 1, les plus anciennes d'abord). Même geste que les boutons
de l'écran. Rend `{ remises, a_envoyer }` ; 422 si `quoi` ou `limite` est invalide.

**N'envoie rien, mais met dans la file** : une convocation en attente part au
prochain passage de la tâche planifiée (5 min), vérifiée ou non. Pour contrôler
un premier courriel, c'est donc **ici** qu'on borne :

1. `remettre { "quoi": "inconnues", "limite": 1 }`
2. `envoyer` (ou attendre la tâche planifiée) ; vérifier la réception
3. `remettre { "quoi": "inconnues" }` pour le reste, puis `envoyer` jusqu'à `restantes: 0`

## Historique

- 2026-09-25 — ajout de `recherche`. Non cassant.

- 2026-09-23 — **Breaking** : `placer` place aussi les dossiers sans e-mail. La clé
  `sans_email` (dossiers NON placés) disparaît, remplacée par `a_prevenir`
  (dossiers placés sans e-mail, inclus dans `places`).

- 2026-09-23 — ajout de `convocations/remettre` (avec `limite`). Non cassant.

- 2026-09-22 — création de `diagnostic` et `convocations/envoyer`. **Breaking** :
  `placer` ne déclenche plus l'envoi des courriels, et répond 422 quand le canal est
  fermé (il comptait auparavant chaque dossier « sans créneau »).
