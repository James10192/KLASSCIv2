@extends('layouts.app')

@section('title', 'Limite d\'Abonnement Atteinte - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles identiques à emploi-temps.index */
    .emploi-temps-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .emploi-temps-header::before {
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

    .emploi-stat-card {
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .emploi-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }

    .emploi-stat-card.primary::before { background: var(--primary); }
    .emploi-stat-card.success::before { background: var(--success); }
    .emploi-stat-card.info::before { background: var(--accent-blue); }
    .emploi-stat-card.warning::before { background: var(--warning); }
    .emploi-stat-card.danger::before { background: var(--danger); }

    .emploi-stat-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-sm);
        font-size: 20px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .emploi-stat-card.primary .emploi-stat-icon { color: var(--primary); }
    .emploi-stat-card.success .emploi-stat-icon { color: var(--success); }
    .emploi-stat-card.info .emploi-stat-icon { color: var(--accent-blue); }
    .emploi-stat-card.warning .emploi-stat-icon { color: var(--warning); }
    .emploi-stat-card.danger .emploi-stat-icon { color: var(--danger); }

    .emploi-stat-value {
        font-size: var(--amount-large);
        font-weight: bold;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }

    .emploi-stat-label {
        color: var(--text-secondary);
        font-size: var(--text-small);
        font-weight: 500;
    }

    .emploi-filter-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
    }

    .emploi-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
        margin-bottom: var(--space-md);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .emploi-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }

    .emploi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }

    .emploi-card.premium::before { background: linear-gradient(135deg, #7c3aed, #5b21b6); }
    .emploi-card.recommended::before { background: var(--primary); }
    .emploi-card.current::before { background: var(--success); }

    .plan-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: var(--primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        z-index: 10;
    }

    .plan-badge.premium {
        background: linear-gradient(135deg, #7c3aed, #5b21b6);
    }

    .plan-badge.current {
        background: var(--success);
    }

    .plan-price {
        font-size: 2rem;
        font-weight: bold;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .plan-price-monthly {
        font-size: 1rem;
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }

    .feature {
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .feature:last-child {
        border-bottom: none;
    }

    .usage-progress {
        margin-top: 0.5rem;
    }

    .usage-progress .progress {
        height: 8px;
        margin-bottom: 0.5rem;
        border-radius: 4px;
    }

    .usage-text {
        font-weight: 600;
        color: var(--text-primary);
        font-size: var(--text-small);
    }

    /* Plans élégants style KLASSCI */
    .plan-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0;
        position: relative;
        transition: all 0.3s ease;
        height: 100%;
        overflow: hidden;
    }

    .plan-card:hover {
        border-color: var(--primary);
        box-shadow: 0 8px 25px rgba(30, 58, 138, 0.15);
        transform: translateY(-2px);
    }

    .plan-card.recommended {
        border-color: var(--primary);
        box-shadow: 0 4px 15px rgba(30, 58, 138, 0.1);
    }

    .plan-card.premium {
        border-color: var(--primary);
        background: linear-gradient(135deg, #fafbff 0%, #f8f9ff 100%);
    }

    .plan-card.current-plan {
        border-color: var(--primary);
        border-width: 2px;
        background: linear-gradient(135deg, #f0f4ff 0%, #e6f1ff 100%);
    }

    .plan-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: var(--primary);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 10;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .plan-badge.premium {
        background: linear-gradient(135deg, var(--primary), #1e40af);
    }

    .plan-status {
        position: absolute;
        top: 12px;
        right: 12px;
        background: var(--primary);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 10;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .plan-header {
        padding: 48px 24px 16px 24px;
        text-align: center;
        border-bottom: 1px solid #f3f4f6;
        position: relative;
    }

    .plan-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 8px 0;
    }

    .plan-subtitle {
        color: #6b7280;
        font-size: 14px;
        margin: 0;
    }

    .plan-pricing {
        padding: 24px;
        text-align: center;
        background: #fafbff;
    }

    .price-main {
        font-size: 36px;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
        margin-bottom: 4px;
    }

    .currency {
        font-size: 18px;
        font-weight: 600;
    }

    .price-period {
        color: #6b7280;
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .price-monthly {
        color: #9ca3af;
        font-size: 14px;
        font-style: italic;
    }

    .plan-features {
        padding: 24px;
    }

    .features-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .features-list li {
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        font-size: 15px;
        position: relative;
        padding-left: 24px;
    }

    .features-list li:last-child {
        border-bottom: none;
    }

    .features-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: var(--primary);
        font-weight: bold;
        font-size: 16px;
    }

    .emergency-section {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid #f59e0b;
        margin-top: var(--space-lg);
    }

    .emergency-code {
        background: white;
        border-radius: var(--radius-small);
        padding: var(--space-md);
        border: 1px solid #e5e7eb;
        font-family: 'Courier New', monospace;
        text-align: center;
        font-weight: bold;
        color: var(--primary);
        margin: var(--space-md) 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .plan-card {
            margin-bottom: 20px;
        }

        .plan-header {
            padding: 40px 16px 12px 16px;
        }

        .plan-badge, .plan-status {
            top: 8px;
            right: 8px;
            padding: 4px 10px;
            font-size: 10px;
        }

        .price-main {
            font-size: 28px;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne identique à emploi-temps -->
        <div class="emploi-temps-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="emploi-stat-icon me-4">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h1 class="mb-1">Limite d'Abonnement Atteinte</h1>
                        <p class="mb-0 opacity-75">Votre établissement a atteint les limites de votre plan actuel KLASSCI</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="mailto:klassci@africandigitconsulting.com?subject=Upgrade Abonnement KLASSCI" class="btn-acasi success">
                        <i class="fas fa-arrow-up me-2"></i>Upgrader
                    </a>
                    <a href="tel:+22505954598430" class="btn-acasi primary">
                        <i class="fas fa-phone me-2"></i>Appeler
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques d'utilisation (identique style emploi-temps) -->
        <div class="kpi-grid mb-xl">
            <div class="card-moderne emploi-stat-card danger">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $stats['total_users'] ?? 0 }} / {{ $config['max_users'] ?? 0 }}</div>
                    <div class="emploi-stat-label">Utilisateurs</div>
                    <div class="usage-progress">
                        <div class="progress">
                            <div class="progress-bar bg-danger" style="width: {{ min(100, ($stats['total_users'] ?? 0) / max(1, $config['max_users'] ?? 1) * 100) }}%"></div>
                        </div>
                        <span class="usage-text">{{ number_format(($stats['total_users'] ?? 0) / max(1, $config['max_users'] ?? 1) * 100, 1) }}% utilisé</span>
                    </div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card danger">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $stats['total_inscriptions_current_year'] ?? 0 }} / {{ $config['max_inscriptions_per_year'] ?? 0 }}</div>
                    <div class="emploi-stat-label">Inscriptions {{ $stats['current_year_name'] ?? '' }}</div>
                    <div class="usage-progress">
                        <div class="progress">
                            <div class="progress-bar bg-danger" style="width: {{ min(100, ($stats['total_inscriptions_current_year'] ?? 0) / max(1, $config['max_inscriptions_per_year'] ?? 1) * 100) }}%"></div>
                        </div>
                        <span class="usage-text">{{ number_format(($stats['total_inscriptions_current_year'] ?? 0) / max(1, $config['max_inscriptions_per_year'] ?? 1) * 100, 1) }}% utilisé</span>
                    </div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card info">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="emploi-stat-value">{{ $config['plan_name'] ?? 'Plan Standard' }}</div>
                    <div class="emploi-stat-label">Plan Actuel</div>
                </div>
            </div>
            <div class="card-moderne emploi-stat-card warning">
                <div class="p-lg">
                    <div class="emploi-stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="emploi-stat-value">{{ count($reasons ?? []) }}</div>
                    <div class="emploi-stat-label">Limites Dépassées</div>
                </div>
            </div>
        </div>

        <!-- Alerts des raisons -->
        @if($reasons ?? false)
            @foreach($reasons as $reason)
                <div class="alert alert-danger alert-dismissible fade show mb-lg" role="alert">
                    <i class="fas fa-times-circle me-2"></i>{{ $reason }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endforeach
        @endif

        <div class="row">
            <!-- Main content -->
            <div class="col-lg-8">
                <div class="card-moderne">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-crown me-2"></i>Plans d'Abonnement KLASSCI
                        </h5>
                        <div class="emploi-view-toggle">
                            <a href="https://presentation.klassci.com" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-external-link-alt me-2"></i>Voir Détails
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Plans élégants avec style KLASSCI -->
                        <div class="row">
                            <!-- Plan Essentiel -->
                            <div class="col-md-4 mb-4">
                                <div class="plan-card {{ ($config['plan_name'] ?? '') === 'Plan Essentiel' ? 'current-plan' : '' }}">
                                    @if(($config['plan_name'] ?? '') === 'Plan Essentiel')
                                        <div class="plan-status">Plan Actuel</div>
                                    @endif
                                    <div class="plan-header">
                                        <h4 class="plan-title">Plan Essentiel</h4>
                                        <div class="plan-subtitle">Pour les petits établissements</div>
                                    </div>
                                    <div class="plan-pricing">
                                        <div class="price-main">1,200,000 <span class="currency">XOF</span></div>
                                        <div class="price-period">par an</div>
                                        <div class="price-monthly">ou 120,000 XOF/mois</div>
                                    </div>
                                    <div class="plan-features">
                                        <ul class="features-list">
                                            <li>4 postes de travail</li>
                                            <li>700 étudiants maximum</li>
                                            <li>20 enseignants maximum</li>
                                            <li>Support technique 6/7</li>
                                            <li>Mises à jour incluses</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Plan Pro -->
                            <div class="col-md-4 mb-4">
                                <div class="plan-card recommended {{ ($config['plan_name'] ?? '') === 'Plan Pro' ? 'current-plan' : '' }}">
                                    @if(($config['plan_name'] ?? '') === 'Plan Pro')
                                        <div class="plan-status">Plan Actuel</div>
                                    @else
                                        <div class="plan-badge">Recommandé</div>
                                    @endif
                                    <div class="plan-header">
                                        <h4 class="plan-title">Plan Pro</h4>
                                        <div class="plan-subtitle">Pour les établissements moyens</div>
                                    </div>
                                    <div class="plan-pricing">
                                        <div class="price-main">2,400,000 <span class="currency">XOF</span></div>
                                        <div class="price-period">par an</div>
                                        <div class="price-monthly">ou 240,000 XOF/mois</div>
                                    </div>
                                    <div class="plan-features">
                                        <ul class="features-list">
                                            <li>9 postes de travail</li>
                                            <li>3,000 étudiants maximum</li>
                                            <li>30 enseignants maximum</li>
                                            <li>Support technique 6/7</li>
                                            <li>Nouvelles fonctionnalités incluses</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Plan Elite -->
                            <div class="col-md-4 mb-4">
                                <div class="plan-card premium {{ ($config['plan_name'] ?? '') === 'Plan Elite' ? 'current-plan' : '' }}">
                                    @if(($config['plan_name'] ?? '') === 'Plan Elite')
                                        <div class="plan-status">Plan Actuel</div>
                                    @else
                                        <div class="plan-badge premium">Premium</div>
                                    @endif
                                    <div class="plan-header">
                                        <h4 class="plan-title">Plan Elite</h4>
                                        <div class="plan-subtitle">Pour les grands établissements</div>
                                    </div>
                                    <div class="plan-pricing">
                                        <div class="price-main">4,800,000 <span class="currency">XOF</span></div>
                                        <div class="price-period">par an</div>
                                        <div class="price-monthly">ou 480,000 XOF/mois</div>
                                    </div>
                                    <div class="plan-features">
                                        <ul class="features-list">
                                            <li>30 postes de travail</li>
                                            <li>Étudiants illimités</li>
                                            <li>Enseignants illimités</li>
                                            <li>Support technique 7/7</li>
                                            <li>Toutes fonctionnalités premium</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar avec actions -->
            <div class="col-lg-4">
                <!-- Actions rapides -->
                <div class="emploi-filter-card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-rocket me-2"></i>Actions Rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="mailto:klassci@africandigitconsulting.com?subject=Upgrade Abonnement KLASSCI&body=Bonjour,%0D%0A%0D%0AJe souhaite upgrader mon abonnement KLASSCI.%0D%0A%0D%0AÉtablissement: {{ $etablissement->nom ?? 'Non défini' }}%0D%0APlan actuel: {{ $config['plan_name'] ?? 'Non défini' }}%0D%0A%0D%0AMerci de me contacter pour discuter des options disponibles.%0D%0A%0D%0ACordialement" class="btn-acasi success">
                                <i class="fas fa-arrow-up me-2"></i>Upgrader Abonnement
                            </a>
                            <a href="tel:+22505954598430" class="btn-acasi primary">
                                <i class="fas fa-phone me-2"></i>Appeler Support
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn-acasi secondary">
                                <i class="fas fa-home me-2"></i>Retour Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="emploi-filter-card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-phone me-2"></i>African Digit Consulting
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="contact-info">
                            <p class="mb-2">
                                <i class="fas fa-phone me-2 text-primary"></i>
                                <strong>+225 05 95 459 843</strong>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                <strong>klassci@africandigitconsulting.com</strong>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-globe me-2 text-primary"></i>
                                <strong>presentation.klassci.com</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Code d'urgence -->
                <div class="emploi-filter-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-key me-2"></i>Accès d'Urgence
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="emergency-section">
                            <div class="text-center mb-3">
                                <i class="fas fa-unlock-alt fa-2x text-warning mb-2"></i>
                                <h6 class="mb-2">Déblocage Temporaire</h6>
                                <p class="small mb-3">Si vous devez accéder au système en urgence, ajoutez ce code à n'importe quelle URL :</p>
                            </div>

                            <div class="emergency-code">
                                ?emergency_code=ADMIN2024EMERGENCY
                            </div>

                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Exemple : {{ url('/dashboard') }}?emergency_code=ADMIN2024EMERGENCY
                                </small>
                            </div>

                            <div class="alert alert-warning mt-3 small">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Ce code permet un accès temporaire (1h) au système, mais pas à la configuration paywall.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection