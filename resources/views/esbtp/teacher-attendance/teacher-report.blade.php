@extends('layouts.app')

@section('title', 'Heures de l\'enseignant — KLASSCI')

@push('styles')
<style>
    .tdr-wrap { max-width: 1180px; margin: 0 auto; }

    .tdr-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 1.9rem 2.25rem 1.5rem; color: #fff;
        margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .tdr-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .tdr-hero-left { display: flex; align-items: center; gap: 1rem; }
    .tdr-hero-avatar {
        width: 60px; height: 60px; border-radius: 16px; flex-shrink: 0;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 800; color: #fff;
    }
    .tdr-hero h1 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; }
    .tdr-hero-meta { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .35rem; }
    .tdr-hero-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
        border-radius: 7px; padding: .2rem .55rem; font-size: .73rem; font-weight: 600; color: rgba(255,255,255,.9);
    }
    .tdr-back {
        display: inline-flex; align-items: center; gap: .4rem; text-decoration: none;
        background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.2);
        border-radius: 10px; padding: .5rem .9rem; font-size: .82rem; font-weight: 600;
    }
    .tdr-back:hover { background: rgba(255,255,255,.25); color: #fff; }

    .tdr-kpis { display: flex; gap: .75rem; margin-top: 1.4rem; flex-wrap: wrap; }
    .tdr-kpi { flex: 1; min-width: 150px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .85rem 1rem; display: flex; align-items: center; gap: .7rem; }
    .tdr-kpi-ico { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; font-size: .95rem; color: #fff; }
    .tdr-kpi--warn .tdr-kpi-ico { background: rgba(245,158,11,.28); }
    .tdr-kpi-val { font-size: 1.3rem; font-weight: 700; color: #fff; line-height: 1; }
    .tdr-kpi-sub { font-size: .85rem; font-weight: 600; color: rgba(255,255,255,.6); }
    .tdr-kpi-lbl { font-size: .68rem; color: rgba(255,255,255,.65); margin-top: .2rem; }

    .tdr-filters { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: .85rem 1.1rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    .tdr-presets { display: inline-flex; background: #f1f5f9; border-radius: 9px; padding: .2rem; gap: .2rem; }
    .tdr-preset { border: none; background: transparent; cursor: pointer; padding: .42rem .85rem; border-radius: 7px; font-size: .78rem; font-weight: 600; color: #475569; transition: background .15s, color .15s; }
    .tdr-preset:hover:not(.tdr-preset--active) { background: rgba(4,83,203,.06); color: #0453cb; }
    .tdr-preset--active { background: #0453cb; color: #fff; }
    .tdr-date-fields { display: none; gap: .5rem; align-items: center; }
    .tdr-date-fields.show { display: flex; }
    .tdr-input { border: 1px solid #e2e8f0; border-radius: 9px; padding: .45rem .7rem; font-size: .82rem; color: #1e293b; }
    .tdr-input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
    .tdr-period-lbl { margin-left: auto; font-size: .8rem; color: #64748b; font-weight: 600; }
    .tdr-spin { color: #0453cb; font-size: .82rem; display: none; align-items: center; gap: .4rem; }
    .tdr-spin.show { display: inline-flex; }

    .tdr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; align-items: start; }
    .tdr-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); margin-bottom: 1.25rem; }
    .tdr-panel-head { display: flex; align-items: center; gap: .65rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .tdr-panel-ico { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .85rem; }
    .tdr-panel-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
    .tdr-panel-sub { font-size: .73rem; color: #94a3b8; }
    .tdr-panel-body { padding: 1.1rem 1.25rem; }

    .tdr-types { display: flex; flex-direction: column; gap: .8rem; }
    .tdr-type-card { border: 1px solid #e9eef5; border-radius: 12px; padding: .85rem 1rem; }
    .tdr-type-head { display: flex; align-items: center; gap: .55rem; }
    .tdr-type-chip { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; padding: .2rem .5rem; border-radius: 7px; }
    .tdr-type-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
    .tdr-type-nf { font-size: .64rem; color: #94a3b8; margin-left: auto; }
    .tdr-type-val { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-top: .5rem; }
    .tdr-type-plan { font-size: .82rem; font-weight: 600; color: #94a3b8; }
    .tdr-type-bar { height: 7px; border-radius: 5px; background: #eef2f7; overflow: hidden; margin-top: .5rem; }
    .tdr-type-bar-fill { height: 100%; border-radius: 5px; transition: width .3s; }
    .tdr-type-meta { display: flex; justify-content: space-between; font-size: .68rem; color: #64748b; margin-top: .3rem; }

    .tdr-noalert { font-size: .82rem; color: #065f46; background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.2); border-radius: 10px; padding: .8rem 1rem; display: flex; align-items: center; gap: .5rem; }
    .tdr-alert { display: flex; align-items: flex-start; gap: .55rem; font-size: .8rem; padding: .65rem .85rem; border-radius: 10px; margin-bottom: .5rem; }
    .tdr-alert:last-child { margin-bottom: 0; }
    .tdr-alert--warning { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.25); color: #92400e; }
    .tdr-alert--danger { background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.2); color: #b91c1c; }
    .tdr-alert i { margin-top: 2px; }
    .tdr-alerts-scroll { max-height: 260px; overflow-y: auto; }

    /* Réutilise les styles de lignes séances de la page report (tar-*) */
    .tdr-seances { max-height: 62vh; overflow-y: auto; }
    .tdr-seance-empty { text-align: center; padding: 2rem 1rem; color: #94a3b8; }
    .tdr-sentinel { padding: 1rem; text-align: center; color: #94a3b8; font-size: .8rem; }
    .tdr-empty { text-align: center; padding: 2rem 1rem; color: #94a3b8; }
    .tdr-empty i { font-size: 1.8rem; display: block; margin-bottom: .5rem; color: #cbd5e1; }

    /* lignes séances (mêmes classes que report) */
    .tar-seance-row { display: flex; align-items: center; gap: .85rem; padding: .75rem .35rem; border-bottom: 1px solid #f1f5f9; position: relative; overflow: hidden; }
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

    @media (max-width: 992px) { .tdr-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .tdr-hero { padding: 1.4rem 1.25rem; } }
</style>
@endpush

@section('content')
<div class="tdr-wrap"
     x-data="teacherPage()"
     data-has-more="{{ $paginator->hasMorePages() ? '1' : '0' }}"
     data-next-page="2"
     data-url="{{ route('esbtp.teacher-attendance.teacher-report.data', $teacher->id) }}">

    {{-- Hero --}}
    <div class="tdr-hero">
        <div class="tdr-hero-top">
            <div class="tdr-hero-left">
                <div class="tdr-hero-avatar">{{ \Illuminate\Support\Str::substr($teacher->user->name ?? 'E', 0, 1) }}</div>
                <div>
                    <h1>{{ $teacher->user->name ?? $teacher->name ?? 'Enseignant' }}</h1>
                    <div class="tdr-hero-meta">
                        @if($teacher->regime)
                            <span class="tdr-hero-pill"><i class="fas fa-file-contract"></i> {{ ucfirst($teacher->regime) }}</span>
                        @endif
                        @if($teacher->specialization)
                            <span class="tdr-hero-pill"><i class="fas fa-graduation-cap"></i> {{ $teacher->specialization }}</span>
                        @endif
                        <span class="tdr-hero-pill"><i class="fas fa-calendar"></i> {{ $anneeEnCours->name ?? 'Année courante' }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('esbtp.teacher-attendance.report') }}" class="tdr-back"><i class="fas fa-arrow-left"></i> Tous les enseignants</a>
        </div>
        <div class="tdr-kpis" id="tdrKpis">
            @include('esbtp.teacher-attendance.partials._teacher_kpis', ['summary' => $summary])
        </div>
    </div>

    {{-- Filtres période --}}
    <div class="tdr-filters">
        <span style="font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Période</span>
        <div class="tdr-presets">
            <button type="button" class="tdr-preset" :class="filters.preset === 'month' ? 'tdr-preset--active' : ''" @click="setPreset('month')">Ce mois</button>
            <button type="button" class="tdr-preset" :class="filters.preset === 'year' ? 'tdr-preset--active' : ''" @click="setPreset('year')">Année</button>
            <button type="button" class="tdr-preset" :class="filters.preset === 'custom' ? 'tdr-preset--active' : ''" @click="setPreset('custom')">Plage</button>
        </div>
        <div class="tdr-date-fields" :class="filters.preset === 'custom' ? 'show' : ''">
            <input type="date" class="tdr-input" x-model="filters.from" @change="applyPeriode()">
            <span style="color:#94a3b8;">→</span>
            <input type="date" class="tdr-input" x-model="filters.to" @change="applyPeriode()">
        </div>
        <span class="tdr-spin" :class="loading ? 'show' : ''"><i class="fas fa-circle-notch fa-spin"></i> Mise à jour…</span>
        <span class="tdr-period-lbl" x-text="periodeLabel">{{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }}</span>
    </div>

    <div class="tdr-grid">
        <div>
            {{-- Ventilation par type --}}
            <div class="tdr-panel">
                <div class="tdr-panel-head">
                    <div class="tdr-panel-ico"><i class="fas fa-shapes"></i></div>
                    <div>
                        <div class="tdr-panel-title">Heures par type de séance</div>
                        <div class="tdr-panel-sub">Réalisées vs planifiées — CM / TD / TP</div>
                    </div>
                </div>
                <div class="tdr-panel-body">
                    <div class="tdr-types" id="tdrTypes">
                        @include('esbtp.teacher-attendance.partials._teacher_types', ['summary' => $summary])
                    </div>
                </div>
            </div>

            {{-- Alertes ponctualité --}}
            <div class="tdr-panel">
                <div class="tdr-panel-head">
                    <div class="tdr-panel-ico" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-triangle-exclamation"></i></div>
                    <div>
                        <div class="tdr-panel-title">Ponctualité</div>
                        <div class="tdr-panel-sub">Retards et séances non émargées</div>
                    </div>
                </div>
                <div class="tdr-panel-body">
                    <div class="tdr-alerts-scroll" id="tdrWarnings">
                        @include('esbtp.teacher-attendance.partials._teacher_warnings', ['summary' => $summary])
                    </div>
                </div>
            </div>
        </div>

        {{-- Séances --}}
        <div class="tdr-panel">
            <div class="tdr-panel-head">
                <div class="tdr-panel-ico"><i class="fas fa-list-check"></i></div>
                <div>
                    <div class="tdr-panel-title">Détail des séances</div>
                    <div class="tdr-panel-sub"><span x-text="totalLabel">{{ $paginator->total() }} séance(s)</span> · durées précises</div>
                </div>
            </div>
            <div class="tdr-panel-body" style="padding-top:.4rem;padding-bottom:.4rem;">
                <div class="tdr-seances" id="tdrSeances">
                    @if($rows->isEmpty())
                        <div class="tdr-empty"><i class="fas fa-calendar-xmark"></i><p>Aucune séance sur cette période.</p></div>
                    @else
                        @include('esbtp.teacher-attendance.partials._report_seances', ['rows' => $rows])
                    @endif
                </div>
                <div class="tdr-sentinel" id="tdrSentinel" x-show="hasMore">
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
function teacherPage() {
    return {
        filters: { preset: @json($preset), from: @json($from->toDateString()), to: @json($to->toDateString()) },
        loading: false, loadingMore: false, hasMore: false, nextPage: 2, url: '',
        periodeLabel: @json($from->format('d/m/Y') . ' → ' . $to->format('d/m/Y')),
        totalLabel: @json($paginator->total() . ' séance(s)'),

        init() {
            this.url = this.$root.dataset.url;
            this.hasMore = this.$root.dataset.hasMore === '1';
            this.nextPage = parseInt(this.$root.dataset.nextPage, 10) || 2;
            this.observeSentinel();
            window.addEventListener('seance:status-updated', () => this.refreshAggregates());
        },

        async refreshAggregates() {
            try {
                const res = await fetch(this.url + '?' + this.params({ mode: 'filter' }).toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const d = await res.json();
                document.getElementById('tdrKpis').innerHTML = d.kpis_html;
                document.getElementById('tdrTypes').innerHTML = d.types_html;
                document.getElementById('tdrWarnings').innerHTML = d.warnings_html;
                // NE PAS toucher #tdrSeances : la ligne animée reste intacte.
            } catch (e) { /* silencieux */ }
        },

        setPreset(p) { this.filters.preset = p; if (p !== 'custom') this.applyPeriode(); },

        params(extra) {
            const p = new URLSearchParams();
            p.set('preset', this.filters.preset);
            if (this.filters.preset === 'custom') { p.set('from', this.filters.from); p.set('to', this.filters.to); }
            Object.entries(extra || {}).forEach(function (e) { p.set(e[0], e[1]); });
            return p;
        },

        async applyPeriode() {
            this.loading = true;
            try {
                const res = await fetch(this.url + '?' + this.params({ mode: 'filter' }).toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const d = await res.json();
                document.getElementById('tdrKpis').innerHTML = d.kpis_html;
                document.getElementById('tdrTypes').innerHTML = d.types_html;
                document.getElementById('tdrWarnings').innerHTML = d.warnings_html;
                document.getElementById('tdrSeances').innerHTML = d.seances_html
                    || '<div class="tdr-empty"><i class="fas fa-calendar-xmark"></i><p>Aucune séance.</p></div>';
                this.hasMore = d.has_more; this.nextPage = d.next_page;
                this.periodeLabel = d.periode.from + ' → ' + d.periode.to;
                this.totalLabel = d.total + ' séance(s)';
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally { this.loading = false; }
        },

        async loadMore() {
            if (!this.hasMore || this.loadingMore) return;
            this.loadingMore = true;
            try {
                const res = await fetch(this.url + '?' + this.params({ mode: 'scroll', page: this.nextPage }).toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const d = await res.json();
                document.getElementById('tdrSeances').insertAdjacentHTML('beforeend', d.seances_html);
                this.hasMore = d.has_more; this.nextPage = d.next_page;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally { this.loadingMore = false; }
        },

        observeSentinel() {
            const s = document.getElementById('tdrSentinel');
            if (!s || !('IntersectionObserver' in window)) return;
            new IntersectionObserver((es) => { es.forEach((e) => { if (e.isIntersecting) this.loadMore(); }); }, { rootMargin: '120px' }).observe(s);
        },
    };
}

@include('esbtp.teacher-attendance.partials._mark_status_js')
</script>
@endpush
