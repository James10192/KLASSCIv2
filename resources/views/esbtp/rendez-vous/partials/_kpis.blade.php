{{-- KPIs du hero. Rendu aussi seul, par index(?fragment=1), apres chaque action. --}}
@php
    $_conv = $convocations;
    $_suivies = $_conv['envoyee'] + $_conv['en_attente'] + $_conv['echec'] + $_conv['sans_email'];
    $_aTraiter = $_conv['en_attente'] + $_conv['echec'];
@endphp
<div class="rdv-kpis">
    <div class="rdv-kpi">
        <div class="rdv-kpi-icon"><i class="fas fa-user-check"></i></div>
        <div>
            <div class="rdv-kpi-value">{{ number_format($kpis['reservations'], 0, ',', ' ') }}</div>
            <div class="rdv-kpi-label">Rendez-vous à venir</div>
        </div>
    </div>
    <div class="rdv-kpi">
        <div class="rdv-kpi-icon"><i class="fas fa-paper-plane"></i></div>
        <div>
            <div class="rdv-kpi-value">{{ number_format($_conv['envoyee'], 0, ',', ' ') }}<span class="rdv-kpi-sur"> / {{ number_format($_suivies, 0, ',', ' ') }}</span></div>
            <div class="rdv-kpi-label">Convocations envoyées</div>
        </div>
    </div>
    <div class="rdv-kpi {{ $_aTraiter > 0 ? 'rdv-kpi--alerte' : '' }}">
        <div class="rdv-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <div class="rdv-kpi-value">{{ number_format($_aTraiter, 0, ',', ' ') }}</div>
            <div class="rdv-kpi-label">
                @if($_conv['echec'] > 0)
                    À traiter, dont {{ $_conv['echec'] }} en échec
                @else
                    Convocations à traiter
                @endif
            </div>
        </div>
    </div>
    <div class="rdv-kpi">
        <div class="rdv-kpi-icon"><i class="fas fa-door-open"></i></div>
        <div>
            <div class="rdv-kpi-value">{{ number_format($kpis['libres'], 0, ',', ' ') }}</div>
            <div class="rdv-kpi-label">Places libres sur {{ $kpis['creneaux'] }} créneaux ouverts</div>
        </div>
    </div>
    <div class="rdv-kpi">
        <div class="rdv-kpi-icon"><i class="fas fa-gauge-high"></i></div>
        <div>
            <div class="rdv-kpi-value">{{ $debit ? $debit['personnes_par_jour'] : '—' }}</div>
            <div class="rdv-kpi-label">
                @if($debit)
                    Familles / jour ({{ $debit['creneaux_par_jour'] }} × {{ $debit['capacite'] }} places)
                @else
                    Capacité par jour : réglages à compléter
                @endif
            </div>
        </div>
    </div>
</div>
