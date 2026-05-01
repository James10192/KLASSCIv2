<x-pdf-document
    title="{{ $reportTitle ?? 'Recouvrement quotidien' }}"
    subtitle="{{ $reportSubtitle ?? 'Liste priorisée des étudiants à relancer' }}"
    :filters="$reportFilters ?? []"
    orientation="landscape">

    <style>
        .kpi-strip {
            display: table; width: 100%; border-collapse: collapse;
            margin-bottom: 14px; border: 1px solid #e2e8f0;
            border-radius: 4px; overflow: hidden;
        }
        .kpi-cell {
            display: table-cell; text-align: center;
            padding: 10px 8px; border-right: 1px solid #e2e8f0;
            background: #fafbfc; width: 25%;
            -webkit-print-color-adjust: exact; color-adjust: exact;
        }
        .kpi-cell:last-child { border-right: none; }
        .kpi-cell-value { font-size: 14px; font-weight: 700; color: #0453cb; }
        .kpi-cell-label {
            font-size: 8px; color: #64748b;
            text-transform: uppercase; letter-spacing: .03em; margin-top: 2px;
        }

        .report-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 6px; }
        .report-table thead th {
            color: #fff; font-weight: 600; padding: 6px 4px; text-align: left;
            text-transform: uppercase; letter-spacing: .3px; font-size: 8px;
            -webkit-print-color-adjust: exact; color-adjust: exact;
        }
        .report-table tbody td { padding: 5px 4px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        .report-table tbody tr:nth-child(even) { background: #fafbfc; }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .amount-cell { font-weight: 600; color: #0f172a; }
        .phone-cell  { font-family: 'Courier New', monospace; font-size: 8.5px; color: #334155; }

        .chip { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; font-weight: 600; }
        .chip-retard { background: rgba(245,158,11,.15); color: #b45309; }

        .badge {
            display: inline-block; padding: 2px 6px; border-radius: 8px;
            font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        }
        .badge-haut  { background: rgba(220,38,38,.15); color: #dc2626; }
        .badge-moyen { background: rgba(245,158,11,.15); color: #b45309; }
        .badge-bas   { background: rgba(16,185,129,.15); color: #047857; }

        .report-footer-summary {
            margin-top: 10px; padding: 8px 12px;
            background: #f1f5f9; border-radius: 4px;
            font-size: 9px; color: #1e293b;
        }
        .report-footer-summary strong { color: #0453cb; }
    </style>

    @if(!empty($kpis))
        <div class="kpi-strip">
            <div class="kpi-cell">
                <div class="kpi-cell-value">{{ $kpis['buckets']['haut'] ?? 0 }}</div>
                <div class="kpi-cell-label">Haut risque</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-cell-value">{{ number_format($kpis['total_solde_haut_risque'] ?? 0, 0, ',', ' ') }}</div>
                <div class="kpi-cell-label">FCFA non recouvrés (haut)</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-cell-value">{{ $kpis['buckets']['moyen'] ?? 0 }}</div>
                <div class="kpi-cell-label">Sous surveillance</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-cell-value">{{ $kpis['total_actifs'] ?? count($rows) }}</div>
                <div class="kpi-cell-label">Étudiants actifs</div>
            </div>
        </div>
    @endif

    <table class="report-table">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Étudiant</th>
                <th>Classe</th>
                <th>Téléphone</th>
                <th class="text-right">Solde restant</th>
                <th class="text-center">Retard</th>
                <th class="text-center">% payé</th>
                <th class="text-center">Niveau</th>
                <th class="text-center">Score</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr class="row-{{ $row['level'] ?? 'bas' }}">
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td><strong>{{ $row['etudiant_nom'] ?? '—' }}</strong></td>
                    <td>{{ $row['classe_nom'] ?? '—' }}</td>
                    <td class="phone-cell">
                        {{ \App\Domain\Notifications\PhoneFormatter::toReadable($row['phone'] ?? null) ?? '—' }}
                    </td>
                    <td class="text-right amount-cell">
                        {{ number_format($row['solde_restant'] ?? 0, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="text-center">
                        @php $r = (int) ($row['jours_retard'] ?? 0); @endphp
                        @if($r > 0)
                            <span class="chip chip-retard">{{ $r }} j</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-center">{{ round((($row['ratio_paye'] ?? 0)) * 100, 0) }}%</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $row['level'] ?? 'bas' }}">{{ ucfirst($row['level'] ?? 'bas') }}</span>
                    </td>
                    <td class="text-center">{{ number_format($row['score'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center" style="padding:20px;color:#64748b;">Aucun étudiant à risque dans ce périmètre.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(count($rows) > 0)
        <div class="report-footer-summary">
            Total affiché : <strong>{{ count($rows) }}</strong> étudiant(s) ·
            Solde cumulé : <strong>{{ number_format(collect($rows)->sum('solde_restant'), 0, ',', ' ') }} FCFA</strong>
        </div>
    @endif
</x-pdf-document>

