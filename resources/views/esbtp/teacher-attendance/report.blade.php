@extends('layouts.app')

@section('title', 'Heures des enseignants — KLASSCI')

@push('styles')
<style>
    .tar-wrap { max-width: 1280px; margin: 0 auto; }

    /* ── Hero ─────────────────────────────────────────────── */
    .tar-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 1.9rem 2.25rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .tar-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .tar-hero-left { display: flex; align-items: center; gap: 1rem; }
    .tar-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff; flex-shrink: 0;
    }
    .tar-hero h1 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; }
    .tar-hero p { color: rgba(255,255,255,.72); font-size: .85rem; margin: .15rem 0 0; }
    .tar-hero-period {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
        border-radius: 9px; padding: .45rem .8rem; font-size: .8rem; font-weight: 600; color: #fff;
    }

    /* ── KPIs ─────────────────────────────────────────────── */
    .tar-kpis { display: flex; gap: .75rem; margin-top: 1.4rem; flex-wrap: wrap; }
    .tar-kpi {
        flex: 1; min-width: 150px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .85rem 1rem; display: flex; align-items: center; gap: .7rem;
    }
    .tar-kpi-ico {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center;
        font-size: .95rem; color: #fff;
    }
    .tar-kpi--warn .tar-kpi-ico { background: rgba(245,158,11,.28); }
    .tar-kpi-val { font-size: 1.3rem; font-weight: 700; color: #fff; line-height: 1; }
    .tar-kpi-sub { font-size: .85rem; font-weight: 600; color: rgba(255,255,255,.6); }
    .tar-kpi-lbl { font-size: .68rem; color: rgba(255,255,255,.65); margin-top: .2rem; }

    /* ── Filtres ──────────────────────────────────────────── */
    .tar-filters {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .tar-filters-row { display: flex; flex-wrap: wrap; gap: .7rem; align-items: flex-end; }
    .tar-presets { display: inline-flex; background: #f1f5f9; border-radius: 9px; padding: .2rem; gap: .2rem; }
    .tar-preset {
        border: none; background: transparent; cursor: pointer;
        padding: .42rem .8rem; border-radius: 7px; font-size: .78rem; font-weight: 600; color: #475569;
        transition: background .15s, color .15s;
    }
    .tar-preset:hover:not(.tar-preset--active) { background: rgba(4,83,203,.06); color: #0453cb; }
    .tar-preset--active { background: #0453cb; color: #fff; }
    .tar-date-fields { display: none; gap: .5rem; align-items: flex-end; }
    .tar-date-fields.show { display: flex; }
    .tar-field { display: flex; flex-direction: column; gap: .25rem; min-width: 150px; }
    .tar-field-lbl { font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
    .tar-input {
        border: 1px solid #e2e8f0; border-radius: 9px; padding: .5rem .7rem;
        font-size: .82rem; color: #1e293b; background: #fff;
    }
    .tar-input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
    .tar-filters-grow { flex: 1; min-width: 170px; }
    .tar-spin { color: #0453cb; font-size: .85rem; display: none; align-items: center; gap: .4rem; }
    .tar-spin.show { display: inline-flex; }

    /* ── Layout principal ─────────────────────────────────── */
    .tar-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 1.25rem; align-items: start; }
    .tar-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    .tar-panel-head { display: flex; align-items: center; gap: .65rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .tar-panel-ico {
        width: 36px; height: 36px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .85rem;
    }
    .tar-panel-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
    .tar-panel-sub { font-size: .73rem; color: #94a3b8; }
    .tar-panel-body { padding: 1.1rem 1.25rem; }

    /* ── Cartes enseignants ───────────────────────────────── */
    .tar-tcards { display: flex; flex-direction: column; gap: .85rem; max-height: 70vh; overflow-y: auto; padding-right: .3rem; }
    .tar-tcard { border: 1px solid #e9eef5; border-radius: 12px; padding: .9rem 1rem; transition: box-shadow .2s, border-color .2s; }
    .tar-tcard:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4,83,203,.07); }
    .tar-tcard-head { display: flex; align-items: center; gap: .7rem; }
    .tar-tcard-avatar {
        width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, #0453cb, #5e91de); color: #fff;
        display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .95rem;
    }
    .tar-tcard-id { flex: 1; min-width: 0; }
    .tar-tcard-name { font-size: .9rem; font-weight: 700; color: #0453cb; text-decoration: none; }
    .tar-tcard-name:hover { text-decoration: underline; }
    .tar-tcard-sub { font-size: .72rem; color: #64748b; margin-top: .1rem; }
    .tar-tcard-warn {
        font-size: .68rem; font-weight: 700; color: #92400e;
        background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.28);
        padding: .15rem .45rem; border-radius: 6px; white-space: nowrap;
    }
    .tar-tcard-bar { height: 7px; border-radius: 5px; background: #eef2f7; overflow: hidden; margin-top: .75rem; }
    .tar-tcard-bar-fill { height: 100%; border-radius: 5px; transition: width .3s; }
    .tar-tcard-bar-meta { display: flex; justify-content: space-between; font-size: .68rem; color: #64748b; margin-top: .3rem; }
    .tar-tcard-types { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .tar-type-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 700; padding: .25rem .5rem; border-radius: 7px;
    }
    .tar-type-code { font-size: .65rem; opacity: .85; }
    .tar-type-nf { opacity: .5; }

    /* ── Liste séances ────────────────────────────────────── */
    .tar-seances { max-height: 70vh; overflow-y: auto; }
    .tar-seance-row {
        display: flex; align-items: center; gap: .85rem;
        padding: .75rem .35rem; border-bottom: 1px solid #f1f5f9;
        position: relative; overflow: hidden;
    }
    /* Travelling-light feedback sur marquage présent/absent/retard */
    .tar-rowhl { position: absolute; top: 0; left: -80%; width: 160%; height: 100%; opacity: 0; pointer-events: none; transform: translateX(-65%) skewX(-12deg); z-index: 5; background: linear-gradient(90deg, rgba(16,185,129,0) 0%, rgba(16,185,129,.55) 50%, rgba(16,185,129,0) 100%); }
    .tar-rowhl--absent { background: linear-gradient(90deg, rgba(220,38,38,0) 0%, rgba(220,38,38,.55) 50%, rgba(220,38,38,0) 100%); }
    .tar-rowhl--late { background: linear-gradient(90deg, rgba(245,158,11,0) 0%, rgba(245,158,11,.55) 50%, rgba(245,158,11,0) 100%); }
    .tar-rowhl.animate { animation: tar-rowhl-move 3.2s ease-out forwards; }
    @keyframes tar-rowhl-move { 0% { opacity: 0; transform: translateX(-65%) skewX(-12deg); } 18% { opacity: .92; } 55% { opacity: .72; } 100% { opacity: 0; transform: translateX(115%) skewX(-12deg); } }
    .tar-seance-row:last-child { border-bottom: none; }
    .tar-seance-date { width: 44px; text-align: center; flex-shrink: 0; }
    .tar-seance-day { font-size: 1.05rem; font-weight: 800; color: #0453cb; line-height: 1; }
    .tar-seance-mon { font-size: .62rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
    .tar-seance-main { flex: 1; min-width: 0; }
    .tar-seance-top { display: flex; align-items: center; gap: .5rem; }
    .tar-seance-matiere { font-size: .85rem; font-weight: 700; color: #1e293b; }
    .tar-seance-typechip { display: inline-flex; align-items: center; gap: .25rem; font-size: .62rem; font-weight: 700; padding: .1rem .4rem; border-radius: 5px; }
    .tar-seance-meta { display: flex; flex-wrap: wrap; gap: .65rem; font-size: .72rem; color: #64748b; margin-top: .2rem; }
    .tar-seance-meta i { color: #94a3b8; margin-right: .15rem; }
    .tar-seance-duree { text-align: center; flex-shrink: 0; width: 56px; }
    .tar-seance-duree-val { font-size: .95rem; font-weight: 800; color: #0f172a; }
    .tar-seance-duree-lbl { font-size: .6rem; color: #94a3b8; text-transform: uppercase; }
    .tar-seance-statut { display: flex; flex-direction: column; align-items: flex-end; gap: .25rem; flex-shrink: 0; min-width: 88px; }
    .tar-statut-badge { font-size: .68rem; font-weight: 700; padding: .18rem .5rem; border-radius: 6px; white-space: nowrap; }
    .tar-warn { font-size: .62rem; font-weight: 700; white-space: nowrap; }
    .tar-warn--late { color: #92400e; }
    .tar-warn--miss { color: #b91c1c; }
    .tar-seance-actions { display: flex; gap: .3rem; margin-top: .3rem; }
    .tar-act { width: 26px; height: 26px; border-radius: 7px; border: 1px solid; background: #fff; cursor: pointer; font-size: .68rem; display: flex; align-items: center; justify-content: center; transition: all .15s; }
    .tar-act--ok { color: #059669; border-color: rgba(16,185,129,.4); }
    .tar-act--ok:hover { background: #10b981; color: #fff; }
    .tar-act--late { color: #b45309; border-color: rgba(245,158,11,.4); }
    .tar-act--late:hover { background: #f59e0b; color: #fff; }
    .tar-act--no { color: #dc2626; border-color: rgba(220,38,38,.4); }
    .tar-act--no:hover { background: #dc2626; color: #fff; }
    .tar-seance-view { display: flex; gap: .3rem; margin-top: .3rem; }
    .tar-vw { width: 26px; height: 26px; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer; font-size: .66rem; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all .15s; }
    .tar-vw:hover { border-color: #0453cb; color: #0453cb; background: rgba(4,83,203,.05); }

    .tar-sentinel { padding: 1rem; text-align: center; color: #94a3b8; font-size: .8rem; }
    .tar-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    .tar-empty i { font-size: 2rem; margin-bottom: .6rem; display: block; color: #cbd5e1; }

    @media (max-width: 992px) {
        .tar-grid { grid-template-columns: 1fr; }
        .tar-tcards, .tar-seances { max-height: none; }
    }
    @media (max-width: 768px) {
        .tar-hero { padding: 1.4rem 1.25rem; }
        .tar-filters-row { flex-direction: column; align-items: stretch; }
    }
</style>
@endpush

@section('content')
<div class="tar-wrap"
     x-data="reportPage()"
     data-has-more="{{ $paginator->hasMorePages() ? '1' : '0' }}"
     data-next-page="2"
     data-url="{{ route('esbtp.teacher-attendance.report.data') }}">

    {{-- Hero --}}
    <div class="tar-hero">
        <div class="tar-hero-top">
            <div class="tar-hero-left">
                <div class="tar-hero-icon"><i class="fas fa-business-time"></i></div>
                <div>
                    <h1>Heures des enseignants</h1>
                    <p>Suivi des heures réellement effectuées (CM / TD / TP) — {{ $anneeEnCours->name ?? 'année courante' }}</p>
                </div>
            </div>
            <div class="tar-hero-period">
                <i class="fas fa-calendar-day"></i>
                <span x-text="periodeLabel">{{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="tar-kpis" id="tarKpis">
            @include('esbtp.teacher-attendance.partials._report_kpis', ['report' => $report])
        </div>
    </div>

    {{-- Filtres --}}
    <form class="tar-filters" id="tarFilters" @submit.prevent="applyFilters()" @change="applyFilters()">
        <div class="tar-filters-row">
            <div class="tar-field">
                <span class="tar-field-lbl">Période</span>
                <div class="tar-presets">
                    <button type="button" class="tar-preset" :class="filters.preset === 'month' ? 'tar-preset--active' : ''" @click="setPreset('month')">Ce mois</button>
                    <button type="button" class="tar-preset" :class="filters.preset === 'year' ? 'tar-preset--active' : ''" @click="setPreset('year')">Année</button>
                    <button type="button" class="tar-preset" :class="filters.preset === 'custom' ? 'tar-preset--active' : ''" @click="setPreset('custom')">Plage</button>
                </div>
            </div>
            <input type="hidden" name="preset" :value="filters.preset">

            <div class="tar-date-fields" :class="filters.preset === 'custom' ? 'show' : ''">
                <div class="tar-field">
                    <span class="tar-field-lbl">Du</span>
                    <input type="date" name="from" class="tar-input" x-model="filters.from">
                </div>
                <div class="tar-field">
                    <span class="tar-field-lbl">Au</span>
                    <input type="date" name="to" class="tar-input" x-model="filters.to">
                </div>
            </div>

            <div class="tar-field tar-filters-grow">
                <span class="tar-field-lbl">Enseignant</span>
                <x-au-select name="teacher_id" :value="$filtres['teacher_id'] ?? ''" placeholder="Tous les enseignants"
                    icon="fa-user-tie" :searchable="true"
                    :options="$teachers->pluck('name','id')->toArray()" />
            </div>
            <div class="tar-field tar-filters-grow">
                <span class="tar-field-lbl">Classe</span>
                <x-au-select name="classe_id" :value="$filtres['classe_id'] ?? ''" placeholder="Toutes les classes"
                    icon="fa-users" :searchable="true"
                    :options="$classes->pluck('name','id')->toArray()" />
            </div>
            <div class="tar-field tar-filters-grow">
                <span class="tar-field-lbl">Matière</span>
                <x-au-select name="matiere_id" :value="$filtres['matiere_id'] ?? ''" placeholder="Toutes les matières"
                    icon="fa-book" :searchable="true"
                    :options="$matieres->pluck('name','id')->toArray()" />
            </div>
            <div class="tar-field">
                <span class="tar-field-lbl">Type</span>
                <x-au-select name="type_seance" :value="$filtres['type_seance'] ?? ''" placeholder="Tous types"
                    icon="fa-shapes" :options="$typeOptions" />
            </div>
            <div class="tar-field">
                <span class="tar-field-lbl">&nbsp;</span>
                <span class="tar-spin" :class="loading ? 'show' : ''"><i class="fas fa-circle-notch fa-spin"></i> Mise à jour…</span>
            </div>
        </div>
    </form>

    {{-- Contenu --}}
    <div class="tar-grid">
        {{-- Cartes enseignants (baromètre heures) --}}
        <div class="tar-panel">
            <div class="tar-panel-head">
                <div class="tar-panel-ico"><i class="fas fa-gauge-high"></i></div>
                <div>
                    <div class="tar-panel-title">Baromètre par enseignant</div>
                    <div class="tar-panel-sub">Heures réalisées vs planifiées, ventilées CM / TD / TP</div>
                </div>
            </div>
            <div class="tar-panel-body">
                <div class="tar-tcards" id="tarTeachers">
                    @include('esbtp.teacher-attendance.partials._report_teachers', ['report' => $report])
                </div>
            </div>
        </div>

        {{-- Liste séances (infinity scroll) --}}
        <div class="tar-panel">
            <div class="tar-panel-head">
                <div class="tar-panel-ico"><i class="fas fa-list-check"></i></div>
                <div>
                    <div class="tar-panel-title">Détail des séances</div>
                    <div class="tar-panel-sub"><span x-text="totalLabel">{{ $paginator->total() }} séance(s)</span> · durées précises</div>
                </div>
            </div>
            <div class="tar-panel-body" style="padding-top:.4rem;padding-bottom:.4rem;">
                <div class="tar-seances" id="tarSeances">
                    @if($rows->isEmpty())
                        <div class="tar-empty"><i class="fas fa-calendar-xmark"></i><p>Aucune séance sur cette période.</p></div>
                    @else
                        @include('esbtp.teacher-attendance.partials._report_seances', ['rows' => $rows])
                    @endif
                </div>
                <div class="tar-sentinel" id="tarSentinel" x-show="hasMore">
                    <i class="fas fa-circle-notch fa-spin" x-show="loadingMore"></i>
                    <span x-show="!loadingMore">Faites défiler pour charger plus…</span>
                </div>
            </div>
        </div>
    </div>

    @include('esbtp.teacher-attendance.partials._seance_modal')
</div>
@endsection

@push('scripts')
<script>
function reportPage() {
    return {
        filters: {
            preset: @json($preset),
            from: @json($from->toDateString()),
            to: @json($to->toDateString()),
        },
        loading: false,
        loadingMore: false,
        hasMore: false,
        nextPage: 2,
        url: '',
        periodeLabel: @json($from->format('d/m/Y') . ' → ' . $to->format('d/m/Y')),
        totalLabel: @json($paginator->total() . ' séance(s)'),

        init() {
            this.url = this.$root.dataset.url;
            this.hasMore = this.$root.dataset.hasMore === '1';
            this.nextPage = parseInt(this.$root.dataset.nextPage, 10) || 2;
            this.observeSentinel();
            // Après un marquage : rafraîchir KPIs + baromètre seulement (préserver la
            // ligne animée — le statut de la ligne est mis à jour en place côté handler).
            window.addEventListener('seance:status-updated', () => this.refreshAggregates());
        },

        async refreshAggregates() {
            try {
                const params = this.buildParams({ mode: 'filter' });
                const res = await fetch(this.url + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();
                document.getElementById('tarKpis').innerHTML = data.kpis_html;
                document.getElementById('tarTeachers').innerHTML = data.teachers_html;
                // NE PAS toucher #tarSeances : la ligne animée reste intacte.
            } catch (e) { /* silencieux */ }
        },

        setPreset(p) {
            this.filters.preset = p;
            if (p !== 'custom') {
                this.applyFilters();
            }
        },

        buildParams(extra) {
            const form = document.getElementById('tarFilters');
            const params = new URLSearchParams();
            // Préset + dates lus depuis l'état Alpine (le DOM :value peut être stale).
            params.set('preset', this.filters.preset);
            if (this.filters.preset === 'custom') {
                params.set('from', this.filters.from);
                params.set('to', this.filters.to);
            }
            // Filtres au-select (selects natifs cachés, déjà à jour dans le DOM).
            ['teacher_id', 'classe_id', 'matiere_id', 'type_seance'].forEach(function (n) {
                const el = form.querySelector('[name="' + n + '"]');
                if (el && el.value) params.set(n, el.value);
            });
            Object.entries(extra || {}).forEach(function (e) { params.set(e[0], e[1]); });
            return params;
        },

        async applyFilters() {
            this.loading = true;
            try {
                const params = this.buildParams({ mode: 'filter' });
                const res = await fetch(this.url + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                document.getElementById('tarKpis').innerHTML = data.kpis_html;
                document.getElementById('tarTeachers').innerHTML = data.teachers_html;
                document.getElementById('tarSeances').innerHTML = data.seances_html
                    || '<div class="tar-empty"><i class="fas fa-calendar-xmark"></i><p>Aucune séance.</p></div>';
                this.hasMore = data.has_more;
                this.nextPage = data.next_page;
                this.periodeLabel = data.periode.from + ' → ' + data.periode.to;
                this.totalLabel = data.total + ' séance(s)';
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.loading = false;
            }
        },

        async loadMore() {
            if (!this.hasMore || this.loadingMore) return;
            this.loadingMore = true;
            try {
                const params = this.buildParams({ mode: 'scroll', page: this.nextPage });
                const res = await fetch(this.url + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                document.getElementById('tarSeances').insertAdjacentHTML('beforeend', data.seances_html);
                this.hasMore = data.has_more;
                this.nextPage = data.next_page;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.loadingMore = false;
            }
        },

        observeSentinel() {
            const sentinel = document.getElementById('tarSentinel');
            if (!sentinel || !('IntersectionObserver' in window)) return;
            const obs = new IntersectionObserver((entries) => {
                entries.forEach((e) => { if (e.isIntersecting) this.loadMore(); });
            }, { rootMargin: '120px' });
            obs.observe(sentinel);
        },
    };
}

@include('esbtp.teacher-attendance.partials._mark_status_js')
</script>
@endpush
