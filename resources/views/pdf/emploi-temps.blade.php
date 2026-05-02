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
           Footer — legende horizontale pleine largeur
           ═══════════════════════════════════════════════ */
        .legend-bar {
            margin-top: 8px;
            padding: 6px 10px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            page-break-inside: avoid;
        }
        .legend-title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #1f2937;
            margin-right: 6px;
        }
        .legend-items {
            margin: 0;
            padding: 0;
            list-style: none;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 8.5px;
            color: #374151;
        }
        .legend-color {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            vertical-align: middle;
            border: 1px solid rgba(0,0,0,0.1);
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .generation-info {
            text-align: center;
            font-size: 7.5px;
            color: #9ca3af;
            margin-top: 4px;
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
             FOOTER — legende seule (affichage tableau)
             ═══════════════════════════════════════════════ --}}
        <div class="legend-bar">
            <div class="legend-title">Légende</div>
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

        {{-- Ligne meta generation (discret) --}}
        @php $pdfCfg = $pdfCfg ?? \App\Helpers\SettingsHelper::getPdfSettings(); @endphp
        <div class="generation-info">
            Document généré le {{ now()->format('d/m/Y à H:i') }}@if(($pdfCfg['show_generator_name'] ?? true) && auth()->check()) par {{ auth()->user()->name }}@endif · {{ $etablissement['nom'] ?? 'KLASSCI' }}
        </div>
    </div>
</body>
</html>
