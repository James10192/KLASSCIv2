{{-- KPIs d'un enseignant. Reçoit $summary. --}}
@php
    $t = $summary['totaux'];
    $taux = $summary['taux_realisation'];
    $fmtH = function ($v) { $h=(int)floor($v); $m=(int)round(($v-$h)*60); return $h.'h'.($m>0?sprintf('%02d',$m):''); };
@endphp
<div class="tdr-kpi">
    <div class="tdr-kpi-ico"><i class="fas fa-hourglass-half"></i></div>
    <div>
        <div class="tdr-kpi-val">{{ $fmtH($t['heures_realisees']) }}</div>
        <div class="tdr-kpi-lbl">Heures réalisées</div>
    </div>
</div>
<div class="tdr-kpi">
    <div class="tdr-kpi-ico"><i class="fas fa-calendar-check"></i></div>
    <div>
        <div class="tdr-kpi-val">{{ $fmtH($t['heures_planifiees']) }}</div>
        <div class="tdr-kpi-lbl">Heures planifiées</div>
    </div>
</div>
<div class="tdr-kpi">
    <div class="tdr-kpi-ico" style="background:rgba(255,255,255,.16);"><i class="fas fa-gauge-high"></i></div>
    <div>
        <div class="tdr-kpi-val">{{ $taux }}%</div>
        <div class="tdr-kpi-lbl">Taux de réalisation</div>
    </div>
</div>
<div class="tdr-kpi">
    <div class="tdr-kpi-ico"><i class="fas fa-chalkboard-user"></i></div>
    <div>
        <div class="tdr-kpi-val">{{ $t['nb_realisees'] }}<span class="tdr-kpi-sub">/{{ $t['nb_seances'] }}</span></div>
        <div class="tdr-kpi-lbl">Séances réalisées</div>
    </div>
</div>
<div class="tdr-kpi tdr-kpi--warn">
    <div class="tdr-kpi-ico"><i class="fas fa-triangle-exclamation"></i></div>
    <div>
        <div class="tdr-kpi-val">{{ count($summary['warnings']) }}</div>
        <div class="tdr-kpi-lbl">Alertes ponctualité</div>
    </div>
</div>
