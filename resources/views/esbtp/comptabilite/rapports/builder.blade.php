@extends('esbtp.comptabilite.components.dashboard-layout')

@section('title', 'Rapports personnalisés')

@section('sidebar')
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="navigation-link">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
    </li>
    <li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.paiements.index') }}" class="navigation-link">
            <i class="fas fa-credit-card me-2"></i>Paiements
        </a>
    </li>
    @if(Route::has('esbtp.comptabilite.depenses.index'))
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.depenses.index') }}" class="navigation-link">
            <i class="fas fa-wallet me-2"></i>Dépenses
        </a>
    </li>
@endif
@if(Route::has('esbtp.comptabilite.factures.index'))
<li class="navigation-item">
        <a href="{{ route('esbtp.comptabilite.factures.index') }}" class="navigation-link">
            <i class="fas fa-file-invoice me-2"></i>Factures
        </a>
    </li>
@endif
<li class="navigation-item active">
        <a href="{{ route('esbtp.comptabilite.rapports.builder') }}" class="navigation-link active">
            <i class="fas fa-chart-pie me-2"></i>Rapports
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-chart-pie me-2"></i>Rapports personnalisés
            </h4>
            <div class="text-muted small">Créez, analysez et exportez vos rapports comptables</div>
                    </div>
                    <div>
            <a href="#" class="btn btn-primary" onclick="clearReport()">
                <i class="fas fa-plus me-1"></i>Nouveau rapport
            </a>
                    </div>
                </div>
@endsection

@section('sidebarRight')
    <div class="card mb-3">
        <div class="card-header py-2 px-3">
            <span class="fw-bold"><i class="fas fa-bolt me-2"></i>Actions Rapides</span>
        </div>
        <div class="card-body py-2 px-3">
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="generateReport()">
                    <i class="fas fa-magic me-1"></i>Génération Auto
                </button>
                <button class="btn btn-outline-success btn-sm" onclick="exportReport()">
                    <i class="fas fa-download me-1"></i>Exporter
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="shareReport()">
                    <i class="fas fa-share me-1"></i>Partager
                </button>
                <button class="btn btn-outline-warning btn-sm" onclick="scheduleReport()">
                    <i class="fas fa-calendar me-1"></i>Programmer
                    </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="loadTemplate()">
                    <i class="fas fa-folder-open me-1"></i>Modèles
                    </button>
                </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header py-2 px-3">
            <span class="fw-bold"><i class="fas fa-robot me-2"></i>Analytics IA</span>
        </div>
        <div class="card-body py-2 px-3">
            <button class="btn btn-outline-light btn-sm w-100 mb-2" onclick="addPredictiveAnalysis('cashflow')">
                <i class="fas fa-money-bill-trend-up me-2"></i>Prévision Cash-Flow
            </button>
            <button class="btn btn-outline-light btn-sm w-100 mb-2" onclick="addPredictiveAnalysis('anomaly')">
                <i class="fas fa-exclamation-triangle me-2"></i>Détection Anomalies
            </button>
            <button class="btn btn-outline-light btn-sm w-100 mb-2" onclick="addPredictiveAnalysis('trends')">
                <i class="fas fa-chart-line me-2"></i>Analyse Tendances
            </button>
            <button class="btn btn-outline-light btn-sm w-100 mb-2" onclick="addPredictiveAnalysis('forecast')">
                <i class="fas fa-crystal-ball me-2"></i>Prévisions IA
            </button>
            <div class="mt-3">
                <h6 class="fw-bold">Projections 6 mois</h6>
                <canvas id="prediction-chart" width="100" height="60"></canvas>
            </div>
        </div>
    </div>
@endsection

