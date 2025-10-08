@extends('layouts.app')

@section('title', 'Suivi des Paiements - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/cursor-fix.css') }}">
<style>
    .btn-acasi.small {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
        border-radius: var(--radius-small);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Suivi des Paiements</h1>
                <p class="header-subtitle">Monitoring des paiements étudiants et relances automatiques</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi secondary" id="paiements-refresh-btn" title="Rafraîchir les données">
                    <i class="fas fa-sync-alt"></i>Rafraîchir
                </button>
                <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="btn-acasi secondary">
                    <i class="fas fa-chart-bar"></i>Suivi par Catégorie
                </a>
                @can('create-paiements')
                <a href="{{ route('esbtp.paiements.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Nouveau Paiement
                </a>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Information année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar me-2"></i>Contexte d'affichage
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}" selected>
                                {{ date('Y') . '-' . (date('Y') + 1) }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les paiements affichés correspondent à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- KPI Cards Harmonisées avec le Système de Catégories -->
        <div id="paiements-metrics-container">
            @include('esbtp.paiements.partials.metrics', ['stats' => $stats])
        </div>

        <!-- Filtres et Actions -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <form action="{{ route('esbtp.paiements.index') }}" method="GET" id="paiements-filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Matricule, nom, n° reçu..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="en_attente" {{ request('status') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="validé" {{ request('status') == 'validé' ? 'selected' : '' }}>Validé</option>
                                <option value="rejeté" {{ request('status') == 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ request('date_debut') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ request('date_fin') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des Paiements -->
        <div id="paiements-table-container"
             data-refresh-url="{{ route('esbtp.paiements.refresh') }}"
             data-last-updated="{{ optional($lastUpdatedAt)->toIso8601String() }}">
            @include('esbtp.paiements.partials.table', ['paiements' => $paiements])
        </div>

        @if(auth()->user()->hasRole('superAdmin'))
        <div id="bulk-actions-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
             background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; padding: 15px 30px;
             border-radius: 50px; box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4); z-index: 1050;
             animation: slideUp 0.3s ease-out;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                    <span id="selected-count" style="font-weight: 600; font-size: 1.1rem;">0</span>
                    <span style="opacity: 0.9;">paiement(s) sélectionné(s)</span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-light btn-sm" onclick="bulkValider()"
                            style="padding: 8px 20px; border-radius: 25px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <i class="fas fa-check-double me-1"></i>Valider la sélection
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="openBulkRejetModal()"
                            style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-times me-1"></i>Rejeter la sélection
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="clearSelection()"
                            style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-times-circle me-1"></i>Annuler
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.table th {
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Styles pour les dropdowns PDF compacts */
.pdf-dropdown .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    min-width: auto;
}

.pdf-dropdown .dropdown-menu {
    min-width: 140px;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item i {
    width: 14px;
    text-align: center;
}

@keyframes slideUp {
    from {
        bottom: -100px;
        opacity: 0;
    }
    to {
        bottom: 20px;
        opacity: 1;
    }
}

</style>

@endpush

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les paiements affichés se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des paiements dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les paiements de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les paiements de 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de rejet groupé -->
@if(auth()->user()->hasRole('superAdmin'))
<div class="modal fade" id="bulkRejetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="bulk-rejet-form" method="POST" action="{{ route('esbtp.paiements.bulk-rejeter') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle text-danger me-2"></i>
                        Rejeter les paiements sélectionnés
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous êtes sur le point de rejeter <strong><span id="bulk-rejet-count">0</span> paiement(s)</strong>.
                    </div>

                    <div class="form-group">
                        <label for="bulk_motif_rejet">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="bulk_motif_rejet" name="motif_rejet" rows="4"
                                  placeholder="Expliquez pourquoi ces paiements sont rejetés..." required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulk_confirmer_rejet" required>
                        <label class="form-check-label" for="bulk_confirmer_rejet">
                            Je confirme le rejet de ces paiements
                        </label>
                    </div>

                    <div id="bulk-selected-paiements"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i>Rejeter les paiements
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modaux de rejet pour les paiements en attente -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

(function () {
    const pollingInterval = 60000;
    let pollingTimer = null;
    let lastUpdatedAt = null;
    let currentUrl = window.location.href;

    function getElements() {
        return {
            filterForm: document.getElementById('paiements-filter-form'),
            tableContainer: document.getElementById('paiements-table-container'),
            metricsContainer: document.getElementById('paiements-metrics-container'),
            refreshButton: document.getElementById('paiements-refresh-btn'),
            filterSubmit: document.querySelector('#paiements-filter-form button[type="submit"]')
        };
    }

    function setLoading(isLoading) {
        const $tableContainer = $('#paiements-table-container');
        const $refreshBtn = $('#paiements-refresh-btn');
        const $submitBtn = $('#paiements-filter-form button[type="submit"]');

        $tableContainer.toggleClass('opacity-50', isLoading);
        if ($refreshBtn.length) {
            $refreshBtn.prop('disabled', isLoading);
        }
        if ($submitBtn.length) {
            $submitBtn.prop('disabled', isLoading);
        }
    }

    function buildIndexUrl() {
        const elements = getElements();
        const form = elements.filterForm;
        const baseUrl = form?.getAttribute('action') || "{{ route('esbtp.paiements.index') }}";
        if (!form) {
            return baseUrl;
        }
        const params = new URLSearchParams(new FormData(form));
        const query = params.toString();
        return query ? `${baseUrl}?${query}` : baseUrl;
    }

    function buildRefreshUrl() {
        const elements = getElements();
        const refreshUrl = elements.tableContainer?.getAttribute('data-refresh-url');
        if (!refreshUrl) {
            return currentUrl;
        }
        const queryIndex = currentUrl.indexOf('?');
        const query = queryIndex >= 0 ? currentUrl.substring(queryIndex + 1) : '';
        return query ? `${refreshUrl}?${query}` : refreshUrl;
    }

    function refreshTooltips() {
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
            $('[data-bs-toggle="tooltip"]').tooltip('dispose').tooltip();
        }
    }

    function restartPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
        }
        pollingTimer = setInterval(function () {
            triggerRefresh(true);
        }, pollingInterval);
    }

    function fetchPaiements(url, { pushState = true, silent = false, skipIfSame = false } = {}) {
        const elements = getElements();
        if (!silent) {
            setLoading(true);
        }

        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des paiements.');
            }
            return response.json();
        })
        .then(data => {
            const incomingTimestamp = data.last_updated_at || null;
            if (skipIfSame && incomingTimestamp && lastUpdatedAt && incomingTimestamp === lastUpdatedAt) {
                return;
            }

            if (data.table && elements.tableContainer) {
                elements.tableContainer.innerHTML = data.table;
                $('#paiements-table-container').data('last-updated', incomingTimestamp);
            }

            if (data.metrics && elements.metricsContainer) {
                elements.metricsContainer.innerHTML = data.metrics;
            }

            if (incomingTimestamp) {
                lastUpdatedAt = incomingTimestamp;
            }

            if (data.url) {
                currentUrl = data.url;
                if (pushState) {
                    window.history.pushState({ url: data.url }, '', data.url);
                }
            } else {
                currentUrl = url;
            }

            refreshTooltips();
            clearSelection();
            updateBulkActionsBar();
            restartPolling();
        })
        .catch(error => {
            console.error(error);
            if (!silent) {
                alert(error.message || 'Impossible de charger les paiements pour le moment.');
            }
        })
        .finally(() => {
            if (!silent) {
                setLoading(false);
            }
        });
    }

    function triggerRefresh(silent = true) {
        const url = buildRefreshUrl();
        fetchPaiements(url, { pushState: false, silent, skipIfSame: silent });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const elements = getElements();
        if (!elements.tableContainer) {
            return;
        }

        const $tableContainer = $('#paiements-table-container');
        lastUpdatedAt = $tableContainer.data('last-updated') || null;

        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        // Modal handlers for year change
        $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
            $('#yearChangeModal').modal('hide');
        });
        $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
            $('#yearChangeModal').modal('hide');
        });

        $('#paiements-refresh-btn').on('click', function () {
            triggerRefresh(false);
        });

        $('#paiements-filter-form').on('submit', function (event) {
            event.preventDefault();
            const url = buildIndexUrl();
            fetchPaiements(url, { pushState: true, skipIfSame: false });
        });

        $('#paiements-filter-form').find('select,input[type="date"]').on('change', function () {
            $('#paiements-filter-form').trigger('submit');
        });

        $(document).on('click', '#paiements-table-container .pagination a', function (event) {
            event.preventDefault();
            const href = $(this).attr('href');
            if (href) {
                fetchPaiements(href, { pushState: true, skipIfSame: false });
            }
        });

        $(document).on('change', '#select-all', function () {
            const isChecked = $(this).prop('checked');
            $('.paiement-checkbox').prop('checked', isChecked);
            updateBulkActionsBar();
        });

        $(document).on('change', '.paiement-checkbox', function () {
            updateBulkActionsBar();
            const totalCheckboxes = $('.paiement-checkbox').length;
            const checkedCheckboxes = $('.paiement-checkbox:checked').length;
            $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
        });

        window.addEventListener('popstate', function (event) {
            const targetUrl = event.state?.url || window.location.href;
            fetchPaiements(targetUrl, { pushState: false, skipIfSame: false });
        });

        restartPolling();
    });

    window.triggerRefreshPaiements = triggerRefresh;
})();

