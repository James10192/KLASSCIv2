# API CLI — Actions lentes

`GET /api/cli/traces/lentes` — lecture seule, ability `cli:read`.

Les actions de l'école qui ont dépassé leur seuil, ou qui ont échoué, regroupées
par (type, nom) et classées par fréquence. C'est ce que lit le contrôle
`slow_actions` de la santé du parc (adminKlassci, `tenant:health-check`).

| | |
|---|---|
| Contrôleur | `App\Http\Controllers\API\CLI\CLITracesLentesController` |
| Agrégat | `App\Domain\Exploitation\TracesLentes\AgregatDesTraces` |
| Écriture | `App\Domain\Exploitation\TracesLentes\EnregistreurDeTraces` |
| Table | `traces_lentes`, purgée à 30 jours (`traces:purger`, chaque nuit à 03:20) |

## Ce qui est mesuré

| type | nom | écrit quand |
|---|---|---|
| `requete` | route nommée (`esbtp.inscriptions.index`), sinon `METHODE uri` | au-dessus d'un seuil, ou statut ≥ 500 |
| `travail` | `file:<classe>`, `bulletins.<type>.tranche`, `bulletins.<type>.conclusion`, `bulletins.<type>.total`, `export.pdf:<rapport ou route>`, `export.excel:<rapport>`, `mailpulse:<opération>`, `whatsapp:<modèle>`, `courriel:<mailer>` | au-dessus d'un seuil, ou en échec |
| `commande` | nom de la commande artisan (celles du planificateur sont des processus artisan) | au-dessus d'un seuil, ou code de sortie non nul |

Ne sont pas mesurées, parce qu'elles tournent sans fin ou ne font qu'envelopper
d'autres commandes déjà mesurées : `schedule:run`, `schedule:work`,
`schedule:finish`, `queue:work`, `queue:listen`, `tinker`, `serve`, `test`.

Pour chaque trace : durée, nombre et temps cumulé des requêtes SQL, mémoire
maximale du processus, code (statut HTTP, code de sortie, ou 0/1 pour un
travail), utilisateur, et `details` (méthode, route, rôle, identifiant de
requête, ou début/fin/résultat d'une commande). **Seule la requête SQL la plus
lente est gardée, réduite à sa forme** : chaînes et nombres remplacés par `?`,
160 caractères au plus. Aucune valeur saisie n'entre dans la table.

Un courriel SMTP ne porte que sa durée : son échec n'émet aucun événement
Laravel, il est donc vu par le journal, pas ici. Ses envois MailPulse, eux,
passent par `mailpulse:*`, qui note l'échec.

## Seuils

Réglés par école dans `/esbtp/settings`, section « Surveillance des lenteurs » :

| clé | défaut | bornes |
|---|---|---|
| `exploitation.traces_lentes.seuil_ms` | 1000 | 50 à 60 000 |
| `exploitation.traces_lentes.seuil_requetes` | 100 | 10 à 10 000 |

Une valeur hors bornes est ramenée dans les bornes à la lecture.

Ils sont relus au plus une fois par minute et par processus. Coupe-circuit
d'exploitation, sans redéploiement : `TRACES_LENTES=false` dans le `.env`.

## Paramètres

| nom | défaut | |
|---|---|---|
| `jours` | 7 | fenêtre se terminant maintenant, 1 à 30 |
| `depuis`, `jusqua` | — | dates ; remplacent `jours` |
| `type` | — | `requete`, `travail` ou `commande` |
| `nom` | — | contient ce texte |
| `limite` | 20 | 1 à 100 |

## Réponse

```json
{
  "success": true,
  "message": "2 action(s) lente(s)",
  "data": {
    "periode": { "depuis": "2026-09-25T18:00:00+00:00", "jusqua": "2026-10-02T18:00:00+00:00" },
    "seuils": { "duree_ms": 1000, "requetes_sql": 100 },
    "actif": true,
    "lignes_lues": 11,
    "tronque": false,
    "actions": [
      { "type": "requete", "nom": "esbtp.resultats.index", "nombre": 10, "par_jour": 1.4,
        "mediane_ms": 1450, "p95_ms": 9000, "max_ms": 9000, "mediane_sql": 105,
        "echecs": 1, "derniere": "2026-10-02 18:00:00" }
    ]
  }
}
```

`echecs` compte les statuts ≥ 500 pour une requête, les codes non nuls pour un
travail ou une commande. Le centile est pris au rang le plus proche ; la
médiane d'un nombre pair de valeurs est la moyenne des deux du milieu. Au-delà
de 50 000 lignes dans la fenêtre, la lecture s'arrête et `tronque` vaut `true` :
ce sont alors les traces **les plus récentes** qui sont lues.

Une commande qui lève avant sa fin est notée avec `code` 1 et
`details.resultat` = `interrompue` (`ok` et `echec` pour une fin normale).
Les traces d'un PDF ou d'un envoi produits pendant une page sont écrites avec
celle de la page, après la réponse. Si la page tombe (temps d'exécution ou
mémoire dépassés), seules les mesures imbriquées déjà terminées sont écrites,
au mieux, à l'arrêt du processus : ni la page ni l'action en cours au moment
de la chute ne laissent de trace.
Cette route ne se trace pas elle-même : la console la lit chaque heure.

## Historique

- **Octobre 2026** — création.
- **Octobre 2026** — seuils bornés aussi par le haut ; lecture tronquée sur les plus récentes ; résultat `interrompue` ; route exclue de sa propre mesure. Pas de rupture de contrat.
