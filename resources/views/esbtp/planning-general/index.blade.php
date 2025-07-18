@extends('layouts.app')

@section('title', 'Planning Général - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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
    
    .stats-planning {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .stat-planning {
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .stat-planning::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }
    
    .stat-planning.primary::before { background: linear-gradient(90deg, var(--primary), #60a5fa); }
    .stat-planning.success::before { background: linear-gradient(90deg, var(--success), #34d399); }
    .stat-planning.warning::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
    .stat-planning.info::before { background: linear-gradient(90deg, var(--info), #38bdf8); }
    
    .stat-icon-planning {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-sm);
        font-size: 20px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .stat-planning.primary .stat-icon-planning { color: var(--primary); }
    .stat-planning.success .stat-icon-planning { color: var(--success); }
    .stat-planning.warning .stat-icon-planning { color: var(--warning); }
    .stat-planning.info .stat-icon-planning { color: var(--info); }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-top: var(--space-xl);
    }
    
    .action-card {
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        overflow: hidden;
    }
    
    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, transparent 0%, rgba(99, 102, 241, 0.02) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .action-card:hover::before {
        opacity: 1;
    }
    
    .action-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        margin-bottom: var(--space-md);
    }
    
    .action-icon.calendar { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
    .action-icon.chart { background: linear-gradient(135deg, #06b6d4, #67e8f9); }
    .action-icon.users { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .action-icon.settings { background: linear-gradient(135deg, #ef4444, #f87171); }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Planning Général</h1>
                <p class="header-subtitle">Vue d'ensemble du planning académique et organisation des cours</p>
            </div>
            <div class="header-actions">
                <div class="btn-group">
                    <button type="button" class="btn-acasi secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-calendar-alt"></i>
                        {{ $anneeSelectionnee ? $anneeSelectionnee->name : 'Sélectionner une année' }}
                    </button>
                    <ul class="dropdown-menu">
                        @foreach($annees as $annee)
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.planning-general.index', ['annee_id' => $annee->id]) }}">
                                    {{ $annee->name }}
                                    @if($annee->is_current)
                                        <span class="badge bg-primary ms-2">En cours</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                @canany(['manage-planning', 'view-all-timetables'])
                <a href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}" class="btn-acasi primary">
                    <i class="fas fa-cogs"></i>Gestion Planning
                </a>
                @endcanany
            </div>
        </div>

        <!-- Navigation du planning -->
        <div class="planning-nav">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('esbtp.planning-general.index', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-home me-2"></i>Vue d'ensemble
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-calendar me-2"></i>Planning Annuel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-chart-pie me-2"></i>Répartition Matières
                    </a>
                </li>
                @canany(['manage-planning', 'view-all-timetables'])
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}">
                        <i class="fas fa-user-tie me-2"></i>Coordinateur
                    </a>
                </li>
                @endcanany
            </ul>
        </div>

        @if(!$anneeSelectionnee)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Aucune année universitaire sélectionnée. Veuillez en choisir une pour afficher le planning.
            </div>
        @else
            <!-- Statistiques du planning -->
            <div class="stats-planning">
                <div class="card-moderne stat-planning primary">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value">{{ number_format($stats['total_seances']) }}</div>
                        <div class="stat-label">Séances programmées</div>
                    </div>
                </div>
                
                <div class="card-moderne stat-planning success">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-value">{{ number_format($stats['total_heures'], 0) }}h</div>
                        <div class="stat-label">Heures de cours</div>
                    </div>
                </div>
                
                <div class="card-moderne stat-planning warning">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value">{{ $stats['total_classes'] }}</div>
                        <div class="stat-label">Classes actives</div>
                    </div>
                </div>
                
                <div class="card-moderne stat-planning info">
                    <div class="p-lg">
                        <div class="stat-icon-planning">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-value">{{ $stats['total_matieres'] }}</div>
                        <div class="stat-label">Matières enseignées</div>
                    </div>
                </div>
            </div>

            <!-- Informations de l'année sélectionnée -->
            <div class="card-moderne mb-lg">
                <div class="p-lg">
                    <div class="section-title mb-md">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations Année {{ $anneeSelectionnee->name }}
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Période :</strong>
                                {{ \Carbon\Carbon::parse($anneeSelectionnee->annee_debut)->format('d/m/Y') }} - 
                                {{ \Carbon\Carbon::parse($anneeSelectionnee->annee_fin)->format('d/m/Y') }}
                            </div>
                            <div class="info-item mt-2">
                                <strong>Statut :</strong>
                                @if($anneeSelectionnee->is_current)
                                    <span class="badge bg-success">Année en cours</span>
                                @else
                                    <span class="badge bg-secondary">Année archivée</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Total enseignants :</strong>
                                {{ $stats['total_enseignants'] }} enseignants actifs
                            </div>
                            <div class="info-item mt-2">
                                <strong>Charge globale :</strong>
                                {{ number_format($stats['total_heures'], 0) }} heures planifiées
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-lg">
                        <i class="fas fa-bolt me-2"></i>
                        Actions Rapides
                    </div>
                    
                    <div class="quick-actions">
                        <a href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon calendar">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h6 class="font-semibold">Planning Annuel</h6>
                                <p class="text-muted mb-0">Visualisez le calendrier complet de l'année académique</p>
                            </div>
                        </a>
                        
                        <a href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon chart">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <h6 class="font-semibold">Répartition Matières</h6>
                                <p class="text-muted mb-0">Analysez la distribution des heures par matière</p>
                            </div>
                        </a>
                        
                        <a href="{{ route('esbtp.emploi-temps.index') }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon users">
                                    <i class="fas fa-table"></i>
                                </div>
                                <h6 class="font-semibold">Emplois du Temps</h6>
                                <p class="text-muted mb-0">Gérez les emplois du temps par classe</p>
                            </div>
                        </a>
                        
                        @canany(['manage-planning', 'view-all-timetables'])
                        <a href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee->id]) }}" class="action-card card-moderne text-decoration-none">
                            <div class="p-lg">
                                <div class="action-icon settings">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h6 class="font-semibold">Gestion Avancée</h6>
                                <p class="text-muted mb-0">Outils de coordination et d'administration</p>
                            </div>
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, observerOptions);
    
    // Observer toutes les cartes
    $('.card-moderne').each(function() {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)',
            'transition': 'all 0.6s ease-out'
        });
        observer.observe(this);
    });
});
</script>
@endpush