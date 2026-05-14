{{-- Carte UE pliable pour la tab Suivi des heures LMD --}}
{{-- Recoit : $ueRow (item de $lmdUesAvecEcues) + $ueIdx (index pour collapse state) --}}
@php
    $totaux = $ueRow['totaux'];
    $totalP = $totaux['cm_p'] + $totaux['td_p'] + $totaux['tp_p'];
    $totalR = $totaux['cm_r'] + $totaux['td_r'] + $totaux['tp_r'];
    $pct = $ueRow['pct_realise'];

    $progressColor = $pct >= 80 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : ($pct > 0 ? '#3b7ddb' : '#94a3b8'));

    // Categorisation visuelle TypeUE en 3 tones monochrome bleu (rule premium-redesign.md).
    $typeUeKey = $ueRow['type_ue']?->value ?? 'orphan';
    $typeToneClass = match ($typeUeKey) {
        'fondamentale', 'specialite' => 'sh-ue-chip--primary',
        'methodologique', 'culture_generale' => 'sh-ue-chip--accent',
        default => 'sh-ue-chip--muted',
    };

    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',') ?: '0';
@endphp

<div class="sh-ue-card" :class="openIds.includes({{ $ueIdx }}) ? 'sh-ue-card--open' : ''">
    <button type="button" class="sh-ue-header" @click="openIds.includes({{ $ueIdx }}) ? openIds = openIds.filter(i => i !== {{ $ueIdx }}) : openIds.push({{ $ueIdx }})">
        <div class="sh-ue-header-left">
            <i class="fas fa-chevron-right sh-ue-caret" :class="openIds.includes({{ $ueIdx }}) ? 'sh-ue-caret--open' : ''"></i>
            @if($ueRow['is_orphan'])
                <span class="sh-ue-chip sh-ue-chip--orphan"><i class="fas fa-exclamation-triangle"></i> Hors UE</span>
            @else
                <span class="sh-ue-chip {{ $typeToneClass }}">{{ $ueRow['type_label'] ?? 'UE' }}</span>
            @endif
            <div class="sh-ue-title">
                @if(!$ueRow['is_orphan'] && $ueRow['code'])
                    <span class="sh-ue-code">{{ $ueRow['code'] }}</span>
                @endif
                <span class="sh-ue-name">{{ $ueRow['name'] }}</span>
            </div>
        </div>
        <div class="sh-ue-header-right">
            @if($ueRow['total_credits'] > 0)
                <span class="sh-ue-credits">
                    <i class="fas fa-award"></i>{{ $ueRow['total_credits'] }} crédits
                </span>
            @endif
            <span class="sh-ue-count">{{ $ueRow['nb_ecues'] }} ECUE{{ $ueRow['nb_ecues'] > 1 ? 's' : '' }}</span>
        </div>
    </button>

    <div class="sh-ue-totaux">
        <div class="sh-ue-bar-wrap" title="{{ $pct }}% du volume total réalisé">
            <div class="sh-ue-bar" style="width:{{ $pct }}%;background:{{ $progressColor }};"></div>
        </div>
        <div class="sh-ue-totaux-row">
            <span class="sh-ue-tot-chip"><i class="fas fa-chalkboard-user"></i>CM <strong>{{ $fmt($totaux['cm_r']) }}h</strong>/{{ $fmt($totaux['cm_p']) }}h</span>
            <span class="sh-ue-tot-chip"><i class="fas fa-pen-ruler"></i>TD <strong>{{ $fmt($totaux['td_r']) }}h</strong>/{{ $fmt($totaux['td_p']) }}h</span>
            <span class="sh-ue-tot-chip"><i class="fas fa-flask-vial"></i>TP <strong>{{ $fmt($totaux['tp_r']) }}h</strong>/{{ $fmt($totaux['tp_p']) }}h</span>
            <span class="sh-ue-tot-chip sh-ue-tot-chip--strong"><i class="fas fa-clock"></i>Total <strong>{{ $fmt($totalR) }}h</strong>/{{ $fmt($totalP) }}h</span>
        </div>
    </div>

    <div class="sh-ue-body" x-show="openIds.includes({{ $ueIdx }})" x-cloak x-transition.opacity>
        <table class="sh-ecue-table">
            <thead>
                <tr>
                    <th>ECUE</th>
                    <th class="sh-num">CM</th>
                    <th class="sh-num">TD</th>
                    <th class="sh-num">TP</th>
                    <th class="sh-num">Total</th>
                    <th class="sh-num sh-hide-sm">Coef</th>
                    <th class="sh-num sh-hide-sm">ECTS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ueRow['ecues'] as $ecue)
                    @php
                        $mat = $ecue['matiere'];
                        $ecueCode = $mat->code ?? null;
                    @endphp
                    <tr>
                        <td>
                            @if($ecueCode)
                                <span class="sh-ecue-code">{{ $ecueCode }}</span>
                            @endif
                            <span class="sh-ecue-name">{{ $mat->name }}</span>
                        </td>
                        <td class="sh-num"><strong>{{ $fmt($ecue['cm_r']) }}</strong>h <span class="sh-num-muted">/ {{ $fmt($ecue['cm_p']) }}h</span></td>
                        <td class="sh-num"><strong>{{ $fmt($ecue['td_r']) }}</strong>h <span class="sh-num-muted">/ {{ $fmt($ecue['td_p']) }}h</span></td>
                        <td class="sh-num"><strong>{{ $fmt($ecue['tp_r']) }}</strong>h <span class="sh-num-muted">/ {{ $fmt($ecue['tp_p']) }}h</span></td>
                        <td class="sh-num sh-num-strong"><strong>{{ $fmt($ecue['total_r']) }}</strong>h <span class="sh-num-muted">/ {{ $fmt($ecue['total_p']) }}h</span></td>
                        <td class="sh-num sh-hide-sm">{{ $fmt($ecue['coefficient']) }}</td>
                        <td class="sh-num sh-hide-sm">{{ $ecue['credits_ects'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
