@extends('layouts.app')

@section('title', 'Gestion des classes - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* =====================================================================
   CLASSES INDEX — namespace ci-*
   Design premium monochrome bleu KLASSCI
   ===================================================================== */

/* -------- Variables locales -------- */
.dashboard-acasi {
    --ci-primary: #0453cb;
    --ci-primary-dark: #033a8e;
    --ci-accent: #3b7ddb;
    --ci-text: #1e293b;
    --ci-muted: #64748b;
    --ci-surface: #f8fafc;
    --ci-border: #e2e8f0;
    --ci-success: #10b981;
    --ci-warn: #f59e0b;
    --ci-danger: #ef4444;
}

/* =========================================
   HERO
   ========================================= */
.ci-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.ci-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}

.ci-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.ci-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ci-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    color: #fff;
}

.ci-hero h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 .2rem;
}
.ci-hero p {
    color: rgba(255,255,255,.75);
    font-size: .88rem;
    margin: 0 0 .5rem;
}

.ci-hero-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .3rem .7rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 99px;
    color: #fff;
    font-size: .78rem;
    font-weight: 500;
}
.ci-hero-chip-btn {
    background: transparent;
    border: none;
    color: rgba(255,255,255,.7);
    padding: 0 .1rem;
    cursor: pointer;
    transition: color .15s;
    font-size: .82rem;
}
.ci-hero-chip-btn:hover { color: #fff; }

.ci-hero-chip--warning {
    background: rgba(245, 158, 11, .22);
    border-color: rgba(254, 215, 170, .55);
}
.ci-hero-chip--warning > i:first-child { color: #fbbf24; }

.ci-hero-actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}

/* Boutons hero */
.ci-btn--glass,
.ci-btn--white {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1.1rem;
    border-radius: 10px;
    font-size: .83rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all .2s ease;
    border: 1px solid transparent;
}
.ci-btn--glass {
    background: rgba(255,255,255,.12);
    color: #fff;
    border-color: rgba(255,255,255,.18);
}
.ci-btn--glass:hover {
    background: rgba(255,255,255,.2);
    color: #fff;
    transform: translateY(-1px);
}
.ci-btn--white {
    background: #fff;
    color: var(--ci-primary);
}
.ci-btn--white:hover {
    background: #f1f5f9;
    color: var(--ci-primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,0,0,.12);
}

/* KPIs hero */
.ci-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
    margin-top: 1.5rem;
    position: relative;
    z-index: 1;
}
.ci-kpi {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    transition: background .2s;
}
.ci-kpi:hover { background: rgba(255,255,255,.15); }
.ci-kpi-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: rgba(255,255,255,.14);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    color: #fff;
    flex-shrink: 0;
}
.ci-kpi-body { flex: 1; min-width: 0; }
.ci-kpi-value {
    font-size: 1.45rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}
.ci-kpi-label {
    font-size: .72rem;
    color: rgba(255,255,255,.7);
    margin-top: .25rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 500;
}
.ci-kpi-bar {
    height: 4px;
    background: rgba(255,255,255,.2);
    border-radius: 99px;
    margin-top: .5rem;
    overflow: hidden;
}
.ci-kpi-bar-fill {
    height: 100%;
    background: #fff;
    border-radius: 99px;
    transition: width .6s ease;
}

/* =========================================
   ALERTS (flash messages)
   ========================================= */
.ci-alert {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .85rem 1.1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-weight: 500;
    font-size: .9rem;
}
.ci-alert--success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}
.ci-alert--danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

/* =========================================
   OVERCAPACITY BANNER (monochrome)
   ========================================= */
