@extends('layouts.app')

@section('title', 'Gestion des annonces - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
<style>
/* Styles spécifiques pour la page des annonces */
.announcements-page {
    background: var(--background);
    min-height: 100vh;
    padding: 0;
    overflow-x: hidden;
}

.announcement-header {
    background: linear-gradient(135deg, #0b3b86 0%, #0453cb 55%, #2b7bda 100%);
    color: #ffffff;
    padding: var(--space-xl) var(--space-xl);
    border-radius: var(--radius-medium);
    margin-bottom: var(--space-lg);
    box-shadow: 0 20px 40px rgba(4, 83, 203, 0.25);
    position: relative;
    overflow: hidden;
}

.announcement-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.15), transparent 55%);
    pointer-events: none;
}

.announcement-header h1 {
    color: #ffffff;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
}

.announcement-header .header-subtitle {
    color: rgba(224, 231, 255, 0.9);
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.btn-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-modern.primary {
    background: #0453cb;
    color: white;
    box-shadow: 0 2px 4px rgba(4, 83, 203, 0.3);
}

.btn-modern.primary:hover {
    background: #1b64d4;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(4, 83, 203, 0.4);
    color: white;
}

.btn-modern.secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-modern.secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(145deg, #ffffff, #f6f9ff);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.25);
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    box-shadow: 0 12px 20px rgba(15, 23, 42, 0.18);
}

.stat-icon.primary { background: linear-gradient(135deg, #0453cb, #1b64d4); }
.stat-icon.success { background: linear-gradient(135deg, #10b981, #059669); }
.stat-icon.warning { background: linear-gradient(135deg, #5e91de, #3b7ddb); }
.stat-icon.danger { background: linear-gradient(135deg, #0f172a, #334155); }

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}

.stat-content p {
    margin: 0.25rem 0 0;
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
}

.content-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.2);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.08));
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-container {
    position: relative;
    max-width: 300px;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.9rem;
}

.table-responsive {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    padding: 0 1rem 1rem;
}

.modern-table thead th {
    background: rgba(4, 83, 203, 0.08);
    padding: 1rem;
    font-weight: 600;
    color: #374151;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.modern-table tbody tr {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.modern-table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 26px rgba(15, 23, 42, 0.12);
}

/* Styles pour les annonces expirées */
.modern-table tbody tr.expired-row {
    background-color: rgba(239, 68, 68, 0.05);
    opacity: 0.8;
}

.modern-table tbody tr.expired-row:hover {
    background-color: rgba(239, 68, 68, 0.1);
}

.modern-table tbody tr.expired-row td {
    color: #6b7280;
}

.modern-table tbody tr.expired-row .table-title {
    text-decoration: line-through;
    color: #9ca3af;
}

.modern-table tbody td {
    padding: 1rem;
    border-bottom: none;
    vertical-align: middle;
    background: #ffffff;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

.modern-table tbody td:first-child {
    border-left: 1px solid rgba(148, 163, 184, 0.2);
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}

.modern-table tbody td:last-child {
    border-right: 1px solid rgba(148, 163, 184, 0.2);
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
}

.table-title {
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge, .priority-badge, .type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-badge.success {
    background: #dcfce7;
    color: #166534;
}

.status-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.priority-badge.priority-2 {
    background: #fecaca;
    color: #991b1b;
}

.priority-badge.priority-1 {
    background: #fed7aa;
    color: #9a3412;
}

.priority-badge.priority-0 {
    background: #e5e7eb;
    color: #374151;
}

.type-badge {
    background: #e0e7ff;
    color: #3730a3;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
}

.btn-action.primary {
    background: rgba(59, 130, 246, 0.15);
    color: #1d4ed8;
}

.btn-action.primary:hover {
    background: #3b82f6;
    color: white;
}

.btn-action.secondary {
    background: rgba(148, 163, 184, 0.2);
    color: #475569;
}

.btn-action.secondary:hover {
    background: #6b7280;
    color: white;
}

.btn-action.danger {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

.btn-action.danger:hover {
    background: #ef4444;
    color: white;
}

.btn-action.disabled {
    background: #f9fafb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    opacity: 0.5;
}

.btn-action.disabled:hover {
    background: #f9fafb !important;
    color: #9ca3af !important;
    transform: none !important;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state i {
    color: #d1d5db !important;
    margin-bottom: 1rem;
}

.empty-state h5 {
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #9ca3af;
    margin: 0;
}

.alert-modern {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.alert-modern.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-modern.error {
    background: #fecaca;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Vue grille pour mobile */
.annonces-grid {
    display: none;
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1rem;
}

.annonce-card {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    border: 1px solid rgba(148, 163, 184, 0.25);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    transition: all 0.25s ease;
}

.annonce-card:hover {
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12);
    transform: translateY(-4px);
}

.annonce-card.expired {
    background-color: rgba(239, 68, 68, 0.05);
    opacity: 0.8;
}

.annonce-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 0.75rem;
}

.annonce-card-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 1.125rem;
    margin: 0;
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.annonce-card-title.expired {
    text-decoration: line-through;
    color: #9ca3af;
}

.annonce-card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.annonce-card-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: rgba(4, 83, 203, 0.06);
    border-radius: 12px;
}

.annonce-card-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.annonce-card-info-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.annonce-card-info-value {
    font-size: 0.875rem;
    color: #1f2937;
    font-weight: 600;
}

.annonce-card-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
    .announcement-header {
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-md);
    }

    .header-actions {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
        margin-top: 1rem;
    }

    .btn-modern {
        width: 100%;
        justify-content: center;
        font-size: 0.875rem;
        padding: 0.625rem 1rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .search-container {
        max-width: 100%;
    }

    /* Cacher le tableau et afficher la grille sur mobile */
    .table-responsive {
        display: none !important;
    }

    .annonces-grid {
        display: grid !important;
    }

    .ann-bas-tableau {
        display: none !important;
    }
}

@media (min-width: 769px) {
    .annonces-grid {
        display: none !important;
    }

    .ann-bas-grille {
        display: none !important;
    }

    .table-responsive {
        display: block !important;
    }
}

@media (max-width: 576px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }

    .annonce-card-info {
        grid-template-columns: 1fr;
    }

    .btn-action {
        width: 100%;
        justify-content: center;
    }
}

/* Skill Apple Design appliqué au système graphique KLASSCI. */
.announcements-page .main-content{max-width:1600px;margin:auto;padding:clamp(12px,2.2vw,30px)!important}
.announcements-page .announcement-header{padding:clamp(20px,2.2vw,32px);border-radius:20px;margin-bottom:18px;box-shadow:0 10px 26px rgba(4,83,203,.14)}
.announcements-page .announcement-header h1{font-size:clamp(1.35rem,1.8vw,1.85rem);letter-spacing:-.035em;text-shadow:none}
.announcements-page .stats-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.announcements-page .stat-card{padding:15px 16px;border-radius:16px;gap:12px;box-shadow:0 3px 13px rgba(15,23,42,.035)}
.announcements-page .stat-card:hover{transform:none;box-shadow:0 7px 20px rgba(4,83,203,.07)}
.announcements-page .stat-icon{width:40px;height:40px;min-width:40px;border-radius:12px;font-size:1rem;box-shadow:none}
.announcements-page .stat-content h3{font-size:1.45rem;letter-spacing:-.04em}
.announcements-page .stat-content p{font-size:.74rem}
.ann-workspace{border-radius:20px;box-shadow:0 4px 24px rgba(15,23,42,.04);border:1px solid #e4eaf4}
.ann-workspace-heading{padding:22px 24px 14px}
.ann-eyebrow{font-size:.66rem;letter-spacing:.12em;font-weight:700;color:#4c7ac2;margin:0 0 6px}
.ann-workspace-heading h2{font-size:1.15rem;font-weight:750;letter-spacing:-.025em;margin:0;color:#142744}
.ann-workspace-heading>p:last-child{font-size:.8rem;color:#64748b;margin:4px 0 0}
.ann-filter-primary{display:grid;grid-template-columns:minmax(210px,2fr) minmax(150px,1fr) minmax(150px,1fr) auto;gap:12px;align-items:end;padding:0 24px 14px}
.ann-filter-primary>div,.ann-advanced-grid>div{min-width:0}
.ann-search-field label,.ann-filter-select>label,.ann-date-field>label{display:block;margin:0 0 6px;font-size:.72rem;font-weight:700;color:#49617f}
.ann-search-field .search-container{max-width:none;width:100%}
.ann-search-field .search-input,.ann-date-field input{height:44px;min-width:0;width:100%;border:1px solid #dbe4f2;border-radius:11px;background:#fff;padding:0 12px;font-size:.82rem;color:#142744}
.ann-search-field .search-input{padding-left:36px}
.ann-search-field .search-input:focus,.ann-date-field input:focus{outline:2px solid rgba(4,83,203,.18);border-color:#0453cb}
.ann-apply{min-height:44px;border:0;border-radius:11px;background:#0453cb;color:#fff;font-size:.81rem;font-weight:700;padding:0 17px;box-shadow:0 4px 12px rgba(4,83,203,.15);display:flex;gap:8px;align-items:center;justify-content:center}
.ann-apply:hover{background:#124cb2}
.ann-advanced{border-top:1px solid #edf2f9;margin:0 24px}
.ann-advanced>summary{display:flex;align-items:center;gap:8px;padding:13px 0;cursor:pointer;font-size:.79rem;color:#0453cb;font-weight:700;list-style:none}
.ann-advanced>summary::-webkit-details-marker{display:none}
.ann-chevron{font-size:.65rem;transition:transform .2s}
.ann-advanced[open] .ann-chevron{transform:rotate(180deg)}
.ann-advanced-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;padding:8px 0 22px}
.ann-filter-bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 24px;border-top:1px solid #edf2f9;color:#718198;font-size:.73rem}
.ann-filter-bottom a{color:#0453cb;text-decoration:none;font-weight:700;white-space:nowrap}
.ann-result-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:15px 24px;background:#f6f9fe;border-top:1px solid #edf2f9;border-bottom:1px solid #edf2f9}
.ann-result-heading strong{color:#12345d;font-size:.85rem}.ann-result-heading span{color:#718198;font-size:.75rem}
.ann-table-wrap{overflow-x:auto}
.ann-workspace .modern-table{border-spacing:0;padding:0;margin:0;min-width:800px}
.ann-workspace .modern-table thead th{padding:14px 18px;font-size:.69rem;letter-spacing:.04em;background:#f9fbff;border-bottom:1px solid #e8eef7;color:#61718b;white-space:nowrap}
.ann-workspace .modern-table tbody td{padding:16px 18px;border:0;border-bottom:1px solid #eef2f8;border-radius:0;font-size:.81rem}
.ann-workspace .modern-table tbody tr:hover{transform:none;box-shadow:none;background:#f7faff}
.ann-workspace .modern-table tbody tr:hover td{background:#f7faff}
.ann-workspace .table-title{font-size:.83rem;line-height:1.45;max-width:400px}
.ann-workspace .status-badge,.ann-workspace .priority-badge,.ann-workspace .type-badge{border-radius:999px;padding:5px 10px;font-size:.68rem;letter-spacing:0;text-transform:none}
.ann-workspace .action-buttons{gap:6px}.ann-workspace .btn-action{width:34px;height:34px;border-radius:10px}
.ann-empty{display:flex;align-items:center;justify-content:center;gap:8px;flex-direction:column;padding:45px 16px;color:#7b8ba5;text-align:center}
.ann-empty strong{color:#334155}
.ann-workspace .annonces-grid{padding:14px;gap:12px}
.ann-workspace .annonce-card{border-radius:14px;box-shadow:none;padding:16px}
.ann-workspace .annonce-card:hover{transform:none;box-shadow:0 4px 14px rgba(4,83,203,.08)}
.ann-workspace .annonce-card-info{background:#f6f9fd;border-radius:10px}
.ann-request-status{font-size:.77rem;color:#64748b;padding:0 24px;margin:0}
.ann-workspace [aria-busy="true"]{opacity:.62;transition:opacity .15s}
@media(max-width:1100px){.ann-filter-primary{grid-template-columns:repeat(2,minmax(0,1fr))}.ann-search-field{grid-column:span 2}.ann-advanced-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:768px){.announcements-page .stats-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.announcements-page .stat-card{flex-direction:row;text-align:left;padding:12px;gap:10px}.announcements-page .stat-icon{width:36px;height:36px;min-width:36px}.ann-workspace .ann-table-wrap{display:none}.ann-workspace .annonces-grid{display:grid}.ann-workspace-heading{padding:18px 16px}.ann-filter-primary{padding:0 16px 14px}.ann-advanced{margin:0 16px}.ann-filter-bottom{padding:12px 16px}.ann-result-heading{padding:12px 16px}.ann-result-hint{display:none}}
@media(min-width:769px){.ann-workspace .annonces-grid{display:none}}
@media(max-width:540px){.ann-filter-primary{grid-template-columns:1fr}.ann-search-field{grid-column:auto}.ann-advanced-grid{grid-template-columns:1fr}.ann-workspace .annonce-card-info{grid-template-columns:repeat(2,minmax(0,1fr))}.ann-filter-bottom>span{display:none}.announcements-page .stat-content h3{font-size:1.2rem}.announcements-page .stat-content p{font-size:.69rem}}
@media(prefers-reduced-motion:reduce){.announcements-page *, .ann-workspace *{animation:none!important;transition:none!important}}
</style>
@endsection

@section('content')
<div class="dashboard-acasi announcements-page">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
        <div class="dashboard-header announcement-header">
            <div class="header-left">
                <h1><i class="fas fa-bullhorn me-2"></i>Gestion des annonces</h1>
                <p class="header-subtitle">Diffusez des informations aux classes et aux étudiants.</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->can('annonces.create'))
                <a href="{{ route('esbtp.annonces.create') }}" class="btn-modern primary">
                    <i class="fas fa-plus"></i>
                    Nouvelle annonce
                </a>
                @endif
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['total'] ?? 0 }}</h3>
                    <p>Total des annonces</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['published'] ?? 0 }}</h3>
                    <p>Annonces publiées</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['pending'] ?? 0 }}</h3>
                    <p>En attente</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['urgent'] ?? 0 }}</h3>
                    <p>Urgentes</p>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-modern success">
                <i class="fas fa-check-circle"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert-modern error">
                <i class="fas fa-exclamation-circle"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <section class="content-card ann-workspace" aria-labelledby="ann-workspace-title">
            <div class="ann-workspace-heading">
                <p class="ann-eyebrow">CENTRE DE DIFFUSION</p>
                <h2 id="ann-workspace-title">Explorer les annonces</h2>
                <p>Retrouvez et suivez les communications de l'établissement.</p>
            </div>
            <form id="ann-filter-form" action="{{ route('esbtp.annonces.index') }}" method="GET" role="search">
                <div class="ann-filter-primary">
                    <div class="ann-search-field">
                        <label for="searchInput">Recherche</label>
                        <div class="search-container">
                            <i class="fas fa-search search-icon" aria-hidden="true"></i>
                            <input id="searchInput" name="q" type="search" maxlength="150"
                                   class="search-input" value="{{ request('q') }}"
                                   placeholder="Titre ou contenu…" autocomplete="off">
                        </div>
                    </div>
                    <div class="ann-filter-select">
                        <label>Statut</label>
                        <x-au-select name="statut" :value="request('statut')" icon="fa-circle-check"
                            placeholder="Tous les statuts" :options="[
                                'active' => 'Actives', 'publiee' => 'Publiées',
                                'brouillon' => 'Brouillons', 'expiree' => 'Expirées'
                            ]" />
                    </div>
                    <div class="ann-filter-select">
                        <label>Priorité</label>
                        <x-au-select name="priorite" :value="request('priorite')" icon="fa-flag"
                            placeholder="Toutes les priorités" :options="[
                                '0' => 'Normale', '1' => 'Importante', '2' => 'Urgente'
                            ]" />
                    </div>
                    <button class="ann-apply" type="submit">
                        <i class="fas fa-filter" aria-hidden="true"></i> Appliquer
                    </button>
                </div>
                <details class="ann-advanced" @if(request()->hasAny(['type', 'email', 'piece_jointe', 'auteur', 'created_from', 'created_to', 'tri'])) open @endif>
                    <summary><i class="fas fa-sliders" aria-hidden="true"></i> Filtres avancés
                        <i class="fas fa-chevron-down ann-chevron" aria-hidden="true"></i></summary>
                    <div class="ann-advanced-grid">
                        <div class="ann-filter-select">
                            <label>Destinataires</label>
                            <x-au-select name="type" :value="request('type')" icon="fa-users"
                                placeholder="Tous les publics" :options="[
                                    'general' => 'Annonce générale', 'classe' => 'Classes ciblées',
                                    'etudiant' => 'Étudiants ciblés'
                                ]" />
                        </div>
                        <div class="ann-filter-select">
                            <label>Notification par e-mail</label>
                            <x-au-select name="email" :value="request('email')" icon="fa-envelope"
                                placeholder="Tous les envois" :options="['oui' => 'Activée', 'non' => 'Désactivée']" />
                        </div>
                        <div class="ann-filter-select">
                            <label>Pièce jointe</label>
                            <x-au-select name="piece_jointe" :value="request('piece_jointe')" icon="fa-paperclip"
                                placeholder="Toutes" :options="['oui' => 'Avec pièce jointe', 'non' => 'Sans pièce jointe']" />
                        </div>
                        <div class="ann-filter-select">
                            <label>Auteur</label>
                            <x-au-select name="auteur" :value="request('auteur')" icon="fa-user"
                                placeholder="Tous les auteurs" :searchable="true"
                                :options="$auteurs->pluck('name', 'id')->all()" />
                        </div>
                        <div class="ann-date-field">
                            <label for="ann-from">Créée à partir du</label>
                            <input id="ann-from" type="date" name="created_from" value="{{ request('created_from') }}">
                        </div>
                        <div class="ann-date-field">
                            <label for="ann-to">Créée jusqu'au</label>
                            <input id="ann-to" type="date" name="created_to" value="{{ request('created_to') }}">
                        </div>
                        <div class="ann-filter-select">
                            <label>Trier les annonces</label>
                            <x-au-select name="tri" :value="request('tri')" icon="fa-arrow-down-wide-short"
                                placeholder="Plus récentes" :options="[
                                    'recent' => 'Plus récentes', 'ancien' => 'Plus anciennes',
                                    'priorite' => 'Priorité décroissante', 'expiration' => 'Expiration proche'
                                ]" />
                        </div>
                    </div>
                </details>
                <div class="ann-filter-bottom">
                    <span>Filtrage sur toutes les annonces, même celles non encore chargées.</span>
                    <a href="{{ route('esbtp.annonces.index') }}" id="ann-reset-filters">Effacer les filtres</a>
                </div>
            </form>
            <div id="ann-results" aria-live="polite" aria-busy="false">
                @include('esbtp.annonces._resultats')
            </div>
            <p class="ann-request-status" id="ann-request-status" role="status" aria-live="polite"></p>
        </section>
    </div>

    {{-- Modal de confirmation de suppression --}}
    <div class="modal fade" id="deleteAnnonceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(15,23,42,.15);">
                <div class="modal-header" style="background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;border:none;padding:1.25rem 1.5rem;">
                    <h5 class="modal-title" style="font-size:.95rem;font-weight:600;">
                        <i class="fas fa-trash-alt" style="margin-right:.4rem;opacity:.8;"></i>
                        Supprimer l'annonce
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:14px;background:rgba(239,68,68,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.3rem;color:#dc2626;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p style="font-weight:600;color:#1e293b;margin-bottom:.5rem;">Cette action est irréversible</p>
                    <p style="font-size:.85rem;color:#64748b;">L'annonce sera définitivement supprimée.</p>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:1rem 1.5rem;gap:.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:9px;font-size:.82rem;">Annuler</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger" style="border-radius:9px;font-size:.82rem;">
                        <i class="fas fa-trash-alt" style="margin-right:.3rem;"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Filtrage SQL sans rechargement : requêtes annulables, résultat de la même route.
(function () {
    const form = document.getElementById('ann-filter-form');
    const results = document.getElementById('ann-results');
    const status = document.getElementById('ann-request-status');
    const search = document.getElementById('searchInput');
    let controller = null;
    let debounce = null;
    let resetting = false;

    async function filter(url) {
        controller?.abort();
        const current = new AbortController();
        controller = current;
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Actualisation des annonces…';
        try {
            const ajax = new URL(url, location.href);
            ajax.searchParams.set('mode', 'filtered');
            const response = await fetch(ajax.toString(), {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, text/html'},
                signal: current.signal
            });
            if (!response.ok) throw Error('HTTP ' + response.status);
            const html = await response.text();
            if (current.signal.aborted) return;
            results.innerHTML = html;
            status.textContent = '';
            const final = new URL(url, location.href);
            final.searchParams.delete('mode');
            history.replaceState(null, '', final.toString());
        } catch (error) {
            if (error.name !== 'AbortError') {
                status.textContent = 'La recherche a échoué. Vous pouvez réessayer.';
            }
        } finally {
            if (controller === current) {
                controller = null;
                results.setAttribute('aria-busy', 'false');
            }
        }
    }

    function apply(event) {
        event?.preventDefault();
        const params = new URLSearchParams(new FormData(form));
        filter(form.action + '?' + params.toString());
    }
    form.addEventListener('submit', apply);
    form.addEventListener('change', event => {
        if (!resetting && event.target !== search) apply();
    });
    search.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(apply, 350);
    });
    document.getElementById('ann-reset-filters').addEventListener('click', event => {
        event.preventDefault();
        resetting = true;
        clearTimeout(debounce);
        search.value = '';
        form.querySelectorAll('input[type="date"]').forEach(input => { input.value = ''; });
        form.querySelectorAll('select[name]').forEach(select => {
            select.value = '';
            select.dispatchEvent(new Event('change', {bubbles: true}));
        });
        resetting = false;
        apply();
    });
})();

// Fonction de suppression d'annonce via modal Bootstrap
let pendingDeleteId = null;

function deleteAnnonce(id) {
    pendingDeleteId = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteAnnonceModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!pendingDeleteId) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("esbtp.annonces.index") }}/' + pendingDeleteId;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';

    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';

    form.appendChild(csrfToken);
    form.appendChild(methodField);
    document.body.appendChild(form);
    form.submit();
});
</script>
@endpush
