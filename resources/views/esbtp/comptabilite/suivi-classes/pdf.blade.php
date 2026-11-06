<x-pdf-document :title="$reportTitle" :subtitle="$reportSubtitle" :filters="$reportFilters" orientation="landscape">
    <style>
        .sfc-report { table-layout: fixed; font-size: 7.3pt; }
        .sfc-report thead { display: table-header-group; }
        .sfc-report tr { page-break-inside: avoid; }
        .sfc-report .money { text-align: right; white-space: nowrap; }
        .sfc-report .total td { font-weight: bold; background: #e8f0fc; }
        .sfc-report td { overflow-wrap: break-word; word-wrap: break-word; }
        .sfc-note { margin: 12px 0; color: #475569; font-size: 8pt; }
    </style>
    <table class="pdf-kpi-table" aria-label="Synthèse du rapport">
        <tr>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Attendu</div><div class="pdf-kpi-value">{{ number_format($rapport['totaux']['attendu'], 0, ',', ' ') }}</div></td>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Règlements imputés</div><div class="pdf-kpi-value">{{ number_format($rapport['totaux']['regle'], 0, ',', ' ') }}</div></td>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Reste à payer</div><div class="pdf-kpi-value">{{ number_format($rapport['totaux']['restant'], 0, ',', ' ') }}</div></td>
            <td class="pdf-kpi-cell"><div class="pdf-kpi-label">Échu impayé</div><div class="pdf-kpi-value">{{ number_format($rapport['totaux']['echu'], 0, ',', ' ') }}</div></td>
        </tr>
    </table>
    <p class="sfc-note">Montants dans la monnaie de l'établissement. Le « reste à payer » découle des échéanciers et des règlements ventilés. Les reliquats historiques ne sont pas ajoutés aux frais annuels une seconde fois. Les encaissements de caisse ne se déduisent pas d'une ventilation par catégorie.</p>
    <table class="pdf-detail-table sfc-report">
        <thead>
        <tr>
            <th>Classe</th><th>Effectif</th><th class="money">Attendu</th><th class="money">Réglé</th>
            @unless($rapport['filtered_category'])<th class="money">Caisse nette</th><th class="money">Écart d'imputation</th>@endunless
            <th class="money">Reste</th><th class="money">Échu</th><th class="money">Taux</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rapport['classes'] as $ligne)
        <tr>
            <td>{{ $ligne['classe'] }}<div style="font-size:7pt;color:#64748b;">{{ $ligne['filiere'] }} · {{ $ligne['niveau'] }}</div></td>
            <td>{{ $ligne['effectif'] }}</td>
            @foreach(['attendu', 'regle'] as $col)<td class="money">{{ number_format($ligne[$col], 0, ',', ' ') }}</td>@endforeach
            @unless($rapport['filtered_category'])
                @foreach(['encaisse', 'ajustements'] as $col)<td class="money">{{ number_format($ligne[$col], 0, ',', ' ') }}</td>@endforeach
            @endunless
            @foreach(['restant', 'echu'] as $col)<td class="money">{{ number_format($ligne[$col], 0, ',', ' ') }}</td>@endforeach
            <td class="money">{{ $ligne['taux'] === null ? '—' : number_format($ligne['taux'], 1, ',', ' ') . ' %' }}</td>
        </tr>
        @empty
        <tr><td colspan="{{ $rapport['filtered_category'] ? 7 : 9 }}" style="text-align:center;">Aucune classe correspondante.</td></tr>
        @endforelse
        <tr class="total">
            <td>TOTAL GÉNÉRAL</td>
            <td>{{ $rapport['totaux']['effectif'] }}</td>
            @foreach(['attendu', 'regle'] as $col)<td class="money">{{ number_format($rapport['totaux'][$col], 0, ',', ' ') }}</td>@endforeach
            @unless($rapport['filtered_category'])
                @foreach(['encaisse', 'ajustements'] as $col)<td class="money">{{ number_format($rapport['totaux'][$col], 0, ',', ' ') }}</td>@endforeach
            @endunless
            @foreach(['restant', 'echu'] as $col)<td class="money">{{ number_format($rapport['totaux'][$col], 0, ',', ' ') }}</td>@endforeach
            <td class="money">{{ $rapport['totaux']['taux'] === null ? '—' : number_format($rapport['totaux']['taux'], 1, ',', ' ') . ' %' }}</td>
        </tr>
        </tbody>
    </table>
</x-pdf-document>
