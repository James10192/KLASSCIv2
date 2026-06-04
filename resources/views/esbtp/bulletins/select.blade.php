@extends('layouts.app')

@section('title', 'Générer des bulletins — KLASSCI')

@push('styles')
<style>
:root {
    --bus-primary: #0453cb;
    --bus-primary-d: #033a8e;
    --bus-secondary: #5e91de;
    --bus-accent: #3b7ddb;
    --bus-text: #1e293b;
    --bus-muted: #64748b;
    --bus-surface: #f8fafc;
    --bus-border: #e2e8f0;
    --bus-success: #10b981;
    --bus-warning: #f59e0b;
    --bus-danger: #dc2626;
}

/* ── HERO ───────────────────────────────────────────── */
.bus-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
}
.bus-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.bus-hero-left { display: flex; align-items: center; gap: 1rem; }
.bus-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, .15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.bus-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.bus-hero p { color: rgba(255, 255, 255, .72); font-size: .88rem; margin: .15rem 0 0; }

.bus-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border: 1px solid transparent;
    border-radius: 10px;
    padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, border-color .15s, color .15s;
}
.bus-btn--glass {
    background: rgba(255, 255, 255, .15);
    color: #fff;
    border-color: rgba(255, 255, 255, .2);
}
.bus-btn--glass:hover { background: rgba(255, 255, 255, .22); color: #fff; }

/* ── ACTIONS GRID ───────────────────────────────────── */
.bus-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
}

.bus-action-card {
    background: #fff;
    border: 1px solid var(--bus-border);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow .2s;
}
.bus-action-card:hover {
    box-shadow: 0 8px 30px rgba(4, 83, 203, .08), 0 2px 8px rgba(15, 23, 42, .04);
}
.bus-action-card__head {
    padding: 1.1rem 1.25rem .85rem;
    border-bottom: 1px solid var(--bus-border);
    background: linear-gradient(135deg, rgba(4, 83, 203, .03), rgba(59, 125, 219, .05));
}
.bus-action-card__head-row { display: flex; align-items: center; gap: .75rem; }
.bus-action-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--bus-primary), var(--bus-accent));
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(4, 83, 203, .22);
}
.bus-action-title { font-size: 1rem; font-weight: 700; color: var(--bus-text); margin: 0; }
.bus-action-sub { font-size: .76rem; color: var(--bus-muted); margin-top: .25rem; line-height: 1.4; }
.bus-action-card__body {
    padding: 1.1rem 1.25rem 1.25rem;
    display: flex; flex-direction: column; gap: .85rem;
    flex: 1;
}
.bus-field { display: flex; flex-direction: column; gap: .35rem; }
.bus-field-label {
    font-size: .68rem; font-weight: 700;
    color: var(--bus-muted);
    text-transform: uppercase;
    letter-spacing: .4px;
    display: flex; align-items: center; gap: .35rem;
}
.bus-field-step {
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: rgba(4, 83, 203, .12); color: var(--bus-primary);
    font-size: .65rem; font-weight: 700;
}
.bus-field--disabled { opacity: .45; pointer-events: none; }
.bus-helper { font-size: .72rem; color: var(--bus-muted); line-height: 1.4; }
.bus-checkbox-row {
    display: flex; align-items: center; gap: .55rem;
    padding: .6rem .75rem;
    background: var(--bus-surface);
    border: 1px solid var(--bus-border);
    border-radius: 8px;
}
.bus-checkbox-row label { font-size: .82rem; color: var(--bus-text); cursor: pointer; margin: 0; }

