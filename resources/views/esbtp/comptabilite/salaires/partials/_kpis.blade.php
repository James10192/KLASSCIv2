{{-- KPIs paie du mois (récap). Reçoit $kpis. --}}
@php $fmt = fn($v) => number_format($v, 0, ',', ' '); @endphp
<div class="pay-kpi">
    <div class="pay-kpi-ico"><i class="fas fa-sack-dollar"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $fmt($kpis['total_net']) }}<span class="pay-kpi-cur">FCFA</span></div>
        <div class="pay-kpi-lbl">Net total à verser</div>
    </div>
</div>
<div class="pay-kpi">
    <div class="pay-kpi-ico"><i class="fas fa-user-group"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_total'] }}</div>
        <div class="pay-kpi-lbl">Enseignants à payer</div>
    </div>
</div>
<div class="pay-kpi pay-kpi--warn">
    <div class="pay-kpi-ico" style="background:rgba(245,158,11,.28);"><i class="fas fa-pen-ruler"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_a_preparer'] }}</div>
        <div class="pay-kpi-lbl">À préparer · {{ $fmt($kpis['net_a_preparer']) }} FCFA</div>
    </div>
</div>
<div class="pay-kpi">
    <div class="pay-kpi-ico" style="background:rgba(255,255,255,.16);"><i class="fas fa-check-double"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_brouillon'] + $kpis['nb_valide'] }}</div>
        <div class="pay-kpi-lbl">{{ $kpis['nb_brouillon'] }} brouillon(s) · {{ $kpis['nb_valide'] }} validé(s)</div>
    </div>
</div>
<div class="pay-kpi pay-kpi--ok">
    <div class="pay-kpi-ico"><i class="fas fa-hand-holding-dollar"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_paye'] }}</div>
        <div class="pay-kpi-lbl">Payés · {{ $fmt($kpis['net_paye']) }} FCFA</div>
    </div>
</div>
