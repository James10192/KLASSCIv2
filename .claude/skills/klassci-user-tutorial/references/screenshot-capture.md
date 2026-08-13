# Capture des vraies captures d'écran KLASSCI (dev-browser)

Table des matières : Principe · Login presentation · Bypass LWS (tenants prod) · Cadrage · Nommage · Lecture des captures.

## Principe

Les captures se prennent avec `dev-browser` (même outil que `visual-check` / `klassci-test-e2e`),
navigateur Chromium à état persistant. On se connecte, on navigue vers l'écran exact de chaque étape,
on capture, on relit le PNG. **Toujours authentifié** : une capture non connectée montre le login, pas
l'écran réel.

Vérifier l'outil d'abord : `npx --no-install dev-browser --help`. Sinon `npx --yes dev-browser` le
télécharge à la volée.

## Login presentation (défaut, simple)

`presentation.klassci.com` n'a pas la protection anti-bot LWS → capture directe possible, y compris en
headless. Login : `superadmin` / `Bonjour@123`.

```bash
npx dev-browser --browser klassci-tuto --timeout 90 <<'EOF' 2>&1 | tail -15
const page = await browser.getPage("main");
await page.goto("https://presentation.klassci.com/login", { waitUntil: "domcontentloaded", timeout: 25000 });
await page.fill('input[placeholder="Votre identifiant"]', "superadmin");
await page.fill('input[placeholder="Votre mot de passe"]', "Bonjour@123");
await page.click('button[type="submit"]');
await new Promise(r => setTimeout(r, 4000));
console.log("Logged in:", page.url());
// Naviguer vers l'écran de l'étape puis capturer
await page.goto("https://presentation.klassci.com/esbtp/attendances/create?_t=" + Date.now(), { waitUntil: "domcontentloaded", timeout: 25000 });
await new Promise(r => setTimeout(r, 2500));
const p = await saveScreenshot(await page.screenshot({ fullPage: false }), "etape1-marquer-presences.png");
console.log("Screenshot:", p);
EOF
```

`--browser <nom>` persiste la session (cookies) entre appels : se logger une fois, puis enchaîner les
captures d'étapes dans des appels suivants sans re-login.

## Bypass LWS (tenants prod : esbtp-yakro, esbtp-abidjan, ephrata, rostan)

Ces tenants sont derrière LWS DDoS Protection qui bloque les navigateurs headless (« Vérification
échouée »). Pour passer : **NE PAS `--headless`**, **spoofer le User-Agent** avant le `goto`, et
`--browser <nom>` pour persister la session.

```bash
npx dev-browser --browser tuto-yakro --timeout 90 <<'EOF' 2>&1 | tail -15
const page = await browser.getPage("main");
await page.setExtraHTTPHeaders({
  "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
});
await page.goto("https://esbtp-yakro.klassci.com/login", { waitUntil: "domcontentloaded", timeout: 25000 });
await page.fill('input[placeholder="Votre identifiant"]', "modestekouakou@esbtp-yakro.klassci.com");
await page.fill('input[placeholder="Votre mot de passe"]', "modestekouakou2025");
await page.click('button[type="submit"]');
await new Promise(r => setTimeout(r, 4000));
// + cache-bust ?_t=Date.now() sur chaque navigation (bypass cache edge Varnish)
EOF
```

Préférer `presentation` pour les captures d'un tuto générique : l'écran est identique (même code
déployé) et sans friction. Ne capturer sur un tenant prod que si le tuto doit montrer des données
réelles de cette école.

## Cadrage

- **Vue d'ensemble d'un écran** : `page.screenshot({ fullPage: false })` (viewport 1280×720 par défaut)
  donne un cadre stable, idéal pour annoter. `fullPage: true` pour les longues listes, mais l'annotation
  en % devient moins précise.
- **Zone ciblée** (un bouton, un champ) : scroller l'élément à l'écran
  (`await page.locator('#sel').scrollIntoViewIfNeeded()`) avant la capture, ou capturer l'élément :
  `await page.locator('.card').screenshot({...})`.
- **Ouvrir les menus déroulants / onglets** nécessaires AVANT de capturer (ex : onglet « Saisie manuelle
  (heures) »), sinon l'écran ne montre pas l'étape.

## Nommage

Un PNG par étape, ordonné, descriptif : `etape1-menu-presences.png`, `etape2-choix-classe.png`,
`etape3-statut-absent.png`, `etape4-saisie-manuelle.png`. Ranger dans un dossier de travail dédié
(ex : `scratchpad/tuto-presences/`). Ces chemins seront référencés en `file://` dans le template HTML.

## Lecture des captures

`saveScreenshot(buf, "nom.png")` retourne le chemin (souvent sous `~/.dev-browser/tmp/`). Relire avec
l'outil Read sur ce chemin pour vérifier le cadrage AVANT d'annoter (le bon écran, l'élément visible,
pas de modal parasite). Recapturer si nécessaire.
