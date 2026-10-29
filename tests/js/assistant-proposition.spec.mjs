/*
 * Harnais navigateur : place et issue d'une carte de proposition (Valider).
 *
 * Charge les VRAIS scripts public/js/assistant/*.js dans Chromium et vérifie :
 *   1. la carte à valider reste en bas de la réponse, sous la phrase qui la
 *      présente, en diffusion comme à la relecture d'historique (avant, elle
 *      arrivait à l'appel de l'outil et la fin de la réponse s'écrivait dessous) ;
 *   2. un clic sur Valider la change en reçu sur place : plus de boutons, le
 *      message de l'écriture et son lien ;
 *   3. une carte rouverte déjà validée montre ce même reçu (issue_message).
 *
 * Lancement : node tests/js/assistant-proposition.spec.mjs (voir README.md).
 * Code de sortie : 0 si tout passe, 1 sinon.
 */
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ICI = path.dirname(fileURLToPath(import.meta.url));
const RACINE = path.resolve(ICI, '..', '..');
const SCRIPTS = ['noyau', 'markdown', 'rendus', 'vue', 'composant'];
const ORIGINE = 'http://klassci.test';

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

const PAGE = `<!doctype html><html lang="fr"><head><meta charset="utf-8">
<link rel="stylesheet" href="/css/assistant.css">
</head><body>
<div class="ast-panel is-open"><div class="ast-thread"><div class="ast-thread-inner"><div id="hote" class="ast-answer"></div></div></div></div>
${SCRIPTS.map((n) => `<script src="/js/assistant/${n}.js"></script>`).join('\n')}
</body></html>`;

const CARTE = {
    id: 42, jeton: 'j', titre: 'Notes : Devoir 1', resume: '1 nouvelle',
    colonnes: ['Étudiant', 'Note'], lignes: [['KOUASSI Aya', '14']],
    avertissements: [], risque: 'moyen', etat: 'en_attente',
    valider_url: '/chatbot/propositions/42/valider', refuser_url: '/chatbot/propositions/42/refuser'
};

const { chromium } = await chargerPlaywright();
const browser = await chromium.launch({ executablePath: executableChromium() });
const page = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
const erreurs = [];
page.on('pageerror', (e) => erreurs.push(e.message));
await page.route('**/*', async (route) => {
    const url = route.request().url();
    if (!url.startsWith(ORIGINE)) { return route.abort(); }
    const chemin = new URL(url).pathname;
    if (chemin === '/') { return route.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: PAGE }); }
    const fichier = path.join(RACINE, 'public', chemin);
    if (fichier.startsWith(path.join(RACINE, 'public')) && fs.existsSync(fichier)) {
        return route.fulfill({ status: 200, contentType: chemin.endsWith('.css') ? 'text/css' : 'application/javascript', body: fs.readFileSync(fichier) });
    }
    return route.fulfill({ status: 404, body: '' });
});

await page.goto(ORIGINE + '/');
await page.waitForFunction(() => window.KlassciAst && window.KlassciAst.Vue);

const constat = await page.evaluate(async (carte) => {
    const A = window.KlassciAst;
    const ctx = {
        repondreProposition: () => Promise.resolve({ ok: true, statut: 'executee', message: '1 note enregistrée.', lien: '/esbtp/evaluations/7' })
    };
    const nouveauMsg = (key) => ({ key, role: 'assistant', status: 'streaming', liens: [], suites: { questions: [], actions: [] } });
    const hote = document.getElementById('hote');
    const ordre = (vue) => Array.from(vue.root.children).map((n) => n.classList.contains('ast-widget--approbation') ? 'carte' : (n.querySelector('.ast-step') || n.classList.contains('ast-steps') ? 'etapes' : 'texte'));

    // Diffusion : étape, carte, PUIS la phrase de Nanan.
    const diffusee = new A.Vue(nouveauMsg('d'), ctx);
    diffusee.monter(hote);
    diffusee.etape('a1', { nom: 'proposer_saisie_notes', etat: 'termine', resume: 'Proposition prête' });
    diffusee.widget('a1', 'approbation', carte);
    diffusee.texteDebut('t1');
    diffusee.texteDelta('t1', 'Je propose la note de 14 pour KOUASSI Aya.');
    diffusee.texteFin('t1');
    diffusee.terminer(false);
    const ordreDiffusion = ordre(diffusee);

    diffusee.root.querySelector('.ast-approval-actions .ast-btn--primary').click();
    await new Promise((r) => setTimeout(r, 50));
    const recu = diffusee.root.querySelector('.ast-approval-state');
    const apresClic = {
        boutons: diffusee.root.querySelectorAll('.ast-approval-actions').length,
        texte: recu.textContent, lien: recu.querySelector('a') ? recu.querySelector('a').getAttribute('href') : null,
        close: diffusee.root.querySelector('.ast-approval').classList.contains('is-close')
    };

    // Relecture : même ordre, et la carte validée montre le même reçu.
    const relue = new A.Vue(nouveauMsg('r'), ctx);
    relue.monter(hote);
    relue.chargerParties([
        { type: 'etape', id: 'a1', nom: 'proposer_saisie_notes', etat: 'termine' },
        { type: 'widget', id: 'a1', kind: 'approbation', data: Object.assign({}, carte, { etat: 'executee', issue_message: '1 note enregistrée.', issue_lien: '/esbtp/evaluations/7' }) },
        { type: 'texte', texte: 'Je propose la note de 14 pour KOUASSI Aya.' }
    ]);
    const recuRelu = relue.root.querySelector('.ast-approval-state');

    return {
        ordreDiffusion, apresClic, ordreRelecture: ordre(relue),
        relu: { boutons: relue.root.querySelectorAll('.ast-approval-actions').length, texte: recuRelu.textContent, lien: recuRelu.querySelector('a') ? recuRelu.querySelector('a').getAttribute('href') : null }
    };
}, CARTE);

if (process.env.CAPTURE) { await page.screenshot({ path: process.env.CAPTURE, fullPage: true }); }
await browser.close();

const echecs = [];
const verifier = (ok, message) => { if (!ok) { echecs.push(message); } };
verifier(constat.ordreDiffusion.at(-1) === 'carte' && constat.ordreDiffusion.indexOf('texte') < constat.ordreDiffusion.indexOf('carte'), 'diffusion : la carte doit rester sous le texte, ordre = ' + constat.ordreDiffusion.join(' > '));
verifier(constat.ordreRelecture.at(-1) === 'carte', 'relecture : la carte doit rester en bas, ordre = ' + constat.ordreRelecture.join(' > '));
verifier(constat.apresClic.boutons === 0 && constat.apresClic.close, 'après Valider, les boutons disparaissent');
verifier(constat.apresClic.texte.includes('1 note enregistrée.') && constat.apresClic.lien === '/esbtp/evaluations/7', 'après Valider, le reçu porte le message et le lien : ' + JSON.stringify(constat.apresClic));
verifier(constat.relu.boutons === 0 && constat.relu.texte.includes('1 note enregistrée.') && constat.relu.lien === '/esbtp/evaluations/7', 'à la relecture, le même reçu : ' + JSON.stringify(constat.relu));
verifier(erreurs.length === 0, 'erreurs JavaScript : ' + erreurs.join(' | '));

if (echecs.length) {
    console.error('ÉCHEC\n- ' + echecs.join('\n- '));
    process.exit(1);
}
console.log('OK : carte en bas, reçu sur place en direct et à la relecture.');
