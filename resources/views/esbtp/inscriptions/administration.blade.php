@extends('layouts.app')

@section('title', 'Administration des Inscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .kpi-card {
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
    }

    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(4, 83, 203, 0.08);
        color: var(--primary);
    }

    .notification-card {
        border-left: 4px solid;
        padding: var(--space-md);
        border-radius: var(--radius-small);
        background: rgba(245, 158, 11, 0.05);
        border-color: var(--warning);
    }

    .inscription-actions-wrapper {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .inscription-actions-spinner {
        display: none;
        min-width: 32px;
    }

    .inscription-actions-wrapper.is-loading .inscription-actions-buttons {
        display: none !important;
    }

    .inscription-actions-wrapper.is-loading .inscription-actions-spinner {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    tr[data-inscription-id].is-loading {
        opacity: 0.75;
    }

    tr[data-inscription-id] {
        position: relative;
        overflow: hidden;
    }

    .inscription-row-highlight {
        position: absolute;
        top: 0;
        left: -80%;
        width: 160%;
        height: 100%;
        opacity: 0;
        pointer-events: none;
        transform: translateX(-65%) skewX(-12deg);
        background: linear-gradient(90deg, rgba(40, 167, 69, 0) 0%, rgba(40, 167, 69, 0.75) 50%, rgba(40, 167, 69, 0) 100%);
        transition: opacity 0.2s ease;
        z-index: 5;
    }

    .inscription-row-highlight.reject {
        background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.75) 50%, rgba(220, 53, 69, 0) 100%);
    }

    .inscription-row-highlight.animate {
        animation: inscription-row-highlight-move 3.2s ease-out forwards;
    }

    .inscription-row-flash {
        animation: inscription-row-flash 0.8s ease-in-out;
    }

    .inscription-row-flash.reject {
        animation-name: inscription-row-flash-reject;
    }

    @keyframes inscription-row-highlight-move {
        0% {
            opacity: 0;
            transform: translateX(-65%) skewX(-12deg);
        }
        18% {
            opacity: 0.92;
        }
        55% {
            opacity: 0.7;
        }
        100% {
            opacity: 0;
            transform: translateX(115%) skewX(-12deg);
        }
    }

    @keyframes inscription-row-flash {
        0% {
            background-color: transparent;
        }
        25% {
            background-color: rgba(40, 167, 69, 0.12);
        }
        100% {
            background-color: transparent;
        }
    }

    @keyframes inscription-row-flash-reject {
        0% {
            background-color: transparent;
        }
        25% {
            background-color: rgba(220, 53, 69, 0.12);
        }
        100% {
            background-color: transparent;
        }
    }

    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            text-align: center;
            gap: var(--space-md);
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-check me-2"></i>Administration des Inscriptions</h1>
                <p class="header-subtitle">Gestion et validation des inscriptions en attente</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" placeholder="Rechercher une inscription..." value="{{ request('search') }}">
                <span class="badge rounded-pill bg-light text-dark me-2">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $anneeEnCours->name ?? 'Année non définie' }}
                </span>
                <span class="text-muted me-2">{{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}</span>
            </div>
        </div>

        <div class="p-lg">
            <!-- Statistiques -->
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Total en attente</div>
                                <div class="h4 mb-1">{{ $stats['total_en_attente'] }}</div>
                                <div class="small text-muted">Toutes les demandes</div>
                            </div>
                            <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Avec paiement</div>
                                <div class="h4 mb-1">{{ $stats['avec_paiement'] }}</div>
                                <div class="small text-muted">Payés ou en attente</div>
                            </div>
                            <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                                <i class="fas fa-credit-card"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Sans paiement</div>
                                <div class="h4 mb-1">{{ $stats['sans_paiement'] }}</div>
                                <div class="small text-muted">Nécessitent un règlement</div>
                            </div>
                            <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Prospects</div>
                                <div class="h4 mb-1">{{ $stats['prospects'] }}</div>
                                <div class="small text-muted">Étape initiale</div>
                            </div>
                            <div class="kpi-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent-blue);">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres de recherche -->
            <div class="card-moderne mb-4">
                <div class="p-lg">
                    <div class="section-title mb-md">
                        <i class="fas fa-filter"></i>Filtrer les inscriptions en attente
                    </div>
                    <form method="GET" action="{{ route('esbtp.inscriptions.administration') }}" id="inscriptions-admin-filter-form">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="filter-search" class="form-label">
                                    <i class="fas fa-search me-1"></i>Recherche par nom ou matricule
                                </label>
                                <input type="text" class="form-control" id="filter-search" name="search" value="{{ request('search') }}" placeholder="Tapez pour rechercher...">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="filiere" class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Filière
                                </label>
                                <select class="form-select" id="filiere" name="filiere">
                                    <option value="">Toutes les filières</option>
                                    @foreach($filieres as $fil)
                                        <option value="{{ $fil->id }}" {{ request('filiere') == $fil->id ? 'selected' : '' }}>
                                            {{ $fil->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="niveau" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>Niveau d'études
                                </label>
                                <select class="form-select" id="niveau" name="niveau">
                                    <option value="">Tous les niveaux</option>
                                    @foreach($niveaux as $niv)
                                        <option value="{{ $niv->id }}" {{ request('niveau') == $niv->id ? 'selected' : '' }}>
                                            {{ $niv->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="workflow_step" class="form-label">
                                    <i class="fas fa-tasks me-1"></i>Étape du workflow
                                </label>
                                <select class="form-select" id="workflow_step" name="workflow_step">
                                    <option value="">Toutes les étapes</option>
                                    <option value="prospect" {{ request('workflow_step') == 'prospect' ? 'selected' : '' }}>Prospect</option>
                                    <option value="documents_complets" {{ request('workflow_step') == 'documents_complets' ? 'selected' : '' }}>Documents complets</option>
                                    <option value="en_validation" {{ request('workflow_step') == 'en_validation' ? 'selected' : '' }}>En validation</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="has_payment" class="form-label">
                                    <i class="fas fa-credit-card me-1"></i>Statut paiement
                                </label>
                                <select class="form-select" id="has_payment" name="has_payment">
                                    <option value="">Tous</option>
                                    <option value="yes" {{ request('has_payment') == 'yes' ? 'selected' : '' }}>Avec paiement</option>
                                    <option value="no" {{ request('has_payment') == 'no' ? 'selected' : '' }}>Sans paiement</option>
                                </select>
                            </div>
                            <div class="col-md-1 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn-acasi primary w-100">
                                    <i class="fas fa-search"></i>Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des inscriptions -->
            <div class="card-moderne">
                <div class="p-lg">
                    <div class="section-title mb-md">
                        <i class="fas fa-list"></i>Inscriptions en attente de validation ({{ $inscriptions->total() }})
                    </div>
                    <div id="inscriptions-admin-results">
                        @include('esbtp.inscriptions.partials.administration-results', ['inscriptions' => $inscriptions])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour associer un paiement -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="fas fa-credit-card me-2"></i>Associer un paiement à l'inscription
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="montant" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Montant payé <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="montant" name="montant" min="0" step="0.01" required placeholder="Entrez le montant...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fee_category_id" class="form-label">
                                    <i class="fas fa-tags me-1"></i>Catégorie de frais <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="fee_category_id" name="fee_category_id" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    @if(isset($categoriesfrais))
                                        @foreach($categoriesfrais as $categorie)
                                            <option value="{{ $categorie->id }}">{{ $categorie->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mode_paiement" class="form-label">
                                    <i class="fas fa-credit-card me-1"></i>Mode de paiement <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                                    <option value="">Sélectionnez un mode</option>
                                    <option value="especes">Espèces</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reference_paiement" class="form-label">
                                    <i class="fas fa-hashtag me-1"></i>Référence du paiement
                                </label>
                                <input type="text" class="form-control" id="reference_paiement" name="reference_paiement" placeholder="Numéro de chèque, référence virement...">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_paiement" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Date du paiement <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="observations" class="form-label">
                                    <i class="fas fa-comment me-1"></i>Observations
                                </label>
                                <textarea class="form-control" id="observations" name="observations" rows="3" placeholder="Commentaires sur le paiement..."></textarea>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="auto_validate_inscription" name="auto_validate_inscription" value="0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="validate_payment" name="validate_payment">
                        <label class="form-check-label" for="validate_payment">
                            Valider le paiement immédiatement
                        </label>
                        <div class="text-muted small">Requis si vous souhaitez valider l'inscription dans la foulée.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Associer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour validation définitive -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Validation définitive de l'inscription
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="validationForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Cette action va convertir le prospect en étudiant et activer son compte utilisateur.
                    </div>
                    <div class="mb-3">
                        <label for="validation_observations" class="form-label">Observations</label>
                        <textarea class="form-control" id="validation_observations" name="observations" rows="3" placeholder="Commentaires sur la validation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i>Valider définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour annulation -->
<div class="modal fade" id="cancelInscriptionModal" tabindex="-1" aria-labelledby="cancelInscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelInscriptionModalLabel">
                    <i class="fas fa-times-circle me-2"></i>Annuler l'inscription
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelInscriptionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cette action annule l'inscription et retire l'étudiant du workflow en cours.
                    </div>
                    <div class="mb-3">
                        <label for="cancel_motif" class="form-label">Motif d'annulation <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancel_motif" name="motif" rows="3" placeholder="Raison de l'annulation..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Annuler l'inscription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const ADMIN_REFRESH_CONTEXT = 'administration';

    function setInscriptionRowLoadingState(inscriptionId, isLoading) {
        const row = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
        if (!row) {
            return;
        }

        row.classList.toggle('is-loading', Boolean(isLoading));

        const actionsWrapper = row.querySelector('.inscription-actions-wrapper');
        if (actionsWrapper) {
            actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
    }

    function refreshInscriptionLigne(inscriptionId, actionType = 'update') {
        setInscriptionRowLoadingState(inscriptionId, true);

        fetch(`/esbtp/inscriptions/${inscriptionId}/refresh-ligne?context=${ADMIN_REFRESH_CONTEXT}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du rafraîchissement de la ligne.');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse invalide.');
            }

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const newRow = template.content.querySelector('tr');
            if (!newRow) {
                throw new Error('HTML de ligne invalide.');
            }

            const existingRow = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
            if (existingRow) {
                existingRow.replaceWith(newRow);
            }

            triggerInscriptionRowHighlight(newRow, actionType);
            bindInscriptionActions();
        })
        .catch(error => {
            debugError(error);
            alert(error.message || 'Erreur lors de la mise à jour.');
        })
        .finally(() => setInscriptionRowLoadingState(inscriptionId, false));
    }

    window.openPaymentModal = function openPaymentModal(inscriptionId, options = {}) {
        const form = document.getElementById('paymentForm');
        const modalElement = document.getElementById('paymentModal');
        const autoValidateInput = document.getElementById('auto_validate_inscription');
        const validatePaymentCheckbox = document.getElementById('validate_payment');

        if (!form || !modalElement) {
            return;
        }

        if (typeof bootstrap === 'undefined') {
            alert('Erreur: Bootstrap n\'est pas chargé. Veuillez recharger la page.');
            return;
        }

        form.action = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;
        form.reset();

        if (autoValidateInput) {
            autoValidateInput.value = options.autoValidate ? '1' : '0';
        }

        if (validatePaymentCheckbox) {
            validatePaymentCheckbox.checked = Boolean(options.autoValidate);
        }

        const dateInput = document.getElementById('date_paiement');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }

        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modal.show();
    };

    window.openCancelModal = function openCancelModal(inscriptionId) {
        const form = document.getElementById('cancelInscriptionForm');
        const modalElement = document.getElementById('cancelInscriptionModal');

        if (!confirm('Confirmer l\'annulation de cette inscription ?')) {
            return;
        }

        if (!form || !modalElement) {
            return;
        }

        if (typeof bootstrap === 'undefined') {
            alert('Erreur: Bootstrap n\'est pas chargé. Veuillez recharger la page.');
            return;
        }

        form.action = `/esbtp/inscriptions/${inscriptionId}/annuler`;
        form.reset();

        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modal.show();
    };

    function handleInscriptionValidation(inscriptionId, hasPayment) {
        if (!hasPayment) {
            openPaymentModal(inscriptionId, { autoValidate: true });
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('inscription_ids[]', inscriptionId);

        setInscriptionRowLoadingState(inscriptionId, true);

        fetch("{{ route('esbtp.inscriptions.bulk-valider') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la validation.');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                highlightInscriptionRow(inscriptionId, 'reject');
                throw new Error(data.message || 'Validation échouée.');
            }

            if (data.message) {
                alert(data.message);
            }

            refreshInscriptionLigne(inscriptionId, 'validate');
        })
        .catch(error => {
            highlightInscriptionRow(inscriptionId, 'reject');
            alert(error.message || 'Erreur lors de la validation.');
        })
        .finally(() => setInscriptionRowLoadingState(inscriptionId, false));
    }

    function triggerInscriptionRowHighlight(row, actionType = 'update') {
        if (!row) {
            return;
        }

        const isReject = ['reject', 'cancel', 'danger'].includes(actionType);

        row.classList.remove('inscription-row-flash', 'reject');
        void row.offsetWidth;

        const highlight = document.createElement('div');
        highlight.className = 'inscription-row-highlight';
        if (isReject) {
            highlight.classList.add('reject');
        }

        row.appendChild(highlight);

        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });

        highlight.addEventListener('animationend', () => {
            highlight.remove();
        });

        row.classList.add('inscription-row-flash');
        if (isReject) {
            row.classList.add('reject');
        }

        setTimeout(() => {
            row.classList.remove('inscription-row-flash', 'reject');
        }, 1200);
    }

    function highlightInscriptionRow(inscriptionId, actionType = 'update') {
        const row = document.querySelector(`tr[data-inscription-id="${inscriptionId}"]`);
        triggerInscriptionRowHighlight(row, actionType);
    }

    function bindInscriptionActions(context = document) {
        context.querySelectorAll('.validate-inscription-btn').forEach(button => {
            button.addEventListener('click', function () {
                const inscriptionId = this.dataset.inscriptionId;
                const hasPayment = this.dataset.hasPayment === '1';
                handleInscriptionValidation(inscriptionId, hasPayment);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('inscriptions-admin-filter-form');
        const resultsContainer = document.getElementById('inscriptions-admin-results');
        const submitButton = form ? form.querySelector('button[type="submit"]') : null;
        const filterSelects = form ? form.querySelectorAll('select') : [];
        const headerSearch = document.querySelector('.dashboard-header .search-bar');
        const formSearchInput = form ? form.querySelector('#filter-search') : null;

        bindInscriptionActions();
        bindPaginationLinks();

        if (headerSearch && formSearchInput) {
            headerSearch.value = headerSearch.value || formSearchInput.value || '';
            headerSearch.addEventListener('input', function () {
                formSearchInput.value = headerSearch.value;
            });

            headerSearch.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    formSearchInput.value = headerSearch.value;
                    submitFilterForm();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitFilterForm();
            });
        }

        filterSelects.forEach(select => {
            select.addEventListener('change', submitFilterForm);
        });

        function submitFilterForm() {
            if (!form) {
                return;
            }

            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;
            fetchResults(targetUrl, { pushState: true });
        }

        function bindPaginationLinks() {
            if (!resultsContainer) {
                return;
            }

            resultsContainer.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchResults(this.href, { pushState: true });
                });
            });
        }

        function setLoading(isLoading) {
            if (submitButton) {
                submitButton.disabled = isLoading;
            }

            if (resultsContainer) {
                resultsContainer.classList.toggle('opacity-50', Boolean(isLoading));
            }
        }

        function fetchResults(url, options = {}) {
            if (!url || !resultsContainer) {
                return;
            }

            setLoading(true);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des inscriptions.');
                }
                return response.json();
            })
            .then(data => {
                resultsContainer.innerHTML = data.html;
                if (options.pushState !== false) {
                    window.history.pushState({ url: data.url }, '', data.url);
                }
                bindInscriptionActions(resultsContainer);
                bindPaginationLinks();
            })
            .catch(error => {
                debugError(error);
                alert('Impossible de charger les inscriptions. Veuillez réessayer.');
            })
            .finally(() => setLoading(false));
        }

        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        window.addEventListener('popstate', function (event) {
            const targetUrl = event.state?.url || window.location.href;
            fetchResults(targetUrl, { pushState: false });
        });

        const paymentForm = document.getElementById('paymentForm');
        if (paymentForm) {
            const validatePaymentCheckbox = paymentForm.querySelector('#validate_payment');
            const autoValidateInput = paymentForm.querySelector('#auto_validate_inscription');

            if (validatePaymentCheckbox && autoValidateInput) {
                validatePaymentCheckbox.addEventListener('change', function () {
                    autoValidateInput.value = this.checked ? '1' : '0';
                });
            }

            paymentForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';

                const formData = new FormData(this);
                const actionUrl = this.action;
                const inscriptionId = actionUrl.split('/').slice(-2, -1)[0];
                const autoValidate = formData.get('auto_validate_inscription') === '1';

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        refreshInscriptionLigne(inscriptionId, autoValidate ? 'validate' : 'update');
                        if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        highlightInscriptionRow(inscriptionId, 'reject');
                        alert(data.message || 'Erreur lors de la création du paiement');
                    }
                })
                .catch(error => {
                    debugError(error);
                    highlightInscriptionRow(inscriptionId, 'reject');
                    alert('Erreur lors de la création du paiement');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }

        const cancelForm = document.getElementById('cancelInscriptionForm');
        if (cancelForm) {
            cancelForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';

                const formData = new FormData(this);
                const actionUrl = this.action;
                const inscriptionId = actionUrl.split('/').slice(-2, -1)[0];

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('cancelInscriptionModal')).hide();
                        highlightInscriptionRow(inscriptionId, 'reject');
                        setTimeout(() => {
                            fetchResults(window.location.href, { pushState: false });
                        }, 500);
                        if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        highlightInscriptionRow(inscriptionId, 'reject');
                        alert(data.message || 'Erreur lors de l\'annulation');
                    }
                })
                .catch(error => {
                    debugError(error);
                    highlightInscriptionRow(inscriptionId, 'reject');
                    alert('Erreur lors de l\'annulation');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }
    });
</script>
@endsection
