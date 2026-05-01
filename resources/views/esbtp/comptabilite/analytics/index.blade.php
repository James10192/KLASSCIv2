@extends('layouts.app')

@section('title', 'Analytics Prédictifs - Comptabilité')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line text-primary mr-2"></i>
            Analytics Prédictifs
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-download mr-1"></i> Exporter
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="#" onclick="exportAnalytics('pdf')">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </a>
                <a class="dropdown-item" href="#" onclick="exportAnalytics('excel')">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Alertes et notifications -->
    <div id="alerts-container"></div>

    <!-- Cartes de résumé -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Projections Cash-Flow
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="cashflow-status">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Anomalies Détectées
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="anomalies-count">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Recommandations
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="recommendations-count">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-lightbulb fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Score de Performance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="performance-score">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation par onglets -->
    <ul class="nav nav-tabs" id="analytics-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="projections-tab" data-toggle="tab" href="#projections" role="tab">
                <i class="fas fa-chart-line mr-1"></i> Projections Cash-Flow
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="anomalies-tab" data-toggle="tab" href="#anomalies" role="tab">
                <i class="fas fa-exclamation-triangle mr-1"></i> Détection d'Anomalies
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="recommandations-tab" data-toggle="tab" href="#recommandations" role="tab">
                <i class="fas fa-lightbulb mr-1"></i> Recommandations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="benchmarking-tab" data-toggle="tab" href="#benchmarking" role="tab">
                <i class="fas fa-chart-bar mr-1"></i> Benchmarking
            </a>
        </li>
    </ul>

    <!-- Contenu des onglets -->
    <div class="tab-content" id="analytics-content">
        <!-- Onglet Projections -->
        <div class="tab-pane fade show active" id="projections" role="tabpanel">
            <div class="card shadow mb-4 mt-3">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>
                        Projections de Cash-Flow Avancées
                    </h6>
                    <div class="dropdown no-arrow">
                        <div class="form-group mb-0 mr-3">
                            <select class="form-control form-control-sm" id="projections-period" onchange="updateProjections()">
                                <option value="3">3 mois</option>
                                <option value="6" selected>6 mois</option>
                                <option value="12">12 mois</option>
                                <option value="18">18 mois</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="projections-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-3x text-gray-300"></i>
                        <p class="text-gray-500 mt-3">Calcul des projections en cours...</p>
                    </div>
                    <div id="projections-content" style="display: none;">
                        <div class="row">
                            <div class="col-lg-8">
                                <canvas id="cashflow-chart" height="300"></canvas>
                            </div>
                            <div class="col-lg-4">
                                <div id="projections-summary"></div>
                                <div id="projections-risks" class="mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Anomalies -->
        <div class="tab-pane fade" id="anomalies" role="tabpanel">
            <div class="card shadow mb-4 mt-3">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Détection d'Anomalies Financières
                    </h6>
                </div>
                <div class="card-body">
                    <div id="anomalies-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-3x text-gray-300"></i>
                        <p class="text-gray-500 mt-3">Analyse des anomalies en cours...</p>
                    </div>
                    <div id="anomalies-content" style="display: none;">
                        <div class="row">
                            <div class="col-lg-8">
                                <div id="anomalies-timeline"></div>
                            </div>
                            <div class="col-lg-4">
                                <div id="anomalies-stats"></div>
                                <div id="anomalies-actions" class="mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Recommandations -->
        <div class="tab-pane fade" id="recommandations" role="tabpanel">
            <div class="card shadow mb-4 mt-3">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-lightbulb mr-2"></i>
                        Recommandations Intelligentes
                    </h6>
                </div>
                <div class="card-body">
                    <div id="recommandations-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-3x text-gray-300"></i>
                        <p class="text-gray-500 mt-3">Génération des recommandations...</p>
                    </div>
                    <div id="recommandations-content" style="display: none;">
                        <div id="recommandations-list"></div>
                        <div id="recommandations-impact" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Benchmarking -->
        <div class="tab-pane fade" id="benchmarking" role="tabpanel">
            <div class="card shadow mb-4 mt-3">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Benchmarking Inter-Périodes
                    </h6>
                </div>
                <div class="card-body">
                    <div id="benchmarking-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-3x text-gray-300"></i>
                        <p class="text-gray-500 mt-3">Calcul des benchmarks...</p>
                    </div>
                    <div id="benchmarking-content" style="display: none;">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="nav nav-pills mb-3" id="benchmark-pills" role="tablist">
                                    <a class="nav-link active" id="monthly-pill" data-toggle="pill" href="#monthly-benchmark" role="tab">Mensuel</a>
                                    <a class="nav-link" id="quarterly-pill" data-toggle="pill" href="#quarterly-benchmark" role="tab">Trimestriel</a>
                                    <a class="nav-link" id="yearly-pill" data-toggle="pill" href="#yearly-benchmark" role="tab">Annuel</a>
                                </div>
                                <div class="tab-content" id="benchmark-content">
                                    <div class="tab-pane fade show active" id="monthly-benchmark" role="tabpanel">
                                        <canvas id="monthly-chart" height="300"></canvas>
                                    </div>
                                    <div class="tab-pane fade" id="quarterly-benchmark" role="tabpanel">
                                        <canvas id="quarterly-chart" height="300"></canvas>
                                    </div>
                                    <div class="tab-pane fade" id="yearly-benchmark" role="tabpanel">
                                        <canvas id="yearly-chart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de détails -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Détails de l'Analyse</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Contenu dynamique -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="exportModalContent()">Exporter</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Configuration globale
