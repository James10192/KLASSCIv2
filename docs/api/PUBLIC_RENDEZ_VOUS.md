# API — Rendez-vous d'inscription (portail public)

Surface signée consommée par klassci.com. Même garde, même HMAC, seaux **séparés** du dépôt en ligne.

## Authentification

HMAC-SHA256 (`X-Klassci-Signature`, `X-Klassci-Timestamp`), comme les candidatures.
Canal : `portail.public:rendezvous`.

Le listing (`/creneaux`) est `catalogue`. Consulter, déplacer, annuler, retrouver portent un **plancher** de temps de réponse.

Aucun champ `matricule` : le garde le lirait et verrouillerait la réinscription. L'identifiant de secours s'appelle `identifiant`.

## Endpoints

Tous en `POST /api/public/rendez-vous/…`, corps JSON + `ip_client` injecté par le relais.

| Chemin | Nature | Corps | Succès |
|---|---|---|---|
| `/creneaux` | catalogue | `{}` | `{ creneaux: [{ id, date, heure_debut, heure_fin, etat }] }` |
| `/reserver` | identité | `reference`, `date_naissance`, `creneau_id` | 201 `{ enregistre, reservation }` |
| `/consulter` | identité + plancher | `reference`, `date_naissance` | `{ trouve, reservation, peut_modifier }` |
| `/deplacer` | identité + plancher | + `creneau_id` | `{ enregistre, reservation }` |
| `/annuler` | identité + plancher | `reference`, `date_naissance` | `{ enregistre: true }` |
| `/retrouver` | identité + plancher | `identifiant`, `date_naissance` | `{ trouve, reference? }` |

`etat` vaut `disponible` ou `complet` — jamais un compte exact (le relais met les lectures en cache 60 s).

Un créneau plein rend 409 `{ code: complet, creneaux }` : la liste rafraîchie, pour que l'écran se redessine sur place.

Échec d'identification : `{ trouve: false, code: introuvable }`, seau `rdv-ref` / `rdv-id` (5 essais / 15 min). Réponse uniforme.

## Dépôt

Les 201 de candidature et de réinscription emportent `reference_publique` et, s'il reste de la place, `rendez_vous: { date, heure_debut, heure_fin }` : le créneau est attribué au dépôt et une convocation (mail HTML + PDF) part. `null` s'il n'y a plus de créneau.

## Historique

- 2026-09-08 — le 201 attribue un créneau et envoie la convocation.
- 2026-09-08 — `rdv_ouvert` retiré du 201 (le canal se lit ailleurs). Référence émise au dépôt.
- 2026-09-07 — création (PR B, inscriptions physiques).
