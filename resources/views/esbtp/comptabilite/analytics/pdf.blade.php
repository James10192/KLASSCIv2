<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle ?? 'Analytics financiers' }}</title>
    @php
        $etablissement = [
            'nom' => \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI'),
            'adresse' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'telephone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'email' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'logo' => \App\Helpers\SettingsHelper::get('school_logo', ''),
        ];
        $pdfCfg          = \App\Helpers\SettingsHelper::getPdfSettings();
        $hdrBg           = $pdfCfg['header_bg_color']  ?? $pdfCfg['primary_color'] ?? '#0453cb';
        $hdrText         = $pdfCfg['header_text_color'] ?? '#ffffff';
        $primary         = $pdfCfg['primary_color']     ?? '#0453cb';
        $secondary       = $pdfCfg['secondary_color']   ?? '#64748b';
        $textColor       = $pdfCfg['text_color']        ?? '#1f2937';
        $showGenerator   = $pdfCfg['show_generator_name'] ?? true;
        $signatureHeight = $pdfCfg['signature_height']   ?? 80;
        $directorName    = \App\Helpers\SettingsHelper::get('director_name', '');
        $directorTitle   = \App\Helpers\SettingsHelper::get('director_title', 'Directeur Général');

        $title           = $reportTitle ?? 'Analytics financiers';
        $subtitle        = $reportSubtitle ?? '';
        $appliedFilters  = $reportFilters ?? [];

        $confidenceLabels = [
            'tres_fiable' => 'Très fiable',
            'fiable'      => 'Fiable',
            'indicatif'   => 'Indicatif',
        ];
    @endphp
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 8px;
            color: {{ $textColor }};
            line-height: 1.3;
            background: white;
        }
        .container { max-width: 100%; background: white; padding: 10px; }

        .header-section {
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .filters-bar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 8.5px;
        }
        .filters-bar .filter-item { display: inline-block; margin-right: 12px; }
        .filters-bar .filter-label {
            font-weight: 600; color: #475569;
            text-transform: uppercase; font-size: 7.5px;
            letter-spacing: 0.3px;
        }
        .filters-bar .filter-value { color: #0f172a; font-weight: 600; }

        /* ─── Sections ─── */
        .section { margin-bottom: 16px; }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            background: {{ $primary }};
            padding: 7px 12px;
            margin: 0 0 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 3px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        /* ─── Cash Flow block ─── */
        .cf-block {
            background: #f8fafc;
            padding: 12px 14px;
            border-radius: 4px;
            border-left: 3px solid {{ $primary }};
            margin-bottom: 8px;
        }
        .cf-value {
            font-size: 20px;
            font-weight: 700;
            color: {{ $primary }};
            margin: 0;
        }
        .cf-meta { font-size: 9px; color: {{ $secondary }}; margin-top: 4px; }
        .cf-confidence {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 700;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .cf-confidence-tres_fiable { background: rgba(16,185,129,.15); color: #047857; }
        .cf-confidence-fiable      { background: rgba(4,83,203,.15);   color: {{ $primary }}; }
        .cf-confidence-indicatif   { background: rgba(245,158,11,.15); color: #b45309; }

        .reasons { margin-top: 8px; padding-left: 16px; }
        .reasons li { font-size: 9px; color: #1e293b; margin-bottom: 2px; }

        /* ─── KPI Bar ─── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 6px;
        }
        .data-table thead th {
            background: {{ $primary }};
            color: #fff;
            font-weight: 600;
            padding: 6px 5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 8.5px;
            text-align: left;
            border-right: 1px solid rgba(255,255,255,.15);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .data-table thead th:last-child { border-right: 0; }
        .data-table tbody td {
            padding: 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .data-table tbody tr:nth-child(even) td { background: #f8fafc; }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-haut  { background: rgba(220,38,38,.15);  color: #dc2626; }
        .badge-moyen { background: rgba(245,158,11,.15); color: #b45309; }
        .badge-bas   { background: rgba(16,185,129,.15); color: #047857; }

        /* ─── Anomalies (regroupées par type) ─── */
        .anom-group {
            margin-bottom: 10px;
        }
        .anom-group-header {
            background: {{ $primary }};
            color: #fff;
            padding: 4px 8px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 3px 3px 0 0;
        }
        .anom-group-header-meta {
            float: right;
            font-weight: 400;
            font-size: 8.5px;
            opacity: .85;
        }
        .anom-group-ok {
            padding: 8px 10px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-top: none;
            border-radius: 0 0 3px 3px;
            font-size: 8.5px;
            color: #047857;
            font-style: italic;
        }
        .anom-item {
            margin-bottom: 4px;
            padding: 5px 8px;
            border-radius: 3px;
            border-left: 3px solid #94a3b8;
            background: #f8fafc;
        }
        .anom-item-critical { border-left-color: #dc2626; background: rgba(220,38,38,.04); }
        .anom-item-warning  { border-left-color: #f59e0b; background: rgba(245,158,11,.04); }
        .anom-item-meta     {
            font-size: 7.5px;
            color: {{ $secondary }};
            margin-bottom: 2px;
            letter-spacing: 0.2px;
        }
        .anom-item-meta-type { font-weight: 700; color: #1e293b; text-transform: none; }
        .anom-item-meta-date { color: {{ $secondary }}; margin-left: 4px; }
        .anom-item-meta-score {
            display: inline-block;
            padding: 1px 5px;
            background: rgba(15,23,42,.08);
            color: #1e293b;
            border-radius: 3px;
            font-weight: 700;
            margin-left: 4px;
        }
        .anom-item-critical .anom-item-meta-score { background: rgba(220,38,38,.12); color: #dc2626; }
        .anom-item-warning  .anom-item-meta-score { background: rgba(245,158,11,.15); color: #b45309; }
        .anom-item-meta-sev {
            display: inline-block;
            padding: 1px 5px;
            background: #94a3b8;
            color: #fff;
            border-radius: 3px;
            font-weight: 700;
            font-size: 7px;
            float: right;
        }
        .anom-item-critical .anom-item-meta-sev { background: #dc2626; }
        .anom-item-warning  .anom-item-meta-sev { background: #f59e0b; }
        .anom-item-message  { font-size: 8.5px; color: #1e293b; line-height: 1.3; }
        .anom-overflow      { font-size: 7.5px; color: {{ $secondary }}; text-align: right; margin-top: 3px; font-style: italic; }

        .empty {
            text-align: center;
            padding: 14px;
            color: {{ $secondary }};
            font-size: 9px;
            font-style: italic;
        }

        /* ─── Signature & cachet ─── */
        .signature-section {
            margin-top: 16px;
            display: table;
            width: 100%;
        }
        .signature-cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 6px;
        }
        .signature-box {
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            min-height: {{ max(80, (int) $signatureHeight) }}px;
            padding: 8px 10px;
            background: #ffffff;
        }
        .signature-label {
            font-size: 8px;
            color: {{ $secondary }};
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .signature-name {
            font-size: 9px;
            font-weight: 600;
            color: #1f2937;
            margin-top: 4px;
        }
        .signature-img {
            max-height: {{ max(40, (int) $signatureHeight - 20) }}px;
            max-width: 200px;
            display: block;
            margin: 4px auto;
        }

        /* ─── Generation info ─── */
        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        @page {
            margin: 0.5cm;
            size: A4 portrait;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header Section — pattern liste-complete-pdf (2 colonnes : Logo | Infos école + titre) --}}
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                <tr>
                    <td width="18%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                        @php($_logo = \App\Helpers\SettingsHelper::resolveLogoBase64())
                        @if($_logo)
                            <span style="display:inline-block; background:#fff; padding:5px; border-radius:5px; border:1px solid rgba(255,255,255,0.35);">
                                <img src="{{ $_logo['data_uri'] }}" style="max-height: 55px; max-width: 100px; display:block;" alt="Logo">
                            </span>
                        @else
                            <div style="font-size: 30px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                        @endif
                    </td>
                    <td width="82%" style="background-color: {{ $hdrBg }}; padding: 12px 16px; vertical-align: middle;">
                        <div style="font-size: 15px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                        @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                        <div style="font-size: 8.5px; color: {{ $hdrText }}; opacity: 0.85; margin-bottom: 8px;">
                            @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                            @if($etablissement['telephone'])
                                @if($etablissement['adresse']) &nbsp;|&nbsp; @endif
                                Tél: {{ $etablissement['telephone'] }}
                            @endif
                            @if($etablissement['email'])
                                @if($etablissement['adresse'] || $etablissement['telephone']) &nbsp;|&nbsp; @endif
                                Email: {{ $etablissement['email'] }}
                            @endif
                        </div>
                        @endif
                        <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                            <div style="font-size: 12px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px; margin-bottom: 3px;">
                                {{ mb_strtoupper($title, 'UTF-8') }}
                            </div>
                            @if($subtitle)
                                <div style="font-size: 8.5px; color: {{ $hdrText }}; opacity: 0.85; font-style: italic;">
                                    {{ $subtitle }}
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Filters bar (résumé filtres appliqués) --}}
        @if(!empty($appliedFilters))
        <div class="filters-bar">
            @foreach($appliedFilters as $label => $value)
                <span class="filter-item">
                    <span class="filter-label">{{ $label }}:</span>
                    <span class="filter-value">{{ $value }}</span>
                </span>
            @endforeach
        </div>
        @endif

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
                        {{ $confidenceLabels[$cashFlow->confidenceLabel] ?? mb_strtoupper($cashFlow->confidenceLabel, 'UTF-8') }}
                    </span>
                    @if(!empty($cashFlow->explanation))
                    <ul class="reasons">
                        @foreach($cashFlow->explanation as $r)
                            <li>{{ $r }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            @else
                <div class="empty">{{ $cashFlow->explanation[0] ?? 'Indisponible.' }}</div>
            @endif
        </div>

        {{-- ===== Banner Mode dégradé ===== --}}
        @if(($echeancierMode ?? 'configured') === 'fallback' && !empty($echeancierNote))
            <div style="margin: 0 0 12px; padding: 8px 12px; border: 1px solid #f5a700; background: #fff8eb; border-radius: 4px; font-size: 9px; color: #92400e;">
                <strong>Mode dégradé :</strong> {{ $echeancierNote }}
            </div>
        @endif

        {{-- ===== Section Recouvrement mois par mois ===== --}}
        @php
            $gapBuckets = $recouvrementGaps ?? [];
        @endphp
        @if(!empty($gapBuckets))
            @php
                $totalExpected = array_sum(array_column($gapBuckets, 'expected'));
                $totalPaid = array_sum(array_column($gapBuckets, 'paid'));
                $totalGap = max(0.0, $totalExpected - $totalPaid);
                $globalRate = $totalExpected > 0 ? round($totalPaid / $totalExpected * 100, 1) : 0;
            @endphp
            <div class="section">
                <h2 class="section-title">Recouvrement mois par mois — attendu vs encaissé</h2>

                <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 8px; border-collapse: collapse;">
                    <tr>
                        <td width="25%" style="padding: 6px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; vertical-align: top;">
                            <div style="font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: .03em;">Attendu cumulé</div>
                            <div style="font-size: 11px; font-weight: 700; color: #0f172a;">{{ number_format($totalExpected, 0, ',', ' ') }} FCFA</div>
                        </td>
                        <td width="2%"></td>
                        <td width="25%" style="padding: 6px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; vertical-align: top;">
                            <div style="font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: .03em;">Encaissé cumulé</div>
                            <div style="font-size: 11px; font-weight: 700; color: #0f172a;">{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</div>
                        </td>
                        <td width="2%"></td>
                        <td width="22%" style="padding: 6px 8px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; vertical-align: top;">
                            <div style="font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: .03em;">Écart restant</div>
                            <div style="font-size: 11px; font-weight: 700; color: #b91c1c;">{{ number_format($totalGap, 0, ',', ' ') }} FCFA</div>
                        </td>
                        <td width="2%"></td>
                        <td width="22%" style="padding: 6px 8px; background: {{ $primary }}; border-radius: 4px; vertical-align: top;">
                            <div style="font-size: 7.5px; color: #ffffff; opacity: .8; text-transform: uppercase; letter-spacing: .03em;">Taux recouvrement</div>
                            <div style="font-size: 11px; font-weight: 700; color: #ffffff;">{{ number_format($globalRate, 1, ',', ' ') }} %</div>
                        </td>
                    </tr>
                </table>

                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-top: 4px;">
                    <thead>
                        <tr style="background: {{ $primary }};">
                            <th style="padding: 5px 8px; color: #fff; font-size: 8.5px; text-align: left;">Mois</th>
                            <th style="padding: 5px 8px; color: #fff; font-size: 8.5px; text-align: right;">Attendu</th>
                            <th style="padding: 5px 8px; color: #fff; font-size: 8.5px; text-align: right;">Encaissé</th>
                            <th style="padding: 5px 8px; color: #fff; font-size: 8.5px; text-align: right;">Écart</th>
                            <th style="padding: 5px 8px; color: #fff; font-size: 8.5px; text-align: center;">Taux</th>
                            <th style="padding: 5px 8px; color: #fff; font-size: 8.5px; text-align: center;">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gapBuckets as $monthKey => $bucket)
                            @php
                                [$y, $m] = array_map('intval', explode('-', $monthKey));
                                $label = ucfirst(\Carbon\Carbon::createFromDate($y, $m, 1)->locale('fr')->translatedFormat('F Y'));
                                $rate = $bucket['expected'] > 0 ? round(($bucket['paid'] / $bucket['expected']) * 100, 1) : 0;
                                $tone = match (true) {
                                    $bucket['gap_ratio'] >= 0.5 => ['bg' => '#fef2f2', 'fg' => '#b91c1c', 'label' => 'CRITIQUE'],
                                    $bucket['gap_ratio'] >= 0.3 => ['bg' => '#fffbeb', 'fg' => '#b45309', 'label' => 'SURVEILLANCE'],
                                    default => ['bg' => '#f0fdf4', 'fg' => '#047857', 'label' => 'SAIN'],
                                };
                            @endphp
                            <tr style="background: {{ $tone['bg'] }}; border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 5px 8px; font-size: 9px; color: #0f172a; font-weight: 600;">{{ $label }}</td>
                                <td style="padding: 5px 8px; font-size: 9px; color: #1e293b; text-align: right;">{{ number_format($bucket['expected'], 0, ',', ' ') }}</td>
                                <td style="padding: 5px 8px; font-size: 9px; color: #1e293b; text-align: right; font-weight: 600;">{{ number_format($bucket['paid'], 0, ',', ' ') }}</td>
                                <td style="padding: 5px 8px; font-size: 9px; color: {{ $tone['fg'] }}; text-align: right; font-weight: 600;">{{ number_format($bucket['gap'], 0, ',', ' ') }}</td>
                                <td style="padding: 5px 8px; font-size: 9px; color: {{ $tone['fg'] }}; text-align: center; font-weight: 700;">{{ $rate }}%</td>
                                <td style="padding: 5px 8px; font-size: 8px; color: {{ $tone['fg'] }}; text-align: center; font-weight: 700;">{{ $tone['label'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ===== Section Default Risk ===== --}}
        <div class="section">
            <h2 class="section-title">Risque de défaut de paiement</h2>
            @if($defaultRisk->isAvailable())
                @php
                    $buckets = $defaultRisk->metadata['buckets'] ?? [];
                    $top     = $defaultRisk->metadata['top_at_risk'] ?? [];
                @endphp
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 10px; border-collapse: collapse;">
                    <tr>
                        <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                            <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">Haut risque</div>
                            <div style="font-size: 18px; font-weight: 700; color: #fff; line-height: 1.1;">{{ $buckets['haut'] ?? 0 }}</div>
                        </td>
                        <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                            <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">Surveillance</div>
                            <div style="font-size: 18px; font-weight: 700; color: #fff; line-height: 1.1;">{{ $buckets['moyen'] ?? 0 }}</div>
                        </td>
                        <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                            <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">Étudiants actifs</div>
                            <div style="font-size: 18px; font-weight: 700; color: #fff; line-height: 1.1;">{{ $defaultRisk->metadata['total_actifs'] ?? 0 }}</div>
                        </td>
                        <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle;">
                            <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">FCFA non recouvrés</div>
                            <div style="font-size: 13px; font-weight: 700; color: #fff; line-height: 1.2;">{{ number_format($defaultRisk->metadata['total_solde_haut_risque'] ?? 0, 0, ',', ' ') }}</div>
                        </td>
                    </tr>
                </table>

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
                                    <td style="text-align:right;font-weight:600;">{{ number_format($row['solde_restant'] ?? 0, 0, ',', ' ') }}</td>
                                    <td style="text-align:center;">{{ $row['jours_retard'] ?? 0 }} j</td>
                                    <td style="text-align:center;"><span class="badge badge-{{ $row['level'] ?? 'bas' }}">{{ mb_strtoupper($row['level'] ?? '', 'UTF-8') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if(count($top) > 25)
                        <p style="font-size:8px;color:{{ $secondary }};text-align:right;margin-top:4px;font-style:italic;">… et {{ count($top) - 25 }} autres (voir Excel pour le détail complet)</p>
                    @endif
                @endif
            @else
                <div class="empty">{{ $defaultRisk->explanation[0] ?? 'Indisponible.' }}</div>
            @endif
        </div>

        {{-- ===== Section Anomalies ===== --}}
        @php
            $pdfTypeLabels = [
                'payment_outlier'  => 'Paiement aberrant',
                'recouvrement_gap' => 'Écart de recouvrement',
                'revenue_spike'    => 'Pic de recettes',
                'revenue_drop'     => 'Chute de recettes',
            ];
            $pdfGroupOf = fn ($t) => match ($t) {
                'payment_outlier'                => 'paiements',
                'recouvrement_gap'               => 'recouvrement',
                'revenue_spike', 'revenue_drop'  => 'revenus',
                default                           => 'autres',
            };
            $pdfGroupMeta = [
                'paiements'    => ['label' => 'Paiements aberrants',      'sub' => 'Outliers sur les paiements des 30 derniers jours'],
                'recouvrement' => ['label' => 'Écarts de recouvrement',   'sub' => 'Mois clos avec un manque-à-gagner significatif'],
                'revenus'      => ['label' => 'Variations mensuelles',    'sub' => 'Pics ou chutes inhabituels (Z-score)'],
            ];
            $pdfImpact = fn ($a) => match ($a->type) {
                'payment_outlier'                => (float) ($a->context['montant'] ?? 0),
                'recouvrement_gap'               => (float) ($a->context['gap'] ?? 0),
                'revenue_spike', 'revenue_drop'  => abs(((float) ($a->context['value'] ?? 0)) - ((float) ($a->context['mean'] ?? 0))),
                default                           => 0.0,
            };
            $pdfScoreLabel = fn ($a) => match ($a->type) {
                'payment_outlier'                => number_format($a->score, 1, ',', ' ') . '× moy.',
                'recouvrement_gap'               => number_format($a->score * 100, 0) . '% écart',
                'revenue_spike', 'revenue_drop'  => 'Z=' . number_format($a->score, 1, ',', ' ') . 'σ',
                default                           => number_format($a->score, 2),
            };
            $pdfDateLabel = function ($a) {
                $ctx = $a->context;
                if (!empty($ctx['date_paiement'])) {
                    return \Carbon\Carbon::parse($ctx['date_paiement'])->locale('fr')->translatedFormat('d/m/Y');
                }
                if (!empty($ctx['year']) && !empty($ctx['month'])) {
                    return ucfirst(\Carbon\Carbon::createFromDate((int) $ctx['year'], (int) $ctx['month'], 1)->locale('fr')->translatedFormat('M Y'));
                }
                return null;
            };
            $pdfAnomCollection = collect($anomalies);
            $pdfOrderedGroups = ['paiements', 'recouvrement', 'revenus'];
            $pdfGroupStats    = [];
            foreach ($pdfOrderedGroups as $g) {
                $items = $pdfAnomCollection->filter(fn ($a) => $pdfGroupOf($a->type) === $g)->values();
                $pdfGroupStats[$g] = [
                    'items'    => $items,
                    'count'    => $items->count(),
                    'critical' => $items->where('severity', 'critical')->count(),
                    'warning'  => $items->where('severity', 'warning')->count(),
                    'impact'   => (float) $items->sum(fn ($a) => $pdfImpact($a)),
                ];
            }
            $pdfMaxItemsPerGroup = 20;
        @endphp
        <div class="section">
            <h2 class="section-title">Anomalies financières détectées</h2>
            @if($pdfAnomCollection->isEmpty())
                <div class="empty">Aucune anomalie détectée. Les flux financiers sont conformes aux tendances historiques.</div>
            @else
                @foreach($pdfOrderedGroups as $g)
                    @php $s = $pdfGroupStats[$g]; @endphp
                    <div class="anom-group">
                        <div class="anom-group-header">
                            {{ $pdfGroupMeta[$g]['label'] }}
                            <span class="anom-group-header-meta">
                                @if($s['count'] > 0)
                                    {{ $s['count'] }} alerte{{ $s['count'] > 1 ? 's' : '' }} · {{ $s['critical'] }} crit. · {{ $s['warning'] }} avert. · {{ number_format($s['impact'], 0, ',', ' ') }} FCFA
                                @else
                                    Aucune alerte
                                @endif
                            </span>
                        </div>

                        @if($s['count'] === 0)
                            <div class="anom-group-ok">✓ Situation conforme — {{ $pdfGroupMeta[$g]['sub'] }}.</div>
                        @else
                            @foreach($s['items']->take($pdfMaxItemsPerGroup) as $alert)
                                @php $date = $pdfDateLabel($alert); @endphp
                                <div class="anom-item anom-item-{{ $alert->severity }}">
                                    <div class="anom-item-meta">
                                        <span class="anom-item-meta-sev">{{ mb_strtoupper($alert->severity, 'UTF-8') }}</span>
                                        <span class="anom-item-meta-type">{{ $pdfTypeLabels[$alert->type] ?? $alert->type }}</span>
                                        @if($date)
                                            <span class="anom-item-meta-date">· {{ $date }}</span>
                                        @endif
                                        <span class="anom-item-meta-score">{{ $pdfScoreLabel($alert) }}</span>
                                    </div>
                                    <div class="anom-item-message">{{ $alert->message }}</div>
                                </div>
                            @endforeach
                            @if($s['count'] > $pdfMaxItemsPerGroup)
                                <div class="anom-overflow">… et {{ $s['count'] - $pdfMaxItemsPerGroup }} autre{{ ($s['count'] - $pdfMaxItemsPerGroup) > 1 ? 's' : '' }} alerte{{ ($s['count'] - $pdfMaxItemsPerGroup) > 1 ? 's' : '' }} (voir l'export Excel pour le détail complet)</div>
                            @endif
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Signature & cachet (emplacement spacieux 80px+) --}}
        <div class="signature-section">
            <div class="signature-cell">
                <div class="signature-box">
                    <div class="signature-label">Signature & Cachet</div>
                    @if(!empty($pdfCfg['signature_director']) && file_exists(storage_path('app/public/' . $pdfCfg['signature_director'])))
                        <img class="signature-img"
                             src="data:image/{{ pathinfo($pdfCfg['signature_director'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $pdfCfg['signature_director']))) }}"
                             alt="Signature directeur">
                    @endif
                    @if($directorName)
                        <div class="signature-name">{{ $directorName }}</div>
                        <div style="font-size: 8px; color: {{ $secondary }};">{{ $directorTitle }}</div>
                    @endif
                </div>
            </div>
            <div class="signature-cell">
                <div class="signature-box">
                    <div class="signature-label">Visa Comptabilité</div>
                </div>
            </div>
        </div>

        {{-- Generation info --}}
        <div class="generation-info">
            <strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong>
            @if($showGenerator && auth()->check())
                par {{ auth()->user()->name }}
            @endif
            <br>
            {{ $etablissement['nom'] ?? 'KLASSCI' }} — Système d'Analytics Financiers
        </div>
    </div>
</body>
</html>
