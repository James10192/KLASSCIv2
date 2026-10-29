@extends('layouts.app')

@section('title', 'Effectifs par classe - KLASSCI')

@section('content')
<div class="main-content eff-page" x-data="effectifsReport()">
    <x-role-hero
        icon="fa-users-rectangle"
        title="Effectifs par classe"
        subtitle="Rapport par année universitaire, calculé sur les inscriptions actives et les dossiers étudiants créés.">
        @can('reports.generate')
            <x-slot:actions>
                <x-export-modal
                    :preview-url="route('esbtp.rapports.effectifs.pdf-preview')"
                    :pdf-url="route('esbtp.rapports.effectifs.pdf')"
                    button-class="eff-export-button"
                    label="Exporter le rapport" />
            </x-slot:actions>
        @endcan
    </x-role-hero>

    <div class="eff-filter-panel">
        <div class="eff-filter-intro">
            <div><h2><i class="fas fa-sliders" aria-hidden="true"></i> Filtres du rapport</h2><p>Les résultats et indicateurs se mettent à jour automatiquement, sans recharger la page.</p></div>
            <button type="button" class="eff-reset" @click="reset()"><i class="fas fa-rotate-left" aria-hidden="true"></i> Réinitialiser</button>
        </div>
        <form x-ref="filters" @change="schedule(false)" @submit.prevent="schedule(false)" class="eff-form" aria-label="Filtres des effectifs">
            <div class="eff-field eff-field--year">
                <label for="eff-annee">Année universitaire</label>
                <x-au-select name="annee_universitaire_id" id="eff-annee" :value="$filters['annee_universitaire_id']" :options="$annees->map(fn ($an) => ['value' => $an->id, 'label' => $an->name])->all()" placeholder="Choisir une année" label="Année universitaire" :searchable="true" />
            </div>
            <div class="eff-field eff-field--number">
                <label for="eff-min">Minimum d’inscrits</label>
                <input id="eff-min" type="number" name="min_inscrits" value="{{ $filters['min_inscrits'] }}" min="0" max="10000" step="1" inputmode="numeric" @input="schedule(true)" aria-describedby="eff-min-help" />
                <small id="eff-min-help">15 par défaut · 0 inclut les classes vides</small>
            </div>
            <div class="eff-field">
                <label for="eff-nombre">Nombre de classes</label>
                <x-au-select name="nombre_classes" id="eff-nombre" :value="$filters['nombre_classes']" :options="['1' => '1 classe', '5' => '5 premières', '10' => '10 premières', '15' => '15 premières', '20' => '20 premières', '25' => '25 premières', '50' => '50 premières', '100' => '100 premières', '200' => '200 premières', '500' => '500 premières', 'all' => 'Toutes les classes']" placeholder="Nombre de classes" :placeholder-is-first-option="false" label="Nombre de classes" />
            </div>
            <div class="eff-field">
                <label for="eff-sort">Trier par</label>
                <x-au-select name="sort" id="eff-sort" :value="$filters['sort']" :options="['effectif_desc' => 'Effectif décroissant', 'nom_asc' => 'Nom de classe (A–Z)']" placeholder="Trier par" :placeholder-is-first-option="false" label="Trier les classes" />
            </div>
            @if($filieres->isNotEmpty())
                <div class="eff-field">
                    <label for="eff-filiere">Filière / mention</label>
                    <x-au-select name="filiere_id" id="eff-filiere" :value="$filters['filiere_id']" :options="$filieres->map(fn ($f) => ['value' => $f->id, 'label' => $f->name])->all()" placeholder="Toutes les filières / mentions" label="Filière ou mention" :searchable="true" />
                </div>
            @endif
            @if($niveaux->isNotEmpty())
                <div class="eff-field">
                    <label for="eff-niveau">Niveau d’étude</label>
                    <x-au-select name="niveau_id" id="eff-niveau" :value="$filters['niveau_id']" :options="$niveaux->map(fn ($n) => ['value' => $n->id, 'label' => $n->name])->all()" placeholder="Tous les niveaux" label="Niveau d’étude" :searchable="true" />
                </div>
            @endif
        </form>
    </div>

    <div class="eff-feedback" aria-live="polite" aria-atomic="true">
        <div class="eff-loading" x-show="loading" x-cloak role="status"><i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Mise à jour des effectifs…</div>
        <div class="eff-error" x-show="error" x-cloak role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> <span x-text="error"></span> <button type="button" @click="schedule(false)">Réessayer</button></div>
    </div>

    <div x-ref="results" :aria-busy="loading.toString()">
        @include('esbtp.rapports.partials.effectifs-resultats', ['report' => $report])
    </div>
</div>
@endsection

