{{--
    Suivi des notes dans la fenêtre des notes LMD. Inclus DANS le script de
    la page (lmd/notes/index) : il lit currentClasseId, currentClasseData et
    le filtre de période de cette page.
--}}
// ══ Suivi des notes ══
const anneeSuivi = @json($anneeCourante->id ?? null);
let lmdSuiviSilencieux = false;
let lmdSuiviMinuteur = null;

// Le panneau suit la classe et le semestre affichés.
function lmdSuiviContexte(semestre) {
    if (lmdSuiviSilencieux || !currentClasseId) return;
    window.dispatchEvent(new CustomEvent('couverture:contexte', { detail: {
        classe_id: currentClasseId, annee_universitaire_id: anneeSuivi, periode: 'semestre' + semestre,
    } }));
}

// Une note enregistrée change le compte : le panneau se recalcule, une fois
// la série de saisies terminée plutôt qu'à chaque note.
function lmdSuiviApresSauvegarde() {
    clearTimeout(lmdSuiviMinuteur);
    lmdSuiviMinuteur = setTimeout(() => window.dispatchEvent(new CustomEvent('couverture:invalider', {
        detail: { classe_id: Number(currentClasseId), annee_universitaire_id: anneeSuivi },
    })), 900);
}

// Une période choisie dans le panneau devient celle de la grille, sans que
// la grille ne la renvoie au panneau.
window.addEventListener('couverture:periode-change', function (event) {
    const filtre = document.getElementById('periodeFilter');
    const periode = String(event.detail?.periode || '');
    const valeur = periode === 'annuel' ? 'all' : periode.replace('semestre', '');
    if (!filtre || ![...filtre.options].some(o => o.value === valeur)) return;
    filtre.value = valeur;
    lmdSuiviSilencieux = true;
    try { filtre.dispatchEvent(new Event('change')); } finally { lmdSuiviSilencieux = false; }
});

// Ouvre la grille d'un élément de la classe affichée : depuis le suivi,
// ou depuis un lien direct (?classe=…&ecue=…).
function lmdOuvrirElement(matiereId) {
    const ueMap = currentClasseData?._ueMap || {};
    const ue = Object.values(ueMap).find(u => u.ecues.some(e => Number(e.id) === Number(matiereId)));
    if (!ue) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'warning', message: "Cet élément n'est pas dans la maquette de la classe affichée." } }));
        return false;
    }
    const ueSelect = document.getElementById('ueSelect');
    ueSelect.value = ue.code || '';
    ueSelect.dispatchEvent(new Event('change'));
    const ecueSelect = document.getElementById('ecueSelect');
    ecueSelect.value = String(matiereId);
    ecueSelect.dispatchEvent(new Event('change'));
    document.querySelector('#modalNotes .ln-modal-toolbar')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return true;
}

// Le bandeau de suivi appelle ce point d'entrée : la page sait ouvrir la
// saisie elle-même, elle ne rouvre pas sa propre adresse.
window.nmOpenCoverageSaisie = function (matiere) {
    lmdOuvrirElement(matiere.id);
};

document.addEventListener('DOMContentLoaded', async function () {
    const params = new URLSearchParams(window.location.search);
    const classe = parseInt(params.get('classe') || '', 10);
    if (!classe) return;
    await openNotesModal(classe, '');
    const ecue = parseInt(params.get('ecue') || '', 10);
    if (ecue) lmdOuvrirElement(ecue);
});
