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

    // Collect all unique start and end times from sessions
    $importantTimes = collect();

    foreach ($seancesCollection as $seance) {
        $startString = $normalizeTimeString($seance->heure_debut ?? null);
        $endString = $normalizeTimeString($seance->heure_fin ?? null);

        $startMinutes = $timeToMinutes($startString);
        $endMinutes = $timeToMinutes($endString);

        if ($startMinutes !== null) {
            $minMinutes = min($minMinutes, (int) floor($startMinutes / $minutesPerSegment) * $minutesPerSegment);
            $importantTimes->push($startMinutes);
        }

        if ($endMinutes !== null) {
            $maxMinutes = max($maxMinutes, (int) ceil($endMinutes / $minutesPerSegment) * $minutesPerSegment);
            $importantTimes->push($endMinutes);
        }
    }

    // Get unique important times (start/end of sessions)
    $importantTimes = $importantTimes->unique()->sort()->values();

    $totalRangeMinutes = max($minutesPerSegment, $maxMinutes - $minMinutes);
    $segmentCount = (int) ceil($totalRangeMinutes / $minutesPerSegment);

    $segments = [];
    for ($segment = 0; $segment <= $segmentCount; $segment++) {
        $minuteValue = $minMinutes + $segment * $minutesPerSegment;

        // Show label for full hours OR if this is an important time (session start/end)
        $isImportantTime = $importantTimes->contains($minuteValue);
        $isFullHour = $minuteValue % 60 === 0;

        $segments[] = [
            'minutes' => $minuteValue,
            'label' => ($isFullHour || $isImportantTime) ? sprintf('%02d:%02d', intdiv($minuteValue, 60), $minuteValue % 60) : null,
            'isFullHour' => $isFullHour,
            'isImportant' => $isImportantTime,
        ];
    }

    // PDF: hauteur de segment optimisee pour remplir toute la page A4 landscape
    // sans passer sur une 2eme page (44 segments * 12px = 528px, laisse de la place
    // pour header + legende + generation-info)
    $segmentHeight = $variant === 'pdf' ? 12 : 26;

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

        $gridRowStart = $relativeStart + 3;
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
    $gridTemplateRows = 'auto 15px repeat(' . $segmentCount . ', ' . $segmentHeight . 'px)';
    $pdfGridTemplateRows = '18px repeat(' . $segmentCount . ', ' . $segmentHeight . 'px)';

@endphp

