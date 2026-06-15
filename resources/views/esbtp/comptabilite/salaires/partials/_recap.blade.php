{{-- Récap paie période. Reçoit $recap, $statutLabels, $canCreate.
     Chaque ligne = 1 enseignant ; chaque pastille = 1 mois (cliquable : préparer ou voir). --}}
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
    $stIcon = [
        'a_preparer' => 'fa-wand-magic-sparkles', 'brouillon' => 'fa-pen',
        'valide' => 'fa-check', 'paye' => 'fa-circle-check', 'annule' => 'fa-ban',
    ];
@endphp
@forelse($recap as $row)
    @php
        $st = $badge[$row['statut']] ?? $badge['a_preparer'];
        $hasEstim = collect($row['months'])->contains('estimation', true);
    @endphp
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

        {{-- Bandeau mensuel cliquable --}}
        <div class="pay-rrow-months">
            @foreach($row['months'] as $m)
                @php
                    $icon = $stIcon[$m['statut']] ?? 'fa-circle';
                    $stLabel = $statutLabels[$m['statut']] ?? $m['statut'];
                    $tip = $m['label'].' '.$m['annee'].' — '.($m['estimation'] ? 'Estimé ' : 'Net ').$fmt($m['net']).' FCFA — '.$stLabel;
                @endphp
                @if($m['has_bulletin'])
                    <a href="{{ route('esbtp.comptabilite.salaires.show', $m['bulletin_id']) }}"
                       class="pay-mchip pay-mchip--{{ $m['statut'] }}" title="{{ $tip }} · voir le bulletin">
                        <span class="pay-mchip-top"><span class="pay-mchip-m">{{ $m['short'] }}</span><i class="fas {{ $icon }}"></i></span>
                        <span class="pay-mchip-net">{{ $fmt($m['net']) }}</span>
                    </a>
                @elseif($canCreate)
                    <button type="button" class="pay-mchip pay-mchip--a_preparer" title="{{ $tip }} · cliquer pour préparer"
                            onclick="window.dispatchEvent(new CustomEvent('paie:prepare-teacher',{detail:{id:{{ $row['teacher_id'] }},mois:{{ $m['mois'] }},annee:{{ $m['annee'] }}}}))">
                        <span class="pay-mchip-top"><span class="pay-mchip-m">{{ $m['short'] }}</span><i class="fas {{ $icon }}"></i></span>
                        <span class="pay-mchip-net">~{{ $fmt($m['net']) }}</span>
                    </button>
                @else
                    <span class="pay-mchip pay-mchip--{{ $m['statut'] }}" title="{{ $tip }}">
                        <span class="pay-mchip-top"><span class="pay-mchip-m">{{ $m['short'] }}</span><i class="fas {{ $icon }}"></i></span>
                        <span class="pay-mchip-net">{{ $fmt($m['net']) }}</span>
                    </span>
                @endif
            @endforeach
        </div>

        {{-- Net total période + statut global --}}
        <div class="pay-rrow-netcol">
            <span class="pay-rrow-net-lbl">Net{{ $hasEstim ? ' estimé' : '' }} · {{ $row['nb_mois'] }} mois</span>
            <strong class="pay-rrow-net-val">{{ $fmt($row['net']) }} <small>FCFA</small></strong>
            <div class="pay-rrow-statusline">
                <span class="pay-rrow-badge" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $statutLabels[$row['statut']] ?? $row['statut'] }}</span>
                @if($row['nb_a_preparer'] > 0)
                    <span class="pay-rrow-hint"><i class="fas fa-pen-ruler"></i> {{ $row['nb_a_preparer'] }} à préparer</span>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="pay-empty">
        <i class="fas fa-user-clock"></i>
        <p>Aucun enseignant avec des heures facturables sur cette période.</p>
    </div>
@endforelse
