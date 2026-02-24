{{-- Composant pour l'aperçu des résultats --}}
<div class="main-card">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-chart-line"></i>
            Aperçu des résultats
        </div>
        <div class="main-card-subtitle">
            @php
                $periodeKey = (string) $periode;
                if ($periodeKey === '1') {
                    $periodeKey = 'semestre1';
                } elseif ($periodeKey === '2') {
                    $periodeKey = 'semestre2';
                }

                $periodeNom = $periodeKey === 'semestre1' ? 'Semestre 1' : 'Semestre 2';
                if (isset($periodes)) {
                    foreach ($periodes as $p) {
                        if ((string) $p->id === (string) $periode || $p->code === $periodeKey) {
                            $periodeNom = $p->nom;
                            break;
                        }
                    }
                }
            @endphp
            {{ $periodeNom }}
        </div>
    </div>
    <div class="main-card-body">
        <div class="results-overview">
            <div class="average-display">
                <div class="average-value {{ $moyenneGenerale >= 10 ? 'success' : 'danger' }}">
                    {{ number_format($moyenneGenerale, 2) }}<span>/20</span>
                </div>
                <div class="average-label">Moyenne générale</div>
                <div class="progress-bar-container">
                    <div class="progress-bar {{ $moyenneGenerale >= 10 ? 'success' : 'danger' }}" 
                         style="width: {{ min($moyenneGenerale * 5, 100) }}%"></div>
                </div>
                <div class="semester-summary">
                    <div class="semester-item">
                        <div class="semester-label">Semestre 1</div>
                        <div class="semester-value">
                            {{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) . '/20' : '—' }}
                        </div>
                    </div>
                    <div class="semester-item">
                        <div class="semester-label">Semestre 2</div>
                        <div class="semester-value">
                            {{ $moyenneSemestre2 !== null ? number_format($moyenneSemestre2, 2) . '/20' : '—' }}
                        </div>
                    </div>
                    <div class="semester-item highlight">
                        <div class="semester-label">Moyenne annuelle</div>
                        <div class="semester-value">
                            @if($periode === 'semestre2' && $moyenneAnnuelle !== null)
                                {{ number_format($moyenneAnnuelle, 2) }}/20
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="semester-note">
                        Coefficients: S1 {{ $semesterWeights['semester1'] }} • S2 {{ $semesterWeights['semester2'] }}
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">{{ count($notesByMatiere) }}</div>
                    <div class="stat-label">Matières évaluées</div>
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ $notes->count() }}</div>
                    <div class="stat-label">Évaluations</div>
                    <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ array_sum(array_column($notesByMatiere, 'total_coefficients')) }}</div>
                    <div class="stat-label">Total coefficients</div>
                    <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                </div>
                <div class="stat-item {{ $moyenneGenerale >= 10 ? 'success' : 'danger' }}">
                    <div class="stat-value">{{ $moyenneGenerale >= 10 ? 'ADMIS' : 'NON ADMIS' }}</div>
                    <div class="stat-label">Décision</div>
                    <div class="stat-icon">
                        <i class="fas {{ $moyenneGenerale >= 10 ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.results-overview {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    align-items: center;
}

.average-display {
    text-align: center;
}

.average-value {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.average-value.success {
    color: var(--success);
}

.average-value.danger {
    color: var(--danger);
}

.average-value span {
    font-size: 1.5rem;
    opacity: 0.7;
}

.average-label {
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background-color: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 4px;
}

.progress-bar.success {
    background-color: var(--success);
}

.progress-bar.danger {
    background-color: var(--danger);
}

.semester-summary {
    margin-top: 1rem;
    border-top: 1px dashed var(--border-color);
    padding-top: 0.75rem;
    display: grid;
    gap: 0.5rem;
}

.semester-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.4rem 0.6rem;
    border-radius: 8px;
    background: var(--background-secondary);
    border: 1px solid var(--border-color);
    font-size: 0.85rem;
}

.semester-item.highlight {
    background: rgba(16, 185, 129, 0.12);
    border-color: rgba(16, 185, 129, 0.4);
}

.semester-label {
    color: var(--text-secondary);
    font-weight: 600;
}

.semester-value {
    font-weight: 700;
    color: var(--text-primary);
}

.semester-note {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-item {
    position: relative;
    background: var(--background-secondary);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    text-align: center;
    border: 1px solid var(--border-color);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.stat-item.success {
    background-color: var(--success-bg);
    border-color: var(--success);
    color: var(--success);
}

.stat-item.danger {
    background-color: var(--danger-bg);
    border-color: var(--danger);
    color: var(--danger);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.stat-item.success .stat-value,
.stat-item.danger .stat-value {
    color: inherit;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.stat-item.success .stat-label,
.stat-item.danger .stat-label {
    color: inherit;
    opacity: 0.8;
}

.stat-icon {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    opacity: 0.3;
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .results-overview {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
