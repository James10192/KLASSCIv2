@extends('layouts.app')

@section('title', 'Gestion des présences')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ── KPI Cards ────────────────────────────────────────────── */
    .att-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .att-kpi-card {
        background: #fff;
        border-radius: var(--radius-large);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .att-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.10);
    }

    .att-kpi-card::after {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 4px;
        border-radius: var(--radius-large) var(--radius-large) 0 0;
    }

    .att-kpi-card.kpi-green::after  { background: #10b981; }
    .att-kpi-card.kpi-red::after    { background: #e53e3e; }
    .att-kpi-card.kpi-orange::after { background: #f97316; }
    .att-kpi-card.kpi-blue::after   { background: #0453cb; }

    .att-kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; color: #fff;
    }

    .att-kpi-card.kpi-green  .att-kpi-icon { background: #10b981; }
    .att-kpi-card.kpi-red    .att-kpi-icon { background: #e53e3e; }
    .att-kpi-card.kpi-orange .att-kpi-icon { background: #f97316; }
    .att-kpi-card.kpi-blue   .att-kpi-icon { background: #0453cb; }

    .att-kpi-label {
        font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.6px;
        color: var(--text-secondary);
    }

    .att-kpi-value {
        font-size: 2.4rem; font-weight: 800; line-height: 1;
        color: var(--text-primary);
    }

    .att-kpi-meta {
        font-size: 0.8rem; color: var(--text-secondary);
        display: flex; align-items: center; gap: 0.4rem;
    }

    .att-kpi-bar {
        height: 4px; border-radius: 2px;
        background: rgba(0,0,0,0.06); overflow: hidden; margin-top: auto;
    }

    .att-kpi-bar-fill {
        height: 100%; border-radius: 2px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .att-kpi-card.kpi-green  .att-kpi-bar-fill { background: #10b981; }
    .att-kpi-card.kpi-red    .att-kpi-bar-fill { background: #e53e3e; }
    .att-kpi-card.kpi-orange .att-kpi-bar-fill { background: #f97316; }
    .att-kpi-card.kpi-blue   .att-kpi-bar-fill { background: #0453cb; }

    /* ── Coordinator Section ─────────────────────────────────── */
    .coord-section {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        margin-bottom: var(--space-xl);
        overflow: hidden;
    }

    .coord-section-header {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        padding: 1.25rem 1.5rem;
        display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
    }

    .coord-section-title {
        color: #fff; font-size: 1rem; font-weight: 700; margin: 0; flex: 1;
    }

    .coord-section-subtitle {
        color: rgba(255,255,255,0.8); font-size: 0.82rem; margin: 0.2rem 0 0;
    }

    .coord-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .coord-kpi-item {
        background: #f8fafc;
        border-radius: var(--radius-medium);
        padding: 1rem 1.25rem;
        position: relative; overflow: hidden;
    }

    .coord-kpi-item::before {
        content: '';
        position: absolute; left: 0; top: 0; bottom: 0;
        width: 3px; border-radius: 0 2px 2px 0;
    }

    .coord-kpi-item.ck-primary::before { background: #0453cb; }
    .coord-kpi-item.ck-success::before { background: #10b981; }
    .coord-kpi-item.ck-warning::before { background: #f97316; }
    .coord-kpi-item.ck-accent::before  { background: #06b6d4; }

    .coord-kpi-num { font-size: 1.8rem; font-weight: 800; line-height: 1.1; }
    .coord-kpi-item.ck-primary .coord-kpi-num { color: #0453cb; }
    .coord-kpi-item.ck-success .coord-kpi-num { color: #10b981; }
    .coord-kpi-item.ck-warning .coord-kpi-num { color: #f97316; }
    .coord-kpi-item.ck-accent  .coord-kpi-num { color: #06b6d4; }

    .coord-kpi-lbl {
        font-size: 0.78rem; font-weight: 600; color: var(--text-secondary);
        text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 0.25rem;
    }

    .coord-kpi-sub { font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.3rem; }

    .coord-progress {
        height: 3px; background: rgba(0,0,0,0.08);
        border-radius: 2px; margin-top: 0.5rem; overflow: hidden;
    }

    .coord-progress-bar { height: 100%; border-radius: 2px; }
    .ck-primary .coord-progress-bar { background: #0453cb; }
    .ck-success .coord-progress-bar { background: #10b981; }

    .coord-alert-row {
        padding: 1rem 1.5rem;
        display: flex; flex-direction: column; gap: 0.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .coord-alert-item {
        display: flex; align-items: center; gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: var(--radius-small); font-size: 0.875rem;
    }

    .coord-alert-item.ca-warning {
        background: #fff7ed; border-left: 3px solid #f97316; color: #c2410c;
    }

    .coord-alert-item.ca-danger {
        background: #fff1f2; border-left: 3px solid #e53e3e; color: #9b1c1c;
    }

    .coord-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: var(--space-md);
        padding: 1.25rem 1.5rem;
    }

    .coord-action-btn {
        display: flex; flex-direction: column; align-items: center;
        gap: 0.5rem; padding: 1rem;
        border-radius: var(--radius-medium);
        border: 1.5px solid rgba(0,0,0,0.08);
        background: #fff; text-decoration: none;
        color: var(--text-primary); transition: all 0.25s ease;
        cursor: pointer; font-family: inherit; font-size: 0.875rem;
    }

    .coord-action-btn:hover {
        border-color: #0453cb; background: rgba(4,83,203,0.04);
        color: #0453cb; transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(4,83,203,0.12); text-decoration: none;
    }

    .coord-action-btn i { font-size: 1.4rem; color: #0453cb; }
    .coord-action-btn span { font-weight: 600; }
    .coord-action-btn small { color: var(--text-secondary); font-size: 0.75rem; }

    /* ── Quick Actions ───────────────────────────────────────── */
    .att-quick-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-xl);
    }

    .att-quick-item {
        background: #fff;
        border: 1.5px solid rgba(0,0,0,0.07);
        border-radius: var(--radius-large);
        padding: 1.25rem 1rem;
        text-align: center; text-decoration: none;
        color: var(--text-primary);
        display: flex; flex-direction: column; align-items: center; gap: 0.6rem;
        transition: all 0.25s ease;
    }

    .att-quick-item:hover {
        border-color: #0453cb; background: #0453cb; color: #fff;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(4,83,203,0.2); text-decoration: none;
    }

    .att-quick-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(4,83,203,0.08);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #0453cb; transition: all 0.25s ease;
    }

    .att-quick-item:hover .att-quick-icon { background: rgba(255,255,255,0.2); color: #fff; }
    .att-quick-title { font-weight: 700; font-size: 0.875rem; }
    .att-quick-desc  { font-size: 0.75rem; color: var(--text-secondary); transition: color 0.25s; }
    .att-quick-item:hover .att-quick-desc { color: rgba(255,255,255,0.8); }

    /* ── Chart & Classes Cards ───────────────────────────────── */
    .att-chart-card, .att-classes-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .att-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 0.5rem;
    }

    .att-card-title {
        font-size: 0.95rem; font-weight: 700; color: var(--text-primary);
        display: flex; align-items: center; gap: 0.5rem; margin: 0;
    }

    .att-card-title i { color: #0453cb; }
    .att-card-body { padding: 1.25rem 1.5rem; }

    .chart-container-premium { position: relative; height: 300px; }

    /* Classe stats */
    .classe-stats-container {
        max-height: 460px; overflow-y: auto; padding-right: 4px;
    }

    .classe-stats-container::-webkit-scrollbar { width: 4px; }
    .classe-stats-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 2px; }
    .classe-stats-container::-webkit-scrollbar-thumb { background: #0453cb; border-radius: 2px; }

    .classe-item {
        border-radius: var(--radius-medium);
        padding: 10px 12px; margin-bottom: 8px;
        background: #f8fafc; border: 1px solid transparent;
        transition: all 0.2s ease;
        animation: slideInUp 0.3s ease-out;
    }

    .classe-item:hover {
        background: rgba(4,83,203,0.04);
        border-color: rgba(4,83,203,0.15);
        transform: translateX(3px);
    }

    .classe-item.hidden { display: none !important; }

    .classe-name    { color: #0453cb; font-size: 0.9rem; font-weight: 700; }
    .classe-students { font-size: 0.75rem; color: var(--text-secondary); }

    .att-rate-badge {
        font-size: 0.75rem; font-weight: 700;
        padding: 0.2rem 0.5rem; border-radius: 20px; white-space: nowrap;
    }

    .att-rate-badge.rate-good   { background: #d1fae5; color: #065f46; }
    .att-rate-badge.rate-medium { background: #ffedd5; color: #9a3412; }
    .att-rate-badge.rate-poor   { background: #fee2e2; color: #991b1b; }

    .classe-mini-stats {
        display: flex; gap: 0.75rem;
        font-size: 0.75rem; font-weight: 600;
    }

    .mini-stat-p { color: #10b981; }
    .mini-stat-a { color: #e53e3e; }
    .mini-stat-r { color: #f97316; }
    .mini-stat-e { color: #06b6d4; }

    .seg-bar {
        height: 5px; border-radius: 3px; overflow: hidden;
        display: flex; background: #e5e7eb; margin-top: 6px;
    }

    .seg-bar-p { background: #10b981; }
    .seg-bar-a { background: #e53e3e; }
    .seg-bar-r { background: #f97316; }
    .seg-bar-e { background: #06b6d4; }

    .view-compact .classe-stats-details { display: none !important; }
    .view-compact .classe-stats-compact { display: flex !important; }
    .view-compact .classe-item          { padding: 6px 12px; margin-bottom: 4px; }
    .view-compact .classe-name          { font-size: 0.85rem; }

    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Activities & Summary ────────────────────────────────── */
    .att-activity-card, .att-summary-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .timeline {
        position: relative; max-height: 360px;
        overflow-y: auto; padding: 1rem;
    }

    .timeline-item { position: relative; padding-left: 32px; margin-bottom: 18px; }

    .timeline-item::before {
        content: ''; position: absolute;
        left: 9px; top: 16px; bottom: -18px;
        width: 2px; background: #e5e7eb;
    }

    .timeline-item:last-child::before { display: none; }

    .timeline-icon {
        position: absolute; left: 0; top: 0;
        width: 18px; height: 18px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 8px; color: #fff;
    }

    .timeline-icon.success { background: #10b981; }
    .timeline-icon.warning { background: #f97316; }
    .timeline-icon.info    { background: #06b6d4; }
    .timeline-icon.danger  { background: #e53e3e; }

    .summary-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.6rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        font-size: 0.875rem;
    }

    .summary-row:last-child { border-bottom: none; }
    .summary-row .lbl { color: var(--text-secondary); }
    .summary-row .val { font-weight: 700; }

    /* ── Filters ─────────────────────────────────────────────── */
    .att-filters-card {
        background: #f8fafc;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        padding: 1.25rem 1.5rem;
        margin-bottom: var(--space-xl);
    }

    .att-filters-card .form-label {
        font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.4px;
        color: var(--text-secondary); margin-bottom: 0.3rem;
    }

    .att-filters-card .form-control,
    .att-filters-card .form-select {
        border: 1.5px solid rgba(0,0,0,0.1);
        border-radius: var(--radius-small);
        font-size: 0.875rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .att-filters-card .form-control:focus,
    .att-filters-card .form-select:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
    }

    /* ── Table ───────────────────────────────────────────────── */
    .att-table-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden;
        margin-bottom: var(--space-xl);
    }

    .att-table { width: 100%; border-collapse: collapse; }

    .att-table thead {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    }

    .att-table thead th {
        color: rgba(255,255,255,0.92); font-weight: 600;
        font-size: 0.75rem; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 0.9rem 1rem;
        border: none; white-space: nowrap;
    }

    .att-table tbody td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        vertical-align: middle; font-size: 0.875rem;
    }

    .att-table tbody tr:last-child td { border-bottom: none; }
    .att-table tbody tr:hover { background: rgba(4,83,203,0.025); }

    .att-avatar {
        width: 36px; height: 36px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }

    .att-student-name { font-weight: 600; font-size: 0.875rem; color: var(--text-primary); }
    .att-student-id   { font-size: 0.75rem; color: var(--text-secondary); }

    .att-class-badge {
        display: inline-block;
        background: rgba(4,83,203,0.08); color: #0453cb;
        padding: 0.2rem 0.55rem; border-radius: 6px;
        font-size: 0.78rem; font-weight: 600;
    }

    .att-status-pill {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.3rem 0.7rem; border-radius: 20px;
        font-size: 0.75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
    }

    .att-status-pill.sp-present { background: #d1fae5; color: #065f46; }
    .att-status-pill.sp-absent  { background: #fee2e2; color: #991b1b; }
    .att-status-pill.sp-retard  { background: #ffedd5; color: #9a3412; }
    .att-status-pill.sp-excuse  { background: #e0f2fe; color: #0369a1; }

    .att-btn-icon {
        width: 32px; height: 32px; border-radius: 8px;
        border: 1.5px solid;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; transition: all 0.2s ease;
        cursor: pointer; text-decoration: none; background: #fff;
    }

    .att-btn-icon.abi-view { border-color: rgba(4,83,203,0.3); color: #0453cb; }
    .att-btn-icon.abi-view:hover { background: #0453cb; color: #fff; border-color: #0453cb; }
    .att-btn-icon.abi-edit { border-color: rgba(249,115,22,0.3); color: #f97316; }
    .att-btn-icon.abi-edit:hover { background: #f97316; color: #fff; border-color: #f97316; }

    .att-empty { padding: 3rem 1rem; text-align: center; color: var(--text-secondary); }

    .att-empty-icon {
        width: 72px; height: 72px; border-radius: 20px;
        background: rgba(4,83,203,0.06);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; color: #0453cb; opacity: 0.5;
        margin: 0 auto 1rem;
    }

    /* ── Modal override ──────────────────────────────────────── */
    .modal-header {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    }

    .modal-header .modal-title { color: #fff; font-weight: 700; }
    .modal-header .btn-close { filter: invert(1); }

    .detail-dl dt {
        font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.4px;
        color: var(--text-secondary);
    }

    .detail-dl dd { font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; }

    /* ── Search wrap ─────────────────────────────────────────── */
    .att-search-wrap { position: relative; }

    .att-search-wrap input {
        padding-left: 2.25rem; font-size: 0.82rem;
        border: 1.5px solid rgba(0,0,0,0.1);
        border-radius: var(--radius-medium);
    }

    .att-search-wrap input:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
        outline: none;
    }

    .att-search-icon {
        position: absolute; left: 0.7rem; top: 50%; transform: translateY(-50%);
        color: var(--text-secondary); font-size: 0.8rem; pointer-events: none;
    }

    .classe-pagination { border-top: 1px solid rgba(0,0,0,0.06); padding-top: 10px; margin-top: 10px; text-align: center; }
    .no-results { padding: 2rem; text-align: center; color: var(--text-secondary); }

    @media (max-width: 768px) {
        .att-kpi-grid    { grid-template-columns: repeat(2, 1fr); }
        .att-quick-grid  { grid-template-columns: repeat(2, 1fr); }
        .coord-kpi-grid  { grid-template-columns: repeat(2, 1fr); }
        .att-kpi-value   { font-size: 1.8rem; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── Header ──────────────────────────────────────────── --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-users-class me-2"></i>Gestion des Présences</h1>
                <p class="header-subtitle">Suivi et analyse des présences étudiantes en temps réel</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.attendances.create') }}" class="btn-acasi primary me-2">
                    <i class="fas fa-plus-circle"></i>Marquer Présences
                </a>
                <a href="{{ route('esbtp.attendances.rapport-form') }}" class="btn-acasi secondary">
                    <i class="fas fa-chart-bar"></i>Générer Rapport
                </a>
            </div>
        </div>

        {{-- ── KPI Cards ────────────────────────────────────────── --}}
        @php
            $total = $stats['total'] ?? 0;
            $pPct  = $total > 0 ? round(($stats['total_present_with_retards'] ?? 0) / $total * 100, 1) : 0;
            $aPct  = $total > 0 ? round(($stats['absent']  ?? 0) / $total * 100, 1) : 0;
            $rPct  = $total > 0 ? round(($stats['retard']  ?? 0) / $total * 100, 1) : 0;
            $ePct  = $total > 0 ? round(($stats['excuse']  ?? 0) / $total * 100, 1) : 0;
        @endphp

        <div class="att-kpi-grid">
            <div class="att-kpi-card kpi-green">
                <div class="att-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div class="att-kpi-label">Présences</div>
                <div class="att-kpi-value">{{ $stats['total_present_with_retards'] ?? 0 }}</div>
                <div class="att-kpi-meta">
                    <i class="fas fa-percentage" style="font-size:.7rem;"></i>{{ $pPct }}% du total
                    @if(($stats['retard'] ?? 0) > 0)&nbsp;·&nbsp;<small>dont {{ $stats['retard'] }} retard(s)</small>@endif
                </div>
                <div class="att-kpi-bar"><div class="att-kpi-bar-fill" style="width:{{ $pPct }}%"></div></div>
            </div>

            <div class="att-kpi-card kpi-red">
                <div class="att-kpi-icon"><i class="fas fa-times-circle"></i></div>
                <div class="att-kpi-label">Absences</div>
                <div class="att-kpi-value">{{ $stats['absent'] ?? 0 }}</div>
                <div class="att-kpi-meta">
                    <i class="fas fa-percentage" style="font-size:.7rem;"></i>{{ $aPct }}% du total
                </div>
                <div class="att-kpi-bar"><div class="att-kpi-bar-fill" style="width:{{ $aPct }}%"></div></div>
            </div>

            <div class="att-kpi-card kpi-orange">
                <div class="att-kpi-icon"><i class="fas fa-clock"></i></div>
                <div class="att-kpi-label">Retards</div>
                <div class="att-kpi-value">{{ $stats['retard'] ?? 0 }}</div>
                <div class="att-kpi-meta">
                    <i class="fas fa-percentage" style="font-size:.7rem;"></i>{{ $rPct }}% du total
                </div>
                <div class="att-kpi-bar"><div class="att-kpi-bar-fill" style="width:{{ $rPct }}%"></div></div>
            </div>

            <div class="att-kpi-card kpi-blue">
                <div class="att-kpi-icon"><i class="fas fa-notes-medical"></i></div>
                <div class="att-kpi-label">Excusés</div>
                <div class="att-kpi-value">{{ $stats['excuse'] ?? 0 }}</div>
                <div class="att-kpi-meta">
                    <i class="fas fa-percentage" style="font-size:.7rem;"></i>{{ $ePct }}% du total
                </div>
                <div class="att-kpi-bar"><div class="att-kpi-bar-fill" style="width:{{ $ePct }}%"></div></div>
            </div>
        </div>

        {{-- ── Section Coordinateur ────────────────────────────── --}}
        @if(auth()->user() && auth()->user()->hasRole('coordinateur') && $coordinatorStats)
        <div class="coord-section">
            <div class="coord-section-header">
                <div>
                    <p class="coord-section-title">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Suivi des Émargements Enseignants — Aujourd'hui
                    </p>
                    <p class="coord-section-subtitle">Supervision en temps réel de l'activité pédagogique</p>
                </div>
                @if($unreadNotifications > 0)
                <a href="{{ route('notifications.index') }}" class="btn-acasi warning btn-sm">
                    <i class="fas fa-bell"></i>{{ $unreadNotifications }} notifications
                </a>
                @endif
            </div>

            <div class="coord-kpi-grid">
                <div class="coord-kpi-item ck-primary">
                    <div class="coord-kpi-lbl">Émargements</div>
                    <div class="coord-kpi-num">{{ $coordinatorStats['teacher_attendances_today'] ?? 0 }}</div>
                    <div class="coord-kpi-sub">sur {{ $coordinatorStats['scheduled_courses_today'] ?? 0 }} cours prévus</div>
                    <div class="coord-progress">
                        <div class="coord-progress-bar" style="width:{{ $coordinatorStats['teacher_attendance_rate'] ?? 0 }}%"></div>
                    </div>
                </div>

                <div class="coord-kpi-item ck-success">
                    <div class="coord-kpi-lbl">Appels Terminés</div>
                    <div class="coord-kpi-num">{{ $coordinatorStats['roll_calls_completed_today'] ?? 0 }}</div>
                    <div class="coord-kpi-sub">{{ $coordinatorStats['students_present_today'] ?? 0 }} présences enregistrées</div>
                    <div class="coord-progress">
                        <div class="coord-progress-bar" style="width:{{ $coordinatorStats['roll_call_completion_rate'] ?? 0 }}%"></div>
                    </div>
                </div>

                <div class="coord-kpi-item ck-warning">
                    <div class="coord-kpi-lbl">Retards Détectés</div>
                    <div class="coord-kpi-num">{{ $coordinatorStats['delays_today'] ?? 0 }}</div>
                    <div class="coord-kpi-sub">
                        @if(($coordinatorStats['delays_today'] ?? 0) > 0)
                            <span style="color:#f97316;">⚠ Attention requise</span>
                        @else
                            Aucun retard ce jour
                        @endif
                    </div>
                </div>

                <div class="coord-kpi-item ck-accent">
                    <div class="coord-kpi-lbl">Cours Clôturés</div>
                    <div class="coord-kpi-num">{{ $coordinatorStats['courses_closed_today'] ?? 0 }}</div>
                    <div class="coord-kpi-sub">séances terminées</div>
                </div>
            </div>

            @if(($coordinatorStats['delays_today'] ?? 0) > 0 || ($coordinatorStats['high_absence_classes'] ?? 0) > 0)
            <div class="coord-alert-row">
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--text-secondary);margin-bottom:.25rem;">
                    <i class="fas fa-bell me-1"></i>Alertes du jour
                </div>
                @if(($coordinatorStats['delays_today'] ?? 0) > 0)
                <div class="coord-alert-item ca-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>{{ $coordinatorStats['delays_today'] }} retard(s) d'émargement</strong>
                        <div style="font-size:.8rem;opacity:.85;">Des enseignants n'ont pas émargé à temps</div>
                    </div>
                </div>
                @endif
                @if(($coordinatorStats['high_absence_classes'] ?? 0) > 0)
                <div class="coord-alert-item ca-danger">
                    <i class="fas fa-users-slash"></i>
                    <div>
                        <strong>{{ $coordinatorStats['high_absence_classes'] }} classe(s) avec forte absentéisme</strong>
                        <div style="font-size:.8rem;opacity:.85;">Plus de 30% d'absences détectées</div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <div class="coord-actions-grid">
                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="coord-action-btn">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Émargements</span>
                    <small>Voir tous les enseignants</small>
                </a>
                <a href="{{ route('notifications.index') }}" class="coord-action-btn">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <small>{{ $unreadNotifications ?? 0 }} non lues</small>
                </a>
                <button class="coord-action-btn" onclick="generateDailyReport(event)">
                    <i class="fas fa-chart-line"></i>
                    <span>Rapport du Jour</span>
                    <small>Générer le récap</small>
                </button>
                <button class="coord-action-btn" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i>
                    <span>Actualiser</span>
                    <small>Données en temps réel</small>
                </button>
            </div>
        </div>
        @endif

        {{-- ── Quick Actions ────────────────────────────────────── --}}
        <div class="att-quick-grid">
            <a href="{{ route('esbtp.attendances.create') }}" class="att-quick-item">
                <div class="att-quick-icon"><i class="fas fa-plus-circle"></i></div>
                <div class="att-quick-title">Marquer Présences</div>
                <div class="att-quick-desc">Enregistrer les présences</div>
            </a>
            <a href="{{ route('esbtp.attendances.rapport-form') }}" class="att-quick-item">
                <div class="att-quick-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="att-quick-title">Générer Rapport</div>
                <div class="att-quick-desc">Analyser les données</div>
            </a>
            <a href="#" class="att-quick-item" onclick="exportData()">
                <div class="att-quick-icon"><i class="fas fa-file-export"></i></div>
                <div class="att-quick-title">Exporter Données</div>
                <div class="att-quick-desc">CSV, Excel, PDF</div>
            </a>
            <a href="#" class="att-quick-item" onclick="showStatistics()">
                <div class="att-quick-icon"><i class="fas fa-analytics"></i></div>
                <div class="att-quick-title">Statistiques</div>
                <div class="att-quick-desc">Analyse approfondie</div>
            </a>
        </div>

        {{-- ── Chart + Classes Stats ─────────────────────────────── --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="att-chart-card h-100">
                    <div class="att-card-header">
                        <h6 class="att-card-title">
                            <i class="fas fa-chart-line"></i>Tendance des 7 Derniers Jours
                        </h6>
                        <div style="display:flex;gap:.75rem;font-size:.75rem;flex-wrap:wrap;color:var(--text-secondary);">
                            <span style="display:flex;align-items:center;gap:.3rem;"><span style="width:10px;height:3px;background:#10b981;border-radius:2px;display:inline-block;"></span>Présences</span>
                            <span style="display:flex;align-items:center;gap:.3rem;"><span style="width:10px;height:3px;background:#e53e3e;border-radius:2px;display:inline-block;"></span>Absences</span>
                            <span style="display:flex;align-items:center;gap:.3rem;"><span style="width:10px;height:3px;background:#f97316;border-radius:2px;display:inline-block;"></span>Retards</span>
                            <span style="display:flex;align-items:center;gap:.3rem;"><span style="width:10px;height:3px;background:#06b6d4;border-radius:2px;display:inline-block;"></span>Excusés</span>
                        </div>
                    </div>
                    <div class="att-card-body">
                        <div class="chart-container-premium">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="att-classes-card h-100">
                    <div class="att-card-header">
                        <h6 class="att-card-title">
                            <i class="fas fa-graduation-cap"></i>Présences par Classe
                        </h6>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <small style="color:var(--text-secondary);">{{ count($classeStats ?? []) }} classes</small>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="compactViewBtn" title="Vue compacte" style="padding:3px 7px;font-size:.75rem;">
                                    <i class="fas fa-th-list"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary active" id="detailedViewBtn" title="Vue détaillée" style="padding:3px 7px;font-size:.75rem;">
                                    <i class="fas fa-th-large"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="att-card-body">
                        @if(isset($classeStats) && count($classeStats) > 5)
                        <div class="att-search-wrap mb-3">
                            <i class="fas fa-search att-search-icon"></i>
                            <input type="text" class="form-control form-control-sm" id="classSearchInput" placeholder="Rechercher une classe... (Ctrl+K)">
                        </div>
                        @endif

                        @if(isset($classeStats) && count($classeStats) > 0)
                        <div class="classe-stats-container" id="classeStatsContainer">
                            <div class="classe-stats-content" id="classeStatsContent">
                                @foreach($classeStats as $index => $classe)
                                @php
                                    $classTotal = $classe['total_attendance'] ?? 1;
                                    $classP = $classe['total_present_with_retards'] ?? ($classe['present'] + $classe['retard']);
                                    $classA = $classe['absent']  ?? 0;
                                    $classR = $classe['retard']  ?? 0;
                                    $classE = $classe['excuse']  ?? 0;
                                    $rate   = $classe['attendance_rate'] ?? 0;
                                    $rateClass = $rate >= 75 ? 'rate-good' : ($rate >= 50 ? 'rate-medium' : 'rate-poor');
                                @endphp
                                <div class="classe-item" data-classe-name="{{ strtolower($classe['name']) }}" data-index="{{ $index }}">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <div class="classe-name">{{ $classe['name'] }}</div>
                                            <div class="classe-students">{{ $classe['total_students'] }} étudiants</div>
                                        </div>
                                        <span class="att-rate-badge {{ $rateClass }}">{{ $rate }}%</span>
                                    </div>

                                    <div class="classe-mini-stats classe-stats-details mb-1">
                                        <span class="mini-stat-p"><i class="fas fa-check me-1"></i>{{ $classP }}</span>
                                        <span class="mini-stat-a"><i class="fas fa-times me-1"></i>{{ $classA }}</span>
                                        <span class="mini-stat-r"><i class="fas fa-clock me-1"></i>{{ $classR }}</span>
                                        <span class="mini-stat-e"><i class="fas fa-file-medical me-1"></i>{{ $classE }}</span>
                                    </div>

                                    <div class="classe-stats-compact d-none">
                                        <div style="display:flex;gap:.5rem;font-size:.72rem;">
                                            <span class="mini-stat-p">{{ $classP }}P</span>
                                            <span class="mini-stat-a">{{ $classA }}A</span>
                                            <span class="mini-stat-e">{{ $classE }}E</span>
                                        </div>
                                    </div>

                                    <div class="seg-bar">
                                        @if($classTotal > 0)
                                            <div class="seg-bar-p" style="width:{{ ($classP / $classTotal) * 100 }}%"></div>
                                            <div class="seg-bar-r" style="width:{{ ($classR / $classTotal) * 100 }}%"></div>
                                            <div class="seg-bar-e" style="width:{{ ($classE / $classTotal) * 100 }}%"></div>
                                            <div class="seg-bar-a" style="width:{{ ($classA / $classTotal) * 100 }}%"></div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            @if(count($classeStats) > 10)
                            <div class="classe-pagination" id="classePagination">
                                <button class="btn btn-sm btn-outline-primary" id="loadMoreClassesBtn">
                                    <i class="fas fa-chevron-down me-1"></i>Voir plus de classes
                                </button>
                            </div>
                            @endif

                            <div class="no-results d-none" id="noSearchResults">
                                <i class="fas fa-search fa-2x mb-2 d-block" style="opacity:.3;color:#0453cb;"></i>
                                <p style="font-size:.875rem;">Aucune classe trouvée</p>
                            </div>
                        </div>
                        @else
                        <div class="att-empty">
                            <div class="att-empty-icon"><i class="fas fa-chart-pie"></i></div>
                            <p style="font-weight:700;">Aucune donnée de présence</p>
                            <p style="font-size:.82rem;">Les statistiques apparaîtront une fois les présences enregistrées</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Activités & Résumé (Coordinateur) ───────────────── --}}
        @if(auth()->user() && auth()->user()->hasRole('coordinateur') && $coordinatorStats)
        <div class="row g-3 mb-4">
            <div class="col-xl-8">
                <div class="att-activity-card h-100">
                    <div class="att-card-header">
                        <h6 class="att-card-title"><i class="fas fa-history"></i>Activités Récentes</h6>
                        <small style="color:var(--text-secondary);">Dernières 24h</small>
                    </div>
                    <div class="timeline" id="recent-activities">
                        <div style="text-align:center;padding:2rem;color:var(--text-secondary);">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            Chargement des activités récentes...
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="att-summary-card h-100">
                    <div class="att-card-header">
                        <h6 class="att-card-title"><i class="fas fa-calendar-check"></i>Résumé du Jour</h6>
                    </div>
                    <div class="att-card-body">
                        <div class="summary-row">
                            <span class="lbl">Cours prévus</span>
                            <span class="val">{{ $coordinatorStats['scheduled_courses_today'] ?? 0 }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="lbl">Émargements</span>
                            <span class="val" style="color:#0453cb;">{{ $coordinatorStats['teacher_attendances_today'] ?? 0 }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="lbl">Taux émargement</span>
                            <span class="val" style="color:#10b981;">{{ $coordinatorStats['teacher_attendance_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="summary-row">
                            <span class="lbl">Appels terminés</span>
                            <span class="val" style="color:#06b6d4;">{{ $coordinatorStats['roll_calls_completed_today'] ?? 0 }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="lbl">Présences enregistrées</span>
                            <span class="val" style="color:#10b981;">{{ $coordinatorStats['students_present_today'] ?? 0 }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="lbl">Cours clôturés</span>
                            <span class="val">{{ $coordinatorStats['courses_closed_today'] ?? 0 }}</span>
                        </div>
                        @if(($coordinatorStats['delays_today'] ?? 0) > 0)
                        <div style="margin-top:.75rem;padding:.6rem .75rem;background:#fff7ed;border-radius:var(--radius-small);border-left:3px solid #f97316;font-size:.82rem;color:#c2410c;">
                            <strong>{{ $coordinatorStats['delays_today'] }} retard(s)</strong> détecté(s)
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Filtres ──────────────────────────────────────────── --}}
        <div class="att-filters-card">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
                <i class="fas fa-filter" style="color:#0453cb;"></i>
                <span style="font-weight:700;font-size:.95rem;color:var(--text-primary);">Filtres de Recherche</span>
            </div>
            <form action="{{ route('esbtp.attendances.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-2 col-sm-4 col-6">
                        <label for="classe_id" class="form-label">Classe</label>
                        <select name="classe_id" id="classe_id" class="form-select form-select-sm">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>{{ $classe->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <label for="matiere_id" class="form-label">Matière</label>
                        <select name="matiere_id" id="matiere_id" class="form-select form-select-sm">
                            <option value="">Toutes les matières</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>{{ $matiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <label for="etudiant_id" class="form-label">Étudiant</label>
                        <select name="etudiant_id" id="etudiant_id" class="form-select form-select-sm">
                            <option value="">Tous les étudiants</option>
                            @foreach($etudiants as $etudiant)
                                <option value="{{ $etudiant->id }}" {{ request('etudiant_id') == $etudiant->id ? 'selected' : '' }}>{{ $etudiant->nom_complet }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" class="form-control form-control-sm" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" class="form-control form-control-sm" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <label for="statut" class="form-label">Statut</label>
                        <select name="statut" id="statut" class="form-select form-select-sm">
                            <option value="">Tous les statuts</option>
                            <option value="present" {{ request('statut') == 'present' ? 'selected' : '' }}>Présent</option>
                            <option value="absent"  {{ request('statut') == 'absent'  ? 'selected' : '' }}>Absent</option>
                            <option value="retard"  {{ request('statut') == 'retard'  ? 'selected' : '' }}>Retard</option>
                            <option value="excuse"  {{ request('statut') == 'excuse'  ? 'selected' : '' }}>Excusé</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:.5rem;margin-top:1rem;">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-search"></i>Filtrer
                    </button>
                    <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-redo"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        {{-- ── Table ────────────────────────────────────────────── --}}
        <div class="att-table-card">
            <div class="att-card-header">
                <h6 class="att-card-title">
                    <i class="fas fa-table"></i>Liste des Présences
                </h6>
                <span style="background:rgba(4,83,203,.08);color:#0453cb;padding:.2rem .6rem;border-radius:20px;font-size:.78rem;font-weight:700;">
                    {{ $attendances->count() }} enregistrements
                </span>
            </div>
            <div class="table-responsive">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th>Matière</th>
                            <th>Statut</th>
                            <th>Enseignant</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $avatarColors = ['#0453cb','#10b981','#f97316','#06b6d4','#0891b2','#dc6803','#0369a1']; @endphp
                        @forelse($attendances as $attendance)
                        @php $avatarBg = $avatarColors[abs(crc32($attendance->etudiant->nom_complet)) % count($avatarColors)]; @endphp
                        <tr>
                            <td>
                                <div style="font-weight:700;font-size:.875rem;">{{ $attendance->date->format('d/m/Y') }}</div>
                                <div style="font-size:.75rem;color:var(--text-secondary);">{{ $attendance->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:.6rem;">
                                    <div class="att-avatar" style="background:{{ $avatarBg }};">
                                        {{ strtoupper(substr($attendance->etudiant->nom_complet, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="att-student-name">{{ $attendance->etudiant->nom_complet }}</div>
                                        <div class="att-student-id">#{{ $attendance->etudiant->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="att-class-badge">
                                    {{ $attendance->classe->name ?? ($attendance->etudiant->classe->name ?? 'N/A') }}
                                </span>
                            </td>
                            <td style="color:var(--text-secondary);">
                                {{ $attendance->matiere->name ?? ($attendance->seanceCours->matiere->name ?? 'N/A') }}
                            </td>
                            <td>
                                @if($attendance->statut === 'present')
                                    <span class="att-status-pill sp-present"><i class="fas fa-check-circle"></i>Présent</span>
                                @elseif($attendance->statut === 'absent')
                                    <span class="att-status-pill sp-absent"><i class="fas fa-times-circle"></i>Absent</span>
                                @elseif($attendance->statut === 'retard' || $attendance->statut === 'late')
                                    <span class="att-status-pill sp-retard"><i class="fas fa-clock"></i>Retard</span>
                                @elseif($attendance->statut === 'excuse')
                                    <span class="att-status-pill sp-excuse"><i class="fas fa-file-medical"></i>Excusé</span>
                                @else
                                    <span class="att-status-pill" style="background:#f1f5f9;color:var(--text-secondary);">{{ ucfirst($attendance->statut) }}</span>
                                @endif
                            </td>
                            <td style="font-size:.875rem;color:var(--text-secondary);">
                                {{ $attendance->teacher->user->name ?? 'N/A' }}
                            </td>
                            <td>
                                <div style="display:flex;gap:.4rem;justify-content:center;">
                                    <button type="button" class="att-btn-icon abi-view"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailsModal{{ $attendance->id }}"
                                            title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="{{ route('esbtp.attendances.edit', $attendance) }}"
                                       class="att-btn-icon abi-edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="att-empty">
                                    <div class="att-empty-icon"><i class="fas fa-user-times"></i></div>
                                    <p style="font-weight:700;color:var(--text-primary);">Aucune présence enregistrée</p>
                                    <p style="font-size:.82rem;">Commencez par marquer les présences des étudiants</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($attendances->hasPages())
            <div style="display:flex;justify-content:center;padding:1rem 1.5rem;">
                {{ $attendances->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ── Modales de détails ──────────────────────────────────────── --}}
@foreach($attendances as $attendance)
<div class="modal fade" id="detailsModal{{ $attendance->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Détails de la Présence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <dl class="row detail-dl">
                    <dt class="col-sm-4">Étudiant</dt>
                    <dd class="col-sm-8">{{ $attendance->etudiant->nom_complet }}</dd>

                    <dt class="col-sm-4">Classe</dt>
                    <dd class="col-sm-8">{{ $attendance->classe->name ?? ($attendance->etudiant->classe->name ?? 'N/A') }}</dd>

                    <dt class="col-sm-4">Matière</dt>
                    <dd class="col-sm-8">{{ $attendance->matiere->name ?? ($attendance->seanceCours->matiere->name ?? 'N/A') }}</dd>

                    <dt class="col-sm-4">Date</dt>
                    <dd class="col-sm-8">{{ $attendance->date->format('d/m/Y') }}</dd>

                    <dt class="col-sm-4">Statut</dt>
                    <dd class="col-sm-8">
                        @if($attendance->statut === 'present')
                            <span class="att-status-pill sp-present"><i class="fas fa-check-circle"></i>Présent</span>
                        @elseif($attendance->statut === 'absent')
                            <span class="att-status-pill sp-absent"><i class="fas fa-times-circle"></i>Absent</span>
                        @elseif($attendance->statut === 'retard' || $attendance->statut === 'late')
                            <span class="att-status-pill sp-retard"><i class="fas fa-clock"></i>Retard</span>
                        @elseif($attendance->statut === 'excuse')
                            <span class="att-status-pill sp-excuse"><i class="fas fa-file-medical"></i>Excusé</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Enseignant</dt>
                    <dd class="col-sm-8">{{ $attendance->teacher->user->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Créé le</dt>
                    <dd class="col-sm-8">{{ $attendance->created_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique des tendances
const ctx = document.getElementById('attendanceChart').getContext('2d');
const attendanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_map(function($date) {
            return \Carbon\Carbon::parse($date)->format('d/m');
        }, array_keys($statsParStatus ?? []))) !!},
        datasets: [{
            label: 'Présences',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'present_with_retards')) !!},
            borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.08)',
            borderWidth: 2.5, fill: true, tension: 0.4,
            pointBackgroundColor: '#10b981', pointRadius: 4, pointHoverRadius: 6
        }, {
            label: 'Absences',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'absent')) !!},
            borderColor: '#e53e3e', backgroundColor: 'rgba(229,62,62,.08)',
            borderWidth: 2.5, fill: true, tension: 0.4,
            pointBackgroundColor: '#e53e3e', pointRadius: 4, pointHoverRadius: 6
        }, {
            label: 'Retards',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'retard')) !!},
            borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.08)',
            borderWidth: 2.5, fill: true, tension: 0.4,
            pointBackgroundColor: '#f97316', pointRadius: 4, pointHoverRadius: 6
        }, {
            label: 'Excusés',
            data: {!! json_encode(array_column($statsParStatus ?? [], 'excuse')) !!},
            borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,.08)',
            borderWidth: 2.5, fill: true, tension: 0.4,
            pointBackgroundColor: '#06b6d4', pointRadius: 4, pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b', titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,.8)', padding: 12, cornerRadius: 8
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

function exportData()     { alert('Fonction d\'export en cours de développement'); }
function showStatistics() { alert('Page de statistiques détaillées en cours de développement'); }

@if(auth()->user() && auth()->user()->hasRole('coordinateur') && $coordinatorStats)
function refreshData() { location.reload(); }

function generateDailyReport(event) {
    const btn = event.target.closest('.coord-action-btn');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:1.4rem;color:#0453cb;"></i><span>Génération...</span><small></small>';
    btn.disabled = true;

    fetch('{{ route("coordinateur.daily-report") ?? "#" }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ date: new Date().toISOString().split('T')[0] })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) showDailyReportModal(data.report);
        else alert('Erreur: ' + (data.error || 'Erreur inconnue'));
    })
    .catch(() => alert('Erreur de connexion'))
    .finally(() => { btn.innerHTML = orig; btn.disabled = false; });
}

function showDailyReportModal(report) {
    alert('Rapport du ' + report.date + ':\n\nCours prévus: ' + report.summary.cours_prevus +
          '\nÉmargements: ' + report.summary.emargements_effectues +
          '\nTaux: ' + report.summary.taux_emargement +
          '\nAppels terminés: ' + report.summary.appels_termines);
}

document.addEventListener('DOMContentLoaded', function() {
    loadRecentActivities();
    let activitiesTimer = setInterval(loadRecentActivities, 300000);
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(activitiesTimer);
        } else {
            activitiesTimer = setInterval(loadRecentActivities, 300000);
        }
    });
});

function loadRecentActivities() {
    const el = document.getElementById('recent-activities');
    if (!el) return;

    fetch('{{ route("coordinateur.recent-activities") ?? "#" }}?limit=10', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.activities) {
            if (!data.activities.length) {
                el.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary);"><i class="fas fa-history fa-2x mb-2 d-block" style="opacity:.3;"></i><p style="font-size:.875rem;">Aucune activité récente</p></div>';
            } else {
                el.innerHTML = data.activities.map(a => `
                    <div class="timeline-item">
                        <div class="timeline-icon ${a.type}"><i class="fas fa-${a.icon}"></i></div>
                        <div class="timeline-content">
                            <h6 class="mb-1" style="font-size:.875rem;">${a.title}</h6>
                            <p class="text-muted mb-1" style="font-size:.8rem;">${a.description}</p>
                            <small class="text-muted">${a.time}</small>
                        </div>
                    </div>`).join('');
            }
        } else {
            el.innerHTML = '<div style="text-align:center;padding:2rem;color:#e53e3e;"><i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i><p style="font-size:.875rem;">Erreur de chargement</p></div>';
        }
    })
    .catch(() => {
        el.innerHTML = '<div style="text-align:center;padding:2rem;color:#f97316;"><i class="fas fa-wifi fa-2x mb-2 d-block"></i><p style="font-size:.875rem;">Impossible de charger les activités</p><button class="btn btn-sm btn-outline-primary mt-2" onclick="loadRecentActivities()">Réessayer</button></div>';
    });
}
@endif

// Gestion des stats par classe
document.addEventListener('DOMContentLoaded', function() {
    const compactViewBtn       = document.getElementById('compactViewBtn');
    const detailedViewBtn      = document.getElementById('detailedViewBtn');
    const classSearchInput     = document.getElementById('classSearchInput');
    const classeStatsContainer = document.getElementById('classeStatsContainer');
    const classeStatsContent   = document.getElementById('classeStatsContent');
    const loadMoreBtn          = document.getElementById('loadMoreClassesBtn');
    const noSearchResults      = document.getElementById('noSearchResults');

    let currentView = 'detailed';
    let visibleItemsCount = 10;
    let allItems = [];
    let filteredItems = [];

    if (classeStatsContent) {
        allItems = Array.from(classeStatsContent.querySelectorAll('.classe-item'));
        filteredItems = [...allItems];
        initializePagination();
    }

    if (compactViewBtn && detailedViewBtn) {
        compactViewBtn.addEventListener('click', switchToCompactView);
        detailedViewBtn.addEventListener('click', switchToDetailedView);
    }

    if (classSearchInput) {
        classSearchInput.addEventListener('input', function() { filterClasses(this.value.toLowerCase().trim()); });
        classSearchInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') e.preventDefault(); });
    }

    if (loadMoreBtn) loadMoreBtn.addEventListener('click', showMoreClasses);

    function switchToCompactView() {
        currentView = 'compact';
        compactViewBtn.classList.add('active'); detailedViewBtn.classList.remove('active');
        if (classeStatsContainer) classeStatsContainer.classList.add('view-compact');
        visibleItemsCount = Math.min(20, filteredItems.length);
        updateVisibility(); animateViewChange();
    }

    function switchToDetailedView() {
        currentView = 'detailed';
        detailedViewBtn.classList.add('active'); compactViewBtn.classList.remove('active');
        if (classeStatsContainer) classeStatsContainer.classList.remove('view-compact');
        visibleItemsCount = Math.min(10, filteredItems.length);
        updateVisibility(); animateViewChange();
    }

    function filterClasses(searchTerm) {
        filteredItems = searchTerm
            ? allItems.filter(item => {
                const el = item.querySelector('.classe-name');
                return (item.dataset.classeName || '').includes(searchTerm) ||
                       (el ? el.textContent.toLowerCase() : '').includes(searchTerm);
              })
            : [...allItems];

        visibleItemsCount = currentView === 'compact' ? 20 : 10;
        updateVisibility();

        if (filteredItems.length === 0 && searchTerm) showNoResults();
        else hideNoResults();

        if (classeStatsContainer) classeStatsContainer.scrollTop = 0;
    }

    function showMoreClasses() {
        const inc = currentView === 'compact' ? 20 : 10;
        visibleItemsCount = Math.min(visibleItemsCount + inc, filteredItems.length);
        updateVisibility();
        filteredItems.slice(visibleItemsCount - inc, visibleItemsCount).forEach((item, i) => {
            setTimeout(() => { item.style.animation = 'slideInUp 0.3s ease-out'; }, i * 50);
        });
    }

    function updateVisibility() {
        allItems.forEach(item => item.classList.add('hidden'));
        filteredItems.slice(0, visibleItemsCount).forEach(item => item.classList.remove('hidden'));
        if (loadMoreBtn) {
            if (visibleItemsCount < filteredItems.length) {
                loadMoreBtn.style.display = 'block';
                loadMoreBtn.innerHTML = `<i class="fas fa-chevron-down me-1"></i>Voir plus (${filteredItems.length - visibleItemsCount} restantes)`;
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }
    }

    function initializePagination() {
        visibleItemsCount = Math.min(currentView === 'compact' ? 20 : 10, filteredItems.length);
        updateVisibility();
    }

    function showNoResults() {
        if (noSearchResults) noSearchResults.classList.remove('d-none');
        if (loadMoreBtn) loadMoreBtn.style.display = 'none';
    }

    function hideNoResults() {
        if (noSearchResults) noSearchResults.classList.add('d-none');
    }

    function animateViewChange() {
        filteredItems.slice(0, visibleItemsCount).forEach((item, i) => {
            item.style.animation = 'none';
            setTimeout(() => { item.style.animation = 'slideInUp 0.3s ease-out'; }, i * 20);
        });
    }

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (window.innerWidth < 768) {
                visibleItemsCount = Math.min(currentView === 'detailed' ? 8 : 15, filteredItems.length);
                updateVisibility();
            }
        }, 250);
    });

    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (classSearchInput) { classSearchInput.focus(); classSearchInput.select(); }
        }
    });
});
</script>
@endpush
