{{-- Cartes par enseignant : heures précises CM/TD/TP + baromètre. Reçoit $report. --}}
@php
    $fmtH = function ($v) {
        $h = (int) floor($v); $m = (int) round(($v - $h) * 60);
        return $h . 'h' . ($m > 0 ? sprintf('%02d', $m) : '');
    };
@endphp
@forelse($report['enseignants'] as $ens)
    @php
        $t = $ens['totaux'];
        $taux = $ens['taux_realisation'];
        $pColor = $taux >= 80 ? '#10b981' : ($taux >= 40 ? '#f59e0b' : '#0453cb');
    @endphp
    <div class="tar-tcard">
        <div class="tar-tcard-head">
            <div class="tar-tcard-avatar">{{ \Illuminate\Support\Str::substr($ens['name'], 0, 1) }}</div>
            <div class="tar-tcard-id">
                <a href="{{ route('esbtp.teacher-attendance.teacher-report', $ens['teacher_id']) }}" class="tar-tcard-name">{{ $ens['name'] }}</a>
                <div class="tar-tcard-sub">{{ $fmtH($t['heures_realisees']) }} réalisées · {{ $t['nb_realisees'] }}/{{ $t['nb_seances'] }} séances</div>
            </div>
            @if($ens['nb_warnings'] > 0)
                <span class="tar-tcard-warn" title="{{ $ens['nb_warnings'] }} alerte(s) de ponctualité"><i class="fas fa-triangle-exclamation"></i> {{ $ens['nb_warnings'] }}</span>
            @endif
        </div>

        <div class="tar-tcard-bar" title="{{ $taux }}% des heures planifiées réalisées">
            <div class="tar-tcard-bar-fill" style="width:{{ min(100, $taux) }}%;background:{{ $pColor }};"></div>
        </div>
        <div class="tar-tcard-bar-meta">
            <span>{{ $taux }}% réalisé</span>
            <span>{{ $fmtH($t['heures_realisees']) }} / {{ $fmtH($t['heures_planifiees']) }}</span>
        </div>

        <div class="tar-tcard-types">
            @foreach($ens['par_type'] as $pt)
                <div class="tar-type-chip" style="{{ $pt['style'] }}" title="{{ $pt['label'] }} — {{ $fmtH($pt['heures_realisees']) }} réalisées sur {{ $fmtH($pt['heures_planifiees']) }} planifiées">
                    <i class="fas {{ $pt['icon'] }}"></i>
                    <span class="tar-type-code">{{ $pt['type'] }}</span>
                    <strong>{{ $fmtH($pt['heures_realisees']) }}</strong>
                    @if(!$pt['facturable'])<span class="tar-type-nf">·</span>@endif
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="tar-empty">
        <i class="fas fa-inbox"></i>
        <p>Aucune séance d'enseignant sur cette période avec ces filtres.</p>
    </div>
@endforelse
