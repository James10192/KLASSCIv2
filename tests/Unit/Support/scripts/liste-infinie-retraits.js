/*
 * Execute public/js/liste-infinie.js REELLEMENT livre, sous Node, contre un
 * serveur simule qui pagine par decalage comme Laravel. Chaque scenario
 * retire des lignes de l'ecran sans recharger, puis fait defiler jusqu'au
 * bout : la liste finale doit etre exactement le serveur, sans trou ni doublon.
 * Ecrit sur la sortie standard un JSON { scenario: { ok, detail } }.
 */
const fs = require('fs');
const chemin = process.argv[2];

// Le strict minimum de DOM pour que le script se charge.
const noop = () => {};
global.window = {};
global.document = {
    readyState: 'complete',
    addEventListener: noop,
    getElementById: () => ({}),
    createElement: () => ({}),
    head: { appendChild: noop },
    body: {},
    querySelectorAll: () => [],
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
};

(async () => {
    const out = {};
    for (const [nom, f] of Object.entries(scenarios)) {
        try { out[nom] = await f(); } catch (e) { out[nom] = { ok: false, detail: String(e) }; }
    }
    process.stdout.write(JSON.stringify(out));
})();
