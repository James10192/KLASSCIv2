@extends('layouts.app')

@section('title', 'Analytics des Relances')

@push('styles')
<style>
.analytics-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 15px;
    color: white;
    transition: transform 0.3s ease;
}

.analytics-card:hover {
    transform: translateY(-5px);
}

.metrics-card {
    background: white;
    border-radius: 15px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    height: 100%;
}

.metrics-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.chart-container {
    position: relative;
    height: 400px;
    margin: 20px 0;
}

.segment-badge {
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 600;
    margin: 2px;
    display: inline-block;
}

.segment-high { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.segment-medium { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
.segment-low { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }

.recommendation-card {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
}

.trend-up {
    color: #28a745;
}

.trend-down {
    color: #dc3545;
}

.table-analytics {
    border-radius: 10px;
    overflow: hidden;
}

.progress-analytics {
    height: 8px;
    border-radius: 10px;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        Analytics des Relances
                    </h1>
                    <p class="text-muted mb-0">Analyse avancée des performances des campagnes de relance</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" id="refreshAnalytics">
                        <i class="fas fa-sync-alt me-1"></i>
                        Actualiser
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalExportRapport">
                        <i class="fas fa-download me-1"></i>
                        Exporter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Métriques principales -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card analytics-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">Taux Global</h6>
                            <h3 class="mb-0">{{ $statistiques['taux_global'] ?? 0 }}%</h3>
                            <small class="opacity-75">d'efficacité</small>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card analytics-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">Conversions</h6>
                            <h3 class="mb-0">{{ $statistiques['conversions_totales'] ?? 0 }}</h3>
                            <small class="opacity-75">ce mois</small>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card analytics-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">Délai Moyen</h6>
                            <h3 class="mb-0">{{ $statistiques['delai_moyen'] ?? 0 }}</h3>
                            <small class="opacity-75">jours</small>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card analytics-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-uppercase">ROI</h6>
                            <h3 class="mb-0">{{ $statistiques['roi'] ?? 0 }}%</h3>
                            <small class="opacity-75">retour investissement</small>
                        </div>
                        <div class="fs-1 opacity-75">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Graphique d'efficacité par type -->
        <div class="col-lg-6 mb-4">
            <div class="card metrics-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Efficacité par Type de Relance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartEfficaciteType"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique taux de conversion par niveau -->
        <div class="col-lg-6 mb-4">
            <div class="card metrics-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group text-success me-2"></i>
                        Taux de Conversion par Niveau
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartConversionNiveau"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Tendances mensuelles -->
        <div class="col-lg-8 mb-4">
            <div class="card metrics-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-info me-2"></i>
                        Tendances des 6 Derniers Mois
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="tendanceType" id="tendanceRelances" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="tendanceRelances">Relances</label>

                        <input type="radio" class="btn-check" name="tendanceType" id="tendanceEfficacite" autocomplete="off">
                        <label class="btn btn-outline-primary" for="tendanceEfficacite">Efficacité</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartTendances"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance des segments -->
        <div class="col-lg-4 mb-4">
            <div class="card metrics-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users text-warning me-2"></i>
                        Performance des Segments
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($statistiques['segmentation_performance'] ?? [] as $segment => $performance)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="segment-badge {{ $performance['taux_reponse'] >= 70 ? 'segment-high' : ($performance['taux_reponse'] >= 50 ? 'segment-medium' : 'segment-low') }}">
                                {{ ucfirst(str_replace('_', ' ', $segment)) }}
                            </span>
                            <strong>{{ $performance['taux_reponse'] }}%</strong>
                        </div>
                        <div class="progress progress-analytics">
                            <div class="progress-bar
                                {{ $performance['taux_reponse'] >= 70 ? 'bg-success' : ($performance['taux_reponse'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                 style="width: {{ $performance['taux_reponse'] }}%"></div>
                        </div>
                        <small class="text-muted">Délai moyen: {{ $performance['delai_moyen_paiement'] }} jours</small>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Prédictions et recommandations -->
        <div class="col-lg-8 mb-4">
            <div class="card metrics-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-crystal-ball text-primary me-2"></i>
                        Prédictions et Recommandations
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Prédictions pour le mois prochain</h6>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-arrow-up trend-up me-2"></i>
                                <span>Efficacité prévue: <strong>{{ $statistiques['predictions']['efficacite_prevue_mois_prochain'] ?? 0 }}%</strong></span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-bell text-info me-2"></i>
                                <span>Volume prévu: <strong>{{ $statistiques['predictions']['volume_relances_prevu'] ?? 0 }}</strong> relances</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Recommandations</h6>
                            @foreach($statistiques['predictions']['recommandations'] ?? [] as $recommandation)
                            <div class="recommendation-card">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                {{ $recommandation }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau détaillé -->
        <div class="col-lg-4 mb-4">
            <div class="card metrics-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table text-dark me-2"></i>
                        Détails par Type
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-analytics mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Taux</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistiques['efficacite_par_type'] ?? [] as $type => $data)
                                <tr>
                                    <td>
                                        <i class="fas fa-{{ $type == 'email' ? 'envelope' : ($type == 'sms' ? 'mobile-alt' : 'file-pdf') }} me-2"></i>
                                        {{ ucfirst($type) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $data['taux_efficacite'] >= 50 ? 'success' : ($data['taux_efficacite'] >= 30 ? 'warning' : 'danger') }}">
                                            {{ $data['taux_efficacite'] }}%
                                        </span>
                                    </td>
                                    <td>{{ $data['total_envoyees'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Export Rapport -->
<div class="modal fade" id="modalExportRapport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exporter le Rapport Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formExportRapport">
                    <div class="mb-3">
                        <label class="form-label">Format d'export</label>
                        <select class="form-select" name="format" required>
                            <option value="pdf">PDF (Rapport complet)</option>
                            <option value="excel">Excel (Données détaillées)</option>
                            <option value="csv">CSV (Données brutes)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Période</label>
                        <select class="form-select" name="periode" required>
                            <option value="mois_actuel">Mois actuel</option>
                            <option value="3_mois">3 derniers mois</option>
                            <option value="6_mois">6 derniers mois</option>
                            <option value="annee">Année en cours</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="inclure_graphiques" id="inclureGraphiques" checked>
                        <label class="form-check-label" for="inclureGraphiques">
                            Inclure les graphiques
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnExporter">
                    <i class="fas fa-download me-1"></i>
                    Exporter
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données des graphiques depuis le serveur
    const donnees = @json($statistiques);

    // Graphique efficacité par type
    const ctxType = document.getElementById('chartEfficaciteType').getContext('2d');
    new Chart(ctxType, {
        type: 'doughnut',
        data: {
            labels: Object.keys(donnees.efficacite_par_type || {}),
            datasets: [{
                data: Object.values(donnees.efficacite_par_type || {}).map(d => d.taux_efficacite),
                backgroundColor: ['#667eea', '#4facfe', '#43e97b', '#f093fb'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Graphique conversion par niveau
    const ctxNiveau = document.getElementById('chartConversionNiveau').getContext('2d');
    new Chart(ctxNiveau, {
        type: 'bar',
        data: {
            labels: Object.keys(donnees.taux_conversion_par_niveau || {}).map(k => k.replace('niveau_', 'Niveau ')),
            datasets: [{
                label: 'Taux de conversion (%)',
                data: Object.values(donnees.taux_conversion_par_niveau || {}).map(d => d.taux),
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Graphique tendances
    const ctxTendances = document.getElementById('chartTendances').getContext('2d');
    const chartTendances = new Chart(ctxTendances, {
        type: 'line',
        data: {
            labels: (donnees.tendances_mensuelles || []).map(t => t.mois),
            datasets: [{
                label: 'Relances envoyées',
                data: (donnees.tendances_mensuelles || []).map(t => t.relances_envoyees),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Basculer entre relances et efficacité
    document.querySelectorAll('input[name="tendanceType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.id === 'tendanceEfficacite') {
                chartTendances.data.datasets[0].label = 'Taux d\'efficacité (%)';
                chartTendances.data.datasets[0].data = (donnees.tendances_mensuelles || []).map(t => t.taux_efficacite);
                chartTendances.data.datasets[0].borderColor = '#43e97b';
                chartTendances.data.datasets[0].backgroundColor = 'rgba(67, 233, 123, 0.1)';
            } else {
                chartTendances.data.datasets[0].label = 'Relances envoyées';
                chartTendances.data.datasets[0].data = (donnees.tendances_mensuelles || []).map(t => t.relances_envoyees);
                chartTendances.data.datasets[0].borderColor = '#667eea';
                chartTendances.data.datasets[0].backgroundColor = 'rgba(102, 126, 234, 0.1)';
            }
            chartTendances.update();
        });
    });

    // Actualisation des données
    document.getElementById('refreshAnalytics').addEventListener('click', function() {
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Actualisation...';
        setTimeout(() => {
            location.reload();
        }, 1000);
    });

    // Export rapport
    document.getElementById('btnExporter').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('formExportRapport'));
        const params = new URLSearchParams(formData).toString();

        window.open(`{{ route('esbtp.comptabilite.relances.export') }}?${params}`, '_blank');

        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalExportRapport'));
        modal.hide();
    });
});
</script>
@endpush
