{{--
    Suivi des notes dans la fenêtre des notes LMD. Inclus DANS le script de
    la page (lmd/notes/index) : il lit currentClasseId, currentClasseData et
    le filtre de période de cette page.
--}}
// ══ Suivi des notes ══
const anneeSuivi = @json($anneeCourante->id ?? null);
let lmdSuiviSilencieux = false;
let lmdSuiviMinuteur = null;

// Contexte minimal pour ecrire dans LA MEME planification que /lmd/planning.
// Pas de seconde table/configuration : le dialogue rapide appelle le PATCH
// canonique de la planification académique.
const lmdPlanningEditable = @json(auth()->user()?->can('lmd.planning.edit') ?? false);
const lmdPlanningPartialUrl = @json(route('esbtp.lmd.planning.partial'));
const lmdPlanningClasses = @json($classes->mapWithKeys(fn ($classe) => [(string) $classe->id => [
    'parcours_id' => $classe->parcours_id,
    'filiere_id' => $classe->filiere_id,
    'niveau_id' => $classe->niveau_etude_id,
    'annee_universitaire_id' => $anneeCourante?->id,
]])->all());
let lmdTeacherPromptedKey = null;

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

function lmdEvalPlanningOptions() {
    const semestre = Number(document.getElementById('evalPeriode')?.value || 0);
    const base = lmdPlanningClasses[String(currentClasseId)] || null;
    if (!base || !currentMatiereId || !semestre) return null;

    return {
        ecueId: Number(currentMatiereId),
        label: currentMatiereName || ('ECUE #' + currentMatiereId),
        context: { ...base, semestre },
    };
}

async function lmdEvalPlanningTeacher(options) {
    if (!options?.context?.parcours_id) return { id: null, name: '' };
    const params = new URLSearchParams({
        parcours_id: options.context.parcours_id,
        niveau_id: options.context.niveau_id || '',
        semestre: options.context.semestre,
    });
    try {
        const response = await fetch(lmdPlanningPartialUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return { id: null, name: '' };
        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        const row = doc.querySelector('[data-lpe-ecue-id="' + String(options.ecueId).replace(/"/g, '') + '"]');
        return row ? {
            id: row.dataset.lpeTeacherId ? Number(row.dataset.lpeTeacherId) : null,
            name: row.dataset.lpeTeacherName || '',
        } : { id: null, name: '' };
    } catch (_) {
        return { id: null, name: '' };
    }
}

function lmdEvalTeacherStatus() {
    let status = document.getElementById('evalPlanningTeacherStatus');
    if (status) return status;
    const context = document.getElementById('evalModalContext');
    if (!context) return null;
    status = document.createElement('div');
    status.id = 'evalPlanningTeacherStatus';
    status.style.cssText = 'margin-top:.55rem;padding:.55rem .7rem;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;font-size:.76rem;color:#475569;display:none;align-items:center;gap:.5rem;flex-wrap:wrap;';
    context.insertAdjacentElement('afterend', status);
    return status;
}

async function lmdEvalRefreshTeacher(autoPrompt = false) {
    if (!lmdPlanningEditable || !window.lmdTeacherQuick) return;
    const options = lmdEvalPlanningOptions();
    const status = lmdEvalTeacherStatus();
    if (!options) {
        if (status) status.style.display = 'none';
        return;
    }

    const key = [currentClasseId, options.ecueId, options.context.semestre].join(':');
    const current = await lmdEvalPlanningTeacher(options);
    if (!document.getElementById('lmdEvalCreateModal')?.classList.contains('show')) return;

    if (status) {
        status.style.display = 'flex';
        status.innerHTML = current.id
            ? '<i class="fas fa-user-check" style="color:#047857"></i><strong>Planning :</strong> ' + escHtml(current.name || ('enseignant #' + current.id)) + ' <button type="button" id="evalPlanningTeacherEdit" style="border:0;background:transparent;color:#0453cb;font-weight:700;padding:0;">Modifier</button>'
            : '<i class="fas fa-user-clock" style="color:#b45309"></i><strong>Planning :</strong> aucun enseignant principal <button type="button" id="evalPlanningTeacherEdit" style="border:0;background:transparent;color:#0453cb;font-weight:700;padding:0;">Assigner maintenant</button>';
        document.getElementById('evalPlanningTeacherEdit')?.addEventListener('click', () => window.lmdTeacherQuick.open({
            ...options,
            currentTeacherId: current.id,
            currentTeacherName: current.name,
            onSaved: () => lmdEvalRefreshTeacher(false),
        }));
    }

    if (!current.id && autoPrompt && lmdTeacherPromptedKey !== key) {
        lmdTeacherPromptedKey = key;
        window.lmdTeacherQuick.open({
            ...options,
            currentTeacherId: null,
            currentTeacherName: '',
            onSaved: () => lmdEvalRefreshTeacher(false),
        });
    }
}

// La fenêtre de création reste celle de la page. On lui ajoute seulement la
// vérification du planning : aucun reload, aucune seconde configuration.
const lmdOpenEvalCreateModal = openEvalCreateModal;
openEvalCreateModal = function () {
    lmdTeacherPromptedKey = null;
    lmdOpenEvalCreateModal();
    queueMicrotask(() => lmdEvalRefreshTeacher(true));
};

document.addEventListener('change', function (event) {
    if (event.target?.id === 'evalPeriode') {
        lmdEvalRefreshTeacher(true);
    }
});

window.addEventListener('lmd:teacher-planning-updated', function (event) {
    if (Number(event.detail?.ecueId) === Number(currentMatiereId)) {
        lmdEvalRefreshTeacher(false);
    }
});

document.addEventListener('DOMContentLoaded', async function () {
    const params = new URLSearchParams(window.location.search);
    const classe = parseInt(params.get('classe') || '', 10);
    if (!classe) return;
    await openNotesModal(classe, '');
    const ecue = parseInt(params.get('ecue') || '', 10);
    if (ecue) lmdOuvrirElement(ecue);
});
