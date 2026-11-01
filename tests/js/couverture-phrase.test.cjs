'use strict';
// Exécute la vraie fabrique Alpine du suivi : la phrase affichée est ce que l'école lit.
const test = require('node:test');
const assert = require('node:assert');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '../../resources/views/esbtp/partials/_couverture-notes-script.blade.php'),
    'utf8',
);
const script = source.match(/<script>([\s\S]*)<\/script>/)[1];
const window = {};
vm.runInNewContext(script, { window, document: {}, console });

function phrase(subjects) {
    const suivi = window.couvertureNotes({});
    suivi.donnees = { summary: { state: 'complete' }, subjects };
    return suivi.phrase();
}

test('annonce tout reçu seulement sans matière en attente', () => {
    assert.strictEqual(phrase([{ statut: 'complete' }]), 'Toutes les notes attendues sont reçues.');
});

test('ne dit pas tout reçu quand des matières sont sans évaluation', () => {
    assert.match(phrase([{ statut: 'complete' }, { statut: 'non_evaluee' }, { statut: 'non_evaluee' }]), /2 matière\(s\) de la maquette sans évaluation/);
});

test('ne dit pas tout reçu quand une évaluation est programmée', () => {
    assert.match(phrase([{ statut: 'complete' }, { statut: 'programmee' }]), /1 matière\(s\) avec une évaluation programmée/);
});

test('annonce les deux causes ensemble', () => {
    const texte = phrase([{ statut: 'non_evaluee' }, { statut: 'programmee' }]);
    assert.match(texte, /sans évaluation/);
    assert.match(texte, /programmée/);
});
