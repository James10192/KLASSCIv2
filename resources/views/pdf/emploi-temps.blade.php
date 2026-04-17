<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du temps - {{ $emploiTemps->classe->name ?? 'Classe' }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 6px 8px;
            font-size: 9px;
            color: #1f2937;
            background: #ffffff;
            line-height: 1.3;
        }

        @page {
            size: A4 landscape;
            margin: 4mm;
        }

        .container {
            max-width: 100%;
            background: white;
        }

        /* ═══════════════════════════════════════════════
           Header unifie — pattern liste-complete-pdf
           ═══════════════════════════════════════════════ */
        .header-section {
            border-radius: 6px;
            margin-bottom: 10px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            overflow: hidden;
        }

        /* ═══════════════════════════════════════════════
           KPIs — 4 cellules fond bleu plein
           ═══════════════════════════════════════════════ */
        .kpi-section {
            margin-bottom: 10px;
        }

        /* ═══════════════════════════════════════════════
           Grille horaire wrapper
           ═══════════════════════════════════════════════ */
        .timetable-wrapper {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            overflow: hidden;
            margin-bottom: 10px;
        }

        /* ═══════════════════════════════════════════════
           Footer — legende + signature + metadata
           ═══════════════════════════════════════════════ */
        .footer-section {
            margin-top: 8px;
            display: table;
            width: 100%;
            page-break-inside: avoid;
        }
        .footer-left,
        .footer-right {
            display: table-cell;
            vertical-align: top;
            padding: 6px 8px;
        }
        .footer-left {
            width: 60%;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .footer-right {
            width: 40%;
            padding-left: 12px;
            text-align: right;
        }

        .legend-title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .legend-items {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .legend-item {
            display: inline-block;
            margin-right: 8px;
            margin-bottom: 2px;
            font-size: 8px;
            color: #374151;
        }
        .legend-color {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            margin-right: 3px;
            vertical-align: middle;
            border: 1px solid rgba(0,0,0,0.1);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .signature-block {
            font-size: 8.5px;
            color: #1f2937;
        }
        .signature-title {
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #6b7280;
            margin-bottom: 3px;
        }
        .signature-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1px;
        }
        .signature-role {
            font-size: 7.5px;
            color: #6b7280;
            font-style: italic;
            margin-bottom: 8px;
        }
        .signature-line {
            display: inline-block;
            border-bottom: 1px solid #9ca3af;
            width: 120px;
            margin-top: 18px;
            font-size: 7px;
            color: #9ca3af;
        }

        .generation-info {
            text-align: center;
            font-size: 7.5px;
            color: #9ca3af;
            margin-top: 6px;
            padding-top: 5px;
            border-top: 1px solid #e5e7eb;
        }

        /* Forcer l'impression couleur */
        * {
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            print-color-adjust: exact;
        }
    </style>
</head>
<body>
    @php
        // Settings PDF dynamiques (respect tenant palette)
        $pdfCfg    = \App\Helpers\SettingsHelper::getPdfSettings();
        $hdrBg     = $pdfCfg['header_bg_color']    ?? $pdfCfg['primary_color'] ?? '#0453cb';
        $hdrText   = $pdfCfg['header_text_color']  ?? '#ffffff';
        $primary   = $pdfCfg['primary_color']      ?? '#0453cb';

        // Signature directeur (optional)
        $directorName  = $settings['director_name']  ?? '';
        $directorTitle = $settings['director_title'] ?? 'Directeur';

        // Periode / Semaine
        $periode = $emploiTemps->semestre ?? 'Annee complete';

        // Extraction semaine depuis titre (pattern "(Semaine DD/MM-DD/MM)") ou fallback dates
        $semaineLabel = null;
        if ($emploiTemps->titre && preg_match('/Semaine\s+([\d\/\-\s]+)/i', $emploiTemps->titre, $matches)) {
            $semaineLabel = 'Semaine ' . trim($matches[1]);
        } elseif ($emploiTemps->date_debut && $emploiTemps->date_fin) {
            $semaineLabel = 'Semaine du ' . \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m')
                . ' au ' . \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y');
        }
    @endphp

    <div class="container">
        {{-- ═══════════════════════════════════════════════
             HEADER — 2 colonnes Logo / Infos
             ═══════════════════════════════════════════════ --}}
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    {{-- Colonne logo 18% --}}
                    <td width="18%" style="background-color: {{ $hdrBg }}; padding: 12px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25);">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}"
                                 style="max-height: 55px; max-width: 95px; object-fit: contain; filter: brightness(0) invert(1);"
                                 alt="Logo">
                        @else
                            <div style="font-size: 28px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4; letter-spacing: -2px;">K</div>
                        @endif
                    </td>

                    {{-- Colonne infos etablissement + titre document 82% --}}
                    <td width="82%" style="background-color: {{ $hdrBg }}; padding: 10px 14px; vertical-align: middle; color: {{ $hdrText }};">
                        {{-- Nom etablissement --}}
                        <div style="font-size: 14px; font-weight: 700; letter-spacing: 0.2px; margin-bottom: 2px;">
                            {{ $etablissement['nom'] ?? 'KLASSCI' }}
                        </div>

                        {{-- Ligne contact --}}
                        @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
                            <div style="font-size: 8px; opacity: 0.85; margin-bottom: 7px;">
                                @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                                @if($etablissement['telephone'])
                                    @if($etablissement['adresse']) &nbsp;|&nbsp; @endif
                                    Tél: {{ $etablissement['telephone'] }}
                                @endif
                                @if($etablissement['email'])
                                    @if($etablissement['adresse'] || $etablissement['telephone']) &nbsp;|&nbsp; @endif
                                    {{ $etablissement['email'] }}
                                @endif
                            </div>
                        @endif

                        {{-- Separateur + titre document + infos classe --}}
                        <div style="border-top: 1px solid rgba(255,255,255,0.35); padding-top: 6px;">
                            <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                                <span>EMPLOI DU TEMPS HEBDOMADAIRE</span>
                                @if($semaineLabel)
                                    <span style="font-size: 9.5px; font-weight: 600; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); padding: 2px 9px; border-radius: 99px; letter-spacing: 0.2px;">
                                        <i>&#128197;</i> {{ $semaineLabel }}
                                    </span>
                                @endif
                            </div>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="33%" style="font-size: 8.5px;">
                                        <span style="opacity: 0.75;">Classe :</span>
                                        <strong>{{ $emploiTemps->classe->name ?? 'N/A' }}</strong>
                                    </td>
                                    <td width="34%" style="font-size: 8.5px; text-align: center;">
                                        <span style="opacity: 0.75;">Filière :</span>
                                        <strong>{{ $emploiTemps->classe->filiere->name ?? 'N/A' }}</strong>
                                    </td>
                                    <td width="33%" style="font-size: 8.5px; text-align: right;">
                                        <span style="opacity: 0.75;">Période :</span>
                                        <strong>{{ $periode }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ═══════════════════════════════════════════════
             GRILLE HORAIRE (engine preserve via partial)
             ═══════════════════════════════════════════════ --}}
        <div class="timetable-wrapper">
            @include('esbtp.emploi-temps.partials.timetable-grid', [
                'seances' => $seances,
                'timeSlots' => $timeSlots,
                'days' => $days,
                'dayLabels' => $joursNoms,
                'sessionStyles' => $sessionTypeColors,
                'sessionLabels' => $sessionTypeLabels,
                'variant' => 'pdf',
            ])
        </div>

        {{-- ═══════════════════════════════════════════════
             FOOTER — legende (60%) + signature (40%)
             ═══════════════════════════════════════════════ --}}
        <div class="footer-section">
            <div class="footer-left">
                <div class="legend-title">Légende des types de séance</div>
                <ul class="legend-items">
                    @foreach($sessionTypeLabels as $type => $label)
                        @php
                            $swatch = $sessionTypeSwatches[$type] ?? ($sessionTypeColors[$type] ?? $sessionTypeColors['default']);
                            $count  = $sessionTypeStats[$type] ?? 0;
                        @endphp
                        <li class="legend-item">
                            <span class="legend-color" style="background: {{ $swatch['bg'] ?? '#0453cb' }};"></span>
                            <strong>{{ $label }}</strong>
                            @if($count > 0)
                                <span style="color: #6b7280;">({{ $count }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="footer-right">
                @if($directorName)
                    <div class="signature-block">
                        <div class="signature-title">{{ $etablissement['ville'] ?? '' }}, le {{ now()->format('d/m/Y') }}</div>
                        <div class="signature-name">{{ $directorName }}</div>
                        <div class="signature-role">{{ $directorTitle }}</div>
                        <div class="signature-line">Signature</div>
                    </div>
                @else
                    <div class="signature-block">
                        <div class="signature-title">Fait à {{ $etablissement['ville'] ?? '—' }}</div>
                        <div class="signature-name">le {{ now()->format('d/m/Y') }}</div>
                        <div class="signature-line">Signature et cachet</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Ligne meta generation --}}
        <div class="generation-info">
            Document généré le {{ now()->format('d/m/Y à H:i') }}
            @if(auth()->check()) · par {{ auth()->user()->name }} @endif
            · {{ $etablissement['nom'] ?? 'KLASSCI' }}
        </div>
    </div>
</body>
</html>