@if($variant === 'web')
    @once
        <style>
            .timeline-grid {
                border: 1px solid #dbeafe;
                border-radius: 18px;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
                background: #ffffff;
                /* overflow visible assure par .egh-body parent pour laisser respirer les dropdowns */
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
                padding: 0 8px;
                font-size: 0.82rem;
                font-weight: 600;
                color: #1e293b;
                margin-top: 0.15em;  /* Descendre légèrement pour aligner avec les lignes */
            }
            .timeline-grid .timeline-hour-cell.full-hour {
                font-weight: 700;
                font-size: 0.88rem;
                color: #0f172a;
            }
            .timeline-grid .timeline-hour-cell.minute-marker {
                font-weight: 500;
                font-size: 0.75rem;
                color: #64748b;
                opacity: 0.85;
                background: #f8fafc;
                border-right: 1px solid #e2e8f0;
                margin-top: -0.15em;  /* Remonter pour aligner avec les lignes */
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
                gap: 6px;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
                justify-self: center;
                width: 95%;
            }
            .timeline-grid .timeline-session:hover {
                transform: translateY(-4px);
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.2);
            }
            .timeline-grid .timeline-session-type {
                font-size: 0.65rem;
                letter-spacing: 0.1em;
                font-weight: 600;
                opacity: 0.75;
                text-transform: uppercase;
            }
            .timeline-grid .timeline-session-subject {
                font-size: 1.15rem;
                font-weight: 800;
                line-height: 1.3;
                text-align: center;
                flex-grow: 1;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .timeline-grid .timeline-session-meta {
                font-size: 0.7rem;
                opacity: 0.85;
            }
            .timeline-grid .timeline-session-bottom {
                display: flex;
                gap: 8px;
                align-items: center;
                flex-wrap: wrap;
                font-size: 0.7rem;
                opacity: 0.85;
            }
            .timeline-grid .timeline-session-bottom > span:not(:last-child)::after {
                content: "•";
                margin-left: 8px;
                opacity: 0.6;
            }
            /* Kebab menu sur seance card — persistent desktop / tap-to-reveal touch */
            .timeline-grid .timeline-session-kebab {
                position: absolute;
                top: 6px;
                right: 6px;
                width: 26px;
                height: 26px;
                border-radius: 7px;
                background: rgba(255,255,255,0.18);
                color: #fff;
                border: 1px solid rgba(255,255,255,0.22);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 0.72rem;
                opacity: 0.6;
                transition: opacity 0.15s ease, background 0.15s ease;
                padding: 0;
                backdrop-filter: blur(6px);
            }
            .timeline-grid .timeline-session:hover .timeline-session-kebab {
                opacity: 1;
            }
            .timeline-grid .timeline-session-kebab:hover,
            .timeline-grid .timeline-session-kebab:focus {
                background: rgba(255,255,255,0.3);
                opacity: 1;
                outline: none;
            }
            /* Touch devices : kebab toujours visible (pas de hover possible) */
            @media (hover: none) {
                .timeline-grid .timeline-session-kebab { opacity: 1; }
            }

            .timeline-grid .timeline-session-dropdown {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                box-shadow: 0 12px 28px rgba(15,23,42,0.18);
                padding: .3rem;
                min-width: 180px;
                z-index: 20;
            }
            .timeline-grid .timeline-session-dropdown .dropdown-item {
                color: #1e293b;
                padding: .45rem .7rem;
                border-radius: 7px;
                font-size: .82rem;
                display: flex;
                align-items: center;
                gap: .5rem;
            }
            .timeline-grid .timeline-session-dropdown .dropdown-item:hover {
                background: #f1f5f9;
                color: #0453cb;
            }
            .timeline-grid .timeline-session-dropdown .dropdown-item i {
                width: 16px;
                text-align: center;
                color: #0453cb;
            }
            .timeline-grid .timeline-session-dropdown .dropdown-item.text-danger i { color: #dc2626; }
            .timeline-grid .timeline-session-dropdown .dropdown-item.text-danger:hover { background: rgba(220,38,38,.06); color: #b91c1c; }

            /* Empty cell "+" : discret par defaut, emphase au hover */
            .timeline-grid .timeline-slot-add {
                width: 26px;
                height: 26px;
                border-radius: 50%;
                border: 1.5px dashed #cbd5e1;
                color: #94a3b8;
                background: rgba(255,255,255,0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.7rem;
                opacity: 0.45;
                transition: all 0.2s ease;
                text-decoration: none;
            }
            .timeline-grid .timeline-day-background:hover ~ .timeline-slot-add,
            .timeline-grid .timeline-slot-add:hover {
                opacity: 1;
                border-color: #0453cb;
                background: #fff;
                color: #0453cb;
                box-shadow: 0 4px 12px rgba(4, 83, 203, 0.2);
            }
            .timeline-grid .timeline-slot-add:hover {
                transform: translate(-50%, -50%) scale(1.1);
            }
            /* Touch : toujours visible */
            @media (hover: none) {
                .timeline-grid .timeline-slot-add { opacity: 0.7; }
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
            <div class="timeline-hour-cell {{ ($segment['isFullHour'] ?? false) ? 'full-hour' : '' }} {{ ($segment['isImportant'] ?? false) && !($segment['isFullHour'] ?? false) ? 'minute-marker' : '' }}" style="grid-column: 1; grid-row: {{ $index + 3 }};">
                {{ $segment['label'] ?? '' }}
            </div>
        @endforeach

        @foreach($normalizedDays as $dayInfo)
            @php
                $daySlug = $dayInfo['slug'];
                $columnIndex = $dayIndexMap[$daySlug] ?? 2;
            @endphp
            <div class="timeline-day-background" style="grid-column: {{ $columnIndex }}; grid-row: 3 / span {{ $segmentCount }};"></div>

            @if($interactive && $emploiTemps)
                @foreach($segments as $index => $segment)
                    @php
                        $minuteValue = $segment['minutes'];
                        if ($minuteValue >= $maxMinutes) { continue; }
                        if ($minuteValue % 60 !== 0) { continue; }
                        $timeString = sprintf('%02d:%02d', intdiv($minuteValue, 60), $minuteValue % 60);
                        $dayNumber = is_numeric($dayInfo['key']) ? $dayInfo['key'] : ($dayToNumber[strtolower($dayInfo['key'])] ?? ($dayToNumber[$daySlug] ?? 1));
                        $rowIndex = $index + 3;
                        if (($occupiedGridRows[$daySlug][$rowIndex] ?? false)) {
                            continue;
                        }
                    @endphp
                    <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id, 'jour' => $dayNumber, 'heure_debut' => $timeString]) }}"
                       class="timeline-slot-add"
                       style="grid-column: {{ $columnIndex }}; grid-row: {{ $rowIndex }}; transform: translate(-50%, -8%); justify-self: center; align-self: center;"
                       title="Ajouter une séance">
                        <i class="fas fa-plus"></i>
                    </a>
                @endforeach
            @endif

            @foreach($timelineSessions[$daySlug] ?? [] as $session)
                <div class="timeline-session type-{{ $session['type'] }}"
                     data-seance-id="{{ $session['id'] }}"
                     data-seance-matiere="{{ $session['matiere'] }}"
                     style="position: relative; grid-column: {{ $columnIndex }}; grid-row: {{ $session['gridRowStart'] }} / {{ $session['gridRowEnd'] }}; background: {{ $session['background'] }}; color: {{ $session['textColor'] }}; justify-self: center; width: 95%; transform: translateY(12px);">
                    <div class="timeline-session-type">{{ $session['typeLabel'] }}</div>
                    <div class="timeline-session-subject">{{ $session['matiere'] }}</div>
                    <div class="timeline-session-bottom">
                        @if($session['enseignant'])
                            <span><i class="fas fa-user-tie me-1"></i>{{ $session['enseignant'] }}</span>
                        @endif
                        @if($session['salle'])
                            <span><i class="fas fa-door-open me-1"></i>{{ $session['salle'] }}</span>
                        @endif
                        @if($session['timeRange'])
                            <span><i class="fas fa-clock me-1"></i>{{ $session['timeRange'] }}</span>
                        @endif
                    </div>
                    @if($session['notes'])
                        <div class="timeline-session-meta">{{ Str::limit($session['notes'], 80) }}</div>
                    @endif

                    @if($interactive)
                        <div class="dropdown" style="position: absolute; top: 4px; right: 4px;">
                            <button type="button"
                                    class="timeline-session-kebab"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    aria-label="Actions séance {{ $session['matiere'] }}">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end timeline-session-dropdown">
                                @can('edit_timetables')
                                <li>
                                    <a class="dropdown-item" href="{{ route('esbtp.seances-cours.edit', $session['id']) }}">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                </li>
                                @endcan
                                @can('delete_timetables')
                                <li>
                                    <button type="button"
                                            class="dropdown-item text-danger"
                                            onclick="window.etsDeleteSeance({{ $session['id'] }}, @js($session['matiere']))">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
@else
    {{-- PDF: Use same CSS Grid structure as web timeline (without interactive elements) - COMPACT VERSION --}}
    <div class="timetable-timeline timetable-variant-{{ $variant }}" style="display: grid; grid-template-columns: 50px repeat({{ count($normalizedDays) }}, 1fr); grid-template-rows: {{ $pdfGridTemplateRows }}; gap: 0; border: 1px solid #e2e8f0; background: white; font-family: Arial, sans-serif;">

        {{-- Header: Time column + Day columns --}}
        <div style="grid-column: 1; grid-row: 1; background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.65rem; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
            Heure
        </div>
        @foreach($normalizedDays as $index => $dayInfo)
            <div style="grid-column: {{ $index + 2 }}; grid-row: 1; background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.65rem; border-bottom: 1px solid #e2e8f0; @if($index < count($normalizedDays) - 1) border-right: 1px solid #e2e8f0; @endif">
                {{ $dayInfo['label'] }}
            </div>
        @endforeach

        {{-- Time labels (only important ones for PDF) --}}
        @foreach($segments as $rowIndex => $segment)
            @if($rowIndex >= $segmentCount)
                @break
            @endif
            @if(($segment['isImportant'] ?? false) || ($segment['isFullHour'] ?? false))
                <div style="grid-column: 1; grid-row: {{ $rowIndex + 2 }}; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; @if($segment['isFullHour']) font-weight: 700; color: #0f172a; @else font-weight: 500; color: #64748b; @endif border-right: 1px solid #e2e8f0; transform: translateY(50%);">
                    {{ $segment['label'] }}
                </div>
            @endif
        @endforeach

        {{-- Grid lines (horizontal) --}}
        @foreach($segments as $rowIndex => $segment)
            @if($rowIndex >= $segmentCount)
                @break
            @endif
            @if(($segment['isImportant'] ?? false) || ($segment['isFullHour'] ?? false))
                @foreach($normalizedDays as $colIndex => $dayInfo)
                    <div style="grid-column: {{ $colIndex + 2 }}; grid-row: {{ $rowIndex + 2 }}; border-bottom: 1px solid {{ $segment['isFullHour'] ? '#cbd5e1' : '#f1f5f9' }}; @if($colIndex < count($normalizedDays) - 1) border-right: 1px solid #e2e8f0; @endif"></div>
                @endforeach
            @endif
        @endforeach

        {{-- Sessions (positioned with grid-row-start/end for proportional height) --}}
        @foreach($normalizedDays as $dayIndex => $dayInfo)
            @php $columnIndex = $dayIndex + 2; $daySlug = $dayInfo['slug']; @endphp
            @foreach($timelineSessions[$daySlug] ?? [] as $session)
                <div style="grid-column: {{ $columnIndex }}; grid-row: {{ $session['gridRowStart'] }} / {{ $session['gridRowEnd'] }}; background: {{ $session['background'] }}; color: {{ $session['textColor'] }}; margin: 0 2px; border-radius: 5px; padding: 4px; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.15);">
                    {{-- Type en haut (petit) --}}
                    <div style="font-size: 0.5rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; opacity: 0.8; text-align: center;">
                        {{ $session['typeLabel'] }}
                    </div>

                    {{-- Matière au centre (GRAND titre) --}}
                    <div style="font-size: 0.75rem; font-weight: 800; text-align: center; line-height: 1.12; flex-grow: 1; display: flex; align-items: center; justify-content: center; padding: 3px 0;">
                        {{ $session['matiere'] }}
                    </div>

                    {{-- Détails en bas (sur la même ligne avec séparateurs) --}}
                    <div style="font-size: 0.5rem; opacity: 0.85; text-align: center; line-height: 1.2; display: flex; flex-wrap: wrap; gap: 3px; align-items: center; justify-content: center;">
                        @if($session['enseignant'])
                            <span style="display: inline-flex; align-items: center; gap: 2px;">
                                <i class="fas fa-user-tie" style="font-size: 0.5rem;"></i>{{ $session['enseignant'] }}
                            </span>
                        @endif
                        @if($session['enseignant'] && ($session['salle'] || $session['timeRange']))
                            <span style="opacity: 0.6;">•</span>
                        @endif
                        @if($session['salle'])
                            <span style="display: inline-flex; align-items: center; gap: 2px;">
                                <i class="fas fa-door-open" style="font-size: 0.5rem;"></i>{{ $session['salle'] }}
                            </span>
                        @endif
                        @if($session['salle'] && $session['timeRange'])
                            <span style="opacity: 0.6;">•</span>
                        @endif
                        @if($session['timeRange'])
                            <span style="display: inline-flex; align-items: center; gap: 2px;">
                                <i class="fas fa-clock" style="font-size: 0.5rem;"></i>{{ $session['timeRange'] }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
@endif
