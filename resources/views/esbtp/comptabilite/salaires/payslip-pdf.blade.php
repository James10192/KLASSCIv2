@php
    $fmt = fn($v) => number_format((float) $v, 0, ',', ' ');
    $fmtH = function ($v) { $h=(int)floor($v); $m=(int)round(($v-$h)*60); return $h.'h'.($m>0?sprintf('%02d',$m):''); };
    $teacherName = $salaire->teacher->user->name ?? $salaire->teacher->name ?? 'Enseignant';
    $brut = (float) $salaire->salaire_base + (float) $salaire->primes;
@endphp
<x-pdf-document
    title="Bulletin de paie"
    :subtitle="$teacherName . ' — ' . $moisLabel . ' ' . $salaire->annee"
    :filters="['Enseignant' => $teacherName, 'Période' => $moisLabel . ' ' . $salaire->annee, 'Statut' => $salaire->statutLabel()]"
    orientation="portrait">

    <style>
        .bp-meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px; }
        .bp-meta td { padding: 4px 8px; border: 1px solid #e5e7eb; }
        .bp-meta .bp-k { color: #64748b; width: 22%; background: #f8fafc; }
        .bp-meta .bp-v { font-weight: 700; color: #1e293b; }
        .bp-sec-title { font-size: 11px; font-weight: 700; color: #0453cb; text-transform: uppercase; letter-spacing: .3px; margin: 14px 0 5px; padding-bottom: 3px; border-bottom: 2px solid #0453cb; }
        .bp-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .bp-table th { background: #0453cb; color: #fff; text-align: left; padding: 5px 8px; font-size: 9px; text-transform: uppercase; }
        .bp-table th.bp-num, .bp-table td.bp-num { text-align: right; white-space: nowrap; }
        .bp-table td { padding: 5px 8px; border-bottom: 1px solid #eef2f7; }
        .bp-table tr:nth-child(even) td { background: #f8fafc; }
        .bp-detail { color: #94a3b8; font-size: 8px; }
        .bp-sub td { border-top: 2px solid #cbd5e1; font-weight: 700; background: #eff6ff !important; }
        .bp-sub--neg td { background: #fef2f2 !important; color: #b91c1c; }
        .bp-net { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .bp-net td { padding: 10px 12px; background: linear-gradient(135deg, #0a3d8f, #0453cb); color: #fff; }
        .bp-net .bp-net-lbl { font-size: 11px; font-weight: 700; }
        .bp-net .bp-net-val { font-size: 18px; font-weight: 800; text-align: right; }
        .bp-pay { margin-top: 12px; font-size: 10px; }
        .bp-sign { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .bp-sign td { width: 50%; padding: 6px 10px; font-size: 9px; color: #64748b; vertical-align: top; }
        .bp-sign-line { border-top: 1px solid #94a3b8; margin-top: 38px; padding-top: 4px; color: #1e293b; font-weight: 700; }
    </style>

    {{-- Identité --}}
    <table class="bp-meta">
        <tr>
            <td class="bp-k">Enseignant</td><td class="bp-v">{{ $teacherName }}</td>
            <td class="bp-k">Période</td><td class="bp-v">{{ $moisLabel }} {{ $salaire->annee }}</td>
        </tr>
        <tr>
            <td class="bp-k">Heures réalisées</td><td class="bp-v">{{ $fmtH($salaire->heures_total) }}</td>
            <td class="bp-k">Statut</td><td class="bp-v">{{ $salaire->statutLabel() }}</td>
        </tr>
    </table>

    {{-- Gains --}}
    <div class="bp-sec-title">Gains — heures réalisées × taux</div>
    <table class="bp-table">
        <thead>
            <tr><th>Désignation</th><th class="bp-num">Base</th><th class="bp-num">Montant</th></tr>
        </thead>
        <tbody>
            @foreach($gains as $g)
                <tr>
                    <td>{{ $g->libelle }}</td>
                    <td class="bp-num">@if($g->heures){{ $fmtH($g->heures) }} × {{ $fmt($g->taux) }}@else—@endif</td>
                    <td class="bp-num">{{ $fmt($g->montant) }}</td>
                </tr>
            @endforeach
            <tr class="bp-sub"><td>Brut</td><td class="bp-num"></td><td class="bp-num">{{ $fmt($brut) }} FCFA</td></tr>
        </tbody>
    </table>

    {{-- Retenues --}}
    <div class="bp-sec-title">Retenues</div>
    <table class="bp-table">
        <thead>
            <tr><th>Désignation</th><th class="bp-num">Montant</th></tr>
        </thead>
        <tbody>
            @forelse($retenues as $r)
                <tr><td>{{ $r->libelle }}</td><td class="bp-num">− {{ $fmt($r->montant) }}</td></tr>
            @empty
                <tr><td colspan="2" style="color:#94a3b8;">Aucune retenue</td></tr>
            @endforelse
            <tr class="bp-sub bp-sub--neg"><td>Total retenues</td><td class="bp-num">− {{ $fmt($salaire->retenues) }} FCFA</td></tr>
        </tbody>
    </table>

    {{-- Net --}}
    <table class="bp-net">
        <tr>
            <td class="bp-net-lbl">NET À PAYER</td>
            <td class="bp-net-val">{{ $fmt($salaire->net_a_payer) }} FCFA</td>
        </tr>
    </table>

    {{-- Règlement --}}
    @if($salaire->isPaye())
        <div class="bp-pay">
            <strong>Règlement :</strong>
            {{ $modeLabel ?? '—' }}
            @if($salaire->date_paiement) · le {{ \Carbon\Carbon::parse($salaire->date_paiement)->format('d/m/Y') }} @endif
            @if($salaire->reference_paiement) · réf. {{ $salaire->reference_paiement }} @endif
        </div>
    @endif

    {{-- Signatures --}}
    <table class="bp-sign">
        <tr>
            <td>Pour l'établissement<div class="bp-sign-line">Le Directeur / La Comptabilité</div></td>
            <td>Reçu par l'enseignant<div class="bp-sign-line">{{ $teacherName }}</div></td>
        </tr>
    </table>
</x-pdf-document>
