{{-- KPI Cards --}}
<div class="kpi-grid">
    <div class="card-moderne kpi-card">
        <div class="kpi-title">Étudiants en Règle</div>
        <div class="kpi-value color-success">{{ $vueEnsemble['etudiants_en_regle'] }}</div>
        <div class="kpi-trend positive">
            <i class="fas fa-check-circle me-2"></i>
            @php
                $totalEtudiants = $vueEnsemble['etudiants_en_regle'] + $vueEnsemble['etudiants_en_retard'] + $vueEnsemble['etudiants_non_payes'];
            @endphp
            @if($totalEtudiants > 0)
                {{ round(($vueEnsemble['etudiants_en_regle'] / $totalEtudiants) * 100, 1) }}% du total
            @else
                Aucun étudiant
            @endif
        </div>
    </div>

    <div class="card-moderne kpi-card">
        <div class="kpi-title">Paiements Partiels</div>
        <div class="kpi-value color-warning">{{ $vueEnsemble['etudiants_en_retard'] }}</div>
        <div class="kpi-trend">
            <i class="fas fa-clock me-2"></i>
            @if($totalEtudiants > 0)
                {{ round(($vueEnsemble['etudiants_en_retard'] / $totalEtudiants) * 100, 1) }}% du total
            @else
                Aucun étudiant
            @endif
        </div>
    </div>

    <div class="card-moderne kpi-card">
        <div class="kpi-title">Impayés</div>
        <div class="kpi-value color-danger">{{ $vueEnsemble['etudiants_non_payes'] }}</div>
        <div class="kpi-trend negative">
            <i class="fas fa-exclamation-triangle me-2"></i>
            @if($totalEtudiants > 0)
                {{ round(($vueEnsemble['etudiants_non_payes'] / $totalEtudiants) * 100, 1) }}% du total
            @else
                Aucun étudiant
            @endif
        </div>
    </div>

    <div class="card-moderne kpi-card">
        <div class="kpi-title">Taux de Recouvrement Global</div>
        <div class="kpi-value color-primary">{{ $vueEnsemble['taux_recouvrement_global'] }}%</div>
        <div class="kpi-trend {{ $vueEnsemble['taux_recouvrement_global'] >= 75 ? 'positive' : ($vueEnsemble['taux_recouvrement_global'] >= 50 ? '' : 'negative') }}">
            <i class="fas fa-chart-line me-2"></i>
            {{ number_format($vueEnsemble['montant_total_recu'], 0, ',', ' ') }} / {{ number_format($vueEnsemble['montant_total_attendu'], 0, ',', ' ') }} FCFA
        </div>
    </div>
</div>