.ci-banner {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-left: 4px solid var(--ci-warn);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: .9rem;
}
.ci-banner-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: #fef3c7;
    display: flex; align-items: center; justify-content: center;
    color: var(--ci-warn);
    flex-shrink: 0;
    font-size: 1rem;
}
.ci-banner-body { flex: 1; min-width: 0; }
.ci-banner-title {
    font-weight: 700;
    color: #92400e;
    margin-bottom: .2rem;
    font-size: .92rem;
}
.ci-banner-text {
    color: #78350f;
    font-size: .82rem;
    line-height: 1.45;
}
.ci-banner-actions {
    display: flex;
    align-items: center;
    gap: .4rem;
    flex-shrink: 0;
}
.ci-btn--outline {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .4rem .8rem;
    border-radius: 8px;
    border: 1px solid #92400e;
    background: transparent;
    color: #92400e;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
}
.ci-btn--outline:hover {
    background: #92400e;
    color: #fff;
}
.ci-banner-close {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #92400e;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.ci-banner-close:hover { background: rgba(146,64,14,.1); }

/* =========================================
   TOOLBAR FILTRES
   ========================================= */
.ci-toolbar {
    background: #fff;
    border: 1px solid var(--ci-border);
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.ci-toolbar-row {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    align-items: center;
}
.ci-search {
    position: relative;
    flex: 1 1 280px;
    min-width: 240px;
}
.ci-search i {
    position: absolute;
    left: .9rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ci-muted);
    font-size: .88rem;
    pointer-events: none;
}
.ci-search input {
    width: 100%;
    padding: .6rem .9rem .6rem 2.3rem;
    border: 1px solid var(--ci-border);
    border-radius: 10px;
    font-size: .88rem;
    background: var(--ci-surface);
    transition: all .2s;
}
.ci-search input:focus {
    outline: none;
    background: #fff;
    border-color: var(--ci-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.ci-filter-select {
    padding: .6rem .9rem;
    border: 1px solid var(--ci-border);
    border-radius: 10px;
    font-size: .85rem;
    background: var(--ci-surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%2364748b' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") no-repeat right .75rem center;
    padding-right: 2rem;
    cursor: pointer;
    appearance: none;
    color: var(--ci-text);
    transition: all .2s;
    min-width: 150px;
}
.ci-filter-select:focus {
    outline: none;
    border-color: var(--ci-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.ci-btn--ghost {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .55rem .85rem;
    border: 1px solid var(--ci-border);
    border-radius: 10px;
    background: #fff;
    color: var(--ci-text);
    font-size: .83rem;
    font-weight: 500;
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
}
.ci-btn--ghost:hover {
    border-color: var(--ci-primary);
    color: var(--ci-primary);
    background: rgba(4,83,203,.04);
}

.ci-toolbar-meta {
    margin-top: .75rem;
    padding-top: .75rem;
    border-top: 1px dashed var(--ci-border);
    font-size: .78rem;
    color: var(--ci-muted);
    display: flex;
    gap: 1rem;
    align-items: center;
}
.ci-count i {
    margin-right: .35rem;
    color: var(--ci-primary);
}

/* Dropdown menu premium */
.ci-dropdown {
    border-radius: 12px;
    border: 1px solid var(--ci-border);
    box-shadow: 0 10px 30px rgba(15,23,42,.1);
    padding: .4rem;
    min-width: 200px;
}
.ci-dropdown .dropdown-item {
    display: flex;
    align-items: center;
    gap: .55rem;
    padding: .55rem .75rem;
    border-radius: 8px;
    font-size: .85rem;
    color: var(--ci-text);
    cursor: pointer;
}
.ci-dropdown .dropdown-item i {
    width: 18px;
    text-align: center;
    color: var(--ci-muted);
    font-size: .88rem;
}
.ci-dropdown .dropdown-item:hover {
    background: rgba(4,83,203,.08);
    color: var(--ci-primary);
}
.ci-dropdown .dropdown-item:hover i { color: var(--ci-primary); }
.ci-dropdown-item--danger:hover,
.ci-dropdown-item--danger:hover i {
    color: #991b1b !important;
    background: rgba(239,68,68,.08) !important;
}

/* =========================================
   GRID RESULTATS
   ========================================= */
.ci-results {
    margin-bottom: 1.5rem;
}
.ci-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.1rem;
}

/* =========================================
   CARD CLASSE (classe-card.blade.php)
   ========================================= */
.ci-card {
    background: #fff;
    border: 1px solid var(--ci-border);
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    position: relative;
    transition: all .2s ease;
    display: flex;
    flex-direction: column;
    gap: .85rem;
}
.ci-card:hover {
    border-color: #c7d4e5;
    box-shadow: 0 8px 26px rgba(4,83,203,.08), 0 2px 6px rgba(15,23,42,.04);
    transform: translateY(-2px);
}
.ci-card--inactive {
    background: #f8fafc;
    opacity: .72;
}
.ci-card-ribbon {
    position: absolute;
    top: 0; left: 0;
    width: 3px;
    height: 100%;
    border-radius: 14px 0 0 14px;
}
.ci-card-ribbon--active { background: linear-gradient(180deg, var(--ci-primary), var(--ci-accent)); }
.ci-card-ribbon--inactive { background: #cbd5e1; }

.ci-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .75rem;
}
.ci-card-identity {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex: 1;
    min-width: 0;
}
.ci-card-icon {
    width: 42px; height: 42px;
    border-radius: 11px;
    background: linear-gradient(135deg, rgba(4,83,203,.1), rgba(59,125,219,.1));
    color: var(--ci-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.ci-card-titles { min-width: 0; }
.ci-card-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 .15rem;
    color: var(--ci-text);
    line-height: 1.25;
}
.ci-card-link {
    color: inherit;
    text-decoration: none;
}
.ci-card-link:hover { color: var(--ci-primary); }
.ci-card-code {
    display: inline-block;
    background: var(--ci-surface);
    color: var(--ci-muted);
    font-family: 'Courier New', monospace;
    font-size: .72rem;
    font-weight: 600;
    padding: .15rem .5rem;
    border-radius: 6px;
    letter-spacing: .03em;
}

.ci-card-header-right {
    display: flex;
    align-items: center;
    gap: .4rem;
    position: relative;
    z-index: 2;
}

.ci-card-status {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: .25rem .55rem;
    border-radius: 99px;
}
.ci-card-status--active {
    background: rgba(16,185,129,.12);
    color: #065f46;
}
.ci-card-status--inactive {
    background: rgba(148,163,184,.2);
    color: #475569;
}

.ci-card-menu { position: relative; }
.ci-card-kebab {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--ci-muted);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    transition: all .15s;
}
.ci-card-kebab:hover,
.ci-card-kebab[aria-expanded="true"] {
    background: var(--ci-surface);
    color: var(--ci-primary);
    border-color: var(--ci-border);
}

.ci-card-meta {
    display: flex;
    flex-direction: column;
    gap: .3rem;
}
.ci-card-meta-line {
    font-size: .82rem;
    color: var(--ci-text);
    display: flex;
    align-items: center;
    gap: .5rem;
}
.ci-card-meta-line i {
    color: var(--ci-muted);
    width: 14px;
    text-align: center;
    font-size: .78rem;
}
.ci-card-meta-parent {
    color: var(--ci-muted);
    font-size: .76rem;
    font-weight: 400;
}

.ci-card-stats {
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: .6rem 0;
    background: var(--ci-surface);
    border-radius: 10px;
    gap: .5rem;
}
.ci-card-stat {
    flex: 1;
    text-align: center;
}
.ci-card-stat-value {
    display: block;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ci-primary);
    line-height: 1;
}
.ci-card-stat-value--warn { color: var(--ci-warn); }
.ci-card-stat-value--ok { color: var(--ci-success); }
.ci-card-stat-label {
    font-size: .68rem;
    color: var(--ci-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 500;
    margin-top: .15rem;
    display: block;
}
.ci-card-stat--separator {
    flex: 0 0 1px;
    background: var(--ci-border);
    align-self: stretch;
    margin: .2rem 0;
}

.ci-card-bar {
    position: relative;
    height: 8px;
    background: var(--ci-surface);
    border-radius: 99px;
    overflow: hidden;
}
.ci-card-bar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width .6s ease;
}
.ci-card-bar-fill--low { background: linear-gradient(90deg, #a7d4ff, #5e91de); }
.ci-card-bar-fill--mid { background: linear-gradient(90deg, #5e91de, var(--ci-primary)); }
.ci-card-bar-fill--high { background: linear-gradient(90deg, var(--ci-primary), var(--ci-primary-dark)); }
.ci-card-bar-fill--full { background: linear-gradient(90deg, #fcd34d, var(--ci-warn)); }
.ci-card-bar-pct {
    position: absolute;
    right: .3rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: .62rem;
    font-weight: 700;
    color: var(--ci-text);
    background: #fff;
    padding: 0 .3rem;
    border-radius: 4px;
}

.ci-card-footer {
    font-size: .75rem;
    color: var(--ci-muted);
    display: flex;
    align-items: center;
    gap: .35rem;
    padding-top: .5rem;
    border-top: 1px solid var(--ci-surface);
}
.ci-card-footer i { font-size: .72rem; }

/* =========================================
   LOAD MORE (restylé)
   ========================================= */
.ci-load-more-container {
    text-align: center;
    margin-top: 1.75rem;
}
.ci-load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .75rem 1.75rem;
    background: #fff;
    border: 1px solid var(--ci-border);
    border-radius: 99px;
    color: var(--ci-primary);
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    box-shadow: 0 1px 3px rgba(15,23,42,.05);
}
.ci-load-more-btn:hover {
    background: var(--ci-primary);
    color: #fff;
    border-color: var(--ci-primary);
    box-shadow: 0 6px 18px rgba(4,83,203,.25);
    transform: translateY(-1px);
}
.ci-load-more-spinner {
    padding: 1rem;
}

/* Empty state */
.ci-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    background: #fff;
    border: 2px dashed var(--ci-border);
    border-radius: 14px;
}
.ci-empty-icon {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: var(--ci-surface);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--ci-muted);
    font-size: 1.75rem;
    margin-bottom: 1rem;
}
.ci-empty-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ci-text);
    margin-bottom: .35rem;
}
.ci-empty-text {
    font-size: .88rem;
    color: var(--ci-muted);
    margin-bottom: 1rem;
}

/* =========================================
   MODALS monochrome
   ========================================= */
.ci-modal-header {
    background: linear-gradient(135deg, #0a3d8f 0%, var(--ci-primary) 100%);
    color: #fff;
    border-bottom: none;
    padding: 1rem 1.5rem;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
}
.ci-modal-header .modal-title {
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
}
.ci-modal-header .btn-close-white { filter: brightness(2); }

.ci-info-box {
    display: flex;
    gap: .6rem;
    padding: .8rem 1rem;
    background: rgba(4,83,203,.06);
    border-left: 3px solid var(--ci-primary);
    border-radius: 8px;
    color: var(--ci-text);
    font-size: .85rem;
    line-height: 1.5;
    margin-top: .75rem;
}
.ci-info-box i {
    color: var(--ci-primary);
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: .15rem;
}

.ci-steps {
    padding-left: 1.2rem;
    line-height: 1.8;
    color: var(--ci-text);
    font-size: .88rem;
}

/* Overcapacity modal table (monochrome) */
.ci-overcapacity-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .85rem;
}
.ci-overcapacity-table th {
    background: var(--ci-surface);
    color: var(--ci-muted);
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: .6rem .8rem;
    border-bottom: 1px solid var(--ci-border);
    text-align: left;
}
.ci-overcapacity-table td {
    padding: .7rem .8rem;
    border-bottom: 1px solid var(--ci-surface);
    vertical-align: middle;
}
.ci-badge {
    display: inline-block;
    padding: .2rem .55rem;
    border-radius: 6px;
    font-size: .75rem;
    font-weight: 600;
}
.ci-badge--muted { background: var(--ci-surface); color: var(--ci-muted); }
.ci-badge--primary { background: rgba(4,83,203,.12); color: var(--ci-primary); }
.ci-badge--warn { background: #fef3c7; color: #92400e; }
.ci-badge--danger { background: #fee2e2; color: #991b1b; }
.ci-badge--success { background: rgba(16,185,129,.12); color: #065f46; }

/* =========================================
   RESPONSIVE
   ========================================= */
@media (max-width: 992px) {
    .ci-hero { padding: 1.5rem 1.5rem 1.25rem; }
    .ci-hero h1 { font-size: 1.3rem; }
}
@media (max-width: 768px) {
    .ci-hero-top { flex-direction: column; align-items: stretch; }
    .ci-hero-actions { justify-content: flex-start; }
    .ci-kpis { grid-template-columns: repeat(2, 1fr); }
    .ci-grid { grid-template-columns: 1fr; }
    .ci-search { flex: 1 1 100%; }
    .ci-filter-select { flex: 1 1 calc(50% - .3rem); min-width: 0; }
}
@media (max-width: 480px) {
    .ci-hero { padding: 1.25rem; }
    .ci-hero h1 { font-size: 1.15rem; }
    .ci-kpis { grid-template-columns: 1fr; }
    .ci-hero-icon { width: 44px; height: 44px; font-size: 1.1rem; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- =========================
             HERO + KPIs
             ========================= --}}
        <div class="ci-hero">
            <div class="ci-hero-top">
                <div class="ci-hero-left">
                    <div class="ci-hero-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <h1>Gestion des classes</h1>
                        <p>Organisation et suivi par filière et niveau</p>
                        @if($anneeAcademique)
                            <span class="ci-hero-chip">
                                <i class="fas fa-calendar-alt"></i>
                                Année {{ $anneeAcademique }}
                                <button type="button" class="ci-hero-chip-btn" data-bs-toggle="modal" data-bs-target="#yearChangeModal" title="Comment changer d'année ?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </span>
                        @else
                            <span class="ci-hero-chip ci-hero-chip--warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Aucune année universitaire définie
                                <button type="button" class="ci-hero-chip-btn" data-bs-toggle="modal" data-bs-target="#yearChangeModal" title="Comment définir une année courante ?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="ci-hero-actions">
                    @can('classes.edit')
                        <form action="{{ route('esbtp.classes.sync-systeme-academique') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="ci-btn--glass" title="Synchroniser BTS/LMD depuis les niveaux d'études">
                                <i class="fas fa-sync-alt"></i>Sync BTS/LMD
                            </button>
                        </form>
                    @endcan
                    @can('classes.create')
                        <button type="button" class="ci-btn--white" id="btn-open-create-modal">
                            <i class="fas fa-plus-circle"></i>Nouvelle classe
                        </button>
                    @endcan
                </div>
            </div>

            <div class="ci-kpis">
                <div class="ci-kpi">
                    <div class="ci-kpi-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="ci-kpi-body">
                        <div class="ci-kpi-value">{{ $kpiStats['totalClasses'] }}</div>
                        <div class="ci-kpi-label">{{ $kpiStats['classesActives'] }} actives</div>
                    </div>
                </div>
                <div class="ci-kpi">
                    <div class="ci-kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="ci-kpi-body">
                        <div class="ci-kpi-value">{{ $kpiStats['totalEtudiants'] }}</div>
                        <div class="ci-kpi-label">Étudiants inscrits</div>
                    </div>
                </div>
                <div class="ci-kpi">
                    <div class="ci-kpi-icon"><i class="fas fa-chair"></i></div>
                    <div class="ci-kpi-body">
                        <div class="ci-kpi-value">{{ $kpiStats['placesDisponibles'] }}</div>
                        <div class="ci-kpi-label">sur {{ $kpiStats['totalPlaces'] }} places</div>
                        <div class="ci-kpi-bar"><div class="ci-kpi-bar-fill" style="width: {{ $kpiStats['tauxOccupation'] }}%"></div></div>
                    </div>
                </div>
                <div class="ci-kpi">
                    <div class="ci-kpi-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="ci-kpi-body">
                        <div class="ci-kpi-value">{{ $kpiStats['tauxOccupation'] }}%</div>
                        <div class="ci-kpi-label">Taux d'occupation</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messages flash --}}
        @if(session('success'))
            <div class="ci-alert ci-alert--success">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="ci-alert ci-alert--danger">
                <i class="fas fa-exclamation-triangle"></i>{{ session('error') }}
            </div>
        @endif

        {{-- Bannière overcapacité (chargée en JS, masquée par défaut) --}}
        <div id="overcapacity-warning" class="ci-banner" style="display: none;">
            <div class="ci-banner-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="ci-banner-body">
                <div class="ci-banner-title" id="overcapacity-title">Classes en surcapacité détectées</div>
                <div class="ci-banner-text" id="overcapacity-message">Certaines classes ont dépassé leur capacité maximale autorisée.</div>
            </div>
            <div class="ci-banner-actions">
                <button type="button" class="ci-btn--outline" onclick="showOvercapacityModal()">
                    <i class="fas fa-list"></i>Voir les détails
                </button>
                <button type="button" class="ci-banner-close" onclick="dismissOvercapacityWarning()" title="Ignorer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- =========================
             TOOLBAR (filtres + export)
             ========================= --}}
        <form method="GET" action="{{ route('esbtp.classes.index') }}" id="filtersForm" class="ci-toolbar">
            <div class="ci-toolbar-row">
                <div class="ci-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Rechercher une classe (nom ou code)..." autocomplete="off">
                </div>

                <select name="filiere_id" id="filiere_id" class="ci-filter-select">
                    <option value="">Toutes les filières</option>
                    @foreach($filieres as $filiere)
                        <option value="{{ $filiere->id }}" @selected(request('filiere_id') == $filiere->id)>{{ $filiere->name }}</option>
                    @endforeach
                </select>

                <select name="niveau_id" id="niveau_id" class="ci-filter-select">
                    <option value="">Tous les niveaux</option>
                    @foreach($niveaux as $niveau)
                        <option value="{{ $niveau->id }}" @selected(request('niveau_id') == $niveau->id)>{{ $niveau->name }}</option>
                    @endforeach
                </select>

                <select name="statut" id="statut" class="ci-filter-select">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected(request('statut') == 'active')>Actives</option>
                    <option value="inactive" @selected(request('statut') == 'inactive')>Inactives</option>
                </select>

                <select name="capacite" id="capacite" class="ci-filter-select">
                    <option value="">Toutes capacités</option>
                    <option value="disponible" @selected(request('capacite') == 'disponible')>Places disponibles</option>
                    <option value="pleine" @selected(request('capacite') == 'pleine')>Classes pleines</option>
                </select>

                <button type="button" id="reset-filters-btn" class="ci-btn--ghost" title="Réinitialiser les filtres">
                    <i class="fas fa-rotate-left"></i>
                </button>

                <div class="dropdown">
                    <button type="button" class="ci-btn--ghost" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download"></i>Exporter
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end ci-dropdown">
                        <li><button type="button" class="dropdown-item" onclick="exportClasses('excel')"><i class="fas fa-file-excel"></i>Excel (.xlsx)</button></li>
                        <li><button type="button" class="dropdown-item" onclick="exportClasses('csv')"><i class="fas fa-file-csv"></i>CSV</button></li>
                        <li><button type="button" class="dropdown-item" onclick="exportClasses('pdf')"><i class="fas fa-file-pdf"></i>PDF</button></li>
                    </ul>
                </div>

                {{-- Bouton submit caché pour maintenir compatibilité form AJAX --}}
                <button type="submit" style="display:none;" aria-hidden="true">Filtrer</button>
            </div>

            <div class="ci-toolbar-meta">
                <span class="ci-count"><i class="fas fa-list"></i><span id="classes-count">{{ $classes->count() }}</span> classe(s) trouvée(s)</span>
            </div>
        </form>

        {{-- =========================
             GRID RÉSULTATS
             ========================= --}}
        <div id="classes-results" class="ci-results">
            @include('esbtp.classes.partials.results', ['classes' => $classes])
        </div>

    </div>
</div>

{{-- ========================================
     MODAL CRÉATION CLASSE (AJAX)
     ======================================== --}}
<div class="modal fade" id="createClasseModal" tabindex="-1" aria-labelledby="createClasseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header ci-modal-header">
                <h5 class="modal-title" id="createClasseModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nouvelle classe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="modal-create-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary" id="modal-create-submit-btn" disabled>
                    <i class="fas fa-save me-1"></i>Enregistrer la classe
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================
     MODAL ÉDITION CLASSE (AJAX)
     ======================================== --}}
<div class="modal fade" id="editClasseModal" tabindex="-1" aria-labelledby="editClasseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header ci-modal-header">
                <h5 class="modal-title" id="editClasseModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifier la classe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="modal-edit-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary" id="modal-edit-submit-btn" disabled>
                    <i class="fas fa-save me-1"></i>Mettre à jour la classe
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================
     MODAL OVERCAPACITY (détails surcapacité)
     ======================================== --}}
<div class="modal fade" id="overcapacityModal" tabindex="-1" aria-labelledby="overcapacityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ci-modal-header">
                <h5 class="modal-title" id="overcapacityModalLabel">
                    <i class="fas fa-triangle-exclamation me-2"></i>Classes en surcapacité
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="ci-info-box mb-3" style="margin-top:0;">
                    <i class="fas fa-info-circle"></i>
                    <div><strong>Attention :</strong> les classes ci-dessous ont dépassé leur capacité maximale autorisée.</div>
                </div>
                <div id="overcapacity-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <div class="mt-2 text-muted">Chargement des classes en surcapacité...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal "Changer d'année" (partial partagé) --}}
@include('esbtp.classes.partials.year-change-modal')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // Chargement initial des classes en surcapacité
    // =============================================
    loadOvercapacityClasses();

    function loadOvercapacityClasses() {
        fetch('{{ route("esbtp.classes.overcapacity") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.classes.length > 0) {
                    const warning = document.getElementById('overcapacity-warning');
                    warning.style.display = 'flex';

                    document.getElementById('overcapacity-title').textContent =
                        `${data.classes.length} classe(s) en surcapacité (${data.annee_universitaire})`;
                    document.getElementById('overcapacity-message').textContent = data.message;

                    loadOvercapacityModalContent(data.classes);
                }
            })
            .catch(error => {
                console.error('Erreur chargement classes surcapacité:', error);
            });
    }

    function loadOvercapacityModalContent(classes) {
        const content = document.getElementById('overcapacity-content');

        if (classes.length === 0) {
            content.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-check-circle" style="font-size:2.5rem;color:#10b981;margin-bottom:.75rem;"></i>
                    <h5 style="color:#065f46;">Aucune classe en surcapacité</h5>
                    <p class="text-muted">Toutes les classes respectent leur capacité maximale.</p>
                </div>
            `;
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="ci-overcapacity-table">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th class="text-center">Capacité</th>
                            <th class="text-center">Inscrits</th>
                            <th class="text-center">Taux</th>
                            <th class="text-center">Dépassement</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        classes.forEach(classe => {
            const tauxClass = classe.taux_occupation >= 150 ? 'danger' : 'warn';
            html += `
                <tr>
                    <td><strong>${classe.nom}</strong></td>
                    <td>${classe.filiere}</td>
                    <td>${classe.niveau}</td>
                    <td class="text-center"><span class="ci-badge ci-badge--muted">${classe.places_totales}</span></td>
                    <td class="text-center"><span class="ci-badge ci-badge--primary">${classe.inscriptions_actives}</span></td>
                    <td class="text-center"><span class="ci-badge ci-badge--${tauxClass}">${classe.taux_occupation}%</span></td>
                    <td class="text-center"><span class="ci-badge ci-badge--danger">+${classe.depassement}</span></td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div class="ci-info-box">
                <i class="fas fa-lightbulb"></i>
                <div>
                    <strong>Recommandation :</strong>
                    <ul class="mb-0 mt-2" style="padding-left:1.1rem;line-height:1.6;">
                        <li>Envisager d'augmenter la capacité des classes concernées</li>
                        <li>Créer des classes supplémentaires si nécessaire</li>
                        <li>Les superAdmins et secrétaires peuvent contourner cette limite</li>
                    </ul>
                </div>
            </div>
        `;

        content.innerHTML = html;
    }

    // Expose pour appels inline
    window.showOvercapacityModal = function() {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('overcapacityModal'));
        modal.show();
    };
    window.dismissOvercapacityWarning = function() {
        document.getElementById('overcapacity-warning').style.display = 'none';
    };
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filtersForm');
    const resultsContainer = document.getElementById('classes-results');
    const submitButton = form.querySelector('button[type="submit"]');
    const filterInputs = form.querySelectorAll('select, input[name="search"]');
    const resetBtn = document.getElementById('reset-filters-btn');
    const classesCountSpan = document.getElementById('classes-count');

    let currentPage = 1;
    let hasMorePages = false;
    let isLoading = false;

    // Debounce recherche
    let searchDebounce = null;

    function getLoadMoreBtn() { return document.getElementById('load-more-btn'); }
    function getLoadMoreSpinner() { return document.getElementById('load-more-spinner'); }

    // Select2 si disponible (guardé — fonctionne sans)
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('#filiere_id, #niveau_id, #statut, #capacite').select2({
            theme: 'bootstrap4',
            placeholder: 'Sélectionner une option',
            allowClear: true,
            minimumResultsForSearch: Infinity
        });
    }

    function setLoading(loading) {
        isLoading = loading;
    }

    function updateLoadMoreButton(hasMore) {
        hasMorePages = hasMore;
        const btn = getLoadMoreBtn();
        const spinner = getLoadMoreSpinner();
        if (btn && spinner) {
            btn.style.display = hasMore ? 'inline-flex' : 'none';
            spinner.classList.add('d-none');
        }
    }

    function fetchResults(reset = true) {
        if (isLoading) return;
        setLoading(true);

        if (reset) {
            currentPage = 1;
            const grid = document.getElementById('classes-grid');
            if (grid) grid.innerHTML = '';
        }

        const formData = new FormData(form);
        formData.set('page', currentPage);
        const params = new URLSearchParams(formData);
        const targetUrl = `${form.action}?${params.toString()}`;

        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) throw new Error('Erreur chargement classes.');
            return response.json();
        })
        .then(data => {
            if (reset) {
                resultsContainer.innerHTML = `
                    <div class="ci-grid" id="classes-grid">
                        ${data.html}
                    </div>
                    <div id="load-more-container" class="ci-load-more-container">
                        <button type="button" id="load-more-btn" class="ci-load-more-btn" style="display: none;">
                            <i class="fas fa-angle-down"></i>Charger plus de classes
                        </button>
                        <div id="load-more-spinner" class="ci-load-more-spinner d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                const grid = document.getElementById('classes-grid');
                if (grid) grid.insertAdjacentHTML('beforeend', data.html);
            }

            bindLoadMore();
            updateLoadMoreButton(data.hasMore);

            if (typeof data.total !== 'undefined' && classesCountSpan) {
                classesCountSpan.textContent = data.total;
            }

            setLoading(false);
        })
        .catch(error => {
            console.error('Erreur chargement classes:', error);
            setLoading(false);
        });
    }

    function bindLoadMore() {
        const btn = getLoadMoreBtn();
        const spinner = getLoadMoreSpinner();
        if (!btn || !spinner) return;

        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', function() {
            if (isLoading || !hasMorePages) return;
            newBtn.style.display = 'none';
            spinner.classList.remove('d-none');
            currentPage++;
            fetchResults(false);
        });
    }

    // Submit form intercepté
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        fetchResults(true);
        return false;
    });

    // Change sur filtres → fetch immédiat
    // Input recherche → debounce 400ms
    filterInputs.forEach((input) => {
        if (input.tagName === 'INPUT' && input.name === 'search') {
            input.addEventListener('input', () => {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(() => fetchResults(true), 400);
            });
        } else {
            input.addEventListener('change', () => fetchResults(true));
        }
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            form.reset();
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                $('#filiere_id, #niveau_id, #statut, #capacite').val(null).trigger('change');
            }
            fetchResults(true);
        });
    }

    bindLoadMore();
    const initialBtn = getLoadMoreBtn();
    if (initialBtn) {
        const initialHasMore = initialBtn.getAttribute('data-has-more') === 'true';
        updateLoadMoreButton(initialHasMore);
    }
});

