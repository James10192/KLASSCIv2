@extends('layouts.app')

@section('title', 'Gestion des notes - Enseignant')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Gestion des notes</h1>
                <p class="header-subtitle">Gérer les évaluations et les notes de vos étudiants</p>
            </div>
            <div class="header-actions">
                <span class="badge rounded-pill bg-light text-dark me-2">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $anneeEnCours->name ?? 'Année non définie' }}
                </span>
                <span class="text-muted me-3">{{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}</span>
                <a href="{{ route('teacher.dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
                <a href="{{ route('esbtp.evaluations.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle évaluation
                </a>
            </div>
        </div>
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
                                @include('teacher.partials.evaluation-card', ['evaluation' => $evaluation])
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
                            <a href="{{ route('esbtp.evaluations.create') }}" class="btn-acasi primary">
                                <i class="fas fa-plus-circle"></i> Créer une évaluation
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

    <div class="modal fade" id="teacherNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content modal-premium-shell">
                <div class="modal-body p-0" id="teacherNoteModalBody">
                    <div class="p-4 text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-2">Chargement...</div>
                    </div>
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
    position: relative;
    overflow: hidden;
    transition: all 0.25s ease;
    background-image: radial-gradient(circle at 20% 0%, rgba(4, 83, 203, 0.12), transparent 55%),
        radial-gradient(circle at 100% 20%, rgba(94, 145, 222, 0.12), transparent 50%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.65), rgba(255, 255, 255, 0.9));
}

.evaluation-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.08), rgba(94, 145, 222, 0));
    opacity: 0;
    transition: opacity 0.25s ease;
    pointer-events: none;
}

.evaluation-card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-4px);
    border-color: rgba(4, 83, 203, 0.35);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
}

.evaluation-card:hover::before {
    opacity: 1;
}

.evaluation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-md);
    gap: var(--space-md);
}

.type-badge {
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-large);
    font-size: var(--text-small);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
}

.type-badge.type-examen {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
    border: 1px solid rgba(239, 68, 68, 0.35);
}

.type-badge.type-devoir {
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.type-badge.type-projet {
    background: rgba(14, 165, 233, 0.12);
    color: #0369a1;
    border: 1px solid rgba(14, 165, 233, 0.3);
}

.type-badge.type-tp {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.type-badge.type-oral {
    background: rgba(148, 163, 184, 0.18);
    color: #475569;
    border: 1px solid rgba(148, 163, 184, 0.4);
}

.type-badge.type-controle {
    background: rgba(79, 70, 229, 0.12);
    color: #4338ca;
    border: 1px solid rgba(79, 70, 229, 0.3);
}

.type-badge.type-rattrapage {
    background: rgba(236, 72, 153, 0.12);
    color: #be185d;
    border: 1px solid rgba(236, 72, 153, 0.3);
}

.evaluation-date {
    color: var(--muted);
    font-size: var(--text-small);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    padding: 0.35rem 0.6rem;
    border-radius: var(--radius-large);
    background: rgba(148, 163, 184, 0.12);
    border: 1px solid rgba(148, 163, 184, 0.28);
}

.evaluation-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 var(--space-md) 0;
    line-height: 1.3;
}

.evaluation-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-sm);
    margin-bottom: var(--space-lg);
}

.meta-pill {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    padding: 0.4rem 0.65rem;
    border-radius: var(--radius-large);
    background: rgba(4, 83, 203, 0.08);
    color: var(--text);
    font-size: var(--text-small);
    font-weight: 500;
    border: 1px solid rgba(4, 83, 203, 0.15);
}

.meta-pill i {
    color: var(--primary);
}

.evaluation-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: var(--space-md);
    border-top: 1px solid var(--border);
    gap: var(--space-md);
    flex-wrap: wrap;
}

.evaluation-helper {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    color: #64748b;
    padding: 0.35rem 0.6rem;
    border-radius: 10px;
    background: rgba(148, 163, 184, 0.12);
    border: 1px solid rgba(148, 163, 184, 0.2);
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
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.status-badge.draft {
    background: rgba(148, 163, 184, 0.18);
    color: #475569;
    border: 1px solid rgba(148, 163, 184, 0.35);
}

.status-badge.neutral {
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
    border: 1px solid rgba(59, 130, 246, 0.25);
}

.evaluation-actions {
    display: flex;
    gap: var(--space-md);
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn-action {
    padding: 0.55rem 0.9rem;
    border-radius: var(--radius-medium);
    font-size: var(--text-small);
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    min-width: 92px;
    justify-content: center;
}

.btn-action.primary {
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary);
    border: 1px solid rgba(var(--primary-rgb), 0.2);
    box-shadow: 0 8px 16px rgba(4, 83, 203, 0.1);
}

.btn-action.primary:hover {
    background: var(--primary);
    color: white;
}

.btn-action.success {
    background: rgba(var(--success-rgb), 0.1);
    color: var(--success);
    border: 1px solid rgba(var(--success-rgb), 0.2);
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.12);
}

.btn-action.disabled {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
}

.modal-premium-shell {
    border-radius: 24px;
    border: none;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.08), rgba(255, 255, 255, 0.95));
}

.modal-premium {
    padding: 2rem;
    background: radial-gradient(circle at top left, rgba(4, 83, 203, 0.08), transparent 55%),
        radial-gradient(circle at bottom right, rgba(94, 145, 222, 0.08), transparent 55%),
        #fff;
}

