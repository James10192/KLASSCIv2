@props([
    'seances' => collect(),
    'timeSlots' => [],
    'days' => [],
    'dayLabels' => [],
    'sessionStyles' => [],
    'sessionLabels' => [],
    'variant' => 'web',
    'emploiTemps' => null,
    'interactive' => false,
])

@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
    use App\Models\ESBTPSeanceCours;

    $jourMapping = [
        1 => 'lundi',
        2 => 'mardi',
        3 => 'mercredi',
        4 => 'jeudi',
        5 => 'vendredi',
        6 => 'samedi',
        0 => 'dimanche',
        7 => 'dimanche',
    ];

    $dayToNumber = array_flip($jourMapping);

    $timeToMinutes = function ($timeValue) {
        if ($timeValue instanceof Carbon) {
            return ((int) $timeValue->format('H')) * 60 + (int) $timeValue->format('i');
        }

        if ($timeValue instanceof \DateTimeInterface) {
            return ((int) $timeValue->format('H')) * 60 + (int) $timeValue->format('i');
        }

        if (is_string($timeValue) && strlen($timeValue) >= 4) {
            [$hour, $minute] = array_pad(explode(':', substr($timeValue, 0, 5)), 2, 0);
            if (is_numeric($hour) && is_numeric($minute)) {
                return ((int) $hour) * 60 + (int) $minute;
            }
        }

        return null;
    };

    $normalizeTimeString = function ($timeValue) {
        if ($timeValue instanceof Carbon) {
            return $timeValue->format('H:i');
        }

        if ($timeValue instanceof \DateTimeInterface) {
            return $timeValue->format('H:i');
        }

        if (is_string($timeValue) && strlen($timeValue) >= 4) {
            return substr($timeValue, 0, 5);
        }

        return null;
    };

    $defaultTimeSlots = [];
    for ($hour = 7; $hour <= 18; $hour++) {
        $defaultTimeSlots[] = sprintf('%02d:00', $hour);
    }

    $normalizedSlots = empty($timeSlots) ? $defaultTimeSlots : array_values($timeSlots);
    $normalizedSlots = array_values(array_unique($normalizedSlots));
    sort($normalizedSlots);

    if (empty($normalizedSlots)) {
        $normalizedSlots = $defaultTimeSlots;
    }

    $minutesPerSegment = 15;

    $normalizedDays = [];
    foreach ($days as $day) {
        if (is_numeric($day)) {
            $numeric = (int) $day;
            $slug = $jourMapping[$numeric] ?? strtolower($day);
            $label = $dayLabels[$numeric] ?? ucfirst($slug);
            $normalizedDays[] = ['key' => $numeric, 'slug' => $slug, 'label' => $label];
        } else {
            $slug = strtolower((string) $day);
            $numeric = collect($jourMapping)->search($slug, true);
            $labelKey = $numeric !== false ? $numeric : $slug;
            $label = $dayLabels[$labelKey] ?? ucfirst($slug);
            $normalizedDays[] = ['key' => $numeric !== false ? $numeric : $slug, 'slug' => $slug, 'label' => $label];
        }
    }
    if (empty($normalizedDays)) {
        $normalizedDays = [
            ['key' => 1, 'slug' => 'lundi', 'label' => 'Lundi'],
            ['key' => 2, 'slug' => 'mardi', 'label' => 'Mardi'],
            ['key' => 3, 'slug' => 'mercredi', 'label' => 'Mercredi'],
            ['key' => 4, 'slug' => 'jeudi', 'label' => 'Jeudi'],
            ['key' => 5, 'slug' => 'vendredi', 'label' => 'Vendredi'],
            ['key' => 6, 'slug' => 'samedi', 'label' => 'Samedi'],
        ];
    }

    $minMinutes = 7 * 60;
    $maxMinutes = 18 * 60;

    $seancesCollection = $seances instanceof \Illuminate\Support\Collection ? $seances : collect($seances ?: []);

    $occupiedGridRows = [];

    foreach ($seancesCollection as $seance) {
        $startString = $normalizeTimeString($seance->heure_debut ?? null);
        $endString = $normalizeTimeString($seance->heure_fin ?? null);

        $startMinutes = $timeToMinutes($startString);
        $endMinutes = $timeToMinutes($endString);

        if ($startMinutes !== null) {
            $minMinutes = min($minMinutes, (int) floor($startMinutes / $minutesPerSegment) * $minutesPerSegment);
        }

        if ($endMinutes !== null) {
            $maxMinutes = max($maxMinutes, (int) ceil($endMinutes / $minutesPerSegment) * $minutesPerSegment);
        }
    }

    $totalRangeMinutes = max($minutesPerSegment, $maxMinutes - $minMinutes);
    $segmentCount = (int) ceil($totalRangeMinutes / $minutesPerSegment);

    $segments = [];
    for ($segment = 0; $segment <= $segmentCount; $segment++) {
        $minuteValue = $minMinutes + $segment * $minutesPerSegment;
        $segments[] = [
            'minutes' => $minuteValue,
            'label' => $minuteValue % 60 === 0 ? sprintf('%02d:%02d', intdiv($minuteValue, 60), $minuteValue % 60) : null,
        ];
    }

    $segmentHeight = $variant === 'pdf' ? 18 : 26;

    $defaultLabels = [
        ESBTPSeanceCours::TYPE_COURSE => 'Cours',
        ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
        ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
        ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
    ];
    $labelMap = array_merge($defaultLabels, $sessionLabels ?? []);

    $getSessionStyle = function ($type) use ($sessionStyles) {
        $style = $sessionStyles[$type] ?? ($sessionStyles['default'] ?? ['bg' => '#0ea5e9', 'text' => '#ffffff']);
        $style['bg'] = $style['bg'] ?? '#0ea5e9';
        $style['text'] = $style['text'] ?? '#ffffff';
        return $style;
    };

    $computeTextColor = function ($hexColor, $fallback = '#ffffff') {
        if (!$hexColor) {
            return $fallback;
        }

        $hex = ltrim($hexColor, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return $fallback;
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;
        return $luminance > 0.6 ? '#0f172a' : '#ffffff';
    };

    $timelineSessions = [];
    $occupiedGridRows = [];
    $allowedDaySlugs = collect($normalizedDays)->pluck('slug')->map(fn ($slug) => strtolower(trim($slug)))->flip();

    foreach ($seancesCollection as $seance) {
        $jourValue = $seance->jour;
        $jourSlug = null;

        if (is_numeric($jourValue)) {
            $jourSlug = $jourMapping[(int) $jourValue] ?? null;
        } elseif (is_string($jourValue)) {
            $jourSlug = strtolower(trim($jourValue));
        }

        if ($jourSlug) {
            $jourSlug = strtolower(trim($jourSlug));
        }

        if (!$jourSlug || !$allowedDaySlugs->has($jourSlug)) {
            continue;
        }

        $startString = $normalizeTimeString($seance->heure_debut ?? null);
        $endString = $normalizeTimeString($seance->heure_fin ?? null);

        $startMinutes = $timeToMinutes($startString);
        $endMinutes = $timeToMinutes($endString);

        if ($startMinutes === null) {
            continue;
        }

        if ($endMinutes === null || $endMinutes <= $startMinutes) {
            $endMinutes = $startMinutes + $minutesPerSegment;
        }

        $relativeStart = max(0, (int) floor(($startMinutes - $minMinutes) / $minutesPerSegment));
        $durationSegments = max(1, (int) ceil(($endMinutes - $startMinutes) / $minutesPerSegment));
        $durationSegments = min($durationSegments, max(1, $segmentCount - $relativeStart));

        $gridRowStart = $relativeStart + 2;
        $gridRowEnd = $gridRowStart + $durationSegments;

        $type = $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
        $style = $getSessionStyle($type);
        $backgroundColor = $seance->color ?: $style['bg'];
        $textColor = $computeTextColor($backgroundColor, $style['text']);

        $matiere = $seance->matiere->name ?? 'Matière';
        $enseignant = $seance->enseignant_nom ?? optional(optional($seance->teacher)->user)->name;
        $salle = $seance->salle;

        try {
            $startDisplay = Carbon::createFromFormat('H:i', $startString)->format('H:i');
        } catch (\Exception $e) {
            $startDisplay = $startString;
        }

        try {
            $endDisplay = Carbon::createFromFormat('H:i', $endString)->format('H:i');
        } catch (\Exception $e) {
            $endDisplay = $endString;
        }

        $timeRange = trim(($startDisplay ?: '') . ' - ' . ($endDisplay ?: ''));

        $timelineSessions[$jourSlug][] = [
            'id' => $seance->id,
            'type' => $type,
            'typeLabel' => strtoupper($labelMap[$type] ?? 'Séance'),
            'background' => $backgroundColor,
            'textColor' => $textColor,
            'matiere' => $matiere,
            'enseignant' => $enseignant,
            'salle' => $salle,
            'timeRange' => $timeRange,
            'notes' => $seance->description,
            'gridColumn' => $jourSlug,
            'gridRowStart' => $gridRowStart,
            'gridRowEnd' => $gridRowEnd,
            'rowspanSegments' => $durationSegments,
        ];

        for ($row = $gridRowStart; $row < $gridRowEnd; $row++) {
            $occupiedGridRows[$jourSlug][$row] = true;
        }

        for ($row = $gridRowStart; $row < $gridRowEnd; $row++) {
            $occupiedGridRows[$jourSlug][$row] = true;
        }
    }

    $daysCount = count($normalizedDays);
    $gridTemplateColumns = '80px repeat(' . $daysCount . ', 1fr)';
    $gridTemplateRows = 'repeat(' . ($segmentCount + 1) . ', ' . $segmentHeight . 'px)';

@endphp

@if($variant === 'web')
    @once
        <style>
            .timeline-grid {
                border: 1px solid #dbeafe;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
                background: #ffffff;
            }
            .timeline-grid .timeline-header {
                display: contents;
            }
            .timeline-grid .timeline-header-cell {
                padding: 10px 12px;
                background: linear-gradient(135deg, #0453cb, #0f6ae0);
                color: #ffffff;
                font-size: 0.75rem;
                letter-spacing: 0.08em;
                font-weight: 600;
                text-transform: uppercase;
                border-right: 1px solid rgba(255,255,255,0.18);
            }
            .timeline-grid .timeline-header-cell:last-child {
                border-right: none;
            }
            .timeline-grid .timeline-hour-cell {
                display: flex;
                align-items: flex-start;
                justify-content: flex-end;
                padding: 6px 8px;
                font-size: 0.82rem;
                font-weight: 600;
                color: #1e293b;
                background: #f8fafc;
                border-right: 1px solid #e2e8f0;
            }
            .timeline-grid .timeline-day-background {
                position: relative;
                background-image: linear-gradient(to bottom, rgba(148, 163, 184, 0.12) 1px, transparent 1px);
                background-size: 100% {{ $segmentHeight * 4 }}px;
                border-right: 1px solid rgba(226, 232, 240, 0.9);
            }
            .timeline-grid .timeline-day-background:last-child {
                border-right: none;
            }
            .timeline-grid .timeline-session {
                border-radius: 14px;
                padding: 10px;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
                display: flex;
                flex-direction: column;
                gap: 4px;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
                justify-self: center;
                width: 95%;
            }
            .timeline-grid .timeline-session:hover {
                transform: translateY(-4px);
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.2);
            }
            .timeline-grid .timeline-session-type {
                font-size: 0.7rem;
                letter-spacing: 0.08em;
                font-weight: 700;
                opacity: 0.85;
            }
            .timeline-grid .timeline-session-subject {
                font-size: 0.95rem;
                font-weight: 700;
            }
            .timeline-grid .timeline-session-meta {
                font-size: 0.78rem;
                opacity: 0.92;
            }
            .timeline-grid .timeline-session-actions {
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            .timeline-grid .timeline-session:hover .timeline-session-actions {
                opacity: 1;
            }
            .timeline-grid .timeline-slot-add {
                width: 26px;
                height: 26px;
                border-radius: 50%;
                border: 2px dashed #3b82f6;
                color: #1d4ed8;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.75rem;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
                transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            }
            .timeline-grid .timeline-slot-add:hover {
                background: #1d4ed8;
                color: #ffffff;
                transform: translate(-50%, -50%) scale(1.05);
            }
        </style>
    @endonce

    @php
        $dayIndexMap = collect($normalizedDays)->pluck('slug')->mapWithKeys(function ($day, $index) {
            return [$day => $index + 2]; // column 1 reserved for hours
        })->toArray();
    @endphp

    <div class="timeline-grid" style="display: grid; grid-template-columns: {{ $gridTemplateColumns }}; grid-template-rows: auto {{ $gridTemplateRows }};">
        <div class="timeline-header">
            <div class="timeline-header-cell">Heure</div>
            @foreach($normalizedDays as $dayInfo)
                <div class="timeline-header-cell">{{ $dayInfo['label'] }}</div>
            @endforeach
        </div>

        @foreach($segments as $index => $segment)
            <div class="timeline-hour-cell" style="grid-column: 1; grid-row: {{ $index + 2 }};">
                {{ $segment['label'] ?? '' }}
            </div>
        @endforeach

        @foreach($normalizedDays as $dayInfo)
            @php
                $daySlug = $dayInfo['slug'];
                $columnIndex = $dayIndexMap[$daySlug] ?? 2;
            @endphp
            <div class="timeline-day-background" style="grid-column: {{ $columnIndex }}; grid-row: 2 / span {{ $segmentCount + 1 }};"></div>

            @if($interactive && $emploiTemps)
                @foreach($segments as $index => $segment)
                    @php
                        $minuteValue = $segment['minutes'];
                        if ($minuteValue >= $maxMinutes) { continue; }
                        if ($minuteValue % 60 !== 0) { continue; }
                        $timeString = sprintf('%02d:%02d', intdiv($minuteValue, 60), $minuteValue % 60);
                        $dayNumber = is_numeric($dayInfo['key']) ? $dayInfo['key'] : ($dayToNumber[strtolower($dayInfo['key'])] ?? ($dayToNumber[$daySlug] ?? 1));
                        $rowIndex = $index + 2;
                        if (($occupiedGridRows[$daySlug][$rowIndex] ?? false)) {
                            continue;
                        }
                    @endphp
                    <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id, 'jour' => $dayNumber, 'heure_debut' => $timeString]) }}"
                       class="timeline-slot-add"
                       style="grid-column: {{ $columnIndex }}; grid-row: {{ $rowIndex }}; transform: translate(-50%, -50%); justify-self: center; align-self: center;"
                       title="Ajouter une séance">
                        <i class="fas fa-plus"></i>
                    </a>
                @endforeach
            @endif

            @foreach($timelineSessions[$daySlug] ?? [] as $session)
                <div class="timeline-session type-{{ $session['type'] }}"
                     style="grid-column: {{ $columnIndex }}; grid-row: {{ $session['gridRowStart'] }} / {{ $session['gridRowEnd'] }}; background: {{ $session['background'] }}; color: {{ $session['textColor'] }}; justify-self: center; width: 95%;">
                    <div class="timeline-session-type">{{ $session['typeLabel'] }}</div>
                    <div class="timeline-session-subject">{{ $session['matiere'] }}</div>
                    @if($session['enseignant'])
                        <div class="timeline-session-meta"><i class="fas fa-user-tie me-1"></i>{{ $session['enseignant'] }}</div>
                    @endif
                    @if($session['salle'])
                        <div class="timeline-session-meta"><i class="fas fa-door-open me-1"></i>{{ $session['salle'] }}</div>
                    @endif
                    @if($session['timeRange'])
                        <div class="timeline-session-meta"><i class="fas fa-clock me-1"></i>{{ $session['timeRange'] }}</div>
                    @endif
                    @if($session['notes'])
                        <div class="timeline-session-meta">{{ Str::limit($session['notes'], 80) }}</div>
                    @endif

                    @if($interactive)
                        <div class="timeline-session-actions">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('esbtp.seances-cours.edit', $session['id']) }}" class="btn btn-light btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('esbtp.seances-cours.destroy', $session['id']) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette séance ?');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