const analyticsConfig = {
    apiUrl: '{{ route("esbtp.comptabilite.analytics-predictifs.api.data") }}',
    refreshInterval: 300000, // 5 minutes
    charts: {},
    data: {}
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeAnalytics();
    setupEventListeners();
    startAutoRefresh();
});

// Fonction d'initialisation principale
function initializeAnalytics() {
    loadProjections();
    loadAnomalies();
    loadRecommandations();
    loadBenchmarking();
    updateSummaryCards();
}

// Configuration des écouteurs d'événements
function setupEventListeners() {
    // Gestion des onglets
    $('#analytics-tabs a').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href').substring(1);
        loadTabContent(target);
    });

    // Gestion des périodes
    $('#projections-period').on('change', function() {
        updateProjections();
    });
}

// Chargement des projections
function loadProjections() {
    $('#projections-loading').show();
    $('#projections-content').hide();

    const periode = $('#projections-period').val() || 6;

    fetchAnalyticsData('projections', { periode: periode })
        .then(data => {
            analyticsConfig.data.projections = data;
            renderProjectionsChart(data);
            renderProjectionsSummary(data);
            $('#projections-loading').hide();
            $('#projections-content').show();
        })
        .catch(error => {
            debugError('Erreur lors du chargement des projections:', error);
            showError('Erreur lors du chargement des projections', 'projections-loading');
        });
}

// Chargement des anomalies
function loadAnomalies() {
    $('#anomalies-loading').show();
    $('#anomalies-content').hide();

    fetchAnalyticsData('anomalies', { periode: 12 })
        .then(data => {
            analyticsConfig.data.anomalies = data;
            renderAnomaliesTimeline(data);
            renderAnomaliesStats(data);
            $('#anomalies-loading').hide();
            $('#anomalies-content').show();
        })
        .catch(error => {
            debugError('Erreur lors du chargement des anomalies:', error);
            showError('Erreur lors du chargement des anomalies', 'anomalies-loading');
        });
}

// Chargement des recommandations
function loadRecommandations() {
    $('#recommandations-loading').show();
    $('#recommandations-content').hide();

    fetchAnalyticsData('recommandations', {})
        .then(data => {
            analyticsConfig.data.recommandations = data;
            renderRecommandationsList(data);
            renderRecommandationsImpact(data);
            $('#recommandations-loading').hide();
            $('#recommandations-content').show();
        })
        .catch(error => {
            debugError('Erreur lors du chargement des recommandations:', error);
            showError('Erreur lors du chargement des recommandations', 'recommandations-loading');
        });
}

// Chargement du benchmarking
function loadBenchmarking() {
    $('#benchmarking-loading').show();
    $('#benchmarking-content').hide();

    fetchAnalyticsData('benchmarking', { periodes: ['mensuel', 'trimestriel', 'annuel'] })
        .then(data => {
            analyticsConfig.data.benchmarking = data;
            renderBenchmarkingCharts(data);
            $('#benchmarking-loading').hide();
            $('#benchmarking-content').show();
        })
        .catch(error => {
            debugError('Erreur lors du chargement du benchmarking:', error);
            showError('Erreur lors du chargement du benchmarking', 'benchmarking-loading');
        });
}

// Fonction générique pour récupérer les données
function fetchAnalyticsData(type, params = {}) {
    return fetch(analyticsConfig.apiUrl + '?' + new URLSearchParams({
        type: type,
        ...params
    }), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            return data.resultats;
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }
    });
}

// Rendu du graphique de projections
function renderProjectionsChart(data) {
    const ctx = document.getElementById('cashflow-chart').getContext('2d');

    if (analyticsConfig.charts.cashflow) {
        analyticsConfig.charts.cashflow.destroy();
    }

    const projections = data.projections || [];
    const labels = projections.map(p => p.periode);
    const recettes = projections.map(p => p.recettes.projection);
    const depenses = projections.map(p => p.depenses.projection);
    const cashflow = projections.map(p => p.cash_flow.projection);

    analyticsConfig.charts.cashflow = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Recettes projetées',
                data: recettes,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Dépenses projetées',
                data: depenses,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Cash-flow net',
                data: cashflow,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
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
                            return new Intl.NumberFormat('fr-FR', {
                                style: 'currency',
                                currency: 'XOF'
                            }).format(value);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' +
                                new Intl.NumberFormat('fr-FR', {
                                    style: 'currency',
                                    currency: 'XOF'
                                }).format(context.parsed.y);
                        }
                    }
                }
            }
        }
    });
}

