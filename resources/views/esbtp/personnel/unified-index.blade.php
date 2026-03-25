@extends('layouts.app')

@section('title', 'Gestion du Personnel - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════
   PERSONNEL UNIFIED — Premium Design (pu- namespace)
   ═══════════════════════════════════════════════════ */

/* ─── Hero ─── */
.pu-hero {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    border-radius: 18px;
    padding: 2rem 2.25rem;
    color: #fff;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}
.pu-hero::before {
    content: '';
    position: absolute;
    top: -40%; right: -10%;
    width: 340px; height: 340px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.pu-hero::after {
    content: '';
    position: absolute;
    bottom: -30%; left: -5%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.pu-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 2;
}
.pu-hero-title {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.pu-hero-title i { font-size: 1.25rem; opacity: 0.85; }
.pu-hero-subtitle {
    font-size: 0.88rem;
    opacity: 0.75;
    margin-top: 0.35rem;
}
.pu-hero-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
    position: relative;
    z-index: 1050;
}
.pu-hero-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    padding: 0.55rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    white-space: nowrap;
}
.pu-hero-btn:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}
.pu-hero-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}
.pu-hero-kpi {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: background 0.2s;
}
.pu-hero-kpi:hover { background: rgba(255,255,255,0.18); }
.pu-hero-kpi-value {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.2;
}
.pu-hero-kpi-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.8;
    margin-top: 0.15rem;
}

/* ─── Dropdown override inside hero ─── */
.pu-hero .dropdown-menu {
    z-index: 1051 !important;
    background: #fff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
    padding: 0.5rem !important;
    min-width: 200px !important;
}
.pu-hero .dropdown-item {
    padding: 0.6rem 0.85rem !important;
    color: #374151 !important;
    border-radius: 8px !important;
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: all 0.15s !important;
}
.pu-hero .dropdown-item:hover {
    background: rgba(4,83,203,0.06) !important;
    color: #0453cb !important;
}
.pu-hero .dropdown-item i {
    width: 18px;
    text-align: center;
    font-size: 0.8rem;
    color: #94a3b8;
}
.pu-hero .dropdown-item:hover i { color: #0453cb; }

/* ─── Alerts ─── */
.pu-alert {
    border-radius: 12px;
    padding: 0.85rem 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.88rem;
    font-weight: 500;
    border: none;
}
.pu-alert-success {
    background: rgba(16,185,129,0.1);
    color: #065f46;
    border-left: 4px solid #10b981;
}
.pu-alert-danger {
    background: rgba(239,68,68,0.1);
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

/* ─── Tabs ─── */
.pu-tabs-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: visible;
    margin-bottom: 1.5rem;
}
.pu-tabs-bar {
    display: flex;
    border-bottom: 2px solid #f3f4f6;
    padding: 0 0.5rem;
    overflow-x: auto;
}
.pu-tab {
    flex: 1;
    min-width: 140px;
    padding: 1rem 0.75rem;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: center;
    position: relative;
    transition: all 0.2s;
    color: #6b7280;
}
.pu-tab::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 15%; right: 15%;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: transparent;
    transition: all 0.2s;
}
.pu-tab.active {
    color: #0453cb;
}
.pu-tab.active::after {
    background: #0453cb;
    left: 10%; right: 10%;
}
.pu-tab:hover:not(.active) {
    color: #374151;
    background: rgba(4,83,203,0.02);
}
.pu-tab-icon {
    display: block;
    font-size: 1.25rem;
    margin-bottom: 0.3rem;
}
.pu-tab.active .pu-tab-icon { color: #0453cb; }
.pu-tab-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
}
.pu-tab-count {
    display: block;
    font-size: 0.68rem;
    color: #94a3b8;
    margin-top: 0.15rem;
    font-weight: 600;
}
.pu-tab.active .pu-tab-count { color: #5e91de; }

/* ─── Panel ─── */
.pu-panel-content {
    padding: 1.5rem;
    min-height: 400px;
}
.pu-panel {
    display: none;
}
.pu-panel.active {
    display: block;
    animation: pu-slide 0.3s ease;
}
@keyframes pu-slide {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ─── Panel Header (search + actions) ─── */
.pu-panel-header {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    align-items: center;
}
.pu-search {
    flex: 1;
    min-width: 220px;
    position: relative;
}
.pu-search input {
    width: 100%;
    padding: 0.6rem 0.75rem 0.6rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #1e293b;
    background: #f8fafc;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.pu-search input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.08);
    background: #fff;
}
.pu-search::before {
    content: '\f002';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.78rem;
    pointer-events: none;
}
.pu-panel-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.6rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-decoration: none;
    white-space: nowrap;
}
.pu-panel-btn-primary {
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
}
.pu-panel-btn-primary:hover {
    opacity: 0.9;
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}
.pu-panel-btn-warning {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: #fff;
}
.pu-panel-btn-warning:hover {
    opacity: 0.9;
    color: #fff;
    text-decoration: none;
}

/* ─── Filters ─── */
.pu-filters {
    display: flex;
    gap: 0.6rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    align-items: center;
}
.pu-filters label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
}
.pu-filter-select {
    padding: 0.45rem 0.65rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.82rem;
    color: #1e293b;
    background: #fff;
    min-width: 140px;
    transition: border-color 0.2s;
}
.pu-filter-select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.08);
}

