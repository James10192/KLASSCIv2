{{-- 6. Evaluations Detail — Accordion --}}
<div class="sr-evaluations-card sr-animate sr-animate-delay-4">
    <div class="sr-evaluations-header">
        <div class="sr-evaluations-header-left">
            <i class="fas fa-list-check"></i>
            <h3>Détail des évaluations</h3>
        </div>
        <span class="sr-table-count">{{ $notes->count() }} notes</span>
    </div>

    @if(count($notesByMatiere) > 0)
        <div class="sr-accordion">
            @foreach($notesByMatiere as $matiere_id => $matiereData)
                <div class="sr-accordion-item">
                    <button type="button" class="sr-accordion-trigger">
                        <div class="sr-accordion-trigger-left">
                            <span class="sr-accordion-dot sr-accordion-dot--{{ $matiereData['moyenne'] >= 10 ? 'success' : 'danger' }}"></span>
                            <span class="sr-accordion-subject">{{ $matiereData['matiere']->name }}</span>
                        </div>
                        <span class="sr-accordion-avg sr-accordion-avg--{{ $matiereData['moyenne'] >= 10 ? 'success' : 'danger' }}">
                            {{ number_format($matiereData['moyenne'], 2) }}/20
                        </span>
                        <span class="sr-eval-count" style="font-size: 0.7rem;">{{ count($matiereData['notes']) }}</span>
                        <i class="fas fa-chevron-down sr-accordion-chevron"></i>
                    </button>
                    <div class="sr-accordion-body">
                        @foreach($matiereData['notes'] as $note)
                            <div class="sr-eval-item">
                                <div>
                                    <div class="sr-eval-title">{{ $note->evaluation->title }}</div>
                                    <div class="sr-eval-meta">
                                        <span class="sr-eval-type">{{ $note->evaluation->type }}</span>
                                        <span class="sr-eval-date">
                                            {{ $note->evaluation->date_evaluation ? $note->evaluation->date_evaluation->format('d/m/Y') : '' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="sr-eval-cell">
                                    <div class="sr-eval-cell-label">Coeff</div>
                                    <div class="sr-eval-cell-value">{{ $note->evaluation->coefficient }}</div>
                                </div>
                                <div>
                                    @php
                                        $noteVal = is_numeric($note->valeur) ? $note->valeur : ($note->note ?? 0);
                                        $scoreClass = (is_numeric($noteVal) && $noteVal >= 10) ? 'success' : 'danger';
                                    @endphp
                                    <div class="sr-eval-score sr-eval-score--{{ $scoreClass }}">
                                        @if(is_numeric($note->valeur))
                                            {{ $note->valeur }}/{{ $note->evaluation->bareme }}
                                        @elseif(is_numeric($note->note))
                                            {{ $note->note }}/{{ $note->evaluation->bareme }}
                                        @else
                                            {{ $note->valeur }}
                                        @endif
                                    </div>
                                </div>
                                <div class="sr-eval-cell">
                                    <div class="sr-eval-cell-label">Pondéré</div>
                                    @php
                                        $noteValue = is_numeric($note->note) ? $note->note : $note->valeur;
                                        $bareme = $note->evaluation->bareme > 0 ? $note->evaluation->bareme : 20;
                                        $coefficient = $note->evaluation->coefficient ?? 1;
                                        $ponderation = is_numeric($noteValue) ? ($noteValue / $bareme) * 20 * $coefficient : 0;
                                    @endphp
                                    <div class="sr-eval-cell-value">{{ number_format($ponderation, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sr-empty">
            <i class="fas fa-clipboard-list"></i>
            <h3>Aucune évaluation</h3>
            <p>Aucune évaluation trouvée pour cet étudiant dans cette période.</p>
        </div>
    @endif
</div>
