@extends('layouts.app')

@section('title', 'Gestion des Coordinateurs - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .coordinateurs-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
    }
    
    .coordinateur-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-md);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .coordinateur-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .coordinateur-avatar {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        font-weight: bold;
    }
    
    .coordinateur-info h6 {
        color: var(--primary);
        margin-bottom: var(--space-xs);
        font-weight: 600;
    }
    
    .coordinateur-meta {
        display: flex;
        gap: var(--space-md);
        margin-top: var(--space-sm);
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.875rem;
        color: var(--text-secondary);
    }
    
    .status-badge {
        position: absolute;
        top: var(--space-md);
        right: var(--space-md);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .status-badge.active {
        background: var(--success);
        color: white;
    }
    
    .status-badge.inactive {
        background: var(--danger);
        color: white;
    }
    
    .actions-group {
        display: flex;
        gap: var(--space-sm);
    }
    
    .search-filters {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: var(--shadow-card);
    }
    
    .stats-grid {
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
    }
    
    .stat-card .icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-md);
        font-size: 1.25rem;
        color: white;
    }
    
    .stat-card.primary .icon { background: var(--primary); }
    .stat-card.success .icon { background: var(--success); }
    .stat-card.warning .icon { background: var(--warning); }
    .stat-card.info .icon { background: var(--info); }
    
    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }
    
    .empty-state .icon {
        font-size: 4rem;
        margin-bottom: var(--space-lg);
        opacity: 0.5;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="coordinateurs-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-users-cog me-2"></i>Gestion des Coordinateurs</h1>
                    <p class="mb-0">Administration et supervision des coordinateurs pédagogiques</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('esbtp.coordinateurs.create') }}" class="btn-acasi primary">
                        <i class="fas fa-plus me-1"></i>Nouveau Coordinateur
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">{{ $coordinateurs->total() }}</div>
                <div class="stat-label">Total Coordinateurs</div>
            </div>
            <div class="stat-card success">
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value">{{ $coordinateurs->where('is_active', true)->count() }}</div>
                <div class="stat-label">Coordinateurs Actifs</div>
            </div>
            <div class="stat-card warning">
                <div class="icon">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-value">{{ $coordinateurs->where('is_active', false)->count() }}</div>
                <div class="stat-label">Coordinateurs Inactifs</div>
            </div>
            <div class="stat-card info">
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value">{{ $coordinateurs->where('created_at', '>=', now()->startOfMonth())->count() }}</div>
                <div class="stat-label">Créés ce mois</div>
            </div>
        </div>

        <!-- Filtres de recherche -->
        <div class="search-filters">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Nom, email, spécialité..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Statut</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactifs</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Trier par</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nom</option>
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Date de création</option>
                        <option value="last_login" {{ request('sort') == 'last_login' ? 'selected' : '' }}>Dernière connexion</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-acasi primary w-100">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des coordinateurs -->
        <div class="card-moderne">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Liste des Coordinateurs</h5>
                <p class="text-muted mb-0">{{ $coordinateurs->total() }} coordinateur(s) trouvé(s)</p>
            </div>
            <div class="card-body">
                @if($coordinateurs->count() > 0)
                    @foreach($coordinateurs as $coordinateur)
                    <div class="coordinateur-card">
                        <div class="status-badge {{ $coordinateur->is_active ? 'active' : 'inactive' }}">
                            {{ $coordinateur->is_active ? 'Actif' : 'Inactif' }}
                        </div>
                        
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <div class="coordinateur-avatar">
                                    {{ strtoupper(substr($coordinateur->name, 0, 2)) }}
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="coordinateur-info">
                                    <h6>{{ $coordinateur->name }}</h6>
                                    <div class="coordinateur-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-envelope"></i>
                                            <span>{{ $coordinateur->email }}</span>
                                        </div>
                                        @if($coordinateur->telephone)
                                        <div class="meta-item">
                                            <i class="fas fa-phone"></i>
                                            <span>{{ $coordinateur->telephone }}</span>
                                        </div>
                                        @endif
                                        @if($coordinateur->specialite)
                                        <div class="meta-item">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>{{ $coordinateur->specialite }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="coordinateur-meta mt-2">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>Créé le {{ $coordinateur->created_at->format('d/m/Y') }}</span>
                                        </div>
                                        @if($coordinateur->last_login_at)
                                        <div class="meta-item">
                                            <i class="fas fa-sign-in-alt"></i>
                                            <span>Dernière connexion: {{ $coordinateur->last_login_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="actions-group">
                                    <a href="{{ route('esbtp.coordinateurs.show', $coordinateur) }}" 
                                       class="btn btn-sm btn-outline-info" title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.coordinateurs.edit', $coordinateur) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($coordinateur->id !== auth()->id())
                                    <form action="{{ route('esbtp.coordinateurs.toggle-status', $coordinateur) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-{{ $coordinateur->is_active ? 'warning' : 'success' }}" 
                                                title="{{ $coordinateur->is_active ? 'Désactiver' : 'Activer' }}"
                                                onclick="return confirm('Êtes-vous sûr de vouloir {{ $coordinateur->is_active ? 'désactiver' : 'activer' }} ce coordinateur ?')">
                                            <i class="fas fa-{{ $coordinateur->is_active ? 'ban' : 'check' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('esbtp.coordinateurs.destroy', $coordinateur) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Supprimer"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce coordinateur ? Cette action est irréversible.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $coordinateurs->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Aucun coordinateur trouvé</h5>
                        <p class="text-muted">Commencez par créer votre premier coordinateur.</p>
                        <a href="{{ route('esbtp.coordinateurs.create') }}" class="btn-acasi primary">
                            <i class="fas fa-plus me-1"></i>Créer un coordinateur
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal pour afficher les credentials --}}
@include('partials.credentials-modal')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée des cartes
    $('.coordinateur-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 300);
        }, index * 100);
    });
});
</script>
@endpush