@extends('layouts.app')

@section('title', 'Gestion du Personnel - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .personnel-header {
        background: linear-gradient(135deg, var(--primary, #0453cb) 0%, var(--secondary, #5e91de) 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: visible;
        z-index: 1;
    }
    
    .personnel-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .personnel-slider {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        overflow: visible;
        margin-bottom: var(--space-xl);
    }
    
    /* Styles pour le dropdown */
    .dropdown {
        position: relative;
        z-index: 1050;
    }
    
    .dropdown-menu {
        z-index: 1051 !important;
        position: absolute !important;
        background: white !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-medium) !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
        margin-top: 2px !important;
    }
    
    .dropdown-item {
        padding: var(--space-sm) var(--space-md) !important;
        color: var(--text-primary) !important;
        text-decoration: none !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.2s ease !important;
    }
    
    .dropdown-item:hover {
        background: rgba(var(--primary-rgb), 0.1) !important;
        color: var(--primary) !important;
    }
    
    .slider-tabs {
        display: flex;
        background: var(--background);
        border-bottom: 1px solid var(--border);
    }
    
    .slider-tab {
        flex: 1;
        padding: var(--space-lg);
        background: transparent;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: var(--text-secondary);
        transition: all 0.3s ease;
        position: relative;
        text-align: center;
    }
    
    .slider-tab.active {
        color: var(--primary);
        background: var(--surface);
    }
    
    .slider-tab.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--primary);
    }
    
    .slider-tab:hover:not(.active) {
        background: rgba(var(--primary-rgb), 0.05);
        color: var(--text-primary);
    }
    
    .slider-tab .tab-icon {
        font-size: 1.5rem;
        margin-bottom: var(--space-xs);
        display: block;
    }
    
    .slider-tab .tab-label {
        font-size: var(--text-normal);
        display: block;
    }
    
    .slider-tab .tab-count {
        font-size: var(--text-small);
        color: var(--text-muted);
        margin-top: var(--space-xs);
        display: block;
    }
    
    .slider-content {
        padding: var(--space-lg);
        min-height: 600px;
    }
    
    .slider-panel {
        display: none;
    }
    
    .slider-panel.active {
        display: block;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .personnel-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        text-align: center;
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .stat-card .icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-sm);
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }
    
    .stat-card.coordinateurs .icon { background: var(--primary); }
    .stat-card.enseignants .icon { background: var(--success); }
    .stat-card.secretaires .icon { background: var(--warning); }
    .stat-card.total .icon { background: var(--accent-blue); }
    
    .stat-value {
        font-size: var(--amount-large);
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        flex-shrink: 0;
    }
    
    .personnel-actions {
        display: flex;
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        flex-wrap: wrap;
    }
    
    .personnel-search {
        flex: 1;
        min-width: 300px;
    }
    
    .search-input {
        width: 100%;
        padding: var(--space-sm) var(--space-md);
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        font-size: var(--text-normal);
        background: var(--surface);
        transition: all 0.2s ease;
    }
    
    .search-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    .personnel-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .personnel-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .personnel-avatar {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        font-weight: bold;
        margin-right: var(--space-md);
    }
    
    .personnel-info {
        flex: 1;
    }
    
    .personnel-name {
        font-size: var(--text-normal);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .personnel-details {
        display: flex;
        gap: var(--space-md);
        margin-bottom: var(--space-xs);
        flex-wrap: wrap;
    }
    
    .personnel-detail {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }
    
    .personnel-actions-group {
        display: flex;
        gap: var(--space-xs);
    }
    
    .status-badge {
        position: absolute;
        top: var(--space-md);
        right: var(--space-md);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-badge.active {
        background: var(--success);
        color: white;
    }
    
    .status-badge.inactive {
        background: var(--danger);
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }
    
    .empty-state .icon {
        font-size: 4rem;
        margin-bottom: var(--space-md);
        opacity: 0.5;
    }
    
    .btn-sm {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
    }
    
    .personnel-filters {
        display: flex;
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
        flex-wrap: wrap;
        align-items: center;
    }
    
    .filter-select {
        padding: var(--space-sm) var(--space-md);
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        background: var(--surface);
        min-width: 150px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    /* Conteneurs principaux */
    .dashboard-acasi,
    .main-content {
        overflow: visible !important;
    }
    
    @media (max-width: 768px) {
        .personnel-actions {
            flex-direction: column;
        }
        
        .personnel-search {
            min-width: 100%;
        }
        
        .personnel-filters {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-select {
            width: 100%;
        }
        
        .personnel-details {
            flex-direction: column;
            gap: var(--space-xs);
        }
        
        .slider-tabs {
            flex-direction: column;
        }
        
        .slider-tab {
            text-align: left;
        }
        
        .dropdown-menu {
            position: fixed !important;
            top: auto !important;
            left: auto !important;
            right: 10px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="personnel-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-users-cog me-2"></i>Gestion du Personnel</h1>
                    <p class="mb-0">Administration unifiée du personnel : coordinateurs, enseignants et secrétaires</p>
                </div>
                <div class="col-md-4 text-end" style="position: relative; z-index: 1050;">
                    <div class="dropdown">
                        <button class="btn-acasi primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-plus me-1"></i>Nouveau Personnel
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(!$isCoordinateur)
                            <li><a class="dropdown-item" href="{{ route('esbtp.coordinateurs.create') }}">
                                <i class="fas fa-user-tie me-2"></i>Coordinateur
                            </a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('esbtp.enseignants.create') }}">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Enseignant
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.secretaires.create') }}">
                                <i class="fas fa-user-secretary me-2"></i>Secrétaire
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.comptables.create') }}">
                                <i class="fas fa-calculator me-2"></i>Comptable
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques générales -->
        <div class="personnel-stats">
            @if(!$isCoordinateur)
            <div class="stat-card coordinateurs">
                <div class="icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-value">{{ $stats['coordinateurs'] ?? 0 }}</div>
                <div class="stat-label">Coordinateurs</div>
            </div>
            @endif
            <div class="stat-card enseignants">
                <div class="icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value">{{ $stats['enseignants'] ?? 0 }}</div>
                <div class="stat-label">Enseignants</div>
            </div>
            <div class="stat-card secretaires">
                <div class="icon">
                    <i class="fas fa-user-secretary"></i>
                </div>
                <div class="stat-value">{{ $stats['secretaires'] ?? 0 }}</div>
                <div class="stat-label">Secrétaires</div>
            </div>
            <div class="stat-card comptables">
                <div class="icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-value">{{ $stats['comptables'] ?? 0 }}</div>
                <div class="stat-label">Comptables</div>
            </div>
            <div class="stat-card total">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                <div class="stat-label">Total Personnel</div>
            </div>
        </div>

        <!-- Slider avec onglets -->
        <div class="personnel-slider">
            <div class="slider-tabs">
                @if(!$isCoordinateur)
                <button class="slider-tab active" data-tab="coordinateurs">
                    <span class="tab-icon">
                        <i class="fas fa-user-tie"></i>
                    </span>
                    <span class="tab-label">Coordinateurs</span>
                    <span class="tab-count">{{ $stats['coordinateurs'] ?? 0 }} personnes</span>
                </button>
                @endif
                <button class="slider-tab {{ $isCoordinateur ? 'active' : '' }}" data-tab="enseignants">
                    <span class="tab-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </span>
                    <span class="tab-label">Enseignants</span>
                    <span class="tab-count">{{ $stats['enseignants'] ?? 0 }} personnes</span>
                </button>
                <button class="slider-tab" data-tab="secretaires">
                    <span class="tab-icon">
                        <i class="fas fa-user-secretary"></i>
                    </span>
                    <span class="tab-label">Secrétaires</span>
                    <span class="tab-count">{{ $stats['secretaires'] ?? 0 }} personnes</span>
                </button>
                <button class="slider-tab" data-tab="comptables">
                    <span class="tab-icon">
                        <i class="fas fa-calculator"></i>
                    </span>
                    <span class="tab-label">Comptables</span>
                    <span class="tab-count">{{ $stats['comptables'] ?? 0 }} personnes</span>
                </button>
            </div>

            <div class="slider-content">
                @if(!$isCoordinateur)
                <!-- Panel Coordinateurs -->
                <div class="slider-panel active" id="coordinateurs-panel">
                    <div class="personnel-actions">
                        <div class="personnel-search">
                            <input type="text" class="search-input" placeholder="Rechercher un coordinateur..." 
                                   id="search-coordinateurs">
                        </div>
                        <a href="{{ route('esbtp.coordinateurs.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Nouveau Coordinateur
                        </a>
                    </div>

                    <div class="personnel-filters">
                        <label class="form-label">Filtrer par :</label>
                        <select class="filter-select" id="filter-coordinateurs-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                        <select class="filter-select" id="filter-coordinateurs-specialite">
                            <option value="">Toutes les spécialités</option>
                            <option value="informatique">Informatique</option>
                            <option value="gestion">Gestion</option>
                            <option value="marketing">Marketing</option>
                        </select>
                    </div>

                    <div id="coordinateurs-list">
                        @if(isset($coordinateurs) && $coordinateurs->count() > 0)
                            @foreach($coordinateurs as $coordinateur)
                            <div class="personnel-card">
                                <div class="status-badge {{ $coordinateur->is_active ? 'active' : 'inactive' }}">
                                    {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <div class="personnel-avatar">
                                        {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                                    </div>
                                    <div class="personnel-info">
                                        <div class="personnel-name">{{ $coordinateur->name }}</div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-envelope"></i>
                                                <span>{{ $coordinateur->email }}</span>
                                            </div>
                                            @if($coordinateur->telephone)
                                            <div class="personnel-detail">
                                                <i class="fas fa-phone"></i>
                                                <span>{{ $coordinateur->telephone }}</span>
                                            </div>
                                            @endif
                                            @if($coordinateur->specialite)
                                            <div class="personnel-detail">
                                                <i class="fas fa-graduation-cap"></i>
                                                <span>{{ $coordinateur->specialite }}</span>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-calendar"></i>
                                                <span>Créé le {{ $coordinateur->created_at->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="personnel-actions-group">
                                        <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}" 
                                           class="btn-acasi secondary btn-sm" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" 
                                           class="btn-acasi primary btn-sm" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($coordinateur->id !== auth()->id())
                                        <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="btn-acasi {{ $coordinateur->is_active ? 'warning' : 'success' }} btn-sm" 
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
                                <div class="icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
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

                <!-- Panel Enseignants -->
                <div class="slider-panel {{ $isCoordinateur ? 'active' : '' }}" id="enseignants-panel">
                    <div class="personnel-actions">
                        <div class="personnel-search">
                            <input type="text" class="search-input" placeholder="Rechercher un enseignant..."
                                   id="search-enseignants">
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn-acasi warning" data-bs-toggle="modal" data-bs-target="#bulkAvailabilityModal">
                                <i class="fas fa-calendar-check me-1"></i>Disponibilités groupées
                            </button>
                            <a href="{{ route('esbtp.enseignants.create') }}" class="btn-acasi primary">
                                <i class="fas fa-plus me-1"></i>Nouvel Enseignant
                            </a>
                        </div>
                    </div>

                    <div class="personnel-filters">
                        <label class="form-label">Filtrer par :</label>
                        <select class="filter-select" id="filter-enseignants-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                        <select class="filter-select" id="filter-enseignants-matiere">
                            <option value="">Toutes les matières</option>
                            <option value="informatique">Informatique</option>
                            <option value="mathematiques">Mathématiques</option>
                            <option value="francais">Français</option>
                            <option value="anglais">Anglais</option>
                        </select>
                    </div>

                    {{-- Tip pour la disponibilité --}}
                    <div style="background-color: rgba(59, 130, 246, 0.1); border-radius: var(--radius-medium); padding: var(--space-md); margin-bottom: var(--space-lg); border-left: 4px solid var(--primary);">
                        <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
                            <i class="fas fa-lightbulb" style="color: var(--primary); margin-top: 2px; font-size: 1.2rem;"></i>
                            <div>
                                <p style="margin: 0; font-size: var(--text-normal); color: var(--text-primary); font-weight: 600;">
                                    <i class="fas fa-info-circle" style="margin-right: 4px;"></i>Astuce
                                </p>
                                <p style="margin: var(--space-xs) 0 0 0; font-size: var(--text-small); color: var(--text-secondary);">
                                    Pour gérer la disponibilité d'un enseignant (horaires, jours disponibles, préférences), consultez sa fiche détaillée en cliquant sur le bouton "Voir détails".
                                </p>
                            </div>
                        </div>
                    </div>

                    <div id="enseignants-list">
                        @if(isset($enseignants) && $enseignants->count() > 0)
                            @foreach($enseignants as $teacher)
                            <div class="personnel-card">
                                <div class="status-badge {{ $teacher->status === 'active' ? 'active' : 'inactive' }}">
                                    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <div class="personnel-avatar">
                                        {{ strtoupper(substr($teacher->user->name, 0, 2)) }}
                                    </div>
                                    <div class="personnel-info">
                                        <div class="personnel-name">
                                            @if($teacher->title)
                                                <span style="font-weight: 500;">{{ $teacher->title }}</span>
                                            @endif
                                            {{ $teacher->user->name }}
                                        </div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-envelope"></i>
                                                <span>{{ $teacher->user->email }}</span>
                                            </div>
                                            @if($teacher->user->telephone)
                                            <div class="personnel-detail">
                                                <i class="fas fa-phone"></i>
                                                <span>{{ $teacher->user->telephone }}</span>
                                            </div>
                                            @endif
                                            @if($teacher->specialization)
                                            <div class="personnel-detail">
                                                <i class="fas fa-graduation-cap"></i>
                                                <span>{{ $teacher->specialization }}</span>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-calendar"></i>
                                                <span>Créé le {{ $teacher->created_at->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="personnel-actions-group">
                                        <a href="{{ route('esbtp.enseignants.show', $teacher) }}" 
                                           class="btn-acasi secondary btn-sm" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.enseignants.edit', $teacher) }}" 
                                           class="btn-acasi primary btn-sm" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($teacher->user->id !== auth()->id())
                                        <button type="button" 
                                                class="btn-acasi {{ $teacher->status === 'active' ? 'warning' : 'success' }} btn-sm" 
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
                                <div class="icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <h5>Aucun enseignant</h5>
                                <p>Commencez par créer votre premier enseignant.</p>
                                <a href="{{ route('esbtp.enseignants.create') }}" class="btn-acasi primary">
                                    <i class="fas fa-plus me-1"></i>Créer un enseignant
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Panel Secrétaires -->
                <div class="slider-panel" id="secretaires-panel">
                    <div class="personnel-actions">
                        <div class="personnel-search">
                            <input type="text" class="search-input" placeholder="Rechercher un secrétaire..." 
                                   id="search-secretaires">
                        </div>
                        <a href="{{ route('esbtp.secretaires.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Nouveau Secrétaire
                        </a>
                    </div>

                    <div class="personnel-filters">
                        <label class="form-label">Filtrer par :</label>
                        <select class="filter-select" id="filter-secretaires-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                        <select class="filter-select" id="filter-secretaires-service">
                            <option value="">Tous les services</option>
                            <option value="administration">Administration</option>
                            <option value="scolarite">Scolarité</option>
                            <option value="comptabilite">Comptabilité</option>
                        </select>
                    </div>

                    <div id="secretaires-list">
                        @if(isset($secretaires) && $secretaires->count() > 0)
                            @foreach($secretaires as $secretaire)
                            <div class="personnel-card">
                                <div class="status-badge {{ $secretaire->is_active ? 'active' : 'inactive' }}">
                                    {{ $secretaire->is_active ? 'Actif' : 'Inactif' }}
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <div class="personnel-avatar">
                                        {{ strtoupper(substr($secretaire->name, 0, 2)) }}
                                    </div>
                                    <div class="personnel-info">
                                        <div class="personnel-name">{{ $secretaire->name }}</div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-envelope"></i>
                                                <span>{{ $secretaire->email }}</span>
                                            </div>
                                            @if($secretaire->telephone)
                                            <div class="personnel-detail">
                                                <i class="fas fa-phone"></i>
                                                <span>{{ $secretaire->telephone }}</span>
                                            </div>
                                            @endif
                                            @if($secretaire->service)
                                            <div class="personnel-detail">
                                                <i class="fas fa-briefcase"></i>
                                                <span>{{ $secretaire->service }}</span>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-calendar"></i>
                                                <span>Créé le {{ $secretaire->created_at->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="personnel-actions-group">
                                        <a href="{{ route('esbtp.secretaires.show', $secretaire) }}" 
                                           class="btn-acasi secondary btn-sm" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.secretaires.edit', $secretaire) }}" 
                                           class="btn-acasi primary btn-sm" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($secretaire->id !== auth()->id())
                                        <button type="button" 
                                                class="btn-acasi {{ $secretaire->is_active ? 'warning' : 'success' }} btn-sm" 
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
                                <div class="icon">
                                    <i class="fas fa-user-secretary"></i>
                                </div>
                                <h5>Aucun secrétaire</h5>
                                <p>Commencez par créer votre premier secrétaire.</p>
                                <a href="{{ route('esbtp.secretaires.create') }}" class="btn-acasi primary">
                                    <i class="fas fa-plus me-1"></i>Créer un secrétaire
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Panel Comptables -->
                <div class="slider-panel" id="comptables-panel">
                    <div class="personnel-actions">
                        <div class="personnel-search">
                            <input type="text" class="search-input" placeholder="Rechercher un comptable..."
                                   id="search-comptables">
                        </div>
                        <a href="{{ route('esbtp.comptables.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Nouveau Comptable
                        </a>
                    </div>

                    <div class="personnel-filters">
                        <label class="form-label">Filtrer par :</label>
                        <select class="filter-select" id="filter-comptables-status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                        <select class="filter-select" id="filter-comptables-department">
                            <option value="">Tous les départements</option>
                            <option value="comptabilite">Comptabilité</option>
                            <option value="finance">Finance</option>
                            <option value="audit">Audit</option>
                        </select>
                    </div>

                    <div id="comptables-list">
                        @if(isset($comptables) && $comptables->count() > 0)
                            @foreach($comptables as $comptable)
                            <div class="personnel-card">
                                <div class="status-badge {{ $comptable->is_active ? 'active' : 'inactive' }}">
                                    {{ $comptable->is_active ? 'Actif' : 'Inactif' }}
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="personnel-avatar" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                        {{ strtoupper(substr($comptable->name, 0, 2)) }}
                                    </div>
                                    <div class="personnel-info">
                                        <div class="personnel-name">{{ $comptable->name }}</div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-envelope"></i>
                                                <span>{{ $comptable->email }}</span>
                                            </div>
                                            @if($comptable->telephone)
                                            <div class="personnel-detail">
                                                <i class="fas fa-phone"></i>
                                                <span>{{ $comptable->telephone }}</span>
                                            </div>
                                            @endif
                                            @if($comptable->department)
                                            <div class="personnel-detail">
                                                <i class="fas fa-building"></i>
                                                <span>{{ $comptable->department }}</span>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="personnel-details">
                                            <div class="personnel-detail">
                                                <i class="fas fa-calendar"></i>
                                                <span>Créé le {{ $comptable->created_at->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="personnel-actions-group">
                                        <a href="{{ route('esbtp.comptables.show', $comptable) }}"
                                           class="btn-acasi secondary btn-sm" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($comptable->id !== auth()->id())
                                        <button type="button"
                                                class="btn-acasi {{ $comptable->is_active ? 'warning' : 'success' }} btn-sm"
                                                title="{{ $comptable->is_active ? 'Désactiver' : 'Activer' }}"
                                                onclick="toggleComptableStatus({{ $comptable->id }})">
                                            <i class="fas fa-{{ $comptable->is_active ? 'ban' : 'check' }}"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <div class="icon">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <h5>Aucun comptable</h5>
                                <p>Commencez par créer votre premier comptable.</p>
                                <a href="{{ route('esbtp.comptables.create') }}" class="btn-acasi primary">
                                    <i class="fas fa-plus me-1"></i>Créer un comptable
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
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                <h5 class="modal-title" id="bulkAvailabilityModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>Modification groupée des disponibilités
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Sélectionnez les enseignants dont vous souhaitez modifier les disponibilités, puis cliquez sur "Modifier les disponibilités".
                </p>

                {{-- Barre de recherche et actions --}}
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

                {{-- Liste des enseignants --}}
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-warning" id="bulk-availability-submit" disabled>
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
    // Gestion des onglets du slider
    $('.slider-tab').click(function() {
        const tabName = $(this).data('tab');
        
        // Mettre à jour les onglets
        $('.slider-tab').removeClass('active');
        $(this).addClass('active');
        
        // Mettre à jour les panels
        $('.slider-panel').removeClass('active');
        $('#' + tabName + '-panel').addClass('active');
    });
    
    // Recherche en temps réel
    $('#search-coordinateurs').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPersonnel('coordinateurs', searchTerm);
    });
    
    $('#search-enseignants').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPersonnel('enseignants', searchTerm);
    });
    
    $('#search-secretaires').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPersonnel('secretaires', searchTerm);
    });

    $('#search-comptables').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPersonnel('comptables', searchTerm);
    });
    
    // Filtres
    $('.filter-select').change(function() {
        const panelType = $(this).attr('id').split('-')[1];
        applyFilters(panelType);
    });
    
    function filterPersonnel(type, searchTerm) {
        $('#' + type + '-list .personnel-card').each(function() {
            const cardText = $(this).text().toLowerCase();
            const isVisible = cardText.includes(searchTerm);
            $(this).toggle(isVisible);
        });
    }
    
    function applyFilters(type) {
        const statusFilter = $('#filter-' + type + '-status').val();
        const secondFilter = $('#filter-' + type + '-specialite, #filter-' + type + '-matiere, #filter-' + type + '-service').val();
        
        $('#' + type + '-list .personnel-card').each(function() {
            let isVisible = true;
            
            // Filtre par statut
            if (statusFilter) {
                const hasActiveStatus = $(this).find('.status-badge.active').length > 0;
                const hasInactiveStatus = $(this).find('.status-badge.inactive').length > 0;
                
                if (statusFilter === 'active' && !hasActiveStatus) {
                    isVisible = false;
                } else if (statusFilter === 'inactive' && !hasInactiveStatus) {
                    isVisible = false;
                }
            }
            
            // Filtre par spécialité/matière/service
            if (secondFilter && isVisible) {
                const cardText = $(this).text().toLowerCase();
                isVisible = cardText.includes(secondFilter.toLowerCase());
            }
            
            $(this).toggle(isVisible);
        });
    }
    
    // Animation d'entrée des cartes
    $('.personnel-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 300);
        }, index * 50);
    });
});

