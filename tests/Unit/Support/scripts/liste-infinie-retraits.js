/*
 * Execute public/js/liste-infinie.js REELLEMENT livre, sous Node, contre un
 * serveur simule qui pagine par decalage comme Laravel. Chaque scenario
 * retire des lignes de l'ecran sans recharger, puis fait defiler jusqu'au
 * bout : la liste finale doit etre exactement le serveur, sans trou ni doublon.
 * Ecrit sur la sortie standard un JSON { scenario: { ok, detail } }.
 */
const fs = require('fs');
const chemin = process.argv[2];

// Le strict minimum de DOM pour que le script se charge, et de quoi faire
// tourner le chemin des listes rendues par le serveur (charger / ajuster) :
// une cible qui range des lignes <tr data-li-cle="…"> et un bas de liste.
const noop = () => {};
const cibles = {};
function ligne(parent, cle) {
    return {
        cle,
        getAttribute: (n) => (n === 'data-li-cle' ? String(cle) : null),
        get nextElementSibling() { const i = parent.enfants.indexOf(this); return parent.enfants[i + 1] || null; },
        remove() { const i = parent.enfants.indexOf(this); if (i >= 0) parent.enfants.splice(i, 1); },
    };
}
function cible(nom) {
    const c = {
        enfants: [],
        get lastElementChild() { return this.enfants[this.enfants.length - 1] || null; },
        get firstElementChild() { return this.enfants[0] || null; },
        insertAdjacentHTML(_, html) {
            for (const m of html.matchAll(/data-li-cle="(\d+)"/g)) c.enfants.push(ligne(c, Number(m[1])));
        },
        querySelectorAll(sel) { const m = sel.match(/data-li-cle="(\d+)"/); return c.enfants.filter(e => m && e.cle === Number(m[1])); },
        dispatchEvent: noop,
    };
    cibles[nom] = c;
    return c;
}
global.window = { innerHeight: 800 };
global.CustomEvent = function () {};
global.document = {
    readyState: 'complete',
    addEventListener: noop,
    getElementById: () => ({}),
    createElement: () => ({}),
    head: { appendChild: noop },
    body: {},
    documentElement: { clientHeight: 800 },
    querySelectorAll: () => [],
    querySelector: (sel) => cibles[sel] || null,
};
global.MutationObserver = function () { this.observe = noop; };
eval(fs.readFileSync(chemin, 'utf8'));
const LI = window.ListeInfinie;

function serveur(n) {
    const lignes = Array.from({ length: n }, (_, i) => i + 1);
    return {
        lignes,
        page(page, parPage) {
            const tranche = lignes.slice((page - 1) * parPage, page * parPage);
            return {
                lignes: tranche.map(id => ({ id })),
                pagination: {
                    current_page: page,
                    has_more: page * parPage < lignes.length,
                    total: lignes.length,
                    par_page: parPage,
                },
            };
        },
        retirer(ids) { ids.forEach(id => { const i = lignes.indexOf(id); if (i >= 0) lignes.splice(i, 1); }); },
    };
}

// Le melange Alpine, sans Alpine : un objet simple suffit, ses methodes n'utilisent que `this`.
function client(srv, parPage, crochet) {
    const c = LI.alpine({
        champ: 'items',
        tranche(page) { return Promise.resolve(crochet ? crochet(page) : null).then(() => srv.page(page, parPage)); },
    });
    c.$refs = {};
    c.$nextTick = noop;
    return c;
}

async function jusquauBout(c) {
    for (let i = 0; i < 500 && c.liAPlus; i++) await c.chargerSuite();
}

function verdict(c, srv) {
    const vus = c.items.map(x => x.id);
    const ok = JSON.stringify(vus) === JSON.stringify(srv.lignes);
    return { ok, detail: ok ? '' : `affiche ${vus.length} lignes (${new Set(vus).size} distinctes), serveur ${srv.lignes.length}` };
}

async function retirerAffichees(c, srv, ids) {
    srv.retirer(ids);
    ids.forEach(id => c.retirer(id));
}

// Le chemin des listes rendues par le serveur : un bas de liste, fetch branche
// sur le serveur simule, la reponse du contrat App\\Support\\ListeInfinie.
function basServeur(srv, parPage, crochet) {
    const c = cible('#t');
    const bouton = { disabled: false, hidden: false, textContent: '', addEventListener: noop };
    const bas = {
        dataset: { url: '/x', query: '', cible: '#t', libelle: 'bulletins', pageSuivante: '', total: '', affiches: '0', parPage: String(parPage) },
        isConnected: true,
        querySelector: (s) => (s === '.li-compteur' ? { textContent: '' } : bouton),
        getBoundingClientRect: () => ({ top: 99999, bottom: 99999 }),
    };
    global.fetch = (url) => {
        const page = Number(new URL(url, 'http://x').searchParams.get('page'));
        return Promise.resolve(crochet ? crochet(page) : null).then(() => {
            const r = srv.page(page, parPage);
            return {
                ok: true,
                json: () => Promise.resolve({
                    rows_html: r.lignes.map(x => '<tr data-li-cle="' + x.id + '"></tr>').join(''),
                    pagination: Object.assign({}, r.pagination, {
                        next_page: r.pagination.has_more ? page + 1 : null,
                        affiches: (page - 1) * parPage + r.lignes.length,
                    }),
                }),
            };
        });
    };
    // Premiere tranche rendue par la page.
    const premiere = srv.page(1, parPage);
    premiere.lignes.forEach(x => c.enfants.push(ligne(c, x.id)));
    bas.dataset.pageSuivante = premiere.pagination.has_more ? '2' : '';
    bas.dataset.affiches = String(premiere.lignes.length);
    bas.dataset.total = String(srv.lignes.length);
    LI.init({ querySelectorAll: () => [Object.assign(bas, { matches: () => true })] });
    return { bas, c };
}

