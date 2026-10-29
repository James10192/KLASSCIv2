{{-- 4 KPI glass cards rendus dans row 2 du sc-hero (fond transparent) --}}
@php
    $totalEtudiants = ($vueEnsemble['etudiants_en_regle'] ?? 0)
        + ($vueEnsemble['etudiants_en_retard'] ?? 0)
        + ($vueEnsemble['etudiants_non_payes'] ?? 0);
    $pct = fn($n) => $totalEtudiants > 0 ? round(($n / $totalEtudiants) * 100, 1) : 0;
    $taux = $vueEnsemble['taux_recouvrement_global'] ?? 0;
@endphp
<div class="sc-hero-kpis">
    <div class="sc-hero-kpi">
        <div class="sc-hero-kpi-head">
            <i class="fas fa-check-circle"></i>
            <span class="sc-hero-kpi-label">Étudiants en règle</span>
        </div>
        <div class="sc-hero-kpi-value">{{ $vueEnsemble['etudiants_en_regle'] ?? 0 }}</div>
        <div class="sc-hero-kpi-meta">
            @if($totalEtudiants > 0)
                {{ $pct($vueEnsemble['etudiants_en_regle'] ?? 0) }}% du total
            @else
                Aucun étudiant
            @endif
        </div>
    </div>
    <div class="sc-hero-kpi">
        <div class="sc-hero-kpi-head">
            <i class="fas fa-clock"></i>
            <span class="sc-hero-kpi-label">Paiements partiels</span>
        </div>
        <div class="sc-hero-kpi-value">{{ $vueEnsemble['etudiants_en_retard'] ?? 0 }}</div>
        <div class="sc-hero-kpi-meta">
            @if($totalEtudiants > 0)
                {{ $pct($vueEnsemble['etudiants_en_retard'] ?? 0) }}% du total
            @else
                Aucun étudiant
            @endif
        </div>
    </div>
    <div class="sc-hero-kpi">
        <div class="sc-hero-kpi-head">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="sc-hero-kpi-label">Impayés</span>
        </div>
        <div class="sc-hero-kpi-value">{{ $vueEnsemble['etudiants_non_payes'] ?? 0 }}</div>
        <div class="sc-hero-kpi-meta">
            @if($totalEtudiants > 0)
                {{ $pct($vueEnsemble['etudiants_non_payes'] ?? 0) }}% du total
            @else
                Aucun étudiant
            @endif
        </div>
    </div>
    <div class="sc-hero-kpi">
        <div class="sc-hero-kpi-head">
            <i class="fas fa-chart-line"></i>
            <span class="sc-hero-kpi-label">Taux de recouvrement</span>
        </div>
        <div class="sc-hero-kpi-value">{{ $taux }}<span class="sc-hero-kpi-unit">%</span></div>
        <div class="sc-hero-kpi-meta">
            {{ number_format($vueEnsemble['montant_total_recu'] ?? 0, 0, ',', ' ') }} / {{ number_format($vueEnsemble['montant_total_attendu'] ?? 0, 0, ',', ' ') }} FCFA
        </div>
    </div>
</div>