@push('styles')
<style>
.eff-page { padding-bottom: 2rem; }
.eff-export-button { display:inline-flex;align-items:center;gap:.5rem;padding:.73rem 1rem;border:1px solid rgba(255,255,255,.45);border-radius:11px;background:rgba(255,255,255,.14);color:#fff;font-weight:700;cursor:pointer; }
.eff-export-button:hover,.eff-export-button:focus-visible { background:rgba(255,255,255,.27);outline:2px solid #fff;outline-offset:2px; }
.eff-filter-panel,.eff-panel { background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 5px 18px rgba(15,23,42,.04); }
.eff-filter-panel { padding:1.3rem 1.5rem;margin-bottom:1.25rem;position:relative;z-index:2; }
.eff-filter-intro,.eff-panel-heading { display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1.1rem; }
.eff-filter-intro h2,.eff-panel-heading h2 { font-size:1.03rem;font-weight:750;color:#152945;margin:0 0 .25rem; }
.eff-filter-intro p,.eff-panel-heading p { margin:0;color:#64748b;font-size:.82rem;max-width:660px; }
.eff-filter-intro h2 i { color:#2563eb;margin-right:.3rem; }
.eff-reset { background:#f1f5f9;border:1px solid #cbd5e1;color:#334155;border-radius:9px;padding:.56rem .85rem;font-size:.81rem;font-weight:700;cursor:pointer; }
.eff-reset:hover,.eff-reset:focus-visible { background:#e2e8f0;outline:2px solid #2563eb;outline-offset:2px; }
.eff-form { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem; }
.eff-field { display:flex;flex-direction:column;gap:.33rem;min-width:0; }
.eff-field label { color:#334155;font-size:.79rem;font-weight:700; }
.eff-field input { width:100%;min-height:44px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;padding:.55rem .8rem;color:#0f172a;font-size:.89rem; }
.eff-field input:focus-visible { outline:2px solid #2563eb;outline-offset:2px;border-color:#2563eb; }
.eff-field .au-select { width:100%;flex:0 0 auto; }
.eff-field small { font-size:.71rem;color:#64748b; }
.eff-feedback { min-height:0; }
.eff-active { display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;margin:0 0 .8rem;font-size:.73rem;color:#475569; }
.eff-active strong { margin-right:.25rem; }
.eff-filter-chip { padding:.35rem .55rem;border:1px solid #e2e8f0;background:#f8fafc;border-radius:7px; }
.eff-filter-chip span { color:#64748b; }
.eff-loading,.eff-error { padding:.7rem 1rem;margin-bottom:1rem;border-radius:10px;font-size:.84rem;font-weight:600; }
.eff-loading { color:#1d4ed8;background:#eff6ff; }
.eff-error { display:flex;align-items:center;gap:.5rem;background:#fff1f2;color:#991b1b;border:1px solid #fecdd3; }
.eff-error button { margin-left:auto;border:0;background:#be123c;color:#fff;padding:.35rem .7rem;border-radius:7px;cursor:pointer; }
.eff-summary { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.9rem;margin-bottom:1.25rem; }
.eff-stat { display:flex;flex-direction:column;gap:.25rem;padding:1.1rem 1.2rem;background:#f8fbff;border:1px solid #dbeafe;border-radius:14px; }
.eff-stat span { color:#475569;font-size:.78rem;font-weight:700; }
.eff-stat strong { color:#1d4ed8;font-size:1.7rem;line-height:1.2;font-weight:800;font-variant-numeric:tabular-nums; }
.eff-stat small { color:#64748b;font-size:.69rem; }
.eff-panel { padding:1.25rem 1.5rem; }
.eff-count { font-weight:700;font-size:.78rem;background:#eff6ff;color:#1d4ed8;padding:.45rem .7rem;border-radius:50px; }
.eff-table-scroll { overflow-x:auto;max-width:100%; }
.eff-table { width:100%;border-collapse:separate;border-spacing:0;font-size:.86rem; }
.eff-table th { background:#f1f5f9;color:#475569;text-align:left;padding:.82rem 1rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.035em;white-space:nowrap; }
.eff-table td { padding:.8rem 1rem;border-bottom:1px solid #edf2f7;color:#334155; }
.eff-table tbody tr:hover { background:#f8fafc; }
.eff-table td strong { font-weight:750;color:#0f172a; }
.eff-code { display:block;color:#94a3b8;font-size:.7rem;margin-top:.15rem; }
.eff-rank { color:#64748b;font-variant-numeric:tabular-nums; }
.eff-right { text-align:right!important; }
.eff-badge { display:inline-block;min-width:3rem;padding:.33rem .56rem;border-radius:8px;background:#dbeafe;color:#1e40af;font-weight:800;font-variant-numeric:tabular-nums;text-align:center; }
.eff-table-footer { display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;padding-top:1rem; }
.eff-table-footer small { font-size:.75rem;color:#64748b; }
.eff-pages nav { max-width:100%; }
.eff-empty { text-align:center;padding:3.2rem 1rem;color:#64748b; }
.eff-empty i { font-size:2rem;color:#94a3b8;margin-bottom:.7rem; }
.eff-empty h3 { color:#1e293b;font-size:1.07rem;margin-bottom:.3rem; }
.eff-empty p { font-size:.84rem; }
@media(max-width:1050px) { .eff-form { grid-template-columns:repeat(2,minmax(0,1fr)); } .eff-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:600px) { .eff-filter-panel,.eff-panel { padding:1rem; } .eff-form { grid-template-columns:minmax(0,1fr); } .eff-summary { gap:.5rem; } .eff-stat { padding:.85rem; } .eff-stat strong { font-size:1.3rem; } .eff-table { min-width:660px; } }
</style>
@endpush

@push('scripts')
<script>
function effectifsReport() {
    return {
        page: {{ (int) $filters['page'] }},
        loading: false, error: '', sequence: 0, timer: null, controller: null, suppress: false,
        init() {
            window.exportFilters = () => this.filterObject();
            this._onPop = () => this.restoreFromUrl();
            window.addEventListener('popstate', this._onPop);
        },
        destroy() {
            clearTimeout(this.timer);
            this.controller?.abort();
            window.removeEventListener('popstate', this._onPop);
            if (window.exportFilters) delete window.exportFilters;
        },
        filterObject() {
            const params = new FormData(this.$refs.filters);
            const values = Object.fromEntries(params.entries());
            return { ...values, page: this.page };
        },
        schedule(debounce = false, preservePage = false, updateHistory = true) {
            if (this.suppress) return;
            if (!preservePage) this.page = 1;
            this.sequence += 1;
            const requestedSequence = this.sequence;
            clearTimeout(this.timer);
            this.controller?.abort();
            this.loading = true;
            this.error = '';
            this.timer = setTimeout(() => this.load(requestedSequence, updateHistory, debounce), debounce ? 400 : 0);
        },
        async load(requestedSequence, updateHistory, debounce) {
            const params = new URLSearchParams();
            Object.entries(this.filterObject()).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== '') params.set(k, v);
            });
            const queryString = params.toString();
            this.controller = new AbortController();
            try {
                const response = await fetch({{ Illuminate\Support\Js::from(route('esbtp.rapports.effectifs.data')) }} + '?' + queryString, {
                    signal: this.controller.signal,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(response.status === 422 ? 'Vérifiez les filtres saisis.' : 'Le serveur ne peut pas charger les effectifs.');
                const data = await response.json();
                if (requestedSequence !== this.sequence) return;
                if (!data.success || typeof data.html !== 'string') throw new Error('La réponse du serveur est incomplète.');
                this.$refs.results.innerHTML = data.html;
                if (updateHistory) {
                    const url = window.location.pathname + '?' + queryString;
                    window.history[debounce ? 'replaceState' : 'pushState']({}, '', url);
                }
            } catch (error) {
                if (requestedSequence !== this.sequence || error.name === 'AbortError') return;
                this.error = error.message || 'Erreur réseau. Veuillez réessayer.';
            } finally {
                if (requestedSequence === this.sequence) this.loading = false;
            }
        },
        selectValue(name, value) {
            const field = this.$refs.filters.elements.namedItem(name);
            if (!field) return;
            field.value = value;
            field.dispatchEvent(new Event('change', { bubbles: true }));
        },
        reset() {
            this.suppress = true;
            const defaults = {
                annee_universitaire_id: {{ Illuminate\Support\Js::from(\App\Models\ESBTPAnneeUniversitaire::anneeCourante()?->id ?? '') }},
                min_inscrits: '15', nombre_classes: '20', sort: 'effectif_desc', filiere_id: '', niveau_id: '',
            };
            Object.entries(defaults).forEach(([key, value]) => this.selectValue(key, value));
            this.page = 1;
            this.$nextTick(() => { this.suppress = false; this.schedule(false); });
        },
        restoreFromUrl() {
            this.suppress = true;
            const params = new URLSearchParams(window.location.search);
            const defaults = {
                annee_universitaire_id: {{ Illuminate\Support\Js::from(\App\Models\ESBTPAnneeUniversitaire::anneeCourante()?->id ?? '') }},
                min_inscrits: '15', nombre_classes: '20', sort: 'effectif_desc', filiere_id: '', niveau_id: '',
            };
            Object.entries(defaults).forEach(([key, value]) => this.selectValue(key, params.get(key) ?? value));
            this.page = Math.max(1, Number.parseInt(params.get('page') || '1', 10) || 1);
            this.$nextTick(() => { this.suppress = false; this.schedule(false, true, false); });
        },
    };
}
document.addEventListener('click', function (event) {
    const anchor = event.target.closest('.eff-page .eff-pages a[href]');
    if (!anchor) return;
    const page = Number.parseInt(new URL(anchor.href, window.location.origin).searchParams.get('page') || '1', 10);
    if (!Number.isInteger(page) || page < 1) return;
    event.preventDefault();
    const container = anchor.closest('.eff-page');
    const component = window.Alpine?.$data(container);
    if (!component) return;
    component.page = page;
    component.schedule(false, true);
});
</script>
@endpush
