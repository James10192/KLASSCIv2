{{-- Composant Header/Slider pour les pages de planning --}}
@props(['title' => 'Planning Général', 'subtitle' => '', 'activeTab' => 'overview', 'anneeSelectionnee' => null, 'annees' => collect()])

<style>
    .planning-nav {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
    }
    
    .planning-nav .nav-tabs {
        border: none;
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--radius-small);
        padding: var(--space-xs);
    }
    
    .planning-nav .nav-link {
        border: none;
        color: var(--text-secondary);
        background: transparent;
        border-radius: var(--radius-small);
        padding: var(--space-sm) var(--space-md);
        margin: 0 var(--space-xs);
        transition: all 0.3s ease;
    }
    
    .planning-nav .nav-link.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
    }
</style>

<!-- Header moderne -->
<div class="dashboard-header">
    <div class="header-left">
        <h1>{{ $title }}</h1>
        <p class="header-subtitle">{{ $subtitle ?: 'Vue d\'ensemble du planning académique et organisation des cours' }}</p>
    </div>
    <div class="header-actions">
        <div class="btn-group">
            <button type="button" class="btn-acasi secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-calendar-alt me-2"></i>
                {{ $anneeSelectionnee ? $anneeSelectionnee->name : 'Sélectionner une année' }}
            </button>
            <ul class="dropdown-menu" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-small); box-shadow: var(--shadow-elevated);">
                @foreach($annees as $annee)
                    <li>
                        <a class="dropdown-item" href="{{ request()->url() }}?annee_id={{ $annee->id }}" 
                           style="color: var(--text-primary); padding: var(--space-sm) var(--space-md); border-radius: var(--radius-small); transition: all 0.2s ease;"
                           onmouseover="this.style.background='var(--background)'" 
                           onmouseout="this.style.background='transparent'">
                            {{ $annee->name }}
                            @if(optional($annee)->is_current)
                                <span class="badge bg-primary ms-2">En cours</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        
        @canany(['manage-planning', 'view-all-timetables'])
        <a href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}" class="btn-acasi secondary">
            <i class="fas fa-cogs me-2"></i>Gestion Planning
        </a>
        <a href="{{ route('esbtp.enseignants.index') }}" class="btn-acasi primary">
            <i class="fas fa-users me-2"></i>Gestion Enseignants
        </a>
        @endcanany
    </div>
</div>

<!-- Navigation du planning -->
<div class="planning-nav">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" 
               href="{{ route('esbtp.planning-general.index', ['annee_id' => $anneeSelectionnee?->id]) }}">
                <i class="fas fa-home me-2"></i>Vue d'ensemble
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'annuel' ? 'active' : '' }}" 
               href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee?->id]) }}">
                <i class="fas fa-calendar me-2"></i>Planning Annuel
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'repartition' ? 'active' : '' }}" 
               href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee?->id]) }}">
                <i class="fas fa-layer-group me-2"></i>Charge par classe
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'evenements' ? 'active' : '' }}" 
               href="{{ route('esbtp.evenements-academiques.index', ['annee_id' => $anneeSelectionnee?->id]) }}">
                <i class="fas fa-calendar-check me-2"></i>Événements Académiques
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'emargement' ? 'active' : '' }}" 
               href="{{ route('esbtp.planning-general.emargement', ['annee_id' => $anneeSelectionnee?->id]) }}">
                <i class="fas fa-qrcode me-2"></i>Codes d'Émargement
            </a>
        </li>
        @canany(['manage-planning', 'view-all-timetables'])
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'coordinateur' ? 'active' : '' }}" 
               href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}">
                <i class="fas fa-user-tie me-2"></i>Coordinateur
            </a>
        </li>
        @endcanany
    </ul>
</div>
