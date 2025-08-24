@extends('layouts.app')

@section('title', 'Mes Notes')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour la page notes */
    .notes-container {
        --notes-primary: var(--primary);
        --notes-secondary: var(--secondary);
        --notes-surface: var(--surface);
        --notes-border: rgba(0, 0, 0, 0.08);
    }

    .toggle-section {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-md);
        margin-bottom: var(--space-xl);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--notes-border);
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

    .notes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .note-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--notes-border);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .note-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .note-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
    }

    .note-card.excellent::before {
        background: linear-gradient(135deg, var(--success), #4caf50);
    }

    .note-card.good::before {
        background: linear-gradient(135deg, var(--warning), #ff9800);
    }

    .note-card.poor::before {
        background: linear-gradient(135deg, var(--danger), #f44336);
    }

    .note-card.absent::before {
        background: linear-gradient(135deg, var(--neutral), #9e9e9e);
    }

    .note-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: var(--space-md);
    }

    .note-matiere {
        font-weight: 700;
        font-size: var(--text-lg);
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
        line-height: 1.2;
    }

    .note-type-badge {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .note-score-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-md);
        padding: var(--space-md);
        background: rgba(var(--primary-rgb), 0.02);
        border-radius: var(--radius-medium);
        border-left: 4px solid var(--primary);
    }

    .note-score {
        display: flex;
        align-items: baseline;
        gap: var(--space-xs);
    }

    .note-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
    }

    .note-bareme {
        font-size: var(--text-lg);
        color: var(--text-secondary);
        font-weight: 500;
    }

    .note-percentage {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        font-weight: 600;
    }

    .note-absent-badge {
        background: rgba(var(--danger-rgb), 0.1);
        color: var(--danger);
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .note-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }

    .note-info-item {
        display: flex;
        flex-direction: column;
        gap: var(--space-xs);
    }

    .note-info-label {
        font-size: var(--text-xs);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .note-info-value {
        font-size: var(--text-sm);
        color: var(--text-primary);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .note-info-value i {
        color: var(--primary);
        font-size: var(--text-xs);
    }

    .note-comment {
        background: rgba(var(--neutral-rgb), 0.05);
        padding: var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-sm);
        color: var(--text-secondary);
        line-height: 1.4;
        margin-top: var(--space-md);
        font-style: italic;
    }

    .moyenne-section {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        text-align: center;
    }

    .moyenne-title {
        font-size: var(--text-lg);
        font-weight: 600;
        margin-bottom: var(--space-md);
        opacity: 0.9;
    }

    .moyenne-value {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: var(--space-sm);
    }

    .moyenne-subtitle {
        font-size: var(--text-sm);
        opacity: 0.8;
    }

    .no-notes {
        text-align: center;
        padding: var(--space-xl) var(--space-md);
        background: white;
        border-radius: var(--radius-large);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .no-notes-icon {
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

    .no-notes-title {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }

    .no-notes-text {
        color: var(--text-secondary);
        font-size: var(--text-base);
        line-height: 1.6;
        margin: 0;
    }

    /* Animation d'apparition */
    .note-card {
        animation: slideInUp 0.6s ease-out;
        animation-fill-mode: both;
    }

    .note-card:nth-child(1) { animation-delay: 0.1s; }
    .note-card:nth-child(2) { animation-delay: 0.2s; }
    .note-card:nth-child(3) { animation-delay: 0.3s; }
    .note-card:nth-child(4) { animation-delay: 0.4s; }
    .note-card:nth-child(5) { animation-delay: 0.5s; }
    .note-card:nth-child(6) { animation-delay: 0.6s; }

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
        .notes-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .note-info {
            grid-template-columns: 1fr;
            gap: var(--space-sm);
        }

        .toggle-buttons {
            flex-direction: column;
        }

        .toggle-btn {
            text-align: center;
        }

        .note-score-section {
            flex-direction: column;
            gap: var(--space-sm);
            text-align: center;
        }
    }

    /* States des notes */
    .note-content {
        display: none;
    }

    .note-content.active {
        display: block;
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi notes-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-graduation-cap me-3"></i>
                        Mes Notes
                    </h1>
                    <p class="header-subtitle">
                        Consultez vos résultats et votre progression académique
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                        <i class="fas fa-chart-line me-2"></i>
                        Année {{ date('Y') }}-{{ date('Y')+1 }}
                    </div>
                </div>
            </div>
        </div>

        @if($notes->isEmpty())
            <div class="no-notes">
                <div class="no-notes-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="no-notes-title">Aucune note disponible</div>
                <p class="no-notes-text">
                    Aucune note n'est disponible pour le moment. Les notes apparaîtront ici dès qu'elles seront saisies par vos enseignants.
                </p>
            </div>
        @else
            <!-- Moyenne générale -->
            <div class="moyenne-section">
                <div class="moyenne-title">Moyenne Générale</div>
                @php
                    $totalPoints = 0;
                    $totalCoeff = 0;
                    foreach($notes as $note) {
                        if(!$note->is_absent) {
                            $totalPoints += ($note->note * $note->evaluation->coefficient);
                            $totalCoeff += $note->evaluation->coefficient;
                        }
                    }
                    $moyenne = $totalCoeff > 0 ? $totalPoints / $totalCoeff : 0;
                @endphp
                <div class="moyenne-value">{{ number_format($moyenne, 2) }}/20</div>
                <div class="moyenne-subtitle">{{ $notes->count() }} note(s) • {{ $totalCoeff }} coefficients</div>
            </div>

            <!-- Section Toggle -->
            <div class="toggle-section">
                <div class="toggle-buttons">
                    <button class="toggle-btn active" data-target="toutes">
                        <i class="fas fa-list me-2"></i>
                        Toutes les notes
                    </button>
                    <button class="toggle-btn" data-target="par-matiere">
                        <i class="fas fa-layer-group me-2"></i>
                        Par matière
                    </button>
                    <button class="toggle-btn" data-target="recentes">
                        <i class="fas fa-clock me-2"></i>
                        Récentes
                    </button>
                </div>
            </div>

            <!-- Toutes les notes -->
            <div class="note-content active" id="toutes">
                <div class="card-moderne mb-lg">
                    <div class="section-card-header">
                        <h6 class="section-card-title">
                            <i class="fas fa-list"></i>
                            Toutes les notes
                        </h6>
                    </div>
                    <div class="section-card-body">
                        <div class="notes-grid">
                            @foreach($notes as $note)
                                @php
                                    $noteClass = '';
                                    if($note->is_absent) {
                                        $noteClass = 'absent';
                                    } else {
                                        $percentage = ($note->note / $note->evaluation->bareme) * 100;
                                        if($percentage >= 80) $noteClass = 'excellent';
                                        elseif($percentage >= 60) $noteClass = 'good';
                                        else $noteClass = 'poor';
                                    }
                                @endphp
                                <div class="note-card {{ $noteClass }}">
                                    <div class="note-header">
                                        <div>
                                            <div class="note-matiere">{{ $note->evaluation->matiere->name }}</div>
                                            <div class="note-type-badge">{{ ucfirst($note->evaluation->type) }}</div>
                                        </div>
                                    </div>

                                    <div class="note-score-section">
                                        @if($note->is_absent)
                                            <div class="note-absent-badge">Absent</div>
                                        @else
                                            <div class="note-score">
                                                <span class="note-value">{{ $note->note }}</span>
                                                <span class="note-bareme">/{{ $note->evaluation->bareme }}</span>
                                            </div>
                                            <div class="note-percentage">
                                                {{ number_format(($note->note / $note->evaluation->bareme) * 100, 1) }}%
                                            </div>
                                        @endif
                                    </div>

                                    <div class="note-info">
                                        <div class="note-info-item">
                                            <div class="note-info-label">Date</div>
                                            <div class="note-info-value">
                                                <i class="fas fa-calendar"></i>
                                                {{ $note->evaluation->date_evaluation->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="note-info-item">
                                            <div class="note-info-label">Coefficient</div>
                                            <div class="note-info-value">
                                                <i class="fas fa-weight-hanging"></i>
                                                {{ $note->evaluation->coefficient }}
                                            </div>
                                        </div>
                                        <div class="note-info-item">
                                            <div class="note-info-label">Note pondérée</div>
                                            <div class="note-info-value">
                                                <i class="fas fa-calculator"></i>
                                                @if(!$note->is_absent)
                                                    {{ number_format(($note->note * $note->evaluation->coefficient), 2) }}
                                                @else
                                                    0
                                                @endif
                                            </div>
                                        </div>
                                        <div class="note-info-item">
                                            <div class="note-info-label">Ajoutée il y a</div>
                                            <div class="note-info-value">
                                                <i class="fas fa-history"></i>
                                                {{ $note->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($note->commentaire)
                                        <div class="note-comment">
                                            <i class="fas fa-quote-left me-2"></i>
                                            {{ $note->commentaire }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes par matière -->
            <div class="note-content" id="par-matiere">
                <div class="card-moderne mb-lg">
                    <div class="section-card-header">
                        <h6 class="section-card-title">
                            <i class="fas fa-layer-group"></i>
                            Notes par matière
                        </h6>
                    </div>
                    <div class="section-card-body">
                        @php
                            $notesParMatiere = $notes->groupBy('evaluation.matiere.name');
                        @endphp
                        
                        @foreach($notesParMatiere as $matiere => $notesMatiere)
                            <div class="mb-lg">
                                <h5 class="mb-md" style="color: var(--primary); font-weight: 700;">
                                    <i class="fas fa-book me-2"></i>
                                    {{ $matiere }}
                                    <span class="badge" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-size: var(--text-xs);">
                                        {{ $notesMatiere->count() }} note(s)
                                    </span>
                                </h5>
                                <div class="notes-grid">
                                    @foreach($notesMatiere as $note)
                                        @php
                                            $noteClass = '';
                                            if($note->is_absent) {
                                                $noteClass = 'absent';
                                            } else {
                                                $percentage = ($note->note / $note->evaluation->bareme) * 100;
                                                if($percentage >= 80) $noteClass = 'excellent';
                                                elseif($percentage >= 60) $noteClass = 'good';
                                                else $noteClass = 'poor';
                                            }
                                        @endphp
                                        <div class="note-card {{ $noteClass }}">
                                            <div class="note-header">
                                                <div>
                                                    <div class="note-matiere">{{ $note->evaluation->titre }}</div>
                                                    <div class="note-type-badge">{{ ucfirst($note->evaluation->type) }}</div>
                                                </div>
                                            </div>

                                            <div class="note-score-section">
                                                @if($note->is_absent)
                                                    <div class="note-absent-badge">Absent</div>
                                                @else
                                                    <div class="note-score">
                                                        <span class="note-value">{{ $note->note }}</span>
                                                        <span class="note-bareme">/{{ $note->evaluation->bareme }}</span>
                                                    </div>
                                                    <div class="note-percentage">
                                                        {{ number_format(($note->note / $note->evaluation->bareme) * 100, 1) }}%
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="note-info">
                                                <div class="note-info-item">
                                                    <div class="note-info-label">Date</div>
                                                    <div class="note-info-value">
                                                        <i class="fas fa-calendar"></i>
                                                        {{ $note->evaluation->date_evaluation->format('d/m/Y') }}
                                                    </div>
                                                </div>
                                                <div class="note-info-item">
                                                    <div class="note-info-label">Coefficient</div>
                                                    <div class="note-info-value">
                                                        <i class="fas fa-weight-hanging"></i>
                                                        {{ $note->evaluation->coefficient }}
                                                    </div>
                                                </div>
                                            </div>

                                            @if($note->commentaire)
                                                <div class="note-comment">
                                                    <i class="fas fa-quote-left me-2"></i>
                                                    {{ $note->commentaire }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Notes récentes -->
            <div class="note-content" id="recentes">
                <div class="card-moderne mb-lg">
                    <div class="section-card-header">
                        <h6 class="section-card-title">
                            <i class="fas fa-clock"></i>
                            Notes récentes
                        </h6>
                    </div>
                    <div class="section-card-body">
                        @php
                            $notesRecentes = $notes->sortByDesc('created_at')->take(6);
                        @endphp
                        <div class="notes-grid">
                            @foreach($notesRecentes as $note)
                                @php
                                    $noteClass = '';
                                    if($note->is_absent) {
                                        $noteClass = 'absent';
                                    } else {
                                        $percentage = ($note->note / $note->evaluation->bareme) * 100;
                                        if($percentage >= 80) $noteClass = 'excellent';
                                        elseif($percentage >= 60) $noteClass = 'good';
                                        else $noteClass = 'poor';
                                    }
                                @endphp
                                <div class="note-card {{ $noteClass }}">
                                    <div class="note-header">
                                        <div>
                                            <div class="note-matiere">{{ $note->evaluation->matiere->name }}</div>
                                            <div class="note-type-badge">{{ ucfirst($note->evaluation->type) }}</div>
                                        </div>
                                    </div>

                                    <div class="note-score-section">
                                        @if($note->is_absent)
                                            <div class="note-absent-badge">Absent</div>
                                        @else
                                            <div class="note-score">
                                                <span class="note-value">{{ $note->note }}</span>
                                                <span class="note-bareme">/{{ $note->evaluation->bareme }}</span>
                                            </div>
                                            <div class="note-percentage">
                                                {{ number_format(($note->note / $note->evaluation->bareme) * 100, 1) }}%
                                            </div>
                                        @endif
                                    </div>

                                    <div class="note-info">
                                        <div class="note-info-item">
                                            <div class="note-info-label">Date évaluation</div>
                                            <div class="note-info-value">
                                                <i class="fas fa-calendar"></i>
                                                {{ $note->evaluation->date_evaluation->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="note-info-item">
                                            <div class="note-info-label">Ajoutée</div>
                                            <div class="note-info-value">
                                                <i class="fas fa-history"></i>
                                                {{ $note->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($note->commentaire)
                                        <div class="note-comment">
                                            <i class="fas fa-quote-left me-2"></i>
                                            {{ $note->commentaire }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
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
        // Gestion du toggle entre les vues de notes
        const toggleButtons = document.querySelectorAll('.toggle-btn');
        const noteContents = document.querySelectorAll('.note-content');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const target = this.dataset.target;

                // Update button states
                toggleButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Update content visibility
                noteContents.forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(target).classList.add('active');
            });
        });

        // Animation d'apparition progressive des cartes de note
        const noteCards = document.querySelectorAll('.note-card');
        noteCards.forEach((card, index) => {
            card.style.animationDelay = `${(index * 0.1) + 0.1}s`;
        });

        // Effet hover amélioré
        noteCards.forEach(card => {
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
