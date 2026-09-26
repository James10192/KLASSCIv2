# Flux de l'assistant IA — contrat (v2)

Route : `POST /chatbot/message/stream` (session web, jeton CSRF). Réponse `text/event-stream`,
en-tête `x-vercel-ai-ui-message-stream: v1` : protocole *UI message stream* du Vercel AI SDK.
Chaque partie est une ligne `data: {json}` suivie d'une ligne vide ; le flux se termine par `data: [DONE]`.

Corps : `message` (≤ 1000 caractères), `session_id` (facultatif), `modele` (clé de `config/assistant.php`, facultatif),
`current_path`, `current_url`, `page_title` (facultatifs), `relance` (booléen, facultatif : « Réessayer » d'une réponse en erreur ou arrêtée ; le serveur retire cette réponse et réutilise la question déjà enregistrée au lieu d'en créer une seconde. Sans effet après une réponse aboutie).

## Parties standard

`start` (avec `messageMetadata.conversationId`) · `start-step` / `finish-step` (un par appel au modèle) ·
`text-start` / `text-delta` / `text-end` · `message-metadata` (`dbMessageId`, `title`) · `error` (`errorText`, sans détail interne) ·
`abort` · `finish`.

Le texte du modèle est du **Markdown GFM** : gras, listes, titres `###`, tableaux courts, liens relatifs vers
l'application (`[DOSSO Ibrahim](/esbtp/etudiants/2743)`), blocs ```` ```mermaid ````.

### Sécurité du rendu (obligatoire côté client)

Le texte du modèle n'est pas fiable : une injection peut passer par une donnée lue par un outil.
Tout ce qui suit vit dans `public/js/assistant/noyau.js`.

- **Balisage du texte** : marked, puis DOMPurify avec une liste blanche limitée à ce que marked
  produit — `p br strong em del s code pre blockquote ul ol li h1-h6 hr table thead tbody tr th td a` —
  et aux attributs `href title class align start`. Sont refusés `style` (un `url()` déclencherait une
  requête vers un tiers à chaque rendu, historique compris ; `position:fixed` permettrait de recouvrir
  l'écran d'un faux message), `id`, tout `data-*` et `aria-*`. Une classe n'est gardée que si elle
  s'écrit `language-…` (c'est la seule que lit l'enrichissement des blocs de code).
- **Liens** : n'est cliquable qu'un chemin interne qui satisfait
  `^/(esbtp|dashboard|chatbot)([/?#][A-Za-z0-9/_\-?=&%.#]*)?\z` (même règle que
  `AfficherTableau::estLienInterne`, côté serveur). Tout autre lien est rendu en texte :
  `/\site.tld`, une tabulation ou un retour à la ligne dans l'URL mènent sinon à un autre site.
  La même règle s'applique aux URL des widgets (cellules de tableau, actions de cartes,
  indicateurs, `lien.url`). Aucun lien ne s'ouvre dans un nouvel onglet.
- **Mermaid** : `securityLevel: 'strict'` toujours, y compris pour les blocs ```` ```mermaid ```` du texte libre,
  qui n'ont pas traversé le contrôle d'`afficher_diagramme`. Avant rendu sont retirés l'en-tête
  `---` de configuration, les directives `%%{…}` et les instructions `click`, `link`, `links`,
  `callback`, y compris après un `;` sur la même ligne. La source est validée par
  `mermaid.parse(…, { suppressErrors: true })` : invalide, elle s'affiche en code sans passer par
  `render`, et les nœuds d'erreur que mermaid laisse sous `<body>` sont retirés dans tous les cas.
- **SVG des diagrammes** : DOMPurify (profil `svg`) sans `a`, `foreignObject`, `image`, `use`,
  `animate`, `set`, ni `href` / `xlink:href` ; la feuille de style et les attributs `style` perdent
  `url()`, `@import` et `expression()`. Le fichier téléchargé est ce SVG nettoyé.
- **Copie en CSV** : une cellule qui commence par `= + - @`, une tabulation ou un retour chariot
  est préfixée d'une apostrophe, pour qu'un tableur ne l'exécute pas.

La preuve est le harnais navigateur `tests/js/assistant-securite.spec.mjs` (lancement dans
`tests/js/README.md`) : il charge les vrais scripts et rejoue ces charges malveillantes.

## Parties propres à KLASSCI

### `data-etape` — une étape de l'agent (un appel d'outil)
`id` = identifiant attribué par le serveur (`a00000001`, `a00000002`…), unique dans l'échange quel que soit le fournisseur. Émise plusieurs fois ; la dernière l'emporte.

```json
{ "type": "data-etape", "id": "a00000001",
  "data": { "nom": "search_debtors", "libelle": "Recherche des retards de paiement…",
            "etat": "en_cours | termine | echec | retire",
            "resume": "15 étudiants en retard sur 219", "detail": "classe : B2 COM", "duree_ms": 840 } }
