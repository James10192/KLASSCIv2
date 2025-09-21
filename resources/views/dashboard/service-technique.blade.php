@extends('layouts.app')

@section('title', 'Dashboard Service Technique - African Digit Consulting')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .service-technique-header {
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
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
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .stat-card {
        background: white;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 2px solid var(--border-light);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }

    .stat-card.primary::before { background: var(--primary); }
    .stat-card.success::before { background: var(--success); }
    .stat-card.info::before { background: var(--accent-blue); }
    .stat-card.warning::before { background: var(--warning); }
    .stat-card.danger::before { background: var(--danger); }

    .stat-card:hover {
        border-color: var(--accent-blue);
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
        margin-bottom: var(--space-md);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        color: var(--text-secondary);
        font-weight: 500;
        margin-bottom: var(--space-sm);
    }

    .stat-sublabel {
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .paywall-status {
        background: white;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        border: 2px solid var(--border-light);
    }

    .status-header {
        display: flex;
        align-items: center;
        margin-bottom: var(--space-md);
    }

    .status-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: var(--space-md);
        font-size: 1.25rem;
        color: white;
    }

    .status-icon.success { background: var(--success); }
    .status-icon.warning { background: var(--warning); }
    .status-icon.danger { background: var(--danger); }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .action-card {
        background: white;
        border: 2px solid var(--border-light);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        text-align: center;
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .action-card:hover {
        border-color: var(--accent-blue);
        box-shadow: 0 8px 25px rgba(33, 150, 243, 0.15);
        transform: translateY(-2px);
        text-decoration: none;
        color: inherit;
    }

    .action-icon {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        background: linear-gradient(135deg, var(--accent-blue), var(--primary));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-md);
        font-size: 1.75rem;
        color: white;
    }

    .action-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .action-description {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .emergency-codes {
        background: #fff8dc;
        border: 2px solid var(--warning);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-top: var(--space-xl);
    }

    .emergency-codes h5 {
        color: var(--warning);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
    }

    .code-item {
        background: white;
        border: 1px solid var(--border-light);
        border-radius: var(--radius-small);
        padding: var(--space-md);
        margin-bottom: var(--space-sm);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .code-value {
        font-family: monospace;
        font-weight: 600;
        color: var(--primary);
    }

    .code-expiry {
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: 1fr;
        }

        .stat-value {
            font-size: 2rem;
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
                        Gestion centralisée des établissements ESBTP-yAKRO
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
                <div class="stat-icon" style="background: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">{{ $stats['total_users'] }}</div>
                <div class="stat-label">Utilisateurs Total</div>
                <div class="stat-sublabel">{{ $paywallConfig['max_users'] }} maximum autorisés</div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon" style="background: var(--success);">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value">{{ $stats['total_students'] }}</div>
                <div class="stat-label">Étudiants</div>
                <div class="stat-sublabel">{{ $recentActivity['new_students_this_month'] }} ce mois</div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon" style="background: var(--accent-blue);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value">{{ $stats['total_teachers'] }}</div>
                <div class="stat-label">Enseignants</div>
                <div class="stat-sublabel">Personnel académique</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon" style="background: var(--warning);">
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
        <div class="card-moderne">
            <div class="card-header-moderne">
                <h3 class="card-title-moderne">
                    <i class="fas fa-school me-2"></i>
                    Établissements Gérés
                </h3>
            </div>
            <div class="card-body-moderne">
                <div class="table-responsive">
                    <table class="table table-moderne">
                        <thead>
                            <tr>
                                <th>Établissement</th>
                                <th>Branche Git</th>
                                <th>Statut</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($etablissements as $etablissement)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university text-primary me-2"></i>
                                            <strong>{{ $etablissement->nom }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ $etablissement->branch }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $etablissement->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($etablissement->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $etablissement->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('esbtp.paywall-config.index') }}" class="btn btn-sm btn-outline-primary" title="Paywall">
                                                <i class="fas fa-shield-alt"></i>
                                            </a>
                                            <a href="{{ route('esbtp.matricule-config.index') }}" class="btn btn-sm btn-outline-info" title="Matricule">
                                                <i class="fas fa-id-card"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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