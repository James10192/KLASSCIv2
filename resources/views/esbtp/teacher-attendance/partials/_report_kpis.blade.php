{{-- KPIs globaux du rapport heures. Reçoit $report. --}}
@php
    $g = $report['global'];
    $fmtH = function ($v) {
        $h = (int) floor($v); $m = (int) round(($v - $h) * 60);
        return $h . 'h' . ($m > 0 ? sprintf('%02d', $m) : '');
    };
    $taux = $report['taux_realisation'];
    $tauxColor = $taux >= 80 ? '#10b981' : ($taux >= 40 ? '#f59e0b' : '#dc2626');
@endphp
<div class="tar-kpi">
    <div class="tar-kpi-ico"><i class="fas fa-hourglass-half"></i></div>
    <div>
        <div class="tar-kpi-val">{{ $fmtH($g['heures_realisees']) }}</div>
        <div class="tar-kpi-lbl">Heures réalisées</div>
    </div>
</div>
<div class="tar-kpi">
    <div class="tar-kpi-ico"><i class="fas fa-calendar-check"></i></div>
    <div>
        <div class="tar-kpi-val">{{ $fmtH($g['heures_planifiees']) }}</div>
        <div class="tar-kpi-lbl">Heures planifiées</div>
    </div>
</div>
<div class="tar-kpi">
    <div class="tar-kpi-ico" style="background:rgba(255,255,255,.16);"><i class="fas fa-gauge-high"></i></div>
    <div>
        <div class="tar-kpi-val">{{ $taux }}%</div>
        <div class="tar-kpi-lbl">Taux de réalisation</div>
    </div>
</div>
<div class="tar-kpi">
    <div class="tar-kpi-ico"><i class="fas fa-chalkboard-user"></i></div>
    <div>
        <div class="tar-kpi-val">{{ $g['nb_realisees'] }}<span class="tar-kpi-sub">/{{ $g['nb_seances'] }}</span></div>
        <div class="tar-kpi-lbl">Séances réalisées</div>
    </div>
</div>
<div class="tar-kpi">
    <div class="tar-kpi-ico" style="background:rgba(255,255,255,.16);"><i class="fas fa-user-group"></i></div>
    <div>
        <div class="tar-kpi-val">{{ $g['nb_enseignants'] }}</div>
        <div class="tar-kpi-lbl">Enseignants</div>
    </div>
</div>
<div class="tar-kpi tar-kpi--warn">
    <div class="tar-kpi-ico"><i class="fas fa-triangle-exclamation"></i></div>
    <div>
        <div class="tar-kpi-val">{{ $report['nb_warnings'] }}</div>
        <div class="tar-kpi-lbl">Alertes ponctualité</div>
    </div>
</div>
