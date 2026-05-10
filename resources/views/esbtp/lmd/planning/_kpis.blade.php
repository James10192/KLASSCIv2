{{-- KPIs dans le hero gradient (rule premium-redesign : KPIs row 2) --}}
<div class="lp-kpi">
    <div class="lp-kpi-icon"><i class="fas fa-cubes"></i></div>
    <div>
        <div class="lp-kpi-value">{{ $kpis['ue_count'] }}</div>
        <div class="lp-kpi-label">Unités d'enseignement</div>
    </div>
</div>
<div class="lp-kpi">
    <div class="lp-kpi-icon"><i class="fas fa-list"></i></div>
    <div>
        <div class="lp-kpi-value">{{ $kpis['ecue_count'] }}</div>
        <div class="lp-kpi-label">ECUE / matières</div>
    </div>
</div>
<div class="lp-kpi">
    <div class="lp-kpi-icon"><i class="fas fa-award"></i></div>
    <div>
        <div class="lp-kpi-value">{{ $kpis['cect_total'] }}</div>
        <div class="lp-kpi-label">Crédits CECT total</div>
    </div>
</div>
