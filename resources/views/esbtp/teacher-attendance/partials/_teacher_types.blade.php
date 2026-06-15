{{-- Ventilation par type (CM/TD/TP) d'un enseignant. Reçoit $summary. --}}
@php
    $fmtH = function ($v) { $h=(int)floor($v); $m=(int)round(($v-$h)*60); return $h.'h'.($m>0?sprintf('%02d',$m):''); };
@endphp
@forelse($summary['par_type'] as $pt)
    @php
        $tx = $pt['heures_planifiees'] > 0 ? round($pt['heures_realisees'] / $pt['heures_planifiees'] * 100, 1) : 0;
        $col = $tx >= 80 ? '#10b981' : ($tx >= 40 ? '#f59e0b' : '#0453cb');
    @endphp
    <div class="tdr-type-card">
        <div class="tdr-type-head">
            <span class="tdr-type-chip" style="{{ $pt['style'] }}"><i class="fas {{ $pt['icon'] }}"></i> {{ $pt['type'] }}</span>
            <span class="tdr-type-name">{{ $pt['label'] }}</span>
            @if(!$pt['facturable'])<span class="tdr-type-nf" title="Type non facturable">non facturable</span>@endif
        </div>
        <div class="tdr-type-val">{{ $fmtH($pt['heures_realisees']) }}<span class="tdr-type-plan"> / {{ $fmtH($pt['heures_planifiees']) }}</span></div>
        <div class="tdr-type-bar"><div class="tdr-type-bar-fill" style="width:{{ min(100,$tx) }}%;background:{{ $col }};"></div></div>
        <div class="tdr-type-meta">
            <span>{{ $tx }}% réalisé</span>
            <span>{{ $pt['nb_realisees'] }}/{{ $pt['nb_seances'] }} séances</span>
        </div>
    </div>
@empty
    <div class="tdr-empty"><i class="fas fa-shapes"></i><p>Aucune séance sur cette période.</p></div>
@endforelse
