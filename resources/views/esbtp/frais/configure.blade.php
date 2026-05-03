@extends('layouts.app')

@section('title', 'Configuration des Frais - KLASSCI')

@push('styles')
<style>
/* ───────────────────────────────────────────────
   FRAIS CONFIGURE — KLASSCI Premium v2
   Namespace: fc-*
──────────────────────────────────────────────── */
:root {
    --fc-primary:    #0453cb;
    --fc-primary-d:  #033a8e;
    --fc-secondary:  #5e91de;
    --fc-dark:       #0f172a;
    --fc-text:       #1e293b;
    --fc-muted:      #64748b;
    --fc-success:    #10b981;
    --fc-warning:    #f59e0b;
    --fc-danger:     #ef4444;
    --fc-surface:    #f8fafc;
    --fc-white:      #ffffff;
    --fc-border:     #e2e8f0;
    --fc-shadow-sm:  0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    --fc-shadow-md:  0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
    --fc-shadow-lg:  0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    --fc-radius:     14px;
}

/* ── HERO (conforme rule premium-redesign KLASSCI) ── */
.fc-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.fc-hero-inner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.fc-hero-left {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}
.fc-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.fc-hero-title {
    font-size: 1.45rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 .2rem;
    letter-spacing: -.01em;
}
.fc-hero-sub {
    color: rgba(255,255,255,.72);
    font-size: .88rem;
    margin: 0 0 .55rem;
}
.fc-hero-chips {
    display: flex; flex-wrap: wrap; gap: .4rem;
}
.fc-hero-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 99px;
    font-size: .74rem; font-weight: 600;
    color: rgba(255,255,255,.94);
}
.fc-hero-chip i { font-size: .7rem; }
.fc-hero-actions { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
.fc-btn-ghost,
.fc-btn-glass {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.15);
    color: #fff;
    text-decoration: none;
    transition: all .2s ease;
    white-space: nowrap;
}
.fc-btn-ghost:hover,
.fc-btn-glass:hover {
    background: rgba(255,255,255,.22);
    color: #fff;
}
.fc-btn-ghost i { font-size: .78rem; }

@media (max-width: 992px) {
    .fc-hero-inner { flex-direction: column; align-items: stretch; }
    .fc-hero-actions { justify-content: flex-start; }
}
@media (max-width: 576px) {
    .fc-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
    .fc-hero-title { font-size: 1.2rem; }
    .fc-hero-sub { font-size: .82rem; }
}

/* ── INFO BAR ── */
.fc-info-bar {
    background: var(--fc-white);
    border: 1px solid var(--fc-border);
    border-radius: var(--fc-radius);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--fc-shadow-sm);
}
.fc-info-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(4,83,203,.06); color: var(--fc-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
}
.fc-info-text { font-size: .82rem; color: var(--fc-muted); line-height: 1.5; }
.fc-info-text strong { color: var(--fc-text); }

/* ── ALERTS ── */
.fc-alert {
    border-radius: var(--fc-radius);
    padding: .85rem 1.1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .85rem;
    font-weight: 500;
}
.fc-alert.is-success { background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.2); color: #065f46; }
.fc-alert.is-error   { background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2); color: #991b1b; }

/* ── CLASSES GRID ── */
.fc-section-title {
    font-size: .9rem; font-weight: 700; color: var(--fc-text); margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .5rem;
}
.fc-section-title i {
    width: 32px; height: 32px; border-radius: 9px;
    background: rgba(4,83,203,.07); color: var(--fc-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem;
}
.fc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}

/* ── CLASS CARD ── */
.fc-card {
    background: var(--fc-white);
    border: 1.5px solid var(--fc-border);
    border-radius: var(--fc-radius);
    padding: 1.25rem;
    transition: all .25s ease;
    box-shadow: var(--fc-shadow-sm);
    position: relative;
    overflow: hidden;
}
.fc-card:hover {
    box-shadow: var(--fc-shadow-lg);
    transform: translateY(-3px);
}
.fc-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 14px 14px 0 0;
    transition: opacity .2s;
}
.fc-card.is-complete::before  { background: var(--fc-success); }
.fc-card.is-partial::before   { background: var(--fc-warning); }
.fc-card.is-empty::before     { background: var(--fc-danger); }

