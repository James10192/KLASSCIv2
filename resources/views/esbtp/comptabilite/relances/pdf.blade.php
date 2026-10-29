@php
    $isFirstChunk = $isFirstChunk ?? true;
    $isLastChunk = $isLastChunk ?? true;
    $rowOffset = $rowOffset ?? 0;

    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $primary = $pdfCfg['primary_color'] ?? '#0453cb';
    $headerText = $pdfCfg['header_text_color'] ?? '#ffffff';
    $secondary = $pdfCfg['secondary_color'] ?? '#64748b';
    $textColor = $pdfCfg['text_color'] ?? '#1f2937';

    $riskLabels = [
        'critical' => ['label' => 'Critique', 'class' => 'risk-critical'],
        'high' => ['label' => 'Élevé', 'class' => 'risk-high'],
        'medium' => ['label' => 'Moyen', 'class' => 'risk-medium'],
        'low' => ['label' => 'Faible', 'class' => 'risk-low'],
    ];

    $totalImpaye = $globalStats['total_impaye'] ?? $relances->sum('solde_restant');
    $nbCritical = $globalStats['nb_critical'] ?? $relances->where('risk_level', 'critical')->count();
    $nbHigh = $globalStats['nb_high'] ?? $relances->where('risk_level', 'high')->count();
    $nbMedium = $globalStats['nb_medium'] ?? $relances->where('risk_level', 'medium')->count();
    $nbLow = $globalStats['nb_low'] ?? $relances->where('risk_level', 'low')->count();
    $nbTotal = $globalStats['nb_total'] ?? $relances->count();

    $filtersForDocument = [];
    if (!empty($activeFilters)) {
        foreach ($activeFilters as $index => $filter) {
            $filtersForDocument['Filtre ' . ($index + 1)] = $filter;
        }
    }
    $filtersForDocument['Année'] = $anneeActive->name ?? 'Toutes';
@endphp

<x-pdf-document
    title="Rapport des relances"
    subtitle="Étudiants avec soldes impayés"
    :filters="$filtersForDocument"
    orientation="landscape">

    <style>
        .relances-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8.3pt;
        }
        .relances-table th {
            background-color: {{ $primary }} !important;
            color: {{ $headerText }} !important;
            font-weight: 700;
            padding: 6px 4px;
            text-align: left;
            border-right: 1px solid rgba(255,255,255,.22);
        }
        .relances-table th:last-child { border-right: 0; }
        .relances-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            color: {{ $textColor }};
            vertical-align: middle;
        }
        .relances-table tbody tr:nth-child(even) td { background: #f8fafc; }
        .row-number {
            font-weight: 700;
            color: {{ $primary }} !important;
        }
        .student-matricule {
            font-family: "Courier New", monospace;
            font-size: 7.5pt;
            color: {{ $secondary }};
        }
        .student-name {
            font-weight: 700;
        }
        .amount {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
        }
        .risk-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 7pt;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }
        .risk-critical { background: #1e293b; }
        .risk-high { background: #dc2626; }
        .risk-medium { background: #f59e0b; }
        .risk-low { background: #16a34a; }
        .empty-state {
            padding: 24px 10px;
            text-align: center;
            color: {{ $secondary }};
        }
    </style>

    @if($isFirstChunk)
        <table class="pdf-kpi-table">
            <tr>
                <td class="pdf-kpi-cell" style="width:25%;">
                    <div class="pdf-kpi-label">Total impayé</div>
                    <div class="pdf-kpi-value">{{ number_format($totalImpaye, 0, ',', ' ') }}</div>
                    <div class="pdf-kpi-sub">FCFA</div>
                </td>
                <td class="pdf-kpi-cell" style="width:25%;">
                    <div class="pdf-kpi-label">Critique</div>
                    <div class="pdf-kpi-value">{{ $nbCritical }}</div>
                    <div class="pdf-kpi-sub">Aucun paiement</div>
                </td>
                <td class="pdf-kpi-cell" style="width:25%;">
                    <div class="pdf-kpi-label">Élevé</div>
                    <div class="pdf-kpi-value">{{ $nbHigh }}</div>
                    <div class="pdf-kpi-sub">Paiement très faible</div>
                </td>
                <td class="pdf-kpi-cell" style="width:25%;">
                    <div class="pdf-kpi-label">Moyen</div>
                    <div class="pdf-kpi-value">{{ $nbMedium }}</div>
                    <div class="pdf-kpi-sub">Suivi requis</div>
                </td>
            </tr>
        </table>
    @endif

    @if($relances->count() > 0)
        <table class="relances-table">
            <thead>
                <tr>
                    <th style="width:4%;">N°</th>
                    <th style="width:9%;">Matricule</th>
                    <th>Nom & prénoms</th>
                    <th style="width:11%;">Classe</th>
                    <th style="width:11%;">Filière</th>
                    <th style="width:10%; text-align:right;">Total dû</th>
                    <th style="width:10%; text-align:right;">Payé</th>
                    <th style="width:11%; text-align:right;">Solde</th>
                    <th style="width:8%;">Risque</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relances as $index => $row)
                    @php
                        $riskInfo = $riskLabels[$row['risk_level'] ?? 'low'] ?? $riskLabels['low'];
                        $rowNum = $rowOffset + $index + 1;
                    @endphp
                    <tr>
                        <td><span class="row-number">{{ $rowNum }}</span></td>
                        <td><span class="student-matricule">{{ $row['matricule'] ?? 'N/A' }}</span></td>
                        <td><span class="student-name">{{ trim(($row['nom'] ?? '') . ' ' . ($row['prenoms'] ?? '')) }}</span></td>
                        <td>{{ $row['classe'] ?? 'N/A' }}</td>
                        <td>{{ $row['filiere'] ?? 'N/A' }}</td>
                        <td class="amount">{{ number_format($row['total_du'] ?? 0, 0, ',', ' ') }}</td>
                        <td class="amount">{{ number_format($row['total_paye'] ?? 0, 0, ',', ' ') }}</td>
                        <td class="amount">{{ number_format($row['solde_restant'] ?? 0, 0, ',', ' ') }}</td>
                        <td><span class="risk-badge {{ $riskInfo['class'] }}">{{ $riskInfo['label'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($isLastChunk)
            <table class="pdf-detail-table">
                <thead>
                    <tr>
                        <th>Récapitulatif</th>
                        <th style="width:18%; text-align:right;">Critique</th>
                        <th style="width:18%; text-align:right;">Élevé</th>
                        <th style="width:18%; text-align:right;">Moyen</th>
                        <th style="width:18%; text-align:right;">Faible</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $nbTotal }} étudiant(s) à relancer - {{ number_format($totalImpaye, 0, ',', ' ') }} FCFA</td>
                        <td class="right">{{ $nbCritical }}</td>
                        <td class="right">{{ $nbHigh }}</td>
                        <td class="right">{{ $nbMedium }}</td>
                        <td class="right">{{ $nbLow }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    @else
        <div class="empty-state">Aucun étudiant avec solde impayé ne correspond aux filtres sélectionnés.</div>
    @endif
</x-pdf-document>