// =============================================
// Export (déclaré globalement pour onclick inline)
// =============================================
function exportClasses(format) {
    const urlParams = new URLSearchParams(window.location.search);
    const urls = {
        excel: '{{ route("esbtp.classes.export.excel") }}',
        csv: '{{ route("esbtp.classes.export.csv") }}',
        pdf: '{{ route("esbtp.classes.export.pdf") }}'
    };
    const exportUrl = urls[format];
    if (!exportUrl) {
        console.error('Format export inconnu:', format);
        return;
    }

    const exportParams = new URLSearchParams();
    ['filiere_id', 'niveau_id', 'statut', 'capacite', 'search'].forEach(key => {
        if (urlParams.has(key)) exportParams.set(key, urlParams.get(key));
    });

    const finalUrl = exportParams.toString() ? `${exportUrl}?${exportParams.toString()}` : exportUrl;
    window.location.href = finalUrl;
}
</script>

<script>
// ============================================================
// GESTION DES MODALS AJAX (Création + Édition) — BS5 natif
// ============================================================

function initClasseFormScripts(formId) {
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $(`#${formId}_filiere_id, #${formId}_niveau_etude_id, #${formId}_annee_universitaire_id`).select2({
            theme: 'bootstrap4',
            placeholder: 'Sélectionner une option',
            allowClear: true,
            dropdownParent: formId.includes('modal') ? $(`#${formId}`).closest('.modal') : undefined
        });

        // Select2 change events don't always propagate to native addEventListener.
        // Wire the niveau toggle explicitly so the parcours group and badge update correctly.
        $(`#${formId}_niveau_etude_id`).on('change', function() {
            var niveauTypes = JSON.parse(this.dataset.niveauTypes || '{}');
            var type = niveauTypes[this.value] || '';
            var isLMD = (type === 'Licence' || type === 'Master' || type === 'Doctorat');
            var badge = document.getElementById(formId + '_systeme_badge');
            if (badge) {
                badge.innerHTML = '<span class="badge ' + (isLMD ? 'bg-primary' : 'bg-secondary') + '" style="font-size:0.85rem;padding:0.4em 0.8em;">' + (isLMD ? 'LMD' : (type ? 'BTS' : '—')) + '</span>';
            }
            var parcoursGroup = document.getElementById(formId + '_parcours_group');
            if (parcoursGroup) {
                parcoursGroup.style.display = isLMD ? '' : 'none';
                if (!isLMD) {
                    var sel = document.getElementById(formId + '_parcours_id');
                    if (sel) sel.value = '';
                }
            }
        });
    }

    $(`#${formId}_name`).on('blur', function() {
        const codeInput = $(`#${formId}_code`);
        if (codeInput.val() === '') {
            const name = $(this).val();
            if (name) {
                const code = name.split(' ').map(w => w.charAt(0).toUpperCase()).join('');
                codeInput.val(code);
            }
        }
    });
}