.bus-submit {
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    width: 100%;
    padding: .65rem 1rem;
    border-radius: 10px;
    border: none;
    font-size: .85rem; font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, var(--bus-primary), var(--bus-accent));
    cursor: pointer;
    transition: filter .15s;
    box-shadow: 0 4px 14px rgba(4, 83, 203, .25);
}
.bus-submit:hover { filter: brightness(1.08); }
.bus-submit:disabled { opacity: .55; cursor: wait; filter: grayscale(.3); }
.bus-submit--info { background: linear-gradient(135deg, #0ea5e9, #3b7ddb); }
.bus-submit--success { background: linear-gradient(135deg, #10b981, #0ea5e9); }

.bus-tag {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .15rem .5rem;
    border-radius: 5px;
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
}
.bus-tag--read   { background: rgba(4, 83, 203, .1); color: var(--bus-primary); }
.bus-tag--preview { background: rgba(14, 165, 233, .1); color: #0369a1; }
.bus-tag--gen { background: rgba(16, 185, 129, .1); color: var(--bus-success); }

.bus-status {
    display: flex; align-items: center; gap: .35rem;
    font-size: .72rem;
    color: var(--bus-muted);
    margin-top: -.35rem;
}
.bus-status i { font-size: .65rem; }

/* Toast container */
.bus-toast-stack {
    position: fixed;
    bottom: 1.25rem; right: 1.25rem;
    display: flex; flex-direction: column; gap: .5rem;
    z-index: 99999;
    max-width: 380px;
    pointer-events: none;
}
.bus-toast {
    pointer-events: auto;
    display: flex; align-items: center; gap: .55rem;
    background: #fff;
    border: 1px solid var(--bus-border);
    border-radius: 10px;
    padding: .65rem .85rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
    font-size: .85rem;
    color: var(--bus-text);
}
.bus-toast--success { border-left: 4px solid var(--bus-success); }
.bus-toast--success > i { color: var(--bus-success); }
.bus-toast--error { border-left: 4px solid var(--bus-danger); }
.bus-toast--error > i { color: var(--bus-danger); }
.bus-toast--info { border-left: 4px solid var(--bus-primary); }
.bus-toast--info > i { color: var(--bus-primary); }
.bus-toast-close {
    background: transparent; border: none; cursor: pointer;
    color: var(--bus-muted); padding: 0; margin-left: auto;
}

@media (max-width: 768px) {
    .bus-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .bus-hero h1 { font-size: 1.2rem; }
}
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="busSelect()" x-init="init()">

    {{-- ══ HERO ═══════════════════════════════════════════ --}}
    <div class="bus-hero">
        <div class="bus-hero-top">
            <div class="bus-hero-left">
                <div class="bus-hero-icon"><i class="fas fa-magic-wand-sparkles"></i></div>
                <div>
                    <h1>Générer des bulletins</h1>
                    <p>Choisissez d'abord la classe, puis l'année universitaire, puis l'action à effectuer</p>
                </div>
            </div>
            <div>
                <a href="{{ route('esbtp.bulletins.index') }}" class="bus-btn bus-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour aux bulletins
                </a>
            </div>
        </div>
    </div>

    <div class="bus-grid">
        {{-- ── CARD 1 : Consulter ─────────────────────── --}}
        <div class="bus-action-card" x-data="busCard({ kind: 'consult' })">
            <div class="bus-action-card__head">
                <div class="bus-action-card__head-row">
                    <div class="bus-action-icon" style="background:linear-gradient(135deg, #0453cb, #3b7ddb);">
                        <i class="fas fa-magnifying-glass"></i>
                    </div>
                    <div>
                        <h3 class="bus-action-title">Consulter</h3>
                        <span class="bus-tag bus-tag--read">Lecture</span>
                    </div>
                </div>
                <p class="bus-action-sub">Accéder à la page Résultats pour voir les bulletins existants d'une classe.</p>
            </div>
            <form class="bus-action-card__body" @submit.prevent="submit()">
                <div class="bus-field">
                    <label class="bus-field-label"><span class="bus-field-step">1</span>Classe</label>
                    <x-au-select
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()"
                        placeholder="Choisir la classe…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        x-model="form.classe_id" />
                </div>
                <div class="bus-field" :class="!form.classe_id ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">2</span>Année universitaire</label>
                    <x-au-select
                        :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => ($a->annee_debut.' - '.$a->annee_fin)])->toArray()"
                        placeholder="Choisir l'année…"
                        icon="fa-calendar"
                        x-model="form.annee_universitaire_id" />
                </div>
                <div class="bus-field" :class="(!form.classe_id || !form.annee_universitaire_id) ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">3</span>Période</label>
                    <x-au-select
                        :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2']"
                        placeholder="Choisir la période…"
                        icon="fa-layer-group"
                        x-model="form.semestre" />
                </div>
                <button type="submit" class="bus-submit" :disabled="busy || !canSubmit()">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                    <span x-text="busy ? 'Chargement…' : 'Consulter les bulletins'"></span>
                </button>
            </form>
        </div>

        {{-- ── CARD 2 : Prévisualiser ───────────────────── --}}
        <div class="bus-action-card" x-data="busCard({ kind: 'preview' })">
            <div class="bus-action-card__head">
                <div class="bus-action-card__head-row">
                    <div class="bus-action-icon" style="background:linear-gradient(135deg, #0ea5e9, #3b7ddb);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <h3 class="bus-action-title">Prévisualiser</h3>
                        <span class="bus-tag bus-tag--preview">PDF live</span>
                    </div>
                </div>
                <p class="bus-action-sub">Aperçu PDF d'un bulletin individuel pour vérification avant publication.</p>
            </div>
            <form class="bus-action-card__body" @submit.prevent="submit()">
                <div class="bus-field">
                    <label class="bus-field-label"><span class="bus-field-step">1</span>Classe</label>
                    <x-au-select
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()"
                        placeholder="Choisir la classe…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        x-model="form.classe_id" />
                </div>
                <div class="bus-field" :class="!form.classe_id ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">2</span>Année universitaire</label>
                    <x-au-select
                        :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => ($a->annee_debut.' - '.$a->annee_fin)])->toArray()"
                        placeholder="Choisir l'année…"
                        icon="fa-calendar"
                        x-model="form.annee_universitaire_id" />
                </div>
                <div class="bus-field" :class="(!form.classe_id || !form.annee_universitaire_id) ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label">
                        <span class="bus-field-step">3</span>Étudiant
                        <span x-show="loadingStudents" x-cloak style="margin-left:auto; font-size:.7rem; color:var(--bus-primary);">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </span>
                    </label>
                    <x-au-select
                        x-model="form.etudiant_id"
                        :searchable="true"
                        placeholder="Choisir l'étudiant…"
                        icon="fa-user-graduate"
                        :options="[]" />
                    <p class="bus-status" x-show="form.classe_id && form.annee_universitaire_id && !loadingStudents">
                        <i class="fas fa-users"></i>
                        <span x-text="students.length + ' étudiant' + (students.length > 1 ? 's' : '') + ' inscrit' + (students.length > 1 ? 's' : '') + ' cette année dans cette classe'"></span>
                    </p>
                </div>
                <button type="submit" class="bus-submit bus-submit--info" :disabled="busy || !canSubmit()">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-eye'"></i>
                    <span x-text="busy ? 'Ouverture…' : 'Prévisualiser le bulletin'"></span>
                </button>
            </form>
        </div>

        {{-- ── CARD 3 : Générer ─────────────────────────── --}}
        <div class="bus-action-card" x-data="busCard({ kind: 'generate' })">
            <div class="bus-action-card__head">
                <div class="bus-action-card__head-row">
                    <div class="bus-action-icon" style="background:linear-gradient(135deg, #10b981, #0ea5e9);">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 class="bus-action-title">Générer en masse</h3>
                        <span class="bus-tag bus-tag--gen">Toute la classe</span>
                    </div>
                </div>
                <p class="bus-action-sub">Créer ou recalculer les bulletins de tous les étudiants d'une classe pour une période donnée.</p>
            </div>
            <form class="bus-action-card__body" @submit.prevent="submit()">
                <div class="bus-field">
                    <label class="bus-field-label"><span class="bus-field-step">1</span>Classe</label>
                    <x-au-select
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()"
                        placeholder="Choisir la classe…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        x-model="form.classe_id" />
                </div>
                <div class="bus-field" :class="!form.classe_id ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label">
                        <span class="bus-field-step">2</span>Année universitaire
                        <span x-show="loadingStudents" x-cloak style="margin-left:auto; font-size:.7rem; color:var(--bus-primary);">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </label>
                    <x-au-select
                        :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => ($a->annee_debut.' - '.$a->annee_fin)])->toArray()"
                        placeholder="Choisir l'année…"
                        icon="fa-calendar"
                        x-model="form.annee_universitaire_id" />
                    <p class="bus-status" x-show="form.classe_id && form.annee_universitaire_id && !loadingStudents">
                        <i class="fas fa-users"></i>
                        <span x-text="students.length + ' étudiant' + (students.length > 1 ? 's' : '') + ' seront concernés'"></span>
                    </p>
                </div>
                <div class="bus-field" :class="(!form.classe_id || !form.annee_universitaire_id) ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">3</span>Période</label>
                    <x-au-select
                        :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2']"
                        placeholder="Choisir la période…"
                        icon="fa-layer-group"
                        x-model="form.periode" />
                </div>
                <div class="bus-checkbox-row">
                    <input type="checkbox" id="bus-recalc" x-model="form.recalculer" :value="1">
                    <label for="bus-recalc">Recalculer si déjà existants</label>
                </div>
                <button type="submit" class="bus-submit bus-submit--success" :disabled="busy || !canSubmit()">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-file-pdf'"></i>
                    <span x-text="busy ? 'Génération en cours…' : 'Générer les bulletins'"></span>
                </button>
            </form>
        </div>
    </div>

    {{-- Toast stack --}}
    <div class="bus-toast-stack" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div class="bus-toast" :class="'bus-toast--' + t.type" x-transition.opacity>
                <i :class="t.type === 'success' ? 'fas fa-circle-check' : (t.type === 'error' ? 'fas fa-circle-exclamation' : 'fas fa-circle-info')"></i>
                <span x-text="t.message"></span>
                <button class="bus-toast-close" @click="removeToast(t.id)"><i class="fas fa-xmark"></i></button>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function busSelect() {
    return {
        toasts: [],
        toastSeq: 0,
        init() {
            window.addEventListener('toast', (ev) => this.pushToast(ev.detail));
        },
        pushToast(detail) {
            const id = ++this.toastSeq;
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => this.removeToast(id), 5000);
        },
        removeToast(id) {
            const idx = this.toasts.findIndex(t => t.id === id);
            if (idx !== -1) this.toasts.splice(idx, 1);
        },
    };
}

