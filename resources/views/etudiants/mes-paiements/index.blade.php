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
            <div class="card-moderne">
                <div class="card-body text-center" style="padding: var(--space-2xl);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--warning); margin-bottom: var(--space-lg);"></i>
                    <h4>Aucune inscription active</h4>
                    <p style="color: var(--text-secondary); margin-bottom: var(--space-lg);">
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
                                            @if($paiement->status === 'validé')
                                                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}"
                                                   class="action-btn btn-primary"
                                                   target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                    Voir le reçu
                                                </a>
                                            @else
                                                <span style="color: var(--text-muted); font-size: var(--text-sm); font-style: italic;">
                                                    -
                                                </span>
                                            @endif
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
@endsection