function displayValidationErrors(errors, formId) {
    $(`#${formId} .is-invalid`).removeClass('is-invalid');
    $(`#${formId} .invalid-feedback`).remove();
    for (const [field, messages] of Object.entries(errors)) {
        const input = document.getElementById(`${formId}_${field}`);
        if (input) {
            input.classList.add('is-invalid');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = messages[0];
            input.parentNode.appendChild(errorDiv);
        }
    }
}

function showSuccessMessage(message) {
    const alertHtml = `
        <div class="ci-alert ci-alert--success" role="alert">
            <i class="fas fa-check-circle"></i>${message}
        </div>
    `;
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertAdjacentHTML('afterbegin', alertHtml);
        setTimeout(() => {
            const alert = mainContent.querySelector('.ci-alert');
            if (alert) alert.remove();
        }, 5000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const btnOpenCreateModal = document.getElementById('btn-open-create-modal');
    const createModalEl = document.getElementById('createClasseModal');
    if (!createModalEl) return;

    const createClasseModal = new bootstrap.Modal(createModalEl);
    const modalCreateBody = document.getElementById('modal-create-body');
    const modalCreateSubmitBtn = document.getElementById('modal-create-submit-btn');

    const editModalEl = document.getElementById('editClasseModal');
    const editClasseModal = new bootstrap.Modal(editModalEl);
    const modalEditBody = document.getElementById('modal-edit-body');
    const modalEditSubmitBtn = document.getElementById('modal-edit-submit-btn');

    if (btnOpenCreateModal) {
        btnOpenCreateModal.addEventListener('click', function() {
            modalCreateBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            `;
            modalCreateSubmitBtn.disabled = true;

            fetch('{{ route("esbtp.classes.create") }}?ajax=1', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.text();
            })
            .then(html => {
                modalCreateBody.innerHTML = html;
                initClasseFormScripts('modal-create-classe-form');
                modalCreateSubmitBtn.disabled = false;
                createClasseModal.show();
            })
            .catch(error => {
                console.error('Erreur chargement formulaire création:', error);
                modalCreateBody.innerHTML = `
                    <div class="ci-alert ci-alert--danger">
                        <i class="fas fa-exclamation-triangle"></i>Erreur lors du chargement du formulaire. Veuillez réessayer.
                    </div>
                `;
            });
        });
    }

    if (modalCreateSubmitBtn) {
        modalCreateSubmitBtn.addEventListener('click', function() {
            const form = document.getElementById('modal-create-classe-form');
            if (form) form.requestSubmit();
        });
    }

    // Intercept submit create form (capture phase)
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'modal-create-classe-form') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const form = e.target;
            modalCreateSubmitBtn.disabled = true;
            modalCreateSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    throw new Error('Réponse non-JSON');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    modalCreateSubmitBtn.disabled = false;
                    modalCreateSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Enregistrer la classe';
                    createClasseModal.hide();
                    addNewClasseCard(data.classe, data.message || 'La classe a été créée avec succès.');
                } else {
                    displayValidationErrors(data.errors, 'modal-create-classe-form');
                    modalCreateSubmitBtn.disabled = false;
                    modalCreateSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Enregistrer la classe';
                }
            })
            .catch(error => {
                console.error('Erreur soumission formulaire:', error);
                alert('Une erreur est survenue lors de l\'enregistrement.');
                modalCreateSubmitBtn.disabled = false;
                modalCreateSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Enregistrer la classe';
            });

            return false;
        }
    }, true);

    // Click handler délégation pour .btn-open-edit-modal
    document.addEventListener('click', function(e) {
        const btnEdit = e.target.closest('.btn-open-edit-modal');
        if (!btnEdit) return;

        e.preventDefault();
        const classeId = btnEdit.getAttribute('data-classe-id');
        if (!classeId) return;

        modalEditBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `;
        modalEditSubmitBtn.disabled = true;
        modalEditSubmitBtn.setAttribute('data-classe-id', classeId);

        fetch(`/esbtp/classes/${classeId}/edit?ajax=1`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.text();
        })
        .then(html => {
            modalEditBody.innerHTML = html;
            initClasseFormScripts('modal-edit-classe-form');
            modalEditSubmitBtn.disabled = false;
            editClasseModal.show();
        })
        .catch(error => {
            console.error('Erreur chargement formulaire édition:', error);
            modalEditBody.innerHTML = `
                <div class="ci-alert ci-alert--danger">
                    <i class="fas fa-exclamation-triangle"></i>Erreur lors du chargement du formulaire.
                </div>
            `;
        });
    });

    if (modalEditSubmitBtn) {
        modalEditSubmitBtn.addEventListener('click', function() {
            const form = document.getElementById('modal-edit-classe-form');
            if (form) form.requestSubmit();
        });
    }

    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'modal-edit-classe-form') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const form = e.target;
            modalEditSubmitBtn.disabled = true;
            modalEditSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mise à jour...';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalEditSubmitBtn.disabled = false;
                    modalEditSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Mettre à jour la classe';
                    editClasseModal.hide();
                    updateClasseCard(data.classe, data.message || 'La classe a été mise à jour.');
                } else {
                    displayValidationErrors(data.errors, 'modal-edit-classe-form');
                    modalEditSubmitBtn.disabled = false;
                    modalEditSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Mettre à jour la classe';
                }
            })
            .catch(error => {
                console.error('Erreur soumission formulaire:', error);
                alert('Une erreur est survenue lors de la mise à jour.');
                modalEditSubmitBtn.disabled = false;
                modalEditSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Mettre à jour la classe';
            });

            return false;
        }
    }, true);
});

