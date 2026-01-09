@extends('layouts.app')

@section('title', 'Dashboard Service Technique - African Digit Consulting')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .service-technique-header {
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .service-technique-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-height: 160px;
        display: flex;
        flex-direction: column;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: 12px 12px 0 0;
    }

    .stat-card.primary::before { background: #2563eb; }
    .stat-card.success::before { background: #10b981; }
    .stat-card.info::before { background: #0ea5e9; }
    .stat-card.warning::before { background: #f59e0b; }
    .stat-card.danger::before { background: #ef4444; }

    .stat-card:hover {
        border-color: #0ea5e9;
        box-shadow: 0 8px 25px rgba(33, 150, 243, 0.15);
        transform: translateY(-2px);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        margin-bottom: 1rem;
        flex-shrink: 0;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }

    .stat-label {
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 1rem;
        line-height: 1.4;
    }

    .stat-sublabel {
        font-size: 0.85rem;
        color: #9ca3af;
        line-height: 1.3;
        margin-top: auto;
    }

    .paywall-status {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 2px solid #e5e7eb;
    }

    .status-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .status-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.25rem;
        color: white;
    }

    .status-icon.success { background: #10b981; }
    .status-icon.warning { background: #f59e0b; }
    .status-icon.danger { background: #ef4444; }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .action-card {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .action-card:hover {
        border-color: #0ea5e9;
        box-shadow: 0 8px 25px rgba(33, 150, 243, 0.15);
        transform: translateY(-2px);
        text-decoration: none;
        color: inherit;
    }

    .action-icon {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.75rem;
        color: white;
    }

    .action-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    .action-description {
        font-size: 0.9rem;
        color: #6b7280;
    }

    .emergency-codes {
        background: #fff8dc;
        border: 2px solid #f59e0b;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 2rem;
    }

    .emergency-codes h5 {
        color: #f59e0b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }

    .code-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .code-value {
        font-family: monospace;
        font-weight: 600;
        color: #2563eb;
    }

    .code-expiry {
        font-size: 0.85rem;
        color: #9ca3af;
    }

    .etablissements-section {
        background: white;
        border-radius: 12px;
        padding: 0;
        border: 2px solid #e5e7eb;
        overflow: hidden;
    }

    .etablissements-header {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .etablissements-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .etablissements-count {
        background: #0ea5e9;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .etablissement-card {
        padding: 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.3s ease;
    }

    .etablissement-card:last-child {
        border-bottom: none;
    }

    .etablissement-card:hover {
        background: #f8fafc;
    }

    .etablissement-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .etablissement-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .etablissement-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
        flex-shrink: 0;
    }

    .etablissement-details h4 {
        margin: 0 0 0.25rem 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
    }

    .etablissement-subtitle {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0;
    }

    .etablissement-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 25px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.active {
        background: #dcfce7;
        color: #166534;
    }

    .status-badge.inactive {
        background: #f3f4f6;
        color: #6b7280;
    }

    .etablissement-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .meta-item {
        display: flex;
        flex-direction: column;
    }

    .meta-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    .meta-value {
        font-weight: 600;
        color: #1f2937;
    }

    .meta-value code {
        background: #f1f5f9;
        color: #0ea5e9;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-size: 0.85rem;
    }

    .etablissement-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid transparent;
    }

    .action-btn.primary {
        background: #0ea5e9;
        color: white;
    }

    .action-btn.primary:hover {
        background: #0284c7;
        color: white;
        text-decoration: none;
    }

    .action-btn.secondary {
        background: #f1f5f9;
        color: #0ea5e9;
        border-color: #e2e8f0;
    }

    .action-btn.secondary:hover {
        background: #0ea5e9;
        color: white;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-card {
            min-height: 140px;
            padding: 1.25rem;
        }

        .quick-actions {
            grid-template-columns: 1fr;
        }

        .stat-value {
            font-size: 2rem;
        }

        .stat-label {
            font-size: 0.9rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            margin-bottom: 0.75rem;
        }
    }
</style>
@endsection

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <!-- Header Service Technique -->
        <div class="service-technique-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="h2 mb-2">
                        <i class="fas fa-tools me-2"></i>
                        Dashboard Service Technique
                    </h1>
                    <p class="mb-0 opacity-90">
                        Bienvenue, {{ auth()->user()->name }} !
                        Gestion centralisée des établissements KLASSCI
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="service-technique-header-accent">
                        <i class="fas fa-cogs fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statut Paywall -->
        <div class="paywall-status">
            <div class="status-header">
                <div class="status-icon {{ $paywallStatus['level'] }}">
                    <i class="fas fa-{{ $paywallStatus['level'] === 'success' ? 'check' : ($paywallStatus['level'] === 'warning' ? 'exclamation-triangle' : 'times') }}"></i>
                </div>
                <div>
                    <h5 class="mb-1">Statut Système Paywall</h5>
                    <p class="mb-0 text-{{ $paywallStatus['level'] }}">{{ $paywallStatus['message'] }}</p>
                </div>
            </div>

            @if($paywallConfig['is_active'])
                <div class="row">
                    <div class="col-md-6">
                        <strong>Plan actuel :</strong> {{ $paywallConfig['plan_name'] }}<br>
                        <strong>Prix :</strong> {{ number_format($paywallConfig['plan_price'], 0, ',', ' ') }} XOF/an
                    </div>
                    <div class="col-md-6">
                        @if($paywallConfig['subscription_end'])
                            <strong>Expiration :</strong> {{ \Carbon\Carbon::parse($paywallConfig['subscription_end'])->format('d/m/Y') }}<br>
                        @endif
                        <strong>Limites :</strong> {{ $paywallConfig['max_users'] }} utilisateurs, {{ $paywallConfig['max_inscriptions_per_year'] }} inscriptions/an
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">Le système paywall n'est pas activé pour cet établissement.</p>
            @endif
        </div>

        <!-- Statistiques Établissement Actuel -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon" style="background: #2563eb;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">{{ $stats['total_users'] }}</div>
                <div class="stat-label">Utilisateurs Total</div>
                <div class="stat-sublabel">{{ $paywallConfig['max_users'] }} maximum autorisés</div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon" style="background: #10b981;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value">{{ $stats['total_students'] }}</div>
                <div class="stat-label">Étudiants</div>
                <div class="stat-sublabel">{{ $recentActivity['new_students_this_month'] }} ce mois</div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon" style="background: #0ea5e9;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value">{{ $stats['total_teachers'] }}</div>
                <div class="stat-label">Enseignants</div>
                <div class="stat-sublabel">Personnel académique</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon" style="background: #f59e0b;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-value">{{ $stats['total_inscriptions_year'] }}</div>
                <div class="stat-label">Inscriptions Année</div>
                <div class="stat-sublabel">{{ $paywallConfig['max_inscriptions_per_year'] }} maximum autorisées</div>
            </div>
        </div>

        <!-- Actions Rapides -->
        <div class="quick-actions">
            <a href="{{ route('esbtp.paywall-config.index') }}" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="action-title">Configuration Paywall</div>
                <div class="action-description">Gérer les abonnements et limites de l'établissement</div>
            </a>

            <a href="{{ route('esbtp.matricule-config.index') }}" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="action-title">Configuration Matricule</div>
                <div class="action-description">Paramétrer la génération des numéros matricule</div>
            </a>

            <a href="{{ route('esbtp.paywall-config.upgrade') }}" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="action-title">Plans d'Abonnement</div>
                <div class="action-description">Voir les plans disponibles et tarifs KLASSCI</div>
            </a>

            <a href="mailto:klassci@africandigitconsulting.com" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="action-title">Support Client</div>
                <div class="action-description">Contacter l'équipe pour assistance</div>
            </a>
        </div>

        <!-- Établissements Gérés -->
        <div class="etablissements-section">
            <div class="etablissements-header">
                <div class="etablissements-title">
                    <i class="fas fa-school"></i>
                    Établissements Gérés
                </div>
                <div class="etablissements-count">{{ $etablissements->count() }} établissement{{ $etablissements->count() > 1 ? 's' : '' }}</div>
            </div>

            @foreach($etablissements as $etablissement)
                <div class="etablissement-card">
                    <div class="etablissement-main">
                        <div class="etablissement-info">
                            <div class="etablissement-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="etablissement-details">
                                <h4>{{ $etablissement->nom }}</h4>
                                <p class="etablissement-subtitle">Établissement d'enseignement supérieur</p>
                            </div>
                        </div>
                        <div class="etablissement-status">
                            <span class="status-badge {{ $etablissement->status }}">
                                {{ $etablissement->status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>

                    <div class="etablissement-meta">
                        <div class="meta-item">
                            <div class="meta-label">Branche Git</div>
                            <div class="meta-value"><code>{{ $etablissement->branch }}</code></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Date de création</div>
                            <div class="meta-value">{{ $etablissement->created_at->format('d/m/Y') }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Type</div>
                            <div class="meta-value">Multi-tenant</div>
                        </div>
                    </div>

                    
                </div>
            @endforeach
        </div>

        <!-- Codes d'Urgence Actifs -->
        @if($activeCodes->count() > 0)
            <div class="emergency-codes">
                <h5><i class="fas fa-key me-2"></i>Codes d'Urgence Actifs ({{ $activeCodes->count() }})</h5>
                @foreach($activeCodes as $code)
                    <div class="code-item">
                        <div>
                            <span class="code-value">{{ $code->code }}</span>
                            <small class="text-muted d-block">Créé par : {{ $code->created_by }}</small>
                        </div>
                        <div class="code-expiry">
                            Expire : {{ $code->expires_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection