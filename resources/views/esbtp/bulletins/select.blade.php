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
    transition: box-shadow .2s, transform .15s;
}
.bus-action-card:hover {
    box-shadow: 0 8px 30px rgba(4, 83, 203, .08), 0 2px 8px rgba(15, 23, 42, .04);
}
.bus-action-card__head {
    padding: 1.1rem 1.25rem .85rem;
    border-bottom: 1px solid var(--bus-border);
    background: linear-gradient(135deg, rgba(4, 83, 203, .03), rgba(59, 125, 219, .05));
}
.bus-action-card__head-row {
    display: flex; align-items: center; gap: .75rem;
}
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
.bus-action-title {
    font-size: 1rem; font-weight: 700;
    color: var(--bus-text);
    margin: 0;
}
.bus-action-sub {
    font-size: .76rem; color: var(--bus-muted); margin-top: .25rem;
    line-height: 1.4;
}
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
}
.bus-helper {
    font-size: .72rem;
    color: var(--bus-muted);
    line-height: 1.4;
}
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
    transition: filter .15s, transform .12s;
    box-shadow: 0 4px 14px rgba(4, 83, 203, .25);
}
.bus-submit:hover { filter: brightness(1.08); }
.bus-submit:disabled { opacity: .55; cursor: wait; }
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

