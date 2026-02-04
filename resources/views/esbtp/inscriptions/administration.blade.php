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

    .action-btn {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
        background: #f8fafc;
        color: #1f2937;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.18);
    }

    .action-view {
        background: #e0f2fe;
        color: #0369a1;
    }

    .action-validate {
        background: #dcfce7;
        color: #15803d;
    }

    .action-payment {
        background: #fef3c7;
        color: #b45309;
    }

    .action-cancel {
        background: #fee2e2;
        color: #b91c1c;
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

    .year-selector {
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #374151;
    }

    /* Styles pour les modaux KLASSCI */
    .klassci-payment-modal .modal-content {
        border: 2px solid rgba(99, 102, 241, 0.25);
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.3), 0 8px 16px rgba(99, 102, 241, 0.15);
        overflow: hidden;
    }

    .klassci-payment-modal .modal-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #ffffff;
        border-bottom: none;
        padding: 20px 24px;
        border-radius: 14px 14px 0 0;
    }

    .klassci-payment-modal .modal-header .modal-title {
        font-weight: 600;
        font-size: 1.125rem;
        display: flex;
        align-items: center;
    }

    .klassci-payment-modal .modal-header .modal-title i {
        font-size: 1.25rem;
    }

    .klassci-payment-modal .btn-close {
        filter: invert(1) brightness(2);
        opacity: 0.9;
        transition: opacity 0.2s;
    }

    .klassci-payment-modal .btn-close:hover {
        opacity: 1;
    }

    .klassci-payment-modal .modal-body {
        background: #ffffff;
        padding: 24px;
    }

    .klassci-payment-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid rgba(99, 102, 241, 0.15);
        padding: 16px 24px;
    }

    .klassci-payment-modal .modal-footer .btn {
        border-radius: 8px;
        font-weight: 600;
        padding: 10px 20px;
    }

    .klassci-payment-modal .modal-footer .btn-primary {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border: none;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .klassci-payment-modal .modal-footer .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    }

    .klassci-payment-modal .form-control:focus,
    .klassci-payment-modal .form-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15);
    }

    .klassci-payment-modal .alert {
        border-radius: 10px;
        border: none;
    }

    .klassci-payment-modal .form-label.fw-bold {
        color: #334155;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .klassci-payment-modal .form-control[readonly] {
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
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

        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar-alt me-2"></i>Année Académique Active
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeEnCours->id ?? '' }}" selected>
                                {{ $anneeEnCours->name ?? 'Aucune année définie' }} (Année en cours)
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
                        Les inscriptions affichées correspondent à l'année académique courante.
                        @if($inscriptions->isEmpty())
                            <strong class="text-warning">Aucune inscription trouvée pour cette année.</strong>
                        @endif
                    </small>
                </div>
                @if($inscriptions->isEmpty())
                    <div class="mt-3">
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Aucune inscription pour l'année {{ $anneeEnCours->name ?? 'courante' }}</strong><br>
                                <small>Il y a {{ \App\Models\ESBTPInscription::count() }} inscriptions au total dans la base, mais aucune pour l'année académique active.
                                Utilisez le bouton "Changer d'année" pour consulter les inscriptions d'autres années.</small>
                            </div>
                        </div>
                    </div>
                @endif
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
                                            {{ $fil->name ?? $fil->nom }}
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
                                            {{ $niv->name ?? $niv->libelle ?? $niv->nom }}
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

@if(auth()->user()->hasRole('superAdmin'))
<div id="bulk-actions-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
     background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; padding: 15px 30px;
     border-radius: 50px; box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4); z-index: 1050;
     animation: slideUp 0.3s ease-out;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
            <span id="selected-count" style="font-weight: 600; font-size: 1.1rem;">0</span>
            <span style="opacity: 0.9;">inscription(s) sélectionnée(s)</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-light btn-sm" onclick="openBulkValidationModal()"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <i class="fas fa-check-double me-1"></i>Valider la sélection
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="clearInscriptionSelection()"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
        </div>
    </div>
</div>

<style>
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
@endif

