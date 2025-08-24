@extends('layouts.app')

@section('title', 'Mes Évaluations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour la page évaluations */
    .evaluations-container {
        --evaluations-primary: var(--primary);
        --evaluations-secondary: var(--secondary);
        --evaluations-surface: var(--surface);
        --evaluations-border: rgba(0, 0, 0, 0.08);
    }

    .toggle-section {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-md);
        margin-bottom: var(--space-xl);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--evaluations-border);
    }

    .toggle-buttons {
        display: flex;
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--radius-medium);
        padding: var(--space-xs);
        gap: var(--space-xs);
    }

    .toggle-btn {
        flex: 1;
        padding: var(--space-sm) var(--space-lg);
        border: none;
        border-radius: var(--radius-small);
        font-weight: 600;
        font-size: var(--text-sm);
        cursor: pointer;
        transition: all 0.3s ease;
        background: transparent;
        color: var(--text-secondary);
    }

    .toggle-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        box-shadow: 0 2px 6px rgba(var(--primary-rgb), 0.25);
    }

    .toggle-btn:hover:not(.active) {
        background: rgba(255, 255, 255, 0.5);
        color: var(--text-primary);
    }

    .evaluations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .evaluation-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--evaluations-border);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .evaluation-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .evaluation-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
    }

    .evaluation-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: var(--space-md);
    }

    .evaluation-title {
        font-weight: 700;
        font-size: var(--text-lg);
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
        line-height: 1.2;
    }

    .evaluation-matiere {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .evaluation-type-badge {
        position: absolute;
        top: var(--space-md);
        right: var(--space-md);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-xs);
        font-weight: 600;
        text-transform: uppercase;
    }

    .evaluation-type-badge.examen {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
    }

    .evaluation-type-badge.controle {
        background: rgba(var(--warning-rgb), 0.1);
        color: var(--warning);
    }

    .evaluation-type-badge.tp {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }

    .evaluation-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }

    .evaluation-info-item {
        display: flex;
        flex-direction: column;
        gap: var(--space-xs);
    }

    .evaluation-info-label {
        font-size: var(--text-xs);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .evaluation-info-value {
        font-size: var(--text-sm);
        color: var(--text-primary);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .evaluation-info-value i {
        color: var(--primary);
        font-size: var(--text-xs);
    }

    .evaluation-description {
        background: rgba(var(--neutral-rgb), 0.05);
        padding: var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-sm);
        color: var(--text-secondary);
        line-height: 1.4;
        margin-top: var(--space-md);
    }

    .evaluation-status {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        margin-top: var(--space-md);
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-small);
        font-size: var(--text-sm);
        font-weight: 600;
    }

    .evaluation-status.effectue {
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
    }

    .evaluation-status.a-venir {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
    }

    .evaluation-status.en-cours {
        background: rgba(var(--warning-rgb), 0.1);
        color: var(--warning);
    }

    .no-evaluations {
        text-align: center;
        padding: var(--space-xl) var(--space-md);
        background: white;
        border-radius: var(--radius-large);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .no-evaluations-icon {
        width: 80px;
        height: 80px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--neutral), var(--text-muted));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto var(--space-lg);
    }

    .no-evaluations-title {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }

    .no-evaluations-text {
        color: var(--text-secondary);
        font-size: var(--text-base);
        line-height: 1.6;
        margin: 0;
    }

    /* Animation d'apparition */
    .evaluation-card {
        animation: slideInUp 0.6s ease-out;
        animation-fill-mode: both;
    }

    .evaluation-card:nth-child(1) { animation-delay: 0.1s; }
    .evaluation-card:nth-child(2) { animation-delay: 0.2s; }
    .evaluation-card:nth-child(3) { animation-delay: 0.3s; }
    .evaluation-card:nth-child(4) { animation-delay: 0.4s; }
    .evaluation-card:nth-child(5) { animation-delay: 0.5s; }
    .evaluation-card:nth-child(6) { animation-delay: 0.6s; }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .evaluations-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .evaluation-info {
            grid-template-columns: 1fr;
            gap: var(--space-sm);
        }

        .toggle-buttons {
            flex-direction: column;
        }

        .toggle-btn {
            text-align: center;
        }
    }

    /* States d'évaluations */
    .evaluation-content {
        display: none;
    }

    .evaluation-content.active {
        display: block;
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi evaluations-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-tasks me-3"></i>
                        Mes Évaluations
                    </h1>
                    <p class="header-subtitle">
                        Consultez vos évaluations programmées et effectuées
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                        <i class="fas fa-calendar me-2"></i>
                        Année {{ date('Y') }}-{{ date('Y')+1 }}
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

        <!-- Section Toggle -->
        <div class="toggle-section">
            <div class="toggle-buttons">
                <button class="toggle-btn active" data-target="a-venir">
                    <i class="fas fa-clock me-2"></i>
                    À venir
                </button>
                <button class="toggle-btn" data-target="effectue">
                    <i class="fas fa-check-circle me-2"></i>
                    Effectuées
                </button>
            </div>
        </div>

        @if($evaluations->isEmpty())
            <div class="no-evaluations">
                <div class="no-evaluations-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="no-evaluations-title">Aucune évaluation disponible</div>
                <p class="no-evaluations-text">
                    Aucune évaluation n'est programmée pour le moment. Les nouvelles évaluations apparaîtront ici dès qu'elles seront créées par vos enseignants.
                </p>
            </div>
        @else
            <!-- Évaluations à venir -->
            <div class="evaluation-content active" id="a-venir">
                <div class="card-moderne mb-lg">
                    <div class="section-card-header">
                        <h6 class="section-card-title">
                            <i class="fas fa-clock"></i>
                            Évaluations à venir
                        </h6>
                    </div>
                    <div class="section-card-body">
                        @php
                            $evaluationsAVenir = collect();
                            foreach($evaluations as $type => $typeEvaluations) {
                                foreach($typeEvaluations as $evaluation) {
                                    if(\Carbon\Carbon::parse($evaluation->date_evaluation)->isFuture()) {
                                        $evaluation->type_display = $type;
                                        $evaluationsAVenir->push($evaluation);
                                    }
                                }
                            }
                            $evaluationsAVenir = $evaluationsAVenir->sortBy('date_evaluation');
                        @endphp

                        @if($evaluationsAVenir->isEmpty())
                            <div class="no-evaluations">
                                <div class="no-evaluations-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="no-evaluations-title">Aucune évaluation à venir</div>
                                <p class="no-evaluations-text">
                                    Vous n'avez aucune évaluation programmée prochainement.
                                </p>
                            </div>
                        @else
                            <div class="evaluations-grid">
                                @foreach($evaluationsAVenir as $evaluation)
                                    <div class="evaluation-card">
                                        <div class="evaluation-type-badge {{ $evaluation->type_display }}">
                                            {{ ucfirst($evaluation->type_display) }}
                                        </div>
                                        
                                        <div class="evaluation-header">
                                            <div>
                                                <div class="evaluation-title">{{ $evaluation->titre }}</div>
                                                <div class="evaluation-matiere">{{ $evaluation->matiere->name }}</div>
                                            </div>
                                        </div>

                                        <div class="evaluation-info">
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Date</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-calendar"></i>
                                                    {{ \Carbon\Carbon::parse($evaluation->date_evaluation)->format('d/m/Y') }}
                                                </div>
                                            </div>
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Durée</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $evaluation->duree_minutes ? $evaluation->duree_minutes . ' min' : 'Non définie' }}
                                                </div>
                                            </div>
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Coefficient</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-percentage"></i>
                                                    {{ $evaluation->coefficient }}
                                                </div>
                                            </div>
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Temps restant</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    @php
                                                        $daysUntil = now()->diffInDays(\Carbon\Carbon::parse($evaluation->date_evaluation), false);
                                                    @endphp
                                                    @if($daysUntil > 0)
                                                        {{ $daysUntil }} jour(s)
                                                    @elseif($daysUntil == 0)
                                                        Aujourd'hui
                                                    @else
                                                        En cours
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if($evaluation->description)
                                            <div class="evaluation-description">
                                                {{ $evaluation->description }}
                                            </div>
                                        @endif

                                        <div class="evaluation-status a-venir">
                                            <i class="fas fa-clock"></i>
                                            À venir
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Évaluations effectuées -->
            <div class="evaluation-content" id="effectue">
                <div class="card-moderne mb-lg">
                    <div class="section-card-header">
                        <h6 class="section-card-title">
                            <i class="fas fa-check-circle"></i>
                            Évaluations effectuées
                        </h6>
                    </div>
                    <div class="section-card-body">
                        @php
                            $evaluationsEffectuees = collect();
                            foreach($evaluations as $type => $typeEvaluations) {
                                foreach($typeEvaluations as $evaluation) {
                                    if(\Carbon\Carbon::parse($evaluation->date_evaluation)->isPast()) {
                                        $evaluation->type_display = $type;
                                        $evaluationsEffectuees->push($evaluation);
                                    }
                                }
                            }
                            $evaluationsEffectuees = $evaluationsEffectuees->sortByDesc('date_evaluation');
                        @endphp

                        @if($evaluationsEffectuees->isEmpty())
                            <div class="no-evaluations">
                                <div class="no-evaluations-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="no-evaluations-title">Aucune évaluation effectuée</div>
                                <p class="no-evaluations-text">
                                    Vous n'avez encore passé aucune évaluation.
                                </p>
                            </div>
                        @else
                            <div class="evaluations-grid">
                                @foreach($evaluationsEffectuees as $evaluation)
                                    <div class="evaluation-card">
                                        <div class="evaluation-type-badge {{ $evaluation->type_display }}">
                                            {{ ucfirst($evaluation->type_display) }}
                                        </div>
                                        
                                        <div class="evaluation-header">
                                            <div>
                                                <div class="evaluation-title">{{ $evaluation->titre }}</div>
                                                <div class="evaluation-matiere">{{ $evaluation->matiere->name }}</div>
                                            </div>
                                        </div>

                                        <div class="evaluation-info">
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Date</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-calendar"></i>
                                                    {{ \Carbon\Carbon::parse($evaluation->date_evaluation)->format('d/m/Y') }}
                                                </div>
                                            </div>
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Durée</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $evaluation->duree_minutes ? $evaluation->duree_minutes . ' min' : 'Non définie' }}
                                                </div>
                                            </div>
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Coefficient</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-percentage"></i>
                                                    {{ $evaluation->coefficient }}
                                                </div>
                                            </div>
                                            <div class="evaluation-info-item">
                                                <div class="evaluation-info-label">Créée il y a</div>
                                                <div class="evaluation-info-value">
                                                    <i class="fas fa-history"></i>
                                                    {{ $evaluation->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>

                                        @if($evaluation->description)
                                            <div class="evaluation-description">
                                                {{ $evaluation->description }}
                                            </div>
                                        @endif

                                        <div class="evaluation-status effectue">
                                            <i class="fas fa-check-circle"></i>
                                            Effectuée
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du toggle entre évaluations à venir et effectuées
        const toggleButtons = document.querySelectorAll('.toggle-btn');
        const evaluationContents = document.querySelectorAll('.evaluation-content');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const target = this.dataset.target;

                // Update button states
                toggleButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Update content visibility
                evaluationContents.forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(target).classList.add('active');
            });
        });

        // Animation d'apparition progressive des cartes d'évaluation
        const evaluationCards = document.querySelectorAll('.evaluation-card');
        evaluationCards.forEach((card, index) => {
            card.style.animationDelay = `${(index * 0.1) + 0.1}s`;
        });

        // Effet hover amélioré
        evaluationCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
            });
        });
    });
</script>
@endpush