// Rendu du résumé des projections
function renderProjectionsSummary(data) {
    const resume = data.resume || {};
    const html = `
        <div class="card border-left-primary">
            <div class="card-body">
                <h6 class="card-title text-primary">Résumé des Projections</h6>
                <p class="card-text">
                    <strong>Recettes totales:</strong> ${formatCurrency(resume.total_recettes_projetees)}<br>
                    <strong>Dépenses totales:</strong> ${formatCurrency(resume.total_depenses_projetees)}<br>
                    <strong>Cash-flow cumulé:</strong>
                    <span class="${resume.cash_flow_cumule >= 0 ? 'text-success' : 'text-danger'}">
                        ${formatCurrency(resume.cash_flow_cumule)}
                    </span><br>
                    <strong>Évaluation:</strong>
                    <span class="badge ${resume.evaluation_globale === 'Positive' ? 'badge-success' : 'badge-warning'}">
                        ${resume.evaluation_globale}
                    </span>
                </p>
            </div>
        </div>
    `;
    $('#projections-summary').html(html);
}

// Mise à jour des cartes de résumé
function updateSummaryCards() {
    // Cette fonction sera appelée après le chargement de toutes les données
    setTimeout(() => {
        const data = analyticsConfig.data;

        // Cash-flow status
        if (data.projections && data.projections.resume) {
            const status = data.projections.resume.evaluation_globale;
            $('#cashflow-status').html(`<span class="badge ${status === 'Positive' ? 'badge-success' : 'badge-warning'}">${status}</span>`);
        }

        // Anomalies count
        if (data.anomalies && data.anomalies.statistiques) {
            const count = data.anomalies.statistiques.total_anomalies;
            $('#anomalies-count').text(count + ' détectées');
        }

        // Recommendations count
        if (data.recommandations && data.recommandations.recommandations) {
            const count = data.recommandations.recommandations.length;
            $('#recommendations-count').text(count + ' disponibles');
        }

        // Performance score
        $('#performance-score').html('<span class="badge badge-info">85%</span>');
    }, 2000);
}

// Fonctions utilitaires
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF'
    }).format(amount || 0);
}

function showError(message, containerId) {
    $(`#${containerId}`).html(`
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ${message}
        </div>
    `);
}

function updateProjections() {
    loadProjections();
}

// Actualisation automatique
function startAutoRefresh() {
    setInterval(() => {
        debugLog('Actualisation automatique des données...');
        const activeTab = $('.nav-link.active').attr('href');
        if (activeTab) {
            loadTabContent(activeTab.substring(1));
        }
    }, analyticsConfig.refreshInterval);
}

function loadTabContent(tabName) {
    switch(tabName) {
        case 'projections':
            loadProjections();
            break;
        case 'anomalies':
            loadAnomalies();
            break;
        case 'recommandations':
            loadRecommandations();
            break;
        case 'benchmarking':
            loadBenchmarking();
            break;
    }
}

// Fonction d'export
function exportAnalytics(format) {
    // Implementation de l'export
    debugLog(`Export en format ${format}`);
}

// Placeholder functions pour les autres rendus
function renderAnomaliesTimeline(data) {
    $('#anomalies-timeline').html('<p class="text-muted">Timeline des anomalies sera implémentée ici</p>');
}

function renderAnomaliesStats(data) {
    $('#anomalies-stats').html('<p class="text-muted">Statistiques des anomalies</p>');
}

function renderRecommandationsList(data) {
    $('#recommandations-list').html('<p class="text-muted">Liste des recommandations</p>');
}

function renderRecommandationsImpact(data) {
    $('#recommandations-impact').html('<p class="text-muted">Impact des recommandations</p>');
}

function renderBenchmarkingCharts(data) {
    $('#monthly-chart').html('<p class="text-muted">Graphique mensuel</p>');
}
</script>
@endpush

@push('styles')
<style>
.card-header {
    background: linear-gradient(45deg, #f8f9fc, #eaecf4);
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    background: linear-gradient(45deg, #4e73df, #224abe);
    color: white;
    border-radius: 5px;
}

.nav-tabs .nav-link:hover {
    background: rgba(78, 115, 223, 0.1);
    color: #4e73df;
}

#analytics-content {
    background: white;
    border-radius: 0 0 5px 5px;
    border: 1px solid #e3e6f0;
    border-top: none;
}

.border-left-success {
    border-left: 4px solid #1cc88a !important;
}

.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

.border-left-info {
    border-left: 4px solid #36b9cc !important;
}

.border-left-primary {
    border-left: 4px solid #4e73df !important;
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(78, 115, 223, 0.3);
    border-radius: 50%;
    border-top-color: #4e73df;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
@endpush