.fc-card-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.fc-card-icon {
    width: 42px; height: 42px; border-radius: 11px;
    background: linear-gradient(135deg, var(--fc-primary), var(--fc-secondary));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
    box-shadow: 0 3px 8px rgba(4,83,203,.2);
}
.fc-card-name { font-weight: 700; color: var(--fc-text); font-size: .9rem; line-height: 1.2; }
.fc-card-meta { font-size: .72rem; color: var(--fc-muted); margin-top: 2px; }
.fc-card-students { font-size: .7rem; color: var(--fc-muted); margin-top: 2px; }

/* Stats row */
.fc-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .6rem;
    margin-bottom: 1rem;
}
.fc-stat {
    text-align: center;
    padding: .6rem .5rem;
    border-radius: 9px;
    background: var(--fc-surface);
}
.fc-stat-value { font-size: 1.1rem; font-weight: 800; line-height: 1; }
.fc-stat-label { font-size: .65rem; font-weight: 600; margin-top: .2rem; text-transform: uppercase; letter-spacing: .04em; }
.fc-stat.is-oblig .fc-stat-value { color: var(--fc-primary); }
.fc-stat.is-oblig .fc-stat-label { color: var(--fc-primary); }
.fc-stat.is-opt .fc-stat-value   { color: var(--fc-secondary); }
.fc-stat.is-opt .fc-stat-label   { color: var(--fc-secondary); }

/* Progress ring */
.fc-ring { text-align: center; margin-bottom: 1rem; }
.fc-ring svg { filter: drop-shadow(0 1px 3px rgba(0,0,0,.08)); }
.fc-ring circle { transition: stroke-dashoffset .6s ease; transform: rotate(-90deg); transform-origin: 50% 50%; }
.fc-ring-pct {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    font-size: .8rem; font-weight: 700; color: var(--fc-text);
}

/* Status badge */
.fc-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .65rem; border-radius: 8px;
    font-size: .68rem; font-weight: 600;
    margin-bottom: 1rem;
}
.fc-badge.is-complete { background: rgba(16,185,129,.08); color: #059669; }
.fc-badge.is-partial  { background: rgba(245,158,11,.08); color: #b45309; }
.fc-badge.is-empty    { background: rgba(239,68,68,.08); color: #dc2626; }

/* Configure button */
.fc-btn-config {
    width: 100%;
    background: var(--fc-primary);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: .65rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    transition: all .2s;
    box-shadow: 0 2px 6px rgba(4,83,203,.18);
}
.fc-btn-config:hover {
    background: var(--fc-primary-d);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}

/* ── MODAL ── */
#configurationModal.modal.show {
    display: flex !important; align-items: center !important; justify-content: center !important;
    position: fixed !important; inset: 0 !important; z-index: 1055 !important; padding: 1rem !important;
}
#configurationModal.modal.show .modal-dialog {
    position: static !important; margin: 0 !important;
    max-width: 90vw !important; width: 900px !important; max-height: 90vh !important;
    transform: none !important; display: flex !important; flex-direction: column !important;
}
#configurationModal .modal-content {
    background: var(--fc-white) !important;
    border-radius: 18px !important;
    box-shadow: 0 20px 60px rgba(15,23,42,.15), 0 4px 16px rgba(4,83,203,.1) !important;
    max-height: 100% !important; overflow: hidden !important;
    display: flex !important; flex-direction: column !important;
    border: none !important;
}
#configurationModal .modal-header {
    background: linear-gradient(135deg, #071631 0%, #0453cb 100%) !important;
    color: #fff !important;
    padding: 1.25rem 1.5rem !important;
    border: none !important;
    border-radius: 18px 18px 0 0 !important;
}
#configurationModal .modal-header .modal-title { font-size: 1rem; font-weight: 600; }
#configurationModal .modal-body { overflow-y: auto !important; flex-grow: 1 !important; padding: 1.5rem !important; }
#configurationModal .modal-footer {
    border-top: 1px solid var(--fc-border) !important;
    padding: 1rem 1.5rem !important;
    gap: .5rem;
}
#configurationModal .modal-footer .btn-secondary {
    background: var(--fc-surface); color: var(--fc-muted); border: 1.5px solid var(--fc-border);
    border-radius: 9px; font-weight: 500; font-size: .82rem; padding: .5rem 1rem;
}
#configurationModal .modal-footer .btn-primary {
    background: var(--fc-primary); border: none; border-radius: 9px;
    font-weight: 600; font-size: .82rem; padding: .5rem 1.25rem;
    box-shadow: 0 2px 6px rgba(4,83,203,.2);
}
#configurationModal .modal-footer .btn-primary:hover { background: var(--fc-primary-d); }

