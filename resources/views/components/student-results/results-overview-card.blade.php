{{-- 4. Results Overview — KPI Dashboard with SVG gauge --}}
@php
    $periodeKey = (string) $periode;
    if ($periodeKey === '1') $periodeKey = 'semestre1';
    elseif ($periodeKey === '2') $periodeKey = 'semestre2';

    $periodeNom = $periodeKey === 'semestre1' ? 'Semestre 1' : 'Semestre 2';
    if (isset($periodes)) {
        foreach ($periodes as $p) {
            if ((string) $p->id === (string) $periode || (isset($p->code) && $p->code === $periodeKey)) {
                $periodeNom = $p->nom;
                break;
            }
        }
    }

    $showAssiduite = isset($afficherNoteAssiduite) && $afficherNoteAssiduite && isset($noteAssiduite);
    $displayAvg = $showAssiduite ? ($moyenneAvecAssiduite ?? $moyenneGenerale) : $moyenneGenerale;
    $gaugePercent = min($displayAvg * 5, 100);
    $gaugeClass = $displayAvg >= 10 ? 'success' : 'danger';
    $isS1Active = ($periodeKey === 'semestre1');
    $isS2Active = ($periodeKey === 'semestre2');
    $annualAvailable = ($moyenneAnnuelle !== null);
@endphp

<div class="sr-overview-card sr-animate sr-animate-delay-2">
    <div class="sr-overview-header">
        <i class="fas fa-chart-line"></i>
        <h3>Aperçu des résultats</h3>
        <span class="sr-periode-badge">{{ $periodeNom }}</span>
    </div>

    <div class="sr-overview-body">
        {{-- Circular gauge + assiduity badge --}}
        <div class="sr-gauge-wrapper">
            <div class="sr-gauge">
                <svg viewBox="0 0 120 120">
                    <circle class="sr-gauge-bg" cx="60" cy="60" r="50"/>
                    <circle class="sr-gauge-fill sr-gauge-fill--{{ $gaugeClass }}"
                            cx="60" cy="60" r="50"
                            data-percent="{{ $gaugePercent }}"
                            style="stroke-dasharray: 314.16; stroke-dashoffset: 314.16;"/>
                </svg>
                <div class="sr-gauge-center">
                    <div class="sr-gauge-value sr-gauge-value--{{ $gaugeClass }}">
                        {{ number_format($displayAvg, 2) }}<span>/20</span>
                    </div>
                    <div class="sr-gauge-label">Moyenne</div>
                </div>
            </div>
            @if($showAssiduite && $noteAssiduite != 0)
                <div class="sr-assiduity-badge {{ $noteAssiduite > 0 ? 'sr-assiduity-badge--positive' : 'sr-assiduity-badge--negative' }}">
                    <i class="fas {{ $noteAssiduite > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                    {{ $noteAssiduite > 0 ? '+' : '' }}{{ number_format($noteAssiduite, 2) }} assiduité
                </div>
            @endif
        </div>

        {{-- Right panel --}}
        <div class="sr-overview-right">
            {{-- Semesters — always show all 3 --}}
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
                <div class="sr-semester-card {{ $annualAvailable ? 'sr-semester-card--annual-available' : '' }}">
                    <div class="sr-semester-label">Annuelle</div>
                    <div class="sr-semester-value">
                        {{-- TOUJOURS afficher la moyenne annuelle quand disponible --}}
                        @if($annualAvailable)
                            {{ number_format($moyenneAnnuelle, 2) }}
                        @else
                            —
                        @endif
                    </div>
                    @if(!$annualAvailable)
                        <div class="sr-semester-tooltip" title="Nécessite les notes des deux semestres">
                            Requiert S1 + S2
                        </div>
                    @endif
                </div>
            </div>
            <div class="sr-semester-note">
                Coefficients : S1 {{ $semesterWeights['semester1'] }} | S2 {{ $semesterWeights['semester2'] }}
                @if($annualAvailable && $isS2Active)
                    <strong style="color: var(--sr-success); margin-left: 0.5rem;">Bilan complet</strong>
                @endif
            </div>

            {{-- Stats --}}
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
                        <i class="fas {{ $displayAvg >= 10 ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                    </div>
                    <div class="sr-stat-value">{{ $displayAvg >= 10 ? 'ADMIS' : 'AJOURNÉ' }}</div>
                    <div class="sr-stat-label">Décision</div>
                </div>
            </div>
        </div>
    </div>
</div>
