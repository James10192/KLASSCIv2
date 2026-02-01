<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    @php
        $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
        $pdfHeaderBg = $pdfSettings['header_bg_color'] ?? '#0453cb';
        $pdfHeaderText = $pdfSettings['header_text_color'] ?? '#ffffff';
        $pdfText = $pdfSettings['text_color'] ?? '#1f2937';
        $pdfSecondary = $pdfSettings['secondary_color'] ?? '#5e91de';
    @endphp
    <meta charset="UTF-8">
    <title>Emploi du temps - {{ $emploiTemps->classe->name ?? 'Classe' }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 4px 6px;
            font-size: 8px;
            color: #0f172a;
            background: #ffffff;
        }

        @page {
            size: A4 landscape;
            margin: 4mm;
        }

        /* Header avec gradient KLASSCI */
        .header {
            background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
            color: #ffffff;
            border-radius: 10px;
            padding: 3px 5px;
            margin-bottom: 3px;
        }
        .header-top {
            display: table;
            width: 100%;
        }
        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }
        .header-right {
            text-align: right;
        }
        .header-title h1 {
            margin: 0;
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .header-title p {
            margin: 1px 0 0 0;
            font-size: 7px;
            opacity: 0.95;
        }
        .header-logo img {
            max-height: 12px;
            max-width: 40px;
            filter: brightness(0) invert(1);
        }


        /* Carte école moderne */
        .school-card {
            background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
            color: white;
            border-radius: 12px;
            padding: 4px 6px;
            margin-bottom: 3px;
        }
        .school-card h2 {
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .school-card p {
            margin: 0;
            font-size: 6.5px;
            opacity: 0.9;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-top: 3px;
            border-spacing: 3px;
        }
        .info-badge {
            display: table-cell;
            width: 25%;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 4px 5px;
            font-size: 6.5px;
        }
        .info-badge strong {
            display: block;
            font-weight: 600;
            margin-bottom: 1px;
        }
        .info-badge small {
            display: block;
            font-size: 6px;
            opacity: 0.85;
        }

        /* Grille emploi du temps avec style moderne */
        .timetable-wrapper {
            margin-top: 3px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: white;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        }
        .timetable-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .timetable-grid th,
        .timetable-grid td {
            border: 1px solid #e5e7eb;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
            font-size: 6.8px;
        }
        .timetable-grid thead th {
            background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
            color: #ffffff;
            font-size: 6.8px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
            padding: 4px;
        }
        .timetable-time-cell {
            background: #f8fafc;
            font-weight: 600;
            font-size: 6.8px;
            color: #1f2937;
            width: 70px;
        }

        /* Sessions avec couleurs et border-radius */
        .tt-session {
            border-radius: 8px;
            padding: 5px 6px;
            font-size: 7px;
        }
        .tt-session-type {
            font-size: 6.5px;
            letter-spacing: 0.08em;
            margin-bottom: 2px;
            opacity: 0.9;
            text-transform: uppercase;
        }
        .tt-session-subject {
            font-size: 8px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .tt-session-teacher,
        .tt-session-room,
        .tt-session-time {
            font-size: 6.5px;
            margin-bottom: 1px;
            opacity: 0.95;
        }

        /* Légende modernisée */
        .legend {
            margin-top: 2px;
            padding: 0;
            list-style: none;
        }
        .legend-item {
            display: inline-block;
            margin-right: 4px;
            margin-bottom: 1px;
            font-size: 5.8px;
            color: #475569;
            background: #f3f4f6;
            padding: 1px 3px;
            border-radius: 8px;
        }
        .legend-color {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-right: 3px;
            vertical-align: middle;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="header-left">
                <div class="header-title">
                    <h1>{{ $etablissement['nom'] }}</h1>
                    <p>{{ $etablissement['type'] ?? 'Enseignement Supérieur Technique' }}</p>
                </div>
            </div>
            <div class="header-right">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo établissement">
                @endif
            </div>
        </div>
    </div>

    <div class="school-card">
        <h2>{{ $emploiTemps->classe->name ?? 'Classe non renseignée' }}</h2>
        <p>{{ $emploiTemps->classe->filiere->name ?? 'Filière non renseignée' }} - {{ $emploiTemps->classe->niveau->name ?? 'Niveau non renseigné' }}</p>

        <div class="info-grid">
            <div class="info-badge">
                <strong>Localisation</strong>
                <small>{{ trim(($etablissement['ville'] ?? '') . ' - ' . ($etablissement['pays'] ?? '')) ?: 'Yamoussoukro - Côte d\'Ivoire' }}</small>
                <small>{{ $etablissement['adresse'] ?: 'Adresse non renseignée' }}</small>
            </div>
            <div class="info-badge">
                <strong>Contact</strong>
                <small>{{ $etablissement['telephone'] ?: '---' }}</small>
                <small>Standard administratif</small>
            </div>
            <div class="info-badge">
                <strong>Email</strong>
                <small>{{ $etablissement['email'] ?: '---' }}</small>
                <small>Contact officiel</small>
            </div>
            <div class="info-badge">
                <strong>Couverture</strong>
                <small>{{ $daysCovered }} jour{{ $daysCovered > 1 ? 's' : '' }} couverts</small>
                <small>{{ $totalHoursFormatted }} cumulées</small>
            </div>
        </div>
    </div>

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

    @if(!empty($sessionTypeLabels))
        <ul class="legend">
            @foreach($sessionTypeLabels as $type => $label)
                @php
                    $swatch = $sessionTypeSwatches[$type] ?? ($sessionTypeColors[$type] ?? $sessionTypeColors['default']);
                @endphp
                <li class="legend-item">
                    <span class="legend-color" style="background: {{ $swatch['bg'] ?? '#0453cb' }};"></span>
                    {{ $label }}
                </li>
            @endforeach
        </ul>
    @endif
</body>
</html>