@else
    @php
        $pdfRows = [];
        for ($segment = 0; $segment < $segmentCount; $segment++) {
            $minuteValue = $minMinutes + $segment * $minutesPerSegment;
            $pdfRows[] = [
                'index' => $segment,
                'label' => $minuteValue % 60 === 0 ? sprintf('%02d:%02d', intdiv($minuteValue, 60), $minuteValue % 60) : '',
            ];
        }

        $pdfCells = [];
        $pdfCovered = [];
        foreach ($timelineSessions as $daySlug => $sessions) {
            foreach ($sessions as $session) {
                $rowIndex = max(0, $session['gridRowStart'] - 2);
                $rowSpan = max(1, $session['rowspanSegments']);
                $pdfCells[$daySlug][$rowIndex] = $session + ['rowspan' => $rowSpan];
                for ($offset = 1; $offset < $rowSpan; $offset++) {
                    $pdfCovered[$daySlug][$rowIndex + $offset] = true;
                }
            }
        }
    @endphp

    <table class="timetable-grid timetable-variant-{{ $variant }}">
        <thead>
            <tr>
                <th class="timetable-time-header">Heure</th>
                @foreach($normalizedDays as $dayInfo)
                    <th class="timetable-day-header">{{ $dayInfo['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($pdfRows as $row)
                <tr>
                    <td class="timetable-time-cell">{{ $row['label'] }}</td>
                    @foreach($normalizedDays as $dayInfo)
                        @php
                            $daySlug = $dayInfo['slug'];
                        @endphp
                        @if(isset($pdfCells[$daySlug][$row['index']]))
                            @php $cell = $pdfCells[$daySlug][$row['index']]; @endphp
                            <td class="timetable-session-cell" rowspan="{{ $cell['rowspan'] }}">
                                <div class="tt-session type-{{ $cell['type'] }}" style="background: {{ $cell['background'] }}; color: {{ $cell['textColor'] }};">
                                    <div class="tt-session-type" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.85;">
                                        {{ $cell['typeLabel'] }}
                                    </div>
                                    <div class="tt-session-subject">{{ $cell['matiere'] }}</div>
                                    @if($cell['enseignant'])
                                        <div class="tt-session-teacher"><i class="fas fa-user-tie"></i> {{ $cell['enseignant'] }}</div>
                                    @endif
                                    @if($cell['salle'])
                                        <div class="tt-session-room"><i class="fas fa-door-open"></i> {{ $cell['salle'] }}</div>
                                    @endif
                                    @if($cell['timeRange'])
                                        <div class="tt-session-time"><i class="fas fa-clock"></i> {{ $cell['timeRange'] }}</div>
                                    @endif
                                    @if($cell['notes'])
                                        <div class="tt-session-notes">{{ Str::limit($cell['notes'], 80) }}</div>
                                    @endif
                                </div>
                            </td>
                        @elseif(isset($pdfCovered[$daySlug][$row['index']]))
                            @continue
                        @else
                            <td class="timetable-empty-cell"></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
