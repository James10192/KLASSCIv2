@extends('layouts.app')

@section('title', 'Gestion des inscriptions - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* =====================================================================
   INSCRIPTIONS INDEX — namespace ii-*
   Design premium monochrome bleu KLASSCI
   Variables sur :root pour etre accessibles aussi bien dans
   .dashboard-acasi que dans les modals et la bulk-bar (hors wrapper).
   ===================================================================== */
:root {
    --ii-primary: #0453cb;
    --ii-primary-dark: #033a8e;
    --ii-accent: #3b7ddb;
    --ii-text: #1e293b;
    --ii-muted: #64748b;
    --ii-surface: #f8fafc;
    --ii-border: #e2e8f0;
    --ii-success: #10b981;
    --ii-warn: #f59e0b;
    --ii-danger: #ef4444;
}

/* ========== HERO ========== */
.ii-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.ii-hero::before {
    content: ''; position: absolute; top: -40px; right: -40px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,.06); pointer-events: none;
}
.ii-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1; }
.ii-hero-left { display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 260px; }
.ii-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.ii-hero h1 { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 0 .2rem; }
.ii-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: 0 0 .5rem; }
.ii-hero-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .3rem .7rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 99px; color: #fff;
    font-size: .78rem; font-weight: 500;
}
.ii-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.ii-btn--glass, .ii-btn--white {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1.1rem; border-radius: 10px;
    font-size: .83rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    transition: all .2s ease; border: 1px solid transparent;
}
.ii-btn--glass { background: rgba(255,255,255,.12); color: #fff; border-color: rgba(255,255,255,.18); }
.ii-btn--glass:hover { background: rgba(255,255,255,.2); color: #fff; transform: translateY(-1px); }
.ii-btn--white { background: #fff; color: var(--ii-primary); }
.ii-btn--white:hover { background: #f1f5f9; color: var(--ii-primary-dark); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.12); }

/* ========== KPIs cliquables ========== */
.ii-kpis {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .7rem; margin-top: 1.5rem;
    position: relative; z-index: 1;
}
.ii-kpi {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .85rem 1rem;
    display: flex; align-items: center; gap: .6rem;
    cursor: pointer; transition: all .2s;
    text-decoration: none; color: inherit;
    width: 100%; text-align: left;
    font-family: inherit; font-size: inherit;
}
.ii-kpi:hover { background: rgba(255,255,255,.18); transform: translateY(-1px); color: inherit; }
.ii-kpi--active {
    background: #fff !important;
    color: var(--ii-primary) !important;
    border-color: #fff !important;
    box-shadow: 0 6px 16px rgba(0,0,0,.18);
}
.ii-kpi--active .ii-kpi-icon { background: rgba(4,83,203,.1); color: var(--ii-primary); }
.ii-kpi--active .ii-kpi-value { color: var(--ii-primary); }
.ii-kpi--active .ii-kpi-label { color: var(--ii-muted); }
.ii-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(255,255,255,.14);
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; color: #fff; flex-shrink: 0;
    transition: all .2s;
}
.ii-kpi-body { flex: 1; min-width: 0; }
.ii-kpi-value { font-size: 1.4rem; font-weight: 700; color: #fff; line-height: 1; }
.ii-kpi-label { font-size: .7rem; color: rgba(255,255,255,.7); margin-top: .2rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 500; }
.ii-kpi-badge {
    position: absolute; top: .4rem; right: .4rem;
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--ii-warn);
    box-shadow: 0 0 0 2px rgba(245,158,11,.2);
}

/* ========== ALERTS ========== */
.ii-alert {
    display: flex; align-items: center; gap: .6rem;
    padding: .85rem 1.1rem; border-radius: 12px;
    margin-bottom: 1rem; font-weight: 500; font-size: .9rem;
}
.ii-alert--success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.ii-alert--danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

.ii-banner {
    background: #fffbeb; border: 1px solid #fde68a;
    border-left: 4px solid var(--ii-warn);
    border-radius: 12px; padding: .9rem 1.25rem;
    margin-bottom: 1rem;
    display: flex; align-items: center; gap: .85rem;
}
.ii-banner-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: #fef3c7;
    display: flex; align-items: center; justify-content: center;
    color: var(--ii-warn); flex-shrink: 0; font-size: 1rem;
}
.ii-banner-body { flex: 1; }
.ii-banner-title { font-weight: 700; color: #92400e; margin-bottom: .15rem; font-size: .9rem; }
.ii-banner-text { color: #78350f; font-size: .82rem; line-height: 1.4; }

/* ========== TOOLBAR ========== */
.ii-toolbar {
    background: #fff; border: 1px solid var(--ii-border);
    border-radius: 14px; padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.ii-toolbar-row {
    display: flex; gap: .6rem; flex-wrap: wrap; align-items: center;
}
.ii-search { position: relative; flex: 1 1 260px; min-width: 220px; }
.ii-search i {
    position: absolute; left: .9rem; top: 50%;
    transform: translateY(-50%); color: var(--ii-muted);
    font-size: .88rem; pointer-events: none;
}
.ii-search input {
    width: 100%; padding: .6rem .9rem .6rem 2.3rem;
    border: 1px solid var(--ii-border); border-radius: 10px;
    font-size: .88rem; background: var(--ii-surface);
    transition: all .2s;
}
.ii-search input:focus {
    outline: none; background: #fff;
    border-color: var(--ii-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.ii-filter-select {
    padding: .6rem .9rem;
    border: 1px solid var(--ii-border); border-radius: 10px;
    font-size: .85rem; background: var(--ii-surface);
    cursor: pointer; appearance: none;
    color: var(--ii-text); transition: all .2s;
    min-width: 140px;
    background: var(--ii-surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%2364748b' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") no-repeat right .75rem center;
    padding-right: 2rem;
}
.ii-filter-select:focus { outline: none; border-color: var(--ii-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.ii-btn--ghost {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .55rem .85rem;
    border: 1px solid var(--ii-border); border-radius: 10px;
    background: #fff; color: var(--ii-text);
    font-size: .83rem; font-weight: 500;
    cursor: pointer; transition: all .2s; text-decoration: none;
}
.ii-btn--ghost:hover { border-color: var(--ii-primary); color: var(--ii-primary); background: rgba(4,83,203,.04); }

.ii-toolbar-meta {
    margin-top: .75rem; padding-top: .75rem;
    border-top: 1px dashed var(--ii-border);
    font-size: .78rem; color: var(--ii-muted);
    display: flex; gap: 1rem; align-items: center;
    flex-wrap: wrap;
}
.ii-chips-active { display: flex; gap: .3rem; flex-wrap: wrap; }
.ii-chip-active {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .55rem;
    background: rgba(4,83,203,.08); color: var(--ii-primary);
    border-radius: 99px; font-size: .72rem; font-weight: 600;
}
.ii-chip-active button {
    background: transparent; border: none; padding: 0;
    color: inherit; cursor: pointer;
    display: inline-flex; align-items: center;
}

/* ========== TABLE ========== */
.ii-results-card {
    background: #fff; border: 1px solid var(--ii-border);
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.ii-table-wrap { overflow-x: auto; }
/* Quand un dropdown kebab est ouvert : liberer l'overflow pour que le menu ne soit pas coupe */
.ii-results-card.ii-has-open-dropdown { overflow: visible; }
.ii-table-wrap.ii-has-open-dropdown { overflow: visible; }
.ii-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.ii-table thead th {
    background: var(--ii-surface);
    color: var(--ii-muted);
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    padding: .8rem 1rem;
    border-bottom: 1px solid var(--ii-border);
    text-align: left;
    white-space: nowrap;
}
.ii-sort-link {
    color: inherit; text-decoration: none;
    display: inline-flex; align-items: center; gap: .3rem;
    cursor: pointer;
}
.ii-sort-link:hover { color: var(--ii-primary); }
.ii-sort-icon { font-size: .7rem; }
.ii-sort-icon--neutral { opacity: .3; }
.ii-sort-icon--active { color: var(--ii-primary); opacity: 1; }

.ii-row { transition: background .15s; cursor: pointer; }
.ii-row:hover { background: rgba(4,83,203,.03); }
.ii-row.is-loading { opacity: .6; pointer-events: none; }
.ii-row.ii-row--error { background: rgba(239,68,68,.05); }
.ii-row.ii-row--warn { background: rgba(245,158,11,.05); }
.ii-table tbody td {
    padding: .75rem 1rem;
    border-bottom: 1px solid var(--ii-surface);
    vertical-align: middle;
    color: var(--ii-text); font-size: .87rem;
}
.ii-col-check { width: 40px; text-align: center; }

/* Étudiant cell */
.ii-etu-cell { display: flex; align-items: center; gap: .75rem; }
.ii-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .78rem;
    flex-shrink: 0; overflow: hidden;
}
.ii-etu-body { min-width: 0; }
.ii-etu-name { font-weight: 600; color: var(--ii-text); line-height: 1.2; }
.ii-etu-meta {
    display: flex; align-items: center; gap: .3rem;
    font-size: .72rem; color: var(--ii-muted); margin-top: .15rem;
}
.ii-matricule {
    font-family: 'Courier New', monospace;
    background: var(--ii-surface); padding: .1rem .35rem;
    border-radius: 4px; font-weight: 600;
}
.ii-numero { font-weight: 500; }
.ii-separator { opacity: .5; }

.ii-filiere-cell { line-height: 1.3; }
.ii-filiere-name { font-weight: 600; color: var(--ii-text); font-size: .85rem; }
.ii-filiere-niveau { font-size: .75rem; color: var(--ii-muted); }
.ii-col-annee { color: var(--ii-muted); font-size: .82rem; }

/* ========== BADGE STATUT (Blade component) ========== */
.ii-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem;
    border-radius: 99px;
    font-size: .73rem; font-weight: 700;
    white-space: nowrap;
    border: 1px solid transparent;
}
.ii-badge i { font-size: .72rem; }
.ii-badge--validee { background: var(--ii-primary); color: #fff; border-color: var(--ii-primary); }
.ii-badge--non_validee {
    background: #fff; color: var(--ii-primary);
    border-color: var(--ii-primary);
}
.ii-badge--en_attente {
    background: rgba(4,83,203,.08); color: var(--ii-primary);
    border-color: rgba(4,83,203,.2);
}
.ii-badge--annulee {
    background: #fff; color: var(--ii-muted);
    border-color: var(--ii-border);
}
.ii-badge--terminee {
    background: #f1f5f9; color: #475569;
    border-color: #cbd5e1;
}
.ii-badge--inconnu { background: #f1f5f9; color: var(--ii-muted); }

.ii-probleme-chip {
    display: inline-flex; align-items: flex-start; gap: .35rem;
    padding: .3rem .55rem;
    background: rgba(245,158,11,.1);
    color: #92400e;
    border: 1px solid rgba(245,158,11,.3);
    border-radius: 8px;
    font-size: .72rem; font-weight: 500;
    line-height: 1.35; margin-top: .35rem;
    max-width: 260px;
}
.ii-probleme-chip i { margin-top: .12rem; flex-shrink: 0; }

.ii-btn-quick {
    display: inline-flex; align-items: center; gap: .3rem;
    margin-top: .35rem;
    padding: .35rem .7rem;
    background: var(--ii-primary); color: #fff;
    border: none; border-radius: 8px;
    font-size: .72rem; font-weight: 600;
    cursor: pointer; transition: all .15s;
}
.ii-btn-quick:hover { background: var(--ii-primary-dark); transform: translateY(-1px); box-shadow: 0 2px 8px rgba(4,83,203,.25); }

/* Actions buttons */
.ii-col-actions { text-align: right; white-space: nowrap; }
.ii-actions { display: inline-flex; gap: .3rem; align-items: center; justify-content: flex-end; }
.ii-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px;
    border: 1px solid var(--ii-border);
    background: #fff; border-radius: 8px;
    cursor: pointer; transition: all .15s;
    font-size: .82rem;
    text-decoration: none;
    color: var(--ii-text);
}
.ii-btn:hover { border-color: var(--ii-primary); color: var(--ii-primary); background: rgba(4,83,203,.04); }
.ii-btn--primary {
    background: var(--ii-primary); color: #fff;
    border-color: var(--ii-primary);
}
.ii-btn--primary:hover { background: var(--ii-primary-dark); color: #fff; border-color: var(--ii-primary-dark); transform: translateY(-1px); box-shadow: 0 2px 8px rgba(4,83,203,.25); }

/* Dropdown */
.ii-dropdown {
    border-radius: 12px; border: 1px solid var(--ii-border);
    box-shadow: 0 10px 30px rgba(15,23,42,.1);
    padding: .4rem; min-width: 200px;
}
.ii-dropdown .dropdown-item {
    display: flex; align-items: center; gap: .55rem;
    padding: .55rem .75rem; border-radius: 8px;
    font-size: .85rem; color: var(--ii-text); cursor: pointer;
    width: 100%; text-align: left;
    background: transparent; border: none;
}
.ii-dropdown .dropdown-item i { width: 18px; text-align: center; color: var(--ii-muted); font-size: .88rem; }
.ii-dropdown .dropdown-item:hover { background: rgba(4,83,203,.08); color: var(--ii-primary); }
.ii-dropdown .dropdown-item:hover i { color: var(--ii-primary); }
.ii-dropdown-item--danger:hover,
.ii-dropdown-item--danger:hover i { color: #991b1b !important; background: rgba(239,68,68,.08) !important; }

/* Inscription actions wrapper spinner pattern */
.inscription-actions-wrapper { display: inline-flex; align-items: center; gap: .5rem; position: relative; }
.inscription-actions-spinner { display: none; }
.inscription-actions-wrapper.is-loading .inscription-actions-buttons { display: none !important; }
.inscription-actions-wrapper.is-loading .inscription-actions-spinner { display: flex !important; }

/* Row highlight animations — flash full-row couleur (pas d'overlay qui overflow) */
tr[data-inscription-id] { position: relative; }
tr[data-inscription-id] > td { transition: background .15s ease; }

.inscription-row-flash > td {
    animation: ii-flash 2.2s ease-in-out;
}
.inscription-row-flash.reject > td {
    animation-name: ii-flash-reject;
}
@keyframes ii-flash {
    0% { background: transparent; }
    10% { background: rgba(16,185,129,.25); }
    35% { background: rgba(16,185,129,.35); }
    70% { background: rgba(16,185,129,.18); }
    100% { background: transparent; }
}
@keyframes ii-flash-reject {
    0% { background: transparent; }
    10% { background: rgba(239,68,68,.25); }
    35% { background: rgba(239,68,68,.35); }
    70% { background: rgba(239,68,68,.18); }
    100% { background: transparent; }
}

/* ========== FOOTER ========== */
.ii-table-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--ii-border);
    background: var(--ii-surface);
    flex-wrap: wrap; gap: 1rem;
}
.ii-per-page { display: flex; align-items: center; gap: .5rem; font-size: .82rem; color: var(--ii-muted); }
.ii-per-page-label { font-weight: 500; margin: 0; }
.ii-per-page-select {
    padding: .3rem .6rem;
    border: 1px solid var(--ii-border); border-radius: 8px;
    background: #fff; font-size: .82rem;
    cursor: pointer;
}
.ii-per-page-select:focus { outline: none; border-color: var(--ii-primary); }
.ii-pagination .pagination { margin: 0; }
.ii-pagination .page-link {
    color: var(--ii-primary);
    border-color: var(--ii-border);
    font-size: .85rem;
}
.ii-pagination .page-item.active .page-link {
    background: var(--ii-primary);
    border-color: var(--ii-primary);
}

/* Empty state */
.ii-empty {
    text-align: center; padding: 3.5rem 1.5rem;
}
.ii-empty-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--ii-surface);
    display: inline-flex; align-items: center; justify-content: center;
    color: var(--ii-muted); font-size: 1.75rem;
    margin-bottom: 1rem;
}
.ii-empty-title { font-size: 1rem; font-weight: 700; color: var(--ii-text); margin-bottom: .3rem; }
.ii-empty-text { font-size: .88rem; color: var(--ii-muted); }

/* ========== MODALS monochrome ========== */
.ii-modal-header {
    background: linear-gradient(135deg, #0a3d8f 0%, var(--ii-primary) 100%);
    color: #fff;
    border-bottom: none;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
}
.ii-modal-header .modal-title { color: #fff; font-weight: 700; font-size: 1rem; }
.ii-info-box {
    display: flex; gap: .6rem;
    padding: .8rem 1rem;
    background: rgba(4,83,203,.06);
    border-left: 3px solid var(--ii-primary);
    border-radius: 8px;
    color: var(--ii-text); font-size: .85rem; line-height: 1.5;
}
.ii-info-box i { color: var(--ii-primary); font-size: 1rem; flex-shrink: 0; margin-top: .15rem; }
.ii-warn-box {
    display: flex; gap: .6rem;
    padding: .8rem 1rem; background: #fef3c7;
    border-left: 3px solid var(--ii-warn);
    border-radius: 8px;
    color: #92400e; font-size: .84rem; line-height: 1.5;
}
.ii-warn-box i { color: var(--ii-warn); font-size: 1rem; flex-shrink: 0; margin-top: .15rem; }

/* ========== BULK BAR ========== */
.ii-bulk-bar {
    display: none;
    position: fixed; bottom: 24px; left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
    color: #fff;
    padding: .9rem 1.5rem;
    border-radius: 99px;
    box-shadow: 0 10px 40px rgba(4,83,203,.35);
    z-index: 1050;
    animation: ii-bulk-slide-up .3s ease-out;
}
.ii-bulk-bar.ii-bulk-bar--visible { display: flex; }
.ii-bulk-bar-content {
    display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.ii-bulk-count {
    display: flex; align-items: center; gap: .4rem;
    font-size: .9rem; font-weight: 600;
}
.ii-bulk-count i { font-size: 1rem; }
.ii-bulk-actions { display: flex; gap: .4rem; flex-wrap: wrap; }
.ii-bulk-btn {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .45rem 1rem;
    background: rgba(255,255,255,.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 99px;
    font-size: .78rem; font-weight: 600;
    cursor: pointer; transition: all .15s;
}
.ii-bulk-btn:hover { background: rgba(255,255,255,.25); transform: translateY(-1px); }
.ii-bulk-btn--primary { background: #fff; color: var(--ii-primary); border-color: #fff; }
.ii-bulk-btn--primary:hover { background: #f1f5f9; color: var(--ii-primary-dark); }
.ii-bulk-btn--danger { background: rgba(255,255,255,.12); color: #fecaca; border-color: rgba(254,202,202,.3); }
.ii-bulk-btn--danger:hover { background: rgba(239,68,68,.25); color: #fff; border-color: #fca5a5; }
.ii-bulk-close {
    width: 32px; height: 32px; border-radius: 50%;
    border: 1px solid rgba(255,255,255,.25);
    background: transparent; color: #fff;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s;
}
.ii-bulk-close:hover { background: rgba(255,255,255,.2); }
@keyframes ii-bulk-slide-up {
    from { bottom: -100px; opacity: 0; }
    to { bottom: 24px; opacity: 1; }
}

/* ========== RESPONSIVE ========== */
@media (max-width: 992px) {
    .ii-hero { padding: 1.5rem 1.5rem 1.25rem; }
    .ii-hero h1 { font-size: 1.3rem; }
    .ii-col-annee, .ii-col-date { display: none; }
}
@media (max-width: 768px) {
    .ii-hero-top { flex-direction: column; align-items: stretch; }
    .ii-hero-actions { justify-content: flex-start; }
    .ii-kpis { grid-template-columns: repeat(2, 1fr); }
    .ii-filter-select { flex: 1 1 calc(50% - .3rem); min-width: 0; }
    .ii-search { flex: 1 1 100%; }
}
@media (max-width: 480px) {
    .ii-hero { padding: 1.25rem; }
    .ii-kpis { grid-template-columns: 1fr; }
    .ii-table-footer { flex-direction: column; align-items: flex-start; }
    .ii-bulk-bar { left: 12px; right: 12px; transform: none; width: calc(100% - 24px); }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- HERO + KPIs --}}
        <div class="ii-hero">
            <div class="ii-hero-top">
                <div class="ii-hero-left">
                    <div class="ii-hero-icon"><i class="fas fa-user-graduate"></i></div>
                    <div>
                        <h1>Gestion des inscriptions</h1>
                        <p>Consultez et gérez toutes les inscriptions de l'établissement</p>
                        <span class="ii-hero-chip">
                            <i class="fas fa-calendar-alt"></i>
                            Année {{ $anneeEnCours->name ?? 'non définie' }}
                        </span>
                    </div>
                </div>
                <div class="ii-hero-actions">
                    @can('inscriptions.validate')
                        <a href="{{ route('esbtp.inscriptions.administration') }}" class="ii-btn--glass">
                            <i class="fas fa-user-check"></i>Administration
                        </a>
                    @endcan
                    @can('inscriptions.create')
                        <a href="{{ route('esbtp.inscriptions.create') }}" class="ii-btn--white">
                            <i class="fas fa-plus-circle"></i>Nouvelle inscription
                        </a>
                    @endcan
                </div>
            </div>

            <div class="ii-kpis" id="ii-kpis">
                <button type="button" class="ii-kpi {{ in_array(request('status','active'), ['all']) ? 'ii-kpi--active' : '' }}" data-kpi-filter="all">
                    <div class="ii-kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="ii-kpi-body">
                        <div class="ii-kpi-value">{{ $stats['total'] ?? 0 }}</div>
                        <div class="ii-kpi-label">Total</div>
                    </div>
                </button>
                <button type="button" class="ii-kpi {{ request('status','active') === 'active' ? 'ii-kpi--active' : '' }}" data-kpi-filter="active">
                    <div class="ii-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="ii-kpi-body">
                        <div class="ii-kpi-value">{{ $stats['actives'] ?? 0 }}</div>
                        <div class="ii-kpi-label">Validées</div>
                    </div>
                </button>
                <button type="button" class="ii-kpi {{ request('status') === 'non_validee' ? 'ii-kpi--active' : '' }}" data-kpi-filter="non_validee" style="position:relative;">
                    <div class="ii-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="ii-kpi-body">
                        <div class="ii-kpi-value">{{ $stats['non_validees'] ?? 0 }}</div>
                        <div class="ii-kpi-label">Non validées</div>
                    </div>
                    @if(($stats['non_validees'] ?? 0) > 0)<span class="ii-kpi-badge"></span>@endif
                </button>
                <button type="button" class="ii-kpi {{ request('status') === 'en_attente' ? 'ii-kpi--active' : '' }}" data-kpi-filter="en_attente">
                    <div class="ii-kpi-icon"><i class="fas fa-clock"></i></div>
                    <div class="ii-kpi-body">
                        <div class="ii-kpi-value">{{ $stats['en_attente'] ?? 0 }}</div>
                        <div class="ii-kpi-label">En attente</div>
                    </div>
                </button>
                <button type="button" class="ii-kpi {{ request('status') === 'annulée' ? 'ii-kpi--active' : '' }}" data-kpi-filter="annulée">
                    <div class="ii-kpi-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="ii-kpi-body">
                        <div class="ii-kpi-value">{{ $stats['annulees'] ?? 0 }}</div>
                        <div class="ii-kpi-label">Annulées</div>
                    </div>
                </button>
            </div>
        </div>

        {{-- Messages flash --}}
        @if(session('success'))
            <div class="ii-alert ii-alert--success">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="ii-alert ii-alert--danger">
                <i class="fas fa-exclamation-triangle"></i>{{ session('error') }}
            </div>
        @endif

        {{-- Bannière problèmes --}}
        @if(session('inscriptions_problemes') && count(session('inscriptions_problemes')) > 0)
            <div class="ii-banner">
                <div class="ii-banner-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="ii-banner-body">
                    <div class="ii-banner-title">{{ count(session('inscriptions_problemes')) }} inscription(s) nécessitent votre attention</div>
                    <div class="ii-banner-text">Les lignes concernées sont marquées en couleur dans la liste ci-dessous. Utilisez les actions rapides pour résoudre chaque problème.</div>
                </div>
            </div>
        @endif

        {{-- TOOLBAR --}}
        <form method="GET"
              action="{{ route('esbtp.inscriptions.index') }}"
              id="inscriptions-filter-form"
              class="ii-toolbar">
            <div class="ii-toolbar-row">
                <div class="ii-search">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           name="search"
                           id="filter-search"
                           value="{{ request('search') }}"
                           placeholder="Rechercher par matricule, nom ou N° d'inscription..."
                           autocomplete="off">
                </div>

                <select name="filiere" id="filiere" class="ii-filter-select" aria-label="Filière">
                    <option value="">Toutes les filières</option>
                    @foreach($filieres as $fil)
                        <option value="{{ $fil->id }}" @selected(request('filiere') == $fil->id)>{{ $fil->name }}</option>
                    @endforeach
                </select>

                <select name="niveau" id="niveau" class="ii-filter-select" aria-label="Niveau">
                    <option value="">Tous les niveaux</option>
                    @foreach($niveaux as $niv)
                        <option value="{{ $niv->id }}" @selected(request('niveau') == $niv->id)>{{ $niv->name }}</option>
                    @endforeach
                </select>

                <select name="annee" id="annee" class="ii-filter-select" aria-label="Année universitaire">
                    <option value="">Année courante</option>
                    @foreach($annees as $an)
                        <option value="{{ $an->id }}" @selected(request('annee') == $an->id)>{{ $an->name }}</option>
                    @endforeach
                </select>

                <select name="status" id="status" class="ii-filter-select" aria-label="Statut">
                    <option value="all" @selected(request('status','active') == 'all')>Tous statuts</option>
                    <option value="active" @selected(request('status','active') == 'active')>Validées</option>
                    <option value="non_validee" @selected(request('status') == 'non_validee')>Non validées ({{ $stats['non_validees'] ?? 0 }})</option>
                    <option value="en_attente" @selected(request('status') == 'en_attente')>En attente</option>
                    <option value="annulée" @selected(request('status') == 'annulée')>Annulées</option>
                    <option value="terminée" @selected(request('status') == 'terminée')>Terminées</option>
                </select>

                {{-- Champs cachés pour sort + per_page (préservés dans AJAX) --}}
                <input type="hidden" name="sort" id="sort-input" value="{{ $sort ?? 'created_at' }}">
                <input type="hidden" name="dir" id="dir-input" value="{{ $dir ?? 'desc' }}">
                <input type="hidden" name="per_page" id="per-page-input" value="{{ $perPage ?? 15 }}">

                <button type="button" id="reset-filters-btn" class="ii-btn--ghost" title="Réinitialiser les filtres">
                    <i class="fas fa-rotate-left"></i>Reset
                </button>

                {{-- Submit caché pour permettre Enter dans l'input --}}
                <button type="submit" style="display:none;" aria-hidden="true">Filtrer</button>
            </div>

            <div class="ii-toolbar-meta">
                <span><i class="fas fa-list"></i> <span id="ii-result-count">{{ $inscriptions->total() }}</span> résultat(s)</span>
                <span class="ii-chips-active" id="ii-active-filters"></span>
            </div>
        </form>

        {{-- RÉSULTATS --}}
        <div class="ii-results-card">
            <div id="inscriptions-results">
                @include('esbtp.inscriptions.partials.results', [
                    'inscriptions' => $inscriptions,
                    'sort' => $sort ?? 'created_at',
                    'dir' => $dir ?? 'desc',
                    'perPage' => $perPage ?? 15,
                ])
            </div>
        </div>
    </div>
</div>

{{-- BULK ACTIONS BAR --}}
@can('inscriptions.validate')
<div id="ii-bulk-bar" class="ii-bulk-bar">
    <div class="ii-bulk-bar-content">
        <div class="ii-bulk-count">
            <i class="fas fa-check-circle"></i>
            <span id="ii-selected-count">0</span> sélection(s)
        </div>
        <div class="ii-bulk-actions">
            <button type="button" class="ii-bulk-btn ii-bulk-btn--primary" onclick="iiBulkValider()">
                <i class="fas fa-check-double"></i>Valider
            </button>
            @can('annuler inscriptions')
                <button type="button" class="ii-bulk-btn" onclick="iiBulkAnnuler()">
                    <i class="fas fa-times"></i>Annuler
                </button>
            @endcan
            <button type="button" class="ii-bulk-btn" onclick="iiBulkExporter()">
                <i class="fas fa-download"></i>Exporter
            </button>
        </div>
        <button type="button" class="ii-bulk-close" onclick="iiClearSelection()" aria-label="Fermer la sélection">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endcan

{{-- MODALS GLOBAUX (data-id dynamique) --}}

{{-- Modal générique de confirmation (Valider + autres actions) --}}
<div class="modal fade" id="ii-modal-confirm" tabindex="-1" aria-labelledby="iiModalConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ii-modal-header">
                <h5 class="modal-title" id="iiModalConfirmLabel">
                    <i class="fas fa-circle-question me-2" id="ii-confirm-icon"></i><span id="ii-confirm-title">Confirmation</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="ii-confirm-body">Êtes-vous sûr ?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="ii-confirm-ok">Confirmer</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Annuler une inscription --}}
<div class="modal fade" id="ii-modal-annuler" tabindex="-1" aria-labelledby="iiModalAnnulerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ii-modal-header">
                <h5 class="modal-title" id="iiModalAnnulerLabel">
                    <i class="fas fa-times-circle me-2"></i>Annuler l'inscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="ii-form-annuler" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p>Annuler l'inscription de <strong id="ii-annuler-student-name">—</strong> ?</p>
                    <div class="mb-3">
                        <label for="ii-annuler-motif" class="form-label fw-bold">Motif d'annulation <span class="text-danger">*</span></label>
                        <textarea id="ii-annuler-motif" name="motif" class="form-control" rows="3" required placeholder="Expliquez brièvement le motif..."></textarea>
                    </div>
                    <div class="ii-warn-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>Cette action marquera l'inscription comme annulée. Elle pourra être réactivée ultérieurement si nécessaire.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-times-circle me-1"></i>Confirmer l'annulation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Supprimer une inscription --}}
<div class="modal fade" id="ii-modal-delete" tabindex="-1" aria-labelledby="iiModalDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ii-modal-header">
                <h5 class="modal-title" id="iiModalDeleteLabel">
                    <i class="fas fa-trash me-2"></i>Supprimer l'inscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="ii-form-delete" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Supprimer définitivement l'inscription de <strong id="ii-delete-student-name">—</strong> ?</p>
                    <div class="ii-warn-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div><strong>Cette action est irréversible.</strong> Toutes les données associées (notes, paiements, bulletins) seront archivées.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash me-1"></i>Supprimer définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Valider Paiement (action rapide) --}}
<div class="modal fade" id="modalValiderPaiement" tabindex="-1" aria-labelledby="modalValiderPaiementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ii-modal-header">
                <h5 class="modal-title" id="modalValiderPaiementLabel">
                    <i class="fas fa-check-circle me-2"></i>Valider le paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="formValiderPaiement" method="POST">
                @csrf
                <input type="hidden" name="inscription_id" id="valider_inscription_id">
                <input type="hidden" name="paiement_id" id="valider_paiement_id">
                <div class="modal-body">
                    <div class="ii-info-box mb-3">
                        <i class="fas fa-info-circle"></i>
                        <span id="validerPaiementInfo">Paiement à valider...</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Montant</label>
                        <input type="text" class="form-control" id="valider_montant" readonly style="background:var(--ii-surface);">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mode de paiement</label>
                        <input type="text" class="form-control" id="valider_mode" readonly style="background:var(--ii-surface);">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Référence</label>
                        <input type="text" class="form-control" id="valider_reference" readonly style="background:var(--ii-surface);">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>Valider le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Changer la classe (action rapide) --}}
<div class="modal fade" id="modalChangerClasse" tabindex="-1" aria-labelledby="modalChangerClasseLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ii-modal-header">
                <h5 class="modal-title" id="modalChangerClasseLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Changer la classe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="formChangerClasse" method="POST">
                @csrf
                <input type="hidden" name="inscription_id" id="changer_inscription_id">
                <div class="modal-body">
                    <div class="ii-warn-box mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>La classe actuelle est pleine. Veuillez sélectionner une nouvelle classe disponible.</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Classe actuelle</label>
                            <input type="text" class="form-control" id="changer_ancienne_classe" readonly style="background:var(--ii-surface);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nouvelle classe <span class="text-danger">*</span></label>
                            <select class="form-select" name="nouvelle_classe_id" id="changer_nouvelle_classe" required>
                                <option value="">Sélectionnez une classe</option>
                            </select>
                        </div>
                    </div>
                    <div id="classeDispoInfo" class="ii-info-box" style="display:none;">
                        <i class="fas fa-check-circle"></i>
                        <span id="classeDispoText">Places disponibles : ...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-exchange-alt me-1"></i>Changer la classe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Créer un paiement (action rapide) --}}
<div class="modal fade" id="modalCreerPaiement" tabindex="-1" aria-labelledby="modalCreerPaiementLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ii-modal-header">
                <h5 class="modal-title" id="modalCreerPaiementLabel">
                    <i class="fas fa-plus-circle me-2"></i>Créer un paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="formCreerPaiement" method="POST">
                @csrf
                <input type="hidden" name="inscription_id" id="creer_inscription_id">
                <input type="hidden" name="etudiant_id" id="creer_etudiant_id">
                <input type="hidden" name="annee_universitaire_id" id="creer_annee_id">
                <div class="modal-body">
                    <div class="ii-info-box mb-3">
                        <i class="fas fa-info-circle"></i>
                        <span id="creerPaiementInfo">Créer un paiement associé à l'inscription</span>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Montant (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" name="montant" class="form-control" id="creer_montant" required min="0" step="0.01" placeholder="Ex: 50000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Catégorie de frais <span class="text-danger">*</span></label>
                            <select name="fee_category_id" class="form-select" id="creer_categorie" required>
                                <option value="">Sélectionnez une catégorie</option>
                                @php
                                    $categoriesfrais = \App\Models\ESBTPFraisCategory::where('is_active', true)->orderBy('name')->get();
                                @endphp
                                @foreach($categoriesfrais as $categorie)
                                    <option value="{{ $categorie->id }}">{{ $categorie->name }} ({{ $categorie->is_mandatory ? 'Obligatoire' : 'Optionnel' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mode de paiement <span class="text-danger">*</span></label>
                            <select name="mode_paiement" class="form-select" id="creer_mode" required>
                                <option value="">Sélectionnez un mode</option>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement bancaire</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Référence</label>
                            <input type="text" name="reference_paiement" class="form-control" id="creer_reference" placeholder="Ex: REF2025-001">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date du paiement <span class="text-danger">*</span></label>
                            <input type="date" name="date_paiement" class="form-control" id="creer_date" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Observations</label>
                            <textarea name="observations" class="form-control" id="creer_observations" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="valider_immediatement" id="creer_valider_immediatement" value="1">
                        <label class="form-check-label fw-bold" for="creer_valider_immediatement">Valider le paiement immédiatement</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Créer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Route map injectée server-side, consommée par public/js/inscriptions/index.js
    window.KLASSCI_INSCRIPTIONS_ROUTES = {
        index: "{{ route('esbtp.inscriptions.index') }}",
        bulkValider: "{{ route('esbtp.inscriptions.bulk-valider') }}",
        refreshLigne: "/esbtp/inscriptions/:id/refresh-ligne",
        valider: "/esbtp/inscriptions/:id/valider",
        annuler: "/esbtp/inscriptions/:id/annuler",
        destroy: "/esbtp/inscriptions/:id",
        paiementEnAttente: "/esbtp/inscriptions/:id/paiement-en-attente",
        classesAlternatives: "/esbtp/inscriptions/:id/classes-alternatives",
        inscriptionData: "/esbtp/inscriptions/:id/data",
        validerAvecPaiement: "/esbtp/inscriptions/:id/valider-avec-paiement",
        changerClasseRapide: "/esbtp/inscriptions/:id/changer-classe-rapide",
        validerPaiementRapide: "/esbtp/paiements/:id/valider-rapide",
    };
    window.KLASSCI_CSRF_TOKEN = "{{ csrf_token() }}";
</script>
<script src="{{ asset('js/inscriptions/index.js') }}" defer></script>
@endpush