function updateBulkActionsBar() {
    const $bar = $('#bulk-actions-bar');
    if (!$bar.length) {
        return;
    }

    const count = $('.paiement-checkbox:checked').length;

    if (count > 0) {
        $bar.stop(true, true).slideDown(200);
        $('#selected-count').text(count);
    } else {
        $bar.stop(true, true).slideUp(200);
    }
}

function bulkValider() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        alert('Veuillez sélectionner au moins un paiement.');
        return;
    }

    const confirmMessage = `Êtes-vous sûr de vouloir valider ${selectedIds.length} paiement(s) ?`;

    if (!confirm(confirmMessage)) {
        return;
    }

    const form = $('<form>', {
        method: 'POST',
        action: "{{ route('esbtp.paiements.bulk-valider') }}"
    });

    form.append($('<input>', {
        type: 'hidden',
        name: '_token',
        value: "{{ csrf_token() }}"
    }));

    selectedIds.forEach(function(id) {
        form.append($('<input>', {
            type: 'hidden',
            name: 'paiements[]',
            value: id
        }));
    });

    $('body').append(form);
    form.submit();
}

function openBulkRejetModal() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        alert('Veuillez sélectionner au moins un paiement.');
        return;
    }

    $('#bulk-rejet-count').text(selectedIds.length);

    const container = $('#bulk-selected-paiements');
    container.empty();

    selectedIds.forEach(function(id) {
        container.append($('<input>', {
            type: 'hidden',
            name: 'paiements[]',
            value: id
        }));
    });

    $('#bulk_motif_rejet').val('');
    $('#bulk_confirmer_rejet').prop('checked', false);

    $('#bulkRejetModal').modal('show');
}

function clearSelection() {
    $('.paiement-checkbox').prop('checked', false);
    $('#select-all').prop('checked', false);
    updateBulkActionsBar();
}

function getSelectedPaiementIds() {
    const ids = [];
    $('.paiement-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}
</script>