/* Modal info bar */
.fc-modal-info {
    background: rgba(4,83,203,.04);
    border: 1px solid rgba(4,83,203,.12);
    border-radius: 10px;
    padding: .75rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .82rem;
    color: var(--fc-text);
}
.fc-modal-info i { color: var(--fc-primary); font-size: .9rem; }

/* ── MODAL CATEGORY ROW (premium — loaded via AJAX) ── */
.fc-cat-row {
    background: var(--fc-white);
    border: 1px solid var(--fc-border);
    border-radius: 14px;
    padding: 1.25rem 1.3rem;
    margin-bottom: 1rem;
    transition: box-shadow .2s ease;
}
.fc-cat-row:last-child { margin-bottom: 0; }
.fc-cat-row:hover { box-shadow: var(--fc-shadow-sm); }

.fc-cat-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1rem;
    padding-bottom: .85rem;
    border-bottom: 1px solid var(--fc-border);
}
.fc-cat-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, var(--fc-primary), var(--fc-secondary));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .85rem; flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(4,83,203,.2);
}
.fc-cat-meta { flex: 1; min-width: 0; }
.fc-cat-name {
    font-weight: 700;
    color: var(--fc-text);
    font-size: .92rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    line-height: 1.2;
}
.fc-cat-pill {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .15rem .55rem;
    border-radius: 6px;
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    background: rgba(4,83,203,.08);
    color: var(--fc-primary);
    border: 1px solid rgba(4,83,203,.15);
}
.fc-cat-desc {
    font-size: .74rem;
    color: var(--fc-muted);
    margin-top: .25rem;
    line-height: 1.35;
}

.fc-cat-section {
    margin-bottom: 1rem;
}
.fc-cat-section:last-child { margin-bottom: 0; }
.fc-cat-section-label {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--fc-text);
    margin-bottom: .2rem;
    display: flex;
    align-items: center;
    gap: .35rem;
}
.fc-cat-section-label i { color: var(--fc-primary); font-size: .68rem; }
.fc-cat-section-hint {
    font-size: .72rem;
    color: var(--fc-muted);
    margin-bottom: .7rem;
    display: flex;
    align-items: center;
    gap: .3rem;
    line-height: 1.35;
}
.fc-cat-section-hint i { color: var(--fc-muted); font-size: .68rem; }

/* 3 statut cards (semantic colors : functional differentiation per rule) */
.fc-statuts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .6rem;
    margin-bottom: .75rem;
}
.fc-statut {
    background: var(--fc-white);
    border: 1.5px solid var(--fc-border);
    border-radius: 10px;
    padding: .75rem;
    transition: border-color .2s, box-shadow .2s;
    position: relative;
}
.fc-statut:hover { box-shadow: 0 2px 8px rgba(15,23,42,.05); }
.fc-statut.is-affecte     { border-top: 3px solid var(--fc-success); }
.fc-statut.is-reaffecte   { border-top: 3px solid var(--fc-warning); }
.fc-statut.is-non-affecte { border-top: 3px solid var(--fc-danger); }

