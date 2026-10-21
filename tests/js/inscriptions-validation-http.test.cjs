'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const common = fs.readFileSync(path.resolve(__dirname, '../../public/js/inscriptions/common.js'), 'utf8');
class FormDataFake {
    constructor(form) { this.values = form?.fields || []; }
    *entries() { yield* this.values; }
}
const response = (status, body, headers = {}) => ({
    ok: status >= 200 && status < 300, status,
    text: async () => body,
    headers: { get: name => headers[name.toLowerCase()] || null },
});

function client(fetchMock) {
    const toasts = [], eventNames = [];
    const document = {
        getElementById: () => null,
        createElement: () => ({ id: '', textContent: '' }),
        head: { appendChild: () => {} },
        addEventListener: () => {},
        querySelector: () => ({ content: 'csrf-opaque' }),
    };
    const window = {
        showToast: (message, kind) => toasts.push({ message, kind }),
        dispatchEvent: event => eventNames.push(event.type),
    };
    vm.runInNewContext(common, {
        window, document, fetch: fetchMock, URLSearchParams,
        FormData: FormDataFake, CustomEvent: class { constructor(type) { this.type = type; } },
        setTimeout: () => {}, console,
    }, { filename: 'public/js/inscriptions/common.js', timeout: 1000 });
    window.iiConfirmerValidationInscription = async () => true;
    return { window, toasts, eventNames };
}

test('POST de validation : URL-encodé, _method PUT, CSRF et encodage conservés', async () => {
    let sent;
    const { window, eventNames } = client(async (url, options) => {
        sent = { url, options };
        return response(200, JSON.stringify({ success: true, inscription_id: 19 }));
    });
    const form = { action: '/esbtp/inscriptions/19/valider', method: 'POST', dataset: {},
        fields: [['_token', 'secret'], ['_method', 'PUT'], ['observations', 'École & élève'], ['parents[0][nom]', 'KOUAMÉ']] };
    assert.equal(await window.iiSoumettreValidationInscription(form, '/aperçu'), true);
    assert.equal(sent.options.headers['Content-Type'], 'application/x-www-form-urlencoded;charset=UTF-8');
    assert.equal(sent.options.headers['X-CSRF-TOKEN'], 'csrf-opaque');
    assert.equal(sent.options.credentials, 'same-origin');
    assert.equal(sent.options.body.get('_method'), 'PUT');
    assert.equal(sent.options.body.get('observations'), 'École & élève');
    assert.equal(sent.options.body.get('parents[0][nom]'), 'KOUAMÉ');
    assert.deepEqual(eventNames, ['inscription:validated']);
    assert.equal(form.dataset.validationBusy, undefined);
});

test('ne transmet jamais [object File] ; fichier réel garde multipart, fichier vide omis', () => {
    const { window } = client(async () => response(200, '{}'));
    const vide = window.iiCorpsFormulaireInscription({
        fields: [['photo', { name: '', size: 0 }], ['nom', 'TEST']],
    });
    assert.equal(vide.body instanceof URLSearchParams, true);
    assert.equal(vide.body.has('photo'), false);
    const reel = window.iiCorpsFormulaireInscription({
        fields: [['photo', { name: 'photo.png', size: 124 }], ['nom', 'TEST']],
    });
    assert.equal(reel.body instanceof FormDataFake, true);
    assert.equal(reel.headers['Content-Type'], undefined);
});

test('200 HTML, vide, JSON non-objet ou success absent ne valident jamais', async () => {
    for (const body of ['<html>login</html>', '', 'null', '[]', '{}', '{"success":false}']) {
        const { window, toasts } = client(async () => response(200, body));
        const ok = await window.iiSoumettreValidationInscription({
            action: '/validation', method: 'POST', dataset: {}, fields: [['_method', 'PUT']],
        }, '/aperçu');
        assert.equal(ok, false);
        assert.equal(toasts.some(x => x.kind === 'success'), false);
    }
});

test('403 HTML indique un blocage possible en amont avec référence sans PII', async () => {
    const { window } = client(async () => response(403, '<html>Accès refusé</html>', {
        'edge-request-id': 'edge-abc.123',
    }));
    await assert.rejects(() => window.iiLireReponseInscription(
        response(403, '<html>Accès refusé</html>', { 'edge-request-id': 'edge-abc.123' }), 'success',
    ), e => e.statut === 403 && /blocage possible en amont/i.test(e.message) && /edge-abc.123/.test(e.message));
});