// ============================================================
// Fonctions refresh card (création + édition)
// ============================================================

function addNewClasseCard(classe, successMessage = null) {
    const classeId = classe.id;
    const refreshUrl = `/esbtp/classes/${classeId}/refresh-ligne`;
    const resultsContainer = document.querySelector('#classes-grid');

    if (!resultsContainer) {
        window.location.reload();
        return;
    }

    fetch(refreshUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(response => response.ok ? response.json() : Promise.reject(new Error(`HTTP ${response.status}`)))
    .then(data => {
        if (!data.success || !data.html) throw new Error(data.message || 'Réponse invalide');

        const template = document.createElement('template');
        template.innerHTML = data.html.trim();

        let newCardFragment = template.content.querySelector(`[data-classe-id="${classeId}"]`);
        if (!newCardFragment) newCardFragment = template.content.querySelector('.ci-card');
        if (!newCardFragment) throw new Error('HTML sans carte valide');

        const newCard = newCardFragment.cloneNode(true);

        const modalFragment = template.content.querySelector(`#deleteModal${classeId}`);
        if (modalFragment) {
            document.body.appendChild(modalFragment.cloneNode(true));
        }

        resultsContainer.insertBefore(newCard, resultsContainer.firstChild);

        // Animation insertion
        newCard.style.opacity = '0';
        newCard.style.transform = 'translateY(-20px)';
        newCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease, background-color 0.3s ease';
        newCard.offsetHeight;
        newCard.style.opacity = '1';
        newCard.style.transform = 'translateY(0)';
        newCard.style.backgroundColor = 'rgba(16, 185, 129, 0.08)';
        setTimeout(() => { newCard.style.backgroundColor = ''; }, 1000);

        if (successMessage) showSuccessMessage(successMessage);
        newCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(error => {
        console.error('Erreur ajout carte:', error);
        window.location.reload();
    });
}

function updateClasseCard(classe, successMessage = null) {
    const classeId = classe.id;
    const refreshUrl = `/esbtp/classes/${classeId}/refresh-ligne`;
    const existingCard = document.querySelector(`[data-classe-id="${classeId}"]`);

    if (!existingCard) {
        window.location.reload();
        return;
    }

    fetch(refreshUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(response => response.ok ? response.json() : Promise.reject(new Error(`HTTP ${response.status}`)))
    .then(data => {
        if (!data.success || !data.html) throw new Error(data.message || 'Réponse invalide');

        const template = document.createElement('template');
        template.innerHTML = data.html.trim();

        let newCardFragment = template.content.querySelector(`[data-classe-id="${classeId}"]`);
        if (!newCardFragment) newCardFragment = template.content.querySelector('.ci-card');
        if (!newCardFragment) throw new Error('HTML sans carte valide');

        const newCard = newCardFragment.cloneNode(true);

        const modalFragment = template.content.querySelector(`#deleteModal${classeId}`);
        if (modalFragment) {
            const newModal = modalFragment.cloneNode(true);
            const existingModal = document.getElementById(`deleteModal${classeId}`);
            if (existingModal) existingModal.replaceWith(newModal);
            else document.body.appendChild(newModal);
        }

        existingCard.replaceWith(newCard);

        newCard.style.transition = 'background-color 0.3s ease';
        newCard.style.backgroundColor = 'rgba(16, 185, 129, 0.08)';
        setTimeout(() => { newCard.style.backgroundColor = ''; }, 500);

        if (successMessage) showSuccessMessage(successMessage);
    })
    .catch(error => {
        console.error('Erreur refresh carte:', error);
        window.location.reload();
    });
}
</script>
@endpush
