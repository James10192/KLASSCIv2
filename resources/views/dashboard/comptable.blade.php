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

    /* ─── Payment Feed ──────────────────────────────────────────── */
    .payment-feed { display: flex; flex-direction: column; gap: 0; }

    .payment-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.05);
        text-decoration: none; color: var(--text-primary);
        transition: background 0.15s ease;
    }
    .payment-item:last-child { border-bottom: none; }
    .payment-item:hover { background: rgba(4,83,203,0.03); color: var(--text-primary); }

    .pay-status-bar { width: 3px; min-height: 40px; border-radius: 2px; flex-shrink: 0; align-self: stretch; }

    .pay-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.3px;
        flex-shrink: 0; text-transform: uppercase;
    }

    .pay-info { flex: 1; min-width: 0; }
    .pay-name { font-size: 0.84rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
    .pay-meta { display: flex; align-items: center; gap: 5px; margin-top: 3px; flex-wrap: wrap; }
    .pay-ref { font-size: 0.71rem; color: var(--text-secondary); font-family: 'Courier New', monospace; }

    .pay-mode-pill {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 1px 7px; border-radius: 10px; font-size: 0.67rem; font-weight: 500;
        background: rgba(4,83,203,0.07); color: var(--primary); border: 1px solid rgba(4,83,203,0.12);
    }
    .pay-status-pill {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 1px 7px; border-radius: 10px; font-size: 0.67rem; font-weight: 600;
    }

    .pay-right { text-align: right; flex-shrink: 0; }
    .pay-amount { font-size: 0.88rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.2; }
    .pay-date { font-size: 0.68rem; color: var(--text-secondary); margin-top: 2px; }

    /* ─── Ranking / Top Impayés ─────────────────────────────────── */
    .rank-list { display: flex; flex-direction: column; gap: 8px; }

    .rank-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: var(--radius-small);
        border: 1px solid var(--border-light); background: #fff;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .rank-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.06); border-color: rgba(4,83,203,0.2); }

    .rank-badge {
        width: 26px; height: 26px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem; font-weight: 800; flex-shrink: 0;
    }
    .rank-1 { background: #fef3c7; color: #92400e; border: 1.5px solid #f59e0b; }
    .rank-2 { background: #f1f5f9; color: #475569; border: 1.5px solid #94a3b8; }
    .rank-3 { background: #fef2e8; color: #7c2d12; border: 1.5px solid #b45309; }
    .rank-other { background: rgba(4,83,203,0.06); color: var(--primary); border: 1.5px solid rgba(4,83,203,0.18); }

    .rank-info { flex: 1; min-width: 0; }
    .rank-name { font-size: 0.82rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
    .rank-matricule { font-size: 0.69rem; color: var(--text-secondary); font-family: 'Courier New', monospace; }
    .rank-track { height: 3px; background: var(--border-light); border-radius: 2px; overflow: hidden; margin-top: 4px; }
    .rank-track-fill { height: 100%; border-radius: 2px; transition: width 0.5s ease; }
    .rank-pct { font-size: 0.67rem; color: var(--text-secondary); margin-top: 2px; }
    .rank-amount { text-align: right; flex-shrink: 0; }
    .rank-solde { font-size: 0.85rem; font-weight: 700; color: #dc2626; letter-spacing: -0.3px; line-height: 1.2; }
    .rank-unit { font-size: 0.65rem; color: var(--text-secondary); }
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
                <div class="card-moderne" style="overflow: hidden;">
                    <div style="padding: var(--space-lg) var(--space-lg) var(--space-md);">
                        <div class="section-title d-flex justify-content-between align-items-center" style="margin-bottom: 0;">
                            <span><i class="fas fa-stream me-2"></i>Paiements récents</span>
                            <a href="{{ route('esbtp.paiements.index') }}" class="btn-acasi secondary" style="font-size: 0.78rem; padding: 4px 12px;">
                                Voir tout <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">
                            {{ $recentPaiements->count() }} derniers enregistrements
                        </div>
                    </div>

                    @if($recentPaiements->count() > 0)
                        <div class="payment-feed">
                            @foreach($recentPaiements as $paiement)
                                @php
                                    $statusColor = match($paiement->status) {
                                        'validé'     => '#10b981',
                                        'en_attente' => '#f59e0b',
                                        'rejeté'     => '#ef4444',
                                        default      => '#94a3b8'
                                    };
                                    $statusLabel = match($paiement->status) {
                                        'validé'     => 'Validé',
                                        'en_attente' => 'En attente',
                                        'rejeté'     => 'Rejeté',
                                        default      => $paiement->status
                                    };
                                    $statusBg = match($paiement->status) {
                                        'validé'     => 'rgba(16,185,129,0.1)',
                                        'en_attente' => 'rgba(245,158,11,0.1)',
                                        'rejeté'     => 'rgba(239,68,68,0.1)',
                                        default      => 'rgba(148,163,184,0.1)'
                                    };
                                    $modeIcon = match(true) {
                                        str_contains($paiement->mode_paiement ?? '', 'Esp')      => 'fa-coins',
                                        str_contains($paiement->mode_paiement ?? '', 'Mobile')   => 'fa-mobile-alt',
                                        str_contains($paiement->mode_paiement ?? '', 'Virement') => 'fa-university',
                                        str_contains($paiement->mode_paiement ?? '', 'Ch')       => 'fa-money-check',
                                        default => 'fa-credit-card'
                                    };
                                    $initials = $paiement->etudiant
                                        ? mb_strtoupper(mb_substr($paiement->etudiant->nom, 0, 1) . mb_substr($paiement->etudiant->prenoms ?? '', 0, 1))
                                        : '??';
                                    $dateAffichee = $paiement->date_paiement
                                        ? \Carbon\Carbon::parse($paiement->date_paiement)
                                        : $paiement->created_at;
                                @endphp
                                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="payment-item">
                                    <div class="pay-status-bar" style="background: {{ $statusColor }};"></div>
                                    <div class="pay-avatar" style="background: {{ $statusBg }}; color: {{ $statusColor }};">
                                        {{ $initials }}
                                    </div>
                                    <div class="pay-info">
                                        <div class="pay-name">
                                            @if($paiement->etudiant)
                                                {{ $paiement->etudiant->nom }} {{ $paiement->etudiant->prenoms }}
                                            @else
                                                Étudiant inconnu
                                            @endif
                                        </div>
                                        <div class="pay-meta">
                                            <span class="pay-ref">{{ $paiement->numero_recu ?: ($paiement->reference_paiement ?: '#'.$paiement->id) }}</span>
                                            @if($paiement->mode_paiement)
                                                <span class="pay-mode-pill">
                                                    <i class="fas {{ $modeIcon }}" style="font-size: 0.6rem;"></i>
                                                    {{ $paiement->mode_paiement }}
                                                </span>
                                            @endif
                                            <span class="pay-status-pill" style="background: {{ $statusBg }}; color: {{ $statusColor }};">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="pay-right">
                                        <div class="pay-amount" style="color: {{ $paiement->status === 'rejeté' ? '#94a3b8' : 'var(--text-primary)' }};">
                                            {{ number_format($paiement->montant, 0, ',', ' ') }}
                                            <span style="font-size: 0.65rem; font-weight: 500; color: var(--text-secondary);">FCFA</span>
                                        </div>
                                        <div class="pay-date">{{ $dateAffichee->diffForHumans() }}</div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted" style="padding: 2.5rem 1rem;">
                            <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(4,83,203,0.06); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                <i class="fas fa-receipt" style="color: var(--primary); opacity: 0.5; font-size: 1.2rem;"></i>
                            </div>
                            <div style="font-size: 0.85rem; font-weight: 500;">Aucun paiement pour cette période</div>
                            <div style="font-size: 0.75rem; margin-top: 4px;">Les paiements de l'année courante s'afficheront ici</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar droite --}}
            <div class="col-lg-4 col-12">
                {{-- Top impayés --}}
                <div class="card-moderne mb-3">
                    <div class="p-lg">
                        <div class="section-title d-flex justify-content-between align-items-center mb-md">
                            <span><i class="fas fa-trophy me-2" style="color: #f59e0b;"></i>Top 5 impayés</span>
                            <span class="text-muted" style="font-size: 0.72rem;">solde restant</span>
                        </div>
                        @if($topImpayes->count() > 0)
                            <div class="rank-list">
                                @foreach($topImpayes as $index => $impaye)
                                    @php
                                        $rank = $index + 1;
                                        $rankClass = match($rank) { 1 => 'rank-1', 2 => 'rank-2', 3 => 'rank-3', default => 'rank-other' };
                                        $pctPaye = $impaye->total_du > 0 ? round(($impaye->total_paye / $impaye->total_du) * 100) : 0;
                                        $trackColor = $pctPaye >= 75 ? 'var(--success)' : ($pctPaye >= 25 ? 'var(--primary)' : 'var(--warning)');
                                    @endphp
                                    <div class="rank-item">
                                        <div class="rank-badge {{ $rankClass }}">{{ $rank }}</div>
                                        <div class="rank-info">
                                            <div class="rank-name">{{ $impaye->nom }} {{ $impaye->prenoms }}</div>
                                            <div class="rank-matricule">{{ $impaye->matricule }}</div>
                                            <div class="rank-track">
                                                <div class="rank-track-fill" style="width: {{ $pctPaye }}%; background: {{ $trackColor }};"></div>
                                            </div>
                                            <div class="rank-pct">{{ $pctPaye }}% payé</div>
                                        </div>
                                        <div class="rank-amount">
                                            <div class="rank-solde">{{ number_format($impaye->solde_restant, 0, ',', ' ') }}</div>
                                            <div class="rank-unit">FCFA restant</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 text-center">
                                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi warning" style="font-size: 0.78rem; padding: 5px 14px;">
                                    <i class="fas fa-bell me-1"></i>Voir les relances
                                </a>
                            </div>
                        @else
                            <div class="text-center text-muted" style="padding: 1.5rem 0;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(16,185,129,0.08); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                                    <i class="fas fa-check-circle" style="color: var(--success); font-size: 1.1rem;"></i>
                                </div>
                                <div style="font-size: 0.82rem; font-weight: 500;">Aucun impayé significatif</div>
                                <div style="font-size: 0.72rem; margin-top: 3px;">Tous les étudiants sont à jour</div>
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
