'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(
    path.resolve(__dirname, '../../resources/views/esbtp/admissions/demandes/_script.blade.php'), 'utf8',
).replace(/^\s*<script>\s*/, '').replace(/\s*<\/script>\s*$/, '');

function reponse(status, body, headers = {}) {
    return {
        ok: status >= 200 && status < 300,
        status,
        text: async () => body,
        headers: {
            get: name => headers[name.toLowerCase()] ?? null,
        },
    };
}

function composant(fetchFake) {
    const window = {
        location: {
            href: 'https://presentation.klassci.com/esbtp/demandes',
            origin: 'https://presentation.klassci.com',
        },
    };
    const document = {
        querySelector: () => ({ content: 'csrf-test' }),
    };
    vm.runInNewContext(source, {
        window, document, fetch: fetchFake, URLSearchParams, FormData, URL,
        console,
    }, { filename: 'demandes/_script.blade.php', timeout: 1000 });
    assert.equal(typeof window.demandesInscription, 'function');
    return { composant: window.demandesInscription(), window };
}

test('le POST de conversion conserve les champs encodes et le CSRF', async () => {
    let requete;
    const { composant: c } = composant(async (url, options) => {
        requete = { url, options };
        return reponse(200, JSON.stringify({ ok: true, redirect: '/fiche/18' }), {
            'content-type': 'application/json',
        });
    });
    const champs = new URLSearchParams({
        nom: 'KOUAMÉ', 'parents[0][relation]': 'Père & tuteur', telephone: '+225 07 00',
    });
    const resultat = await c.appeler('/esbtp/inscriptions', { methode: 'POST', corps: champs });
    assert.equal(resultat.ok, true);
    assert.equal(requete.options.headers['Content-Type'], 'application/x-www-form-urlencoded;charset=UTF-8');
    assert.equal(requete.options.headers['X-CSRF-TOKEN'], 'csrf-test');
    assert.equal(requete.options.credentials, 'same-origin');
    assert.equal(requete.options.body.get('parents[0][relation]'), 'Père & tuteur');
    assert.equal(requete.options.body.get('nom'), 'KOUAMÉ');
});

test('200 HTML, JSON invalide et corps vide ne sont jamais des succès', async () => {
    for (const body of ['<html>Maintenance</html>', '{invalide', '']) {
        const { composant: c } = composant(async () => reponse(200, body, {
            'content-type': 'text/html',
        }));
        await assert.rejects(() => c.appeler('/test'), /Réponse inattendue du serveur/);
    }
});

test('les refus en amont restent prudents et affichent la référence technique', async () => {
    const { composant: c } = composant(async () => reponse(403, '<html>Refus</html>', {
        'content-type': 'text/html', 'edge-request-id': 'test-123',
    }));
    await assert.rejects(() => c.appeler('/test'), e => {
        assert.equal(e.statut, 403);
        assert.equal(e.referenceEdge, 'test-123');
        assert.match(e.message, /blocage possible en amont/);
        assert.match(e.message, /test-123/);
        return true;
    });
});

test('les refus Laravel JSON 403 et 422 ne sont pas attribués à la passerelle', async () => {
    for (const [status, message] of [[403, 'Permission refusée'], [422, 'Champ absent'], [419, 'Session expirée']]) {
        const { composant: c } = composant(async () => reponse(status, JSON.stringify({
            message, errors: { nom: ['Invalide'] },
        }), { 'content-type': 'application/json' }));
        await assert.rejects(() => c.appeler('/test'), e => {
            assert.equal(e.statut, status);
            assert.equal(e.message, message);
            assert.equal(e.donnees.errors.nom[0], 'Invalide');
            return true;
        });
    }
});

test('une inscription ne confirme jamais un JSON sans redirect valide', async () => {
    for (const payload of [
        { ok: true },
        { ok: false, redirect: '/fiche/18' },
        { ok: true, redirect: 'https://autre-domaine.example/fiche/18' },
    ]) {
        const { composant: c, window } = composant(async () => reponse(200, JSON.stringify(payload), {
            'content-type': 'application/json',
        }));
        c.cfg.store = '/esbtp/inscriptions';
        c.ins.prep = { candidature: { id: 5 }, matricule_automatique: true };
        c.ins.f = { nom: 'TEST', prenoms: 'Exemple', sexe: 'M', date_naissance: '2007-01-01', telephone: '0101010101', classe_id: 9 };
        c.redoChamps = () => ({});
        c.insPret = () => true;
        c.notifier = () => { throw Error('notification de succès interdite'); };
        await c.inscrire();
        assert.equal(window.location.href, 'https://presentation.klassci.com/esbtp/demandes');
        assert.ok(c.ins.message.length > 0);
        assert.equal(c.occupe, false);
    }
});
