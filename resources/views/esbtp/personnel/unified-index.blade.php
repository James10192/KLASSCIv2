@extends('layouts.app')

@section('title', 'Gestion du Personnel - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* === KPI STATS GRID === */
    .staff-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-xl);
    }

    .staff-kpi-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: var(--space-md);
        transition: all 0.2s ease;
    }

    .staff-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .staff-kpi-icon {
        width: 54px;
        height: 54px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
        flex-shrink: 0;
    }

    .staff-kpi-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
    }

    .staff-kpi-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    /* === TABS NAVIGATION === */
    .staff-tabs-wrapper {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-lg);
        padding: 8px;
        display: flex;
        gap: 6px;
    }

    .staff-tab {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px var(--space-md);
        border-radius: var(--radius-small);
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: var(--text-normal);
        color: var(--text-secondary);
        background: transparent;
        transition: all 0.25s ease;
        white-space: nowrap;
    }

    .staff-tab i {
        font-size: 1rem;
        opacity: 0.8;
    }

    .staff-tab .tab-badge {
        background: rgba(4, 83, 203, 0.1);
        color: var(--primary);
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        transition: all 0.25s;
    }

    .staff-tab.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 10px rgba(4, 83, 203, 0.3);
    }

    .staff-tab.active i { opacity: 1; }

    .staff-tab.active .tab-badge {
        background: rgba(255, 255, 255, 0.25);
        color: white;
    }

    .staff-tab:hover:not(.active) {
        background: rgba(4, 83, 203, 0.06);
        color: var(--primary);
    }

    /* === TAB PANELS === */
    .staff-panel { display: none; }
    .staff-panel.active {
        display: block;
        animation: fadeSlideIn 0.25s ease;
    }

    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* === PANEL TOOLBAR === */
    .panel-toolbar {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        flex-wrap: wrap;
    }

    .panel-search {
        flex: 1;
        min-width: 220px;
        position: relative;
    }

    .panel-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .panel-search input {
        width: 100%;
        padding: 10px 14px 10px 36px;
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-small);
        font-size: var(--text-normal);
        background: var(--surface);
        color: var(--text-primary);
        transition: all 0.2s;
    }

    .panel-search input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    .panel-search input::placeholder { color: var(--text-muted); }

    .panel-filter {
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        background: var(--surface);
        color: var(--text-primary);
        min-width: 140px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .panel-filter:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    /* === INFO TIP === */
    .info-tip {
        background: rgba(4, 83, 203, 0.06);
        border-left: 3px solid var(--primary);
        border-radius: var(--radius-small);
        padding: var(--space-sm) var(--space-md);
        margin-bottom: var(--space-lg);
        display: flex;
        align-items: flex-start;
        gap: var(--space-sm);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .info-tip i { color: var(--primary); margin-top: 2px; flex-shrink: 0; }

    /* === STAFF CARDS === */
    .staff-list { display: flex; flex-direction: column; gap: var(--space-sm); }

    .staff-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        border: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow: var(--shadow-card);
        padding: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-md);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .staff-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 4px 0 0 4px;
    }

    .staff-card.role-coordinateur::before { background: var(--primary); }
    .staff-card.role-enseignant::before   { background: var(--success); }
    .staff-card.role-secretaire::before   { background: var(--warning); }

    .staff-card:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-hover);
    }

    /* === STAFF AVATAR === */
    .staff-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: white;
        font-weight: 700;
        flex-shrink: 0;
        letter-spacing: 0.5px;
    }

    .staff-avatar.avatar-coordinateur {
        background: linear-gradient(135deg, #0453cb, #5e91de);
    }
    .staff-avatar.avatar-enseignant {
        background: linear-gradient(135deg, #10b981, #34d399);
    }
    .staff-avatar.avatar-secretaire {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
    }

    /* === STAFF INFO === */
    .staff-info { flex: 1; min-width: 0; }

    .staff-name {
        font-size: var(--text-normal);
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 6px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .staff-title-prefix {
        color: var(--text-secondary);
        font-weight: 500;
    }

    .staff-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .staff-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: var(--text-small);
        color: var(--text-secondary);
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 3px 9px;
        white-space: nowrap;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .staff-chip i {
        color: var(--text-muted);
        font-size: 10px;
        flex-shrink: 0;
    }

    .staff-meta {
        margin-top: 6px;
        font-size: var(--text-small);
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* === STAFF RIGHT SIDE === */
    .staff-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: var(--space-sm);
        flex-shrink: 0;
    }

    /* === STATUS PILL === */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: var(--text-small);
        font-weight: 600;
    }

    .status-pill.active-pill {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .status-pill.inactive-pill {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    /* === ACTION BUTTONS === */
    .staff-actions {
        display: flex;
        gap: 6px;
    }

    .action-btn {
        width: 34px;
        height: 34px;
        border-radius: var(--radius-small);
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        background: transparent;
    }

    .action-btn.view {
        color: var(--text-secondary);
        border-color: #e5e7eb;
        background: #f8fafc;
    }
    .action-btn.view:hover {
        color: var(--primary);
        border-color: var(--primary);
        background: rgba(4,83,203,0.06);
    }

    .action-btn.edit {
        color: var(--primary);
        border-color: #bfdbfe;
        background: rgba(4,83,203,0.06);
    }
    .action-btn.edit:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .action-btn.deactivate {
        color: #d97706;
        border-color: #fde68a;
        background: rgba(245,158,11,0.08);
    }
    .action-btn.deactivate:hover {
        background: #f59e0b;
        color: white;
        border-color: #f59e0b;
    }

    .action-btn.activate {
        color: #059669;
        border-color: #a7f3d0;
        background: rgba(16,185,129,0.08);
    }
    .action-btn.activate:hover {
        background: var(--success);
        color: white;
        border-color: var(--success);
    }

    /* === EMPTY STATE === */
    .empty-state {
        text-align: center;
        padding: var(--space-xl) var(--space-lg);
        color: var(--text-secondary);
    }

    .empty-state-icon {
        font-size: 3.5rem;
        color: var(--primary);
        opacity: 0.2;
        margin-bottom: var(--space-lg);
    }

    .empty-state h5 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }

    .empty-state p {
        font-size: var(--text-normal);
        margin-bottom: var(--space-lg);
        max-width: 300px;
        margin-left: auto;
        margin-right: auto;
    }

    /* === DROPDOWN === */
    .dropdown { position: relative; z-index: 1050; }

    .dropdown-menu {
        z-index: 1051 !important;
        position: absolute !important;
        background: white !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-medium) !important;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12) !important;
        min-width: 200px !important;
        padding: 6px !important;
    }

    .dropdown-item {
        padding: 9px var(--space-md) !important;
        color: var(--text-primary) !important;
        text-decoration: none !important;
        display: flex !important;
        align-items: center !important;
        gap: var(--space-sm) !important;
        border-radius: var(--radius-small) !important;
        font-size: var(--text-normal) !important;
        font-weight: 500 !important;
        transition: all 0.15s !important;
    }

    .dropdown-item:hover {
        background: rgba(4, 83, 203, 0.08) !important;
        color: var(--primary) !important;
    }

    .dropdown-item i { width: 16px; text-align: center; }

    /* === OVERFLOW FIX === */
    .dashboard-acasi,
    .main-content { overflow: visible !important; }

    /* === RESPONSIVE === */
    @media (max-width: 992px) {
        .staff-kpi-grid { grid-template-columns: repeat(2, 1fr); }

        .staff-tab span.tab-label { display: none; }
        .staff-tab { gap: 6px; }
    }

    @media (max-width: 768px) {
        .panel-toolbar { flex-direction: column; align-items: stretch; }
        .panel-search { min-width: 100%; }
        .panel-filter { width: 100%; }

        .staff-card { flex-wrap: wrap; }
        .staff-right {
            flex-direction: row;
            align-items: center;
            width: 100%;
            margin-top: var(--space-sm);
        }

        .staff-tabs-wrapper { flex-direction: column; }
        .staff-tab { justify-content: flex-start; }

        .dropdown-menu {
            position: fixed !important;
            top: auto !important;
            left: 10px !important;
            right: 10px !important;
            width: calc(100% - 20px) !important;
        }
    }

    @media (max-width: 480px) {
        .staff-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .staff-chips { display: none; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-users-cog me-2" style="color: var(--primary);"></i>Gestion du Personnel</h1>
                <p class="header-subtitle">Administration unifiée : coordinateurs, enseignants et secrétaires</p>
            </div>
            <div class="header-actions" style="position: relative; z-index: 1050;">
                <div class="dropdown">
                    <button class="btn-acasi primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-plus me-1"></i>Nouveau Personnel
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if(!$isCoordinateur)
                        <li><a class="dropdown-item" href="{{ route('esbtp.coordinateurs.create') }}">
                            <i class="fas fa-user-tie" style="color: var(--primary);"></i>Coordinateur
                        </a></li>
                        @endif
                        <li><a class="dropdown-item" href="{{ route('esbtp.enseignants.create') }}">
                            <i class="fas fa-chalkboard-teacher" style="color: var(--success);"></i>Enseignant
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.secretaires.create') }}">
                            <i class="fas fa-user-tie" style="color: var(--warning);"></i>Secrétaire
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- KPI Stats -->
        <div class="staff-kpi-grid">
            @if(!$isCoordinateur)
            <div class="staff-kpi-card">
                <div class="staff-kpi-icon" style="background: linear-gradient(135deg, #0453cb, #5e91de);">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div>
                    <div class="staff-kpi-value">{{ $stats['coordinateurs'] ?? 0 }}</div>
                    <div class="staff-kpi-label">Coordinateurs</div>
                </div>
            </div>
            @endif
            <div class="staff-kpi-card">
                <div class="staff-kpi-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <div class="staff-kpi-value">{{ $stats['enseignants'] ?? 0 }}</div>
                    <div class="staff-kpi-label">Enseignants</div>
                </div>
            </div>
            <div class="staff-kpi-card">
                <div class="staff-kpi-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div>
                    <div class="staff-kpi-value">{{ $stats['secretaires'] ?? 0 }}</div>
                    <div class="staff-kpi-label">Secrétaires</div>
                </div>
            </div>
            <div class="staff-kpi-card">
                <div class="staff-kpi-icon" style="background: linear-gradient(135deg, #6366f1, #818cf8);">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="staff-kpi-value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="staff-kpi-label">Total Personnel</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="staff-tabs-wrapper">
            @if(!$isCoordinateur)
            <button class="staff-tab active" data-tab="coordinateurs">
                <i class="fas fa-user-tie"></i>
                <span class="tab-label">Coordinateurs</span>
                <span class="tab-badge">{{ $stats['coordinateurs'] ?? 0 }}</span>
            </button>
            @endif
            <button class="staff-tab {{ $isCoordinateur ? 'active' : '' }}" data-tab="enseignants">
                <i class="fas fa-chalkboard-teacher"></i>
                <span class="tab-label">Enseignants</span>
                <span class="tab-badge">{{ $stats['enseignants'] ?? 0 }}</span>
            </button>
            <button class="staff-tab" data-tab="secretaires">
                <i class="fas fa-user-tie"></i>
                <span class="tab-label">Secrétaires</span>
                <span class="tab-badge">{{ $stats['secretaires'] ?? 0 }}</span>
            </button>
        </div>

        <!-- ======================== -->
        <!-- PANEL: COORDINATEURS    -->
        <!-- ======================== -->
        @if(!$isCoordinateur)
        <div class="staff-panel active" id="coordinateurs-panel">
            <div class="panel-toolbar">
                <div class="panel-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher un coordinateur..." id="search-coordinateurs">
                </div>
                <select class="panel-filter" id="filter-coordinateurs-status">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs seulement</option>
                    <option value="inactive">Inactifs seulement</option>
                </select>
                <a href="{{ route('esbtp.coordinateurs.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus me-1"></i>Nouveau Coordinateur
                </a>
            </div>

            <div class="staff-list" id="coordinateurs-list">
                @if(isset($coordinateurs) && $coordinateurs->count() > 0)
                    @foreach($coordinateurs as $coordinateur)
                    <div class="staff-card role-coordinateur">
                        <div class="staff-avatar avatar-coordinateur">
                            {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                        </div>
                        <div class="staff-info">
                            <h6 class="staff-name">{{ $coordinateur->name }}</h6>
                            <div class="staff-chips">
                                <span class="staff-chip">
                                    <i class="fas fa-envelope"></i>
                                    {{ $coordinateur->email }}
                                </span>
                                @if($coordinateur->telephone)
                                <span class="staff-chip">
                                    <i class="fas fa-phone"></i>
                                    {{ $coordinateur->telephone }}
                                </span>
                                @endif
                                @if($coordinateur->specialite)
                                <span class="staff-chip">
                                    <i class="fas fa-graduation-cap"></i>
                                    {{ $coordinateur->specialite }}
                                </span>
                                @endif
                            </div>
                            <div class="staff-meta">
                                <i class="fas fa-calendar-alt"></i>
                                Depuis le {{ $coordinateur->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                        <div class="staff-right">
                            <span class="status-pill {{ $coordinateur->is_active ? 'active-pill' : 'inactive-pill' }}">
                                <i class="fas fa-circle" style="font-size: 6px;"></i>
                                {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <div class="staff-actions">
                                <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}"
                                   class="action-btn view" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}"
                                   class="action-btn edit" title="Modifier">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                @if($coordinateur->id !== auth()->id())
                                <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="action-btn {{ $coordinateur->is_active ? 'deactivate' : 'activate' }}"
                                            title="{{ $coordinateur->is_active ? 'Désactiver' : 'Activer' }}"
                                            onclick="return confirm('Êtes-vous sûr de vouloir {{ $coordinateur->is_active ? 'désactiver' : 'activer' }} ce coordinateur ?')">
                                        <i class="fas fa-{{ $coordinateur->is_active ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-user-tie"></i></div>
                        <h5>Aucun coordinateur</h5>
                        <p>Commencez par créer votre premier coordinateur.</p>
                        <a href="{{ route('esbtp.coordinateurs.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Créer un coordinateur
                        </a>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- ======================== -->
        <!-- PANEL: ENSEIGNANTS       -->
        <!-- ======================== -->
        <div class="staff-panel {{ $isCoordinateur ? 'active' : '' }}" id="enseignants-panel">
            <div class="panel-toolbar">
                <div class="panel-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher un enseignant..." id="search-enseignants">
                </div>
                <select class="panel-filter" id="filter-enseignants-status">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs seulement</option>
                    <option value="inactive">Inactifs seulement</option>
                </select>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn-acasi warning" data-bs-toggle="modal" data-bs-target="#bulkAvailabilityModal">
                        <i class="fas fa-calendar-check me-1"></i>Disponibilités groupées
                    </button>
                    <a href="{{ route('esbtp.enseignants.create') }}" class="btn-acasi primary">
                        <i class="fas fa-plus me-1"></i>Nouvel Enseignant
                    </a>
                </div>
            </div>

            <div class="info-tip">
                <i class="fas fa-lightbulb"></i>
                <span>Pour gérer la disponibilité d'un enseignant (horaires, jours disponibles, préférences), consultez sa fiche détaillée via le bouton <strong>œil</strong>.</span>
            </div>

            <div class="staff-list" id="enseignants-list">
                @if(isset($enseignants) && $enseignants->count() > 0)
                    @foreach($enseignants as $teacher)
                    <div class="staff-card role-enseignant">
                        <div class="staff-avatar avatar-enseignant">
                            {{ strtoupper(substr($teacher->user->name, 0, 2)) }}
                        </div>
                        <div class="staff-info">
                            <h6 class="staff-name">
                                @if($teacher->title)
                                    <span class="staff-title-prefix">{{ $teacher->title }}</span>
                                @endif
                                {{ $teacher->user->name }}
                            </h6>
                            <div class="staff-chips">
                                <span class="staff-chip">
                                    <i class="fas fa-envelope"></i>
                                    {{ $teacher->user->email }}
                                </span>
                                @if($teacher->user->telephone)
                                <span class="staff-chip">
                                    <i class="fas fa-phone"></i>
                                    {{ $teacher->user->telephone }}
                                </span>
                                @endif
                                @if($teacher->specialization)
                                <span class="staff-chip">
                                    <i class="fas fa-graduation-cap"></i>
                                    {{ $teacher->specialization }}
                                </span>
                                @endif
                            </div>
                            <div class="staff-meta">
                                <i class="fas fa-calendar-alt"></i>
                                Depuis le {{ $teacher->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                        <div class="staff-right">
                            <span class="status-pill {{ $teacher->status === 'active' ? 'active-pill' : 'inactive-pill' }}">
                                <i class="fas fa-circle" style="font-size: 6px;"></i>
                                {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                            <div class="staff-actions">
                                <a href="{{ route('esbtp.enseignants.show', $teacher) }}"
                                   class="action-btn view" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('esbtp.enseignants.edit', $teacher) }}"
                                   class="action-btn edit" title="Modifier">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                @if($teacher->user->id !== auth()->id())
                                <button type="button"
                                        class="action-btn {{ $teacher->status === 'active' ? 'deactivate' : 'activate' }}"
                                        title="{{ $teacher->status === 'active' ? 'Désactiver' : 'Activer' }}"
                                        onclick="toggleTeacherStatus({{ $teacher->id }})">
                                    <i class="fas fa-{{ $teacher->status === 'active' ? 'ban' : 'check' }}"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h5>Aucun enseignant</h5>
                        <p>Commencez par créer votre premier enseignant.</p>
                        <a href="{{ route('esbtp.enseignants.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Créer un enseignant
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- ======================== -->
        <!-- PANEL: SECRÉTAIRES       -->
        <!-- ======================== -->
        <div class="staff-panel" id="secretaires-panel">
            <div class="panel-toolbar">
                <div class="panel-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher un secrétaire..." id="search-secretaires">
                </div>
                <select class="panel-filter" id="filter-secretaires-status">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs seulement</option>
                    <option value="inactive">Inactifs seulement</option>
                </select>
                <a href="{{ route('esbtp.secretaires.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus me-1"></i>Nouveau Secrétaire
                </a>
            </div>

            <div class="staff-list" id="secretaires-list">
                @if(isset($secretaires) && $secretaires->count() > 0)
                    @foreach($secretaires as $secretaire)
                    <div class="staff-card role-secretaire">
                        <div class="staff-avatar avatar-secretaire">
                            {{ strtoupper(substr($secretaire->name, 0, 2)) }}
                        </div>
                        <div class="staff-info">
                            <h6 class="staff-name">{{ $secretaire->name }}</h6>
                            <div class="staff-chips">
                                <span class="staff-chip">
                                    <i class="fas fa-envelope"></i>
                                    {{ $secretaire->email }}
                                </span>
                                @if($secretaire->telephone)
                                <span class="staff-chip">
                                    <i class="fas fa-phone"></i>
                                    {{ $secretaire->telephone }}
                                </span>
                                @endif
                                @if($secretaire->service)
                                <span class="staff-chip">
                                    <i class="fas fa-briefcase"></i>
                                    {{ $secretaire->service }}
                                </span>
                                @endif
                            </div>
                            <div class="staff-meta">
                                <i class="fas fa-calendar-alt"></i>
                                Depuis le {{ $secretaire->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                        <div class="staff-right">
                            <span class="status-pill {{ $secretaire->is_active ? 'active-pill' : 'inactive-pill' }}">
                                <i class="fas fa-circle" style="font-size: 6px;"></i>
                                {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <div class="staff-actions">
                                <a href="{{ route('esbtp.secretaires.show', $secretaire) }}"
                                   class="action-btn view" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('esbtp.secretaires.edit', $secretaire) }}"
                                   class="action-btn edit" title="Modifier">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                @if($secretaire->id !== auth()->id())
                                <button type="button"
                                        class="action-btn {{ $secretaire->is_active ? 'deactivate' : 'activate' }}"
                                        title="{{ $secretaire->is_active ? 'Désactiver' : 'Activer' }}"
                                        onclick="toggleSecretaireStatus({{ $secretaire->id }})">
                                    <i class="fas fa-{{ $secretaire->is_active ? 'ban' : 'check' }}"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-user-tie"></i></div>
                        <h5>Aucun secrétaire</h5>
                        <p>Commencez par créer votre premier secrétaire.</p>
                        <a href="{{ route('esbtp.secretaires.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Créer un secrétaire
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- Modal pour afficher les credentials --}}
@include('partials.credentials-modal')

{{-- Modal disponibilités groupées --}}
<div class="modal fade" id="bulkAvailabilityModal" tabindex="-1" aria-labelledby="bulkAvailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: var(--radius-medium); border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: var(--radius-medium) var(--radius-medium) 0 0; border: none;">
                <h5 class="modal-title" id="bulkAvailabilityModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>Modification groupée des disponibilités
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="padding: var(--space-xl);">
                <p style="color: var(--text-secondary); font-size: var(--text-small); margin-bottom: var(--space-lg); display: flex; align-items: center; gap: var(--space-sm);">
                    <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                    Sélectionnez les enseignants dont vous souhaitez modifier les disponibilités, puis cliquez sur "Modifier les disponibilités".
                </p>

                <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
                    <div class="flex-grow-1">
                        <input type="text" id="bulk-availability-search" class="form-control"
                               placeholder="Rechercher un enseignant..."
                               style="border-radius: var(--radius-small);">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="bulk-select-all-availability">
                        <i class="fas fa-check-double me-1"></i>Tout sélectionner
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulk-deselect-all-availability">
                        <i class="fas fa-times me-1"></i>Tout désélectionner
                    </button>
                </div>

                <div class="list-group" id="bulk-availability-list" style="max-height: 400px; overflow-y: auto; border-radius: var(--radius-small);">
                    @if(isset($enseignants) && $enseignants->count() > 0)
                        @foreach($enseignants as $teacher)
                            <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 bulk-availability-item"
                                   data-name="{{ strtolower($teacher->user->name ?? '') }}"
                                   data-email="{{ strtolower($teacher->user->email ?? '') }}"
                                   style="cursor: pointer;">
                                <input type="checkbox" class="form-check-input bulk-availability-checkbox"
                                       value="{{ $teacher->id }}" style="margin: 0; flex-shrink: 0;">
                                <div class="staff-avatar avatar-enseignant" style="width: 36px; height: 36px; font-size: 0.75rem; flex-shrink: 0;">
                                    {{ strtoupper(substr($teacher->user->name ?? 'N', 0, 2)) }}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size: var(--text-normal);">
                                        @if($teacher->title)
                                            <span style="color: var(--text-secondary); font-weight: 500;">{{ $teacher->title }}</span>
                                        @endif
                                        {{ $teacher->user->name ?? 'N/A' }}
                                    </div>
                                    <small style="color: var(--text-muted);">
                                        {{ $teacher->user->email ?? '' }}
                                        @if($teacher->specialization)
                                            · {{ $teacher->specialization }}
                                        @endif
                                    </small>
                                </div>
                                <span class="status-pill {{ $teacher->status === 'active' ? 'active-pill' : 'inactive-pill' }}" style="font-size: 11px;">
                                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                            </label>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-slash fa-2x mb-2 d-block" style="opacity: 0.3;"></i>
                            <p>Aucun enseignant disponible</p>
                        </div>
                    @endif
                </div>

                <div class="mt-3 text-end">
                    <span class="status-pill active-pill" id="bulk-availability-count">0 enseignant(s) sélectionné(s)</span>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: var(--space-md) var(--space-xl);">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-warning fw-bold" id="bulk-availability-submit" disabled>
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

    // === GESTION ONGLETS ===
    $('.staff-tab').click(function() {
        const tabName = $(this).data('tab');
        $('.staff-tab').removeClass('active');
        $(this).addClass('active');
        $('.staff-panel').removeClass('active');
        $('#' + tabName + '-panel').addClass('active');
    });

    // === RECHERCHE EN TEMPS RÉEL ===
    $('#search-coordinateurs').on('input', function() {
        filterStaff('coordinateurs', $(this).val().toLowerCase());
    });

    $('#search-enseignants').on('input', function() {
        filterStaff('enseignants', $(this).val().toLowerCase());
    });

    $('#search-secretaires').on('input', function() {
        filterStaff('secretaires', $(this).val().toLowerCase());
    });

    // === FILTRES STATUT ===
    $('.panel-filter').change(function() {
        const panelType = $(this).attr('id').split('-')[1];
        applyFilters(panelType);
    });

    function filterStaff(type, searchTerm) {
        $('#' + type + '-list .staff-card').each(function() {
            const cardText = $(this).text().toLowerCase();
            $(this).toggle(cardText.includes(searchTerm));
        });
    }

    function applyFilters(type) {
        const statusFilter = $('#filter-' + type + '-status').val();

        $('#' + type + '-list .staff-card').each(function() {
            let isVisible = true;

            if (statusFilter) {
                const isActive = $(this).find('.status-pill.active-pill').length > 0;
                if (statusFilter === 'active' && !isActive) isVisible = false;
                if (statusFilter === 'inactive' && isActive) isVisible = false;
            }

            $(this).toggle(isVisible);
        });
    }

    // === ANIMATION ENTRÉE ===
    $('.staff-card').each(function(index) {
        $(this).css({ opacity: 0, transform: 'translateY(12px)' });
        setTimeout(() => {
            $(this).css({ transition: 'all 0.3s ease', opacity: 1, transform: 'translateY(0)' });
        }, index * 40);
    });
});

// === TOGGLE STATUT ENSEIGNANT ===
function toggleTeacherStatus(teacherId) {
    if (confirm('Êtes-vous sûr de vouloir changer le statut de cet enseignant ?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch(`/esbtp/enseignants/${teacherId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour du statut');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la mise à jour du statut');
        });
    }
}

// === TOGGLE STATUT SECRÉTAIRE ===
function toggleSecretaireStatus(secretaireId) {
    if (confirm('Êtes-vous sûr de vouloir changer le statut de ce secrétaire ?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch(`/esbtp/secretaires/${secretaireId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour du statut');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la mise à jour du statut');
        });
    }
}

// === MODAL DISPONIBILITÉS GROUPÉES ===
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
        countBadge.textContent = checked + ' enseignant(s) sélectionné(s)';
        submitBtn.disabled = checked === 0;
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            items.forEach(item => {
                const name = item.dataset.name || '';
                const email = item.dataset.email || '';
                item.classList.toggle('d-none', !name.includes(query) && !email.includes(query));
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
            const selectedIds = Array.from(document.querySelectorAll('.bulk-availability-checkbox:checked'))
                .map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('Veuillez sélectionner au moins un enseignant.');
                return;
            }

            const params = selectedIds.map(id => 'ids[]=' + encodeURIComponent(id)).join('&');
            window.location.href = '{{ route("esbtp.enseignants.bulk-availability") }}?' + params;
        });
    }

    updateCount();
})();
</script>
@endpush