/* ─── Tip box ─── */
.pu-tip {
    background: rgba(4,83,203,0.04);
    border: 1px solid rgba(4,83,203,0.1);
    border-left: 4px solid #0453cb;
    border-radius: 10px;
    padding: 0.85rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
}
.pu-tip i { color: #0453cb; margin-top: 2px; font-size: 0.9rem; }
.pu-tip-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #0453cb;
    margin-bottom: 0.15rem;
}
.pu-tip-text {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0;
}

/* ─── Personnel Card ─── */
.pu-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    margin-bottom: 0.75rem;
    border: 1px solid #f3f4f6;
    border-left: 3px solid transparent;
    transition: all 0.2s;
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.pu-card:hover {
    border-left-color: #0453cb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transform: translateY(-1px);
}
.pu-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.pu-avatar-green { background: linear-gradient(135deg, #10b981, #34d399); }
.pu-info {
    flex: 1;
    min-width: 0;
}
.pu-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: #111827;
    line-height: 1.3;
}
.pu-meta {
    display: flex;
    gap: 0.85rem;
    flex-wrap: wrap;
    margin-top: 0.25rem;
}
.pu-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.75rem;
    color: #6b7280;
}
.pu-meta-item i { font-size: 0.65rem; color: #94a3b8; }
.pu-status {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    flex-shrink: 0;
}
.pu-status-active {
    background: rgba(16,185,129,0.1);
    color: #065f46;
}
.pu-status-active::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #10b981;
}
.pu-status-inactive {
    background: rgba(239,68,68,0.08);
    color: #991b1b;
}
.pu-status-inactive::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #ef4444;
}
.pu-actions {
    display: flex;
    gap: 0.35rem;
    flex-shrink: 0;
}
.pu-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.pu-action-btn:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: rgba(4,83,203,0.04);
    text-decoration: none;
}
.pu-action-btn.pu-act-edit:hover {
    border-color: #f59e0b;
    color: #d97706;
    background: rgba(245,158,11,0.04);
}
.pu-action-btn.pu-act-danger:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239,68,68,0.04);
}
.pu-action-btn.pu-act-success:hover {
    border-color: #10b981;
    color: #10b981;
    background: rgba(16,185,129,0.04);
}

