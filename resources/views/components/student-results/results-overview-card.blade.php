{{-- 4. Results Overview --}}
@php
    $periodeKey = (string) $periode;
    if ($periodeKey === '1') $periodeKey = 'semestre1';
    elseif ($periodeKey === '2') $periodeKey = 'semestre2';

    $periodeNom = match ($periodeKey) {
        'annuel' => 'Annuel',
        'semestre2' => 'Semestre 2',
        default => 'Semestre 1',
    };

    if (isset($periodes)) {
        foreach ($periodes as $p) {
            if ((string) $p->id === (string) $periode || (isset($p->code) && $p->code === $periodeKey)) {
                $periodeNom = $p->nom;
                break;
            }
        }
    }

    $uiState = $detailUiState['state'] ?? 'standard';
    $annualIncomplete = $uiState === 'annual_incomplete';
    $annualComplete = $uiState === 'annual_complete';
    $primarySemesterLabel = $detailUiState['primary_semester_label'] ?? null;
    $primaryAverage = $detailUiState['primary_average'] ?? null;
    $displayAvg = $detailUiState['display_average'] ?? ($moyenneAvecAssiduite ?? $moyenneGenerale ?? null);
    $showAssiduite = isset($afficherNoteAssiduite) && $afficherNoteAssiduite && isset($noteAssiduite) && ! $annualIncomplete;
    $gaugePercent = $displayAvg !== null ? min($displayAvg * 5, 100) : 0;
    $gaugeClass = ($displayAvg ?? 0) >= 10 ? 'success' : 'danger';
    $isS1Active = ($periodeKey === 'semestre1');
    $isS2Active = ($periodeKey === 'semestre2');
    $isAnnualActive = ($periodeKey === 'annuel');
    $annualAvailable = ($moyenneAnnuelle !== null);
@endphp

