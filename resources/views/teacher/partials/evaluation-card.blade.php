@php
    $typeIcons = [
        'examen' => 'fa-file-circle-check',
        'devoir' => 'fa-pen-ruler',
        'tp' => 'fa-flask-vial',
        'projet' => 'fa-diagram-project',
        'oral' => 'fa-microphone-lines',
        'controle' => 'fa-list-check',
        'rattrapage' => 'fa-rotate-left',
    ];
    $typeIcon = $typeIcons[$evaluation->type] ?? 'fa-clipboard-check';
    $canOpenNotesModal = $evaluation->is_published
        && (! $evaluation->date_evaluation || $evaluation->date_evaluation->isPast() || $evaluation->date_evaluation->isToday());
    $notesDisabledReason = null;
    if (! $evaluation->is_published) {
        $notesDisabledReason = "Publiez l'évaluation pour saisir les notes";
    } elseif ($evaluation->date_evaluation && $evaluation->date_evaluation->isFuture()) {
        $notesDisabledReason = "La saisie est disponible après la date d'évaluation";
    }
@endphp

<article class="tg-eval" data-evaluation-id="{{ $evaluation->id }}">
    <header class="tg-eval-top">
        <span class="tg-eval-type tg-eval-type--{{ $evaluation->type }}">
            <i class="fa-solid {{ $typeIcon }}"></i>
            <span>{{ ucfirst($evaluation->type) }}</span>
        </span>
        <span class="tg-eval-date">
            <i class="fa-regular fa-calendar"></i>
            {{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format('d/m/Y') : '—' }}
        </span>
    </header>

    <h3 class="tg-eval-title">{{ $evaluation->titre }}</h3>

    <ul class="tg-eval-meta">
        <li><i class="fa-solid fa-people-group"></i> {{ $evaluation->classe->name ?? '—' }}</li>
        <li><i class="fa-solid fa-book-open"></i> {{ $evaluation->matiere->name ?? '—' }}</li>
        <li><i class="fa-solid fa-scale-balanced"></i> /{{ $evaluation->bareme ?? '20' }} pts</li>
    </ul>

    <footer class="tg-eval-foot">
        <div class="tg-eval-status">
            @if($evaluation->is_published)
                <span class="tg-pill tg-pill--ok"><i class="fa-solid fa-circle-check"></i> Publiée</span>
            @else
                <span class="tg-pill tg-pill--draft"><i class="fa-solid fa-circle-minus"></i> Brouillon</span>
            @endif
            <span class="tg-pill tg-pill--count">
                <i class="fa-solid fa-list-check"></i>
                {{ $evaluation->notes_count ?? 0 }} note{{ ($evaluation->notes_count ?? 0) > 1 ? 's' : '' }}
            </span>
        </div>

        <div class="tg-eval-actions">
            <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="tg-eval-btn tg-eval-btn--ghost">
                <i class="fa-regular fa-eye"></i> Voir
            </a>
            @if($canOpenNotesModal)
                <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="tg-eval-btn tg-eval-btn--primary">
                    <i class="fa-solid fa-pen-to-square"></i> Saisir
                </a>
            @else
                <button type="button" class="tg-eval-btn tg-eval-btn--primary is-disabled" disabled title="{{ $notesDisabledReason }}">
                    <i class="fa-solid fa-pen-to-square"></i> Saisir
                </button>
            @endif
        </div>

        @if(!$canOpenNotesModal && $notesDisabledReason)
            <div class="tg-eval-helper">
                <i class="fa-solid fa-circle-info"></i> {{ $notesDisabledReason }}
            </div>
        @endif
    </footer>
</article>
