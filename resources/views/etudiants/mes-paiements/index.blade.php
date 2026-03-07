@extends('layouts.app')

@section('title', 'Mes Paiements - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ================================
       KPI STATS CARDS
       ================================ */
    .kpi-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .kpi-stat-card {
        background: #fff;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .kpi-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: var(--card-gradient, linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%));
        border-radius: 0 var(--radius-large) 0 100%;
        opacity: 0.5;
    }

    .kpi-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }

    .kpi-stat-card.primary::before {
        --card-gradient: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
    }

    .kpi-stat-card.success::before {
        --card-gradient: linear-gradient(135deg, rgba(40, 199, 111, 0.15) 0%, rgba(34, 166, 94, 0.15) 100%);
    }

    .kpi-stat-card.warning::before {
        --card-gradient: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 152, 0, 0.15) 100%);
    }

    .kpi-stat-card.info::before {
        --card-gradient: linear-gradient(135deg, rgba(23, 162, 184, 0.15) 0%, rgba(13, 110, 253, 0.15) 100%);
    }

    .kpi-stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-md);
        position: relative;
        z-index: 1;
    }

    .kpi-stat-label {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--text-xl);
    }

    .kpi-stat-card.primary .kpi-stat-icon {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
    }

    .kpi-stat-card.success .kpi-stat-icon {
        background: linear-gradient(135deg, #28c76f 0%, #22a65e 100%);
        color: #fff;
    }

    .kpi-stat-card.warning .kpi-stat-icon {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #fff;
    }

    .kpi-stat-card.info .kpi-stat-icon {
        background: linear-gradient(135deg, #17a2b8 0%, #0d6efd 100%);
        color: #fff;
    }

    .kpi-stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .kpi-stat-card.primary .kpi-stat-value {
        color: var(--primary);
    }

    .kpi-stat-card.success .kpi-stat-value {
        color: #28c76f;
    }

    .kpi-stat-card.warning .kpi-stat-value {
        color: #ffc107;
    }

    .kpi-stat-card.info .kpi-stat-value {
        color: #17a2b8;
    }

    /* ================================
       TABLE STYLES
       ================================ */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .paiements-table {
        width: 100%;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .paiements-table thead th {
        background: var(--surface);
        color: var(--text-primary);
        font-weight: 600;
        text-transform: uppercase;
        font-size: var(--text-sm);
        letter-spacing: 0.5px;
        padding: var(--space-md) var(--space-lg);
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
    }

    .paiements-table tbody td {
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }

    .paiements-table tbody tr {
        transition: all 0.2s ease;
    }

    .paiements-table tbody tr:hover {
        background-color: var(--surface);
    }

    .paiements-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Status badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-xs) var(--space-md);
        border-radius: var(--radius-full);
        font-size: var(--text-sm);
        font-weight: 600;
        white-space: nowrap;
    }

    .status-badge i {
        font-size: 0.75rem;
    }

    .status-badge.status-valide {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-badge.status-en-attente {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .status-badge.status-rejete {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Action buttons */
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-small);
        font-size: var(--text-sm);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }

    .action-btn.btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
    }

    .action-btn.btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(var(--primary-rgb), 0.3);
    }

    .action-btn.btn-info {
        background: linear-gradient(135deg, #17a2b8, #0d6efd);
        color: #fff;
    }

    .action-btn.btn-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
    }

    .action-btn.btn-success {
        background: linear-gradient(135deg, #28c76f, #22a65e);
        color: #fff;
    }

    .action-btn.btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 199, 111, 0.3);
    }

    /* Modal paiement detail */
    .paiement-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem 0;
        border-bottom: 1px solid var(--border, #eee);
    }

    .paiement-detail-row:last-child {
        border-bottom: none;
    }

    .paiement-detail-label {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .paiement-detail-value {
        font-weight: 600;
        color: var(--text-primary);
        text-align: right;
    }

    /* ================================
       EMPTY STATE
       ================================ */
    .no-paiements {
        text-align: center;
        padding: var(--space-3xl) var(--space-xl);
        background: white;
        border-radius: var(--radius-large);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .no-paiements-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--surface), #e9ecef);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: var(--space-lg);
    }

    .no-paiements-icon i {
        font-size: 2.5rem;
        color: var(--text-muted);
    }

    .no-paiements-title {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }

    .no-paiements-text {
        font-size: var(--text-base);
        color: var(--text-secondary);
        margin: 0;
    }

    /* ================================
       RESPONSIVE DESIGN
       ================================ */
    @media (max-width: 768px) {
        .kpi-stats-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .kpi-stat-card {
            padding: var(--space-md);
        }

        .kpi-stat-value {
            font-size: 1.5rem;
        }

        .paiements-table thead th,
        .paiements-table tbody td {
            padding: var(--space-sm) var(--space-md);
            font-size: var(--text-sm);
        }

        .action-btn {
            padding: var(--space-xs) var(--space-sm);
            font-size: 0.8rem;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: var(--space-xs) var(--space-sm);
        }

        .student-header .d-flex {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: var(--space-md);
        }

        .student-header h1 {
            font-size: 1.5rem !important;
        }

        .student-header .header-subtitle {
            font-size: 0.875rem !important;
        }

        .student-header .text-end {
            text-align: left !important;
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .paiements-table {
            font-size: 0.8rem;
        }

        .paiements-table thead th,
        .paiements-table tbody td {
            padding: var(--space-xs) var(--space-sm);
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Étudiant Moderne (identique à mes-evaluations) -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-wallet me-3"></i>
                        Mes Paiements
                    </h1>
                    <p class="header-subtitle">
                        Consultez l'historique de vos paiements et votre situation financière
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                        <i class="fas fa-calendar me-2"></i>
                        Année {{ $anneeCourante->name ?? date('Y') . '-' . (date('Y') + 1) }}
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="margin: var(--space-lg) 0;">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="margin: var(--space-lg) 0;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning" style="margin: var(--space-lg) 0;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
            </div>
        @endif

        @if(!$inscription)
            <div class="card-moderne" style="margin: var(--space-xl) 0;">
                <div class="card-body text-center" style="padding: var(--space-3xl) var(--space-2xl);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3.5rem; color: var(--warning); margin-bottom: var(--space-xl); display: inline-block;"></i>
                    <h4 style="margin-bottom: var(--space-md); font-weight: 700;">Aucune inscription active</h4>
                    <p style="color: var(--text-secondary); margin-bottom: var(--space-xl); max-width: 600px; margin-left: auto; margin-right: auto;">
                        Vous n'avez pas d'inscription active pour l'année en cours.
                        Veuillez contacter l'administration pour régulariser votre situation.
                    </p>
                    <a href="{{ route('esbtp.mon-profil.index') }}" class="btn-acasi primary">
                        <i class="fas fa-user me-2"></i>
                        Voir mon profil
                    </a>
                </div>
            </div>
        @else
        <!-- KPI Stats Cards -->
        <div class="kpi-stats-grid">
            <!-- Total Frais -->
            <div class="kpi-stat-card primary">
                <div class="kpi-stat-header">
                    <span class="kpi-stat-label">Total Frais</span>
                    <div class="kpi-stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <h3 class="kpi-stat-value">{{ number_format($kpiStats['totalFrais'], 0, ',', ' ') }} FCFA</h3>
            </div>

            <!-- Total Payé -->
            <div class="kpi-stat-card success">
                <div class="kpi-stat-header">
                    <span class="kpi-stat-label">Total Payé</span>
                    <div class="kpi-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <h3 class="kpi-stat-value">{{ number_format($kpiStats['totalPaye'], 0, ',', ' ') }} FCFA</h3>
            </div>

            <!-- Reste à Payer -->
            <div class="kpi-stat-card warning">
                <div class="kpi-stat-header">
                    <span class="kpi-stat-label">Reste à Payer</span>
                    <div class="kpi-stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <h3 class="kpi-stat-value">{{ number_format($kpiStats['resteDu'], 0, ',', ' ') }} FCFA</h3>
            </div>

            <!-- Taux de Paiement -->
            <div class="kpi-stat-card info">
                <div class="kpi-stat-header">
                    <span class="kpi-stat-label">Taux de Paiement</span>
                    <div class="kpi-stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <h3 class="kpi-stat-value">{{ number_format($kpiStats['tauxPaiement'], 1) }}%</h3>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="card-moderne">
            <div class="section-card-header">
                <h6 class="section-card-title">
                    <i class="fas fa-receipt"></i>
                    Historique des Paiements
                </h6>
            </div>
            <div class="section-card-body">
                @if($paiements->isEmpty())
                    <!-- Empty State -->
                    <div class="no-paiements">
                        <div class="no-paiements-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="no-paiements-title">Aucun paiement enregistré</div>
                        <p class="no-paiements-text">
                            Vous n'avez effectué aucun paiement pour l'instant.
                        </p>
                    </div>
                @else
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="paiements-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Mode</th>
                                    <th>Référence</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paiements as $paiement)
                                    <tr>
                                        <!-- Date -->
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                                                <span style="font-weight: 600; color: var(--text-primary);">
                                                    {{ \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') }}
                                                </span>
                                                <span style="font-size: var(--text-sm); color: var(--text-secondary);">
                                                    {{ \Carbon\Carbon::parse($paiement->date_paiement)->format('H:i') }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Montant -->
                                        <td>
                                            <span style="font-weight: 700; color: var(--text-primary); font-size: 1.05rem;">
                                                {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                                            </span>
                                        </td>

                                        <!-- Mode -->
                                        <td>
                                            <span style="font-weight: 500; color: var(--text-primary);">
                                                @if($paiement->mode_paiement === 'especes')
                                                    Espèces
                                                @elseif($paiement->mode_paiement === 'cheque')
                                                    Chèque
                                                @elseif($paiement->mode_paiement === 'virement')
                                                    Virement
                                                @elseif($paiement->mode_paiement === 'carte')
                                                    Carte
                                                @elseif($paiement->mode_paiement === 'mobile_money')
                                                    Mobile Money
                                                @else
                                                    {{ ucfirst($paiement->mode_paiement) }}
                                                @endif
                                            </span>
                                        </td>

                                        <!-- Référence -->
                                        <td>
                                            <span style="font-family: monospace; background: var(--surface); padding: var(--space-xs) var(--space-sm); border-radius: var(--radius-small); font-size: var(--text-sm); color: var(--text-primary);">
                                                {{ $paiement->reference_paiement ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <!-- Statut -->
                                        <td>
                                            @if($paiement->status === 'validé')
                                                <span class="status-badge status-valide">
                                                    <i class="fas fa-check-circle"></i>
                                                    Validé
                                                </span>
                                            @elseif($paiement->status === 'en_attente')
                                                <span class="status-badge status-en-attente">
                                                    <i class="fas fa-clock"></i>
                                                    En attente
                                                </span>
                                            @elseif($paiement->status === 'rejeté')
                                                <span class="status-badge status-rejete">
                                                    <i class="fas fa-times-circle"></i>
                                                    Rejeté
                                                </span>
                                            @else
                                                <span class="status-badge">
                                                    {{ ucfirst($paiement->status) }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td>
                                            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                                <button type="button"
                                                    class="action-btn btn-info"
                                                    title="Voir les détails"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#paiementDetailModal"
                                                    data-paiement-id="{{ $paiement->id }}"
                                                    data-date="{{ \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') }}"
                                                    data-montant="{{ number_format($paiement->montant, 0, ',', ' ') }}"
                                                    data-mode="{{ $paiement->mode_paiement }}"
                                                    data-reference="{{ $paiement->reference_paiement ?? 'N/A' }}"
                                                    data-motif="{{ $paiement->motif ?? 'N/A' }}"
                                                    data-status="{{ $paiement->status }}"
                                                    data-observations="{{ $paiement->observations ?? '' }}"
                                                    data-numero-recu="{{ $paiement->numero_recu ?? 'N/A' }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if($paiement->status === 'validé')
                                                    <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}"
                                                       class="action-btn btn-success"
                                                       target="_blank"
                                                       title="Télécharger PDF">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
        </div>
    </div>
</div>
<!-- Modal Détails Paiement -->
<div class="modal fade" id="paiementDetailModal" tabindex="-1" aria-labelledby="paiementDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--radius-large); border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: var(--radius-large) var(--radius-large) 0 0; border: none; padding: 1.25rem 1.5rem;">
                <h5 class="modal-title" id="paiementDetailModalLabel" style="font-weight: 700; margin: 0;">
                    <i class="fas fa-receipt me-2"></i>
                    Détails du paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div id="modal-paiement-numero" style="text-align: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 2px solid var(--border, #eee);">
                    <div style="font-size: var(--text-sm); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">Numéro de reçu</div>
                    <div id="modal-numero-recu" style="font-family: monospace; font-size: 1.1rem; font-weight: 700; color: var(--primary); background: var(--surface, #f8f9fa); padding: 0.4rem 1rem; border-radius: var(--radius-medium); display: inline-block;"></div>
                </div>

                <div class="paiement-detail-row">
                    <span class="paiement-detail-label">Date</span>
                    <span class="paiement-detail-value" id="modal-date"></span>
                </div>
                <div class="paiement-detail-row">
                    <span class="paiement-detail-label">Montant</span>
                    <span class="paiement-detail-value" id="modal-montant" style="font-size: 1.2rem; color: var(--primary);"></span>
                </div>
                <div class="paiement-detail-row">
                    <span class="paiement-detail-label">Mode de paiement</span>
                    <span class="paiement-detail-value" id="modal-mode"></span>
                </div>
                <div class="paiement-detail-row">
                    <span class="paiement-detail-label">Référence</span>
                    <span class="paiement-detail-value" id="modal-reference" style="font-family: monospace;"></span>
                </div>
                <div class="paiement-detail-row">
                    <span class="paiement-detail-label">Motif</span>
                    <span class="paiement-detail-value" id="modal-motif"></span>
                </div>
                <div class="paiement-detail-row">
                    <span class="paiement-detail-label">Statut</span>
                    <span id="modal-status"></span>
                </div>
                <div id="modal-observations-row" class="paiement-detail-row" style="display: none;">
                    <span class="paiement-detail-label">Observations</span>
                    <span class="paiement-detail-value" id="modal-observations" style="text-align: right; max-width: 60%;"></span>
                </div>
            </div>
            <div class="modal-footer" style="border: none; padding: 1rem 1.5rem 1.5rem; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
                <a id="modal-pdf-link" href="#" class="btn btn-success" target="_blank" style="display: none;">
                    <i class="fas fa-download me-1"></i> Télécharger PDF
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pdfRouteBase = "{{ rtrim(route('esbtp.paiements.recu', ['paiement' => '__ID__']), '') }}";

    const modal = document.getElementById('paiementDetailModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;

        const modeRaw = btn.getAttribute('data-mode') || '';
        const modeLabels = {
            'especes': 'Espèces',
            'cheque': 'Chèque',
            'virement': 'Virement bancaire',
            'carte': 'Carte bancaire',
            'mobile_money': 'Mobile Money',
            'Espèces': 'Espèces',
            'Chèque': 'Chèque',
            'Virement bancaire': 'Virement bancaire',
            'Mobile Money': 'Mobile Money',
        };
        const modeLabel = modeLabels[modeRaw] || modeRaw;

        const status = btn.getAttribute('data-status') || '';
        const statusLabels = {
            'validé': '<span class="status-badge status-valide"><i class="fas fa-check-circle"></i> Validé</span>',
            'en_attente': '<span class="status-badge status-en-attente"><i class="fas fa-clock"></i> En attente</span>',
            'rejeté': '<span class="status-badge status-rejete"><i class="fas fa-times-circle"></i> Rejeté</span>',
        };

        const paiementId = btn.getAttribute('data-paiement-id');
        const observations = btn.getAttribute('data-observations') || '';

        modal.querySelector('#modal-numero-recu').textContent = btn.getAttribute('data-numero-recu') || 'N/A';
        modal.querySelector('#modal-date').textContent = btn.getAttribute('data-date') || 'N/A';
        modal.querySelector('#modal-montant').textContent = (btn.getAttribute('data-montant') || '0') + ' FCFA';
        modal.querySelector('#modal-mode').textContent = modeLabel;
        modal.querySelector('#modal-reference').textContent = btn.getAttribute('data-reference') || 'N/A';
        modal.querySelector('#modal-motif').textContent = btn.getAttribute('data-motif') || 'N/A';
        modal.querySelector('#modal-status').innerHTML = statusLabels[status] || ('<span class="status-badge">' + status + '</span>');

        const obsRow = modal.querySelector('#modal-observations-row');
        if (observations && observations.trim() !== '') {
            modal.querySelector('#modal-observations').textContent = observations;
            obsRow.style.display = 'flex';
        } else {
            obsRow.style.display = 'none';
        }

        const pdfLink = modal.querySelector('#modal-pdf-link');
        if (status === 'validé') {
            pdfLink.href = pdfRouteBase.replace('__ID__', paiementId);
            pdfLink.style.display = 'inline-flex';
        } else {
            pdfLink.style.display = 'none';
        }
    });
});
</script>
@endpush
