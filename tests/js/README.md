# Harnais navigateur

`assistant-securite.spec.mjs` charge les scripts réels de `public/js/assistant/` dans Chromium
(Playwright) et rejoue des charges malveillantes dans le texte, un tableau et des diagrammes.

```bash
node tests/js/assistant-securite.spec.mjs    # code 0 : toutes les vérifications passent
```

Il faut Node 18+ et Playwright avec Chromium (`npm i -g playwright && npx playwright install chromium`).
Variables utiles : `PLAYWRIGHT_PATH` (dossier `node_modules` qui contient playwright), `CHROMIUM_PATH`,
`ASSISTANT_CDN_DIRECT=1` (Chromium joint le CDN lui-même au lieu de passer par `curl`), `CAPTURE=fichier.png`.
