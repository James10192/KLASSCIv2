<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle ?? 'Recouvrement quotidien' }}</title>
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

        $title           = $reportTitle ?? 'Recouvrement quotidien';
        $subtitle        = $reportSubtitle ?? 'Liste priorisée des étudiants à relancer';
        $appliedFilters  = $reportFilters ?? [];

        $totalSolde      = collect($rows ?? [])->sum('solde_restant');
        $countRows       = count($rows ?? []);
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
        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        /* ─── Header (pattern liste-complete-pdf : 2 colonnes logo|infos) ─── */
        .header-section {
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        /* ─── Filters bar ─── */
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

        /* ─── Table ─── */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            background: white;
            font-size: 9px;
        }
        .report-table thead th {
            background: {{ $primary }};
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 8.5px;
            padding: 6px 5px;
            text-align: left;
            border-right: 1px solid rgba(255,255,255,.15);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .report-table thead th:last-child { border-right: 0; }
        .report-table tbody td {
            padding: 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .report-table tbody tr:nth-child(even) td { background: #f8fafc; }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .amount-cell { font-weight: 600; color: #0f172a; }
        .phone-cell  {
            font-family: 'Courier New', monospace;
            font-size: 8.5px;
            color: #334155;
        }
        .student-name { font-weight: 600; color: #1f2937; }

        .chip {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 600;
        }
        .chip-retard { background: rgba(245,158,11,.15); color: #b45309; }

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

        /* ─── Footer summary (2 colonnes : résumé / infos doc) ─── */
        .footer-section {
            margin-top: 12px;
            display: table;
            width: 100%;
        }
        .footer-left, .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 3px;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 9px;
        }
        .summary-title {
            font-size: 10px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-cell {
            display: table-cell;
            width: 50%;
            padding: 2px;
            text-align: center;
        }
        .summary-value {
            font-size: 12px;
            font-weight: 700;
            color: {{ $primary }};
        }
        .summary-label {
            font-size: 8px;
            color: {{ $secondary }};
            margin-top: 1px;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 9px;
            margin-left: 3px;
        }
        .info-field { margin-bottom: 5px; }
        .info-label { font-size: 8px; color: {{ $secondary }}; margin-bottom: 1px; }
        .info-value { font-size: 9px; font-weight: 600; color: #374151; }

        /* ─── Signature & cachet (emplacement spacieux) ─── */
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
            position: relative;
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
            size: A4 landscape;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header Section — pattern liste-complete-pdf (2 colonnes : Logo | Infos école + titre) --}}
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                <tr>
                    {{-- Colonne gauche : Logo --}}
                    <td width="18%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                        @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                            <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}"
                                 style="max-height: 55px; max-width: 100px; filter: brightness(0) invert(1);" alt="Logo">
                        @else
                            <div style="font-size: 30px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                        @endif
                    </td>
                    {{-- Colonne droite : Nom école + contact + titre document --}}
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

        {{-- KPI Bar — 4 cellules pleine largeur fond primary --}}
        @if(!empty($kpis))
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px; border-collapse: collapse;">
            <tr>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">Haut risque</div>
                    <div style="font-size: 18px; font-weight: 700; color: #fff; line-height: 1.1; margin-bottom: 3px;">{{ $kpis['buckets']['haut'] ?? 0 }}</div>
                    <div style="font-size: 7px; color: #fff; opacity: 0.65;">Étudiants critiques</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">Solde haut risque</div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; line-height: 1.2; margin-bottom: 3px;">{{ number_format($kpis['total_solde_haut_risque'] ?? 0, 0, ',', ' ') }}</div>
                    <div style="font-size: 7px; color: #fff; opacity: 0.65;">FCFA non recouvrés</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">Surveillance</div>
                    <div style="font-size: 18px; font-weight: 700; color: #fff; line-height: 1.1; margin-bottom: 3px;">{{ $kpis['buckets']['moyen'] ?? 0 }}</div>
                    <div style="font-size: 7px; color: #fff; opacity: 0.65;">Sous surveillance</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 10px 8px; text-align: center; vertical-align: middle;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; opacity: 0.85; margin-bottom: 4px;">Total actifs</div>
                    <div style="font-size: 18px; font-weight: 700; color: #fff; line-height: 1.1; margin-bottom: 3px;">{{ $kpis['total_actifs'] ?? $countRows }}</div>
                    <div style="font-size: 7px; color: #fff; opacity: 0.65;">Étudiants suivis</div>
                </td>
            </tr>
        </table>
        @endif

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

        {{-- Table --}}
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:30px;" class="text-center">#</th>
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
                @forelse($rows ?? [] as $i => $row)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td><span class="student-name">{{ $row['etudiant_nom'] ?? '—' }}</span></td>
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
                            <span class="badge badge-{{ $row['level'] ?? 'bas' }}">{{ mb_strtoupper($row['level'] ?? 'bas', 'UTF-8') }}</span>
                        </td>
                        <td class="text-center">{{ number_format($row['score'] ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center" style="padding:20px;color:#94a3b8;">
                            Aucun étudiant à risque dans ce périmètre.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Footer summary 2-colonnes : résumé statistique / infos document --}}
        @if($countRows > 0)
        <div class="footer-section">
            <div class="footer-left">
                <div class="summary-card">
                    <div class="summary-title">Résumé statistique</div>
                    <div class="summary-grid">
                        <div class="summary-row">
                            <div class="summary-cell">
                                <div class="summary-value">{{ $countRows }}</div>
                                <div class="summary-label">Étudiants suivis</div>
                            </div>
                            <div class="summary-cell">
                                <div class="summary-value">{{ number_format($totalSolde, 0, ',', ' ') }}</div>
                                <div class="summary-label">FCFA cumulés</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-right">
                <div class="info-card">
                    <div class="summary-title">Informations document</div>
                    <div class="info-field">
                        <div class="info-label">Document généré le :</div>
                        <div class="info-value">{{ now()->format('d/m/Y à H:i') }}</div>
                    </div>
                    @if($showGenerator && auth()->check())
                    <div class="info-field">
                        <div class="info-label">Par :</div>
                        <div class="info-value">{{ auth()->user()->name }}</div>
                    </div>
                    @endif
                    <div class="info-field">
                        <div class="info-label">Établissement :</div>
                        <div class="info-value">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

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
            {{ $etablissement['nom'] ?? 'KLASSCI' }} — Système de Gestion du Recouvrement
        </div>
    </div>
</body>
</html>
