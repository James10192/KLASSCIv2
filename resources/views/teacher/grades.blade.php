@extends('layouts.app')

@section('title', 'Gestion des notes - Enseignant')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-edit me-2"></i>Gestion des notes</h1>
            <p class="header-subtitle">Gérer les évaluations et les notes de vos étudiants</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('teacher.dashboard') }}" class="btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
            <a href="{{ route('esbtp.evaluations.create') }}" class="btn-primary">
                <i class="fas fa-plus-circle me-1"></i>Nouvelle évaluation
            </a>
        </div>
    </div>

    <div class="main-content">
        <!-- Statistiques rapides -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $evaluations->total() ?? 0 }}</div>
                    <div class="stat-label">Évaluations totales</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $evaluations->where('is_published', true)->count() }}</div>
                    <div class="stat-label">Évaluations publiées</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $recentGrades->count() ?? 0 }}</div>
                    <div class="stat-label">Notes récentes</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $evaluations->where('is_published', false)->count() }}</div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>
        </div>

        <div class="grades-layout">
            <!-- Mes évaluations -->
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-clipboard-list"></i>
                        Mes évaluations
                    </div>
                    <div class="main-card-subtitle">{{ $evaluations->total() }} évaluation(s) au total</div>
                </div>
                <div class="main-card-body">
                    @if($evaluations->count() > 0)
                        <div class="evaluations-grid">
                            @foreach($evaluations as $evaluation)
                            <div class="evaluation-card">
                                <div class="evaluation-header">
                                    <div class="evaluation-type">
                                        <span class="type-badge type-{{ $evaluation->type }}">
                                            {{ ucfirst($evaluation->type) }}
                                        </span>
                                    </div>
                                    <div class="evaluation-date">
                                        {{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format('d/m/Y') : 'Non définie' }}
                                    </div>
                                </div>
                                
                                <div class="evaluation-content">
                                    <h4 class="evaluation-title">{{ $evaluation->titre }}</h4>
                                    <div class="evaluation-details">
                                        <div class="detail-item">
                                            <i class="fas fa-users text-muted"></i>
                                            <span>{{ $evaluation->classe->name ?? 'Non définie' }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-book text-muted"></i>
                                            <span>{{ $evaluation->matiere->name ?? 'Non définie' }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-award text-muted"></i>
                                            <span>{{ $evaluation->bareme ?? '20' }} pts</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="evaluation-footer">
                                    <div class="evaluation-status">
                                        @if($evaluation->is_published)
                                            <span class="status-badge published">
                                                <i class="fas fa-eye"></i> Publiée
                                            </span>
                                        @else
                                            <span class="status-badge draft">
                                                <i class="fas fa-eye-slash"></i> Brouillon
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="evaluation-actions">
                                        <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn-action primary">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                        @if($evaluation->is_published)
                                        <a href="{{ route('esbtp.notes.create', ['evaluation_id' => $evaluation->id]) }}" class="btn-action success">
                                            <i class="fas fa-edit"></i> Notes
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-container">
                            {{ $evaluations->links() }}
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>Aucune évaluation</h3>
                            <p>Vous n'avez pas encore créé d'évaluations.</p>
                            <a href="{{ route('esbtp.evaluations.create') }}" class="btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Créer une évaluation
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes récentes -->
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-chart-bar"></i>
                        Notes récemment saisies
                    </div>
                    <div class="main-card-subtitle">Activité récente</div>
                </div>
                <div class="main-card-body">
                    @if($recentGrades->count() > 0)
                        <div class="recent-grades-list">
                            @foreach($recentGrades as $note)
                            <div class="grade-item">
                                <div class="grade-student">
                                    <div class="student-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="student-info">
                                        <div class="student-name">
                                            {{ $note->etudiant->nom ?? '' }} {{ $note->etudiant->prenoms ?? '' }}
                                        </div>
                                        <div class="student-class">
                                            {{ $note->evaluation->classe->name ?? 'Classe inconnue' }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grade-details">
                                    <div class="grade-evaluation">
                                        {{ $note->evaluation->titre ?? 'Évaluation inconnue' }}
                                    </div>
                                    <div class="grade-score">
                                        <span class="score">{{ $note->note }}</span>
                                        <span class="max-score">/{{ $note->evaluation->bareme ?? 20 }}</span>
                                    </div>
                                </div>
                                
                                <div class="grade-date">
                                    <i class="fas fa-clock text-muted"></i>
                                    {{ $note->created_at ? $note->created_at->diffForHumans() : 'Date inconnue' }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state small">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Aucune note récente</h4>
                            <p>Commencez à saisir des notes pour voir l'activité ici.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
/* Layout vertical pour grades */
.grades-layout {
    display: flex;
    flex-direction: column;
    gap: var(--space-xl);
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-lg);
    margin-bottom: var(--space-xl);
}

.stat-card {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-lg);
    background: var(--card-background);
    border-radius: var(--radius-medium);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-subtle);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-medium);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-icon.bg-primary { background: var(--primary); }
.stat-icon.bg-success { background: var(--success); }
.stat-icon.bg-info { background: var(--info); }
.stat-icon.bg-warning { background: var(--warning); }

.stat-content {
    flex-grow: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1;
    margin-bottom: var(--space-xs);
}

.stat-label {
    color: var(--muted);
    font-size: var(--text-small);
    font-weight: 500;
}

.evaluations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--space-lg);
}