/* ─── Empty State ─── */
.pu-empty {
    padding: 3rem 2rem;
    text-align: center;
}
.pu-empty-icon {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: #f1f5f9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}
.pu-empty-icon i { font-size: 1.75rem; color: #cbd5e1; }
.pu-empty h3 { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 0.4rem; }
.pu-empty p { font-size: 0.85rem; color: #6b7280; margin: 0 0 1rem; }
.pu-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.82rem;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    text-decoration: none;
    transition: all 0.2s;
}
.pu-empty-btn:hover { opacity: 0.9; color: #fff; text-decoration: none; }

/* ─── Animations ─── */
@keyframes pu-fade-up {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.pu-animate { animation: pu-fade-up 0.5s ease-out both; }
.pu-delay-1 { animation-delay: 0.1s; }
.pu-delay-2 { animation-delay: 0.2s; }
.pu-delay-3 { animation-delay: 0.3s; }

/* ─── Overflow fix for dropdown ─── */
.dashboard-acasi,
.main-content { overflow: visible !important; }

/* ─── Responsive ─── */
@media (max-width: 992px) {
    .pu-hero-top { flex-direction: column; }
    .pu-hero-actions { width: 100%; }
    .pu-hero-kpis { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .pu-hero { padding: 1.5rem; border-radius: 14px; }
    .pu-hero-title { font-size: 1.25rem; }
    .pu-hero-kpis { grid-template-columns: 1fr 1fr; }
    .pu-tabs-bar { flex-direction: column; padding: 0; }
    .pu-tab { text-align: left; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.75rem; }
    .pu-tab-icon { display: inline; margin-bottom: 0; font-size: 1rem; }
    .pu-tab-count { display: none; }
    .pu-tab::after { left: 0; right: auto; width: 3px; top: 15%; bottom: 15%; height: auto; border-radius: 0 3px 3px 0; }
    .pu-panel-header { flex-direction: column; }
    .pu-search { min-width: 100%; }
    .pu-filters { flex-direction: column; align-items: stretch; }
    .pu-card { flex-wrap: wrap; }
    .pu-actions { width: 100%; justify-content: flex-end; margin-top: 0.5rem; }
    .pu-hero-actions { flex-direction: column; }
}
@media (max-width: 576px) {
    .pu-hero-kpis { grid-template-columns: 1fr; }
    .pu-meta { flex-direction: column; gap: 0.25rem; }
}
</style>
@endpush

@section('content')
@php
    // Tabs cachés selon le rôle de l'utilisateur connecté
    $hiddenTabs = [];
    $ur = $userRole ?? '';
    if ($ur === 'coordinateur') $hiddenTabs[] = 'coordinateurs';
    if ($ur === 'secretaire') $hiddenTabs[] = 'secretaires';
    // Premier tab visible = actif par défaut
    $tabOrder = ['coordinateurs', 'enseignants', 'secretaires', 'comptables', 'caissiers'];
    $firstVisibleTab = collect($tabOrder)->first(fn($t) => !in_array($t, $hiddenTabs));
@endphp

<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ═══ Hero ═══ --}}
        <div class="pu-hero pu-animate">
            <div class="pu-hero-top">
                <div>
                    <div class="pu-hero-title">
                        <i class="fas fa-users-cog"></i>Gestion du Personnel
                    </div>
                    <div class="pu-hero-subtitle">Administration unifiée : coordinateurs, enseignants, secrétaires et comptables</div>
                </div>
                <div class="pu-hero-actions">
                    <div class="dropdown">
                        <button class="pu-hero-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-plus"></i>Nouveau Personnel
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(($userRole ?? '') !== 'coordinateur')
                            <li><a class="dropdown-item" href="{{ route('esbtp.coordinateurs.create') }}">
                                <i class="fas fa-user-tie"></i>Coordinateur
                            </a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('esbtp.enseignants.create') }}">
                                <i class="fas fa-chalkboard-teacher"></i>Enseignant
                            </a></li>
                            @if(($userRole ?? '') !== 'secretaire')
                            <li><a class="dropdown-item" href="{{ route('esbtp.secretaires.create') }}">
                                <i class="fas fa-user-shield"></i>Secrétaire
                            </a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('esbtp.comptables.create') }}">
                                <i class="fas fa-calculator"></i>Comptable
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.caissiers.create') }}">
                                <i class="fas fa-cash-register"></i>Caissier
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="pu-hero-kpis">
                @if(!in_array('coordinateurs', $hiddenTabs))
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['coordinateurs'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Coordinateurs</div>
                </div>
                @endif
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['enseignants'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Enseignants</div>
                </div>
                @if(!in_array('secretaires', $hiddenTabs))
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['secretaires'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Secrétaires</div>
                </div>
                @endif
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['comptables'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Comptables</div>
                </div>
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['caissiers'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Caissiers</div>
                </div>
                <div class="pu-hero-kpi">
                    <div class="pu-hero-kpi-value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="pu-hero-kpi-label">Total</div>
                </div>
            </div>
        </div>

        {{-- ═══ Alerts ═══ --}}
        @if(session('success'))
            <div class="pu-alert pu-alert-success pu-animate pu-delay-1">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="pu-alert pu-alert-danger pu-animate pu-delay-1">
                <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
            </div>
        @endif

        {{-- ═══ Tabs Card ═══ --}}
        <div class="pu-tabs-card pu-animate pu-delay-2">
            <div class="pu-tabs-bar">
                @if(!in_array('coordinateurs', $hiddenTabs))
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'coordinateurs' ? 'active' : '' }}" data-tab="coordinateurs">
                    <span class="pu-tab-icon"><i class="fas fa-user-tie"></i></span>
                    <span class="pu-tab-label">Coordinateurs</span>
                    <span class="pu-tab-count">{{ $stats['coordinateurs'] ?? 0 }} personnes</span>
                </button>
                @endif
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'enseignants' ? 'active' : '' }}" data-tab="enseignants">
                    <span class="pu-tab-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                    <span class="pu-tab-label">Enseignants</span>
                    <span class="pu-tab-count">{{ $stats['enseignants'] ?? 0 }} personnes</span>
                </button>
                @if(!in_array('secretaires', $hiddenTabs))
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'secretaires' ? 'active' : '' }}" data-tab="secretaires">
                    <span class="pu-tab-icon"><i class="fas fa-user-shield"></i></span>
                    <span class="pu-tab-label">Secrétaires</span>
                    <span class="pu-tab-count">{{ $stats['secretaires'] ?? 0 }} personnes</span>
                </button>
                @endif
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'comptables' ? 'active' : '' }}" data-tab="comptables">
                    <span class="pu-tab-icon"><i class="fas fa-calculator"></i></span>
                    <span class="pu-tab-label">Comptables</span>
                    <span class="pu-tab-count">{{ $stats['comptables'] ?? 0 }} personnes</span>
                </button>
                <button class="pu-tab slider-tab {{ $firstVisibleTab === 'caissiers' ? 'active' : '' }}" data-tab="caissiers">
                    <span class="pu-tab-icon"><i class="fas fa-cash-register"></i></span>
                    <span class="pu-tab-label">Caissiers</span>
                    <span class="pu-tab-count">{{ $stats['caissiers'] ?? 0 }} personnes</span>
                </button>
            </div>

            <div class="pu-panel-content">

                {{-- ═══ Panel Coordinateurs ═══ --}}
                @if(!in_array('coordinateurs', $hiddenTabs))
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'coordinateurs' ? 'active' : '' }}" id="coordinateurs-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un coordinateur..." id="search-coordinateurs">
                        </div>
                        <a href="{{ route('esbtp.coordinateurs.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Coordinateur
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-coordinateurs-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div id="coordinateurs-list">
                        @if(isset($coordinateurs) && $coordinateurs->count() > 0)
                            @foreach($coordinateurs as $coordinateur)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar">
                                    {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">{{ $coordinateur->name }}</div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $coordinateur->email }}</span>
                                        @if($coordinateur->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $coordinateur->telephone }}</span>
                                        @endif
                                        @if($coordinateur->specialite)
                                        <span class="pu-meta-item"><i class="fas fa-graduation-cap"></i>{{ $coordinateur->specialite }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $coordinateur->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $coordinateur->is_active ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" class="pu-action-btn pu-act-edit" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @if($coordinateur->id !== auth()->id())
                                    <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="pu-action-btn {{ $coordinateur->is_active ? 'pu-act-danger' : 'pu-act-success' }}"
                                                title="{{ $coordinateur->is_active ? 'Désactiver' : 'Activer' }}"
                                                onclick="return confirm('Êtes-vous sûr de vouloir {{ $coordinateur->is_active ? 'désactiver' : 'activer' }} ce coordinateur ?')">
                                            <i class="fas fa-{{ $coordinateur->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-user-tie"></i></div>
                                <h3>Aucun coordinateur</h3>
                                <p>Commencez par créer votre premier coordinateur.</p>
                                <a href="{{ route('esbtp.coordinateurs.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un coordinateur
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ═══ Panel Enseignants ═══ --}}
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'enseignants' ? 'active' : '' }}" id="enseignants-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un enseignant..." id="search-enseignants">
                        </div>
                        <button type="button" class="pu-panel-btn pu-panel-btn-warning" data-bs-toggle="modal" data-bs-target="#bulkAvailabilityModal">
                            <i class="fas fa-calendar-check"></i>Disponibilités
                        </button>
                        <a href="{{ route('esbtp.enseignants.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouvel Enseignant
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-enseignants-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div class="pu-tip">
                        <i class="fas fa-lightbulb"></i>
                        <div>
                            <div class="pu-tip-title">Astuce</div>
                            <p class="pu-tip-text">Pour gérer la disponibilité d'un enseignant, consultez sa fiche détaillée en cliquant sur le bouton "Voir".</p>
                        </div>
                    </div>

                    <div id="enseignants-list">
                        @if(isset($enseignants) && $enseignants->count() > 0)
                            @foreach($enseignants as $teacher)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar">
                                    {{ strtoupper(substr($teacher->user->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">
                                        @if($teacher->title)
                                            <span style="font-weight: 500; color: #64748b;">{{ $teacher->title }}</span>
                                        @endif
                                        {{ $teacher->user->name }}
                                    </div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $teacher->user->email }}</span>
                                        @if($teacher->user->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $teacher->user->telephone }}</span>
                                        @endif
                                        @if($teacher->specialization)
                                        <span class="pu-meta-item"><i class="fas fa-graduation-cap"></i>{{ $teacher->specialization }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $teacher->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $teacher->status === 'active' ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.enseignants.show', $teacher) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.enseignants.edit', $teacher) }}" class="pu-action-btn pu-act-edit" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @if($teacher->user->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn {{ $teacher->status === 'active' ? 'pu-act-danger' : 'pu-act-success' }}"
                                            title="{{ $teacher->status === 'active' ? 'Désactiver' : 'Activer' }}"
                                            onclick="toggleTeacherStatus({{ $teacher->id }})">
                                        <i class="fas fa-{{ $teacher->status === 'active' ? 'pause' : 'play' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <h3>Aucun enseignant</h3>
                                <p>Commencez par créer votre premier enseignant.</p>
                                <a href="{{ route('esbtp.enseignants.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un enseignant
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ═══ Panel Secrétaires ═══ --}}
                @if(!in_array('secretaires', $hiddenTabs))
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'secretaires' ? 'active' : '' }}" id="secretaires-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un secrétaire..." id="search-secretaires">
                        </div>
                        <a href="{{ route('esbtp.secretaires.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Secrétaire
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-secretaires-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div id="secretaires-list">
                        @if(isset($secretaires) && $secretaires->count() > 0)
                            @foreach($secretaires as $secretaire)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar">
                                    {{ strtoupper(substr($secretaire->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">{{ $secretaire->name }}</div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $secretaire->email }}</span>
                                        @if($secretaire->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $secretaire->telephone }}</span>
                                        @endif
                                        @if($secretaire->service)
                                        <span class="pu-meta-item"><i class="fas fa-briefcase"></i>{{ $secretaire->service }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $secretaire->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $secretaire->is_active ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.secretaires.show', $secretaire) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.secretaires.edit', $secretaire) }}" class="pu-action-btn pu-act-edit" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @if($secretaire->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn {{ $secretaire->is_active ? 'pu-act-danger' : 'pu-act-success' }}"
                                            title="{{ $secretaire->is_active ? 'Désactiver' : 'Activer' }}"
                                            onclick="toggleSecretaireStatus({{ $secretaire->id }})">
                                        <i class="fas fa-{{ $secretaire->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-user-shield"></i></div>
                                <h3>Aucun secrétaire</h3>
                                <p>Commencez par créer votre premier secrétaire.</p>
                                <a href="{{ route('esbtp.secretaires.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un secrétaire
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ═══ Panel Comptables ═══ --}}
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'comptables' ? 'active' : '' }}" id="comptables-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un comptable..." id="search-comptables">
                        </div>
                        <a href="{{ route('esbtp.comptables.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Comptable
                        </a>
                    </div>

                    <div class="pu-filters">
                        <label>Filtrer :</label>
                        <select class="pu-filter-select filter-select" id="filter-comptables-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>

                    <div id="comptables-list">
                        @if(isset($comptables) && $comptables->count() > 0)
                            @foreach($comptables as $comptable)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar pu-avatar-green">
                                    {{ strtoupper(substr($comptable->name, 0, 2)) }}
                                </div>
                                <div class="pu-info">
                                    <div class="pu-name">{{ $comptable->name }}</div>
                                    <div class="pu-meta">
                                        <span class="pu-meta-item"><i class="fas fa-envelope"></i>{{ $comptable->email }}</span>
                                        @if($comptable->telephone)
                                        <span class="pu-meta-item"><i class="fas fa-phone"></i>{{ $comptable->telephone }}</span>
                                        @endif
                                        @if($comptable->department)
                                        <span class="pu-meta-item"><i class="fas fa-building"></i>{{ $comptable->department }}</span>
                                        @endif
                                        <span class="pu-meta-item"><i class="fas fa-calendar"></i>{{ $comptable->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <span class="pu-status {{ $comptable->is_active ? 'pu-status-active' : 'pu-status-inactive' }}">
                                    {{ $comptable->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <div class="pu-actions">
                                    <a href="{{ route('esbtp.comptables.show', $comptable) }}" class="pu-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($comptable->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn {{ $comptable->is_active ? 'pu-act-danger' : 'pu-act-success' }}"
                                            title="{{ $comptable->is_active ? 'Désactiver' : 'Activer' }}"
                                            onclick="toggleComptableStatus({{ $comptable->id }})">
                                        <i class="fas fa-{{ $comptable->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-calculator"></i></div>
                                <h3>Aucun comptable</h3>
                                <p>Commencez par créer votre premier comptable.</p>
                                <a href="{{ route('esbtp.comptables.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un comptable
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Caissiers Panel --}}
                <div class="pu-panel slider-panel {{ $firstVisibleTab === 'caissiers' ? 'active' : '' }}" id="caissiers-panel">
                    <div class="pu-panel-header">
                        <div class="pu-search">
                            <input type="text" placeholder="Rechercher un caissier..." id="search-caissiers">
                        </div>
                        <a href="{{ route('esbtp.caissiers.create') }}" class="pu-panel-btn pu-panel-btn-primary">
                            <i class="fas fa-plus"></i>Nouveau Caissier
                        </a>
                    </div>
                    <div class="pu-grid" id="caissiers-grid">
                        @if(isset($caissiers) && $caissiers->count() > 0)
                            @foreach($caissiers as $caissier)
                            <div class="pu-card personnel-card">
                                <div class="pu-avatar pu-avatar-blue">
                                    {{ strtoupper(substr($caissier->name, 0, 2)) }}
                                </div>
                                <div class="pu-card-body">
                                    <h4 class="pu-card-name">{{ $caissier->name }}</h4>
                                    <p class="pu-card-info">{{ $caissier->email }}</p>
                                    @if($caissier->telephone)
                                    <p class="pu-card-info"><i class="fas fa-phone"></i> {{ $caissier->telephone }}</p>
                                    @endif
                                    <span class="pu-badge {{ $caissier->is_active ? 'pu-badge-success' : 'pu-badge-danger' }}">
                                        {{ $caissier->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                                <div class="pu-card-actions">
                                    @if($caissier->id !== auth()->id())
                                    <button type="button"
                                            class="pu-action-btn pu-act-danger"
                                            title="Supprimer"
                                            onclick="deletePersonnel('caissier', {{ $caissier->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="pu-empty">
                                <div class="pu-empty-icon"><i class="fas fa-cash-register"></i></div>
                                <h3>Aucun caissier</h3>
                                <p>Commencez par créer votre premier caissier.</p>
                                <a href="{{ route('esbtp.caissiers.create') }}" class="pu-empty-btn">
                                    <i class="fas fa-plus"></i>Créer un caissier
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Modal pour afficher les credentials --}}
@include('partials.credentials-modal')

{{-- Modal pour sélection des enseignants pour modification groupée des disponibilités --}}
<div class="modal fade" id="bulkAvailabilityModal" tabindex="-1" aria-labelledby="bulkAvailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 18px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%); color: white; border-radius: 18px 18px 0 0; padding: 1.25rem 1.5rem;">
                <h5 class="modal-title fw-bold" id="bulkAvailabilityModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>Modification groupée des disponibilités
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Sélectionnez les enseignants dont vous souhaitez modifier les disponibilités.
                </p>

                <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
                    <div class="flex-grow-1">
                        <input type="text" id="bulk-availability-search" class="form-control" placeholder="Rechercher un enseignant...">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="bulk-select-all-availability">
                        <i class="fas fa-check-double me-1"></i>Tout sélectionner
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulk-deselect-all-availability">
                        <i class="fas fa-times me-1"></i>Tout désélectionner
                    </button>
                </div>

                <div class="list-group" id="bulk-availability-list" style="max-height: 400px; overflow-y: auto;">
                    @if(isset($enseignants) && $enseignants->count() > 0)
                        @foreach($enseignants as $teacher)
                            <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 bulk-availability-item"
                                   data-name="{{ strtolower($teacher->user->name ?? '') }}"
                                   data-email="{{ strtolower($teacher->user->email ?? '') }}">
                                <input type="checkbox" class="form-check-input bulk-availability-checkbox" value="{{ $teacher->id }}" style="margin: 0;">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">
                                        @if($teacher->title)
                                            <span class="text-muted">{{ $teacher->title }}</span>
                                        @endif
                                        {{ $teacher->user->name ?? 'N/A' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $teacher->user->email ?? '' }}
                                        @if($teacher->specialization)
                                            · {{ $teacher->specialization }}
                                        @endif
                                    </small>
                                </div>
                                <span class="badge {{ $teacher->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                            </label>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-slash fa-2x mb-2"></i>
                            <p>Aucun enseignant disponible</p>
                        </div>
                    @endif
                </div>

                <div class="mt-3 text-end">
                    <span class="badge bg-primary" id="bulk-availability-count">0 enseignant(s) sélectionné(s)</span>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="bulk-availability-submit" disabled>
                    <i class="fas fa-calendar-check me-1"></i>Modifier les disponibilités
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // ═══ Tab switching ═══
    $('.slider-tab').click(function() {
        const tabName = $(this).data('tab');
        $('.slider-tab').removeClass('active');
        $(this).addClass('active');
        $('.slider-panel').removeClass('active');
        $('#' + tabName + '-panel').addClass('active');
    });

    // ═══ Search ═══
    ['coordinateurs', 'enseignants', 'secretaires', 'comptables'].forEach(function(type) {
        $('#search-' + type).on('input', function() {
            const q = $(this).val().toLowerCase();
            $('#' + type + '-list .personnel-card').each(function() {
                $(this).toggle($(this).text().toLowerCase().includes(q));
            });
        });
    });

    // ═══ Filters ═══
    $('.filter-select').change(function() {
        const panelType = $(this).attr('id').split('-')[1];
        const statusFilter = $('#filter-' + panelType + '-status').val();
        $('#' + panelType + '-list .personnel-card').each(function() {
            let show = true;
            if (statusFilter) {
                const hasActive = $(this).find('.pu-status-active').length > 0;
                const hasInactive = $(this).find('.pu-status-inactive').length > 0;
                if (statusFilter === 'active' && !hasActive) show = false;
                if (statusFilter === 'inactive' && !hasInactive) show = false;
            }
            $(this).toggle(show);
        });
    });
});

