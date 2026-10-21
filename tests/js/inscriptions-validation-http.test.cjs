'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const code = fs.readFileSync(path.resolve(__dirname, '../../public/js/inscriptions/common.js'), 'utf8');
class FormDataDeTest {
    constructor(form) { this.champs = form.champs || []; }
    *[Symbol.iterator]() { yield* this.champs; }
}
function reponse(status, body, headers = {}) {
    return { status, ok: status >= 200 && status < 300, text: async () => body,
        headers: { get: nom => headers[nom.toLowerCase()] ?? null } };
}
function creer(repondre) {
    const toasts = [], events = [];
    const document = {
        getElementById: () => null,
        addEventListener() {},
        createElement: () => ({ style: {}, textContent: '', classList: { add() {}, remove() {} } }),
        head: { appendChild() {} },
    };
    const window = { dispatchEvent: e => events.push(e) };
    vm.runInNewContext(code, { window, document, fetch: repondre,
        URLSearchParams, FormData: FormDataDeTest, console,
        CustomEvent: class { constructor(type, opts) { this.type = type; this.detail = opts.detail; } },
    }, { filename: 'public/js/inscriptions/common.js', timeout: 1000 });
    window.showToast = (message, type) => toasts.push({ message, type });
    return { window, toasts, events };
}
function formulaire() {
    return { action: '/esbtp/inscriptions/42/valider', method: 'POST', dataset: {},
        champs: [['_token', 'csrf-test'], ['_method', 'PUT'],
            ['observations', 'Élève & tuteur'], ['redirect_to', 'etudiant']] };
}

test('POST URL-encodé préserve CSRF, _method PUT, UTF-8 et le contexte', async () => {
    let requete;
    const { window } = creer(async (url, opts) => {
        requete = { url, opts };
        return reponse(200, JSON.stringify({ success: true, inscription_id: 42 }));
    });
    assert.equal((await window.iiEnvoyerFormulaireInscription(formulaire())).success, true);
    assert.equal(requete.opts.method, 'POST');
    assert.equal(requete.opts.credentials, 'same-origin');
    assert.equal(requete.opts.headers['Content-Type'], 'application/x-www-form-urlencoded;charset=UTF-8');
    assert.equal(requete.opts.body.get('_token'), 'csrf-test');
    assert.equal(requete.opts.body.get('_method'), 'PUT');
    assert.equal(requete.opts.body.get('observations'), 'Élève & tuteur');
    assert.equal(requete.opts.body.get('redirect_to'), 'etudiant');
});
test('les fichiers ne sont jamais convertis silencieusement en texte', async () => {
    const { window } = creer(async () => { throw Error('Envoi interdit'); });
    const form = formulaire(); form.champs.push(['photo', { name: 'portrait.png' }]);
    await assert.rejects(() => window.iiEnvoyerFormulaireInscription(form), /fichier/);
});
test('200 HTML, vide, JSON invalide, sans success ou sans inscription ID ne valident rien', async () => {
    for (const corps of ['<html>error</html>', '', '{invalide', '{}',
        '{"success":true}', '{"success":true,"inscription_id":0}']) {
        const { window } = creer(async () => reponse(200, corps));
        await assert.rejects(() => window.iiEnvoyerFormulaireInscription(formulaire()));
    }
});
test('403 HTML est qualifié de possible refus HTTP amont, avec référence filtrée', async () => {
    const { window } = creer(async () => reponse(403, '<html>Forbidden</html>', {
        'edge-request-id': 'abc<script>alert(1)</script>' }));
    await assert.rejects(() => window.iiEnvoyerFormulaireInscription(formulaire()), e => {
        assert.equal(e.statut, 403); assert.match(e.message, /passerelle HTTP/);
        assert.doesNotMatch(e.message, /</); return true;
    });
});
test('403/419/422/429/500 JSON sont distingués du refus proxy', async () => {
    for (const code of [403, 419, 422, 429, 500]) {
        const { window } = creer(async () => reponse(code,
            JSON.stringify({ success: false, message: 'Message Laravel ' + code })));
        await assert.rejects(() => window.iiEnvoyerFormulaireInscription(formulaire()), e => {
            assert.equal(e.statut, code); assert.equal(e.message, 'Message Laravel ' + code);
            return true;
        });
    }
});
test('échec métier ne met pas la fiche en succès', async () => {
    const { window, events, toasts } = creer(async () =>
        reponse(200, '{"success":false,"message":"Paiement en attente"}'));
    window.iiConfirmerValidationInscription = async () => true;
    assert.equal(await window.iiSoumettreValidationInscription(formulaire(), '/preview'), false);
    assert.equal(events.length, 0); assert.match(toasts[0].message, /Paiement en attente/);
});
test('une panne de rafraîchissement ne masque pas une validation réellement confirmée', async () => {
    const { window, events, toasts } = creer(async () =>
        reponse(200, '{"success":true,"inscription_id":42}'));
    window.iiConfirmerValidationInscription = async () => true;
    assert.equal(await window.iiSoumettreValidationInscription(formulaire(), '/preview',
        () => { throw Error('rafraîchissement perdu'); }), true);
    assert.equal(events[0].type, 'inscription:validated');
    assert.equal(toasts.filter(x => x.type === 'error').length, 0);
});
test('fiche inscription : même contrat ; création complète : photo et multipart conservés', () => {
    const detail = fs.readFileSync(path.resolve(__dirname,
        '../../resources/views/esbtp/inscriptions/show.blade.php'), 'utf8');
    const creation = fs.readFileSync(path.resolve(__dirname,
        '../../resources/views/esbtp/inscriptions/create.blade.php'), 'utf8');
    assert.match(detail, /window\.iiEnvoyerFormulaireInscription\(form\)/);
    assert.match(detail, /window\.iiLireReponseInscription\(response\)/);
    assert.match(creation, /enctype="multipart\/form-data"/);
    assert.match(creation, /type="file"/);
    assert.ok(creation.includes('form.enctype = avecFichier'));
    assert.ok(creation.includes('application/x-www-form-urlencoded'));
});

test('liste et administration : les validations groupées et aperçus restent URL-encodés', () => {
    const index = fs.readFileSync(path.resolve(__dirname, '../../public/js/inscriptions/index.js'), 'utf8');
    const admin = fs.readFileSync(path.resolve(__dirname,
        '../../resources/views/esbtp/inscriptions/administration.blade.php'), 'utf8');
    assert.ok(index.includes('body: new URLSearchParams(formData)'));
    assert.ok(admin.includes('body: new URLSearchParams(previewPayload)'));
    assert.equal((admin.match(/body: new URLSearchParams\\(formData\\)/g) || []).length, 2);
    assert.ok(admin.includes('window.iiLireReponseInscription(response)'));
});

test('les modales caisse, validation de paiement et classes restent URL-encodées', () => {
    const index = fs.readFileSync(path.resolve(__dirname, '../../public/js/inscriptions/index.js'), 'utf8');
    const admin = fs.readFileSync(path.resolve(__dirname,
        '../../resources/views/esbtp/inscriptions/administration.blade.php'), 'utf8');
    assert.ok(index.includes('body: new URLSearchParams(new FormData(this))'));
    assert.equal((admin.match(/body: new URLSearchParams\\(formData\\)/g) || []).length, 6);
    assert.ok(admin.includes("credentials: 'same-origin'"));
});


