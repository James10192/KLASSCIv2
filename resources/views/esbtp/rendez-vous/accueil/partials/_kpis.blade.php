{{-- Compteurs du jour. Rendu aussi seul par index(?fragment=1). --}}
@php
    $_c = $compteurs;
    $_traites = $_c['recus'] + $_c['traitees'];
    $_pct = $_c['attendus'] > 0 ? (int) round(100 * $_traites / $_c['attendus']) : 0;
@endphp
<div class="rdv-kpis">
    <div class="rdv-kpi">
        <div class="rdv-kpi-icon"><i class="fas fa-users"></i></div>
        <div><div class="rdv-kpi-value">{{ $_c['attendus'] }}</div><div class="rdv-kpi-label">Familles attendues</div></div>
    </div>
    <div class="rdv-kpi">
        <div class="rdv-kpi-icon"><i class="fas fa-circle-check"></i></div>
        <div><div class="rdv-kpi-value">{{ $_c['recus'] }}</div><div class="rdv-kpi-label">Reçues</div></div>
    </div>
    <div class="rdv-kpi {{ $_c['en_retard'] > 0 ? 'rdv-kpi--alerte' : '' }}">
        <div class="rdv-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <div class="rdv-kpi-value">{{ $_c['a_recevoir'] }}</div>
            <div class="rdv-kpi-label">À recevoir{{ $_c['en_retard'] > 0 ? ', dont '.$_c['en_retard'].' en retard' : '' }}</div>
        </div>
    </div>
    <div class="rdv-kpi {{ $_c['non_venues'] > 0 ? 'rdv-kpi--alerte' : '' }}">
        <div class="rdv-kpi-icon"><i class="fas fa-user-clock"></i></div>
        <div>
            <div class="rdv-kpi-value">{{ $_c['non_venues'] }}</div>
            <div class="rdv-kpi-label">Non venues à reprogrammer{{ $_c['reprogrammees'] > 0 ? ', '.$_c['reprogrammees'].' déjà reprogrammée'.($_c['reprogrammees'] > 1 ? 's' : '') : '' }}</div>
        </div>
    </div>
</div>
<div class="rac-avancement" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $_pct }}" aria-label="Familles reçues ou traitées">
    <div class="rac-avancement-barre"><span style="width: {{ $_pct }}%"></span></div>
    <span>{{ $_traites }} / {{ $_c['attendus'] }} reçues ou traitées</span>
</div>
