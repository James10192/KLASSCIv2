@php
    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $primary = $pdfCfg['primary_color'] ?? '#0453cb';
    $headerText = $pdfCfg['header_text_color'] ?? '#ffffff';
    $secondary = $pdfCfg['secondary_color'] ?? '#64748b';
    $textColor = $pdfCfg['text_color'] ?? '#1f2937';
    $signatureHeight = max(80, (int) ($pdfCfg['signature_height'] ?? 80));

    $filtersForDocument = collect($filtersSummary ?? [])
        ->mapWithKeys(fn ($item) => [$item['label'] ?? 'Filtre' => $item['value'] ?? null])
        ->all();

    $formatMontant = fn ($m) => number_format((float) $m, 0, ',', ' ');

    $statusBadge = function ($status) {
        $normalized = mb_strtolower(trim($status ?? ''), 'UTF-8');

        return match ($normalized) {
            'validé', 'valide' => ['Validé', 'pay-status pay-status--valid'],
            'en_attente', 'en attente' => ['En attente', 'pay-status pay-status--pending'],
            'rejeté', 'rejete' => ['Rejeté', 'pay-status pay-status--rejected'],
            'annulé', 'annule' => ['Annulé', 'pay-status pay-status--default'],
            '' => ['-', 'pay-status pay-status--default'],
            default => [ucfirst(str_replace('_', ' ', (string) $status)), 'pay-status pay-status--default'],
        };
    };
@endphp

<x-pdf-document
    :title="$context['title'] ?? 'Tableau détaillé des paiements'"
    :subtitle="$context['subtitle_creator'] ?? null"
    :filters="$filtersForDocument"
    :orientation="$showCreator ? 'landscape' : 'portrait'">

    <style>
        .pay-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: {{ $showCreator ? '8.2pt' : '8.8pt' }};
        }
        .pay-table th {
            background-color: {{ $primary }} !important;
            color: {{ $headerText }} !important;
            padding: 6px 5px;
            text-align: left;
            font-weight: 700;
            border-right: 1px solid rgba(255,255,255,.22);
        }
        .pay-table th:last-child {
            border-right: 0;
        }
        .pay-table td {
            padding: 5px;
            border-bottom: 1px solid #e5e7eb;
            color: {{ $textColor }};
            vertical-align: top;
        }
        .pay-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .pay-num {
            font-family: "Courier New", monospace;
            font-weight: 700;
            color: {{ $primary }} !important;
            white-space: nowrap;
        }
        .pay-student-name {
            display: block;
            font-weight: 700;
        }
        .pay-student-meta {
            display: block;
            font-size: 7.4pt;
            color: {{ $secondary }};
            margin-top: 1px;
        }
        .pay-amount {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
        }
        .pay-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 7pt;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }
        .pay-status--valid { background: #16a34a; }
        .pay-status--pending { background: #f59e0b; }
        .pay-status--rejected { background: #dc2626; }
        .pay-status--default { background: #64748b; }
        .pay-signature-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            page-break-inside: avoid;
        }
        .pay-signature-cell {
            width: 50%;
            padding: 0 7px;
            vertical-align: top;
        }
        .pay-signature-box {
            border: 1px dashed #94a3b8;
            min-height: {{ $signatureHeight }}px;
            padding: 8px 10px;
            background: #ffffff;
        }
        .pay-signature-label {
            font-size: 8pt;
            font-weight: 700;
            color: {{ $secondary }};
            margin-bottom: 4px;
        }
    </style>

    <table class="pdf-kpi-table">
        <tr>
            <td class="pdf-kpi-cell" style="width:33.33%;">
                <div class="pdf-kpi-label">Nombre de paiements</div>
                <div class="pdf-kpi-value">{{ number_format($count, 0, ',', ' ') }}</div>
                <div class="pdf-kpi-sub">Lignes exportées</div>
            </td>
            <td class="pdf-kpi-cell" style="width:33.33%;">
                <div class="pdf-kpi-label">Total encaissé</div>
                <div class="pdf-kpi-value">{{ $formatMontant($totalMontant) }}</div>
                <div class="pdf-kpi-sub">FCFA</div>
            </td>
            <td class="pdf-kpi-cell" style="width:33.33%;">
                <div class="pdf-kpi-label">Généré le</div>
                <div class="pdf-kpi-value" style="font-size:10pt;">{{ $dateGeneration->format('d/m/Y') }}</div>
                <div class="pdf-kpi-sub">{{ $dateGeneration->format('H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="pay-table">
        <thead>
            <tr>
                <th style="width:8%;">Date</th>
                <th style="width:10%;">N° reçu</th>
                <th style="width:{{ $showCreator ? '25' : '31' }}%;">Étudiant</th>
                <th style="width:{{ $showCreator ? '13' : '16' }}%;">Classe</th>
                <th style="width:10%;">Mode</th>
                <th style="width:12%; text-align:right;">Montant</th>
                <th style="width:10%; text-align:center;">Statut</th>
                @if($showCreator)
                    <th style="width:12%;">Encaissé par</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($paiements as $p)
                @php
                    [$statusLabel, $statusClass] = $statusBadge($p->status ?? $p->statut ?? '');
                    $etu = $p->etudiant;
                    $classe = optional($p->inscription)->classe;
                @endphp
                <tr>
                    <td>{{ $p->date_paiement ? $p->date_paiement->format('d/m/Y') : '-' }}</td>
                    <td><span class="pay-num">{{ $p->numero_recu ?? '-' }}</span></td>
                    <td>
                        @if($etu)
                            <span class="pay-student-name">{{ trim(($etu->nom ?? '') . ' ' . ($etu->prenoms ?? '')) }}</span>
                            <span class="pay-student-meta">{{ $etu->matricule ?? '-' }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $classe?->name ?? '-' }}</td>
                    <td>{{ $p->mode_paiement ?? '-' }}</td>
                    <td class="pay-amount">{{ $formatMontant($p->montant) }} FCFA</td>
                    <td style="text-align:center;"><span class="{{ $statusClass }}">{{ $statusLabel }}</span></td>
                    @if($showCreator)
                        <td>{{ $p->createdBy?->name ?? '-' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showCreator ? 8 : 7 }}" style="text-align:center; padding:20px; color:#94a3b8;">
                        Aucun paiement à afficher.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="pdf-detail-table">
        <thead>
            <tr>
                <th>Récapitulatif</th>
                <th style="width:30%; text-align:right;">Valeur</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Nombre de paiements</td>
                <td class="right">{{ number_format($count, 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Total encaissé</td>
                <td class="right">{{ $formatMontant($totalMontant) }} FCFA</td>
            </tr>
        </tbody>
    </table>

    <table class="pay-signature-grid">
        <tr>
            <td class="pay-signature-cell">
                <div class="pay-signature-box">
                    <div class="pay-signature-label">Signature & cachet</div>
                </div>
            </td>
            <td class="pay-signature-cell">
                <div class="pay-signature-box">
                    <div class="pay-signature-label">Visa comptabilité</div>
                </div>
            </td>
        </tr>
    </table>
</x-pdf-document>
