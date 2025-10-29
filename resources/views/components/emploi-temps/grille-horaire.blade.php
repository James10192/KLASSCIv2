@props([
    'seances' => collect(),
    'emploiTemps' => null,
    'timeSlots' => [],
    'days' => [],
])

@php
    use App\Models\ESBTPSeanceCours;

    $sessionTypeLabels = [
        ESBTPSeanceCours::TYPE_COURSE => 'Cours',
        ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
        ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
        ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
    ];

    $sessionTypeColors = [
        ESBTPSeanceCours::TYPE_COURSE => ['bg' => '#0453cb', 'text' => '#ffffff'],
        ESBTPSeanceCours::TYPE_HOMEWORK => ['bg' => '#3ba54f', 'text' => '#ffffff'],
        ESBTPSeanceCours::TYPE_BREAK => ['bg' => '#f59e0b', 'text' => '#1f2937'],
        ESBTPSeanceCours::TYPE_LUNCH => ['bg' => '#0ea5e9', 'text' => '#ffffff'],
        'default' => ['bg' => '#5e91de', 'text' => '#ffffff'],
    ];

    foreach ($seances as $seance) {
        $type = $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
        if (!isset($sessionTypeColors[$type]) && !empty($seance->color)) {
            $sessionTypeColors[$type] = ['bg' => $seance->color, 'text' => '#ffffff'];
        }
    }
@endphp

<div class="main-card mb-4">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-calendar-week"></i>
            Grille horaire
        </div>
        <div class="main-card-subtitle">Visualisation dynamique de la semaine en cours</div>
    </div>
    <div class="main-card-body">
        <div class="mb-4">
            <h6 class="mb-3"><i class="fas fa-palette me-2"></i>Légende des formats :</h6>
            <div class="d-flex flex-wrap gap-3">
                @foreach($sessionTypeLabels as $type => $label)
                    @php
                        $swatch = $sessionTypeColors[$type] ?? $sessionTypeColors['default'];
                    @endphp
                    <div class="legend-item d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill" style="background: rgba(15,23,42,0.05);">
                        <span class="legend-color" style="display:inline-block;width:16px;height:16px;border-radius:50%;background: {{ $swatch['bg'] }};"></span>
                        <small class="fw-semibold text-uppercase" style="letter-spacing: 0.05em;">{{ $label }}</small>
                    </div>
                @endforeach
            </div>
        </div>

        @include('esbtp.emploi-temps.partials.timetable-grid', [
            'seances' => $seances,
            'timeSlots' => $timeSlots,
            'days' => $days,
            'sessionStyles' => $sessionTypeColors,
            'sessionLabels' => $sessionTypeLabels,
            'variant' => 'web',
            'emploiTemps' => $emploiTemps,
            'interactive' => true,
        ])
    </div>
</div>
