@php
    $fmt = fn($v) => number_format((float) $v, 0, ',', ' ');
    $fmtH = function ($v) { $h=(int)floor($v); $m=(int)round(($v-$h)*60); return $h.'h'.($m>0?sprintf('%02d',$m):''); };
    $totalBase = array_sum(array_map(fn($r) => (float) $r['base'], $rows));
    $totalRet  = array_sum(array_map(fn($r) => (float) $r['retenues'], $rows));
    $totalNet  = array_sum(array_map(fn($r) => (float) $r['net'], $rows));
@endphp
<x-pdf-document :title="$reportTitle" :subtitle="$reportSubtitle" :filters="$reportFilters" orientation="landscape">
    <style>
        .pp-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .pp-table th { background: #0453cb; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; }
        .pp-table th.pp-num { text-align: right; }   /* sinon `.pp-table th` (spécificité +) écrase `.pp-num` → en-têtes mal alignés */
        .pp-table td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        .pp-table td.pp-num { text-align: right; }
        .pp-table tr:nth-child(even) td { background: #f8fafc; }
        .pp-num { text-align: right; white-space: nowrap; }
        .pp-net { font-weight: 700; color: #0453cb; }
        .pp-badge { font-size: 8px; font-weight: 700; padding: 1px 5px; border-radius: 3px; }
        .pp-total td { border-top: 2px solid #0453cb; font-weight: 700; background: #eff6ff !important; }
        .pp-est { color: #b45309; font-size: 8px; }
    </style>
    <table class="pp-table">
        <thead>
            <tr>
                <th>Enseignant</th>
                <th class="pp-num">Mois</th>
                <th class="pp-num">Heures réalisées</th>
                <th class="pp-num">Base</th>
                <th class="pp-num">Retenues</th>
                <th class="pp-num">Net à payer</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                @php $estime = ($r['nb_a_preparer'] ?? 0) > 0; @endphp
                <tr>
                    <td>{{ $r['name'] }}</td>
                    <td class="pp-num">{{ $r['nb_mois'] ?? 1 }}</td>
                    <td class="pp-num">{{ $fmtH($r['heures']) }}</td>
                    <td class="pp-num">{{ $fmt($r['base']) }}</td>
                    <td class="pp-num">− {{ $fmt($r['retenues']) }}</td>
                    <td class="pp-num pp-net">{{ $fmt($r['net']) }}@if($estime)<br><span class="pp-est">estimé</span>@endif</td>
                    <td>{{ $statutLabels[$r['statut']] ?? $r['statut'] }}</td>
                </tr>
            @endforeach
            <tr class="pp-total">
                <td>TOTAL ({{ count($rows) }} enseignant{{ count($rows) > 1 ? 's' : '' }})</td>
                <td class="pp-num"></td>
                <td class="pp-num"></td>
                <td class="pp-num">{{ $fmt($totalBase) }}</td>
                <td class="pp-num">− {{ $fmt($totalRet) }}</td>
                <td class="pp-num pp-net">{{ $fmt($totalNet) }} FCFA</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</x-pdf-document>