.fc-statut-head {
    display: flex;
    align-items: center;
    gap: .4rem;
    margin-bottom: .55rem;
}
.fc-statut-dot {
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .55rem;
    color: #fff;
    flex-shrink: 0;
}
.fc-statut.is-affecte     .fc-statut-dot { background: var(--fc-success); }
.fc-statut.is-reaffecte   .fc-statut-dot { background: var(--fc-warning); }
.fc-statut.is-non-affecte .fc-statut-dot { background: var(--fc-danger); }
.fc-statut-label {
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.fc-statut.is-affecte     .fc-statut-label { color: #047857; }
.fc-statut.is-reaffecte   .fc-statut-label { color: #b45309; }
.fc-statut.is-non-affecte .fc-statut-label { color: #b91c1c; }

.fc-input-wrap {
    position: relative;
    margin-bottom: .4rem;
}
.fc-input {
    width: 100%;
    padding: .5rem 2.75rem .5rem .65rem;
    font-size: .84rem;
    font-weight: 600;
    border: 1.5px solid var(--fc-border);
    border-radius: 8px;
    background: var(--fc-white);
    color: var(--fc-text);
    transition: border-color .2s, box-shadow .2s;
    font-family: inherit;
}
.fc-input::placeholder { color: #cbd5e1; font-weight: 500; }
.fc-input:focus {
    outline: none;
    border-color: var(--fc-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.fc-input-suffix {
    position: absolute;
    right: .5rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: .6rem;
    font-weight: 700;
    color: var(--fc-muted);
    letter-spacing: .04em;
    pointer-events: none;
}
.fc-statut-hint {
    font-size: .65rem;
    color: var(--fc-muted);
    line-height: 1.35;
}

/* Copy actions — monochrome ghost buttons */
.fc-copy-actions {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
}
.fc-copy-btn {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .3rem .65rem;
    background: transparent;
    border: 1.5px solid var(--fc-border);
    border-radius: 7px;
    font-size: .7rem;
    font-weight: 600;
    color: var(--fc-muted);
    cursor: pointer;
    transition: all .15s;
    font-family: inherit;
}
.fc-copy-btn:hover {
    background: var(--fc-surface);
    color: var(--fc-primary);
    border-color: rgba(4,83,203,.25);
}
.fc-copy-btn i { font-size: .62rem; }

/* Deadline input group */
.fc-deadline {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: .5rem;
    align-items: center;
}
.fc-deadline-suffix {
    font-size: .78rem;
    color: var(--fc-muted);
    white-space: nowrap;
    padding: 0 .4rem;
}

/* Status bar at bottom */
.fc-cat-status {
    margin-top: 1rem;
    padding: .5rem .75rem;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .72rem;
    font-weight: 600;
}
.fc-cat-status.is-done {
    background: rgba(16,185,129,.08);
    color: #047857;
}
.fc-cat-status.is-todo {
    background: rgba(245,158,11,.08);
    color: #b45309;
}
.fc-cat-status i { font-size: .72rem; }

/* ── EMPTY STATE ── */
.fc-empty {
    text-align: center; padding: 3rem 2rem; color: var(--fc-muted);
}
.fc-empty i { font-size: 2.5rem; opacity: .15; margin-bottom: 1rem; display: block; }
.fc-empty p { font-size: .85rem; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .fc-grid { grid-template-columns: 1fr; }
    .fc-hero { padding: 1.5rem; border-radius: 14px; }
    .fc-hero-title { font-size: 1.2rem; }
}
@media (max-width: 576px) {
    #configurationModal.modal.show { padding: .5rem !important; }
    #configurationModal.modal.show .modal-dialog { max-width: 95vw !important; width: 95vw !important; }
    .fc-statuts { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── HERO (conforme rule premium-redesign) ── --}}
        <div class="fc-hero">
            <div class="fc-hero-inner">
                <div class="fc-hero-left">
                    <span class="fc-hero-icon"><i class="fas fa-sliders-h"></i></span>
                    <div>
                        <h1 class="fc-hero-title">Configuration des Frais par Classe</h1>
                        <p class="fc-hero-sub">Configurez les tarifs obligatoires pour chaque combinaison filière et niveau</p>
                        <div class="fc-hero-chips">
                            <span class="fc-hero-chip">
                                <i class="fas fa-coins"></i>
                                Gestion des frais
                            </span>
                        </div>
                    </div>
                </div>
                <div class="fc-hero-actions">
                    <a href="{{ route('esbtp.frais.optional-config') }}" class="fc-btn-ghost">
                        <i class="fas fa-puzzle-piece"></i>
                        <span>Frais Optionnels</span>
                    </a>
                    <a href="{{ route('esbtp.frais.index') }}" class="fc-btn-ghost">
                        <i class="fas fa-arrow-left"></i>
                        <span>Retour</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ── ALERTS ── --}}
        @if(session('success'))
            <div class="fc-alert is-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="fc-alert is-error">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── INFO BAR ── --}}
        <div class="fc-info-bar">
            <div class="fc-info-icon"><i class="fas fa-info"></i></div>
            <div class="fc-info-text">
                <strong>Classe = Filière + Niveau.</strong>
                Cliquez sur une carte pour configurer les montants des frais obligatoires.
                Les frais <span style="color:var(--fc-danger);font-weight:600;">obligatoires</span> doivent tous être configurés pour chaque classe.
            </div>
        </div>

        {{-- ── CLASSES GRID ── --}}
        <div class="fc-section-title">
            <i class="fas fa-graduation-cap"></i>
            Classes ({{ $classes->count() }})
        </div>

        @if($classes->count() > 0)
            <div class="fc-grid">
                @foreach($classes as $classe)
                    @php
                        $totalRequired = $classe->total_obligatoires;
                        $totalConfigured = $classe->obligatoires_configures;
                        $percentage = $totalRequired > 0 ? ($totalConfigured / $totalRequired) * 100 : 0;
                        $circumference = 2 * 3.14159 * 22;
                        $strokeDashoffset = $circumference - ($percentage / 100) * $circumference;

                        if ($totalConfigured == $totalRequired) {
                            $statusClass = 'is-complete';
                            $statusIcon = 'fa-check-circle';
                            $statusText = 'Complet';
                            $ringColor = '#10b981';
                        } elseif ($totalConfigured > 0) {
                            $statusClass = 'is-partial';
                            $statusIcon = 'fa-exclamation-triangle';
                            $statusText = 'Partiel';
                            $ringColor = '#f59e0b';
                        } else {
                            $statusClass = 'is-empty';
                            $statusIcon = 'fa-times-circle';
                            $statusText = 'Non configuré';
                            $ringColor = '#ef4444';
                        }
                    @endphp

                    <div class="fc-card {{ $statusClass }}">
                        <div class="fc-card-header">
                            <div class="fc-card-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div>
                                <div class="fc-card-name">{{ $classe->name }}</div>
                                <div class="fc-card-meta">{{ $classe->filiere->name }} · {{ $classe->niveau->name }}</div>
                                <div class="fc-card-students"><i class="fas fa-users" style="margin-right:.25rem;"></i>{{ $classe->effectif }} étudiants</div>
                            </div>
                        </div>

                        <div class="fc-stats">
                            <div class="fc-stat is-oblig">
                                <div class="fc-stat-value">{{ $totalConfigured }}/{{ $totalRequired }}</div>
                                <div class="fc-stat-label">Obligatoires</div>
                            </div>
                            <div class="fc-stat is-opt">
                                @if($classe->optionnels_configures > 0)
                                    <div class="fc-stat-value">{{ $classe->optionnels_configures }}</div>
                                    <div class="fc-stat-label">Optionnels</div>
                                @else
                                    <div class="fc-stat-value" style="color:var(--fc-muted);">—</div>
                                    <div class="fc-stat-label" style="color:var(--fc-muted);">Optionnels</div>
                                @endif
                            </div>
                        </div>

                        <div class="fc-ring" style="position:relative;display:inline-block;width:100%;">
                            <div style="display:flex;align-items:center;justify-content:center;">
                                <div style="position:relative;width:52px;height:52px;"
                                     role="img"
                                     aria-label="Configuration {{ number_format($percentage, 0) }}% complète">
                                    <svg width="52" height="52" aria-hidden="true">
                                        <circle cx="26" cy="26" r="22" stroke="#e9eef5" stroke-width="4" fill="transparent"/>
                                        <circle cx="26" cy="26" r="22"
                                                stroke="{{ $ringColor }}"
                                                stroke-width="4"
                                                fill="transparent"
                                                stroke-linecap="round"
                                                stroke-dasharray="{{ $circumference }}"
                                                stroke-dashoffset="{{ $strokeDashoffset }}"/>
                                    </svg>
                                    <div class="fc-ring-pct">{{ number_format($percentage, 0) }}%</div>
                                </div>
                            </div>
                        </div>

                        <div style="text-align:center;">
                            <span class="fc-badge {{ $statusClass }}">
                                <i class="fas {{ $statusIcon }}" style="font-size:.65em;"></i>
                                {{ $statusText }}
                            </span>
                        </div>

                        <button type="button"
                                class="fc-btn-config configure-btn"
                                data-filiere-id="{{ $classe->filiere->id }}"
                                data-niveau-id="{{ $classe->niveau->id }}"
                                data-filiere-name="{{ $classe->filiere->name }}"
                                data-niveau-name="{{ $classe->niveau->name }}"
                                onclick="openConfigurationModal(this)">
                            <i class="fas fa-cogs"></i>
                            <span class="configure-text">Configurer les Frais</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="fc-empty">
                <i class="fas fa-graduation-cap"></i>
                <p>Aucune classe trouvée. Vérifiez que vous avez des filières et niveaux actifs.</p>
            </div>
        @endif

        {{-- ── MODAL ── --}}
        <div class="modal fade" id="configurationModal" tabindex="-1" aria-labelledby="configurationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="configurationModalLabel">
                            <i class="fas fa-cogs" style="margin-right:.4rem;opacity:.8;"></i>
                            Configuration des Frais
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="fc-modal-info">
                            <i class="fas fa-graduation-cap"></i>
                            <div>
                                <strong>Classe :</strong> <span id="modalClasseInfo">-</span><br>
                                <small style="color:var(--fc-muted);">Configurez les montants des frais obligatoires pour cette combinaison filière/niveau.</small>
                            </div>
                        </div>

                        <form id="configurationForm" method="POST" action="{{ route('esbtp.frais.update-configuration') }}">
                            @csrf
                            <input type="hidden" id="modalFiliereId" name="filiere_id">
                            <input type="hidden" id="modalNiveauId" name="niveau_id">

                            <div id="categoriesContainer">
                                <div style="text-align:center;padding:2rem;">
                                    <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;color:var(--fc-primary);"></i>
                                    <p style="margin-top:.75rem;color:var(--fc-muted);font-size:.85rem;">Chargement des catégories...</p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times" style="margin-right:.3rem;"></i>Annuler
                        </button>
                        <button type="button" id="saveConfigurationBtn" class="btn btn-primary">
                            <i class="fas fa-save" style="margin-right:.3rem;"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<x-fab-encaisser />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM ready - Simple modal system');

    window.openConfigurationModal = function(button) {
        debugLog('Opening configuration modal...');

        const filiereId = button.dataset.filiereId;
        const niveauId = button.dataset.niveauId;
        const filiereName = button.dataset.filiereName;
        const niveauName = button.dataset.niveauName;

        if (typeof bootstrap === 'undefined') {
            alert('Bootstrap non disponible');
            return;
        }

        document.getElementById('modalFiliereId').value = filiereId;
        document.getElementById('modalNiveauId').value = niveauId;
        document.getElementById('modalClasseInfo').textContent = `${filiereName} - ${niveauName}`;

        const modalElement = document.getElementById('configurationModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        modalElement.addEventListener('shown.bs.modal', function() {
            loadCategories(filiereId, niveauId);
        }, { once: true });
    };

    function loadCategories(filiereId, niveauId) {
        const container = document.getElementById('categoriesContainer');
        const url = `{{ route('esbtp.frais.get-categories') }}?filiere_id=${filiereId}&niveau_id=${niveauId}&type=mandatory`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    container.innerHTML = data.html;
                } else {
                    container.innerHTML = '<div class="alert alert-warning">Aucune catégorie trouvée</div>';
                }
            })
            .catch(error => {
                debugError('Erreur:', error);
                container.innerHTML = '<div class="alert alert-danger">Erreur de chargement</div>';
            });
    }

    window.copyToAll = function(categoryId, sourceField) {
        const sourceInput = document.getElementById(`${sourceField}_${categoryId}`);
        if (!sourceInput || !sourceInput.value) {
            alert('Veuillez d\'abord saisir le montant source');
            return;
        }

        const value = sourceInput.value;
        const fields = ['amount_affecte', 'amount_reaffecte', 'amount_non_affecte'];

        fields.forEach(field => {
            const input = document.getElementById(`${field}_${categoryId}`);
            if (input) {
                input.value = value;
                input.style.backgroundColor = '#e6fffa';
                setTimeout(() => { input.style.backgroundColor = ''; }, 500);
            }
        });

        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copié!';
        btn.classList.add('btn-success');
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
        }, 1500);
    };

    document.getElementById('saveConfigurationBtn').addEventListener('click', function() {
        const form = document.getElementById('configurationForm');
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('configurationModal')).hide();
                location.reload();
            } else {
                alert('Erreur: ' + (data.message || 'Impossible d\'enregistrer'));
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Erreur de connexion');
        });
    });
});
</script>
@endpush
