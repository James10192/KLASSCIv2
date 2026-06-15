@extends('layouts.app')

@section('title', 'Suivi des présences — Coordination')

@push('styles')
<style>
    .cad-wrap { max-width: 1280px; margin: 0 auto; }

    .cad-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 1.9rem 2.25rem 1.5rem; color: #fff;
        margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .cad-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .cad-hero-left { display: flex; align-items: center; gap: 1rem; }
    .cad-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; color: #fff; flex-shrink: 0; }
    .cad-hero h1 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; }
    .cad-hero p { color: rgba(255,255,255,.72); font-size: .85rem; margin: .15rem 0 0; }
    .cad-hero-actions { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .cad-date {
        display: inline-flex; align-items: center; gap: .45rem;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
        border-radius: 10px; padding: .35rem .65rem;
    }
    .cad-date input { background: transparent; border: none; color: #fff; font-size: .82rem; font-weight: 600; outline: none; }
    .cad-date input::-webkit-calendar-picker-indicator { filter: invert(1); }
    .cad-btn-link {
        display: inline-flex; align-items: center; gap: .4rem; text-decoration: none;
        background: #fff; color: #0453cb; border-radius: 10px; padding: .5rem .9rem; font-size: .82rem; font-weight: 700;
    }
    .cad-btn-link:hover { color: #033a8e; }
    .cad-spin { color: #fff; font-size: .82rem; display: none; align-items: center; gap: .4rem; }
    .cad-spin.show { display: inline-flex; }

    .cad-kpis { display: flex; gap: .7rem; margin-top: 1.4rem; flex-wrap: wrap; }
    .cad-kpi { flex: 1; min-width: 140px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .8rem .9rem; display: flex; align-items: center; gap: .65rem; }
    .cad-kpi-ico { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; font-size: .9rem; color: #fff; }
    .cad-kpi--warn .cad-kpi-ico { background: rgba(245,158,11,.28); }
    .cad-kpi-val { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1; }
    .cad-kpi-sub { font-size: .8rem; font-weight: 600; color: rgba(255,255,255,.6); }
    .cad-kpi-lbl { font-size: .66rem; color: rgba(255,255,255,.65); margin-top: .2rem; }

    .cad-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; align-items: start; }
    .cad-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); margin-bottom: 1.25rem; }
    .cad-panel-head { display: flex; align-items: center; gap: .65rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .cad-panel-ico { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .85rem; }
    .cad-panel-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
    .cad-panel-sub { font-size: .73rem; color: #94a3b8; }
    .cad-panel-body { padding: 1.1rem 1.25rem; }

    /* Workflow — cartographie pipeline */
    .cad-flow { display: flex; align-items: stretch; gap: .35rem; flex-wrap: wrap; }
    .cad-node { flex: 1; min-width: 102px; text-align: center; border: 1px solid #e9eef5; border-radius: 12px; padding: .8rem .5rem; background: #fff; transition: box-shadow .2s, border-color .2s; }
    .cad-node:hover { border-color: #c7d4e5; box-shadow: 0 4px 14px rgba(4,83,203,.07); }
    .cad-node-ico { width: 40px; height: 40px; border-radius: 11px; margin: 0 auto .5rem; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem; }
    .cad-node--start .cad-node-ico { background: linear-gradient(135deg, #033a8e, #0453cb); }
    .cad-node--step .cad-node-ico { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
    .cad-node--done .cad-node-ico { background: linear-gradient(135deg, #059669, #10b981); }
    .cad-node-val { font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1; }
    .cad-node-title { font-size: .73rem; font-weight: 700; color: #334155; margin-top: .3rem; }
    .cad-node-sub { font-size: .63rem; color: #94a3b8; margin-top: .1rem; text-transform: uppercase; letter-spacing: .3px; }
    .cad-flow-arrow { display: flex; align-items: center; color: #cbd5e1; font-size: .8rem; flex: 0 0 auto; }
    .cad-flow-summary { margin-top: 1.1rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0; }
    .cad-flow-summary-head { display: flex; justify-content: space-between; align-items: center; font-size: .82rem; color: #475569; font-weight: 600; }
    .cad-flow-summary-head i { color: #0453cb; margin-right: .35rem; }
    .cad-flow-summary-head strong { font-size: 1.1rem; color: #0453cb; }
    .cad-flow-bar { height: 9px; border-radius: 6px; background: #eef2f7; overflow: hidden; margin: .5rem 0 .35rem; }
    .cad-flow-bar-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0453cb, #10b981); transition: width .4s; }
    .cad-flow-summary-meta { font-size: .72rem; color: #94a3b8; }
    @media (max-width: 700px) { .cad-flow-arrow { display: none; } .cad-node { min-width: 44%; } }

    /* Subjects */
    .cad-subject { display: flex; align-items: center; gap: 1rem; padding: .7rem .25rem; border-bottom: 1px solid #f1f5f9; }
    .cad-subject:last-child { border-bottom: none; }
    .cad-subject-info { flex: 1; min-width: 0; }
    .cad-subject-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
    .cad-subject-meta { display: flex; flex-wrap: wrap; gap: .65rem; font-size: .7rem; color: #64748b; margin-top: .2rem; }
    .cad-subject-meta i { color: #94a3b8; margin-right: .12rem; }
    .cad-subject-prog { display: flex; align-items: center; gap: .55rem; width: 150px; flex-shrink: 0; }
    .cad-subject-bar { flex: 1; height: 7px; border-radius: 5px; background: #eef2f7; overflow: hidden; }
    .cad-subject-bar-fill { height: 100%; border-radius: 5px; transition: width .3s; }
    .cad-subject-pct { font-size: .75rem; font-weight: 800; width: 38px; text-align: right; }

    /* Alerts */
    .cad-noalert { font-size: .82rem; color: #065f46; background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.2); border-radius: 10px; padding: .8rem 1rem; display: flex; align-items: center; gap: .5rem; }
    .cad-alert { display: flex; align-items: flex-start; gap: .6rem; padding: .75rem .9rem; border-radius: 10px; margin-bottom: .6rem; }
    .cad-alert:last-child { margin-bottom: 0; }
    .cad-alert--danger { background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.2); color: #b91c1c; }
    .cad-alert--warning { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.25); color: #92400e; }
    .cad-alert--info { background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.18); color: #1e3a8a; }
    .cad-alert i { margin-top: 2px; }
    .cad-alert-title { font-size: .83rem; font-weight: 700; }
    .cad-alert-msg { font-size: .77rem; margin-top: .1rem; }
    .cad-alert-details { margin: .35rem 0 0; padding-left: 1.1rem; font-size: .72rem; opacity: .9; }

    .cad-empty { text-align: center; padding: 2rem 1rem; color: #94a3b8; }
    .cad-empty i { font-size: 1.8rem; display: block; margin-bottom: .5rem; color: #cbd5e1; }

    @media (max-width: 992px) { .cad-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .cad-hero { padding: 1.4rem 1.25rem; } .cad-subject-prog { width: 110px; } }
</style>
@endpush

@section('content')
<div class="cad-wrap"
     x-data="coordDashboard()"
     data-url="{{ route('coordinateur.attendance-dashboard.data') }}">

    {{-- Hero --}}
    <div class="cad-hero">
        <div class="cad-hero-top">
            <div class="cad-hero-left">
                <div class="cad-hero-icon"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <h1>Suivi des présences</h1>
                    <p>Monitoring émargements enseignants & présences étudiants — <span x-text="dateLabel">{{ $date->translatedFormat('l d F Y') }}</span></p>
                </div>
            </div>
            <div class="cad-hero-actions">
                <span class="cad-spin" :class="loading ? 'show' : ''"><i class="fas fa-circle-notch fa-spin"></i></span>
                <label class="cad-date">
                    <i class="fas fa-calendar-day"></i>
                    <input type="date" x-model="date" @change="reload()" value="{{ $date->toDateString() }}">
                </label>
                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="cad-btn-link"><i class="fas fa-business-time"></i> Heures enseignants</a>
            </div>
        </div>
        <div class="cad-kpis" id="cadKpis">
            @include('coordinateur.partials._cad_kpis', ['stats' => $stats])
        </div>
    </div>

    {{-- Workflow — cartographie pleine largeur --}}
    <div class="cad-panel" style="margin-bottom:1.25rem;">
        <div class="cad-panel-head">
            <div class="cad-panel-ico"><i class="fas fa-diagram-project"></i></div>
            <div>
                <div class="cad-panel-title">Cartographie du workflow</div>
                <div class="cad-panel-sub">Séance → Émargement → Appel → Bouclage du cours</div>
            </div>
        </div>
        <div class="cad-panel-body" id="cadWorkflow">
            @include('coordinateur.partials._cad_workflow', ['stats' => $stats])
        </div>
    </div>

    <div class="cad-grid">
        {{-- Alertes --}}
        <div class="cad-panel">
            <div class="cad-panel-head">
                <div class="cad-panel-ico" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-bell"></i></div>
                <div>
                    <div class="cad-panel-title">Alertes</div>
                    <div class="cad-panel-sub">Points d'attention du jour</div>
                </div>
            </div>
            <div class="cad-panel-body" id="cadAlerts">
                @include('coordinateur.partials._cad_alerts', ['stats' => $stats])
            </div>
        </div>

        {{-- Matières --}}
        <div class="cad-panel">
            <div class="cad-panel-head">
                <div class="cad-panel-ico"><i class="fas fa-book"></i></div>
                <div>
                    <div class="cad-panel-title">Avancement par matière</div>
                    <div class="cad-panel-sub">Émargements + appels effectués</div>
                </div>
            </div>
            <div class="cad-panel-body" id="cadSubjects">
                @include('coordinateur.partials._cad_subjects', ['stats' => $stats])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function coordDashboard() {
    return {
        date: @json($date->toDateString()),
        dateLabel: @json($date->translatedFormat('l d F Y')),
        loading: false,
        url: '',

        init() { this.url = this.$root.dataset.url; },

        async reload() {
            this.loading = true;
            try {
                const res = await fetch(this.url + '?date=' + encodeURIComponent(this.date), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const d = await res.json();
                document.getElementById('cadKpis').innerHTML = d.kpis_html;
                document.getElementById('cadWorkflow').innerHTML = d.workflow_html;
                document.getElementById('cadSubjects').innerHTML = d.subjects_html;
                document.getElementById('cadAlerts').innerHTML = d.alerts_html;
                this.dateLabel = d.date_label;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
