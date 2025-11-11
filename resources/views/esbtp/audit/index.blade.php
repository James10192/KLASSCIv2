@extends('layouts.app')

@section('title', 'Audit Trail - Sécurité')

@section('content')
<div class="container-fluid">
    <!-- En-tête avec statistiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-shield-alt text-primary"></i>
                        Audit Trail & Sécurité
                    </h2>
                    <p class="text-muted mb-0">Surveillance et traçabilité des actions système</p>
                </div>
                <div class="d-flex gap-2">
                    @can('security.audit.export')
                        <button type="button" class="btn btn-outline-success" id="exportExcelBtn">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="exportPdfBtn">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    @endcan
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filtersModal">
                        <i class="fas fa-filter"></i> Filtres Avancés
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques d'audit -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['total_audits']) }}</h4>
                            <p class="mb-0">Total Audits</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-database fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['today_audits']) }}</h4>
                            <p class="mb-0">Aujourd'hui</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['financial_audits']) }}</h4>
                            <p class="mb-0">Audits Financiers</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['critical_events']) }}</h4>
                            <p class="mb-0">Événements Critiques</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres rapides -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="quickFiltersForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="quickSearch" class="form-label">Recherche rapide</label>
                            <input type="text" class="form-control" id="quickSearch" placeholder="ID, IP, référence...">
                        </div>
                        <div class="col-md-2">
                            <label for="quickEvent" class="form-label">Événement</label>
                            <select class="form-select" id="quickEvent">
                                <option value="">Tous</option>
                                <option value="created">Création</option>
                                <option value="updated">Modification</option>
                                <option value="deleted">Suppression</option>
                                <option value="restored">Restauration</option>
                                <option value="retrieved">Consultation</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="quickModel" class="form-label">Type de modèle</label>
                            <select class="form-select" id="quickModel">
                                <option value="">Tous</option>
                                @foreach($auditableModels as $class => $name)
                                    <option value="{{ $class }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="quickDateFrom" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="quickDateFrom" value="{{ now()->subDays(7)->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Table des audits -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i>
                        Logs d'Audit
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        @can('security.users.monitor')
                            <a href="{{ route('esbtp.audit.user-activity') }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-users"></i> Activité Utilisateurs
                            </a>
                        @endcan
                        @can('comptabilite.audit.view')
                            <a href="{{ route('esbtp.audit.comptabilite') }}" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-calculator"></i> Audit Comptabilité
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <!-- Loading indicator -->
                    <div id="loadingIndicator" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement des données d'audit...</p>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive" id="auditTableContainer">
                        <table class="table table-hover table-striped" id="auditTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Événement</th>
                                    <th>Modèle</th>
                                    <th>ID Entité</th>
                                    <th>Utilisateur</th>
                                    <th>IP</th>
                                    <th>Navigateur</th>
                                    <th>Date/Heure</th>
                                    <th>Risque</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="auditTableBody">
                                <!-- Les données seront chargées via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div id="paginationContainer" class="d-flex justify-content-center mt-3">
                        <!-- La pagination sera générée via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Filtres Avancés -->
<div class="modal fade" id="filtersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-filter"></i>
                    Filtres Avancés
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="advancedFiltersForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="advancedUser" class="form-label">Utilisateur</label>
                            <select class="form-select" id="advancedUser">
                                <option value="">Tous les utilisateurs</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="advancedRisk" class="form-label">Niveau de risque</label>
                            <select class="form-select" id="advancedRisk">
                                <option value="">Tous</option>
                                <option value="Critique">Critique</option>
                                <option value="Élevé">Élevé</option>
                                <option value="Moyen">Moyen</option>
                                <option value="Faible">Faible</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="advancedDateFrom" class="form-label">Date de début</label>
                            <input type="datetime-local" class="form-control" id="advancedDateFrom">
                        </div>
                        <div class="col-md-6">
                            <label for="advancedDateTo" class="form-label">Date de fin</label>
                            <input type="datetime-local" class="form-control" id="advancedDateTo">
                        </div>
                        <div class="col-md-6">
                            <label for="advancedIP" class="form-label">Adresse IP</label>
                            <input type="text" class="form-control" id="advancedIP" placeholder="192.168.1.1">
                        </div>
                        <div class="col-md-6">
                            <label for="advancedBrowser" class="form-label">Navigateur</label>
                            <select class="form-select" id="advancedBrowser">
                                <option value="">Tous</option>
                                <option value="Chrome">Chrome</option>
                                <option value="Firefox">Firefox</option>
                                <option value="Safari">Safari</option>
                                <option value="Edge">Edge</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning" id="resetFiltersBtn">
                    <i class="fas fa-undo"></i> Réinitialiser
                </button>
                <button type="button" class="btn btn-primary" id="applyAdvancedFiltersBtn">
                    <i class="fas fa-check"></i> Appliquer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détails Audit -->
<div class="modal fade" id="auditDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i>
                    Détails de l'Audit
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="auditDetailsContent">
                <!-- Le contenu sera chargé via AJAX -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.risk-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.risk-critique { background-color: #dc3545; }
.risk-eleve { background-color: #fd7e14; }
.risk-moyen { background-color: #ffc107; color: #000; }
.risk-faible { background-color: #198754; }

.audit-changes {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,.075);
}

.stats-card {
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-2px);
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentFilters = {};

    // Charger les données initiales
    loadAuditData();

    // Gestionnaire de soumission des filtres rapides
    $('#quickFiltersForm').on('submit', function(e) {
        e.preventDefault();

        currentFilters = {
            search: $('#quickSearch').val(),
            event: $('#quickEvent').val(),
            model_type: $('#quickModel').val(),
            date_from: $('#quickDateFrom').val(),
            date_to: $('#quickDateTo').val() || new Date().toISOString().split('T')[0]
        };

        currentPage = 1;
        loadAuditData();
    });

    // Gestionnaire des filtres avancés
    $('#applyAdvancedFiltersBtn').on('click', function() {
        currentFilters = {
            ...currentFilters,
            user_id: $('#advancedUser').val(),
            risk_level: $('#advancedRisk').val(),
            date_from: $('#advancedDateFrom').val(),
            date_to: $('#advancedDateTo').val(),
            ip_address: $('#advancedIP').val(),
            browser: $('#advancedBrowser').val()
        };

        currentPage = 1;
        loadAuditData();
        $('#filtersModal').modal('hide');
    });

    // Réinitialiser les filtres
    $('#resetFiltersBtn').on('click', function() {
        $('#quickFiltersForm')[0].reset();
        $('#advancedFiltersForm')[0].reset();
        currentFilters = {};
        currentPage = 1;
        loadAuditData();
    });

    // Actualiser
    $('#refreshBtn').on('click', function() {
        loadAuditData();
    });

    // Export Excel
    $('#exportExcelBtn').on('click', function() {
        exportData('excel');
    });

    // Export PDF
    $('#exportPdfBtn').on('click', function() {
        exportData('pdf');
    });

    // Fonction pour charger les données d'audit
    function loadAuditData() {
        $('#loadingIndicator').show();
        $('#auditTableContainer').hide();

        const params = {
            page: currentPage,
            ...currentFilters
        };

        $.ajax({
            url: '{{ route("esbtp.audit.data") }}',
            method: 'GET',
            data: params,
            success: function(response) {
                renderAuditTable(response.data);
                renderPagination(response);
                $('#loadingIndicator').hide();
                $('#auditTableContainer').show();
            },
            error: function(xhr) {
                debugError('Erreur lors du chargement des données:', xhr);
                $('#loadingIndicator').hide();
                $('#auditTableContainer').show();

                toastr.error('Erreur lors du chargement des données d\'audit');
            }
        });
    }

    // Fonction pour rendre la table
    function renderAuditTable(audits) {
        const tbody = $('#auditTableBody');
        tbody.empty();

        if (audits.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="fas fa-search fa-2x mb-2"></i><br>
                        Aucun audit trouvé avec les critères sélectionnés
                    </td>
                </tr>
            `);
            return;
        }

        audits.forEach(function(audit) {
            const riskClass = getRiskClass(audit.risk_level);
            const changesPreview = getChangesPreview(audit.changes);

            tbody.append(`
                <tr>
                    <td><span class="badge bg-secondary">#${audit.id}</span></td>
                    <td><span class="badge bg-info">${audit.event}</span></td>
                    <td><small class="text-muted">${audit.auditable_type}</small></td>
                    <td><code>${audit.auditable_id}</code></td>
                    <td>${audit.user}</td>
                    <td><code class="small">${audit.ip_address}</code></td>
                    <td><small>${audit.user_agent}</small></td>
                    <td><small>${audit.created_at}</small></td>
                    <td><span class="badge risk-badge ${riskClass}">${audit.risk_level}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewAuditDetails(${audit.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Fonction pour rendre la pagination
    function renderPagination(response) {
        const container = $('#paginationContainer');
        container.empty();

        if (response.last_page > 1) {
            let pagination = '<nav><ul class="pagination">';

            // Bouton précédent
            if (response.current_page > 1) {
                pagination += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${response.current_page - 1})">Précédent</a></li>`;
            }

            // Pages
            for (let i = Math.max(1, response.current_page - 2); i <= Math.min(response.last_page, response.current_page + 2); i++) {
                const active = i === response.current_page ? 'active' : '';
                pagination += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
            }

            // Bouton suivant
            if (response.current_page < response.last_page) {
                pagination += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${response.current_page + 1})">Suivant</a></li>`;
            }

            pagination += '</ul></nav>';
            container.html(pagination);
        }
    }

    // Fonction pour obtenir la classe CSS du niveau de risque
    function getRiskClass(riskLevel) {
        switch(riskLevel) {
            case 'Critique': return 'risk-critique';
            case 'Élevé': return 'risk-eleve';
            case 'Moyen': return 'risk-moyen';
            case 'Faible': return 'risk-faible';
            default: return 'bg-secondary';
        }
    }

    // Fonction pour obtenir un aperçu des changements
    function getChangesPreview(changes) {
        if (!changes || changes.length === 0) {
            return '<small class="text-muted">Aucun changement</small>';
        }

        const count = changes.length;
        const firstChange = changes[0];

        if (count === 1) {
            return `<small>${firstChange.field}: ${firstChange.old} → ${firstChange.new}</small>`;
        } else {
            return `<small>${firstChange.field}: ${firstChange.old} → ${firstChange.new} <em>(+${count-1} autres)</em></small>`;
        }
    }

    // Fonction pour changer de page
    window.changePage = function(page) {
        currentPage = page;
        loadAuditData();
    };

    // Fonction pour voir les détails d'un audit
    window.viewAuditDetails = function(auditId) {
        $.ajax({
            url: `/esbtp/audit/${auditId}`,
            method: 'GET',
            success: function(response) {
                $('#auditDetailsContent').html(response);
                $('#auditDetailsModal').modal('show');
            },
            error: function(xhr) {
                toastr.error('Erreur lors du chargement des détails');
            }
        });
    };

    // Fonction d'export
    function exportData(format) {
        const params = new URLSearchParams(currentFilters);
        const url = format === 'excel'
            ? '{{ route("esbtp.audit.export.excel") }}'
            : '{{ route("esbtp.audit.export.pdf") }}';

        window.open(`${url}?${params.toString()}`, '_blank');
    }
});
</script>
@endpush
