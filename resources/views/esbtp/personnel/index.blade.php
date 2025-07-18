@extends('layouts.app')

@section('title', 'Gestion du Personnel - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .personnel-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
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
        overflow: hidden;
        margin-bottom: var(--space-xl);
    }
    
    .slider-tabs {
        display: flex;
        background: var(--background);
        border-bottom: 1px solid #e5e7eb;
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
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
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
        margin: 0 auto var(--space-md);
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-card.coordinateurs .icon { background: var(--primary); }
    .stat-card.enseignants .icon { background: var(--success); }
    .stat-card.secretaires .icon { background: var(--warning); }
    .stat-card.total .icon { background: var(--accent-blue); }
    
    .stat-value {
        font-size: var(--amount-large);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
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
        border: 1px solid #e5e7eb;
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
        border: 1px solid #e5e7eb;
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
        border: 1px solid #e5e7eb;
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
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="personnel-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-users-cog me-2"></i>Gestion du Personnel</h1>
                    <p class="mb-0">Administration complète du personnel : coordinateurs, enseignants et secrétaires</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="dropdown">
                        <button class="btn-acasi primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-plus me-1"></i>Nouveau Personnel
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('esbtp.coordinateurs.create') }}">
                                <i class="fas fa-user-tie me-2"></i>Coordinateur
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="showCreateModal('enseignant')">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Enseignant
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="showCreateModal('secretaire')">
                                <i class="fas fa-user-secretary me-2"></i>Secrétaire
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques générales -->
        <div class="personnel-stats">
            <div class="stat-card coordinateurs">
                <div class="icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-value">{{ $stats['coordinateurs'] ?? 0 }}</div>
                <div class="stat-label">Coordinateurs</div>
            </div>
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
            <div class="stat-card total">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">{{ ($stats['coordinateurs'] ?? 0) + ($stats['enseignants'] ?? 0) + ($stats['secretaires'] ?? 0) }}</div>
                <div class="stat-label">Total Personnel</div>
            </div>
        </div>

        <!-- Slider avec onglets -->
        <div class="personnel-slider">
            <div class="slider-tabs">
                <button class="slider-tab active" data-tab="coordinateurs">
                    <span class="tab-icon">
                        <i class="fas fa-user-tie"></i>
                    </span>
                    <span class="tab-label">Coordinateurs</span>
                    <span class="tab-count">{{ $stats['coordinateurs'] ?? 0 }} personnes</span>
                </button>
                <button class="slider-tab" data-tab="enseignants">
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
            </div>

            <div class="slider-content">
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

                <!-- Panel Enseignants -->
                <div class="slider-panel" id="enseignants-panel">
                    <div class="personnel-actions">
                        <div class="personnel-search">
                            <input type="text" class="search-input" placeholder="Rechercher un enseignant..." 
                                   id="search-enseignants">
                        </div>
                        <button class="btn-acasi primary" onclick="showCreateModal('enseignant')">
                            <i class="fas fa-plus me-1"></i>Nouvel Enseignant
                        </button>
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

                    <div id="enseignants-list">
                        <div class="empty-state">
                            <div class="icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h5>Aucun enseignant</h5>
                            <p>Commencez par créer votre premier enseignant.</p>
                            <button class="btn-acasi primary" onclick="showCreateModal('enseignant')">
                                <i class="fas fa-plus me-1"></i>Créer un enseignant
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Panel Secrétaires -->
                <div class="slider-panel" id="secretaires-panel">
                    <div class="personnel-actions">
                        <div class="personnel-search">
                            <input type="text" class="search-input" placeholder="Rechercher un secrétaire..." 
                                   id="search-secretaires">
                        </div>
                        <button class="btn-acasi primary" onclick="showCreateModal('secretaire')">
                            <i class="fas fa-plus me-1"></i>Nouveau Secrétaire
                        </button>
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
                        <div class="empty-state">
                            <div class="icon">
                                <i class="fas fa-user-secretary"></i>
                            </div>
                            <h5>Aucun secrétaire</h5>
                            <p>Commencez par créer votre premier secrétaire.</p>
                            <button class="btn-acasi primary" onclick="showCreateModal('secretaire')">
                                <i class="fas fa-plus me-1"></i>Créer un secrétaire
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de création (placeholder) -->
<div class="modal fade" id="createPersonnelModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPersonnelModalLabel">Créer un nouveau personnel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Formulaire de création dynamique -->
                <p>Fonctionnalité en cours de développement...</p>
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

function showCreateModal(type) {
    const modal = new bootstrap.Modal(document.getElementById('createPersonnelModal'));
    const modalTitle = document.getElementById('createPersonnelModalLabel');
    
    switch(type) {
        case 'enseignant':
            modalTitle.textContent = 'Créer un nouvel enseignant';
            break;
        case 'secretaire':
            modalTitle.textContent = 'Créer un nouveau secrétaire';
            break;
        default:
            modalTitle.textContent = 'Créer un nouveau personnel';
    }
    
    modal.show();
}
</script>
@endpush