@props([
    'seances' => collect(),
    'timeSlots' => [],
    'days' => [],
    'dayLabels' => [],
    'sessionStyles' => [],
    'sessionLabels' => [],
    'variant' => 'web',
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

    $timeToMinutes = function ($timeValue) {
        if ($timeValue instanceof Carbon) {
            return ((int) $timeValue->format('H')) * 60 + (int) $timeValue->format('i');
        }

        if (is_string($timeValue) && strlen($timeValue) >= 4) {
            [$hour, $minute] = array_pad(explode(':', substr($timeValue, 0, 5)), 2, 0);
            if (is_numeric($hour) && is_numeric($minute)) {
                return ((int) $hour) * 60 + (int) $minute;
            }
        }

        return 0;
    };

    $defaultTimeSlots = [];
    for ($hour = 8; $hour < 18; $hour++) {
        $defaultTimeSlots[] = sprintf('%02d:00', $hour);
    }

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

    $normalizedSlots = empty($timeSlots) ? $defaultTimeSlots : array_values($timeSlots);
    $normalizedSlots = array_values(array_unique($normalizedSlots));
    sort($normalizedSlots);

    $intervalMinutes = 60;
    if (count($normalizedSlots) > 1) {
        $intervalMinutes = max(1, $timeToMinutes($normalizedSlots[1]) - $timeToMinutes($normalizedSlots[0]));
    }
    $firstSlotMinutes = $timeToMinutes($normalizedSlots[0] ?? '08:00');

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

    $occupiedCells = [];
    $cellsCovered = [];
    $seanceLookup = [];
    foreach ($normalizedDays as $dayInfo) {
        foreach ($normalizedSlots as $slotIndex => $slot) {
            $occupiedCells[$dayInfo['slug']][$slotIndex] = false;
            $cellsCovered[$dayInfo['slug']][$slotIndex] = false;
        }
    }

    if ($seances && $seances->count()) {
        $seancesParJour = [];
        foreach ($seances as $seance) {
            $jourNumeric = $seance->jour;
            $jourSlug = isset($jourMapping[$jourNumeric]) ? $jourMapping[$jourNumeric] : null;
            if (!$jourSlug || !isset($occupiedCells[$jourSlug])) {
                continue;
            }
            $seancesParJour[$jourSlug][] = $seance;
        }

        foreach ($seancesParJour as $jourSlug => $listeSeances) {
            usort($listeSeances, function ($a, $b) use ($timeToMinutes, $normalizeTimeString) {
                $startA = $timeToMinutes($normalizeTimeString($a->heure_debut ?? null));
                $startB = $timeToMinutes($normalizeTimeString($b->heure_debut ?? null));
                return $startA <=> $startB;
            });

            foreach ($listeSeances as $index => $seance) {
                $heureDebut = $normalizeTimeString($seance->heure_debut ?? null);
                $heureFin = $normalizeTimeString($seance->heure_fin ?? null);

                if (!$heureDebut || !$heureFin) {
                    continue;
                }

                $startMinutes = $timeToMinutes($heureDebut);
                $endMinutes = $timeToMinutes($heureFin);

                if ($endMinutes <= $startMinutes) {
                    $endMinutes = $startMinutes + $intervalMinutes;
                }

                $startSlotIndex = (int) floor(($startMinutes - $firstSlotMinutes) / $intervalMinutes);
                $startSlotIndex = max(0, min($startSlotIndex, count($normalizedSlots) - 1));

                $endSlotIndex = (int) ceil(($endMinutes - $firstSlotMinutes) / $intervalMinutes) - 1;
                $endSlotIndex = max($endSlotIndex, $startSlotIndex);
                $endSlotIndex = min($endSlotIndex, count($normalizedSlots) - 1);

                if (isset($listeSeances[$index + 1])) {
                    $nextSeance = $listeSeances[$index + 1];
                    $nextStartString = $normalizeTimeString($nextSeance->heure_debut ?? null);
                    if ($nextStartString) {
                        $nextStartMinutes = $timeToMinutes($nextStartString);
                        $nextStartSlotIndex = (int) floor(($nextStartMinutes - $firstSlotMinutes) / $intervalMinutes);
                        $nextStartSlotIndex = max(0, min($nextStartSlotIndex, count($normalizedSlots) - 1));

                        if ($nextStartSlotIndex <= $endSlotIndex && $nextStartSlotIndex >= $startSlotIndex) {
                            $adjustedEnd = max($startSlotIndex, $nextStartSlotIndex - 1);
                            if ($adjustedEnd < $startSlotIndex) {
                                $adjustedEnd = $startSlotIndex;
                            }
                            $endSlotIndex = $adjustedEnd;
                        }
                    }
                }

                $rowspan = max(1, $endSlotIndex - $startSlotIndex + 1);

                $seanceLookup[$jourSlug][$startSlotIndex] = [
                    'seance' => $seance,
                    'rowspan' => $rowspan,
                    'startSlotIndex' => $startSlotIndex,
                    'endSlotIndex' => $endSlotIndex,
                ];

                for ($i = $startSlotIndex; $i <= $endSlotIndex; $i++) {
                    $occupiedCells[$jourSlug][$i] = true;
                    if ($i !== $startSlotIndex) {
                        $cellsCovered[$jourSlug][$i] = true;
                    }
                }
            }
        }
    }

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

    $swatchMap = [];
    if ($seances && $seances->count()) {
        foreach ($seances as $seance) {
            $type = $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
            if (!isset($swatchMap[$type])) {
                $swatchMap[$type] = $seance->color ?: $getSessionStyle($type)['bg'];
            }
        }
    }
    foreach ($labelMap as $type => $_) {
        if (!isset($swatchMap[$type])) {
            $swatch = $getSessionStyle($type);
            $swatchMap[$type] = $swatch['bg'];
        }
    }
@endphp

<div class="timetable-wrapper timetable-variant-{{ $variant }}">
    <table class="timetable-grid">
        <thead>
            <tr>
                <th class="timetable-time-header">Heure</th>
                @foreach($normalizedDays as $dayInfo)
                    <th class="timetable-day-header">{{ $dayInfo['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($normalizedSlots as $slotIndex => $timeSlot)
                <tr>
                    <td class="timetable-time-cell">{{ $timeSlot }}</td>
                    @foreach($normalizedDays as $dayInfo)
                        @php
                            $daySlug = $dayInfo['slug'];
                            $cellOccupied = $occupiedCells[$daySlug][$slotIndex] ?? false;
                            $cellCovered = $cellsCovered[$daySlug][$slotIndex] ?? false;
                            $seanceData = $seanceLookup[$daySlug][$slotIndex] ?? null;
                        @endphp

                        @if($seanceData && $cellOccupied)
                            @php
                                $seanceToDisplay = $seanceData['seance'];
                                $rowspan = $seanceData['rowspan'] ?? 1;

                                $type = $seanceToDisplay->type ?? ESBTPSeanceCours::TYPE_COURSE;
                                $style = $getSessionStyle($type);
                                $matiere = $seanceToDisplay->matiere->name ?? 'Matière';
                                $enseignant = $seanceToDisplay->enseignant_nom ?? optional(optional($seanceToDisplay->teacher)->user)->name;
                                $salle = $seanceToDisplay->salle;
                                $timeRange = '';
                                if ($seanceToDisplay->heure_debut && $seanceToDisplay->heure_fin) {
                                    $startHour = $seanceToDisplay->heure_debut instanceof Carbon
                                        ? $seanceToDisplay->heure_debut->format('H:i')
                                        : Carbon::parse($seanceToDisplay->heure_debut)->format('H:i');
                                    $endHour = $seanceToDisplay->heure_fin instanceof Carbon
                                        ? $seanceToDisplay->heure_fin->format('H:i')
                                        : Carbon::parse($seanceToDisplay->heure_fin)->format('H:i');
                                    $timeRange = $startHour . ' - ' . $endHour;
                                }
                                $notes = $seanceToDisplay->description;
                                $typeLabel = strtoupper($labelMap[$type] ?? 'Séance');
                                $backgroundColor = $seanceToDisplay->color ?: $style['bg'];
                                $textColor = $computeTextColor($backgroundColor, $style['text']);
                            @endphp
                            <td class="timetable-session-cell" rowspan="{{ $rowspan }}">
                                <div class="tt-session type-{{ $type }}" style="background: {{ $backgroundColor }}; color: {{ $textColor }};">
                                    <div class="tt-session-type" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.85;">
                                        {{ $typeLabel }}
                                    </div>
                                    <div class="tt-session-subject">{{ $matiere }}</div>
                                    @if($enseignant)
                                        <div class="tt-session-teacher"><i class="fas fa-user-tie"></i> {{ $enseignant }}</div>
                                    @endif
                                    @if($salle)
                                        <div class="tt-session-room"><i class="fas fa-door-open"></i> {{ $salle }}</div>
                                    @endif
                                    @if($timeRange)
                                        <div class="tt-session-time"><i class="fas fa-clock"></i> {{ $timeRange }}</div>
                                    @endif
                                    @if($notes)
                                        <div class="tt-session-notes">{{ Str::limit($notes, 80) }}</div>
                                    @endif
                                </div>
                            </td>
                        @elseif($cellCovered)
                            @continue
                        @else
                            <td class="timetable-empty-cell"></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