test('403 et 422 JSON restent des refus métier ; 419, 429 et 500 ont un diagnostic', async () => {
    const { window } = client(async () => response(200, '{}'));
    for (const [code, body, snippet] of [
        [403, '{"message":"Permission refusée"}', 'Permission refusée'],
        [422, '{"message":"Paiement encore en attente"}', 'Paiement encore en attente'],
        [419, '', '419'],
        [429, '', '429'],
        [500, '{"message":"SQLSTATE secret : mot de passe"}', '500'],
    ]) {
        await assert.rejects(() => window.iiLireReponseInscription(response(code, body), 'success'), e => {
            assert.equal(e.statut, code);
            assert.match(e.message, new RegExp(snippet));
            if (code === 500) assert.doesNotMatch(e.message, /SQLSTATE|mot de passe/);
            return true;
        });
    }
});

test('aucun envoi si l’aperçu métier interdit la validation', async () => {
    let mutation = 0;
    const { window } = client(async () => {
        mutation++;
        return response(200, '{"eligible":false,"message":"Versement en attente"}');
    });
    window.iiConfirmerValidationInscription = async () => false;
    assert.equal(await window.iiSoumettreValidationInscription({
        action: '/validation', method: 'POST', dataset: {}, fields: [],
    }, '/aperçu'), false);
    assert.equal(mutation, 0);
});

test('les trois parcours AJAX de validation et le lot utilisent un parseur strict', () => {
    const show = fs.readFileSync(path.resolve(__dirname, '../../resources/views/esbtp/inscriptions/show.blade.php'), 'utf8');
    const admin = fs.readFileSync(path.resolve(__dirname, '../../resources/views/esbtp/inscriptions/administration.blade.php'), 'utf8');
    assert.match(show, /iiCorpsFormulaireInscription\(form\)/);
    assert.match(show, /iiLireReponseInscription\(response, 'success'\)/);
    assert.match(admin, /new URLSearchParams\(\)/);
    assert.match(admin, /iiLireReponseInscription\(response, 'success'\)/);
    const list = fs.readFileSync(path.resolve(__dirname, '../../public/js/inscriptions/index.js'), 'utf8');
    const create = fs.readFileSync(path.resolve(__dirname, '../../resources/views/esbtp/inscriptions/create.blade.php'), 'utf8');
    assert.match(list, /iiLireReponseInscription\(r, 'success'\)/);
    assert.match(list, /new URLSearchParams\(\)/);
    assert.match(admin, /async function handleInscriptionValidation[\s\S]*?iiLireReponseInscription\(response, 'success'\)/);
    assert.match(create, /form\.enctype = hasFile \? 'multipart\/form-data' : 'application\/x-www-form-urlencoded'/);
});


test('les notifications partagées affichent les réponses serveur en texte, jamais en HTML', () => {
    const makeNode = () => ({
        style: {}, children: [], textContent: '', innerHTML: '',
        appendChild(node) { this.children.push(node); },
        addEventListener() {},
        querySelector(selector) {
            if (selector === 'span') return this.span ||= { textContent: '' };
            if (selector === 'button') return { addEventListener() {} };
            return null;
        },
    });
    const document = {
        getElementById: () => null,
        createElement: () => makeNode(),
        head: makeNode(), body: makeNode(),
        addEventListener() {},
    };
    const window = {};
    vm.runInNewContext(common, {
        window, document, setTimeout() {}, console,
        URLSearchParams, FormData: FormDataFake,
    }, { filename: 'public/js/inscriptions/common.js', timeout: 1000 });

    const payload = '<img src=x onerror=alert(1)><b>Réponse</b>';
    window.showToast(payload, 'error');
    const container = document.body.children[0];
    const toast = container.children[0];
    assert.ok(container && toast);
    assert.equal(toast.querySelector('span').textContent, payload);
    assert.doesNotMatch(toast.innerHTML, /<img|<b>/);

    const index = fs.readFileSync(
        path.resolve(__dirname, '../../public/js/inscriptions/index.js'), 'utf8'
    );
    assert.match(index, /toast\\.querySelector\\('span'\\)\\.textContent = String\\(message\\)/);
    assert.doesNotMatch(index, /String\\(message\\)\\.replace\\(\\/\\[<>\\]\\/g, ''\\)/);
});
