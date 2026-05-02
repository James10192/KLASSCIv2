<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <title>Rapport des Relances</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 8px;
            color: #333;
            line-height: 1.3;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 10px;
        }

        .header-section {
            border-radius: 6px;
            margin-bottom: 12px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            overflow: hidden;
        }

        /* Table relances */
        .relances-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            background: white;
            font-size: 9px;
        }

        .relances-table th {
            background: #007bff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            font-size: 8.5px;
            padding: 6px 4px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .relances-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            font-size: 8.5px;
        }

        .relances-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .row-number {
            background: #007bff;
            color: white;
            padding: 2px 4px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 8px;
            min-width: 16px;
            display: inline-block;
        }

        .student-matricule {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 2px 3px;
            border-radius: 2px;
            font-size: 8px;
            color: #374151;
        }

        .name-cell {
            text-align: left !important;
            padding-left: 4px !important;
        }

        .student-name {
            font-weight: 600;
            font-size: 8.5px;
            color: #1f2937;
        }

        .montant-value {
            font-weight: 600;
            font-size: 8.5px;
        }

        .montant-du    { color: #1e293b; }
        .montant-paye  { color: #10b981; }
        .montant-reste { color: #0453cb; font-weight: 700; }

        /* Badges risque — KLASSCI colors only */
        .risk-badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 600;
            color: white;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .risk-critical { background-color: #1e293b; }
        .risk-high     { background-color: #0453cb; }
        .risk-medium   { background-color: #5e91de; }
        .risk-low      { background-color: #10b981; }

        /* Footer */
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
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px;
        }

        .summary-title {
            font-size: 10px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-row {
            display: table-row;
        }

        .summary-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 2px;
        }

        .summary-value {
            font-size: 11px;
            font-weight: bold;
            color: #0453cb;
        }

        .summary-label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 1px;
        }

        .info-card {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px;
            margin-left: 3px;
        }

        .info-field {
            margin-bottom: 5px;
        }

        .info-label {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 1px;
        }

        .info-value {
            font-size: 9px;
            font-weight: 600;
            color: #374151;
        }

        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        .empty-state {
            text-align: center;
            padding: 20px 10px;
            color: #6b7280;
        }

        @media print {
            body { background: white; padding: 4px; }
            .container { padding: 8px; }
        }

        @page {
            margin: 0.5cm;
            size: A4 landscape;
        }
    </style>
</head>
<body>
    @php
        // Support chunk mode : variables injectées par le controller en mode chunk,
        // ou valeurs par défaut pour un rendu direct (non-chunked)
        $isFirstChunk = $isFirstChunk ?? true;
        $isLastChunk  = $isLastChunk  ?? true;
        $rowOffset    = $rowOffset    ?? 0;

        $pdfCfg  = \App\Helpers\SettingsHelper::getPdfSettings();
        $hdrBg   = $pdfCfg['header_bg_color']  ?? $pdfCfg['primary_color'] ?? '#0453cb';
        $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
        $primary = $pdfCfg['primary_color']     ?? '#0453cb';

        $riskLabels = [
            'critical' => ['label' => 'Critique', 'class' => 'risk-critical'],
            'high'     => ['label' => 'Élevé',    'class' => 'risk-high'],
            'medium'   => ['label' => 'Moyen',    'class' => 'risk-medium'],
            'low'      => ['label' => 'Faible',   'class' => 'risk-low'],
        ];

        // Utiliser les stats globales (toute la collection) si disponibles,
        // sinon calculer depuis le chunk courant (rendu direct)
        $totalImpaye = $globalStats['total_impaye'] ?? $relances->sum('solde_restant');
        $nbCritical  = $globalStats['nb_critical']  ?? $relances->where('risk_level', 'critical')->count();
        $nbHigh      = $globalStats['nb_high']      ?? $relances->where('risk_level', 'high')->count();
        $nbMedium    = $globalStats['nb_medium']    ?? $relances->where('risk_level', 'medium')->count();
        $nbLow       = $globalStats['nb_low']       ?? $relances->where('risk_level', 'low')->count();
        $nbTotal     = $globalStats['nb_total']     ?? $relances->count();
    @endphp

    <div class="container">

        {{-- ===== HEADER (premier chunk uniquement) ===== --}}
        @if($isFirstChunk)
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    {{-- Logo --}}
                    <td width="18%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                        @if(!empty($etablissement['logo']) && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                            <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}"
                                 style="max-height: 55px; max-width: 100px; filter: brightness(0) invert(1);" alt="Logo">
                        @else
                            <div style="font-size: 30px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                        @endif
                    </td>
                    {{-- Infos école + titre --}}
                    <td width="82%" style="background-color: {{ $hdrBg }}; padding: 12px 16px; vertical-align: middle;">
                        <div style="font-size: 15px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                        @if(!empty($etablissement['adresse']) || !empty($etablissement['telephone']) || !empty($etablissement['email']))
                        <div style="font-size: 8.5px; color: {{ $hdrText }}; opacity: 0.85; margin-bottom: 8px;">
                            @if(!empty($etablissement['adresse'])){{ $etablissement['adresse'] }}@endif
                            @if(!empty($etablissement['telephone']))
                                @if(!empty($etablissement['adresse'])) &nbsp;|&nbsp; @endif
                                Tél: {{ $etablissement['telephone'] }}
                            @endif
                            @if(!empty($etablissement['email']))
                                @if(!empty($etablissement['adresse']) || !empty($etablissement['telephone'])) &nbsp;|&nbsp; @endif
                                Email: {{ $etablissement['email'] }}
                            @endif
                        </div>
                        @endif
                        <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 7px;">
                            <div style="font-size: 12px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px; margin-bottom: 5px;">RAPPORT DES RELANCES — ÉTUDIANTS AVEC SOLDES IMPAYÉS</div>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="33%" style="font-size: 9px; color: {{ $hdrText }};">
                                        <span style="opacity: 0.75;">Année en cours :</span>
                                        <strong>{{ $anneeActive->name ?? 'Toutes' }}</strong>
                                    </td>
                                    <td width="33%" style="font-size: 9px; color: {{ $hdrText }}; text-align: center;">
                                        <span style="opacity: 0.75;">Date :</span>
                                        <strong>{{ now()->format('d/m/Y') }}</strong>
                                    </td>
                                    <td width="34%" style="font-size: 9px; color: {{ $hdrText }}; text-align: right;">
                                        @if(!empty($activeFilters))
                                            <span style="opacity: 0.75;">Filtres :</span>
                                            <strong>{{ implode(', ', $activeFilters) }}</strong>
                                        @else
                                            <span style="opacity: 0.75;">Filtres :</span>
                                            <strong>Aucun</strong>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ===== KPI BAR — 4 cellules ===== --}}
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
            <tr>
                <td width="25%" style="background-color: {{ $primary }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">TOTAL IMPAYÉ</div>
                    <div style="font-size: 12px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ number_format($totalImpaye, 0, ',', ' ') }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">FCFA</div>
                </td>
                <td width="25%" style="background-color: #1e293b; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">CRITIQUE</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $nbCritical }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Aucun paiement</div>
                </td>
                <td width="25%" style="background-color: {{ $primary }}; padding: 9px 8px; text-align: center; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.25);">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">ÉLEVÉ</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $nbHigh }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Partiel &lt; 25%</div>
                </td>
                <td width="25%" style="background-color: #5e91de; padding: 9px 8px; text-align: center; vertical-align: middle;">
                    <div style="font-size: 7.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: white; opacity: 0.8; margin-bottom: 4px;">MOYEN</div>
                    <div style="font-size: 18px; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 4px;">{{ $nbMedium }}</div>
                    <div style="font-size: 7px; color: white; opacity: 0.65;">Partiel &gt; 25%</div>
                </td>
            </tr>
        </table>
        @endif {{-- /isFirstChunk --}}

        {{-- ===== TABLE RELANCES ===== --}}
        @if($relances->count() > 0)
            <table class="relances-table">
                <thead>
                    <tr>
                        <th width="20">N°</th>
                        <th width="65">Matricule</th>
                        <th>Nom &amp; Prénoms</th>
                        <th width="80">Classe</th>
                        <th width="80">Filière</th>
                        <th width="70">Total dû</th>
                        <th width="70">Payé</th>
                        <th width="70">Solde restant</th>
                        <th width="35">Risque</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($relances as $index => $row)
                    @php
                        $riskInfo = $riskLabels[$row['risk_level'] ?? 'low'] ?? $riskLabels['low'];
                        $pctPaye  = ($row['total_du'] ?? 0) > 0
                            ? round(($row['total_paye'] / $row['total_du']) * 100)
                            : 0;
                        $rowNum = $rowOffset + $index + 1;
                    @endphp
                    <tr>
                        <td><span class="row-number">{{ $rowNum }}</span></td>
                        <td><span class="student-matricule">{{ $row['matricule'] ?? 'N/A' }}</span></td>
                        <td class="name-cell">
                            <div class="student-name">{{ $row['nom'] ?? '' }} {{ $row['prenoms'] ?? '' }}</div>
                        </td>
                        <td>{{ $row['classe'] ?? 'N/A' }}</td>
                        <td>{{ $row['filiere'] ?? 'N/A' }}</td>
                        <td><span class="montant-value montant-du">{{ number_format($row['total_du'] ?? 0, 0, ',', ' ') }}</span></td>
                        <td><span class="montant-value montant-paye">{{ number_format($row['total_paye'] ?? 0, 0, ',', ' ') }}</span></td>
                        <td><span class="montant-value montant-reste">{{ number_format($row['solde_restant'] ?? 0, 0, ',', ' ') }}</span></td>
                        <td><span class="risk-badge {{ $riskInfo['class'] }}">{{ $riskInfo['label'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ===== FOOTER (dernier chunk uniquement) ===== --}}
            @if($isLastChunk)
            <div class="footer-section">
                <div class="footer-left">
                    <div class="summary-card">
                        <div class="summary-title">Récapitulatif risques</div>
                        <div class="summary-grid">
                            <div class="summary-row">
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: #1e293b;">{{ $nbCritical }}</div>
                                    <div class="summary-label">Critique</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: #0453cb;">{{ $nbHigh }}</div>
                                    <div class="summary-label">Élevé</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: #5e91de;">{{ $nbMedium }}</div>
                                    <div class="summary-label">Moyen</div>
                                </div>
                                <div class="summary-cell">
                                    <div class="summary-value" style="color: #10b981;">{{ $nbLow }}</div>
                                    <div class="summary-label">Faible</div>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 6px; padding-top: 5px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #374151;">
                            <strong>Total étudiants :</strong> {{ $nbTotal }} &nbsp;|&nbsp;
                            <strong>Total impayé :</strong> {{ number_format($totalImpaye, 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                </div>
                <div class="footer-right">
                    @php $pdfCfg = $pdfCfg ?? \App\Helpers\SettingsHelper::getPdfSettings(); @endphp
                    <div class="info-card">
                        <div class="summary-title">Informations document</div>
                        <div class="info-field">
                            <div class="info-label">Généré le :</div>
                            <div class="info-value">{{ now()->format('d/m/Y à H:i') }}</div>
                        </div>
                        @if(($pdfCfg['show_generator_name'] ?? true) && auth()->check())
                            <div class="info-field">
                                <div class="info-label">Par :</div>
                                <div class="info-value">{{ auth()->user()->name }}</div>
                            </div>
                        @endif
                        <div class="info-field">
                            <div class="info-label">Établissement :</div>
                            <div class="info-value">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                        </div>
                        <div class="info-field">
                            <div class="info-label">Année en cours :</div>
                            <div class="info-value">{{ $anneeActive->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif {{-- /isLastChunk --}}

        @else
            <div class="empty-state">
                <p>Aucun étudiant avec solde impayé ne correspond aux filtres sélectionnés.</p>
            </div>
        @endif

        @if($isLastChunk)
        <div class="generation-info">
            <strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong><br>
            {{ $etablissement['nom'] ?? 'KLASSCI' }} — Gestion des Relances
        </div>
        @endif
    </div>
</body>
</html>
