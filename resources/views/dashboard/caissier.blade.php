@extends('layouts.app')

@section('title', 'Tableau de bord Caissier')

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

    .quick-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: var(--radius-small);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .quick-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .quick-action-btn.primary {
        background: linear-gradient(135deg, #0453cb, #5e91de);
        color: #fff;
    }
    .quick-action-btn.primary:hover {
        color: #fff;
    }

    .quick-action-btn.secondary {
        background: #fff;
        color: var(--text-primary);
        border: 1px solid var(--border-light);
    }
    .quick-action-btn.secondary:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    /* Payment feed */
    .payment-feed { display: flex; flex-direction: column; gap: 0; }

    .payment-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.05);
        text-decoration: none; color: var(--text-primary);
        transition: background 0.15s ease;
    }
    .payment-item:last-child { border-bottom: none; }
    .payment-item:hover { background: rgba(4,83,203,0.03); color: var(--text-primary); }

    .pay-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.3px;
        flex-shrink: 0; text-transform: uppercase;
        background: rgba(4, 83, 203, 0.08); color: var(--primary);
    }

    .pay-info { flex: 1; min-width: 0; }
    .pay-name { font-size: 0.84rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
    .pay-meta { display: flex; align-items: center; gap: 5px; margin-top: 3px; flex-wrap: wrap; }

    .pay-status-pill {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 1px 7px; border-radius: 10px; font-size: 0.67rem; font-weight: 600;
    }
    .pay-status-pill.validated { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .pay-status-pill.pending { background: rgba(4, 83, 203, 0.08); color: var(--primary); }

    .pay-right { text-align: right; flex-shrink: 0; }
    .pay-amount { font-size: 0.88rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.2; }
    .pay-date { font-size: 0.68rem; color: var(--text-secondary); margin-top: 2px; }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.4;
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
        .quick-actions-row {
            flex-direction: column;
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
                <h1><i class="fas fa-cash-register me-2"></i>Tableau de bord</h1>
                <p class="header-subtitle">Bienvenue, <strong>{{ $user->name }}</strong> | Caisse</p>
            </div>
            <div class="header-actions">
                <span class="badge rounded-pill bg-light text-dark me-2">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $anneeEnCours->name ?? 'Année non définie' }}
                </span>
                <span class="text-muted">{{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}</span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card-moderne mb-4" style="padding: var(--space-lg);">
            <div class="d-flex flex-wrap gap-3 quick-actions-row">
                <a href="{{ route('esbtp.inscriptions.pre-inscription') }}" class="quick-action-btn primary">
                    <i class="fas fa-user-plus"></i>
                    Nouvelle pré-inscription
                </a>
                <a href="{{ route('esbtp.etudiants.index') }}" class="quick-action-btn secondary">
                    <i class="fas fa-search"></i>
                    Rechercher un étudiant
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="row g-3 mb-4">
            {{-- Paiements du jour --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Paiements du jour</div>
                            <div class="kpi-value">{{ $paiementsAujourdhuiCount }}</div>
                            <div class="text-muted small">opérations</div>
                        </div>
                        <div class="kpi-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Montant encaissé --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Montant encaissé</div>
                            <div class="kpi-value text-success">{{ number_format($montantEncaisseAujourdhui, 0, ',', ' ') }}</div>
                            <div class="text-muted small">FCFA aujourd'hui</div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pré-inscriptions aujourd'hui --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Pré-inscriptions</div>
                            <div class="kpi-value">{{ $preInscriptionsAujourdhui }}</div>
                            <div class="text-muted small">aujourd'hui</div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(4, 83, 203, 0.08); color: var(--primary);">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- En attente admin --}}
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card-moderne kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">En attente admin</div>
                            <div class="kpi-value" style="{{ $preInscriptionsEnAttente > 0 ? 'color: #c2410c;' : '' }}">{{ $preInscriptionsEnAttente }}</div>
                            <div class="text-muted small">pré-inscriptions</div>
                        </div>
                        <div class="kpi-icon" style="{{ $preInscriptionsEnAttente > 0 ? 'background: rgba(194, 65, 12, 0.1); color: #c2410c;' : '' }}">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Payments --}}
        <div class="card-moderne">
            <div class="d-flex justify-content-between align-items-center" style="padding: var(--space-md) var(--space-lg); border-bottom: 1px solid var(--border-light);">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-history me-2" style="color: var(--primary);"></i>Derniers paiements
                </h6>
                @can('paiements.view')
                <a href="{{ route('esbtp.paiements.index') }}" class="text-decoration-none small" style="color: var(--primary);">
                    Voir tout <i class="fas fa-arrow-right ms-1"></i>
                </a>
                @endcan
            </div>

            <div class="payment-feed">
                @forelse($paiementsRecents as $paiement)
                    @php
                        $etudiant = $paiement->etudiant;
                        $initials = $etudiant ? strtoupper(substr($etudiant->nom ?? '', 0, 1) . substr($etudiant->prenoms ?? '', 0, 1)) : '??';
                        $nomComplet = $etudiant ? ($etudiant->nom . ' ' . $etudiant->prenoms) : 'Inconnu';
                        $isValide = $paiement->status === 'validé';
                    @endphp
                    <div class="payment-item">
                        <div class="pay-avatar">{{ $initials }}</div>
                        <div class="pay-info">
                            <div class="pay-name">{{ $nomComplet }}</div>
                            <div class="pay-meta">
                                <span class="pay-status-pill {{ $isValide ? 'validated' : 'pending' }}">
                                    <i class="fas fa-{{ $isValide ? 'check' : 'clock' }}" style="font-size: 0.55rem;"></i>
                                    {{ $isValide ? 'Validé' : ucfirst($paiement->status) }}
                                </span>
                                @if($paiement->mode_paiement)
                                    <span style="font-size: 0.7rem; color: var(--text-secondary);">{{ ucfirst($paiement->mode_paiement) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="pay-right">
                            <div class="pay-amount" style="color: {{ $isValide ? 'var(--success)' : 'var(--text-primary)' }};">
                                {{ number_format($paiement->montant, 0, ',', ' ') }} F
                            </div>
                            <div class="pay-date">{{ $paiement->created_at->format('H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div><i class="fas fa-receipt"></i></div>
                        <p class="mb-0">Aucun paiement enregistré</p>
                        <small>Vos paiements apparaîtront ici</small>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