```

`resume`, `detail`, `duree_ms` n'existent qu'à `termine` / `echec`. `detail` est absent sur un appel refusé.
`retire` : l'étape disparaît (modèle abandonné au profit du suivant, ou appel identique déjà fait dans l'échange).

### `data-widget` — le résultat d'une étape, affiché sous elle
Même `id` que l'étape qui l'a produit ; émis une fois, juste après l'étape terminée, donc à sa place chronologique.

```json
{ "type": "data-widget", "id": "a00000001", "data": { "kind": "…", "…": "…", "lien": {"url": "/esbtp/…", "libelle": "…"}, "total": 219 } }
```

| `kind` | Données |
|---|---|
| `graphique` | `type` (`barres`, `courbe`, `anneau`), `titre`, `libelles[]`, `series[{nom, valeurs[]}]`, `unite` |
| `tableau` | `titre`, `colonnes[{cle, libelle, type}]` (`texte`, `montant`, `nombre`, `date`, `lien`, `statut`), `lignes[]` ; lien `{url, texte}` (URL interne seulement), statut `{texte, ton}` (`succes`, `alerte`, `danger`, `neutre`) |
| `diagramme` | `titre`, `mermaid` (types acceptés : flowchart, graph, sequenceDiagram, stateDiagram(-v2), timeline, mindmap, journey, gantt, pie ; sans `%%{…}`, `click`, lien) |
| `kpis` | `titre`, `elements[{libelle, valeur, unite, repere, ton, url}]` |
| `approbation` | proposition d'un outil `proposer_*` : `id`, `jeton`, `titre`, `resume`, `colonnes[]` (libellés), `lignes[][]`, `avertissements[]`, `risque` (`moyen`, `eleve`), `expire_a`, `etat`, `valider_url`, `refuser_url` (relatives). Rien n'est écrit avant le clic. |
| `table`, `cards`, `fee-groups`, `payment-groups`, `stat-cards`, `timetable`, `checklist`, `form`, `proposal` | formes `display_data` historiques |

### `data-suites` (fin d'échange) · `data-lien`
`{follow_up: [...], follow_up_actions: [...]}` ; `{url}` n'est envoyé que si aucun widget ne porte déjà le lien.

## Historique

`GET /chatbot/conversations/{conversationId}/history` : chaque message porte `parties` (nulle pour les messages
antérieurs à la v2, qui gardent `content` + `display_type` / `display_data`) :

```json
[ {"type": "texte", "texte": "…"},
  {"type": "etape", "id": "a00000001", "nom": "…", "libelle": "…", "etat": "termine", "resume": "…", "detail": "…", "duree_ms": 840},
  {"type": "widget", "id": "a00000001", "kind": "…", "data": {…}},
  {"type": "suites", "data": {…}}, {"type": "lien", "url": "…"} ]
```

### Valider ou refuser une proposition

`POST /chatbot/propositions/{id}/valider` avec `{"jeton": "…"}` ; `POST /chatbot/propositions/{id}/refuser`.
Réponse `{statut, message, lien?}` : **200** si `statut` vaut `executee` (ou `refusee`), **409** sinon —
`traitee` (déjà traitée), `expiree` (30 minutes), `perimee` (les données ont changé depuis la proposition : rien
n'est écrit, la proposition est à refaire), `echec` (erreur à l'écriture, rien n'est écrit), `refus` (jeton, personne
ou droit). Le serveur refait la préparation au moment du clic et compare son empreinte avant d'écrire. Dans
l'historique, `etat` d'un widget `approbation` est l'état réel au moment de la lecture.

## Côté modèle (pour mémoire)

Le modèle ne reçoit pas le widget : il reçoit `ResumeOutil::pourModele()` (douze lignes au plus, 6 000 octets,
identifiants et URL, consigne de ne pas recopier le widget). Un appel identique (même outil, mêmes arguments) n'est
pas rejoué. La trace compacte des appels est enregistrée dans `metadata.trace` et rejouée pour les trois dernières réponses.

## Historique des versions

- **v2.1 (septembre 2026)** — nouveau kind `approbation` et routes `propositions/{id}/valider|refuser` (ajout, non cassant).

- **v2 (septembre 2026)** — paramètre `relance`. ⚠️ changement cassant : `data-outil` est remplacé par `data-etape` ; les résultats ne partent
  plus en `data-table` / `data-cards` / … en fin de réponse mais en `data-widget` sous chaque étape. Nouveaux kinds
  `graphique`, `tableau`, `diagramme`, `kpis`. Champ `parties` dans l'historique.
- **v1 (septembre 2026)** — protocole UI message stream, puces `data-outil`, résultats en fin de réponse.