.evaluation-card {
    background: var(--card-background);
    border: 1px solid var(--border);
    border-radius: var(--radius-medium);
    padding: var(--space-lg);
    transition: all 0.2s ease;
}

.evaluation-card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}

.evaluation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-md);
}

.type-badge {
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-large);
    font-size: var(--text-small);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.type-badge.type-examen {
    background: rgba(var(--danger-rgb), 0.1);
    color: var(--danger);
}

.type-badge.type-devoir {
    background: rgba(var(--warning-rgb), 0.1);
    color: var(--warning);
}

.type-badge.type-projet {
    background: rgba(var(--info-rgb), 0.1);
    color: var(--info);
}

.type-badge.type-tp {
    background: rgba(var(--success-rgb), 0.1);
    color: var(--success);
}

.evaluation-date {
    color: var(--muted);
    font-size: var(--text-small);
    font-weight: 500;
}

.evaluation-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 var(--space-md) 0;
    line-height: 1.3;
}

.evaluation-details {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
    margin-bottom: var(--space-lg);
}

.detail-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    color: var(--muted);
    font-size: var(--text-small);
}

.detail-item i {
    width: 16px;
    text-align: center;
}

.evaluation-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-badge {
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
}

.status-badge.published {
    background: rgba(var(--success-rgb), 0.1);
    color: var(--success);
}

.status-badge.draft {
    background: rgba(var(--muted-rgb), 0.1);
    color: var(--muted);
}

.evaluation-actions {
    display: flex;
    gap: var(--space-sm);
}

.btn-action {
    padding: var(--space-sm) var(--space-md);
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
}

.btn-action.primary {
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary);
    border: 1px solid rgba(var(--primary-rgb), 0.2);
}

.btn-action.primary:hover {
    background: var(--primary);
    color: white;
}

.btn-action.success {
    background: rgba(var(--success-rgb), 0.1);
    color: var(--success);
    border: 1px solid rgba(var(--success-rgb), 0.2);
}

.btn-action.success:hover {
    background: var(--success);
    color: white;
}

.recent-grades-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.grade-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md);
    background: rgba(var(--muted-rgb), 0.02);
    border-radius: var(--radius-small);
    border: 1px solid var(--border);
}

.grade-student {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    min-width: 0;
    flex: 1;
}

.student-avatar {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-medium);
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.student-info {
    min-width: 0;
}

.student-name {
    font-weight: 600;
    color: var(--text);
    font-size: var(--text-small);
}

.student-class {
    color: var(--muted);
    font-size: var(--text-small);
}

.grade-details {
    flex: 1;
    min-width: 0;
}

.grade-evaluation {
    font-weight: 500;
    color: var(--text);
    font-size: var(--text-small);
    margin-bottom: var(--space-xs);
}

.grade-score {
    color: var(--muted);
    font-size: var(--text-small);
}

.score {
    font-weight: 700;
    color: var(--primary);
    font-size: 1rem;
}

.grade-date {
    color: var(--muted);
    font-size: var(--text-small);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    flex-shrink: 0;
}

.pagination-container {
    margin-top: var(--space-xl);
    padding-top: var(--space-lg);
    border-top: 1px solid var(--border);
}

.empty-state.small {
    padding: var(--space-lg);
    text-align: center;
}

.empty-state.small i {
    font-size: 2rem;
    color: var(--muted);
    margin-bottom: var(--space-sm);
}

.empty-state.small h4 {
    font-size: 1.1rem;
    color: var(--text);
    margin: 0 0 var(--space-sm) 0;
}

.empty-state.small p {
    color: var(--muted);
    margin: 0;
}

@media (max-width: 768px) {
    .grades-layout {
        gap: var(--space-lg);
    }
    
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--space-md);
    }
    
    .evaluations-grid {
        grid-template-columns: 1fr;
        gap: var(--space-md);
    }
    
    .evaluation-footer {
        flex-direction: column;
        gap: var(--space-sm);
        align-items: stretch;
    }
    
    .evaluation-actions {
        justify-content: center;
    }
    
    .grade-item {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        gap: var(--space-sm);
    }
    
    .main-card-body {
        padding: var(--space-md);
    }
}
</style> 