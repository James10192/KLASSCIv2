@extends('layouts.app')

@section('title', 'Répartition des Matières - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .filtres-repartition {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
        padding: var(--space-lg);
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
    }
    
    .graphique-repartition {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-xl);
    }
    
    .graphique-container {
        position: relative;
        height: 400px;
        margin-bottom: var(--space-lg);
    }
    
    .tableau-repartition {
        background: var(--surface);
        border-radius: var(--radius-medium);
        overflow: hidden;
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-xl);
    }
    
    .tableau-repartition table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .tableau-repartition th {
        background: var(--primary);
        color: white;
        padding: var(--space-md);
        text-align: left;
        font-weight: 600;
    }
    
    .tableau-repartition td {
        padding: var(--space-md);
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    
    .tableau-repartition tr:hover {
        background: rgba(var(--primary-rgb), 0.05);
    }
    
    .barre-progression {
        width: 100%;
        height: 20px;
        background: var(--background-muted);
        border-radius: var(--radius-full);
        overflow: hidden;
        position: relative;
    }
    
    .barre-progression-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--success), var(--success-light));
        border-radius: var(--radius-full);
        transition: width 1s ease-out;
        position: relative;
    }
    
    .barre-progression-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmer 2s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .badge-pourcentage {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-full);
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .badge-pourcentage.faible {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }
    
    .badge-pourcentage.moyen {
        background: rgba(var(--warning-rgb), 0.1);
        color: var(--warning);
    }
    
    .badge-pourcentage.eleve {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }
    
    .comparaison-objectifs {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }
    
    .objectif-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        border-left: 4px solid var(--info);
    }
    
    .objectif-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: var(--space-md);
    }
    
    .objectif-progres {
        display: flex;
        justify-content: space-between;
        margin-bottom: var(--space-sm);
        font-size: 0.9rem;
    }
    
    .actions-repartition {
        display: flex;
        gap: var(--space-md);
        margin-bottom: var(--space-xl);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Répartition des Matières</h1>
                <p class="header-subtitle">Analyse de la distribution des heures par matière</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi secondary" onclick="exporterRepartition()">
                    <i class="fas fa-download"></i>Exporter
                </button>
                <a href="{{ route('esbtp.planning-general.index', ['annee_id' => request('annee_id')]) }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
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
        <form method="GET" class="filtres-repartition">
            <div class="form-group">
                <label for="annee_id" class="form-label">Année universitaire</label>
                <select name="annee_id" id="annee_id" class="form-control">
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>
                            {{ $annee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label for="classe_id" class="form-label">Classe (optionnel)</label>
                <select name="classe_id" id="classe_id" class="form-control">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" {{ $classeId == $classe->id ? 'selected' : '' }}>
                            {{ $classe->name }} - {{ $classe->filiere->nom ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group d-flex align-items-end">
                <button type="submit" class="btn-acasi primary w-100">
                    <i class="fas fa-filter"></i>Filtrer
                </button>
            </div>
        </form>

        <!-- Actions rapides -->
        <div class="actions-repartition">
            <button type="button" class="btn-acasi secondary" onclick="toggleVueGraphique()">
                <i class="fas fa-chart-pie"></i>Vue graphique
            </button>
            <button type="button" class="btn-acasi secondary" onclick="toggleVueTableau()">
                <i class="fas fa-table"></i>Vue tableau
            </button>
            <button type="button" class="btn-acasi secondary" onclick="comparer()">
                <i class="fas fa-balance-scale"></i>Comparer avec objectifs
            </button>
        </div>

        <!-- Graphique de répartition -->
        <div class="graphique-repartition" id="vue-graphique">
            <div class="section-title mb-lg">
                <i class="fas fa-chart-pie me-2"></i>
                Répartition des Heures par Matière
            </div>
            
            <div class="graphique-container">
                <canvas id="repartitionChart"></canvas>
            </div>
            
            <div class="text-center">
                <small class="text-muted">
                    Total des heures: 
                    <strong>{{ $repartition->sum('total_heures') }}h</strong> 
                    réparties sur {{ $repartition->count() }} matières
                </small>
            </div>
        </div>

        <!-- Tableau détaillé -->
        <div class="tableau-repartition" id="vue-tableau">
            <table>
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Nombre de séances</th>
                        <th>Total heures</th>
                        <th>Pourcentage</th>
                        <th>Progression</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalHeures = $repartition->sum('total_heures');
                    @endphp
                    
                    @foreach($repartition as $item)
                    @php
                        $pourcentage = $totalHeures > 0 ? ($item['total_heures'] / $totalHeures * 100) : 0;
                        $badgeClass = $pourcentage < 5 ? 'faible' : ($pourcentage < 15 ? 'moyen' : 'eleve');
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="matiere-icon me-3">
                                    <i class="fas fa-book text-primary"></i>
                                </div>
                                <div>
                                    <strong>{{ $item['matiere']->nom ?? 'Matière inconnue' }}</strong>
                                    <div class="text-muted small">
                                        {{ $item['matiere']->code ?? '' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $item['nb_seances'] }}</span>
                        </td>
                        <td>
                            <strong>{{ number_format($item['total_heures'], 1) }}h</strong>
                        </td>
                        <td>
                            <span class="badge-pourcentage {{ $badgeClass }}">
                                {{ number_format($pourcentage, 1) }}%
                            </span>
                        </td>
                        <td>
                            <div class="barre-progression">
                                <div class="barre-progression-fill" 
                                     style="width: {{ $pourcentage }}%"
                                     data-pourcentage="{{ $pourcentage }}">
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($pourcentage >= 15)
                                <span class="badge bg-success">Élevé</span>
                            @elseif($pourcentage >= 5)
                                <span class="badge bg-warning">Moyen</span>
                            @else
                                <span class="badge bg-danger">Faible</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Comparaison avec les objectifs -->
        @if(!empty($objectifsComparaison))
        <div class="comparaison-objectifs">
            @foreach($objectifsComparaison as $objectif)
            <div class="objectif-card">
                <div class="objectif-header">
                    <h6>{{ $objectif['nom'] }}</h6>
                    <span class="badge bg-{{ $objectif['atteint'] ? 'success' : 'warning' }}">
                        {{ $objectif['atteint'] ? 'Atteint' : 'En cours' }}
                    </span>
                </div>
                
                <div class="objectif-progres">
                    <span>{{ $objectif['progression'] }}%</span>
                    <span>{{ $objectif['heures_actuelles'] }}h / {{ $objectif['heures_objectif'] }}h</span>
                </div>
                
                <div class="barre-progression">
                    <div class="barre-progression-fill" 
                         style="width: {{ $objectif['progression'] }}%">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function() {
    // Initialisation du graphique en camembert
    const ctx = document.getElementById('repartitionChart').getContext('2d');
    const repartitionData = @json($repartition);
    
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: repartitionData.map(item => item.matiere?.nom || 'Matière inconnue'),
            datasets: [{
                data: repartitionData.map(item => item.total_heures),
                backgroundColor: [
                    '#8b5cf6', '#06b6d4', '#f59e0b', '#ef4444', '#10b981',
                    '#6366f1', '#f97316', '#ec4899', '#84cc16', '#6b7280'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return `${context.label}: ${context.raw}h (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Animation des barres de progression
    setTimeout(() => {
        $('.barre-progression-fill').each(function() {
            const pourcentage = $(this).data('pourcentage');
            $(this).css('width', pourcentage + '%');
        });
    }, 500);
});

function toggleVueGraphique() {
    $('#vue-graphique').show();
    $('#vue-tableau').hide();
}

function toggleVueTableau() {
    $('#vue-graphique').hide();
    $('#vue-tableau').show();
}

function comparer() {
    // Logique pour afficher la comparaison avec les objectifs
    alert('Fonctionnalité de comparaison à implémenter');
}

function exporterRepartition() {
    // Logique d'export
    alert('Export en cours de développement');
}
</script>
@endpush