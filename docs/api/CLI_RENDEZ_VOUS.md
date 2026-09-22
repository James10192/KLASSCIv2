# API CLI — Rendez-vous d'inscription

Préfixe `/api/cli`, authentification Sanctum (`Authorization: Bearer <jeton>`).

| Méthode | Chemin | Capacité | Rôle |
|---|---|---|---|
| POST | `/rendez-vous/generer` | `cli:admin` | Génère les créneaux depuis les réglages |
| POST | `/rendez-vous/placer` | `cli:admin` | Place les dossiers en attente, pose leur convocation en attente |
| GET | `/rendez-vous/diagnostic` | `cli:read` | État de chaque maillon, comptes de convocations |
| POST | `/rendez-vous/convocations/envoyer` | `cli:admin` | Envoie un paquet de convocations en attente (`max` 1 à 50, défaut 50 ; 25 s) |
| POST | `/rendez-vous/convocations/remettre` | `cli:admin` | Remet en attente `quoi: inconnues` (réservations d'avant le suivi) ou `quoi: echecs`, sans rien envoyer |

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

## `POST /rendez-vous/placer`

- 422 avec `message` si rien n'a été tenté : canal fermé, ou aucune place libre.
- 200 sinon : `{ places, sans_email, sans_creneau, deja, refus: null }`.

Les convocations sont **posées en attente**, pas envoyées. L'envoi passe par
`/rendez-vous/convocations/envoyer` ou par la tâche planifiée (toutes les 5 min).

## `POST /rendez-vous/convocations/envoyer`

- 200 : `{ envoyees, echecs, restantes, bloque: null, en_cours: false }`. Rappeler tant que `restantes > 0`.
- 409 : un autre envoi (tâche planifiée, écran) tient le verrou ; rien n'a été tenté. Rappeler dans une minute.
- 503 : `bloque` porte la raison qui arrêterait aussi les suivantes (MailPulse
  désactivé, clé absente, service injoignable). Rappeler ne sert à rien tant
  qu'elle tient.

## `POST /rendez-vous/convocations/remettre`

Corps : `{ "quoi": "inconnues" }` ou `{ "quoi": "echecs" }`. Même geste que les
boutons de l'écran. Rend `{ remises, a_envoyer }` ; 422 si `quoi` est absent ou
inconnu. **N'envoie rien** : enchaîner avec `/convocations/envoyer`, en commençant
par `{ "max": 1 }` pour vérifier un premier courriel.

## Historique

- 2026-09-23 — ajout de `convocations/remettre` et du paramètre `max` de
  `convocations/envoyer`. Non cassant.

- 2026-09-22 — création de `diagnostic` et `convocations/envoyer`. **Breaking** :
  `placer` ne déclenche plus l'envoi des courriels, et répond 422 quand le canal est
  fermé (il comptait auparavant chaque dossier « sans créneau »).
