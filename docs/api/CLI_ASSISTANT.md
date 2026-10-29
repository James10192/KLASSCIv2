# API CLI — Assistant IA

Réglages de l'assistant IA d'une école depuis klassci-cli. Mêmes actions que
l'onglet « Assistant IA » de `/esbtp/settings` (service
`App\Domain\Assistant\Reglages\ReglagesAssistant`).

Authentification : jeton Sanctum. Lecture : `cli:read`. Écriture et test :
`cli:admin`.

## Principe

- Une clé posée ici est **chiffrée** (APP_KEY, cast `encrypted`) dans sa propre
  table `assistant_cles`, hors des réglages : ni l'export, ni les sauvegardes,
  ni le journal, ni `/api/cli/settings` ne la voient. Elle **prime sur le .env**.
- Elle n'est **jamais renvoyée** : les réponses ne portent que la source
  (`reglages`, `env`, `aucune`) et les 4 derniers caractères.
- Fournisseurs : ceux de `config/assistant.php` (`openrouter`, `anthropic`,
  `openai`, `gemini`, `mistral`, `deepseek`).

## Endpoints

| Méthode | Route | Corps | Rôle |
|---|---|---|---|
| GET | `/api/cli/assistant` | — | État : fournisseurs, modèles, modèle par défaut et effectif |
| PUT | `/api/cli/assistant/cle` | `{fournisseur, cle}` | Pose la clé (422 si fournisseur inconnu, clé vide ou avec espaces) |
| DELETE | `/api/cli/assistant/cle/{fournisseur}` | — | Retire la clé posée par l'école |
| PUT | `/api/cli/assistant/modele` | `{modele}` | Modèle par défaut (réglage `assistant.modele_defaut`, 422 si inconnu) |
| POST | `/api/cli/assistant/tester` | `{modele?}` | Essai réel : une réponse courte puis un appel d'outil fictif par modèle. Throttle 6/min |
| GET | `/api/cli/assistant/consommation` | `?jours=30` (1 à 366) | Coût de l'assistant sur la période. Droit `cli:read` |
| PUT | `/api/cli/assistant/budget` | `{fcfa}` (≥ 0 ; 0 = sans limite) | Budget mensuel d'IA de l'école. Droit `cli:admin` |

Réponse d'état (`data`) :

```json
{
  "fournisseurs": { "openrouter": { "source": "reglages", "fin": "abcd" } },
  "modeles": [{ "cle": "or-gpt-4o-mini", "libelle": "GPT-4o mini (OpenRouter)", "fournisseur": "openrouter", "identifiant": "openai/gpt-4o-mini", "configure": true }],
  "modele_defaut": "or-gpt-4o-mini",
  "modele_effectif": "or-gpt-4o-mini",
  "paliers": { "economique": ["or-gemini-flash-lite"], "standard": ["or-gemini-flash"], "avance": [] },
  "budget": { "mensuel_fcfa": 15000, "source": "master", "depense_du_mois_fcfa": 1240.5, "etat": "normal" }
}
```

`budget.source` : d'où vient le budget en vigueur — `master` (fixé dans
adminKlassci, champ `assistant.budget_mensuel_fcfa` de `/tenants/{code}/limits` ;
il prime, et `PUT /assistant/budget` répond alors 422), `ecole` (réglage posé par
klassci-cli) ou `env`. En pause, `modele_effectif` vaut `null`.

`paliers` : modèles réellement joignables de chaque palier du routage automatique,
dans l'ordre d'essai. `budget.etat` : `normal`, `economique` (budget atteint :
palier économique seulement), `pause` (seuil de pause atteint : aucun appel).
`modele_defaut` : modèle préféré posé par l'école (`assistant:modele`) ; il passe
en tête de son palier. `modele_effectif` : modèle que le routeur prendrait pour une
question simple, en ce moment.

Consommation (`data`) :

```json
{
  "periode": { "depuis": "2026-08-27", "jusqua": "2026-09-25" },
  "total": { "echanges": 128, "appels": 214, "tokens_entree": 912000, "tokens_sortie": 41000, "cout_usd": 0.62, "cout_fcfa": 372.1 },
  "par_modele":   [{ "cle": "or-gemini-flash-lite", "echanges": 96, "appels": 150, "tokens_entree": 0, "tokens_sortie": 0, "cout_usd": 0.2, "cout_fcfa": 120.4 }],
  "par_palier":   [{ "cle": "economique", "...": "mêmes champs" }],
  "par_fonction": [{ "cle": "question", "...": "mêmes champs" }],
  "par_jour":     [{ "cle": "2026-09-25", "...": "mêmes champs" }],
  "par_personne": [{ "cle": 3, "nom": "Awa Koné", "...": "mêmes champs (10 premiers)" }],
  "budget": { "mensuel_fcfa": 15000, "depense_du_mois_fcfa": 372.1, "etat": "normal" }
}
```

Une ligne de consommation est écrite par modèle appelé dans un échange
(`assistant_consommations`). Le coût est celui que renvoie OpenRouter
(`usage.cost`, en dollars, `cout_exact = true`) ; pour les autres fournisseurs, il
est calculé sur le tarif déclaré dans `config/assistant.php`. Il est converti en
FCFA au taux `assistant.budget.taux_usd_fcfa`, conservé sur chaque ligne.
`fonction` : `question` (un échange) ou `titre` (titre de conversation).

Rapport de test (`data.resultats[]`) : `texte.ok`, `texte.ms`, `texte.erreur`
(`http_401` clé refusée, `http_402` crédit épuisé, `limite_debit`, `reseau`…),
`outils.ok` (le modèle a appelé l'outil fictif), `outils.appels`.

## klassci-cli

```bash
klassci assistant:etat presentation
klassci assistant:cle presentation openrouter          # saisie masquée
klassci assistant:cle presentation openrouter < cle.txt
klassci assistant:modele presentation or-gpt-4o-mini
klassci assistant:test presentation
klassci assistant:consommation presentation --jours=7
klassci assistant:budget presentation 15000            # 0 = sans limite
```

## Historique

- Septembre 2026 — création.
- Septembre 2026 — `consommation` et `budget` ajoutés ; `etat` expose `paliers` et `budget` (ajout de champs, non cassant).
- Septembre 2026 — **changement de sens (cassant pour qui lisait ces champs)** :
  - `etat.modele_defaut` vaut `null` quand l'école n'a choisi aucun modèle (c'est alors le routeur qui choisit) ;
    il ne renvoie plus le défaut de la configuration.
  - `etat.modele_effectif` : modèle d'une question simple en ce moment ; `null` quand l'assistant est en pause budgétaire.
  - `etat.budget.source` : `master` (adminKlassci), `ecole` ou `env`.
  - `PUT budget` répond **422** quand le budget est fixé dans adminKlassci : il se modifie là-bas.
