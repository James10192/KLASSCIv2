{{-- 5. Subjects Table — premium design --}}
@php
    $resolvedAverage = $moyenneAvecAssiduite ?? $moyenneGenerale;
    $hasResolvedAverage = $resolvedAverage !== null;
@endphp
<div class="sr-table-card sr-animate sr-animate-delay-3">
    <div class="sr-table-header">
        <div class="sr-table-header-left">
            <i class="fas fa-book-open"></i>
            <h3>Résultats par matière</h3>
        </div>
        <span class="sr-table-count">{{ count($notesByMatiere) }} matières</span>
    </div>

    @if(count($notesByMatiere) > 0)
        <div class="sr-table-responsive">
            <table class="sr-table">
                <thead>
                    <tr>
                        <th style="width: 5%" class="text-center">#</th>
                        <th style="width: 35%">Matière</th>
                        <th style="width: 12%" class="text-center">Éval.</th>
                        <th style="width: 12%" class="text-center">Coeff.</th>
                        <th style="width: 18%" class="text-center">Moyenne</th>
                        <th style="width: 18%" class="text-center">Appréciation</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = 1; @endphp
                    @foreach($notesByMatiere as $matiere_id => $matiereData)
                        <tr>
                            <td class="text-center" style="color: var(--sr-muted-light); font-weight: 600; font-size: 0.8rem;">
                                {{ str_pad($i++, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="sr-subject-cell">
                                    <div class="sr-subject-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="sr-subject-info">
                                        <div class="sr-subject-name">{{ $matiereData['matiere']->name }}</div>
                                        <div class="sr-subject-meta">
                                            @if($matiereData['matiere']->code)
                                                <span class="sr-subject-code">{{ $matiereData['matiere']->code }}</span>
                                            @endif
                                            @if(isset($matiereData['origin']))
                                                @if($matiereData['origin'] === 'classe')
                                                    <span class="sr-badge-origin sr-badge-origin--classe">Classe</span>
                                                @elseif($matiereData['origin'] === 'notes')
                                                    <span class="sr-badge-origin sr-badge-origin--notes">Notes</span>
                                                @endif
                                            @endif
                                            @if(isset($matiereData['source']))
                                                @if($matiereData['source'] == 'calculee')
                                                    <span class="sr-badge-origin sr-badge-origin--auto">Auto</span>
                                                @else
                                                    <span class="sr-badge-origin sr-badge-origin--manual">Manuel</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="sr-eval-count">{{ count($matiereData['notes']) }}</span>
                            </td>
                            <td class="text-center">
                                {{-- Coefficient OFFICIEL de la matière dans la classe (esbtp_matiere_coefficients),
                                     PAS la somme des coefficients d'évaluations. --}}
                                <span class="sr-coeff">{{ $matiereData['matiere_coefficient'] ?? $matiereData['total_coefficients'] }}</span>
                            </td>
                            <td class="text-center">
                                <div class="sr-avg-cell">
                                    <span class="sr-avg-badge sr-avg-badge--{{ $matiereData['moyenne'] >= 10 ? 'success' : 'danger' }}">
                                        {{ number_format($matiereData['moyenne'], 2) }}/20
                                    </span>
                                    <div class="sr-avg-progress">
                                        <div class="sr-avg-progress-fill sr-avg-progress-fill--{{ $matiereData['moyenne'] >= 10 ? 'success' : 'danger' }}"
                                             style="width: {{ min($matiereData['moyenne'] * 5, 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($matiereData['moyenne'] >= 16)
                                    <span class="sr-appreciation sr-appreciation--excellent">Excellent</span>
                                @elseif($matiereData['moyenne'] >= 14)
                                    <span class="sr-appreciation sr-appreciation--tres-bien">Très bien</span>
                                @elseif($matiereData['moyenne'] >= 12)
                                    <span class="sr-appreciation sr-appreciation--bien">Bien</span>
                                @elseif($matiereData['moyenne'] >= 10)
                                    <span class="sr-appreciation sr-appreciation--passable">Passable</span>
                                @else
                                    <span class="sr-appreciation sr-appreciation--insuffisant">Insuffisant</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="sr-table-footer-total">
                        <td colspan="3" class="text-end" style="font-weight: 700; color: var(--sr-ink);">
                            Total des coefficients
                        </td>
                        <td class="text-center" style="font-weight: 800; font-size: 1rem; color: var(--sr-ink);">
                            {{ array_sum(array_map(fn($m) => $m['matiere_coefficient'] ?? $m['total_coefficients'] ?? 0, $notesByMatiere)) }}
                        </td>
                        <td class="text-center" style="font-weight: 800; font-size: 1rem; color: {{ $hasResolvedAverage ? ($resolvedAverage >= 10 ? 'var(--sr-success)' : 'var(--sr-danger)') : 'var(--sr-muted)' }};">
                            @if($hasResolvedAverage)
                                {{ number_format($resolvedAverage, 2) }}/20
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-center">
                            @if($hasResolvedAverage && $resolvedAverage >= 10)
                                <span class="sr-appreciation sr-appreciation--tres-bien">ADMIS</span>
                            @elseif(!$hasResolvedAverage)
                                <span class="sr-appreciation" style="background: #fff7ed; color: #c2410c;">À recalculer</span>
                            @else
                                <span class="sr-appreciation sr-appreciation--insuffisant">AJOURNÉ</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="sr-table-footer-result">
                        <td colspan="3" class="text-end">Moyenne générale pondérée</td>
                        <td class="text-center">{{ array_sum(array_map(fn($m) => $m['matiere_coefficient'] ?? $m['total_coefficients'] ?? 0, $notesByMatiere)) }}</td>
                        <td class="text-center" style="font-size: 1.1rem; font-weight: 800;">
                            @if($hasResolvedAverage)
                                {{ number_format($resolvedAverage, 2) }}/20
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="sr-decision">
                                <i class="fas {{ $hasResolvedAverage && $resolvedAverage >= 10 ? 'fa-check-circle' : ($hasResolvedAverage ? 'fa-times-circle' : 'fa-rotate-right') }}"></i>
                                {{ $hasResolvedAverage ? ($resolvedAverage >= 10 ? 'ADMIS' : 'AJOURNÉ') : 'À recalculer' }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="sr-empty">
            <i class="fas fa-book-open"></i>
            <h3>Aucune note disponible</h3>
            <p>Aucune note trouvée pour cet étudiant dans cette période.</p>
        </div>
    @endif
</div>