// ═══ Toggle Status Functions ═══
function toggleTeacherStatus(teacherId) {
    if (confirm('Changer le statut de cet enseignant ?')) {
        fetch(`/esbtp/enseignants/${teacherId}/toggle-status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { if (d.success) { alert(d.message); location.reload(); } else alert('Erreur'); })
        .catch(() => alert('Erreur'));
    }
}

function toggleComptableStatus(comptableId) {
    if (confirm('Changer le statut de ce comptable ?')) {
        fetch(`/esbtp/comptables/${comptableId}/toggle-status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { if (d.success) { alert(d.message); location.reload(); } else alert('Erreur'); })
        .catch(() => alert('Erreur'));
    }
}

function toggleSecretaireStatus(secretaireId) {
    if (confirm('Changer le statut de ce secrétaire ?')) {
        fetch(`/esbtp/secretaires/${secretaireId}/toggle-status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { if (d.success) { alert(d.message); location.reload(); } else alert('Erreur'); })
        .catch(() => alert('Erreur'));
    }
}

// ═══ Bulk Availability Modal ═══
(function() {
    const searchInput = document.getElementById('bulk-availability-search');
    const selectAllBtn = document.getElementById('bulk-select-all-availability');
    const deselectAllBtn = document.getElementById('bulk-deselect-all-availability');
    const submitBtn = document.getElementById('bulk-availability-submit');
    const countBadge = document.getElementById('bulk-availability-count');
    const checkboxes = document.querySelectorAll('.bulk-availability-checkbox');
    const items = document.querySelectorAll('.bulk-availability-item');

    function updateCount() {
        const checked = document.querySelectorAll('.bulk-availability-checkbox:checked').length;
        if (countBadge) countBadge.textContent = checked + ' enseignant(s) sélectionné(s)';
        if (submitBtn) submitBtn.disabled = checked === 0;
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            items.forEach(item => {
                item.classList.toggle('d-none', q && !(item.dataset.name || '').includes(q) && !(item.dataset.email || '').includes(q));
            });
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            items.forEach(item => {
                if (!item.classList.contains('d-none')) {
                    const cb = item.querySelector('.bulk-availability-checkbox');
                    if (cb) cb.checked = true;
                }
            });
            updateCount();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            updateCount();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            const ids = Array.from(document.querySelectorAll('.bulk-availability-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) { alert('Veuillez sélectionner au moins un enseignant.'); return; }
            window.location.href = '{{ route("esbtp.enseignants.bulk-availability") }}?' + ids.map(id => 'ids[]=' + encodeURIComponent(id)).join('&');
        });
    }

    updateCount();
})();
</script>
@endpush
