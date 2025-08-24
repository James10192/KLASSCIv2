{{-- Composant pour le détail des évaluations par matière --}}
<div class="main-card mb-4">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-list-check"></i>
            Détail des évaluations
        </div>
        <div class="main-card-subtitle">Notes détaillées par matière et évaluation</div>
    </div>
    <div class="main-card-body">
        @if(count($notesByMatiere) > 0)
            <div class="evaluations-grid">
                @foreach($notesByMatiere as $matiere_id => $matiereData)
                    <div class="subject-evaluation-card">
                        <div class="subject-header {{ $matiereData['moyenne'] >= 10 ? 'success' : 'danger' }}">
                            <div class="subject-title">
                                <i class="fas fa-book"></i>
                                {{ $matiereData['matiere']->name }}
                            </div>
                            <div class="subject-average">
                                {{ number_format($matiereData['moyenne'], 2) }}/20
                            </div>
                        </div>
                        <div class="evaluations-list">
                            @foreach($matiereData['notes'] as $note)
                                <div class="evaluation-item">
                                    <div class="evaluation-info">
                                        <div class="evaluation-title">{{ $note->evaluation->title }}</div>
                                        <div class="evaluation-meta">
                                            <span class="evaluation-type">{{ $note->evaluation->type }}</span>
                                            <span class="evaluation-date">
                                                {{ $note->evaluation->date_evaluation ? $note->evaluation->date_evaluation->format('d/m/Y') : 'N/A' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="evaluation-scores">
                                        <div class="coefficient">
                                            <span class="label">Coeff</span>
                                            <span class="value">{{ $note->evaluation->coefficient }}</span>
                                        </div>
                                        <div class="score {{ (is_numeric($note->valeur) ? $note->valeur : ($note->note ?? 0)) >= 10 ? 'success' : 'danger' }}">
                                            @if(is_numeric($note->valeur))
                                                {{ $note->valeur }}/{{ $note->evaluation->bareme }}
                                            @elseif(is_numeric($note->note))
                                                {{ $note->note }}/{{ $note->evaluation->bareme }}
                                            @else
                                                {{ $note->valeur }}
                                            @endif
                                        </div>
                                        <div class="weighted-score">
                                            @php
                                                $noteValue = is_numeric($note->note) ? $note->note : $note->valeur;
                                                $bareme = $note->evaluation->bareme > 0 ? $note->evaluation->bareme : 20;
                                                $coefficient = $note->evaluation->coefficient ?? 1;
                                                
                                                if(is_numeric($noteValue)) {
                                                    $ponderation = ($noteValue / $bareme) * 20 * $coefficient;
                                                } else {
                                                    $ponderation = 0;
                                                }
                                            @endphp
                                            <span class="label">Pondéré</span>
                                            <span class="value">{{ number_format($ponderation, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Aucune évaluation</h3>
                <p>Aucune évaluation trouvée pour cet étudiant dans cette classe pour cette période.</p>
            </div>
        @endif
    </div>
</div>

<style>
.evaluations-grid {
    display: grid;
    gap: 1.5rem;
}

.subject-evaluation-card {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    background: white;
}

.subject-header {
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: between;
    align-items: center;
    color: white;
}

.subject-header.success {
    background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
}

.subject-header.danger {
    background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
}

.subject-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.subject-average {
    font-weight: bold;
    font-size: 1.25rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
}

.evaluations-list {
    padding: 0;
}

.evaluation-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.evaluation-item:last-child {
    border-bottom: none;
}

.evaluation-info {
    flex: 1;
}

.evaluation-title {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.evaluation-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.evaluation-type {
    background: var(--background-secondary);
    padding: 0.125rem 0.5rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
}

.evaluation-scores {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.coefficient,
.weighted-score {
    text-align: center;
    min-width: 60px;
}

.coefficient .label,
.weighted-score .label {
    display: block;
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.coefficient .value,
.weighted-score .value {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
}

.score {
    padding: 0.5rem 0.75rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-align: center;
    min-width: 80px;
}

.score.success {
    background-color: var(--success-bg);
    color: var(--success);
    border: 1px solid var(--success);
}

.score.danger {
    background-color: var(--danger-bg);
    color: var(--danger);
    border: 1px solid var(--danger);
}

@media (max-width: 768px) {
    .evaluation-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .evaluation-scores {
        width: 100%;
        justify-content: space-between;
    }
    
    .subject-header {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}
</style>