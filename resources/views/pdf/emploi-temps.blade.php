<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps - {{ $emploiTemps->classe->name ?? 'Classe' }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            box-sizing: border-box;
        }
        .info-card-line {
            font-size: 10px;
            margin-bottom: 3px;
            line-height: 1.35;
        }
        .info-card-line:last-child {
            margin-bottom: 0;
        }
        body {
            margin: 0;
            padding: 12px 16px;
            font-size: 9.5px;
            color: #0f172a;
            background: #ffffff;
        }
        .timetable-wrapper {
            margin-top: 10px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }
        .timetable-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .timetable-grid th,
        .timetable-grid td {
            border: 1px solid #e2e8f0;
            padding: 4px 5px;
            text-align: center;
            font-size: 8.5px;
        }
        .timetable-grid thead th {
            background: #0453cb;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            -webkit-print-color-adjust: exact;
        }
        .timetable-time-cell {
            background: #eff4ff;
            font-weight: 600;
            width: 84px;
        }
        .header {
            background-color: #0453cb;
            background-image: linear-gradient(135deg, #0453cb, #5e91de);
            color: #ffffff;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 10px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .header-top {
            display: table;
            width: 100%;
        }
        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
        }
        .header-title h1 {
            margin: 0;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .header-title p {
            margin: 3px 0 0 0;
            font-size: 8.5px;
            opacity: 0.9;
        }
        .header-logo img {
            max-height: 40px;
            max-width: 110px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .header-info {
            display: table;
            width: 100%;
            margin-top: 8px;
        }
        .header-info-row {
            display: table-row;
        }
        .header-info-cell {
            display: table-cell;
            width: 25%;
            padding: 0 4px;
        }
        .header-card {
            background-color: #0f4ec3;
            border-radius: 8px;
            padding: 6px 7px;
            color: #ffffff;
            font-size: 8.5px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .header-card-title {
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 7.2px;
            font-weight: 700;
            margin-bottom: 3px;
            opacity: 0.85;
        }
        .header-card-line {
            margin-bottom: 2px;
            line-height: 1.3;
        }
        .header-card-line:last-child {
            margin-bottom: 0;
        }
        .tt-session {
            border-radius: 10px;
            padding: 6px;
            font-size: 8px;
            -webkit-print-color-adjust: exact;
        }
        .tt-session-type {
            font-size: 7.5px;
            letter-spacing: 0.08em;
            margin-bottom: 2px;
            opacity: 0.85;
        }
        .tt-session-subject {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .tt-session-teacher,
        .tt-session-room,
        .tt-session-time {
            font-size: 7.3px;
            margin-bottom: 1px;
            display: block;
        }
        .legend {
            margin-top: 12px;
            padding: 0;
            list-style: none;
        }
        .legend-item {
            display: inline-block;
            margin-right: 12px;
            font-size: 8.5px;
            color: #475569;
        }
        .legend-color {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 6px;
            margin-right: 5px;
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
        <div class="header-info">
            <div class="header-info-row">
                <div class="header-info-cell">
                    <div class="header-card">
                        <div class="header-card-title">Localisation</div>
                        <div class="header-card-line">
                            {{ trim(($etablissement['ville'] ?? '') . ' - ' . ($etablissement['pays'] ?? '')) ?: 'Yamoussoukro - Côte d\'Ivoire' }}
                        </div>
                        <div class="header-card-line">
                            {{ $etablissement['adresse'] ?: 'Adresse non renseignée' }}
                        </div>
                    </div>
                </div>
                <div class="header-info-cell">
                    <div class="header-card">
                        <div class="header-card-title">Contact</div>
                        <div class="header-card-line">Standard administratif</div>
                        <div class="header-card-line">Tél : {{ $etablissement['telephone'] ?: '--' }}</div>
                        <div class="header-card-line">Email : {{ $etablissement['email'] ?: '--' }}</div>
                        <div class="header-card-line">Contact officiel</div>
                    </div>
                </div>
                <div class="header-info-cell">
                    <div class="header-card">
                        <div class="header-card-title">Classe &amp; Filière</div>
                        <div class="header-card-line">
                            {{ mb_strtoupper($emploiTemps->classe->filiere->name ?? 'Filière non renseignée', 'UTF-8') }}
                        </div>
                        <div class="header-card-line">
                            {{ $emploiTemps->classe->niveau->name ?? 'Niveau non renseigné' }}
                        </div>
                        <div class="header-card-line">
                            {{ mb_strtoupper($emploiTemps->classe->name ?? 'Classe non renseignée', 'UTF-8') }}
                        </div>
                    </div>
                </div>
                <div class="header-info-cell">
                    <div class="header-card">
                        <div class="header-card-title">Couverture</div>
                        <div class="header-card-line">
                            {{ $daysCovered }} jour{{ $daysCovered > 1 ? 's' : '' }} couverts
                        </div>
                        <div class="header-card-line">
                            {{ $totalHoursFormatted }} cumulées
                        </div>
                    </div>
                </div>
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