async function jusquauBoutServeur(bas) {
    for (let i = 0; i < 500 && bas.dataset.pageSuivante; i++) {
        LI.charger(bas);
        await new Promise(r => setTimeout(r, 0));
        await new Promise(r => setTimeout(r, 0));
    }
}

function verdictServeur(c, srv) {
    const vus = c.enfants.map(e => e.cle);
    const ok = JSON.stringify(vus) === JSON.stringify(srv.lignes);
    return { ok, detail: ok ? '' : `affiche ${vus.length} lignes (${new Set(vus).size} distinctes), serveur ${srv.lignes.length}` };
}

const scenarios = {
    async sans_retrait() {
        const srv = serveur(95); const c = client(srv, 20);
        await c.recharger(); await jusquauBout(c);
        return verdict(c, srv);
    },
    async moins_qu_une_tranche() {
        const srv = serveur(95); const c = client(srv, 20);
        await c.recharger(); await c.chargerSuite();
        await retirerAffichees(c, srv, [3, 17, 25]);
        await jusquauBout(c);
        return verdict(c, srv);
    },
    async plus_qu_une_tranche() {
        // « Tout cocher » sur 100 lignes chargees, puis suppression groupee.
        const srv = serveur(230); const c = client(srv, 20);
        await c.recharger();
        for (let i = 0; i < 4; i++) await c.chargerSuite();
        await retirerAffichees(c, srv, c.items.map(x => x.id));
        await jusquauBout(c);
        return verdict(c, srv);
    },
    async retraits_cumules() {
        const srv = serveur(120); const c = client(srv, 20);
        await c.recharger(); await c.chargerSuite();
        await retirerAffichees(c, srv, [1, 2, 3]);
        await retirerAffichees(c, srv, c.items.slice(0, 25).map(x => x.id));
        await jusquauBout(c);
        return verdict(c, srv);
    },
    async retrait_pendant_un_chargement() {
        const srv = serveur(120);
        let pendant = null;
        const c = client(srv, 20, (page) => { if (pendant) { const f = pendant; pendant = null; return f(); } });
        await c.recharger(); await c.chargerSuite();
        // La tranche 3 est choisie, puis 5 lignes affichees partent avant que
        // le serveur ne la serve.
        pendant = () => retirerAffichees(c, srv, [5, 6, 7, 8, 9]);
        await c.chargerSuite();
        await new Promise(r => setTimeout(r, 0));
        await jusquauBout(c);
        return verdict(c, srv);
    },
    async insertion_en_tete() {
        // Le journal d'audit s'ecrit pendant qu'on le lit : une entree arrive
        // en tete apres la premiere tranche. La suite doit avancer, pas boucler.
        const srv = serveur(95); const c = client(srv, 25);
        await c.recharger();
        srv.lignes.unshift(1000);
        const demandes = [];
        const tranche = c.chargerSuite.bind(c);
        for (let i = 0; i < 40 && c.liAPlus; i++) { demandes.push(c.liPage); await tranche(); }
        const vus = c.items.map(x => x.id);
        const attendu = srv.lignes.filter(id => id !== 1000);
        const ok = JSON.stringify(vus) === JSON.stringify(attendu) && demandes.length <= 5;
        return { ok, detail: ok ? '' : `${demandes.length} chargements, ${vus.length} lignes affichees sur ${attendu.length}` };
    },
    async serveur_sans_retrait() {
        const srv = serveur(95); const { bas, c } = basServeur(srv, 20);
        await jusquauBoutServeur(bas);
        return verdictServeur(c, srv);
    },
    async serveur_plus_qu_une_tranche() {
        // Bulletins : « tout cocher » sur 100 lignes chargees, suppression groupee.
        const srv = serveur(230); const { bas, c } = basServeur(srv, 20);
        for (let i = 0; i < 4; i++) { LI.charger(bas); await new Promise(r => setTimeout(r, 0)); await new Promise(r => setTimeout(r, 0)); }
        const ids = c.enfants.map(e => e.cle);
        srv.retirer(ids); c.enfants.length = 0;
        LI.ajuster(bas, ids.length);
        await jusquauBoutServeur(bas);
        return verdictServeur(c, srv);
    },
    async serveur_retrait_pendant_un_chargement() {
        const srv = serveur(120);
        let pendant = null;
        const { bas, c } = basServeur(srv, 20, () => { if (pendant) { const f = pendant; pendant = null; return f(); } });
        LI.charger(bas); await new Promise(r => setTimeout(r, 0)); await new Promise(r => setTimeout(r, 0));
        pendant = () => {
            const ids = [5, 6, 7, 8, 9];
            srv.retirer(ids);
            ids.forEach(id => { const e = c.enfants.find(x => x.cle === id); if (e) e.remove(); });
            LI.ajuster(bas, ids.length);
        };
        await jusquauBoutServeur(bas);
        return verdictServeur(c, srv);
    },
};

(async () => {
    const out = {};
    for (const [nom, f] of Object.entries(scenarios)) {
        try { out[nom] = await f(); } catch (e) { out[nom] = { ok: false, detail: String(e) }; }
    }
    process.stdout.write(JSON.stringify(out));
})();
