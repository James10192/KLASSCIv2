@extends('layouts.app')

@section('title', 'Planning LMD')

@push('styles')
<style>
    .lp-page { padding: 1rem 0; }
    .lp-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
    .lp-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .lp-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lp-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
    .lp-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .lp-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .lp-loading .lp-kpis, .lp-loading .lp-content-area { opacity: .55; pointer-events: none; }
    .lp-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; transition: opacity .2s ease; }
    .lp-kpi { flex: 1; min-width: 140px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; }
    .lp-kpi-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem; }
    .lp-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
    .lp-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
    .lp-filters { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(15,23,42,.04); position: relative; }
    .lp-filters-row { display: flex; gap: .75rem; flex-wrap: wrap; align-items: flex-end; }
    .lp-filter-group { flex: 1 1 220px; min-width: 200px; display: flex; flex-direction: column; }
    .lp-filter-label { display: block; font-size: .68rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .35rem; }
    .lp-filter-group .au-select, .lp-filter-group .au-select-trigger { width: 100%; }
    .lp-spinner { position: absolute; top: 1rem; right: 1.25rem; width: 18px; height: 18px; border: 2px solid #e2e8f0; border-top-color: #0453cb; border-radius: 50%; animation: lp-spin .7s linear infinite; }
    @@keyframes lp-spin { to { transform: rotate(360deg); } }
    .lp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); overflow: hidden; }
    .lp-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .lp-card-title { display: flex; align-items: center; gap: .75rem; font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0; }
    .lp-card-title-icon { width: 32px; height: 32px; border-radius: 9px; background: linear-gradient(135deg, #0453cb, #3b7ddb); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .85rem; }
    .lp-card-meta { font-size: .8rem; color: #64748b; }
    .lp-table { width: 100%; border-collapse: collapse; }
    .lp-table th { padding: .65rem 1rem; text-align: left; font-size: .68rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .lp-table th.lp-th-num { text-align: right; }
    .lp-ue-row { background: #f8fafc; cursor: pointer; }
    .lp-ue-row:hover { background: #f1f5f9; }
    .lp-ue-row td { padding: .85rem 1rem; font-weight: 600; color: #0f172a; border-bottom: 1px solid #e2e8f0; }
    .lp-ue-caret { display: inline-block; width: 1rem; text-align: center; color: #0453cb; transition: transform .2s ease; }
    .lp-ue-caret-open { transform: rotate(90deg); }
    .lp-ue-code { font-family: 'SF Mono', Consolas, monospace; font-size: .78rem; background: rgba(4,83,203,.08); color: #0453cb; padding: .15rem .5rem; border-radius: 6px; margin-right: .5rem; }
    .lp-ue-code-virtual { background: rgba(100,116,139,.1); color: #64748b; font-style: italic; font-family: inherit; }
    .lp-ecue-row td { padding: .65rem 1rem; font-size: .87rem; color: #334155; border-bottom: 1px solid #f1f5f9; background: #fff; }
    .lp-ecue-indent { padding-left: 2.5rem !important; }
    .lp-ecue-code { font-family: 'SF Mono', Consolas, monospace; font-size: .76rem; color: #64748b; margin-right: .5rem; }
    .lp-volume { font-variant-numeric: tabular-nums; text-align: right; color: #1e293b; font-weight: 500; }
    .lp-volume-zero { color: #cbd5e1; }
    .lp-volume-total { font-weight: 700; color: #0453cb; }
    .lp-no-planif { font-size: .72rem; color: #94a3b8; font-style: italic; }
    .lp-type-chip { display: inline-block; padding: .15rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.15); }
    .lp-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 3rem 2rem; text-align: center; }
    .lp-empty-icon { width: 64px; height: 64px; border-radius: 16px; background: rgba(4,83,203,.08); color: #0453cb; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
    .lp-empty h3 { font-size: 1.15rem; font-weight: 600; color: #1e293b; margin: 0 0 .5rem; }
    .lp-empty p { color: #64748b; font-size: .9rem; margin: 0 0 1.25rem; max-width: 480px; margin-left: auto; margin-right: auto; }
    .lp-empty-cta { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem 1.1rem; background: #0453cb; color: #fff; font-size: .85rem; font-weight: 600; border-radius: 10px; text-decoration: none; }
    .lp-empty-cta:hover { background: #033a8e; color: #fff; }
</style>
@endpush

@section('content')
<div class="lp-page"
     x-data="lpPlanning"
     :class="loading ? 'lp-loading' : ''"
     @lpm:saved.window="fetchPartial()">
    <div class="lp-hero">
        <div class="lp-hero-top">
            <div class="lp-hero-left">
                <div class="lp-hero-icon"><i class="fas fa-sitemap"></i></div>
                <div>
                    <h1>Planning LMD</h1>
                    <p>Maquette pédagogique UE / ECUE par parcours et semestre</p>
                </div>
            </div>
        </div>
        <div class="lp-kpis" id="lpKpis">
            @include('esbtp.lmd.planning._kpis')
        </div>
    </div>

    <div class="lp-filters">
        <template x-if="loading"><div class="lp-spinner"></div></template>
        <div class="lp-filters-row">
            <div class="lp-filter-group">
                <label class="lp-filter-label">Parcours</label>
                <x-au-select
                    name="parcours_id"
                    icon="fa-route"
                    placeholder="Tous les parcours"
                    :value="$filters['parcours_id']"
                    :searchable="$parcours->count() > 8"
                    :options="$parcours->mapWithKeys(fn ($p) => [$p->id => $p->label_complet])->all()"
                    x-on:change="reload($event.target.value, 'parcours_id')" />
            </div>
            <div class="lp-filter-group">
                <label class="lp-filter-label">Niveau</label>
                <x-au-select
                    name="niveau_id"
                    icon="fa-layer-group"
                    placeholder="Tous niveaux"
                    :value="$filters['niveau_id']"
                    :options="$niveaux->mapWithKeys(fn ($n) => [$n->id => $n->name])->all()"
                    x-on:change="reload($event.target.value, 'niveau_id')" />
            </div>
            <div class="lp-filter-group">
                <label class="lp-filter-label">Semestre</label>
                <x-au-select
                    name="semestre"
                    icon="fa-calendar-alt"
                    placeholder="Tous semestres"
                    :value="$filters['semestre']"
                    :options="collect($semestres)->mapWithKeys(fn ($s) => [$s => 'Semestre ' . $s])->all()"
                    x-on:change="reload($event.target.value, 'semestre')" />
            </div>
        </div>
    </div>

    <div class="lp-content-area" id="lpContent">
        @include('esbtp.lmd.planning._listing')
    </div>
</div>

@include('esbtp.lmd.planning._link_ue_modal')
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lpPlanning', () => ({
        loading: false,
        filters: {
            parcours_id: @json($filters['parcours_id']),
            niveau_id: @json($filters['niveau_id']),
            semestre: @json($filters['semestre']),
        },
        partialUrl: @json(route('esbtp.lmd.planning.partial')),
        pageUrl: @json(route('esbtp.lmd.planning.index')),

        async reload(value, key) {
            this.filters[key] = value || null;
            await this.fetchPartial();
            this.syncUrl();
        },

        async fetchPartial() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => {
                    if (v !== null && v !== '') params.append(k, v);
                });
                const resp = await fetch(this.partialUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const json = await resp.json();
                document.getElementById('lpKpis').innerHTML = json.kpis || '';
                document.getElementById('lpContent').innerHTML = json.listing || '';
            } catch (e) {
                console.error('Planning fetch failed:', e);
            } finally {
                this.loading = false;
            }
        },

        syncUrl() {
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([k, v]) => {
                if (v !== null && v !== '') params.append(k, v);
            });
            const newUrl = this.pageUrl + (params.toString() ? '?' + params : '');
            history.replaceState({}, '', newUrl);
        },
    }));
});

// Toggle expand/collapse via event delegation — survives AJAX innerHTML replace
document.addEventListener('click', function (e) {
    const row = e.target.closest('.js-ue-row');
    if (!row) return;
    const container = row.closest('#lpContent');
    if (!container) return;
    const idx = row.dataset.idx;
    const caret = row.querySelector('.js-ue-caret');
    const ecues = container.querySelectorAll('.js-ecue-row[data-parent-idx="' + idx + '"]');
    const isOpen = caret.classList.toggle('lp-ue-caret-open');
    ecues.forEach(function (tr) { tr.style.display = isOpen ? '' : 'none'; });
});
</script>
@endpush