.modal-premium-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.modal-premium-title {
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.modal-premium-subtitle {
    color: var(--muted);
    margin: 0;
}

.modal-premium-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.modal-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.7rem;
    border-radius: 999px;
    background: rgba(4, 83, 203, 0.12);
    color: var(--primary);
    font-size: 0.85rem;
    border: 1px solid rgba(4, 83, 203, 0.2);
}

.modal-pill-neutral {
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
    border: 1px solid rgba(59, 130, 246, 0.25);
}

.modal-pill-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.25);
}

.modal-premium-body {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.modal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

.modal-section {
    background: rgba(15, 23, 42, 0.03);
    padding: 1rem;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.modal-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text);
}

.modal-premium-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.card-flash-success {
    animation: cardFlashSuccess 1.2s ease;
}

@keyframes cardFlashSuccess {
    0% {
        box-shadow: 0 0 0 rgba(16, 185, 129, 0.0);
        transform: translateY(-4px) scale(1.01);
    }
    40% {
        box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.2);
    }
    100% {
        box-shadow: 0 0 0 rgba(16, 185, 129, 0.0);
        transform: translateY(0) scale(1);
    }
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

/* Amélioration de l'empty-state pour les boutons */
.empty-state .btn-acasi {
    margin-top: var(--space-lg);
    padding: var(--space-md) var(--space-xl);
    font-size: var(--text-normal);
    font-weight: 600;
    box-shadow: var(--shadow-elevated);
    border-radius: var(--radius-medium);
}

.empty-state .btn-acasi:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.empty-state h3 {
    color: var(--text-primary);
    margin-bottom: var(--space-sm);
    font-size: 1.25rem;
    font-weight: 600;
}

.empty-state p {
    margin-bottom: 0;
    color: var(--text-secondary);
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
        justify-content: stretch;
    }
    
    .btn-action {
        width: 100%;
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('teacherNoteModal');
    const modalBody = document.getElementById('teacherNoteModalBody');
    const csrfToken = '{{ csrf_token() }}';
    const noteModalUrlTemplate = @json(route('teacher.grades.note-modal', ['evaluation' => '__id__']));
    const refreshCardUrlTemplate = @json(route('teacher.grades.card', ['evaluation' => '__id__']));
    let currentEvaluationId = null;

    function buildUrl(template, id) {
        return template.replace('__id__', id);
    }

    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 1rem;">
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.insertAdjacentHTML('afterbegin', alertHtml);
            setTimeout(() => {
                const alert = mainContent.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    }

    function refreshEvaluationCard(evaluationId) {
        const card = document.querySelector(`[data-evaluation-id="${evaluationId}"]`);
        if (!card) {
            return;
        }

        fetch(buildUrl(refreshCardUrlTemplate, evaluationId), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Refresh invalide');
            }
            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const newCard = template.content.querySelector(`[data-evaluation-id="${evaluationId}"]`) || template.content.firstElementChild;
            if (newCard) {
                card.replaceWith(newCard);
                newCard.classList.add('card-flash-success');
                setTimeout(() => {
                    newCard.classList.remove('card-flash-success');
                }, 1200);
            }
        })
        .catch(() => {
            window.location.reload();
        });
    }

    function openNoteModal(evaluationId) {
        if (!modalElement) {
            return;
        }
        currentEvaluationId = evaluationId;
        modalBody.innerHTML = `
            <div class="p-4 text-center">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted mt-2">Chargement...</div>
            </div>
        `;

        fetch(buildUrl(noteModalUrlTemplate, evaluationId), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Impossible de charger le modal');
            }
            modalBody.innerHTML = data.html;
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="p-4 text-danger">Erreur de chargement.</div>';
        });

        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-action="open-notes-modal"]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        const evaluationId = trigger.dataset.evaluationId;
        if (evaluationId) {
            openNoteModal(evaluationId);
        }
    });

    modalElement?.addEventListener('submit', function (event) {
        const form = event.target.closest('#teacherNoteForm');
        if (!form) {
            return;
        }
        event.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success === false) {
                let message = data.message || 'Erreur lors de l\'enregistrement.';
                if (!data.message && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey) {
                        message = data.errors[firstKey][0];
                    }
                }
                throw new Error(message);
            }
            return data;
        })
        .then(data => {
            showSuccessMessage(data.message || 'Note enregistrée.');
            const bsModal = bootstrap.Modal.getInstance(modalElement);
            bsModal.hide();
        })
        .catch(error => {
            const existingAlert = form.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger mt-3';
            alert.textContent = error.message;
            form.prepend(alert);
        });
    });

    modalBody?.addEventListener('change', function (event) {
        const checkbox = event.target.closest('#teacher_note_absent');
        if (!checkbox) {
            return;
        }
        const noteInput = modalBody.querySelector('input[name="note"]');
        if (!noteInput) {
            return;
        }
        if (checkbox.checked) {
            noteInput.value = '';
            noteInput.setAttribute('disabled', 'disabled');
            noteInput.removeAttribute('required');
        } else {
            noteInput.removeAttribute('disabled');
            noteInput.setAttribute('required', 'required');
        }
    });

    modalElement?.addEventListener('hidden.bs.modal', function () {
        if (currentEvaluationId) {
            refreshEvaluationCard(currentEvaluationId);
        }
    });
});
</script>
@endsection