<div class="sr-overview-card sr-animate sr-animate-delay-2">
    <div class="sr-overview-header">
        <i class="fas fa-chart-line"></i>
        <h3>Aperçu des résultats</h3>
        <span class="sr-periode-badge">{{ $periodeNom }}</span>
    </div>

    <div class="px-4 pt-3">
        <div class="alert {{ !empty($afficherNoteAssiduite) ? 'alert-success' : 'alert-secondary' }} py-2 px-3 mb-0" style="font-size: 0.9rem;">
            <i class="fas {{ !empty($afficherNoteAssiduite) ? 'fa-toggle-on' : 'fa-toggle-off' }} me-2"></i>
            @if(!empty($afficherNoteAssiduite))
                Assiduite activee : les moyennes affichees incluent le bonus/malus d'assiduite.
            @else
                Assiduite desactivee : les moyennes affichees restent brutes, sans bonus/malus.
            @endif
        </div>
    </div>

    <div class="sr-overview-body">
        <div class="sr-gauge-wrapper">
            <div class="sr-gauge" @if($annualIncomplete) style="opacity: 0.72;" @endif>
                <svg viewBox="0 0 120 120">
                    <circle class="sr-gauge-bg" cx="60" cy="60" r="50"/>
                    <circle class="sr-gauge-fill sr-gauge-fill--{{ $gaugeClass }}"
                            cx="60" cy="60" r="50"
                            data-percent="{{ $gaugePercent }}"
                            style="stroke-dasharray: 314.16; stroke-dashoffset: 314.16;"/>
                </svg>
                <div class="sr-gauge-center">
                    <div class="sr-gauge-value sr-gauge-value--{{ $gaugeClass }}">
                        {{ $displayAvg !== null ? number_format($displayAvg, 2) : '—' }}@if($displayAvg !== null)<span>/20</span>@endif
                    </div>
                    <div class="sr-gauge-label">
                        @if($annualIncomplete)
                            Valeur partielle
                        @elseif($isAnnualActive)
                            Moyenne annuelle
                        @else
                            Moyenne
                        @endif
                    </div>
                </div>
            </div>
            @if($showAssiduite && $noteAssiduite != 0)
                <div class="sr-assiduity-badge {{ $noteAssiduite > 0 ? 'sr-assiduity-badge--positive' : 'sr-assiduity-badge--negative' }}">
                    <i class="fas {{ $noteAssiduite > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                    {{ $noteAssiduite > 0 ? '+' : '' }}{{ number_format($noteAssiduite, 2) }} assiduité
                </div>
            @endif
        </div>

        <div class="sr-overview-right">
            @if($annualIncomplete)
                <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 0.9rem;">
                    L'analyse annuelle BTS est incomplète.
                    @if($primarySemesterLabel)
                        La valeur affichée reprend {{ $primarySemesterLabel }} seulement.
                    @endif
                    Les deux semestres sont requis pour une vraie moyenne annuelle.
                </div>
            @elseif($annualComplete)
                <div class="alert alert-success py-2 px-3 mb-3" style="font-size: 0.9rem;">
                    Analyse annuelle complète disponible sur les deux semestres.
                </div>
            @endif

            <div class="sr-semesters">
                <div class="sr-semester-card {{ $isS1Active ? 'sr-semester-card--active' : '' }}">
                    <div class="sr-semester-label">Semestre 1</div>
                    <div class="sr-semester-value">
                        {{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '—' }}
                    </div>
                </div>
                <div class="sr-semester-card {{ $isS2Active ? 'sr-semester-card--active' : '' }}">
                    <div class="sr-semester-label">Semestre 2</div>
                    <div class="sr-semester-value">
                        {{ $moyenneSemestre2 !== null ? number_format($moyenneSemestre2, 2) : '—' }}
                    </div>
                </div>
                <div class="sr-semester-card {{ $isAnnualActive ? 'sr-semester-card--active' : '' }} {{ $annualAvailable ? 'sr-semester-card--annual-available' : '' }}">
                    <div class="sr-semester-label">Annuelle</div>
                    <div class="sr-semester-value" @if($annualIncomplete) style="color:#64748b;" @endif>
                        @if($annualAvailable)
                            {{ number_format($moyenneAnnuelle, 2) }}
                        @elseif($annualIncomplete && $primaryAverage !== null)
                            {{ number_format($primaryAverage, 2) }}
                        @else
                            —
                        @endif
                    </div>
                    @if($annualIncomplete && $primarySemesterLabel)
                        <div class="sr-semester-tooltip">
                            {{ $primarySemesterLabel }} seulement
                        </div>
                    @elseif(!$annualAvailable)
                        <div class="sr-semester-tooltip" title="Nécessite les notes des deux semestres">
                            Requiert S1 + S2
                        </div>
                    @endif
                </div>
            </div>
            <div class="sr-semester-note">
                Coefficients : S1 {{ $semesterWeights['semester1'] }} | S2 {{ $semesterWeights['semester2'] }}
                @if($annualComplete)
                    <strong style="color: var(--sr-success); margin-left: 0.5rem;">Bilan complet</strong>
                @elseif($annualIncomplete && $primarySemesterLabel)
                    <strong style="color: #b45309; margin-left: 0.5rem;">{{ $primarySemesterLabel }} seulement</strong>
                @endif
            </div>

            <div class="sr-stats">
                <div class="sr-stat sr-stat--primary">
                    <div class="sr-stat-icon"><i class="fas fa-book"></i></div>
                    <div class="sr-stat-value">{{ count($notesByMatiere) }}</div>
                    <div class="sr-stat-label">Matières</div>
                </div>
                <div class="sr-stat sr-stat--info">
                    <div class="sr-stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="sr-stat-value">{{ $notes->count() }}</div>
                    <div class="sr-stat-label">Évaluations</div>
                </div>
                <div class="sr-stat sr-stat--warning">
                    <div class="sr-stat-icon"><i class="fas fa-calculator"></i></div>
                    <div class="sr-stat-value">{{ array_sum(array_column($notesByMatiere, 'total_coefficients')) }}</div>
                    <div class="sr-stat-label">Coefficients</div>
                </div>
                <div class="sr-stat sr-stat--{{ $gaugeClass }}">
                    <div class="sr-stat-icon">
                        <i class="fas {{ ($displayAvg ?? 0) >= 10 ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                    </div>
                    <div class="sr-stat-value">
                        @if($displayAvg === null)
                            —
                        @elseif($annualIncomplete)
                            Partiel
                        @else
                            {{ $displayAvg >= 10 ? 'ADMIS' : 'AJOURNÉ' }}
                        @endif
                    </div>
                    <div class="sr-stat-label">Décision</div>
                </div>
            </div>
        </div>
    </div>
</div>
