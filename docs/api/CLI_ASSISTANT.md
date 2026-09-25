# API CLI — Assistant IA

Réglages de l'assistant IA d'une école depuis klassci-cli. Mêmes actions que
l'onglet « Assistant IA » de `/esbtp/settings` (service
`App\Domain\Assistant\Reglages\ReglagesAssistant`).

Authentification : jeton Sanctum. Lecture : `cli:read`. Écriture et test :
`cli:admin`.

## Principe

- Une clé posée ici est **chiffrée** (APP_KEY) dans la table `settings`, sous
  `assistant_cle_<fournisseur>`. Elle **prime sur le .env** du serveur.
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

Réponse d'état (`data`) :

```json
{
  "fournisseurs": { "openrouter": { "source": "reglages", "fin": "abcd" } },
  "modeles": [{ "cle": "or-gpt-4o-mini", "libelle": "GPT-4o mini (OpenRouter)", "fournisseur": "openrouter", "identifiant": "openai/gpt-4o-mini", "configure": true }],
  "modele_defaut": "or-gpt-4o-mini",
  "modele_effectif": "or-gpt-4o-mini"
}
```

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
```

## Historique

- Septembre 2026 — création.
