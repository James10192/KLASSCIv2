@extends('layouts.app')

@section('title', 'Répartition des Matières - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .repartition-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
    }
    
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
    
    .chart-container {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        position: relative;
        height: 400px;
    }
    
    .chart-container canvas {
        max-height: 100%;
        width: 100% !important;
    }
    
    .matiere-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-sm);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .matiere-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary);
        transition: width 0.3s ease;
    }
    
    .matiere-card:hover::before {
        width: 8px;
    }
    
    .matiere-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }
    
    .matiere-nom {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }
    
    .matiere-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: var(--space-lg);
    }
    
    .stat-item {
        text-align: center;
        flex: 1;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary);
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }
    
    .progress-bar-matiere {
        height: 6px;
        background: var(--border);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-top: var(--space-sm);
    }
    
    .progress-fill-matiere {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--primary-light));
        transition: width 0.8s ease;
    }
    
    .filters-section {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: var(--shadow-card);
    }
    
    .summary-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .summary-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        text-align: center;
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
    }
    
    .summary-card .icon {
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
    
    .summary-card.primary .icon { background: var(--primary); }
    .summary-card.success .icon { background: var(--success); }
    .summary-card.info .icon { background: var(--info); }
    .summary-card.warning .icon { background: var(--warning); }
    
    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: var(--space-xs);
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: var(--radius-small);
        margin-right: var(--space-sm);
    }
    
    .objectif-badge {
        position: absolute;
        top: var(--space-md);
        right: var(--space-md);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .objectif-badge.atteint {
        background: var(--success);
        color: white;
    }
    
    .objectif-badge.proche {
        background: var(--warning);
        color: white;
    }
    
    .objectif-badge.loin {
        background: var(--danger);
        color: white;
    }
    
    @media (max-width: 768px) {
        .chart-container {
            height: 300px;
        }
        
        .matiere-stats {
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .summary-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header -->
        <div class="repartition-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chart-pie me-2"></i>Répartition des Matières</h1>
                    <p class="mb-0">Analyse de la distribution des heures d'enseignement par matière</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('esbtp.planning-general.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation du planning -->
        <div class="planning-nav">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.index', ['annee_id' => request('annee_id')]) }}">
                        <i class="fas fa-home me-2"></i>Vue d'ensemble
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.annuel', ['annee_id' => request('annee_id')]) }}">
                        <i class="fas fa-calendar me-2"></i>Planning Annuel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => request('annee_id')]) }}">
                        <i class="fas fa-chart-pie me-2"></i>Répartition Matières
                    </a>
                </li>
                @canany(['manage-planning', 'view-all-timetables'])
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => request('annee_id')]) }}">
                        <i class="fas fa-user-tie me-2"></i>Coordinateur
                    </a>
                </li>
                @endcanany
            </ul>
        </div>

        <!-- Filtres -->
        <div class="filters-section">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label for="annee_id" class="form-label">Année Universitaire</label>
                    <select name="annee_id" id="annee_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les années</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ request('annee_id') == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="classe_id" class="form-label">Classe</label>
                    <select name="classe_id" id="classe_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                {{ $classe->name }} ({{ $classe->filiere->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <div class="d-flex gap-2">
                        <button type="submit" name="periode" value="semestre1" class="btn btn-sm btn-outline-primary {{ request('periode') == 'semestre1' ? 'active' : '' }}">
                            Semestre 1
                        </button>
                        <button type="submit" name="periode" value="semestre2" class="btn btn-sm btn-outline-primary {{ request('periode') == 'semestre2' ? 'active' : '' }}">
                            Semestre 2
                        </button>
                        <button type="submit" name="periode" value="annee" class="btn btn-sm btn-outline-primary {{ request('periode') == 'annee' || !request('periode') ? 'active' : '' }}">
                            Année
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistiques résumées -->
        <div class="summary-stats">
            <div class="summary-card primary">
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-value">{{ $repartition->count() }}</div>
                <div class="stat-label">Matières enseignées</div>
            </div>
            <div class="summary-card success">
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value">{{ number_format($repartition->sum('total_heures'), 1) }}h</div>
                <div class="stat-label">Total heures</div>
            </div>
            <div class="summary-card info">
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value">{{ number_format($repartition->sum('nb_seances')) }}</div>
                <div class="stat-label">Séances programmées</div>
            </div>
            <div class="summary-card warning">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">{{ number_format($repartition->avg('total_heures'), 1) }}h</div>
                <div class="stat-label">Moyenne par matière</div>
            </div>
        </div>

        <div class="row">
            <!-- Graphique en secteurs -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Répartition par Heures</h5>
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
            
            <!-- Graphique en barres -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Comparaison des Matières</h5>
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Liste détaillée des matières -->
        <div class="card-moderne">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Détail par Matière</h5>
                <p class="text-muted mb-0">
                    @if(request('classe_id'))
                        Classe : {{ $classes->find(request('classe_id'))->name ?? 'N/A' }}
                    @else
                        Toutes les classes
                    @endif
                    @if(request('annee_id'))
                        - Année : {{ $annees->find(request('annee_id'))->name ?? 'N/A' }}
                    @endif
                </p>
            </div>
            <div class="card-body">
                @if($repartition->count() > 0)
                    @foreach($repartition as $index => $item)
                    <div class="matiere-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="matiere-nom">{{ $item['matiere']->name ?? 'Matière inconnue' }}</h6>
                                <small class="text-muted">{{ $item['matiere']->code ?? 'N/A' }}</small>
                            </div>
                            <div class="objectif-badge atteint">
                                {{ $item['pourcentage'] }}%
                            </div>
                        </div>
                        
                        <div class="matiere-stats">
                            <div class="stat-item">
                                <div class="stat-value">{{ number_format($item['total_heures'], 1) }}h</div>
                                <div class="stat-label">Total heures</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ $item['nb_seances'] }}</div>
                                <div class="stat-label">Séances</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ number_format($item['total_heures'] / max($item['nb_seances'], 1), 1) }}h</div>
                                <div class="stat-label">Moy. par séance</div>
                            </div>
                        </div>
                        
                        <div class="progress-bar-matiere">
                            <div class="progress-fill-matiere" style="width: {{ min($item['pourcentage'], 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <h5>Aucune donnée disponible</h5>
                        <p class="text-muted">Modifiez les filtres pour afficher des données de répartition.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Données pour les graphiques
    const repartitionData = @json($repartition->toArray());
    const totalHeures = repartitionData.reduce((sum, item) => sum + parseFloat(item.total_heures), 0);
    
    // Les pourcentages sont déjà calculés côté serveur, pas besoin de les recalculer
    
    // Couleurs pour les graphiques
    const colors = [
        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
        '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16'
    ];
    
    if (repartitionData.length > 0) {
        // Graphique en secteurs
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: repartitionData.map(item => item.matiere ? item.matiere.name : 'N/A'),
                datasets: [{
                    data: repartitionData.map(item => parseFloat(item.total_heures)),
                    backgroundColor: colors.slice(0, repartitionData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const percentage = totalHeures > 0 ? ((value / totalHeures) * 100).toFixed(1) : 0;
                                return `${label}: ${value}h (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Graphique en barres
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: repartitionData.map(item => item.matiere ? item.matiere.name : 'N/A'),
                datasets: [{
                    label: 'Heures',
                    data: repartitionData.map(item => parseFloat(item.total_heures)),
                    backgroundColor: colors.slice(0, repartitionData.length),
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + 'h';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                const percentage = totalHeures > 0 ? ((value / totalHeures) * 100).toFixed(1) : 0;
                                return `${value}h (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Animation des barres de progression
    setTimeout(() => {
        $('.progress-fill-matiere').each(function(index) {
            const width = $(this).css('width');
            $(this).css('width', '0');
            
            setTimeout(() => {
                $(this).animate({
                    'width': width
                }, 800);
            }, index * 200);
        });
    }, 500);
    
    // Animation d'entrée des cartes
    $('.matiere-card').each(function(index) {
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