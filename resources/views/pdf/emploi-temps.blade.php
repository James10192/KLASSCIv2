<!DOCTYPE html>
<html lang="fr">
<head>
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
            padding: 16px 20px;
            color: #1f2937;
            font-size: 10px;
            background: #ffffff;
        }
        .container {
            width: 100%;
        }
        .header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            padding: 16px 18px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .header-left h1 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 0.1em;
        }
        .header-left p {
            margin: 2px 0 0 0;
            font-size: 10px;
            opacity: 0.85;
        }
        .header-logo img {
            max-height: 48px;
            max-width: 120px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .kpi-grid {
            display: table;
            width: 100%;
            margin: 14px 0;
            border-spacing: 8px;
        }
        .kpi-card {
            display: table-cell;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            vertical-align: top;
        }
        .kpi-title {
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.12em;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: 700;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 3px;
        }
        .kpi-desc {
            font-size: 8px;
            color: #94a3b8;
        }

        .school-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .school-card-header {
            background: #1d4ed8;
            color: white;
            padding: 10px 12px;
            text-align: center;
            -webkit-print-color-adjust: exact;
        }
        .school-card-header h2 {
            margin: 0;
            font-size: 14px;
            letter-spacing: 0.08em;
        }
        .school-card-body {
            padding: 12px;
            background: #ffffff;
        }
        .info-grid {
            display: table;
            width: 100%;
            border-spacing: 6px;
        }
        .info-item {
            display: table-cell;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px;
            vertical-align: top;
        }
        .info-title {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 3px;
            font-weight: 700;
        }
        .info-value {
            font-size: 10px;
            color: #1f2937;
        }

        .section-title {
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 9px;
            font-weight: 700;
            color: #1d4ed8;
            margin: 14px 0 6px 0;
        }

        .stat-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .stat-table th,
        .stat-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 9px;
        }
        .stat-table th {
            background: #2563eb;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            -webkit-print-color-adjust: exact;
        }
        .stat-table td {
            background: #f8fafc;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 6px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
        }
        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .timetable-wrapper {
            margin-top: 12px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            overflow: hidden;
        }
        .timetable-wrapper table {
            width: 100%;
        }
        .timetable-grid thead th {
            background: #1d4ed8 !important;
            color: white !important;
            font-size: 9px;
            -webkit-print-color-adjust: exact;
        }
        .timetable-time-cell {
            background: #f1f5f9 !important;
            font-weight: 600;
        }
        .timetable-session-cell {
            padding: 2px !important;
        }
        .tt-session {
            border-radius: 12px;
            padding: 6px;
            font-size: 8px;
            text-align: center;
        }
        .tt-session-subject {
            font-weight: 700;
            font-size: 9px;
            margin-bottom: 2px;
        }
        .tt-session-teacher,
        .tt-session-room,
        .tt-session-time {
            font-size: 7.5px;
            margin-bottom: 1px;
        }
        .tt-session-notes {
            font-size: 7px;
            opacity: 0.8;
        }
        .footer {
            margin-top: 12px;
            font-size: 8px;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>EMPLOI DU TEMPS</h1>
                <p>{{ $emploiTemps->classe->name ?? 'Classe' }} • {{ $periodeAffichage ?? ($emploiTemps->annee->name ?? 'Période non renseignée') }}</p>
            </div>
            @if($logoBase64)
                <div class="header-logo">
                    <img src="{{ $logoBase64 }}" alt="Logo">
                </div>
            @endif
        </div>

        <div class="kpi-grid">
            @foreach($summaryStats as $stat)
                <div class="kpi-card">
                    <div class="kpi-title">{{ $stat['label'] }}</div>
                    <div class="kpi-value">
                        @if(is_numeric($stat['value']))
                            {{ number_format($stat['value'], 0, ',', ' ') }}
                        @else
                            {{ $stat['value'] }}
                        @endif
                    </div>
                    <div class="kpi-desc">{{ $stat['description'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="school-card">
            <div class="school-card-header">
                <h2>{{ $etablissement['nom'] }}</h2>
                <div style="font-size: 9px; opacity: 0.85;">
                    {{ $etablissement['type'] ?? 'Établissement' }}
                </div>
            </div>
            <div class="school-card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-title">Adresse</div>
                        <div class="info-value">
                            {{ $etablissement['adresse'] ?: 'Adresse non renseignée' }}<br>
                            {{ $etablissement['ville'] ?? '' }} {{ $etablissement['pays'] ? ' - ' . $etablissement['pays'] : '' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-title">Contact</div>
                        <div class="info-value">
                            Tél : {{ $etablissement['telephone'] ?: '---' }}<br>
                            Email : {{ $etablissement['email'] ?: '---' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-title">Classe & Filière</div>
                        <div class="info-value">
                            Classe : {{ $emploiTemps->classe->name ?? '---' }}<br>
                            Filière : {{ $emploiTemps->classe->filiere->name ?? '---' }}<br>
                            Niveau : {{ $emploiTemps->classe->niveau->name ?? '---' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-title">Couverture</div>
                        <div class="info-value">
                            {{ $daysCovered }} jour{{ $daysCovered > 1 ? 's' : '' }} couverts<br>
                            {{ $totalHoursFormatted }} cumulées
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title">Répartition par type de séance</div>
        <table class="stat-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Volume</th>
                    <th>Part</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessionTypeLabels as $type => $label)
                    @php
                        $count = $sessionTypeStats[$type] ?? 0;
                        $style = $sessionTypeColors[$type] ?? $sessionTypeColors['default'];
                        $share = $totalSeances > 0 ? number_format(($count / $totalSeances) * 100, 0) : 0;
                    @endphp
                    <tr>
                        <td style="background: {{ $style['bg'] }}; color: {{ $style['text'] }}; -webkit-print-color-adjust: exact;">
                            {{ $label }}
                        </td>
                        <td>{{ $count }} séance{{ $count > 1 ? 's' : '' }}</td>
                        <td>{{ $share }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Matières principales</div>
        <table class="stat-table">
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Nombre de séances</th>
                    <th>Part</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matiereStats->take(8) as $matiere => $count)
                    @php
                        $share = $totalSeances > 0 ? number_format(($count / $totalSeances) * 100, 0) : 0;
                    @endphp
                    <tr>
                        <td>{{ $matiere }}</td>
                        <td>{{ $count }}</td>
                        <td>{{ $share }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Aucune séance enregistrée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Grille hebdomadaire</div>
        <div class="timetable-wrapper">
            @include('esbtp.emploi-temps.partials.timetable-grid', [
                'seances' => $seances,
                'timeSlots' => $timeSlots,
                'days' => $days,
                'dayLabels' => $joursNoms,
                'sessionStyles' => $sessionTypeColors,
                'variant' => 'pdf',
            ])
        </div>

        <div class="legend">
            @foreach($sessionTypeLabels as $type => $label)
                @php
                    $style = $sessionTypeColors[$type] ?? $sessionTypeColors['default'];
                @endphp
                <div class="legend-item">
                    <span class="legend-color" style="background: {{ $style['bg'] }};"></span>
                    {{ $label }}
                </div>
            @endforeach
        </div>

        <div class="footer">
            Document généré le {{ $date_edition }} — {{ $settings['school_name'] ?? '' }}
        </div>
    </div>
</body>
</html>
