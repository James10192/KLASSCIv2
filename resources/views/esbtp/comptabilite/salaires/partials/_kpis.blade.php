{{-- KPIs paie du mois. Reçoit $kpis. --}}
@php $fmt = fn($v) => number_format($v, 0, ',', ' '); @endphp
<div class="pay-kpi">
    <div class="pay-kpi-ico"><i class="fas fa-sack-dollar"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $fmt($kpis['total_net']) }}<span class="pay-kpi-cur">FCFA</span></div>
        <div class="pay-kpi-lbl">Net total du mois</div>
    </div>
</div>
<div class="pay-kpi">
    <div class="pay-kpi-ico"><i class="fas fa-file-invoice"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_total'] }}</div>
        <div class="pay-kpi-lbl">Bulletins</div>
    </div>
</div>
<div class="pay-kpi">
    <div class="pay-kpi-ico" style="background:rgba(255,255,255,.16);"><i class="fas fa-pen-ruler"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_brouillon'] }}</div>
        <div class="pay-kpi-lbl">Brouillons</div>
    </div>
</div>
<div class="pay-kpi">
    <div class="pay-kpi-ico" style="background:rgba(255,255,255,.16);"><i class="fas fa-check-double"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_valide'] }}</div>
        <div class="pay-kpi-lbl">Validés</div>
    </div>
</div>
<div class="pay-kpi pay-kpi--ok">
    <div class="pay-kpi-ico"><i class="fas fa-hand-holding-dollar"></i></div>
    <div>
        <div class="pay-kpi-val">{{ $kpis['nb_paye'] }}</div>
        <div class="pay-kpi-lbl">Payés · {{ $fmt($kpis['net_paye']) }} FCFA</div>
    </div>
</div>
