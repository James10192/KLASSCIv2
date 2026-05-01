<x-pdf-document
    title="{{ $reportTitle ?? 'Analytics financiers' }}"
    subtitle="{{ $reportSubtitle ?? '' }}"
    :filters="$reportFilters ?? []">

    <style>
        .section { margin-bottom: 18px; }
        .section-title {
            font-size: 12px; font-weight: 700; color: #0453cb;
            border-bottom: 2px solid #0453cb; padding-bottom: 4px; margin: 0 0 8px;
            text-transform: uppercase; letter-spacing: .4px;
            -webkit-print-color-adjust: exact; color-adjust: exact;
        }
        .kpi-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .kpi-cell {
            display: table-cell; text-align: center;
            padding: 12px 8px; border: 1px solid #e2e8f0;
            background: #fafbfc; width: 25%;
        }
        .kpi-cell-value { font-size: 14px; font-weight: 700; color: #0453cb; }
        .kpi-cell-label { font-size: 8px; color: #64748b; text-transform: uppercase; margin-top: 3px; }

        .cf-block {
            background: #fafbfc; padding: 10px 12px;
            border-radius: 4px; border-left: 3px solid #0453cb; margin-bottom: 8px;
        }
        .cf-value { font-size: 18px; font-weight: 700; color: #0453cb; margin: 0; }
        .cf-meta { font-size: 9px; color: #64748b; margin-top: 4px; }
        .cf-confidence {
            display: inline-block; padding: 2px 8px; border-radius: 8px;
            font-size: 8px; font-weight: 700; margin-top: 4px;
        }
        .cf-confidence-tres_fiable { background: rgba(16,185,129,.15); color: #047857; }
        .cf-confidence-fiable { background: rgba(4,83,203,.15); color: #0453cb; }
        .cf-confidence-indicatif { background: rgba(245,158,11,.15); color: #b45309; }

        .reasons { margin-top: 8px; padding-left: 14px; }
        .reasons li { font-size: 9px; color: #1e293b; margin-bottom: 2px; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .data-table thead th {
            color: #fff; font-weight: 600; padding: 6px 4px;
            text-transform: uppercase; letter-spacing: .3px; font-size: 8px; text-align: left;
            -webkit-print-color-adjust: exact; color-adjust: exact;
        }
        .data-table tbody td { padding: 5px 4px; border-bottom: 1px solid #e5e7eb; }
        .data-table tbody tr:nth-child(even) { background: #fafbfc; }

        .badge {
            display: inline-block; padding: 1px 6px; border-radius: 8px;
            font-size: 8px; font-weight: 700; text-transform: uppercase;
        }
        .badge-haut { background: rgba(220,38,38,.15); color: #dc2626; }
        .badge-moyen { background: rgba(245,158,11,.15); color: #b45309; }
        .badge-bas { background: rgba(16,185,129,.15); color: #047857; }

        .anomaly { display: table; width: 100%; margin-bottom: 6px; padding: 8px 10px; border-radius: 4px; border-left: 3px solid #94a3b8; background: #fafbfc; }
        .anomaly-critical { border-left-color: #dc2626; background: rgba(220,38,38,.04); }
        .anomaly-warning  { border-left-color: #f59e0b; background: rgba(245,158,11,.04); }
        .anomaly-meta { font-size: 8px; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
        .anomaly-message { font-size: 9px; color: #1e293b; }

        .empty { text-align: center; padding: 14px; color: #64748b; font-size: 9px; font-style: italic; }
    </style>

    {{-- ===== Section Cash Flow ===== --}}
    <div class="section">
        <h2 class="section-title">Projection cash-flow — mois prochain</h2>
        @if($cashFlow->isAvailable())
            <div class="cf-block">
                <p class="cf-value">{{ number_format($cashFlow->value, 0, ',', ' ') }} FCFA</p>
                @if($cashFlow->confidenceInterval)
                    <p class="cf-meta">
                        Intervalle 95% : de {{ number_format($cashFlow->confidenceInterval->lower, 0, ',', ' ') }}
                        à {{ number_format($cashFlow->confidenceInterval->upper, 0, ',', ' ') }} FCFA
                    </p>
                @endif
                <span class="cf-confidence cf-confidence-{{ $cashFlow->confidenceLabel }}">
                    @if($cashFlow->confidenceLabel === 'tres_fiable') Très fiable
                    @elseif($cashFlow->confidenceLabel === 'fiable') Fiable
                    @else Indicatif
                    @endif
                </span>
                <ul class="reasons">
                    @foreach($cashFlow->explanation as $r)
                        <li>{{ $r }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="empty">{{ $cashFlow->explanation[0] ?? 'Indisponible.' }}</div>
        @endif
    </div>

    {{-- ===== Section Default Risk ===== --}}
    <div class="section">
        <h2 class="section-title">Risque de défaut de paiement</h2>
        @if($defaultRisk->isAvailable())
            @php
                $buckets = $defaultRisk->metadata['buckets'] ?? [];
                $top = $defaultRisk->metadata['top_at_risk'] ?? [];
            @endphp
            <div class="kpi-grid">
                <div class="kpi-cell">
                    <div class="kpi-cell-value">{{ $buckets['haut'] ?? 0 }}</div>
                    <div class="kpi-cell-label">Haut risque</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-cell-value">{{ $buckets['moyen'] ?? 0 }}</div>
                    <div class="kpi-cell-label">Surveillance</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-cell-value">{{ $defaultRisk->metadata['total_actifs'] ?? 0 }}</div>
                    <div class="kpi-cell-label">Étudiants actifs</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-cell-value">{{ number_format($defaultRisk->metadata['total_solde_haut_risque'] ?? 0, 0, ',', ' ') }}</div>
                    <div class="kpi-cell-label">FCFA non recouvrés (haut)</div>
                </div>
            </div>

            @if(!empty($top))
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:25px;">#</th>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th style="text-align:right;">Solde</th>
                            <th style="text-align:center;">Retard</th>
                            <th style="text-align:center;">Niveau</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($top, 0, 25) as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $row['etudiant_nom'] ?? '—' }}</strong></td>
                                <td>{{ $row['classe_nom'] ?? '—' }}</td>
                                <td style="text-align:right;">{{ number_format($row['solde_restant'] ?? 0, 0, ',', ' ') }}</td>
                                <td style="text-align:center;">{{ $row['jours_retard'] ?? 0 }} j</td>
                                <td style="text-align:center;"><span class="badge badge-{{ $row['level'] ?? 'bas' }}">{{ ucfirst($row['level'] ?? '') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($top) > 25)
                    <p style="font-size:8px;color:#64748b;text-align:right;margin-top:4px;">… et {{ count($top) - 25 }} autres (voir l'onglet Excel pour le détail complet)</p>
                @endif
            @endif
        @else
            <div class="empty">{{ $defaultRisk->explanation[0] ?? 'Indisponible.' }}</div>
        @endif
    </div>

    {{-- ===== Section Anomalies ===== --}}
    <div class="section">
        <h2 class="section-title">Anomalies financières</h2>
        @if(empty($anomalies))
            <div class="empty">Aucune anomalie détectée.</div>
        @else
            @foreach(array_slice($anomalies, 0, 15) as $alert)
                <div class="anomaly anomaly-{{ $alert->severity }}">
                    <div class="anomaly-meta">
                        {{ strtoupper($alert->severity) }} · {{ str_replace('_', ' ', $alert->type) }} · score {{ number_format($alert->score, 2) }}
                    </div>
                    <div class="anomaly-message">{{ $alert->message }}</div>
                </div>
            @endforeach
            @if(count($anomalies) > 15)
                <p style="font-size:8px;color:#64748b;text-align:right;margin-top:4px;">… et {{ count($anomalies) - 15 }} autres anomalies</p>
            @endif
        @endif
    </div>
</x-pdf-document>