if (typeof window.busCard !== 'function') {
window.busCard = function (cfg) {
    return {
        kind: cfg.kind,
        busy: false,
        loadingStudents: false,
        students: [],
        form: {
            classe_id: '',
            annee_universitaire_id: '',
            etudiant_id: '',
            semestre: '',
            periode: '',
            recalculer: false,
        },

        init() {
            this.$watch('form.classe_id', () => { this.form.etudiant_id = ''; this.fetchStudents(); });
            this.$watch('form.annee_universitaire_id', () => { this.form.etudiant_id = ''; this.fetchStudents(); });
        },

        canSubmit() {
            if (!this.form.classe_id || !this.form.annee_universitaire_id) return false;
            if (this.kind === 'consult')  return !!this.form.semestre;
            if (this.kind === 'preview')  return !!this.form.etudiant_id;
            if (this.kind === 'generate') return !!this.form.periode;
            return false;
        },

        async fetchStudents() {
            if (!this.form.classe_id || !this.form.annee_universitaire_id) {
                this.students = [];
                return;
            }
            // Seules les cards qui ont besoin de la liste d'étudiants la chargent
            if (this.kind !== 'preview' && this.kind !== 'generate') return;
            this.loadingStudents = true;
            try {
                const baseUrl = `{{ route('esbtp.classes.etudiants', ['classe' => '__ID__']) }}`.replace('__ID__', this.form.classe_id);
                const url = baseUrl + '?annee_universitaire_id=' + encodeURIComponent(this.form.annee_universitaire_id);
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const data = await res.json();
                this.students = (data.etudiants || []).map(e => ({
                    value: e.id,
                    label: `${e.nom || ''} ${e.prenoms || e.prenom || ''}`.trim() + ` (${e.matricule || ''})`,
                }));
                if (this.kind === 'preview') this.injectStudentsIntoSelect();
            } catch (err) {
                this.notify('error', 'Erreur lors du chargement des étudiants : ' + err.message);
                this.students = [];
            } finally {
                this.loadingStudents = false;
            }
        },

        injectStudentsIntoSelect() {
            // Le composant au-select est rendu côté serveur avec un native <select> caché.
            // Pour preview card, on injecte les options étudiants dynamiquement.
            const card = this.$root || this.$el;
            const nativeSel = card.querySelector('select[name=""], select.au-select-native');
            // Trouve le native du 3e au-select (étudiant)
            const wrappers = card.querySelectorAll('.au-select');
            if (wrappers.length < 3) return;
            const studentWrapper = wrappers[2];
            const native = studentWrapper.querySelector('select.au-select-native');
            if (!native) return;
            const currentValue = this.form.etudiant_id;
            let html = '<option value="">' + (this.students.length ? 'Sélectionner…' : 'Aucun étudiant') + '</option>';
            this.students.forEach(s => {
                const sel = String(s.value) === String(currentValue) ? ' selected' : '';
                html += `<option value="${s.value}"${sel}>${s.label}</option>`;
            });
            native.innerHTML = html;
            // Force resync : le composant Alpine au-select écoute change sur le native
            native.value = currentValue || '';
            native.dispatchEvent(new Event('change', { bubbles: true }));
        },

        async submit() {
            if (!this.canSubmit()) {
                this.notify('error', 'Veuillez remplir tous les champs requis.');
                return;
            }
            this.busy = true;
            try {
                if (this.kind === 'consult') {
                    const params = new URLSearchParams();
                    params.set('classe_id', this.form.classe_id);
                    params.set('annee_universitaire_id', this.form.annee_universitaire_id);
                    params.set('semestre', this.form.semestre);
                    window.location.href = `{{ route('esbtp.resultats.index') }}?` + params.toString();
                    return;
                }
                if (this.kind === 'preview') {
                    const params = new URLSearchParams();
                    params.set('annee', this.form.annee_universitaire_id);
                    params.set('classe', this.form.classe_id);
                    params.set('etudiant', this.form.etudiant_id);
                    window.location.href = `{{ route('esbtp.bulletins.preview') }}?` + params.toString();
                    return;
                }
                if (this.kind === 'generate') {
                    const fd = new FormData();
                    fd.append('classe_id', this.form.classe_id);
                    fd.append('annee_universitaire_id', this.form.annee_universitaire_id);
                    fd.append('periode', this.form.periode);
                    if (this.form.recalculer) fd.append('recalculer', '1');
                    const res = await fetch(`{{ route('esbtp.bulletins.generer-classe') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    if (res.status === 422) {
                        const data = await res.json();
                        const msg = Object.values(data.errors || {}).flat().join(' • ') || data.message || 'Validation refusée';
                        this.notify('error', msg);
                        return;
                    }
                    if (!res.ok && !res.redirected) {
                        throw new Error(`Erreur HTTP ${res.status}`);
                    }
                    this.notify('success', `Bulletins générés pour la classe. Redirection…`);
                    setTimeout(() => {
                        window.location.href = `{{ route('esbtp.bulletins.index') }}?classe_id=${this.form.classe_id}&annee_universitaire_id=${this.form.annee_universitaire_id}&periode_id=${this.form.periode}`;
                    }, 1100);
                }
            } catch (err) {
                this.notify('error', err.message || 'Erreur inattendue.');
            } finally {
                if (this.kind === 'generate') this.busy = false;
                else setTimeout(() => { this.busy = false; }, 400);
            }
        },

        notify(type, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
        },
    };
};
}
</script>
@endpush
@endsection