<div class="modal fade" id="bulkValidationModal" tabindex="-1" aria-labelledby="bulkValidationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content klassci-payment-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkValidationModalLabel">
                    <i class="fas fa-check-double me-2"></i>Validation groupée des inscriptions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Résumé de la sélection :</strong>
                    <div class="mt-2">Total sélectionné : <strong id="bulk-total-count">0</strong></div>
                    <div class="mt-1">Prêtes à valider : <strong id="bulk-ready-count">0</strong></div>
                </div>

                <div id="bulk-no-payment-section" class="mb-3 d-none">
                    <div class="alert alert-warning">
                        <strong>Sans paiement :</strong> ces inscriptions seront ignorées.
                    </div>
                    <ul id="bulk-no-payment-list" class="list-group"></ul>
                </div>

                <div id="bulk-pending-payment-section" class="mb-3 d-none">
                    <div class="alert alert-warning">
                        <strong>Paiements non validés :</strong> ces inscriptions seront ignorées tant que le paiement n'est pas validé.
                    </div>
                    <ul id="bulk-pending-payment-list" class="list-group"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="bulk-validation-confirm">
                    <i class="fas fa-check-double me-1"></i>Confirmer la validation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour associer un paiement -->
<div class="modal fade klassci-payment-modal" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
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

<!-- Modal: Valider Paiement -->
<div class="modal fade klassci-payment-modal" id="modalValiderPaiement" tabindex="-1" aria-labelledby="modalValiderPaiementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalValiderPaiementLabel">
                    <i class="fas fa-check-circle me-2"></i>Valider le paiement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formValiderPaiement" method="POST">
                    @csrf
                    <input type="hidden" name="inscription_id" id="valider_inscription_id">
                    <input type="hidden" name="paiement_id" id="valider_paiement_id">

                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="validerPaiementInfo">Paiement à valider...</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Montant</label>
                        <input type="text" class="form-control" id="valider_montant" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mode de paiement</label>
                        <input type="text" class="form-control" id="valider_mode" readonly>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Référence</label>
                        <input type="text" class="form-control" id="valider_reference" readonly>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Valider le paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Changer la Classe -->
<div class="modal fade klassci-payment-modal" id="modalChangerClasse" tabindex="-1" aria-labelledby="modalChangerClasseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChangerClasseLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Changer la classe
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formChangerClasse" method="POST">
                    @csrf
                    <input type="hidden" name="inscription_id" id="changer_inscription_id">

                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        La classe actuelle est pleine. Veuillez sélectionner une nouvelle classe.
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Classe actuelle</label>
                            <input type="text" class="form-control" id="changer_ancienne_classe" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nouvelle classe <span class="text-danger">*</span></label>
                            <select class="form-select" name="nouvelle_classe_id" id="changer_nouvelle_classe" required>
                                <option value="">Sélectionnez une classe</option>
                            </select>
                        </div>
                    </div>

                    <div id="classeDispoInfo" class="alert alert-success" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="classeDispoText">Places disponibles: ...</span>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-exchange-alt me-2"></i>Changer la classe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour validation définitive -->
