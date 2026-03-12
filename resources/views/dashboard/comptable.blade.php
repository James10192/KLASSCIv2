@extends('layouts.app')

@section('title', 'Tableau de bord Comptable')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    body {
        background-color: var(--background);
    }

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

    .kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .kpi-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
    }

    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        background: var(--border-light);
        overflow: hidden;
    }

    .progress-bar-custom .fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.6s ease;
    }

    .notification-card {
        border-left: 4px solid;
        padding: var(--space-md);
        border-radius: var(--radius-small);
    }

    .notification-card.warning {
        background: rgba(245, 158, 11, 0.05);
        border-color: var(--warning);
    }

    .notification-card.success {
        background: rgba(16, 185, 129, 0.05);
        border-color: var(--success);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .quick-link {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-md);
        border-radius: var(--radius-small);
        text-decoration: none;
        color: var(--text-primary);
        border: 1px solid var(--border-light);
        transition: all 0.2s ease;
    }

    .quick-link:hover {
        border-color: var(--primary);
        background: rgba(4, 83, 203, 0.04);
        color: var(--primary);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .quick-link .link-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-small);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .mode-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            text-align: center;
            gap: var(--space-md);
        }
        .kpi-value {
            font-size: 1.2rem;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">

        {{-- Header --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calculator me-2"></i>Tableau de bord comptable</h1>
                <p class="header-subtitle">Bienvenue, <strong>{{ $user->name }}</strong> ! Suivi financier en temps réel</p>
            </div>
            <div class="header-actions">
                <span class="badge rounded-pill bg-light text-dark me-2">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $anneeEnCours->name ?? 'Année non définie' }}
                </span>
                <span class="text-muted">{{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}</span>
            </div>
        </div>

        {{-- Alerte paiements en attente --}}
        @if($paiementsEnAttenteCount > 0)
            <div class="card-moderne mb-4 notification-card warning">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <div class="fw-semibold mb-1">
                            <i class="fas fa-clock text-warning me-2"></i>
                            {{ $paiementsEnAttenteCount }} paiement(s) en attente de validation
                        </div>
                        <div class="text-muted small">
                            Montant total en attente : <strong>{{ number_format($totalEnAttente, 0, ',', ' ') }} FCFA</strong>
                        </div>
                    </div>
                    <a href="{{ route('esbtp.paiements.index', ['status' => 'en_attente']) }}" class="btn-acasi warning">
                        <i class="fas fa-check-circle me-1"></i>Valider
                    </a>
                </div>
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="row g-3 mb-4">
            {{-- Total Frais Dus --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Total frais dus</div>
                            <div class="kpi-value">{{ number_format($totalFraisDus, 0, ',', ' ') }}</div>
                            <div class="text-muted small">FCFA</div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(30, 41, 59, 0.08); color: var(--text-primary);">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Encaissé --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Encaissé</div>
                            <div class="kpi-value text-success">{{ number_format($totalEncaisse, 0, ',', ' ') }}</div>
                            <div class="text-muted small">FCFA</div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Restant --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Restant à percevoir</div>
                            <div class="kpi-value" style="color: var(--primary);">{{ number_format($montantRestant, 0, ',', ' ') }}</div>
                            <div class="text-muted small">FCFA</div>
                        </div>
                        <div class="kpi-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Taux de Recouvrement --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="kpi-label">Taux de recouvrement</div>
                            <div class="kpi-value">{{ $tauxRecouvrement }}%</div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(4, 83, 203, 0.12); color: var(--primary);">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="fill" style="width: {{ min($tauxRecouvrement, 100) }}%; background: {{ $tauxRecouvrement >= 75 ? 'var(--success)' : ($tauxRecouvrement >= 50 ? 'var(--primary)' : 'var(--warning)') }};"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Encaissements du mois --}}
        <div class="card-moderne mb-4 notification-card success">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div>
                    <div class="fw-semibold">
                        <i class="fas fa-calendar-check text-success me-2"></i>
                        Encaissements du mois de {{ \Carbon\Carbon::now()->isoFormat('MMMM YYYY') }}
                    </div>
                    <div class="h5 mb-0 mt-1 text-success">{{ number_format($encaisseMois, 0, ',', ' ') }} FCFA</div>
                </div>
                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi primary">
                    <i class="fas fa-chart-line me-1"></i>Dashboard avancé
                </a>
            </div>
        </div>

        <div class="row g-3">
            {{-- Paiements récents --}}
            <div class="col-lg-8 col-12">
                <div class="card-moderne">
                    <div class="p-lg">
                        <div class="section-title mb-md d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-money-bill-wave me-2"></i>Paiements récents</span>
                            <a href="{{ route('esbtp.paiements.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                        </div>
                        @if($recentPaiements->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Référence</th>
                                            <th>Étudiant</th>
                                            <th>Montant</th>
                                            <th>Mode</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentPaiements as $paiement)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="text-decoration-none fw-medium">
                                                        {{ $paiement->numero_recu ?: $paiement->reference_paiement ?: '#' . $paiement->id }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($paiement->etudiant)
                                                        <div class="fw-medium">{{ $paiement->etudiant->nom }} {{ $paiement->etudiant->prenoms }}</div>
                                                        <div class="text-muted small">{{ $paiement->etudiant->matricule }}</div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="fw-semibold">{{ number_format($paiement->montant, 0, ',', ' ') }}</td>
                                                <td>
                                                    @if($paiement->mode_paiement)
                                                        <span class="mode-badge" style="background: rgba(4, 83, 203, 0.08); color: var(--primary);">
                                                            @if(str_contains($paiement->mode_paiement, 'Esp'))
                                                                <i class="fas fa-coins"></i>
                                                            @elseif(str_contains($paiement->mode_paiement, 'Mobile'))
                                                                <i class="fas fa-mobile-alt"></i>
                                                            @elseif(str_contains($paiement->mode_paiement, 'Virement'))
                                                                <i class="fas fa-university"></i>
                                                            @elseif(str_contains($paiement->mode_paiement, 'Ch'))
                                                                <i class="fas fa-money-check"></i>
                                                            @else
                                                                <i class="fas fa-credit-card"></i>
                                                            @endif
                                                            {{ $paiement->mode_paiement }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusColor = match($paiement->status) {
                                                            'validé' => 'success',
                                                            'en_attente' => 'warning',
                                                            'rejeté' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        $statusLabel = match($paiement->status) {
                                                            'validé' => 'Validé',
                                                            'en_attente' => 'En attente',
                                                            'rejeté' => 'Rejeté',
                                                            default => $paiement->status
                                                        };
                                                    @endphp
                                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border border-{{ $statusColor }}-subtle">
                                                        <span class="status-dot bg-{{ $statusColor }} me-1"></span>
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td class="text-muted small">
                                                    {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : $paiement->created_at->format('d/m/Y') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-receipt fa-2x mb-2 d-block opacity-50"></i>
                                Aucun paiement récent
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar droite --}}
            <div class="col-lg-4 col-12">
                {{-- Top impayés --}}
                <div class="card-moderne mb-3">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-exclamation-circle me-2" style="color: var(--primary);"></i>Top 5 impayés
                        </div>
                        @if($topImpayes->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($topImpayes as $impaye)
                                    <div class="list-group-item px-0 border-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-medium">{{ $impaye->nom }} {{ $impaye->prenoms }}</div>
                                                <div class="text-muted small">{{ $impaye->matricule }}</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold" style="color: var(--primary);">{{ number_format($impaye->solde_restant, 0, ',', ' ') }}</div>
                                                <div class="text-muted small">FCFA</div>
                                            </div>
                                        </div>
                                        <div class="progress-bar-custom mt-2">
                                            @php $pctPaye = $impaye->total_du > 0 ? round(($impaye->total_paye / $impaye->total_du) * 100) : 0; @endphp
                                            <div class="fill" style="width: {{ $pctPaye }}%; background: {{ $pctPaye >= 75 ? 'var(--success)' : ($pctPaye >= 25 ? 'var(--primary)' : 'var(--warning)') }};"></div>
                                        </div>
                                        <div class="text-muted small mt-1">{{ $pctPaye }}% payé — {{ number_format($impaye->total_paye, 0, ',', ' ') }} / {{ number_format($impaye->total_du, 0, ',', ' ') }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 text-center">
                                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-bell me-1"></i>Voir les relances
                                </a>
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
                                Aucun impayé significatif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Répartition par mode de paiement --}}
                @if($paiementsParMode->count() > 0)
                <div class="card-moderne mb-3">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-chart-bar me-2"></i>Modes de paiement (mois)
                        </div>
                        @php $totalModes = $paiementsParMode->sum('total'); @endphp
                        @foreach($paiementsParMode as $mode)
                            @php
                                $pct = $totalModes > 0 ? round(($mode->total / $totalModes) * 100) : 0;
                                $modeIcon = match(true) {
                                    str_contains($mode->mode_paiement ?? '', 'Esp') => 'fa-coins',
                                    str_contains($mode->mode_paiement ?? '', 'Mobile') => 'fa-mobile-alt',
                                    str_contains($mode->mode_paiement ?? '', 'Virement') => 'fa-university',
                                    str_contains($mode->mode_paiement ?? '', 'Ch') => 'fa-money-check',
                                    default => 'fa-credit-card'
                                };
                            @endphp
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3" style="width: 32px; text-align: center;">
                                    <i class="fas {{ $modeIcon }}" style="color: var(--primary);"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $mode->mode_paiement ?? 'Autre' }}</span>
                                        <span class="fw-medium">{{ number_format($mode->total, 0, ',', ' ') }} ({{ $pct }}%)</span>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="fill" style="width: {{ $pct }}%; background: var(--primary);"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Accès rapides --}}
                <div class="card-moderne">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-bolt me-2"></i>Accès rapides
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <a href="{{ route('esbtp.frais.index') }}" class="quick-link">
                                <div class="link-icon" style="background: rgba(4, 83, 203, 0.08); color: var(--primary);">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">Gestion des frais</div>
                                    <div class="text-muted small">Catégories et tarifs</div>
                                </div>
                            </a>
                            <a href="{{ route('esbtp.paiements.index') }}" class="quick-link">
                                <div class="link-icon" style="background: rgba(16, 185, 129, 0.08); color: var(--success);">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">Liste des paiements</div>
                                    <div class="text-muted small">Valider et suivre</div>
                                </div>
                            </a>
                            <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="quick-link">
                                <div class="link-icon" style="background: rgba(94, 145, 222, 0.12); color: var(--secondary);">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">Suivi par catégorie</div>
                                    <div class="text-muted small">Analyse par type de frais</div>
                                </div>
                            </a>
                            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="quick-link">
                                <div class="link-icon" style="background: rgba(245, 158, 11, 0.08); color: var(--warning);">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">Relances</div>
                                    <div class="text-muted small">Étudiants avec impayés</div>
                                </div>
                            </a>
                            <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="quick-link">
                                <div class="link-icon" style="background: rgba(30, 41, 59, 0.08); color: var(--text-primary);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">Dashboard avancé</div>
                                    <div class="text-muted small">Rapports et analytics</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
