<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Emploi du Temps - {{ $emploiTemps->classe->name ?? 'Non défini' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #1e3a8a;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
        }
        .info {
            margin-bottom: 15px;
            padding: 8px;
            background-color: #f8f9fa;
            border-left: 4px solid #1e3a8a;
        }
        .timetable {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .timetable th, .timetable td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
            font-size: 8px;
        }
        .timetable th {
            background-color: #1e3a8a;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }
        .time-column {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 80px;
        }
        .session-cell {
            background-color: #e3f2fd;
            padding: 2px;
            font-size: 7px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EMPLOI DU TEMPS</h1>
    </div>

    <div class="info">
        <strong>Classe:</strong> {{ $emploiTemps->classe->name ?? 'Non définie' }} | 
        <strong>Filière:</strong> {{ $emploiTemps->classe->filiere->name ?? 'Non définie' }} | 
        <strong>Année:</strong> {{ $emploiTemps->annee->name ?? 'Non définie' }}
    </div>

    <table class="timetable">
        <thead>
            <tr>
                <th class="time-column">Heure</th>
                <th>Lundi</th>
                <th>Mardi</th>
                <th>Mercredi</th>
                <th>Jeudi</th>
                <th>Vendredi</th>
                <th>Samedi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($timeSlots as $timeIndex => $time)
                <tr>
                    <td class="time-column">{{ $time }}</td>
                    @foreach($days as $dayIndex => $day)
                        <td>
                            @if(isset($seancesParJour[$dayIndex + 1][$timeIndex]))
                                @php
                                    $seance = $seancesParJour[$dayIndex + 1][$timeIndex];
                                @endphp
                                <div class="session-cell">
                                    <strong>{{ $seance->matiere->name ?? 'Matière' }}</strong><br>
                                    {{ $seance->enseignant_nom ?? 'Enseignant' }}<br>
                                    <small>{{ $seance->salle ?? 'Salle' }}</small>
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        use App\Helpers\SettingsHelper;
        $schoolName = SettingsHelper::get('school_name', 'École Spéciale du Bâtiment et des Travaux Publics');
    @endphp
    
    <div class="footer">
        <p>{{ $schoolName }} - {{ $date_edition }}</p>
    </div>
</body>
</html>