@section('content-block')
<div class="container-fluid px-0">
    <div class="row g-3">
        <!-- Left Panel: Component Library & Schedule -->
        <div class="col-xl-3 col-lg-4 mb-4">
            <div class="card mb-3">
                <div class="card-header py-2 px-3">
                    <span class="fw-bold"><i class="fas fa-cubes me-2"></i>Bibliothèque de composants</span>
                        </div>
                <div class="card-body py-2 px-3" id="component-library">
                                <div class="drag-component" data-type="kpi">
                                    <div class="component-icon icon-kpi">
                                        <i class="fas fa-tachometer-alt"></i>
                                    </div>
                                    <div>
                            <strong>KPI</strong>
                            <div class="small text-muted">Indicateurs financiers</div>
                                    </div>
                                </div>
                                <div class="drag-component" data-type="chart">
                                    <div class="component-icon icon-chart">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <strong>Graphique</strong>
                            <div class="small text-muted">Évolution</div>
                                    </div>
                                </div>
                                <div class="drag-component" data-type="table">
                                    <div class="component-icon icon-table">
                                        <i class="fas fa-table"></i>
                                    </div>
                                    <div>
                                        <strong>Tableau</strong>
                                        <div class="small text-muted">Données détaillées</div>
                                    </div>
                                </div>
                                <div class="drag-component" data-type="filter">
                                    <div class="component-icon icon-filter">
                                        <i class="fas fa-filter"></i>
                                    </div>
                                    <div>
                            <strong>Filtres</strong>
                                        <div class="small text-muted">Critères de sélection</div>
                                    </div>
                                </div>
                                <div class="drag-component" data-type="export">
                                    <div class="component-icon icon-export">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <div>
                                        <strong>Export</strong>
                                        <div class="small text-muted">PDF, Excel, CSV</div>
                                    </div>
                                </div>
                            </div>
                        </div>
            <div class="card">
                <div class="card-header py-2 px-3">
                    <span class="fw-bold"><i class="fas fa-calendar-alt me-2"></i>Rapports Programmés</span>
                </div>
                <div class="card-body py-2 px-3">
                            <button class="btn btn-primary btn-sm w-100 mb-3" onclick="openScheduleModal()">
                                <i class="fas fa-plus me-1"></i>Nouveau Planning
                            </button>
                            <div id="scheduled-reports">
                                <div class="schedule-item">
                                    <div>
                                        <strong>Rapport Mensuel</strong>
                                        <div class="small text-muted">Chaque 1er du mois à 08:00</div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="schedule-item">
                                    <div>
                                        <strong>KPIs Hebdomadaires</strong>
                                        <div class="small text-muted">Chaque lundi à 09:00</div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <!-- Center Panel: Report Builder -->
        <div class="col-xl-6 col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-hammer me-2"></i>Constructeur de Rapport
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="clearReport()">
                            <i class="fas fa-eraser me-1"></i>Effacer
                        </button>
                        <button class="btn btn-outline-primary" onclick="loadTemplate()">
                            <i class="fas fa-folder-open me-1"></i>Modèle
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Report Configuration -->
                    <div class="filter-panel mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nom du Rapport</label>
                                <input type="text" id="report-name" class="form-control" placeholder="Nom du rapport" value="Rapport Personnalisé">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Période</label>
                                <select id="report-period" class="form-select">
                                    <option value="month">Ce mois</option>
                                    <option value="quarter">Ce trimestre</option>
                                    <option value="year">Cette année</option>
                                    <option value="custom">Personnalisée</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Format</label>
                                <select id="export-format" class="form-select">
                                    <option value="pdf">PDF</option>
                                    <option value="excel">Excel</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Drop Zone for Components -->
                    <div class="drop-zone" id="report-canvas">
                        <div class="text-center text-muted">
                            <i class="fas fa-plus-circle fa-3x mb-3 opacity-50"></i>
                            <h5>Glissez-déposez les composants ici</h5>
                            <p class="mb-0">Construisez votre rapport en glissant les éléments depuis la bibliothèque</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Right Panel: Live Preview -->
        <div class="col-xl-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-eye me-2"></i>Aperçu en temps réel
                    </h6>
                </div>
                <div class="card-body">
                    <div class="preview-panel" id="live-preview">
                        <div class="text-center text-muted">
                            <i class="fas fa-file-alt fa-2x mb-2 opacity-50"></i>
                            <p class="small mb-0">L'aperçu apparaîtra ici</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>Programmer un Rapport
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="schedule-form">
                    <div class="mb-3">
                        <label class="form-label">Nom du rapport programmé</label>
                        <input type="text" class="form-control" id="schedule-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fréquence</label>
                        <select class="form-select" id="schedule-frequency">
                            <option value="daily">Quotidien</option>
                            <option value="weekly">Hebdomadaire</option>
                            <option value="monthly">Mensuel</option>
                            <option value="quarterly">Trimestriel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Heure d'envoi</label>
                        <input type="time" class="form-control" id="schedule-time" value="08:00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destinataires (emails)</label>
                        <textarea class="form-control" id="schedule-recipients" rows="3"
                                  placeholder="admin@etablissement.com, comptable@etablissement.com"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveSchedule()">Programmer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    class ReportBuilder {
        constructor() {
            this.components = [];
            this.reportConfig = {};
            this.initializeDragAndDrop();
            this.initializePredictiveChart();
            this.setupEventListeners();
        }

        initializeDragAndDrop() {
            // Initialize Sortable for component library
            new Sortable(document.getElementById('component-library'), {
                group: {
                    name: 'components',
                    pull: 'clone',
                    put: false
                },
                animation: 150,
                sort: false,
                onEnd: (evt) => {
                    if (evt.to.id === 'report-canvas') {
                        this.addComponent(evt.item.dataset.type);
                        evt.item.remove();
                    }
                }
            });

            // Initialize Sortable for report canvas
            new Sortable(document.getElementById('report-canvas'), {
                group: 'components',
                animation: 150,
                onAdd: (evt) => {
                    this.handleComponentDrop(evt);
                },
                onUpdate: (evt) => {
                    this.updateComponentOrder();
                }
            });
        }

        initializePredictiveChart() {
            const ctx = document.getElementById('prediction-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Prévision Recettes',
                        data: [120000, 135000, 148000, 162000, 175000, 190000],
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        handleComponentDrop(evt) {
            const componentType = evt.item.dataset.type;
            const componentId = 'comp_' + Date.now();

            evt.item.innerHTML = this.generateComponentHTML(componentType, componentId);
            evt.item.className = 'dropped-component';
            evt.item.dataset.id = componentId;

            this.components.push({
                id: componentId,
                type: componentType,
                config: this.getDefaultConfig(componentType)
            });

            this.updateLivePreview();
        }

        generateComponentHTML(type, id) {
            const templates = {
                kpi: `
                    <div class="component-controls">
                        <button class="btn btn-sm btn-outline-primary" onclick="reportBuilder.configureComponent('${id}')">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="reportBuilder.removeComponent('${id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h6><i class="fas fa-tachometer-alt me-2"></i>Indicateur KPI</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 bg-primary text-white rounded text-center">
                                <h4>2,450,000 FCFA</h4>
                                <small>Total Recettes</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-success text-white rounded text-center">
                                <h4>85%</h4>
                                <small>Taux Recouvrement</small>
                            </div>
                        </div>
                    </div>
                `,
                chart: `
                    <div class="component-controls">
                        <button class="btn btn-sm btn-outline-primary" onclick="reportBuilder.configureComponent('${id}')">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="reportBuilder.removeComponent('${id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h6><i class="fas fa-chart-line me-2"></i>Graphique d'Évolution</h6>
                    <canvas id="chart_${id}" width="400" height="200"></canvas>
                `,
                table: `
                    <div class="component-controls">
                        <button class="btn btn-sm btn-outline-primary" onclick="reportBuilder.configureComponent('${id}')">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="reportBuilder.removeComponent('${id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h6><i class="fas fa-table me-2"></i>Tableau de Données</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>KOUAME Jean</td>
                                    <td>150,000 FCFA</td>
                                    <td>01/12/2024</td>
                                    <td><span class="badge bg-success">Payé</span></td>
                                </tr>
                                <tr>
                                    <td>TRAORE Marie</td>
                                    <td>175,000 FCFA</td>
                                    <td>03/12/2024</td>
                                    <td><span class="badge bg-warning">En cours</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `,
                filter: `
                    <div class="component-controls">
                        <button class="btn btn-sm btn-outline-primary" onclick="reportBuilder.configureComponent('${id}')">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="reportBuilder.removeComponent('${id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h6><i class="fas fa-filter me-2"></i>Filtres</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm">
                                <option>Toutes les filières</option>
                                <option>Informatique</option>
                                <option>Commerce</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary btn-sm w-100">Appliquer</button>
                        </div>
                    </div>
                `,
                export: `
                    <div class="component-controls">
                        <button class="btn btn-sm btn-outline-primary" onclick="reportBuilder.configureComponent('${id}')">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="reportBuilder.removeComponent('${id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h6><i class="fas fa-download me-2"></i>Options d'Export</h6>
                    <div class="btn-group w-100">
                        <button class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                        <button class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                        <button class="btn btn-outline-info btn-sm">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                    </div>
                `
            };

            return templates[type] || '<p>Composant inconnu</p>';
        }

        getDefaultConfig(type) {
            const configs = {
                kpi: { metrics: ['recettes', 'recouvrement'], period: 'month' },
                chart: { type: 'line', data: 'monthly_revenue', period: 'year' },
                table: { source: 'paiements', limit: 10, columns: ['etudiant', 'montant', 'date'] },
                filter: { fields: ['filiere', 'date', 'statut'] },
                export: { formats: ['pdf', 'excel', 'csv'] }
            };

            return configs[type] || {};
        }

        updateLivePreview() {
            const preview = document.getElementById('live-preview');
            const reportName = document.getElementById('report-name').value;

            let previewHTML = `
                <div class="text-center mb-3">
                    <h6 class="fw-bold">${reportName}</h6>
                    <small class="text-muted">Aperçu - ${this.components.length} composant(s)</small>
                </div>
            `;

            this.components.forEach(comp => {
                previewHTML += `
                    <div class="border rounded p-2 mb-2" style="font-size: 0.8em;">
                        <strong>${this.getComponentLabel(comp.type)}</strong>
                        <div class="text-muted small">${this.getComponentDescription(comp.type)}</div>
                    </div>
                `;
            });

            if (this.components.length === 0) {
                previewHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-file-alt fa-2x mb-2 opacity-50"></i>
                        <p class="small mb-0">L'aperçu apparaîtra ici</p>
                    </div>
                `;
            }

            preview.innerHTML = previewHTML;
        }

        getComponentLabel(type) {
            const labels = {
                kpi: 'Indicateurs KPI',
                chart: 'Graphique',
                table: 'Tableau',
                filter: 'Filtres',
                export: 'Export'
            };
            return labels[type] || type;
        }

        getComponentDescription(type) {
            const descriptions = {
                kpi: 'Métriques financières principales',
                chart: 'Évolution des données dans le temps',
                table: 'Données détaillées en tableau',
                filter: 'Critères de filtrage des données',
                export: 'Options d\'exportation des données'
            };
            return descriptions[type] || '';
        }

        setupEventListeners() {
            document.getElementById('report-name').addEventListener('input', () => {
                this.updateLivePreview();
            });

            document.getElementById('report-period').addEventListener('change', () => {
                this.updateLivePreview();
            });
        }

        configureComponent(id) {
            // Open configuration modal for component
            debugLog('Configure component:', id);
            // Implementation would open a modal with component-specific settings
        }

        removeComponent(id) {
            const element = document.querySelector(`[data-id="${id}"]`);
            if (element) {
                element.remove();
                this.components = this.components.filter(comp => comp.id !== id);
                this.updateLivePreview();
            }
        }

        updateComponentOrder() {
            // Update components array based on DOM order
            const elements = document.querySelectorAll('#report-canvas .dropped-component');
            const newOrder = [];
            elements.forEach(el => {
                const id = el.dataset.id;
                const component = this.components.find(comp => comp.id === id);
                if (component) {
                    newOrder.push(component);
                }
            });
            this.components = newOrder;
        }

        generateReport() {
            if (this.components.length === 0) {
                alert('Veuillez ajouter au moins un composant au rapport.');
                return;
            }

            const reportData = {
                name: document.getElementById('report-name').value,
                period: document.getElementById('report-period').value,
                format: document.getElementById('export-format').value,
                components: this.components
            };

            // Send to backend for generation
            fetch('{{ route("esbtp.comptabilite.rapports.generer") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(reportData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rapport généré avec succès!');
                    window.open(data.url, '_blank');
                } else {
                    alert('Erreur lors de la génération: ' + data.message);
                }
            })
            .catch(error => {
                debugError('Error:', error);
                alert('Erreur lors de la génération du rapport.');
            });
        }

        exportReport() {
            this.generateReport();
        }

        shareReport() {
            // Implementation for sharing report
            alert('Fonctionnalité de partage en cours de développement.');
        }

        scheduleReport() {
            document.getElementById('schedule-name').value = document.getElementById('report-name').value;
            new bootstrap.Modal(document.getElementById('scheduleModal')).show();
        }
    }

    // Global functions
    function addPredictiveAnalysis(type) {
        debugLog('Adding predictive analysis:', type);
        // Implementation would add AI-powered analytics components
    }

    function openScheduleModal() {
        new bootstrap.Modal(document.getElementById('scheduleModal')).show();
    }

    function saveSchedule() {
        const scheduleData = {
            name: document.getElementById('schedule-name').value,
            frequency: document.getElementById('schedule-frequency').value,
            time: document.getElementById('schedule-time').value,
            recipients: document.getElementById('schedule-recipients').value
        };

        // Send to backend
        fetch('{{ route("esbtp.comptabilite.rapports.schedule") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(scheduleData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rapport programmé avec succès!');
                bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
                // Refresh scheduled reports list
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }

    function clearReport() {
        if (confirm('Êtes-vous sûr de vouloir effacer le rapport?')) {
            document.getElementById('report-canvas').innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-plus-circle fa-3x mb-3 opacity-50"></i>
                    <h5>Glissez-déposez les composants ici</h5>
                    <p class="mb-0">Construisez votre rapport en glissant les éléments depuis la bibliothèque</p>
                </div>
            `;
            reportBuilder.components = [];
            reportBuilder.updateLivePreview();
        }
    }

    function loadTemplate() {
        // Implementation for loading report templates
        alert('Fonctionnalité de modèles en cours de développement.');
    }

    // Initialize Report Builder
    let reportBuilder;
    document.addEventListener('DOMContentLoaded', function() {
        reportBuilder = new ReportBuilder();
    });
</script>
@endsection
