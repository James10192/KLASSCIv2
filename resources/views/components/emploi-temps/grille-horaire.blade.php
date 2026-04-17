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

    // Couleurs semantiques par type (rule premium-redesign : types de seances ont un sens fonctionnel
    // que l'oeil doit capter en < 1s). Couleurs choisies pour bonne lisibilite ecran + contraste
    // suffisant pour impression noir & blanc (tons moyens bien differencies en niveaux de gris).
    $sessionTypeColors = [
        ESBTPSeanceCours::TYPE_COURSE   => ['bg' => '#0453cb', 'text' => '#ffffff'], // Bleu KLASSCI (primary)
        ESBTPSeanceCours::TYPE_HOMEWORK => ['bg' => '#10b981', 'text' => '#ffffff'], // Vert success
        ESBTPSeanceCours::TYPE_BREAK    => ['bg' => '#f59e0b', 'text' => '#1f2937'], // Orange warning
        ESBTPSeanceCours::TYPE_LUNCH    => ['bg' => '#0ea5e9', 'text' => '#ffffff'], // Cyan neutre
        'default'                        => ['bg' => '#5e91de', 'text' => '#ffffff'],
    ];

    $sessionTypeIcons = [
        ESBTPSeanceCours::TYPE_COURSE   => 'fa-chalkboard',
        ESBTPSeanceCours::TYPE_HOMEWORK => 'fa-pencil-alt',
        ESBTPSeanceCours::TYPE_BREAK    => 'fa-coffee',
        ESBTPSeanceCours::TYPE_LUNCH    => 'fa-utensils',
    ];

    foreach ($seances as $seance) {
        $type = $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
        if (!isset($sessionTypeColors[$type]) && !empty($seance->color)) {
            $sessionTypeColors[$type] = ['bg' => $seance->color, 'text' => '#ffffff'];
        }
    }
@endphp

@push('styles')
<style>
    .egh-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        overflow: visible;
        margin-bottom: 1rem;
    }
    .egh-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .egh-header-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        color: #0f172a;
        font-weight: 700;
        font-size: .92rem;
    }
    .egh-header-title i {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
    }
    .egh-legend {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .egh-legend-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .65rem;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 99px;
        font-size: .72rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all .15s ease;
        user-select: none;
    }
    .egh-legend-chip:hover {
        border-color: #0453cb;
        color: #0453cb;
    }
    .egh-legend-chip.is-active {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    .egh-legend-chip.is-inactive {
        opacity: 0.4;
        text-decoration: line-through;
        text-decoration-color: #94a3b8;
        text-decoration-thickness: 1.5px;
    }
    .egh-legend-chip.is-inactive .egh-legend-dot {
        filter: grayscale(1);
    }
    .egh-legend-chip i { font-size: .7rem; }
    .egh-legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .egh-body { padding: 1rem 1.25rem; }
    .egh-body .timeline-grid {
        overflow: visible !important;
        max-height: none !important;
    }
</style>
@endpush

<div class="egh-card"
     x-data="{ activeTypes: { course: true, homework: true, break: true, lunch: true } }"
     x-effect="Object.entries(activeTypes).forEach(([type, isActive]) => {
         $el.querySelectorAll('.timeline-session.type-' + type).forEach(el => {
             el.style.display = isActive ? '' : 'none';
         });
     })">
    <div class="egh-header">
        <div class="egh-header-title">
            <i class="fas fa-calendar-week"></i>
            Grille horaire
        </div>
        <div class="egh-legend" role="group" aria-label="Filtrer les types de séance">
            @foreach($sessionTypeLabels as $type => $label)
                @php $typeColor = $sessionTypeColors[$type]['bg'] ?? '#5e91de'; @endphp
                <button type="button"
                        class="egh-legend-chip is-active"
                        :class="{ 'is-active': activeTypes.{{ $type }}, 'is-inactive': !activeTypes.{{ $type }} }"
                        @click="activeTypes.{{ $type }} = !activeTypes.{{ $type }}"
                        :aria-pressed="activeTypes.{{ $type }}">
                    <span class="egh-legend-dot" style="background: {{ $typeColor }};"></span>
                    <i class="fas {{ $sessionTypeIcons[$type] ?? 'fa-circle' }}"></i>
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>
    <div class="egh-body">
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
