<x-pdf-document
    :title="'PV de Réconciliation Caisse'"
    :subtitle="'Session ' . $session->code"
    :filters="[
        'Période' => optional($session->period_start)->format('d/m/Y') . ($session->period_end != $session->period_start ? ' → ' . optional($session->period_end)->format('d/m/Y') : ''),
        'Fréquence' => ucfirst($session->frequency),
        'Statut' => $session->status->label(),
    ]"
    orientation="portrait"
    signature-block="director">

    <style>
        .rec-pdf-section { margin-bottom: 1.2rem; }
        .rec-pdf-h2 {
            font-size: 11pt; font-weight: 700; color: #0453cb;
            border-bottom: 2px solid #0453cb; padding-bottom: 2pt;
            margin-bottom: 6pt;
            text-transform: uppercase; letter-spacing: 0.5pt;
        }
        .rec-pdf-meta {
            width: 100%; border-collapse: collapse;
            font-size: 9pt; margin-bottom: 8pt;
        }
        .rec-pdf-meta td {
            padding: 3pt 6pt; border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .rec-pdf-meta td.label {
            background: #f8fafc; color: #64748b;
            font-weight: 600; width: 35%;
            text-transform: uppercase; font-size: 8pt; letter-spacing: 0.3pt;
        }

        .rec-pdf-table {
            width: 100%; border-collapse: collapse;
            font-size: 9pt; margin-bottom: 6pt;
        }
        .rec-pdf-table th {
            background: #0453cb; color: #fff;
            padding: 5pt 6pt; text-align: left;
            font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3pt;
        }
        .rec-pdf-table td {
            padding: 5pt 6pt; border-bottom: 1px solid #f1f5f9;
        }
        .rec-pdf-table .num { text-align: right; font-variant-numeric: tabular-nums; }
        .rec-pdf-table .pos { color: #047857; font-weight: 700; }
        .rec-pdf-table .neg { color: #b91c1c; font-weight: 700; }
        .rec-pdf-table .zero { color: #64748b; }

        .rec-pdf-summary {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 4pt; padding: 8pt;
            margin-top: 4pt;
        }
        .rec-pdf-summary strong { font-size: 11pt; }
    </style>

    {{-- Métadonnées session --}}
    <div class="rec-pdf-section">
        <div class="rec-pdf-h2">Identification de la session</div>
        <table class="rec-pdf-meta">
            <tr>
                <td class="label">Code session</td>
                <td><strong>{{ $session->code }}</strong></td>
            </tr>
            <tr>
                <td class="label">Période</td>
                <td>{{ optional($session->period_start)->format('d/m/Y') }}@if($session->period_start != $session->period_end) → {{ optional($session->period_end)->format('d/m/Y') }}@endif</td>
            </tr>
            <tr>
                <td class="label">Fréquence</td>
                <td>{{ ucfirst($session->frequency) }}</td>
            </tr>
            <tr>
                <td class="label">Ouverte par</td>
                <td>{{ optional($session->opener)->name ?? '—' }} — le {{ optional($session->opened_at)->format('d/m/Y à H:i') }}</td>
            </tr>
            @if($session->approved_by)
            <tr>
                <td class="label">Approuvée par</td>
                <td>{{ optional($session->approver)->name ?? '—' }} — le {{ optional($session->approved_at)->format('d/m/Y à H:i') }}</td>
            </tr>
            @endif
            @if($session->closed_by)
            <tr>
                <td class="label">Clôturée par</td>
                <td>{{ optional($session->closer)->name ?? '—' }} — le {{ optional($session->closed_at)->format('d/m/Y à H:i') }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Comptages caisse --}}
    <div class="rec-pdf-section">
        <div class="rec-pdf-h2">Comptages par mode de paiement</div>
        @if($cashCounts->isEmpty())
            <p style="font-size:9pt;color:#64748b;font-style:italic;">Aucun comptage saisi.</p>
        @else
            <table class="rec-pdf-table">
                <thead>
                    <tr>
                        <th>Mode</th>
                        <th class="num">Compté (physique)</th>
                        <th class="num">Système (validés)</th>
                        <th class="num">Écart</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashCounts as $cc)
                        @php
                            $ecart = $cc->ecart;
                            $cls = $ecart > 0 ? 'pos' : ($ecart < 0 ? 'neg' : 'zero');
                        @endphp
                        <tr>
                            <td><strong>{{ $cc->modeLabel() }}</strong></td>
                            <td class="num">{{ number_format((float) $cc->montant_compte, 0, ',', ' ') }} FCFA</td>
                            <td class="num">{{ number_format((float) $cc->montant_systeme, 0, ',', ' ') }} FCFA</td>
                            <td class="num {{ $cls }}">{{ number_format($ecart, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="rec-pdf-summary">
                Total écart constaté :
                @php $cls = $totalEcart > 0 ? 'pos' : ($totalEcart < 0 ? 'neg' : 'zero'); @endphp
                <strong class="{{ $cls }}">{{ number_format($totalEcart, 0, ',', ' ') }} FCFA</strong>
            </div>
        @endif
    </div>

    {{-- Écarts résolus --}}
    @if($discrepancies->isNotEmpty())
    <div class="rec-pdf-section">
        <div class="rec-pdf-h2">Écarts traités</div>
        <table class="rec-pdf-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th class="num">Montant</th>
                    <th>Résolution</th>
                    <th>Motif</th>
                </tr>
            </thead>
            <tbody>
                @foreach($discrepancies as $d)
                    <tr>
                        <td>{{ str_replace('_', ' ', $d->type) }}</td>
                        <td class="num">{{ number_format((float) $d->montant_ecart, 0, ',', ' ') }} FCFA</td>
                        <td>{{ str_replace('_', ' ', $d->resolution_type ?? '—') }}</td>
                        <td>{{ $d->motif }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($session->notes)
    <div class="rec-pdf-section">
        <div class="rec-pdf-h2">Notes</div>
        <p style="font-size:9pt;color:#1e293b;">{{ $session->notes }}</p>
    </div>
    @endif

</x-pdf-document>
