# API CLI — état du planificateur

Savoir, sans SSH ni cPanel, si la tâche cron de l'instance tourne. Sans elle, rien
de planifié ne part : boîte d'envoi KLASSCI Care (avis Nanan, signalements remis à
plus tard), suivi des réponses du support, relances, purges — et rien ne le dit à l'école.

## `GET /api/cli/planificateur`

Jeton `cli:admin`. Lecture seule.

```json
{
  "actif": true,
  "dernier_passage": "2026-10-02T07:12:00+00:00",
  "source": "master",
  "silence_secondes": 34,
  "passages": { "cron": "2026-10-01T18:02:00+00:00", "master": "2026-10-02T07:12:00+00:00" },
  "boite_envoi_care": { "en_attente": 0, "plus_ancienne": null }
}
```

- `actif` : un passage depuis moins de 180 secondes (deux passages manqués au plus).
- `source` : `cron` (tâche cron propre à l'instance) ou `master` (lancé par
  adminKlassci, commande `tenant:planificateur`). `null` : jamais lancé depuis le
  déploiement de ce contrôle.
- `passages` : le dernier passage de chaque source. adminKlassci ne lance le
  planificateur d'une école que si `passages.cron` a plus de 150 secondes.
- `boite_envoi_care.en_attente` qui grossit alors que `actif` est vrai : le Master
  refuse ou ne répond pas — voir les journaux (`GET /api/cli/logs?search=Care`).

Le pouls est écrit à chaque minute dans `storage/app/planificateur.json`
(`App\Domain\Exploitation\PoulsPlanificateur`). adminKlassci lit ce fichier
directement : son chemin est un contrat entre les deux applications.

## Historique

- **Octobre 2026** — création.
