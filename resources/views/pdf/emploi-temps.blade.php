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
        body {
            margin: 0;
            padding: 16px 20px;
            font-size: 10px;
            color: #1f2937;
            background: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #0453cb, #5e91de);
            color: #ffffff;
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            -webkit-print-color-adjust: exact;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 0.1em;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 10px;
            opacity: 0.9;
        }
        .header-logo img {
            max-height: 48px;
            max-width: 120px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .info-grid {
            margin: 14px 0;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            background: #f8fafc;
        }
        .info-section {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .info-block {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .info-title {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 8px;
            color: #0453cb;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .info-line {
            font-size: 10px;
            margin-bottom: 2px;
        }
        .info-line strong {
            font-weight: 600;
        }
        .timetable-wrapper {
            margin-top: 16px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            overflow: hidden;
        }
        .timetable-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .timetable-grid th,
        .timetable-grid td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            text-align: center;
            font-size: 9px;
        }
        .timetable-grid thead th {
            background: #0453cb;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            -webkit-print-color-adjust: exact;
        }
        .timetable-time-cell {
            background: #f1f5f9;
            font-weight: 600;
            width: 100px;
        }
        .tt-session {
            border-radius: 12px;
            padding: 6px;
            font-size: 8px;
        }
        .tt-session-subject {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .tt-session-teacher,
        .tt-session-room,
        .tt-session-time {
            font-size: 7.5px;
            margin-bottom: 1px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>EMPLOI DU TEMPS</h1>
            <p>{{ $emploiTemps->classe->name ?? 'Classe' }} • {{ $periodeAffichage ?? ($emploiTemps->annee->name ?? 'Période non renseignée') }}</p>
        </div>
        @if($logoBase64)
            <div class="header-logo">
                <img src="{{ $logoBase64 }}" alt="Logo">
            </div>
        @endif
    </div>

    <div class="info-grid">
        <div class="info-section">
            <div class="info-block">
                <div class="info-title">École Spéciale du Bâtiment et des Travaux Publics</div>
                <div class="info-line">{{ $etablissement['type'] ?? 'Enseignement Supérieur Technique' }}</div>
            </div>
            <div class="info-block">
                <div class="info-title">Adresse</div>
                <div class="info-line">{{ $etablissement['adresse'] ?: 'Adresse non renseignée' }}</div>
                <div class="info-line">{{ trim(($etablissement['ville'] ?? '') . ' - ' . ($etablissement['pays'] ?? '')) ?: 'Yamoussoukro - Côte d\'Ivoire' }}</div>
            </div>
            <div class="info-block">
                <div class="info-title">Contact</div>
                <div class="info-line">Tél : {{ $etablissement['telephone'] ?: '--' }}</div>
                <div class="info-line">Email : {{ $etablissement['email'] ?: '--' }}</div>
            </div>
            <div class="info-block">
                <div class="info-title">Classe & Filière</div>
                <div class="info-line"><strong>Classe :</strong> {{ $emploiTemps->classe->name ?? '--' }}</div>
                <div class="info-line"><strong>Filière :</strong> {{ $emploiTemps->classe->filiere->name ?? '--' }}</div>
                <div class="info-line"><strong>Niveau :</strong> {{ $emploiTemps->classe->niveau->name ?? '--' }}</div>
            </div>
            <div class="info-block">
                <div class="info-title">Couverture</div>
                <div class="info-line">{{ $daysCovered }} jour{{ $daysCovered > 1 ? 's' : '' }} couverts</div>
                <div class="info-line">{{ $totalHoursFormatted }} cumulées</div>
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
            'variant' => 'pdf',
        ])
    </div>
</body>
</html>
