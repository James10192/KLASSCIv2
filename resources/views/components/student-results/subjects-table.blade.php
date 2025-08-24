{{-- Composant pour le tableau des résultats par matière --}}
<div class="main-card mb-4">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-book"></i>
            Résultats par matière
        </div>
        <div class="main-card-subtitle">Détail des moyennes par discipline</div>
    </div>
    <div class="main-card-body table-container">
        @if(count($notesByMatiere) > 0)
            <div class="table-responsive">
                <table class="table-moderne table-full-width">
                    <thead>
                        <tr>
                            <th style="width: 8%">#</th>
                            <th style="width: 32%">Matière</th>
                            <th style="width: 15%" class="text-center">Évaluations</th>
                            <th style="width: 15%" class="text-center">Coefficient</th>
                            <th style="width: 15%" class="text-center">Moyenne</th>
                            <th style="width: 15%" class="text-center">Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 1; @endphp
                        @foreach($notesByMatiere as $matiere_id => $matiereData)
                            <tr>
                                <td class="text-center">{{ $i++ }}</td>
                                <td>
                                    <div class="subject-cell">
                                        <div class="subject-icon">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="subject-info">
                                            <div class="subject-name">{{ $matiereData['matiere']->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ count($matiereData['notes']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="coefficient">{{ $matiereData['total_coefficients'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="average-badge {{ $matiereData['moyenne'] >= 10 ? 'success' : 'danger' }}">
                                        {{ number_format($matiereData['moyenne'], 2) }}/20
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($matiereData['moyenne'] >= 16)
                                        <span class="appreciation excellent">Excellent</span>
                                    @elseif($matiereData['moyenne'] >= 14)
                                        <span class="appreciation tres-bien">Très bien</span>
                                    @elseif($matiereData['moyenne'] >= 12)
                                        <span class="appreciation bien">Bien</span>
                                    @elseif($matiereData['moyenne'] >= 10)
                                        <span class="appreciation passable">Passable</span>
                                    @else
                                        <span class="appreciation insuffisant">Insuffisant</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <th colspan="3" class="text-end">Total des coefficients :</th>
                            <th class="text-center">{{ array_sum(array_column($notesByMatiere, 'total_coefficients')) }}</th>
                            <th class="text-center average-cell {{ $moyenneGenerale >= 10 ? 'success' : 'danger' }}">
                                {{ number_format($moyenneGenerale, 2) }}/20
                            </th>
                            <th class="text-center">
                                <span class="decision {{ $moyenneGenerale >= 10 ? 'success' : 'danger' }}">
                                    {{ $moyenneGenerale >= 10 ? 'ADMIS' : 'NON ADMIS' }}
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>Aucune note disponible</h3>
                <p>Aucune note trouvée pour cet étudiant dans cette période.</p>
            </div>
        @endif
    </div>
</div>

<style>
.table-responsive {
    width: 100%;
    overflow-x: auto;
}

.table-moderne,
.table-full-width {
    width: 100% !important;
    min-width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin-bottom: 0;
}

.table-moderne th,
.table-moderne td {
    padding: 0.875rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Allow text wrapping for the subject column */
.table-moderne td:nth-child(2) {
    white-space: normal;
    word-wrap: break-word;
}

.table-moderne th {
    background-color: var(--background-secondary);
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
}

.table-moderne tbody tr:hover {
    background-color: var(--hover-bg, #f8f9fa);
}

.subject-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.subject-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-bg);
    color: var(--primary);
    border-radius: 50%;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.subject-info {
    min-width: 0;
    flex: 1;
}

.subject-name {
    font-weight: 600;
    color: var(--text-primary);
}

.coefficient {
    font-weight: 600;
    color: var(--text-primary);
}

.average-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 0.875rem;
}

.average-badge.success {
    background-color: var(--success-bg);
    color: var(--success);
    border: 1px solid var(--success);
}

.average-badge.danger {
    background-color: var(--danger-bg);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.appreciation {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.appreciation.excellent {
    background-color: #065f46;
    color: white;
}

.appreciation.tres-bien {
    background-color: var(--success-bg);
    color: var(--success);
}

.appreciation.bien {
    background-color: var(--info-bg);
    color: var(--info);
}

.appreciation.passable {
    background-color: var(--warning-bg);
    color: var(--warning);
}

.appreciation.insuffisant {
    background-color: var(--danger-bg);
    color: var(--danger);
}

.summary-row {
    background-color: var(--background-secondary);
    font-weight: 600;
}

.summary-row th {
    padding: 1rem 0.75rem;
    border-top: 2px solid var(--border-color);
}

.average-cell.success {
    color: var(--success);
}

.average-cell.danger {
    color: var(--danger);
}

.decision {
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.decision.success {
    color: var(--success);
}

.decision.danger {
    color: var(--danger);
}

/* Force full width table layout - only for tables */
.main-card-body:has(.table-responsive) {
    padding: 0 !important;
}

/* Fallback for browsers that don't support :has() */
.main-card-body.table-container {
    padding: 0 !important;
}

.table-responsive {
    border-radius: 0;
}

.summary-row th {
    padding: 1rem 0.75rem !important;
    border-top: 2px solid var(--border-color);
    font-size: 0.925rem;
}

.summary-row:first-child {
    background-color: var(--background-secondary) !important;
}

.summary-row:last-child {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
    color: white !important;
}

.summary-row:last-child th {
    border-top: 2px solid var(--primary);
    color: white !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-moderne th,
    .table-moderne td {
        padding: 0.5rem 0.375rem;
        font-size: 0.875rem;
    }
    
    .subject-cell {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .subject-icon {
        margin: 0 auto;
    }
}
</style>