@extends('layouts.app')

@section('title', 'Accès Bloqué - Paywall')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header de blocage -->
                <div class="text-center mb-5">
                    <div class="blocked-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h1 class="blocked-title">Accès Limité</h1>
                    <p class="blocked-subtitle">Votre établissement a atteint ses limites d'abonnement</p>
                </div>

                <!-- Informations de blocage -->
                <div class="card-moderne mb-lg">
                    <div class="section-card-header danger">
                        <h3 class="section-card-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Limites Dépassées
                        </h3>
                    </div>
                    <div class="section-card-body">
                        @if(session('paywall_blocked') && session('error'))
                            <div class="alert alert-danger">
                                <i class="fas fa-ban me-2"></i>
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="blocked-reasons">
                            <h5>Raisons du blocage :</h5>
                            <ul class="reason-list">
                                @if($reasons ?? false)
                                    @foreach($reasons as $reason)
                                        <li><i class="fas fa-times-circle text-danger me-2"></i>{{ $reason }}</li>
                                    @endforeach
                                @else
                                    <li><i class="fas fa-times-circle text-danger me-2"></i>Limites d'abonnement dépassées</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Solutions -->
                <div class="card-moderne mb-lg">
                    <div class="section-card-header warning">
                        <h3 class="section-card-title">
                            <i class="fas fa-lightbulb"></i>
                            Solutions
                        </h3>
                    </div>
                    <div class="section-card-body">
                        <div class="solutions-grid">
                            <div class="solution-item">
                                <div class="solution-icon">
                                    <i class="fas fa-users-slash"></i>
                                </div>
                                <div class="solution-content">
                                    <h6>Réduire les utilisateurs</h6>
                                    <p>Supprimez des comptes utilisateurs inutiles (enseignants, coordinateurs, secrétaires)</p>
                                </div>
                            </div>

                            <div class="solution-item">
                                <div class="solution-icon">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="solution-content">
                                    <h6>Gérer les inscriptions</h6>
                                    <p>Archivez les inscriptions inactives ou supprimez les doublons pour l'année courante</p>
                                </div>
                            </div>

                            <div class="solution-item">
                                <div class="solution-icon">
                                    <i class="fas fa-level-up-alt"></i>
                                </div>
                                <div class="solution-content">
                                    <h6>Augmenter les limites</h6>
                                    <p>Contactez l'administrateur pour augmenter vos quotas d'abonnement</p>
                                </div>
                            </div>

                            <div class="solution-item">
                                <div class="solution-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="solution-content">
                                    <h6>Renouveler l'abonnement</h6>
                                    <p>Si votre abonnement a expiré, procédez au renouvellement</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accès d'urgence -->
                <div class="card-moderne mb-lg">
                    <div class="section-card-header info">
                        <h3 class="section-card-title">
                            <i class="fas fa-key"></i>
                            Accès d'Urgence Administrateur
                        </h3>
                    </div>
                    <div class="section-card-body">
                        <div class="emergency-access">
                            <p><strong>Pour l'administrateur :</strong> Si vous devez accéder à la configuration du paywall en urgence, utilisez le code d'accès :</p>

                            <div class="emergency-code-section">
                                <div class="emergency-instructions">
                                    <h6><i class="fas fa-info-circle me-2"></i>Instructions :</h6>
                                    <ol>
                                        <li>Ajoutez <code>?emergency_code=ADMIN2024EMERGENCY</code> à n'importe quelle URL</li>
                                        <li>Exemple : <code>{{ url('/dashboard') }}?emergency_code=ADMIN2024EMERGENCY</code></li>
                                        <li>L'accès sera valide pendant 1 heure</li>
                                    </ol>
                                </div>

                                <div class="quick-access-button">
                                    <a href="{{ route('esbtp.paywall-config.index') }}?emergency_code=ADMIN2024EMERGENCY" class="btn btn-warning btn-lg">
                                        <i class="fas fa-unlock me-2"></i>
                                        Accès d'Urgence Paywall
                                    </a>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Ce code doit rester confidentiel et n'être utilisé qu'en cas d'urgence.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="text-center">
                    <div class="action-buttons">
                        @auth
                            @if(auth()->user()->hasRole(['superAdmin', 'secretaire']))
                                <a href="{{ route('esbtp.paywall-config.index') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cogs me-2"></i>
                                    Configurer le Paywall
                                </a>
                            @endif
                        @endauth

                        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-home me-2"></i>
                            Retour au Dashboard
                        </a>

                        <a href="mailto:klassci@africandigitconsulting.com" class="btn btn-info btn-lg">
                            <i class="fas fa-envelope me-2"></i>
                            Contacter African Digit Consulting
                        </a>
                    </div>

                    <div class="contact-info mt-4">
                        <h6 class="text-center mb-3"><strong>African Digit Consulting</strong></h6>
                        <p class="text-muted">
                            <i class="fas fa-phone me-2"></i>Service Technique :
                            <strong>+225 05 95 459 843</strong>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-envelope me-2"></i>Email :
                            <strong>klassci@africandigitconsulting.com</strong>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-building me-2"></i>Services :
                            <strong>Renouvellement • Augmentation quotas • Facturation</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS spécifique -->
<style>
.blocked-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    font-size: 3rem;
    color: white;
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
}

.blocked-title {
    font-size: 2.5rem;
    font-weight: bold;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.blocked-subtitle {
    font-size: 1.25rem;
    color: #6b7280;
    margin-bottom: 0;
}

.section-card-header.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.section-card-header.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.section-card-header.info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.emergency-access {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 1.5rem;
}

.emergency-code-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1rem 0;
    border: 1px solid #e5e7eb;
}

.emergency-instructions {
    margin-bottom: 1.5rem;
}

.emergency-instructions h6 {
    color: #374151;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.emergency-instructions ol {
    margin: 0;
    padding-left: 1.5rem;
}

.emergency-instructions li {
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.emergency-instructions code {
    background: #f3f4f6;
    color: #374151;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.quick-access-button {
    text-align: center;
}

.quick-access-button .btn {
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.quick-access-button .btn:hover {
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
}

.reason-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.reason-list li {
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 1.1rem;
}

.reason-list li:last-child {
    border-bottom: none;
}

.solutions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.solution-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.solution-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    border-color: #3b82f6;
}

.solution-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
    margin-bottom: 1rem;
}

.solution-content h6 {
    font-weight: bold;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.solution-content p {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.contact-info {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
}

.contact-info p {
    margin-bottom: 0.5rem;
}

.contact-info p:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .blocked-title {
        font-size: 2rem;
    }

    .blocked-subtitle {
        font-size: 1rem;
    }

    .solutions-grid {
        grid-template-columns: 1fr;
    }

    .action-buttons {
        flex-direction: column;
        align-items: center;
    }

    .btn-lg {
        width: 100%;
        max-width: 300px;
    }
}
</style>
@endsection