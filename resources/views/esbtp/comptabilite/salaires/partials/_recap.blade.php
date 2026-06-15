{{-- Récap paie : tous les enseignants à payer ce mois. Reçoit $recap, $statutLabels, $canCreate. --}}
@php
    $fmt = fn($v) => number_format($v, 0, ',', ' ');
    $fmtH = function ($v) { $h=(int)floor($v); $m=(int)round(($v-$h)*60); return $h.'h'.($m>0?sprintf('%02d',$m):''); };
    $badge = [
        'a_preparer' => ['bg' => 'rgba(245,158,11,.12)', 'color' => '#b45309'],
        'brouillon'  => ['bg' => 'rgba(100,116,139,.12)', 'color' => '#475569'],
        'valide'     => ['bg' => 'rgba(4,83,203,.1)',     'color' => '#0453cb'],
        'paye'       => ['bg' => 'rgba(16,185,129,.12)',  'color' => '#065f46'],
        'annule'     => ['bg' => 'rgba(220,38,38,.1)',    'color' => '#b91c1c'],
    ];
@endphp
@forelse($recap as $row)
    @php $st = $badge[$row['statut']] ?? $badge['a_preparer']; @endphp
    <div class="pay-rrow">
        <div class="pay-rrow-avatar">{{ \Illuminate\Support\Str::substr($row['name'], 0, 1) }}</div>
        <div class="pay-rrow-id">
            <div class="pay-rrow-name">{{ $row['name'] }}</div>
            <div class="pay-rrow-types">
                <span class="pay-rrow-h"><i class="fas fa-hourglass-half"></i> {{ $fmtH($row['heures']) }}</span>
                @foreach($row['types'] as $t)
                    <span class="pay-rrow-chip" style="{{ $t['style'] }}" title="{{ $t['type'] }} — {{ $fmtH($t['heures']) }} × {{ $fmt($t['taux']) }} FCFA">
                        <i class="fas {{ $t['icon'] }}"></i> {{ $t['type'] }} {{ $fmtH($t['heures']) }}
                    </span>
                @endforeach
            </div>
        </div>
        <div class="pay-rrow-amounts">
            <div class="pay-rrow-amt"><span class="pay-rrow-amt-lbl">Base</span>{{ $fmt($row['base']) }}</div>
            <div class="pay-rrow-amt pay-rrow-amt--neg"><span class="pay-rrow-amt-lbl">Retenues</span>− {{ $fmt($row['retenues']) }}</div>
            <div class="pay-rrow-amt pay-rrow-amt--net">
                <span class="pay-rrow-amt-lbl">Net{{ $row['estimation'] ? ' estimé' : '' }}</span>
                <strong>{{ $fmt($row['net']) }}</strong>
            </div>
        </div>
        <div class="pay-rrow-end">
            <span class="pay-rrow-badge" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $statutLabels[$row['statut']] ?? $row['statut'] }}</span>
            @if($row['has_bulletin'])
                <a href="{{ route('esbtp.comptabilite.salaires.show', $row['bulletin_id']) }}" class="pay-rrow-btn pay-rrow-btn--view"><i class="fas fa-eye"></i> Voir</a>
            @elseif($canCreate)
                <button type="button" class="pay-rrow-btn pay-rrow-btn--prep"
                        onclick="window.dispatchEvent(new CustomEvent('paie:prepare-teacher',{detail:{id:{{ $row['teacher_id'] }}}}))"><i class="fas fa-calculator"></i> Préparer</button>
            @endif
        </div>
    </div>
@empty
    <div class="pay-empty">
        <i class="fas fa-user-clock"></i>
        <p>Aucun enseignant avec des heures facturables sur cette période.</p>
    </div>
@endforelse