.bus-errors {
    padding: .75rem 1rem;
    background: rgba(220, 38, 38, .08);
    border: 1px solid rgba(220, 38, 38, .25);
    color: #7f1d1d;
    border-radius: 10px;
    margin-bottom: 1rem;
    font-size: .82rem;
}
.bus-errors ul { margin: 0; padding-left: 1.25rem; }

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
                    <p>Consulter, prévisualiser ou générer en masse les bulletins d'une classe</p>
                </div>
            </div>
            <div>
                <a href="{{ route('esbtp.bulletins.index') }}" class="bus-btn bus-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour aux bulletins
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="bus-errors">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bus-grid">
        {{-- ── CARD 1 : Consulter ─────────────────────── --}}
        <div class="bus-action-card">
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
            <form action="{{ route('esbtp.resultats.index') }}" method="GET" class="bus-action-card__body">
                <div class="bus-field">
                    <label class="bus-field-label">Année universitaire</label>
                    <x-au-select
                        name="annee_universitaire_id"
                        :value="$anneeActuelle?->id"
                        placeholder="Sélectionner…"
                        icon="fa-calendar"
                        :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => ($a->annee_debut.' - '.$a->annee_fin)])->toArray()" />
                </div>
                <div class="bus-field">
                    <label class="bus-field-label">Classe</label>
                    <x-au-select
                        name="classe_id"
                        placeholder="Sélectionner…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()" />
                </div>
                <div class="bus-field">
                    <label class="bus-field-label">Période</label>
                    <x-au-select
                        name="semestre"
                        placeholder="Sélectionner…"
                        icon="fa-layer-group"
                        :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2']" />
                </div>
                <button type="submit" class="bus-submit">
                    <i class="fas fa-magnifying-glass"></i> Consulter les bulletins
                </button>
            </form>
        </div>

        {{-- ── CARD 2 : Prévisualiser ───────────────────── --}}
        <div class="bus-action-card">
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
            <form action="{{ route('esbtp.bulletins.preview') }}" method="GET" class="bus-action-card__body">
                <div class="bus-field">
                    <label class="bus-field-label">Année universitaire</label>
                    <x-au-select
                        name="annee"
                        :value="$anneeActuelle?->id"
                        placeholder="Sélectionner…"
                        icon="fa-calendar"
                        :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => ($a->annee_debut.' - '.$a->annee_fin)])->toArray()" />
                </div>
                <div class="bus-field">
                    <label class="bus-field-label">Classe</label>
                    <x-au-select
                        name="classe"
                        x-ref="previewClasse"
                        placeholder="Sélectionner…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()" />
                </div>
                <div class="bus-field">
                    <label class="bus-field-label">Étudiant</label>
                    <x-au-select
                        name="etudiant"
                        x-ref="previewEtudiant"
                        placeholder="Choisissez d'abord une classe"
                        icon="fa-user-graduate"
                        :options="[]" />
                </div>
                <button type="submit" class="bus-submit bus-submit--info">
                    <i class="fas fa-eye"></i> Prévisualiser le bulletin
                </button>
            </form>
        </div>

        {{-- ── CARD 3 : Générer ─────────────────────────── --}}
        <div class="bus-action-card">
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
            <form action="{{ route('esbtp.bulletins.generer-classe') }}" method="POST" class="bus-action-card__body">
                @csrf
                <div class="bus-field">
                    <label class="bus-field-label">Année universitaire</label>
                    <x-au-select
                        name="annee_universitaire_id"
                        :value="$anneeActuelle?->id"
                        placeholder="Sélectionner…"
                        icon="fa-calendar"
                        :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => ($a->annee_debut.' - '.$a->annee_fin)])->toArray()" />
                </div>
                <div class="bus-field">
                    <label class="bus-field-label">Classe</label>
                    <x-au-select
                        name="classe_id"
                        placeholder="Sélectionner…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()" />
                </div>
                <div class="bus-field">
                    <label class="bus-field-label">Période</label>
                    <x-au-select
                        name="periode"
                        placeholder="Sélectionner…"
                        icon="fa-layer-group"
                        :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2']" />
                </div>
                <div class="bus-checkbox-row">
                    <input type="checkbox" id="bus-recalc" name="recalculer" value="1">
                    <label for="bus-recalc">Recalculer si déjà existants</label>
                </div>
                <p class="bus-helper">
                    <i class="fas fa-info-circle"></i>
                    Si coché, les bulletins déjà générés seront recalculés depuis les notes courantes.
                </p>
                <button type="submit" class="bus-submit bus-submit--success">
                    <i class="fas fa-file-pdf"></i> Générer les bulletins
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function busSelect() {
    return {
        etudiantsByClasse: {},

        init() {
            // Quand la classe de la card preview change → fetch les étudiants
            window.addEventListener('au-select:changed', (ev) => {
                if (!ev.detail || ev.detail.name !== 'classe') return;
                this.loadEtudiants(ev.detail.value);
            });
            // Fallback : écouter aussi sur le <select> caché interne
            document.addEventListener('change', (ev) => {
                if (ev.target && ev.target.name === 'classe' && ev.target.closest('form')?.action.includes('/bulletins/preview')) {
                    this.loadEtudiants(ev.target.value);
                }
            });
        },

        previewClasseValue() {
            const sel = this.$refs.previewClasse?.querySelector('select[name="classe"]');
            return sel ? sel.value : '';
        },

        async loadEtudiants(classeId) {
            const etudiantTrigger = this.$refs.previewEtudiant;
            if (!etudiantTrigger) return;
            const nativeSel = etudiantTrigger.querySelector('select[name="etudiant"]');
            if (!nativeSel) return;
            // Reset
            nativeSel.innerHTML = '<option value="">Chargement…</option>';
            if (!classeId) {
                nativeSel.innerHTML = '<option value="">Choisissez d\'abord une classe</option>';
                return;
            }
            try {
                const url = `{{ route('esbtp.classes.etudiants', ['classe' => '__ID__']) }}`.replace('__ID__', classeId);
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const data = await res.json();
                const items = data.etudiants || [];
                if (!items.length) {
                    nativeSel.innerHTML = '<option value="">Aucun étudiant dans cette classe</option>';
                    return;
                }
                let html = '<option value="">Sélectionner…</option>';
                items.forEach(e => {
                    const name = `${e.nom || ''} ${e.prenom || e.prenoms || ''}`.trim();
                    html += `<option value="${e.id}">${name} (${e.matricule || ''})</option>`;
                });
                nativeSel.innerHTML = html;
                nativeSel.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (err) {
                nativeSel.innerHTML = '<option value="">Erreur de chargement</option>';
            }
        },

        submitForm(form) {
            // Form GET classique : laisse le browser submit naturellement,
            // mais avec @submit.prevent, on retire les params vides puis submit.
            const data = new FormData(form);
            const params = new URLSearchParams();
            for (const [k, v] of data.entries()) {
                if (v !== '' && v !== null) params.set(k, v);
            }
            window.location.href = form.action + (params.toString() ? '?' + params.toString() : '');
        },
    };
}
</script>
@endpush
@endsection