// Fonctions pour toggle le statut
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
                // Afficher un message de succès
                alert(data.message);
                // Recharger la page pour refléter les changements
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour du statut');
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Une erreur est survenue lors de la mise à jour du statut');
        });
    }
}

function toggleComptableStatus(comptableId) {
    if (confirm('Êtes-vous sûr de vouloir changer le statut de ce comptable ?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/esbtp/comptables/${comptableId}/toggle-status`, {
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
                // Afficher un message de succès
                alert(data.message);
                // Recharger la page pour refléter les changements
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour du statut');
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Une erreur est survenue lors de la mise à jour du statut');
        });
    }
}

// ============================================
// Gestion du modal de modification groupée des disponibilités
// ============================================
(function() {
    const searchInput = document.getElementById('bulk-availability-search');
    const selectAllBtn = document.getElementById('bulk-select-all-availability');
    const deselectAllBtn = document.getElementById('bulk-deselect-all-availability');
    const submitBtn = document.getElementById('bulk-availability-submit');
    const countBadge = document.getElementById('bulk-availability-count');
    const checkboxes = document.querySelectorAll('.bulk-availability-checkbox');
    const items = document.querySelectorAll('.bulk-availability-item');

    // Fonction pour mettre à jour le compteur et l'état du bouton
    function updateCount() {
        const checked = document.querySelectorAll('.bulk-availability-checkbox:checked').length;
        countBadge.textContent = checked + ' enseignant(s) sélectionné(s)';
        submitBtn.disabled = checked === 0;
    }

    // Recherche
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            items.forEach(item => {
                const name = item.dataset.name || '';
                const email = item.dataset.email || '';
                const matches = name.includes(query) || email.includes(query);
                item.classList.toggle('d-none', !matches);
            });
        });
    }

    // Sélectionner tout (visible)
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            items.forEach(item => {
                if (!item.classList.contains('d-none')) {
                    const checkbox = item.querySelector('.bulk-availability-checkbox');
                    if (checkbox) checkbox.checked = true;
                }
            });
            updateCount();
        });
    }

    // Désélectionner tout
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            updateCount();
        });
    }

    // Événement sur chaque checkbox
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });

    // Soumission
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.bulk-availability-checkbox:checked'))
                .map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('Veuillez sélectionner au moins un enseignant.');
                return;
            }

            // Construire l'URL avec les IDs sélectionnés
            const params = selectedIds.map(id => 'ids[]=' + encodeURIComponent(id)).join('&');
            const url = '{{ route("esbtp.enseignants.bulk-availability") }}?' + params;

            // Rediriger vers la page de modification groupée
            window.location.href = url;
        });
    }

    // Initialiser le compteur
    updateCount();
})();
</script>
@endpush
