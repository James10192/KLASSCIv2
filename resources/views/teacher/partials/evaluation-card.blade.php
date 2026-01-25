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
@endphp

<div class="evaluation-card" data-evaluation-id="{{ $evaluation->id }}">
    <div class="evaluation-header">
        <div class="evaluation-type">
            <span class="type-badge type-{{ $evaluation->type }}">
                <i class="fas {{ $typeIcon }}"></i>
                {{ ucfirst($evaluation->type) }}
            </span>
        </div>
        <div class="evaluation-date">
            <i class="fas fa-calendar-day"></i>
            {{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format('d/m/Y') : 'Non définie' }}
        </div>
    </div>

    <div class="evaluation-content">
        <h4 class="evaluation-title">{{ $evaluation->titre }}</h4>
        <div class="evaluation-meta">
            <span class="meta-pill">
                <i class="fas fa-people-group"></i>
                {{ $evaluation->classe->name ?? 'Non définie' }}
            </span>
            <span class="meta-pill">
                <i class="fas fa-book-open"></i>
                {{ $evaluation->matiere->name ?? 'Non définie' }}
            </span>
            <span class="meta-pill">
                <i class="fas fa-scale-balanced"></i>
                {{ $evaluation->bareme ?? '20' }} pts
            </span>
        </div>
    </div>

    <div class="evaluation-footer">
        <div class="evaluation-status">
            @if($evaluation->is_published)
                <span class="status-badge published">
                    <i class="fas fa-circle-check"></i> Publiée
                </span>
            @else
                <span class="status-badge draft">
                    <i class="fas fa-circle-minus"></i> Brouillon
                </span>
            @endif
            <span class="status-badge neutral">
                <i class="fas fa-list-check"></i>
                {{ $evaluation->notes_count ?? 0 }} notes
            </span>
        </div>

        <div class="evaluation-actions">
            <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn-action primary">
                <i class="fas fa-eye"></i> Voir
            </a>
            <button type="button"
                    class="btn-action success {{ $canOpenNotesModal ? '' : 'disabled' }}"
                    data-action="open-notes-modal"
                    data-evaluation-id="{{ $evaluation->id }}"
                    {{ $canOpenNotesModal ? '' : 'disabled' }}>
                <i class="fas fa-pen-to-square"></i> Notes
            </button>
        </div>
    </div>
</div>
