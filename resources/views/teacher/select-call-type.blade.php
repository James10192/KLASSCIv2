@extends('layouts.app')

@section('title', 'Sélection Type d\'Appel')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .call-type-container {
        max-width: 800px;
        margin: 0 auto;
        padding: var(--space-lg);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .course-info-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        text-align: center;
        box-shadow: var(--shadow-card);
    }

    .course-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-md);
    }

    .course-details {
        display: flex;
        justify-content: center;
        gap: var(--space-xl);
        flex-wrap: wrap;
        margin-top: var(--space-md);
        opacity: 0.9;
    }

    .course-detail-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .workflow-progress {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: var(--shadow-card);
    }

    .progress-title {
        font-size: var(--text-large);
        font-weight: 600;
        margin-bottom: var(--space-md);
        color: var(--text-primary);
        text-align: center;
    }

    .progress-steps {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        margin-bottom: var(--space-lg);
    }

    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 2;
        background: var(--surface);
        padding: var(--space-sm);
        border-radius: var(--radius-medium);
    }

    .step-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: var(--space-xs);
        transition: all 0.3s ease;
    }

    .step-icon.completed {
        background: var(--success);
        color: white;
    }

    .step-icon.current {
        background: var(--primary);
        color: white;
        animation: pulse 2s infinite;
    }

    .step-icon.pending {
        background: var(--surface-secondary);
        color: var(--text-secondary);
        border: 2px solid var(--border);
    }

    .step-label {
        font-size: var(--text-small);
        font-weight: 500;
        color: var(--text-secondary);
        text-align: center;
    }

    .step-label.current {
        color: var(--primary);
        font-weight: 600;
    }

    .progress-line {
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--border);
        z-index: 1;
    }

    .progress-line-fill {
        height: 100%;
        background: var(--success);
        transition: width 0.5s ease;
    }

    .call-type-selection {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        box-shadow: var(--shadow-card);
    }

    .selection-title {
        font-size: var(--title-section);
        font-weight: 700;
        text-align: center;
        margin-bottom: var(--space-xl);
        color: var(--text-primary);
    }

    .call-type-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .call-type-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: var(--space-xl);
        border: 3px solid var(--border);
        border-radius: var(--radius-medium);
        background: var(--surface);
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        color: var(--text-primary);
        position: relative;
        overflow: hidden;
    }

    .call-type-btn:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
        border-color: var(--primary);
    }

    .call-type-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .call-type-btn.completed {
        border-color: var(--success);
        background: rgba(16, 185, 129, 0.1);
    }

    .call-type-icon {
        width: 80px;
        height: 80px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        margin-bottom: var(--space-md);
        transition: all 0.3s ease;
    }

    .call-type-btn:hover .call-type-icon {
        transform: scale(1.1);
    }

    .call-type-btn.start .call-type-icon {
        background: linear-gradient(135deg, var(--accent-blue), #0891b2);
        color: white;
    }

    .call-type-btn.end .call-type-icon {
        background: linear-gradient(135deg, var(--success), #059669);
        color: white;
    }

    .call-type-btn.completed .call-type-icon {
        background: var(--success);
        color: white;
    }

    .call-type-title {
        font-size: var(--text-large);
        font-weight: 700;
        margin-bottom: var(--space-sm);
    }

    .call-type-description {
        font-size: var(--text-normal);
        color: var(--text-secondary);
        text-align: center;
        line-height: 1.5;
    }

    .call-type-status {
        position: absolute;
        top: var(--space-sm);
        right: var(--space-sm);
        background: var(--success);
        color: white;
        border-radius: var(--radius-circle);
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .back-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-sm);
        padding: var(--space-md) var(--space-xl);
        background: var(--surface-secondary);
        color: var(--text-secondary);
        border: none;
        border-radius: var(--radius-medium);
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
        margin: 0 auto;
    }

    .back-button:hover {
        background: var(--border);
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    @media (max-width: 768px) {
        .call-type-buttons {
            grid-template-columns: 1fr;
        }
        
        .course-details {
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .progress-steps {
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .progress-line {
            display: none;
        }
    }
</style>
@endsection

@section('content')
<div class="call-type-container">
    <!-- Informations du cours -->
    <div class="course-info-header">
        <h1 class="course-title">
            <i class="fas fa-clipboard-list"></i>
            Gestion de la Séance
        </h1>
        <div class="course-details">
            <div class="course-detail-item">
                <i class="fas fa-book"></i>
                <span>{{ $seance->matiere->name ?? 'Matière non définie' }}</span>
            </div>
            <div class="course-detail-item">
                <i class="fas fa-users"></i>
                <span>{{ $seance->classe->name ?? 'Classe non définie' }}</span>
            </div>
            <div class="course-detail-item">
                <i class="fas fa-clock"></i>
                <span>
                    {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }} - 
                    {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
                </span>
            </div>
            <div class="course-detail-item">
                <i class="fas fa-calendar"></i>
                <span>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Progression du workflow -->
    <div class="workflow-progress">
        <h2 class="progress-title">Progression de la Séance</h2>
        <div class="progress-steps">
            <div class="progress-line">
                <div class="progress-line-fill" style="width: {{ $workflow->getProgressPercentage() }}%;"></div>
            </div>
            
            <div class="progress-step">
                <div class="step-icon {{ $workflow->attendance_start_signed ? 'completed' : ($workflow->current_step === 'attendance_start' ? 'current' : 'pending') }}">
                    @if($workflow->attendance_start_signed)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas fa-signature"></i>
                    @endif
                </div>
                <span class="step-label {{ $workflow->current_step === 'attendance_start' ? 'current' : '' }}">Émargement début</span>
            </div>

            <div class="progress-step">
                <div class="step-icon {{ $workflow->call_start_done ? 'completed' : ($workflow->current_step === 'call_start' ? 'current' : 'pending') }}">
                    @if($workflow->call_start_done)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas fa-play"></i>
                    @endif
                </div>
                <span class="step-label {{ $workflow->current_step === 'call_start' ? 'current' : '' }}">Appel Début</span>
            </div>

            <div class="progress-step">
                <div class="step-icon {{ $workflow->attendance_end_signed ? 'completed' : ($workflow->current_step === 'attendance_end' ? 'current' : 'pending') }}">
                    @if($workflow->attendance_end_signed)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas fa-signature"></i>
                    @endif
                </div>
                <span class="step-label {{ $workflow->current_step === 'attendance_end' ? 'current' : '' }}">Émargement fin</span>
            </div>

            <div class="progress-step">
                <div class="step-icon {{ $workflow->call_end_done ? 'completed' : ($workflow->current_step === 'call_end' ? 'current' : 'pending') }}">
                    @if($workflow->call_end_done)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas fa-stop"></i>
                    @endif
                </div>
                <span class="step-label {{ $workflow->current_step === 'call_end' ? 'current' : '' }}">Appel Fin</span>
            </div>

            <div class="progress-step">
                <div class="step-icon {{ $workflow->report_submitted ? 'completed' : ($workflow->current_step === 'report' ? 'current' : 'pending') }}">
                    @if($workflow->report_submitted)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas fa-file-alt"></i>
                    @endif
                </div>
                <span class="step-label {{ $workflow->current_step === 'report' ? 'current' : '' }}">Rapport</span>
            </div>
        </div>
    </div>

    <!-- Sélection du type d'appel -->
    <div class="call-type-selection">
        <h2 class="selection-title">Choisissez le type d'appel à effectuer</h2>
        
        <div class="call-type-buttons">
            <!-- Appel de début -->
            <a href="{{ $workflow->canExecuteStep('call_start') && !$workflow->call_start_done ? route('teacher.roll-call', ['seance' => $seance->id, 'type' => 'start']) : '#' }}" 
               class="call-type-btn start {{ $workflow->call_start_done ? 'completed' : ($workflow->canExecuteStep('call_start') ? '' : 'disabled') }}">
                
                @if($workflow->call_start_done)
                    <div class="call-type-status">
                        <i class="fas fa-check"></i>
                    </div>
                @endif
                
                <div class="call-type-icon">
                    @if($workflow->call_start_done)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas fa-play"></i>
                    @endif
                </div>
                
                <h3 class="call-type-title">Appel de Début</h3>
                <p class="call-type-description">
                    Effectuer l'appel des étudiants en début de cours pour marquer les présences et retards
                </p>
            </a>

            <!-- Appel de fin -->
            <a href="{{ $workflow->canExecuteStep('call_end') && !$workflow->call_end_done && $canEndCall ? route('teacher.roll-call', ['seance' => $seance->id, 'type' => 'end']) : '#' }}"
               class="call-type-btn end {{ $workflow->call_end_done ? 'completed' : ($workflow->canExecuteStep('call_end') && $canEndCall ? '' : 'disabled') }}"
               data-end-call-message="{{ $endCallMessage ?? '' }}">

                @if($workflow->call_end_done)
                    <div class="call-type-status">
                        <i class="fas fa-check"></i>
                    </div>
                @endif

                <div class="call-type-icon">
                    @if($workflow->call_end_done)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas fa-stop"></i>
                    @endif
                </div>

                <h3 class="call-type-title">Appel de Fin</h3>
                <p class="call-type-description">
                    @if(!$canEndCall && !$workflow->call_end_done)
                        <small class="text-warning"><i class="fas fa-clock me-1"></i>{{ $endCallMessage }}</small>
                    @else
                        Vérifier les présences en fin de cours et procéder aux ajustements nécessaires
                    @endif
                </p>
            </a>
        </div>

        @if($workflow->call_start_done && $workflow->call_end_done && !$workflow->report_submitted)
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Prochaine étape :</strong> Vous devez maintenant créer le rapport de cours pour terminer la séance.
                <a href="{{ route('teacher.session-report.create', $seance->id) }}" class="btn-acasi btn-acasi-primary ms-3">
                    <i class="fas fa-file-alt"></i>
                    Créer le rapport
                </a>
            </div>
        @endif

        <a href="{{ route('teacher.dashboard') }}" class="back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Retour au tableau de bord</span>
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée pour les boutons
    const buttons = document.querySelectorAll('.call-type-btn');
    buttons.forEach((button, index) => {
        button.style.animationDelay = `${index * 0.2}s`;
        button.style.animation = 'fadeInUp 0.6s ease forwards';
    });

    // Empêcher le clic sur les boutons désactivés
    document.querySelectorAll('.call-type-btn.disabled').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            // Afficher un message d'information
            let message;
            if (this.classList.contains('start')) {
                message = 'Vous devez d\'abord compléter l\'émargement.';
            } else if (this.classList.contains('end')) {
                // Récupérer le message personnalisé depuis l'attribut data
                message = this.dataset.endCallMessage || 'Vous devez d\'abord effectuer l\'appel de début.';
            }

            alert(message);
        });
    });
});

// CSS pour l'animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .call-type-btn {
        opacity: 0;
    }
`;
document.head.appendChild(style);
</script>
@endsection