<div class="modal fade klassci-payment-modal" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
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
<div class="modal fade klassci-payment-modal" id="cancelInscriptionModal" tabindex="-1" aria-labelledby="cancelInscriptionModalLabel" aria-hidden="true">
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

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade klassci-payment-modal" id="yearChangeModal" tabindex="-1" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Comment changer l'année académique ?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les inscriptions d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les inscriptions affichées se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois.
                    Changer l'année courante affecte l'affichage des inscriptions dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Actuellement :</strong><br>
                    • Année courante = {{ $anneeEnCours->name ?? 'Non définie' }}<br>
                    • Inscriptions visibles = {{ $inscriptions->count() }} sur {{ \App\Models\ESBTPInscription::count() }} au total
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-2"></i>Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const ADMIN_REFRESH_CONTEXT = 'administration';
    const ADMIN_BASE_URL = "{{ route('esbtp.inscriptions.administration') }}";

    // Fonction de debug pour les erreurs
    function debugError(error) {
        if (console && console.error) {
            console.error('Erreur détectée:', error);
            if (error.response) {
                console.error('Response:', error.response);
            }
            if (error.stack) {
                console.error('Stack:', error.stack);
            }
        }
    }

    function showYearChangeInfo() {
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(document.getElementById('yearChangeModal'));
            modal.show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#yearChangeModal').modal('show');
        } else {
            const modal = document.getElementById('yearChangeModal');
            if (modal) {
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');

                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'modal-backdrop';
                document.body.appendChild(backdrop);
            }
        }
    }

    function closeYearModal() {
        const modal = document.getElementById('yearChangeModal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');

            const backdrop = document.getElementById('modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
    }

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
            const wasChecked = existingRow?.querySelector('.inscription-checkbox')?.checked ?? false;
            if (existingRow) {
                existingRow.replaceWith(newRow);
            }

            const newCheckbox = newRow.querySelector('.inscription-checkbox');
            if (newCheckbox && wasChecked) {
                newCheckbox.checked = true;
            }

            triggerInscriptionRowHighlight(newRow, actionType);
            bindInscriptionActions();
            bindBulkSelectionHandlers();
            updateInscriptionSelectionCount();
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

    function ouvrirModalValiderPaiement(inscriptionId) {
        fetch(`/esbtp/inscriptions/${inscriptionId}/paiement-en-attente`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.paiement) {
                    document.getElementById('valider_inscription_id').value = inscriptionId;
                    document.getElementById('valider_paiement_id').value = data.paiement.id;
                    document.getElementById('valider_montant').value = new Intl.NumberFormat('fr-FR').format(data.paiement.montant) + ' FCFA';
                    document.getElementById('valider_mode').value = data.paiement.mode_paiement || 'N/A';
                    document.getElementById('valider_reference').value = data.paiement.reference_paiement || 'N/A';
                    document.getElementById('validerPaiementInfo').textContent = `Paiement de ${data.paiement.etudiant.nom} ${data.paiement.etudiant.prenoms}`;
                    document.getElementById('formValiderPaiement').action = `/esbtp/paiements/${data.paiement.id}/valider-rapide`;

                    const modal = new bootstrap.Modal(document.getElementById('modalValiderPaiement'));
                    modal.show();
                } else {
                    alert('Impossible de récupérer les informations du paiement: ' + (data.message || ''));
                }
            })
            .catch(error => {
                debugError(error);
                alert('Erreur lors du chargement des données');
            });
    }

    function ouvrirModalChangerClasse(inscriptionId) {
        fetch(`/esbtp/inscriptions/${inscriptionId}/classes-alternatives`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('changer_inscription_id').value = inscriptionId;
                    document.getElementById('changer_ancienne_classe').value = data.classeActuelle.name;

                    const select = document.getElementById('changer_nouvelle_classe');
                    select.innerHTML = '<option value="">Sélectionnez une classe</option>';

                    data.classesAlternatives.forEach(classe => {
                        const option = document.createElement('option');
                        option.value = classe.id;

                        if (classe.is_available) {
                            option.textContent = `${classe.name} (${classe.places_disponibles}/${classe.places_totales} places disponibles)`;
                        } else {
                            option.textContent = `${classe.name} (COMPLET - ${classe.places_disponibles}/${classe.places_totales})`;
                            option.style.color = '#dc3545';
                            option.style.fontWeight = 'bold';
                        }

                        option.dataset.placesDisponibles = classe.places_disponibles;
                        option.dataset.isAvailable = classe.is_available;
                        select.appendChild(option);
                    });

                    select.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        if (selectedOption.value) {
                            const isAvailable = selectedOption.dataset.isAvailable === 'true';
                            const placesDisponibles = selectedOption.dataset.placesDisponibles;

                            document.getElementById('classeDispoInfo').style.display = 'block';

                            if (isAvailable) {
                                document.getElementById('classeDispoText').textContent =
                                    `✓ Places disponibles: ${placesDisponibles}`;
                                document.getElementById('classeDispoText').style.color = '#28a745';
                            } else {
                                document.getElementById('classeDispoText').textContent =
                                    `⚠ Classe complète (${placesDisponibles} places disponibles)`;
                                document.getElementById('classeDispoText').style.color = '#dc3545';
                            }
                        } else {
                            document.getElementById('classeDispoInfo').style.display = 'none';
                        }
                    });

                    document.getElementById('formChangerClasse').action = `/esbtp/inscriptions/${inscriptionId}/changer-classe-rapide`;

                    const modal = new bootstrap.Modal(document.getElementById('modalChangerClasse'));
                    modal.show();
                } else {
                    alert(data.message || 'Impossible de récupérer les classes alternatives');
                }
            })
            .catch(error => {
                debugError(error);
                alert('Erreur lors du chargement des données');
            });
    }

    function handleInscriptionValidation(inscriptionId, hasPayment, forceValidation = false) {
        if (!hasPayment && !forceValidation) {
            openPaymentModal(inscriptionId, { autoValidate: true });
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('inscription_ids[]', inscriptionId);
        if (forceValidation) {
            formData.append('force', '1');
        }

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

            const problems = data.inscriptions_problemes || {};
            const actionType = problems[inscriptionId] ? 'reject' : 'validate';
            refreshInscriptionLigne(inscriptionId, actionType);
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

    function updateInscriptionSelectionCount() {
        const checkboxes = document.querySelectorAll('.inscription-checkbox:checked');
        const count = checkboxes.length;
        const bulkBar = document.getElementById('bulk-actions-bar');
        const selectedCountSpan = document.getElementById('selected-count');

        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }

        if (bulkBar) {
            bulkBar.style.display = count > 0 ? 'block' : 'none';
        }
    }

    function bindBulkSelectionHandlers() {
        const selectAllCheckbox = document.getElementById('select-all-inscriptions');
        if (selectAllCheckbox) {
            selectAllCheckbox.onchange = function () {
                const checkboxes = document.querySelectorAll('.inscription-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateInscriptionSelectionCount();
            };
        }

        document.querySelectorAll('.inscription-checkbox').forEach(checkbox => {
            checkbox.onchange = function () {
                updateInscriptionSelectionCount();

                const allCheckboxes = document.querySelectorAll('.inscription-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.inscription-checkbox:checked');
                const selectAll = document.getElementById('select-all-inscriptions');

                if (selectAll) {
                    selectAll.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
                }
            };
        });
    }

    function clearInscriptionSelection() {
        document.querySelectorAll('.inscription-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAll = document.getElementById('select-all-inscriptions');
        if (selectAll) {
            selectAll.checked = false;
        }
        updateInscriptionSelectionCount();
    }

    let bulkSelectedIds = [];

    function openBulkValidationModal() {
        const checkboxes = document.querySelectorAll('.inscription-checkbox:checked');
        const rows = Array.from(checkboxes).map(cb => cb.closest('tr')).filter(Boolean);

        if (rows.length === 0) {
            alert('Veuillez sélectionner au moins une inscription à valider.');
            return;
        }

        bulkSelectedIds = rows.map(row => row.dataset.inscriptionId);

        const noPaymentList = document.getElementById('bulk-no-payment-list');
        const pendingPaymentList = document.getElementById('bulk-pending-payment-list');
        const noPaymentSection = document.getElementById('bulk-no-payment-section');
        const pendingPaymentSection = document.getElementById('bulk-pending-payment-section');
        const totalCount = document.getElementById('bulk-total-count');
        const readyCount = document.getElementById('bulk-ready-count');

        const noPaymentItems = [];
        const pendingPaymentItems = [];
        const classePleineItems = [];
        let ready = 0;

        rows.forEach(row => {
            const hasPayment = row.dataset.hasPayment === '1';
            const paymentStatus = row.dataset.paymentStatus || 'aucun';
            const label = row.dataset.studentLabel || 'Étudiant';
            const matricule = row.dataset.matricule ? `(${row.dataset.matricule})` : '';
            const display = `${label} ${matricule}`.trim();
            const hasClassePleineProbleme = row.querySelector('.badge')?.textContent?.includes('Classe pleine') || false;

            if (hasClassePleineProbleme) {
                classePleineItems.push({
                    id: row.dataset.inscriptionId,
                    label: display,
                    action: 'classe_pleine',
                    classeLabel: row.dataset.classeLabel || 'N/A'
                });
            } else if (!hasPayment || paymentStatus === 'aucun') {
                noPaymentItems.push({
                    id: row.dataset.inscriptionId,
                    label: display,
                    action: 'payment'
                });
            } else if (paymentStatus === 'en_attente') {
                pendingPaymentItems.push({
                    id: row.dataset.inscriptionId,
                    label: display,
                    action: 'pending'
                });
            } else {
                ready += 1;
            }
        });

        totalCount.textContent = rows.length;
        readyCount.textContent = ready;

        const renderList = (element, items) => {
            element.innerHTML = '';
            items.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex align-items-center justify-content-between gap-2';
                li.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-user text-muted"></i>
                        <span>${item.label}</span>
                    </div>
                `;

                if (item.action === 'payment') {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-sm btn-outline-primary bulk-action-button';
                    button.textContent = 'Créer paiement';
                    button.dataset.inscriptionId = item.id;
                    button.dataset.action = 'payment';
                    li.appendChild(button);
                }

                if (item.action === 'pending') {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-sm btn-outline-warning bulk-action-button';
                    button.textContent = 'Valider paiement';
                    button.dataset.inscriptionId = item.id;
                    button.dataset.action = 'validate-payment';
                    li.appendChild(button);

                    const viewButton = document.createElement('button');
                    viewButton.type = 'button';
                    viewButton.className = 'btn btn-sm btn-outline-secondary bulk-action-button';
                    viewButton.textContent = 'Voir dossier';
                    viewButton.dataset.inscriptionId = item.id;
                    viewButton.dataset.action = 'show';
                    li.appendChild(viewButton);
                }

                if (item.action === 'classe_pleine') {
                    const changeButton = document.createElement('button');
                    changeButton.type = 'button';
                    changeButton.className = 'btn btn-sm btn-outline-primary bulk-action-button';
                    changeButton.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Changer classe';
                    changeButton.dataset.inscriptionId = item.id;
                    changeButton.dataset.action = 'change-class';
                    li.appendChild(changeButton);

                    const forceButton = document.createElement('button');
                    forceButton.type = 'button';
                    forceButton.className = 'btn btn-sm btn-outline-danger bulk-action-button';
                    forceButton.innerHTML = '<i class="fas fa-bolt me-1"></i>Forcer';
                    forceButton.dataset.inscriptionId = item.id;
                    forceButton.dataset.action = 'force-validate';
                    li.appendChild(forceButton);
                }

                element.appendChild(li);
            });
        };

        renderList(noPaymentList, noPaymentItems);
        renderList(pendingPaymentList, pendingPaymentItems);

        // Ajouter une section pour les classes pleines si nécessaire
        let classePleineSection = document.getElementById('bulk-classe-pleine-section');
        if (!classePleineSection && classePleineItems.length > 0) {
            classePleineSection = document.createElement('div');
            classePleineSection.id = 'bulk-classe-pleine-section';
            classePleineSection.className = 'mb-3';
            classePleineSection.innerHTML = `
                <div class="alert alert-warning">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i>Classes pleines</strong>
                    <div>Les inscriptions suivantes ont une classe pleine :</div>
                </div>
                <ul id="bulk-classe-pleine-list" class="list-group"></ul>
            `;
            pendingPaymentSection.parentNode.insertBefore(classePleineSection, pendingPaymentSection.nextSibling);
        }

        if (classePleineSection) {
            const classePleineList = document.getElementById('bulk-classe-pleine-list');
            renderList(classePleineList, classePleineItems);
            classePleineSection.classList.toggle('d-none', classePleineItems.length === 0);
        }

        noPaymentSection.classList.toggle('d-none', noPaymentItems.length === 0);
        pendingPaymentSection.classList.toggle('d-none', pendingPaymentItems.length === 0);

        document.querySelectorAll('#bulkValidationModal .bulk-action-button').forEach(button => {
            button.addEventListener('click', function () {
                const inscriptionId = this.dataset.inscriptionId;
                if (this.dataset.action === 'payment') {
                    openPaymentModal(inscriptionId, { autoValidate: true });
                } else if (this.dataset.action === 'validate-payment') {
                    ouvrirModalValiderPaiement(inscriptionId);
                } else if (this.dataset.action === 'show') {
                    window.open(`/esbtp/inscriptions/${inscriptionId}`, '_blank');
                } else if (this.dataset.action === 'change-class') {
                    // Fermer le modal de validation groupée
                    const bulkModal = bootstrap.Modal.getInstance(document.getElementById('bulkValidationModal'));
                    if (bulkModal) bulkModal.hide();
                    // Ouvrir le modal de changement de classe
                    ouvrirModalChangerClasse(inscriptionId);
                } else if (this.dataset.action === 'force-validate') {
                    // Fermer le modal de validation groupée
                    const bulkModal = bootstrap.Modal.getInstance(document.getElementById('bulkValidationModal'));
                    if (bulkModal) bulkModal.hide();
                    // Forcer la validation
                    if (confirm('Êtes-vous sûr de vouloir forcer la validation malgré la classe pleine ?')) {
                        handleInscriptionValidation(inscriptionId, true);
                    }
                }
            });
        });

        const modalElement = document.getElementById('bulkValidationModal');
        if (typeof bootstrap !== 'undefined' && modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

    function bulkValiderInscriptions(inscriptionIds = null) {
        const ids = inscriptionIds && inscriptionIds.length ? inscriptionIds : bulkSelectedIds;

        if (!ids.length) {
            alert('Veuillez sélectionner au moins une inscription à valider.');
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        ids.forEach(id => formData.append('inscription_ids[]', id));

        fetch("{{ route('esbtp.inscriptions.bulk-valider') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Erreur lors de la validation groupée.');
                return;
            }

            if (data.message) {
                alert(data.message);
            }

            const problems = data.inscriptions_problemes || {};
            ids.forEach((id, index) => {
                const actionType = problems[id] ? 'reject' : 'validate';
                setTimeout(() => {
                    refreshInscriptionLigne(id, actionType);
                }, index * 120);
            });

            clearInscriptionSelection();
        })
        .catch(error => {
            debugError(error);
            alert('Erreur lors de la validation groupée.');
        });
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
        bindBulkSelectionHandlers();
        updateInscriptionSelectionCount();
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
                bindBulkSelectionHandlers();
                updateInscriptionSelectionCount();
                bindPaginationLinks();
            })
            .catch(error => {
                debugError(error);
                alert('Impossible de charger les inscriptions. Veuillez réessayer.');
            })
            .finally(() => setLoading(false));
        }

        window.fetchResults = fetchResults;

        window.resetAdminFilters = function() {
            const form = document.getElementById('inscriptions-admin-filter-form');
            if (form) {
                form.reset();
            }
            const headerSearch = document.querySelector('.dashboard-header .search-bar');
            if (headerSearch) headerSearch.value = '';
            fetchResults(ADMIN_BASE_URL, { pushState: true });
        };

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

        const validerPaiementForm = document.getElementById('formValiderPaiement');
        if (validerPaiementForm) {
            validerPaiementForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Validation en cours...';

                const formData = new FormData(this);
                const actionUrl = this.action;
                const inscriptionId = document.getElementById('valider_inscription_id').value;

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
                        bootstrap.Modal.getInstance(document.getElementById('modalValiderPaiement')).hide();
                        refreshInscriptionLigne(inscriptionId, 'validate');
                    } else {
                        alert(data.message || 'Erreur lors de la validation');
                    }
                })
                .catch(error => {
                    debugError(error);
                    alert('Erreur lors de la validation du paiement');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }

        const changerClasseForm = document.getElementById('formChangerClasse');
        if (changerClasseForm) {
            changerClasseForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changement en cours...';

                const formData = new FormData(this);
                const actionUrl = this.action;
                const inscriptionId = document.getElementById('changer_inscription_id').value;

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
                        bootstrap.Modal.getInstance(document.getElementById('modalChangerClasse')).hide();
                        // Rafraîchir la ligne en AJAX au lieu de recharger toute la page
                        refreshInscriptionLigne(inscriptionId, 'change-class');
                    } else {
                        alert(data.message || 'Erreur lors du changement de classe');
                    }
                })
                .catch(error => {
                    debugError(error);
                    alert('Erreur lors du changement de classe');
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

        const bulkConfirmButton = document.getElementById('bulk-validation-confirm');
        if (bulkConfirmButton) {
            bulkConfirmButton.addEventListener('click', function () {
                const modal = bootstrap.Modal.getInstance(document.getElementById('bulkValidationModal'));
                if (modal) {
                    modal.hide();
                }
                bulkValiderInscriptions(bulkSelectedIds);
            });
        }

        const closeButton = document.querySelector('#yearChangeModal .close');
        if (closeButton) {
            closeButton.addEventListener('click', closeYearModal);
        }

        const cancelButton = document.querySelector('#yearChangeModal .btn-secondary');
        if (cancelButton) {
            cancelButton.addEventListener('click', closeYearModal);
        }
    });
</script>
@endpush
