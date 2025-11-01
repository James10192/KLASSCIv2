<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps - {{ $emploiTemps->classe->name ?? 'Classe' }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            margin: 0;
            padding: 8px 10px;
            font-size: 8px;
            color: #0f172a;
            background: #ffffff;
        }

        /* HEADER COMPACT - TABLE LAYOUT */
        .header-compact {
            background: #0453cb;
            color: #ffffff;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .header-compact h1 {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 3px 0;
        }
        .header-compact p {
            font-size: 8px;
            opacity: 0.95;
            margin: 0 0 8px 0;
        }

        /* INFO TABLE - 4 colonnes */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .info-table td {
            width: 25%;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 5px;
            padding: 5px 7px;
            font-size: 6.5px;
            vertical-align: top;
        }
        .info-table td + td {
            padding-left: 10px;
        }
        .info-label {
            display: block;
            font-weight: 700;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 3px;
        }
        .info-value {
            display: block;
            opacity: 0.95;
            line-height: 1.4;
            margin-bottom: 2px;
        }

        /* GRILLE EMPLOI DU TEMPS - TABLE */
        .timetable-wrapper {
            margin-top: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }
        .timetable {
            width: 100%;
            border-collapse: collapse;
        }
        .timetable th,
        .timetable td {
            border: 1px solid #e2e8f0;
            padding: 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 7px;
        }

        /* Header jours */
        .timetable thead th {
            background: #0453cb;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            padding: 6px 4px;
        }

        /* Cellule heure */
        .time-cell {
            background: #f8fafc;
            font-weight: 700;
            font-size: 7.5px;
            color: #1e293b;
            width: 50px;
            padding: 4px;
            text-align: center;
        }

        /* Cellule jour vide */
        .day-cell-empty {
            background: #ffffff;
            min-height: 30px;
        }

        /* CARTE SÉANCE */
        .session-card {
            border-radius: 6px;
            padding: 5px 6px;
            width: 100%;
            height: 100%;
            text-align: center;
        }

        /* Type séance (haut, petit) */
        .session-type {
            font-size: 5.5px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 600;
            opacity: 0.75;
            margin-bottom: 3px;
        }

        /* Matière (centre, GRAND) */
        .session-subject {
            font-size: 9px;
            font-weight: 800;
            line-height: 1.2;
            margin: 4px 0;
        }

        /* Infos bas (petit) */
        .session-meta {
            font-size: 6px;
            opacity: 0.9;
            margin-top: 3px;
            line-height: 1.3;
        }
        .session-meta-line {
            margin: 1px 0;
        }

        /* Couleurs séances */
        .session-cours {
            background-color: #0453cb;
            color: #ffffff;
        }
        .session-devoir {
            background-color: #3ba54f;
            color: #ffffff;
        }
        .session-recreation {
            background-color: #f59e0b;
            color: #1f2937;
        }
        .session-dejeuner {
            background-color: #0ea5e9;
            color: #ffffff;
        }

        /* Légende */
        .legend {
            margin-top: 5px;
            padding: 0;
            list-style: none;
            text-align: center;
        }
        .legend-item {
            display: inline-block;
            margin: 0 6px;
            font-size: 6.5px;
            color: #475569;
        }
        .legend-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 3px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    {{-- HEADER COMPACT --}}
    <div class="header-compact">
        <h1>{{ $etablissement['nom'] }}</h1>
        <p>{{ $etablissement['type'] ?? 'Enseignement Supérieur Technique' }}</p>

        {{-- 4 COLONNES INFO - TABLE --}}
        <table class="info-table">
            <tr>
                <td>
                    <span class="info-label">LOCALISATION</span>
                    <span class="info-value">{{ $etablissement['ville'] ?? 'Yamoussoukro' }} - {{ $etablissement['pays'] ?? 'Côte d\'Ivoire' }}</span>
                    <span class="info-value">{{ $etablissement['adresse'] ?: 'Adresse non renseignée' }}</span>
                </td>
                <td>
                    <span class="info-label">CONTACT</span>
                    <span class="info-value">{{ $etablissement['telephone'] ? 'Tél : ' . $etablissement['telephone'] : 'Tél : --' }}</span>
                    <span class="info-value">{{ $etablissement['email'] ? 'Email : ' . $etablissement['email'] : 'Email : --' }}</span>
                    <span class="info-value">Contact officiel</span>
                </td>
                <td>
                    <span class="info-label">CLASSE & FILIÈRE</span>
                    <span class="info-value">{{ $emploiTemps->classe->filiere->name ?? 'Filière' }}</span>
                    <span class="info-value">{{ $emploiTemps->classe->niveau->name ?? 'Niveau' }}</span>
                    <span class="info-value">{{ $emploiTemps->classe->name ?? 'Classe' }}</span>
                </td>
                <td>
                    <span class="info-label">COUVERTURE</span>
                    <span class="info-value">{{ $daysCovered }} jour{{ $daysCovered > 1 ? 's' : '' }} couvert{{ $daysCovered > 1 ? 's' : '' }}</span>
                    <span class="info-value">{{ $totalHoursFormatted }} cumulées</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- GRILLE EMPLOI DU TEMPS --}}
    @php
        use Carbon\Carbon;
        use App\Models\ESBTPSeanceCours;

        // Mapping jours
        $jourMapping = [
            1 => 'lundi', 2 => 'mardi', 3 => 'mercredi',
            4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi',
        ];

        // Normaliser jours
        $normalizedDays = [];
        foreach ($days as $day) {
            if (is_numeric($day)) {
                $numeric = (int) $day;
                $slug = $jourMapping[$numeric] ?? 'jour';
                $label = $joursNoms[$numeric] ?? ucfirst($slug);
            } else {
                $slug = strtolower((string) $day);
                $numeric = array_search($slug, $jourMapping) ?: 1;
                $label = $joursNoms[$numeric] ?? ucfirst($slug);
            }
            $normalizedDays[] = ['key' => $numeric, 'slug' => $slug, 'label' => $label];
        }

        // Collecter heures (heures pleines UNIQUEMENT pour simplifier)
        $allHours = collect();
        foreach ($seances as $seance) {
            $startTime = $seance->heure_debut instanceof Carbon ? $seance->heure_debut->format('H:i') : substr($seance->heure_debut, 0, 5);
            $endTime = $seance->heure_fin instanceof Carbon ? $seance->heure_fin->format('H:i') : substr($seance->heure_fin, 0, 5);

            [$startHour] = explode(':', $startTime);
            [$endHour] = explode(':', $endTime);

            $allHours->push((int)$startHour);
            $allHours->push((int)$endHour);
        }

        // Plage heures
        $minHour = $allHours->isEmpty() ? 7 : $allHours->min();
        $maxHour = $allHours->isEmpty() ? 18 : $allHours->max();

        // Segments d'heures
        $hourSegments = [];
        for ($h = $minHour; $h <= $maxHour; $h++) {
            $hourSegments[] = sprintf('%02d:00', $h);
        }

        // Organiser séances par jour/heure
        $sessionsByDayHour = [];

        foreach ($seances as $seance) {
            $jourValue = $seance->jour;
            $jourSlug = is_numeric($jourValue) ? ($jourMapping[(int)$jourValue] ?? null) : strtolower(trim($jourValue));

            if (!$jourSlug) continue;

            $startTime = $seance->heure_debut instanceof Carbon ? $seance->heure_debut->format('H:i') : substr($seance->heure_debut, 0, 5);
            $endTime = $seance->heure_fin instanceof Carbon ? $seance->heure_fin->format('H:i') : substr($seance->heure_fin, 0, 5);

            [$startHour] = explode(':', $startTime);
            $startHourKey = sprintf('%02d:00', (int)$startHour);

            // Type classe CSS
            $typeClass = match($seance->type ?? 'course') {
                ESBTPSeanceCours::TYPE_COURSE => 'cours',
                ESBTPSeanceCours::TYPE_HOMEWORK => 'devoir',
                ESBTPSeanceCours::TYPE_BREAK => 'recreation',
                ESBTPSeanceCours::TYPE_LUNCH => 'dejeuner',
                default => 'cours',
            };

            // Calculer rowspan (nombre d'heures)
            [$endHour] = explode(':', $endTime);
            $durationHours = (int)$endHour - (int)$startHour;
            $rowspan = max(1, $durationHours);

            $sessionsByDayHour[$jourSlug][$startHourKey] = [
                'rowspan' => $rowspan,
                'typeClass' => $typeClass,
                'typeLabel' => strtoupper($sessionTypeLabels[$seance->type ?? ESBTPSeanceCours::TYPE_COURSE] ?? 'Cours'),
                'matiere' => $seance->matiere->name ?? 'Matière',
                'enseignant' => $seance->enseignant_nom ?? optional(optional($seance->teacher)->user)->name,
                'salle' => $seance->salle,
                'timeRange' => "$startTime - $endTime",
            ];
        }

        // Cellules couvertes par rowspan
        $coveredCells = [];
        foreach ($sessionsByDayHour as $daySlug => $sessions) {
            foreach ($sessions as $hourKey => $session) {
                $hourIndex = array_search($hourKey, $hourSegments);
                if ($hourIndex !== false) {
                    for ($r = 1; $r < $session['rowspan']; $r++) {
                        if (isset($hourSegments[$hourIndex + $r])) {
                            $coveredCells[$daySlug][$hourSegments[$hourIndex + $r]] = true;
                        }
                    }
                }
            }
        }
    @endphp

    <div class="timetable-wrapper">
        <table class="timetable">
            <thead>
                <tr>
                    <th style="width: 50px;">Heure</th>
                    @foreach($normalizedDays as $dayInfo)
                        <th>{{ $dayInfo['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($hourSegments as $hour)
                    <tr>
                        {{-- Cellule heure --}}
                        <td class="time-cell">{{ $hour }}</td>

                        {{-- Cellules jours --}}
                        @foreach($normalizedDays as $dayInfo)
                            @php
                                $daySlug = $dayInfo['slug'];
                                $isCovered = isset($coveredCells[$daySlug][$hour]);
                                $hasSession = isset($sessionsByDayHour[$daySlug][$hour]);
                            @endphp

                            @if($isCovered)
                                {{-- Skip : déjà couvert par rowspan --}}
                            @elseif($hasSession)
                                {{-- Cellule avec séance --}}
                                @php $session = $sessionsByDayHour[$daySlug][$hour]; @endphp
                                <td rowspan="{{ $session['rowspan'] }}" style="vertical-align: middle; padding: 0;">
                                    <div class="session-card session-{{ $session['typeClass'] }}">
                                        <div class="session-type">{{ $session['typeLabel'] }}</div>
                                        <div class="session-subject">{{ $session['matiere'] }}</div>
                                        <div class="session-meta">
                                            @if($session['enseignant'])
                                                <div class="session-meta-line">{{ $session['enseignant'] }}</div>
                                            @endif
                                            @if($session['salle'])
                                                <div class="session-meta-line">Salle {{ $session['salle'] }}</div>
                                            @endif
                                            <div class="session-meta-line">{{ $session['timeRange'] }}</div>
                                        </div>
                                    </div>
                                </td>
                            @else
                                {{-- Cellule vide --}}
                                <td class="day-cell-empty"></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- LÉGENDE --}}
    @if(!empty($sessionTypeLabels))
        <ul class="legend">
            @foreach($sessionTypeLabels as $type => $label)
                @php
                    $color = match($type) {
                        ESBTPSeanceCours::TYPE_COURSE => '#0453cb',
                        ESBTPSeanceCours::TYPE_HOMEWORK => '#3ba54f',
                        ESBTPSeanceCours::TYPE_BREAK => '#f59e0b',
                        ESBTPSeanceCours::TYPE_LUNCH => '#0ea5e9',
                        default => '#0453cb',
                    };
                @endphp
                <li class="legend-item">
                    <span class="legend-dot" style="background-color: {{ $color }};"></span>
                    {{ $label }}
                </li>
            @endforeach
        </ul>
    @endif
</body>
</html>
