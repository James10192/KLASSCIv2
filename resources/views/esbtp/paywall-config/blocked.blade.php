@extends('layouts.app')

@section('title', 'Accès Bloqué - Service Technique Requis')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles identiques à emploi-temps.index et upgrade */
    .emploi-temps-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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

    .emploi-stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .emploi-stat-label {
        color: var(--text-secondary);
        font-weight: 500;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .plans-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: var(--space-lg);
        margin-top: var(--space-xl);
    }

    .plan-card {
        background: white;
        border: 2px solid var(--border-light);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .plan-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, var(--accent-blue), var(--primary));
        border-radius: var(--radius-large) var(--radius-large) 0 0;
    }

    .plan-card:hover {
        border-color: var(--accent-blue);
        box-shadow: 0 12px 40px rgba(33, 150, 243, 0.15);
        transform: translateY(-4px);
    }

    .plan-card.current {
        border-color: var(--accent-blue);
        background: linear-gradient(135deg, #f8fbff, #ffffff);
        position: relative;
    }

    .plan-card.current::after {
        content: 'PLAN ACTUEL';
        position: absolute;
        top: 20px;
        right: -30px;
        background: var(--accent-blue);
        color: white;
        padding: 5px 40px;
        font-size: 0.75rem;
        font-weight: 700;
        transform: rotate(45deg);
        letter-spacing: 1px;
    }

    .plan-header {
        text-align: center;
        margin-bottom: var(--space-lg);
        position: relative;
        z-index: 2;
    }

    .plan-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .plan-price {
        font-size: 3rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.25rem;
    }

    .plan-currency {
        color: var(--text-secondary);
        font-size: 1rem;
        font-weight: 500;
    }

    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 var(--space-lg) 0;
    }

    .plan-features li {
        padding: var(--space-sm) 0;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        color: var(--text-primary);
    }

    .plan-features li:last-child {
        border-bottom: none;
    }

    .plan-features li i {
        color: var(--success);
        margin-right: var(--space-sm);
        font-size: 1.1rem;
    }

    .plan-footer {
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .btn-contact {
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        color: white;
        border: none;
        padding: var(--space-md) var(--space-xl);
        border-radius: var(--radius-medium);
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-contact::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }

    .btn-contact:hover::before {
        left: 100%;
    }

    .btn-contact:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(33, 150, 243, 0.3);
        color: white;
    }

    .emergency-section {
        background: linear-gradient(135deg, #fff8dc, #ffffff);
        border: 2px solid var(--warning);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin-top: var(--space-xl);
        position: relative;
    }

    .emergency-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: var(--warning);
        border-radius: var(--radius-large) var(--radius-large) 0 0;
    }

    .emergency-header {
        display: flex;
        align-items: center;
        margin-bottom: var(--space-lg);
    }

    .emergency-icon {
        width: 60px;
        height: 60px;
        background: var(--warning);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: var(--space-lg);
        font-size: 1.5rem;
        color: white;
    }

    .emergency-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .alert-box {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-top: var(--space-lg);
    }

    .alert-box.danger {
        background: #f8d7da;
        border-color: #f5c6cb;
    }

    .blocked-reasons {
        background: white;
        border: 2px solid var(--danger);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin: var(--space-lg) 0;
    }

    .blocked-reasons h5 {
        color: var(--danger);
        font-weight: 700;
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
    }

    .blocked-reasons h5 i {
        margin-right: var(--space-sm);
    }

    .blocked-reasons ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .blocked-reasons li {
        padding: var(--space-sm) 0;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        color: var(--text-primary);
    }

    .blocked-reasons li:last-child {
        border-bottom: none;
    }

    .blocked-reasons li i {
        margin-right: var(--space-sm);
    }

    .contact-info-section {
        margin-top: var(--space-xl);
        padding-top: var(--space-xl);
        border-top: 1px solid var(--border-light);
    }

    .contact-card {
        background: linear-gradient(135deg, #f8fbff, #ffffff);
        border: 2px solid var(--accent-blue);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        position: relative;
        overflow: hidden;
    }

    .contact-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, var(--accent-blue), var(--primary));
        border-radius: var(--radius-large) var(--radius-large) 0 0;
    }

    .contact-header {
        text-align: center;
        margin-bottom: var(--space-xl);
    }

    .contact-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-lg);
        font-size: 2rem;
        color: white;
        box-shadow: 0 8px 25px rgba(33, 150, 243, 0.3);
    }

    .contact-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .contact-subtitle {
        color: var(--text-secondary);
        font-size: 1.1rem;
        margin: 0;
    }

    .contact-item {
        background: white;
        border: 1px solid var(--border-light);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        height: 100%;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .contact-item:hover {
        border-color: var(--accent-blue);
        box-shadow: 0 4px 15px rgba(33, 150, 243, 0.1);
        transform: translateY(-2px);
    }

    .contact-item-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
        flex-shrink: 0;
    }

    .contact-item-content h6 {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .contact-link {
        color: var(--accent-blue);
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .contact-link:hover {
        color: var(--primary);
        text-decoration: underline;
    }

    .contact-services {
        background: white;
        border: 1px solid var(--border-light);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-top: var(--space-lg);
    }

    .contact-services h6 {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: var(--space-md);
    }

    .services-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .service-tag {
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 500;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .plans-container {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .plan-price {
            font-size: 2.5rem;
        }

        .emploi-stat-value {
            font-size: 2rem;
        }

        .emergency-header {
            flex-direction: column;
            text-align: center;
        }

        .emergency-icon {
            margin-right: 0;
            margin-bottom: var(--space-md);
        }

        .contact-item {
            flex-direction: column;
            text-align: center;
            gap: var(--space-sm);
        }

        .contact-title {
            font-size: 1.5rem;
        }

        .services-tags {
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <!-- Header principal -->
        <div class="emploi-temps-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="h2 mb-2">
                        <i class="fas fa-shield-alt me-2"></i>
                        Accès Restreint - Service Technique Requis
                    </h1>
                    <p class="mb-0 opacity-90">
                        Cette section est réservée exclusivement au service technique d'African Digit Consulting
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="emploi-temps-header-accent">
                        <i class="fas fa-tools fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques actuelles -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card-moderne emploi-stat-card danger">
                    <div class="card-body text-center">
                        <div class="emploi-stat-value text-danger">{{ $stats['total_users'] ?? 0 }}</div>
                        <div class="emploi-stat-label">Utilisateurs</div>
                        <div class="emploi-stat-sublabel">{{ $config['max_users'] ?? 'N/A' }} maximum</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card-moderne emploi-stat-card warning">
                    <div class="card-body text-center">
                        <div class="emploi-stat-value text-warning">{{ $stats['total_inscriptions_current_year'] ?? 0 }}</div>
                        <div class="emploi-stat-label">Inscriptions</div>
                        <div class="emploi-stat-sublabel">{{ $config['max_inscriptions_per_year'] ?? 'N/A' }} maximum</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card-moderne emploi-stat-card info">
                    <div class="card-body text-center">
                        <div class="emploi-stat-value text-info">
                            @if($config['subscription_end'])
                                {{ \Carbon\Carbon::parse($config['subscription_end'])->diffInDays(now()) }}j
                            @else
                                ∞
                            @endif
                        </div>
                        <div class="emploi-stat-label">Abonnement</div>
                        <div class="emploi-stat-sublabel">
                            @if($config['subscription_end'])
                                Jusqu'au {{ \Carbon\Carbon::parse($config['subscription_end'])->format('d/m/Y') }}
                            @else
                                Illimité
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card-moderne emploi-stat-card primary">
                    <div class="card-body text-center">
                        <div class="emploi-stat-value">{{ $config['plan_name'] ?? 'N/A' }}</div>
                        <div class="emploi-stat-label">Plan actuel</div>
                        <div class="emploi-stat-sublabel">{{ number_format($config['plan_price'] ?? 0, 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raisons du blocage -->
        @if(isset($reasons) && count($reasons) > 0)
        <div class="blocked-reasons">
            <h5><i class="fas fa-exclamation-triangle"></i>Raisons du blocage</h5>
            <ul>
                @foreach($reasons as $reason)
                    <li><i class="fas fa-times-circle text-danger"></i>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Plans d'abonnement -->
        <div class="plans-container">
            <!-- Plan Essentiel -->
            <div class="plan-card {{ ($config['plan_name'] ?? '') == 'Plan Essentiel' ? 'current' : '' }}">
                <div class="plan-header">
                    <div class="plan-name">Plan Essentiel</div>
                    <div class="plan-price">1,200,000</div>
                    <div class="plan-currency">XOF / an</div>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i>Installation sur 4 postes</li>
                    <li><i class="fas fa-check"></i>Capacité 700 étudiants</li>
                    <li><i class="fas fa-check"></i>Capacité 20 professeurs</li>
                    <li><i class="fas fa-check"></i>Support 6/7</li>
                    <li><i class="fas fa-check"></i>Formation incluse</li>
                    <li><i class="fas fa-check"></i>Maintenance annuelle</li>
                </ul>
                <div class="plan-footer">
                    <a href="mailto:klassci@africandigitconsulting.com?subject=Demande Plan Essentiel - {{ config('app.name') }}&body=Bonjour,%0D%0A%0D%0AJe souhaite souscrire au Plan Essentiel pour notre établissement.%0D%0A%0D%0ACordialement" class="btn-contact">
                        <i class="fas fa-envelope me-2"></i>Demander ce Plan
                    </a>
                </div>
            </div>

            <!-- Plan Pro -->
            <div class="plan-card {{ ($config['plan_name'] ?? '') == 'Plan Pro' ? 'current' : '' }}">
                <div class="plan-header">
                    <div class="plan-name">Plan Pro</div>
                    <div class="plan-price">2,400,000</div>
                    <div class="plan-currency">XOF / an</div>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i>Installation sur 9 postes</li>
                    <li><i class="fas fa-check"></i>Capacité 3,000 étudiants</li>
                    <li><i class="fas fa-check"></i>Capacité 30 professeurs</li>
                    <li><i class="fas fa-check"></i>Support 6/7</li>
                    <li><i class="fas fa-check"></i>Accès gratuit nouvelles fonctionnalités</li>
                    <li><i class="fas fa-check"></i>Formation incluse</li>
                </ul>
                <div class="plan-footer">
                    <a href="mailto:klassci@africandigitconsulting.com?subject=Demande Plan Pro - {{ config('app.name') }}&body=Bonjour,%0D%0A%0D%0AJe souhaite souscrire au Plan Pro pour notre établissement.%0D%0A%0D%0ACordialement" class="btn-contact">
                        <i class="fas fa-envelope me-2"></i>Demander ce Plan
                    </a>
                </div>
            </div>

            <!-- Plan Elite -->
            <div class="plan-card {{ ($config['plan_name'] ?? '') == 'Plan Elite' ? 'current' : '' }}">
                <div class="plan-header">
                    <div class="plan-name">Plan Elite</div>
                    <div class="plan-price">4,800,000</div>
                    <div class="plan-currency">XOF / an</div>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i>Installation sur 30 postes</li>
                    <li><i class="fas fa-check"></i>Capacité étudiants illimitée</li>
                    <li><i class="fas fa-check"></i>Capacité professeurs illimitée</li>
                    <li><i class="fas fa-check"></i>Support 7/7</li>
                    <li><i class="fas fa-check"></i>Accès gratuit nouvelles fonctionnalités</li>
                    <li><i class="fas fa-check"></i>Formation incluse</li>
                </ul>
                <div class="plan-footer">
                    <a href="mailto:klassci@africandigitconsulting.com?subject=Demande Plan Elite - {{ config('app.name') }}&body=Bonjour,%0D%0A%0D%0AJe souhaite souscrire au Plan Elite pour notre établissement.%0D%0A%0D%0ACordialement" class="btn-contact">
                        <i class="fas fa-envelope me-2"></i>Demander ce Plan
                    </a>
                </div>
            </div>
        </div>

        <!-- Section accès d'urgence -->
        <div class="emergency-section">
            <div class="emergency-header">
                <div class="emergency-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <h3 class="emergency-title">Accès d'Urgence Disponible</h3>
                    <p class="text-muted mb-0">Pour déblocage temporaire du système (1 heure)</p>
                </div>
            </div>

            <div class="alert-box">
                <p><strong><i class="fas fa-info-circle me-2"></i>Codes d'accès d'urgence :</strong></p>
                <ul>
                    <li>Les codes d'urgence sont générés par le service technique uniquement</li>
                    <li>Chaque code est temporaire et à usage unique</li>
                    <li>Contactez le service technique pour obtenir un code d'accès</li>
                </ul>

                <div class="text-center mt-3">
                    <a href="mailto:klassci@africandigitconsulting.com?subject=Demande code d'urgence - {{ config('app.name') }}&body=Bonjour,%0D%0A%0D%0AJe demande un code d'accès d'urgence pour débloquer temporairement le système.%0D%0A%0D%0ACordialement" class="btn-contact">
                        <i class="fas fa-envelope me-2"></i>Demander un Code d'Urgence
                    </a>
                </div>
            </div>

            <div class="alert-box danger">
                <p><strong><i class="fas fa-exclamation-triangle me-2"></i>Important :</strong></p>
                <ul class="mb-0">
                    <li>Ce code permet un accès temporaire au système</li>
                    <li>La configuration paywall reste accessible uniquement au service technique</li>
                    <li>Contactez le service technique pour toute modification de configuration</li>
                </ul>
            </div>
        </div>

        <!-- Actions -->
        <div class="text-center mt-4 mb-4">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg me-3">
                <i class="fas fa-home me-2"></i>
                Retour au Dashboard
            </a>

            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>
                Connexion Service Technique
            </a>
        </div>

        <!-- Contact Information -->
        <div class="contact-info-section">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact-card">
                        <div class="contact-header">
                            <div class="contact-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h4 class="contact-title">Besoin d'Assistance ?</h4>
                            <p class="contact-subtitle">Contactez notre équipe pour toute question concernant votre abonnement</p>
                        </div>

                        <div class="contact-details">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="contact-item">
                                        <div class="contact-item-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="contact-item-content">
                                            <h6>Email Support</h6>
                                            <a href="mailto:klassci@africandigitconsulting.com" class="contact-link">
                                                klassci@africandigitconsulting.com
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="contact-item">
                                        <div class="contact-item-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="contact-item-content">
                                            <h6>Téléphone</h6>
                                            <a href="tel:+22505954598843" class="contact-link">
                                                +225 05 95 459 843
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="contact-services">
                            <h6><i class="fas fa-cogs me-2"></i>Services Disponibles</h6>
                            <div class="services-tags">
                                <span class="service-tag">Renouvellement d'abonnement</span>
                                <span class="service-tag">Augmentation de quotas</span>
                                <span class="service-tag">Support technique</span>
                                <span class="service-tag">Facturation</span>
                                <span class="service-tag">Configuration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection