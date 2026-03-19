@extends('layouts.app')

@section('title', 'Notes LMD | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Notes Index — Premium Redesign v2
       Prefix: ln- (lmd-notes)
       ══════════════════════════════════════════════ */

    .ln-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .ln-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem;
        color: #fff; margin-bottom: 1.5rem; overflow: hidden;
        animation: ln-fadeDown .5s ease-out;
    }
    .ln-hero::before {
        content: ''; position: absolute; top: -60%; right: -10%;
        width: 420px; height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .ln-hero::after {
        content: ''; position: absolute; bottom: -40%; left: 5%;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }
    .ln-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1;
    }
    .ln-hero-left { display: flex; align-items: center; gap: 1rem; }
    .ln-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0;
    }
    .ln-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
    .ln-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }

    /* KPIs in hero */
    .ln-hero-kpis {
        display: flex; gap: .75rem; margin-top: 1.5rem;
        position: relative; z-index: 1; flex-wrap: wrap;
    }
    .ln-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15); border-radius: 12px;
        padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
        transition: background .2s;
    }
    .ln-kpi:hover { background: rgba(255,255,255,.15); }
    .ln-kpi-icon {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
    }
    .ln-kpi--classes .ln-kpi-icon   { background: rgba(255,255,255,.18); color: #fff; }
    .ln-kpi--etudiants .ln-kpi-icon { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .ln-kpi--evals .ln-kpi-icon     { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .ln-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1; color: #fff; }
    .ln-kpi-label { font-size: .75rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* ── Classes grid ── */
    .ln-section-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 1rem;
        animation: ln-fadeUp .45s ease-out .1s both;
    }
    .ln-section-title {
        font-size: 1.05rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: .5rem;
    }
    .ln-section-title i { color: #0453cb; }
    .ln-section-count {
        font-size: .8rem; color: #94a3b8; font-weight: 500;
        background: #f1f5f9; padding: .25rem .6rem; border-radius: 20px;
    }

    .ln-cards {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1rem; animation: ln-fadeUp .45s ease-out .15s both;
    }
    .ln-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden; transition: all .25s; display: flex; flex-direction: column;
    }
    .ln-card:hover { box-shadow: 0 4px 20px rgba(4,83,203,.08); transform: translateY(-2px); }

    .ln-card-head {
        display: flex; align-items: center; gap: .75rem;
        padding: 1.15rem 1.25rem; border-bottom: 1px solid #f1f5f9;
    }
    .ln-card-icon {
        width: 42px; height: 42px; border-radius: 11px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem; flex-shrink: 0;
    }
    .ln-card-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
    .ln-card-sub { font-size: .78rem; color: #94a3b8; margin-top: .1rem; }

    .ln-card-metrics {
        display: grid; grid-template-columns: 1fr 1fr 1fr;
        padding: .85rem 1.25rem; gap: .5rem;
    }
    .ln-metric { display: flex; flex-direction: column; }
    .ln-metric-label { font-size: .68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
    .ln-metric-value { font-size: 1.1rem; font-weight: 700; color: #1e293b; }

    .ln-card-foot {
        padding: .85rem 1.25rem; border-top: 1px solid #f1f5f9;
        display: flex; gap: .5rem; margin-top: auto;
    }
    .ln-card-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .5rem 1rem; border-radius: 9px; font-size: .82rem;
        font-weight: 600; border: none; cursor: pointer; transition: all .2s;
        text-decoration: none; flex: 1; justify-content: center;
    }
    .ln-card-btn--primary {
        background: #0453cb; color: #fff;
        box-shadow: 0 2px 6px rgba(4,83,203,.2);
    }
    .ln-card-btn--primary:hover { background: #0340a0; color: #fff; text-decoration: none; }
    .ln-card-btn--outline {
        background: #fff; color: #0453cb; border: 1.5px solid #dbeafe;
    }
    .ln-card-btn--outline:hover { background: #eff6ff; border-color: #0453cb; color: #0453cb; text-decoration: none; }

    /* ── Empty ── */
    .ln-empty-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
    }
    .ln-empty { text-align: center; padding: 4rem 2rem; }
    .ln-empty-icon {
        width: 76px; height: 76px; border-radius: 20px; background: #f1f5f9;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #cbd5e1; margin-bottom: 1.15rem;
    }
    .ln-empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: .4rem; }
    .ln-empty-text { font-size: .88rem; color: #94a3b8; max-width: 380px; margin: 0 auto; }

    /* ── Premium Modal ── */
    .ln-modal .modal-content {
        border-radius: 18px; border: none;
        box-shadow: 0 25px 80px rgba(0,0,0,.2), 0 8px 24px rgba(4,83,203,.1);
        overflow: hidden;
    }
    .ln-modal .modal-header { position: relative; padding: 0; border: none; }
    .ln-modal-hero {
        padding: 1.5rem 2rem 1.25rem;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);
        color: #fff; position: relative; overflow: hidden;
    }
    .ln-modal-hero::before {
        content: ''; position: absolute; top: -50%; right: -15%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%);
        pointer-events: none;
    }
    .ln-modal-hero-top {
        display: flex; align-items: center; justify-content: space-between;
        position: relative; z-index: 1;
    }
    .ln-modal-hero-left { display: flex; align-items: center; gap: .85rem; }
    .ln-modal-icon {
        width: 46px; height: 46px; border-radius: 12px;
        background: rgba(255,255,255,.15); backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }
    .ln-modal-title { font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff; }
    .ln-modal-subtitle { font-size: .78rem; opacity: .7; margin-top: .15rem; }
    .ln-modal .btn-close { filter: brightness(0) invert(1); opacity: .7; position: relative; z-index: 2; }
    .ln-modal .btn-close:hover { opacity: 1; }

    /* Hero mini KPIs */
    .ln-modal-kpis {
        display: flex; gap: .6rem; margin-top: 1rem; position: relative; z-index: 1;
    }
    .ln-modal-kpi {
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 8px; padding: .45rem .75rem;
        display: flex; align-items: center; gap: .5rem;
    }
    .ln-modal-kpi-val { font-size: 1rem; font-weight: 700; color: #fff; }
    .ln-modal-kpi-lbl { font-size: .7rem; color: rgba(255,255,255,.6); }

    /* Modal body */
    .ln-modal .modal-body { padding: 0; }

    .ln-modal-toolbar {
        display: flex; align-items: center; gap: .75rem; padding: 1rem 1.5rem;
        border-bottom: 1px solid #e8ecf1; flex-wrap: wrap; background: #fafbfc;
    }
    .ln-modal-toolbar select {
        border-radius: 9px; border: 1.5px solid #e2e8f0; padding: .45rem .75rem;
        font-size: .84rem; background: #fff; transition: all .2s;
    }
    .ln-modal-toolbar select:focus {
        border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); outline: none;
    }
    .ln-modal-add-btn {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .45rem .85rem; border-radius: 9px; font-size: .82rem;
        font-weight: 600; background: #059669; color: #fff; border: none;
        cursor: pointer; transition: all .2s; margin-left: auto;
        text-decoration: none;
    }
    .ln-modal-add-btn:hover { background: #047857; color: #fff; text-decoration: none; }

    /* Notes grid */
    .ln-grid-wrap { overflow-x: auto; max-height: 520px; overflow-y: auto; }
    .ln-grid {
        width: 100%; border-collapse: collapse; font-size: .84rem;
    }
    .ln-grid thead { position: sticky; top: 0; z-index: 5; }
    .ln-grid thead th {
        padding: .55rem .65rem; background: #f8fafc; border-bottom: 2px solid #e2e8f0;
        font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase;
        letter-spacing: .04em; white-space: nowrap; text-align: center;
    }
    .ln-grid thead th:first-child { text-align: left; min-width: 180px; position: sticky; left: 0; z-index: 6; background: #f8fafc; }
    .ln-grid tbody td {
        padding: .4rem .5rem; border-bottom: 1px solid #f1f5f9;
        vertical-align: middle; text-align: center;
    }
    .ln-grid tbody td:first-child { text-align: left; position: sticky; left: 0; z-index: 3; background: #fff; }
    .ln-grid tbody tr:hover td { background: #f8fbff; }
    .ln-grid tbody tr:hover td:first-child { background: #f0f6ff; }

    .ln-student-name { font-weight: 600; color: #1e293b; font-size: .82rem; white-space: nowrap; }
    .ln-student-mat { font-size: .7rem; color: #94a3b8; font-family: 'SF Mono', monospace; }

    .ln-note-input {
        width: 56px; padding: .3rem .4rem; border: 1.5px solid #e2e8f0;
        border-radius: 7px; font-size: .84rem; text-align: center;
        font-weight: 600; transition: all .2s; background: #fff;
    }
    .ln-note-input:focus { border-color: #0453cb; box-shadow: 0 0 0 2px rgba(4,83,203,.1); outline: none; }
    .ln-note-input:disabled { background: #f1f5f9; color: #94a3b8; }
    .ln-note-input.ln-saved { border-color: #10b981; background: #f0fdf4; }

    .ln-abs-check { width: 1rem; height: 1rem; accent-color: #dc2626; cursor: pointer; }

    .ln-note-cell { display: flex; align-items: center; gap: .25rem; justify-content: center; }

    /* Eval header */
    .ln-eval-th { min-width: 90px; }
    .ln-eval-title { font-size: .72rem; font-weight: 700; color: #1e293b; text-transform: none; letter-spacing: 0; }
    .ln-eval-type { font-size: .62rem; color: #94a3b8; font-weight: 500; text-transform: none; letter-spacing: 0; }

    /* Average column */
    .ln-avg { font-weight: 700; font-size: .88rem; }
    .ln-avg--pass { color: #059669; }
    .ln-avg--fail { color: #dc2626; }

    /* Class averages row */
    .ln-grid tfoot td {
        padding: .55rem .65rem; background: #f0f6ff; border-top: 2px solid #dbeafe;
        font-weight: 700; color: #0453cb; font-size: .84rem; text-align: center;
    }
    .ln-grid tfoot td:first-child { text-align: left; position: sticky; left: 0; z-index: 3; background: #f0f6ff; }

    /* Empty / loading states */
    .ln-modal-empty { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
    .ln-modal-empty i { font-size: 2rem; opacity: .4; display: block; margin-bottom: .75rem; }
    .ln-modal-loading { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
    .ln-modal-loading i { font-size: 2rem; }
    .ln-autosave-info {
        padding: .6rem 1.5rem; background: #f0fdf4; border-top: 1px solid #bbf7d0;
        font-size: .78rem; color: #059669; display: flex; align-items: center; gap: .4rem;
    }

    /* Modal footer */
    .ln-modal .modal-footer {
        border-top: 1px solid #e8ecf1; padding: .85rem 1.5rem;
        background: #fafbfc; display: flex; gap: .5rem; justify-content: flex-end;
    }
    .ln-modal-fbtn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .5rem 1rem; border-radius: 9px; font-size: .82rem;
        font-weight: 600; border: none; cursor: pointer; transition: all .2s;
    }
    .ln-modal-fbtn--close { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .ln-modal-fbtn--close:hover { background: #f1f5f9; }
    .ln-modal-fbtn--action { background: #0453cb; color: #fff; text-decoration: none; }
    .ln-modal-fbtn--action:hover { background: #0340a0; color: #fff; text-decoration: none; }

    /* Modal slide animation */
    .ln-modal.fade .modal-dialog { transform: translateY(20px) scale(.98); transition: transform .25s ease-out, opacity .2s; }
    .ln-modal.show .modal-dialog { transform: translateY(0) scale(1); }

    .ln-modal-loading { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
    .ln-modal-loading i { font-size: 2rem; }

    /* ── Animations ── */
    @keyframes ln-fadeDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes ln-fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .ln-hero { padding: 1.5rem; border-radius: 14px; }
        .ln-hero-top { flex-direction: column; }
        .ln-hero-kpis { flex-direction: column; }
        .ln-cards { grid-template-columns: 1fr; }
        .ln-modal-toolbar { flex-direction: column; align-items: stretch; }
        .ln-modal-add-btn { margin-left: 0; }
    }
</style>
@endpush

@section('content')
<div class="ln-page">

    {{-- ══ Hero ══ --}}
    @php
        $totalClasses = $classes->count();
        $totalEtudiants = $classes->sum('etudiants_count');
        $totalEvals = $evalCounts->sum();
    @endphp

    <div class="ln-hero">
        <div class="ln-hero-top">
            <div class="ln-hero-left">
                <div class="ln-hero-icon"><i class="fas fa-edit"></i></div>
                <div class="ln-hero-info">
                    <h1>Notes LMD</h1>
                    <p>Gestion des notes par classe — {{ $anneeCourante->name ?? 'Aucune année' }}</p>
                </div>
            </div>
        </div>

        <div class="ln-hero-kpis">
            <div class="ln-kpi ln-kpi--classes">
                <div class="ln-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalClasses }}</div>
                    <div class="ln-kpi-label">Classes LMD</div>
                </div>
            </div>
            <div class="ln-kpi ln-kpi--etudiants">
                <div class="ln-kpi-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalEtudiants }}</div>
                    <div class="ln-kpi-label">Étudiants actifs</div>
                </div>
            </div>
            <div class="ln-kpi ln-kpi--evals">
                <div class="ln-kpi-icon"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalEvals }}</div>
                    <div class="ln-kpi-label">Évaluations</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
        @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    {{-- ══ Classes grid ══ --}}
    @if($classes->isEmpty())
        <div class="ln-empty-card">
            <div class="ln-empty">
                <div class="ln-empty-icon"><i class="fas fa-layer-group"></i></div>
                <div class="ln-empty-title">Aucune classe LMD</div>
                <div class="ln-empty-text">Aucune classe utilisant le système LMD n'a été trouvée. Configurez d'abord vos classes.</div>
            </div>
        </div>
    @else
        <div class="ln-section-header">
            <div class="ln-section-title">
                <i class="fas fa-th-large"></i>
                Classes LMD
            </div>
            <span class="ln-section-count">{{ $totalClasses }} classe{{ $totalClasses > 1 ? 's' : '' }}</span>
        </div>

        <div class="ln-cards">
            @foreach($classes as $classe)
                @php $nbEvals = $evalCounts[$classe->id] ?? 0; @endphp
                <div class="ln-card">
                    <div class="ln-card-head">
                        <div class="ln-card-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <div class="ln-card-title">{{ $classe->name }}</div>
                            <div class="ln-card-sub">
                                {{ $classe->filiere->name ?? '' }}
                                @if($classe->niveau) &middot; {{ $classe->niveau->name ?? '' }} @endif
                            </div>
                        </div>
                    </div>

                    <div class="ln-card-metrics">
                        <div class="ln-metric">
                            <span class="ln-metric-label">Étudiants</span>
                            <span class="ln-metric-value">{{ $classe->etudiants_count }}</span>
                        </div>
                        <div class="ln-metric">
                            <span class="ln-metric-label">Évaluations</span>
                            <span class="ln-metric-value">{{ $nbEvals }}</span>
                        </div>
                        <div class="ln-metric">
                            <span class="ln-metric-label">Année</span>
                            <span class="ln-metric-value" style="font-size:.82rem; color:#64748b;">{{ $anneeCourante->name ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="ln-card-foot">
                        <button type="button" class="ln-card-btn ln-card-btn--primary"
                                onclick="openNotesModal({{ $classe->id }}, '{{ addslashes($classe->name) }}')">
                            <i class="fas fa-edit"></i>Gérer les notes
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══ MODAL — Gestion des notes (pattern BTS avec UE → ECUE) ══ --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="modal fade ln-modal" id="modalNotes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="ln-modal-hero w-100">
                    <div class="ln-modal-hero-top">
                        <div class="ln-modal-hero-left">
                            <div class="ln-modal-icon"><i class="fas fa-edit"></i></div>
                            <div>
                                <h5 class="ln-modal-title">Gestion des Notes — <span id="notesModalTitle">Sélectionnez une classe</span></h5>
                                <div class="ln-modal-subtitle" id="notesModalSubtitle">Sélectionnez une UE puis un ECUE pour saisir les notes</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="ln-modal-kpis">
                        <div class="ln-modal-kpi">
                            <span class="ln-modal-kpi-val" id="nkpi_etudiants">—</span>
                            <span class="ln-modal-kpi-lbl">Étudiants</span>
                        </div>
                        <div class="ln-modal-kpi">
                            <span class="ln-modal-kpi-val" id="nkpi_evals">—</span>
                            <span class="ln-modal-kpi-lbl">Évaluations</span>
                        </div>
                        <div class="ln-modal-kpi">
                            <span class="ln-modal-kpi-val" id="nkpi_matieres">—</span>
                            <span class="ln-modal-kpi-lbl">ECUEs</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-body">
                {{-- Toolbar: UE → ECUE selectors --}}
                <div class="ln-modal-toolbar">
                    <select id="ueSelect" style="min-width:220px;">
                        <option value="">— Choisir une UE —</option>
                    </select>
                    <select id="ecueSelect" style="min-width:220px;" disabled>
                        <option value="">— Choisir un ECUE —</option>
                    </select>
                    <select id="periodeFilter" style="min-width:120px;">
                        <option value="all">Toutes</option>
                        <option value="semestre1">Semestre 1</option>
                        <option value="semestre2">Semestre 2</option>
                    </select>
                    <a href="#" id="createEvalLink" class="ln-modal-add-btn" style="display:none;">
                        <i class="fas fa-plus"></i> Créer évaluation
                    </a>
                </div>

                {{-- Loading --}}
                <div class="ln-modal-loading" id="notesLoading" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <div style="margin-top:.75rem; font-size:.88rem;">Chargement des notes...</div>
                </div>

                {{-- Notes grid --}}
                <div class="ln-grid-wrap" id="notesGridWrap" style="display:none;">
                    <table class="ln-grid" id="notesGrid">
                        <thead><tr><th>Étudiant</th></tr></thead>
                        <tbody id="studentsRows"></tbody>
                        <tfoot id="classAvgRow"></tfoot>
                    </table>
                </div>

                {{-- Empty / initial state --}}
                <div class="ln-modal-empty" id="notesEmpty">
                    <i class="fas fa-hand-pointer"></i>
                    Sélectionnez une UE puis un ECUE pour afficher la grille de notes.
                </div>

                {{-- Auto-save info --}}
                <div class="ln-autosave-info" id="autosaveInfo" style="display:none;">
                    <i class="fas fa-check-circle"></i>
                    Les notes sont automatiquement enregistrées à chaque modification.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="ln-modal-fbtn ln-modal-fbtn--close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ══ State ══
let notesModal = null;
let currentClasseId = null;
let currentClasseData = null; // { classe, etudiants, evaluations, matieres, ues }
let evaluationsData = {};
let notesData = {};
let evalParamsCache = {};

document.addEventListener('DOMContentLoaded', function() {
    notesModal = new bootstrap.Modal(document.getElementById('modalNotes'));
});

// ══ Open modal for a class ══
async function openNotesModal(classeId, classeName) {
    currentClasseId = classeId;

    // Reset UI
    document.getElementById('notesModalTitle').textContent = classeName;
    document.getElementById('notesModalSubtitle').textContent = 'Chargement...';
    document.getElementById('notesLoading').style.display = 'block';
    document.getElementById('notesGridWrap').style.display = 'none';
    document.getElementById('notesEmpty').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'none';
    document.getElementById('nkpi_etudiants').textContent = '—';
    document.getElementById('nkpi_evals').textContent = '—';
    document.getElementById('nkpi_matieres').textContent = '—';

    // Reset selects
    const ueSelect = document.getElementById('ueSelect');
    const ecueSelect = document.getElementById('ecueSelect');
    ueSelect.innerHTML = '<option value="">— Choisir une UE —</option>';
    ecueSelect.innerHTML = '<option value="">— Choisir un ECUE —</option>';
    ecueSelect.disabled = true;

    notesModal.show();

    try {
        const resp = await fetch('/esbtp/lmd/notes/classe/' + classeId + '/data', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        currentClasseData = await resp.json();
        const data = currentClasseData;

        // Update hero
        const sub = [data.classe.filiere, data.classe.niveau].filter(Boolean).join(' · ');
        document.getElementById('notesModalSubtitle').textContent = sub || 'Sélectionnez une UE puis un ECUE';

        // KPIs
        document.getElementById('nkpi_etudiants').textContent = data.etudiants.length;
        document.getElementById('nkpi_evals').textContent = data.evaluations.length;
        document.getElementById('nkpi_matieres').textContent = data.matieres.length;

        // Build UE list from matières (group by ue_code)
        const ueMap = {};
        data.matieres.forEach(m => {
            const key = m.ue_code || 'SANS_UE';
            if (!ueMap[key]) ueMap[key] = { code: m.ue_code, name: m.ue_name, ecues: [] };
            ueMap[key].ecues.push(m);
        });

        Object.values(ueMap).forEach(ue => {
            const opt = document.createElement('option');
            opt.value = ue.code || '';
            opt.textContent = (ue.code ? ue.code + ' — ' : '') + (ue.name || 'Sans UE');
            ueSelect.appendChild(opt);
        });

        // Store UE map for ECUE population
        currentClasseData._ueMap = ueMap;

        document.getElementById('notesLoading').style.display = 'none';

        // Reset empty state message
        document.getElementById('notesEmpty').innerHTML =
            '<i class="fas fa-hand-pointer"></i>Sélectionnez une UE puis un ECUE pour afficher la grille de notes.';

        // Si aucune UE liée → message explicatif
        if (data.matieres.length === 0) {
            document.getElementById('notesEmpty').innerHTML =
                '<i class="fas fa-exclamation-triangle" style="color:#d97706; font-size:2rem; opacity:.7; display:block; margin-bottom:.75rem;"></i>' +
                '<strong style="color:#1e293b;">Aucune UE liée à cette classe</strong><br>' +
                '<span style="font-size:.84rem;">Pour saisir des notes, vous devez d\'abord :</span>' +
                '<div style="text-align:left; max-width:360px; margin:.75rem auto 0; font-size:.84rem; color:#475569;">' +
                '1. Lier la classe à un <strong>parcours</strong> (dans Gestion des classes)<br>' +
                '2. Lier des <strong>UEs au parcours</strong> (dans <a href="{{ route("esbtp.lmd.parcours-domain.index") }}" style="color:#0453cb; text-decoration:underline;">Parcours LMD</a> → bouton violet 📖)<br>' +
                '3. Les UEs doivent avoir des <strong>ECUEs (matières)</strong> rattachés' +
                '</div>';
        }
        document.getElementById('notesEmpty').style.display = 'block';

    } catch (err) {
        console.error('Error loading classe data:', err);
        document.getElementById('notesLoading').innerHTML =
            '<i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>' +
            '<div style="margin-top:.75rem; color:#dc2626;">Erreur de chargement.</div>';
    }
}

// ══ UE selection → populate ECUEs ══
document.getElementById('ueSelect').addEventListener('change', function() {
    const ecueSelect = document.getElementById('ecueSelect');
    ecueSelect.innerHTML = '<option value="">— Choisir un ECUE —</option>';
    ecueSelect.disabled = true;

    document.getElementById('notesGridWrap').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'none';
    document.getElementById('notesEmpty').style.display = 'block';

    const ueCode = this.value;
    if (!ueCode || !currentClasseData?._ueMap) return;

    const ue = currentClasseData._ueMap[ueCode];
    if (!ue) return;

    ue.ecues.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = (m.code ? m.code + ' — ' : '') + m.name;
        ecueSelect.appendChild(opt);
    });
    ecueSelect.disabled = false;
});

// ══ ECUE selection → load evaluations & build grid ══
document.getElementById('ecueSelect').addEventListener('change', function() {
    const matiereId = this.value;
    if (!matiereId || !currentClasseId) return;
    loadEvaluationsAndBuildGrid(currentClasseId, matiereId);
});

// ══ Period filter ══
document.getElementById('periodeFilter').addEventListener('change', function() {
    if (document.getElementById('ecueSelect').value) {
        buildNotesGrid();
    }
});

// ══ Load evaluations for class + matière (same API as BTS) ══
async function loadEvaluationsAndBuildGrid(classeId, matiereId) {
    document.getElementById('notesEmpty').style.display = 'none';
    document.getElementById('notesLoading').style.display = 'block';
    document.getElementById('notesGridWrap').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'none';

    try {
        const resp = await fetch(`/api/evaluations/by-class-matiere/${classeId}/${matiereId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();

        if (!data.success) {
            throw new Error(data.message || 'Erreur');
        }

        evaluationsData = data.evaluations || {};
        notesData = data.notes || {};

        // Cache params
        evalParamsCache = {};
        Object.values(evaluationsData).forEach(ev => {
            evalParamsCache[ev.id] = { bareme: parseFloat(ev.bareme) || 20, coefficient: parseFloat(ev.coefficient) || 1 };
        });

        document.getElementById('notesLoading').style.display = 'none';
        buildNotesGrid();

    } catch (err) {
        console.error('Error loading evaluations:', err);
        document.getElementById('notesLoading').style.display = 'none';
        document.getElementById('notesEmpty').style.display = 'block';
        document.getElementById('notesEmpty').innerHTML =
            '<i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>' +
            'Aucune évaluation trouvée pour cet ECUE. Créez d\'abord une évaluation.';
    }
}

// ══ Build the notes grid (BTS pattern) ══
function buildNotesGrid() {
    const periodeFilter = document.getElementById('periodeFilter').value;
    const students = currentClasseData?.etudiants || [];

    // Filter & sort evaluations
    let evals = Object.values(evaluationsData);
    if (periodeFilter !== 'all') {
        evals = evals.filter(ev => {
            const p = normalizePeriode(ev.periode);
            return p === periodeFilter;
        });
    }
    evals.sort((a, b) => (a.date_evaluation || '').localeCompare(b.date_evaluation || ''));

    if (evals.length === 0) {
        document.getElementById('notesGridWrap').style.display = 'none';
        document.getElementById('notesEmpty').style.display = 'block';
        document.getElementById('notesEmpty').innerHTML =
            '<i class="fas fa-clipboard"></i>Aucune évaluation trouvée pour ce filtre.';
        return;
    }

    // Build header
    let headerHtml = '<tr><th>Étudiant</th>';
    evals.forEach(ev => {
        headerHtml += `<th class="ln-eval-th">
            <div class="ln-eval-title">${escHtml(ev.titre || ev.type_evaluation || 'Eval')}</div>
            <div class="ln-eval-type">${escHtml(ev.type_evaluation || '')} — /${ev.bareme || 20}</div>
        </th>`;
    });
    headerHtml += '<th>Moyenne</th></tr>';

    // Build student rows
    let bodyHtml = '';
    students.forEach(stu => {
        const stuNotes = notesData[stu.id] || {};
        bodyHtml += `<tr data-student-id="${stu.id}">`;
        bodyHtml += `<td><div class="ln-student-name">${escHtml(stu.nom)} ${escHtml(stu.prenoms)}</div><div class="ln-student-mat">${escHtml(stu.matricule || '')}</div></td>`;

        evals.forEach(ev => {
            const noteVal = stuNotes[ev.id] ?? '';
            const isAbsent = stuNotes[ev.id + '_absent'] || false;
            const bareme = ev.bareme || 20;
            bodyHtml += `<td><div class="ln-note-cell">
                <input type="number" class="ln-note-input" step="0.25" min="0" max="${bareme}"
                       value="${isAbsent ? '' : noteVal}"
                       data-student-id="${stu.id}" data-eval-id="${ev.id}"
                       ${isAbsent ? 'disabled' : ''}
                       onchange="saveNote(${stu.id}, ${ev.id}, this.value)">
                <input type="checkbox" class="ln-abs-check" title="Absent"
                       data-student-id="${stu.id}" data-eval-id="${ev.id}"
                       ${isAbsent ? 'checked' : ''}
                       onchange="toggleAbsence(${stu.id}, ${ev.id}, this.checked)">
            </div></td>`;
        });

        bodyHtml += '<td class="ln-avg" id="avg-' + stu.id + '">--</td>';
        bodyHtml += '</tr>';
    });

    // Class averages row
    let footHtml = '<tr><td><strong>Moyenne classe</strong></td>';
    evals.forEach(ev => {
        footHtml += `<td id="class-avg-${ev.id}">--</td>`;
    });
    footHtml += '<td id="class-overall-avg">--</td></tr>';

    document.querySelector('#notesGrid thead').innerHTML = headerHtml;
    document.getElementById('studentsRows').innerHTML = bodyHtml;
    document.getElementById('classAvgRow').innerHTML = footHtml;
    document.getElementById('notesGridWrap').style.display = 'block';
    document.getElementById('notesEmpty').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'flex';

    // Calculate averages
    students.forEach(stu => calculateStudentAverage(stu.id));
    calculateClassAverages();
}

// ══ Save a single note (AJAX, same endpoint as BTS) ══
function saveNote(studentId, evaluationId, noteValue) {
    const absCheckbox = document.querySelector(`.ln-abs-check[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);
    const isAbsent = absCheckbox?.checked || false;
    const input = document.querySelector(`.ln-note-input[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);

    fetch('{{ route("esbtp.notes.save-ajax") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            etudiant_id: studentId,
            evaluation_id: evaluationId,
            note: isAbsent ? 0 : (noteValue || 0),
            is_absent: isAbsent ? 'on' : '',
        })
    }).then(r => r.json()).then(data => {
        if (data.success && input) {
            input.classList.add('ln-saved');
            setTimeout(() => input.classList.remove('ln-saved'), 1200);
        }
        // Update local cache
        if (!notesData[studentId]) notesData[studentId] = {};
        notesData[studentId][evaluationId] = isAbsent ? 0 : parseFloat(noteValue || 0);
        notesData[studentId][evaluationId + '_absent'] = isAbsent;
        calculateStudentAverage(studentId);
        calculateClassAverages();
    }).catch(err => console.error('Save error:', err));
}

// ══ Toggle absence ══
function toggleAbsence(studentId, evaluationId, isAbsent) {
    const input = document.querySelector(`.ln-note-input[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);
    if (input) {
        input.disabled = isAbsent;
        if (isAbsent) input.value = '';
    }
    saveNote(studentId, evaluationId, isAbsent ? 0 : (input?.value || 0));
}

// ══ Calculate student average ══
function calculateStudentAverage(studentId) {
    const inputs = document.querySelectorAll(`.ln-note-input[data-student-id="${studentId}"]`);
    let totalPoints = 0, totalCoeff = 0;

    inputs.forEach(inp => {
        const evalId = inp.dataset.evalId;
        const absCheck = document.querySelector(`.ln-abs-check[data-student-id="${studentId}"][data-eval-id="${evalId}"]`);
        if (absCheck?.checked) return;
        const val = parseFloat(inp.value);
        if (isNaN(val)) return;
        const params = evalParamsCache[evalId] || { bareme: 20, coefficient: 1 };
        const normalized = (val / params.bareme) * 20;
        totalPoints += normalized * params.coefficient;
        totalCoeff += params.coefficient;
    });

    const avgEl = document.getElementById('avg-' + studentId);
    if (!avgEl) return;
    if (totalCoeff > 0) {
        const avg = totalPoints / totalCoeff;
        avgEl.textContent = avg.toFixed(2);
        avgEl.className = 'ln-avg ' + (avg >= 10 ? 'ln-avg--pass' : 'ln-avg--fail');
    } else {
        avgEl.textContent = '--';
        avgEl.className = 'ln-avg';
    }
}

// ══ Calculate class averages ══
function calculateClassAverages() {
    const evals = Object.values(evaluationsData);

    // Per-evaluation average
    evals.forEach(ev => {
        const inputs = document.querySelectorAll(`.ln-note-input[data-eval-id="${ev.id}"]`);
        let sum = 0, count = 0;
        inputs.forEach(inp => {
            const sid = inp.dataset.studentId;
            const absCheck = document.querySelector(`.ln-abs-check[data-student-id="${sid}"][data-eval-id="${ev.id}"]`);
            if (absCheck?.checked) return;
            const val = parseFloat(inp.value);
            if (!isNaN(val)) { sum += val; count++; }
        });
        const el = document.getElementById('class-avg-' + ev.id);
        if (el) el.textContent = count > 0 ? (sum / count).toFixed(2) : '--';
    });

    // Overall class average
    const students = currentClasseData?.etudiants || [];
    let totalAvg = 0, avgCount = 0;
    students.forEach(stu => {
        const avgEl = document.getElementById('avg-' + stu.id);
        if (avgEl && avgEl.textContent !== '--') {
            totalAvg += parseFloat(avgEl.textContent);
            avgCount++;
        }
    });
    const overallEl = document.getElementById('class-overall-avg');
    if (overallEl) overallEl.textContent = avgCount > 0 ? (totalAvg / avgCount).toFixed(2) : '--';
}

// ══ Helpers ══
function normalizePeriode(p) {
    if (!p) return 'semestre1';
    const s = String(p).toLowerCase();
    if (s.includes('2') || s === 's2') return 'semestre2';
    return 'semestre1';
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>
@endpush
