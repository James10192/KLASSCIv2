@php
    $typeIcons = [
        'examen' => '<i class="fas fa-file-circle-check text-primary"></i>',
        'devoir' => '<i class="fas fa-pen-ruler text-success"></i>',
        'tp' => '<i class="fas fa-flask-vial text-warning"></i>',
        'projet' => '<i class="fas fa-diagram-project text-info"></i>',
        'oral' => '<i class="fas fa-microphone-lines text-secondary"></i>',
        'controle' => '<i class="fas fa-list-check text-secondary"></i>',
    ];
    $typeIcon = $typeIcons[$evaluation->type] ?? '<i class="fas fa-clipboard text-muted"></i>';
@endphp

<tr data-evaluation-id="{{ $evaluation->id }}"
    data-is-published="{{ $evaluation->is_published ? '1' : '0' }}"
    data-notes-published="{{ $evaluation->notes_published ? '1' : '0' }}"
    data-status="{{ $evaluation->status }}"
    data-can-publish-notes="{{ $evaluation->canPublishNotes() ? '1' : '0' }}"
    class="position-relative">
    <td>
        <div class="form-check">
            <input class="form-check-input evaluation-checkbox" type="checkbox" id="evaluation-{{ $evaluation->id }}" value="{{ $evaluation->id }}">
            <label class="form-check-label" for="evaluation-{{ $evaluation->id }}"></label>
        </div>
    </td>
    <td>
        <div class="d-flex flex-column">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="fw-semibold text-decoration-none color-primary">
                    {{ $evaluation->titre }}
                </a>
                <span class="{{ $evaluation->status_badge_class }}" data-status-badge>
                    {{ $evaluation->status_label }}
                </span>
                @if(!$evaluation->is_published)
                    <span class="badge bg-secondary" title="Invisible aux étudiants tant que non publiée">
                        <i class="fas fa-eye-slash me-1"></i>Non publiée
                    </span>
                @endif
            </div>
            <small class="text-muted">
                Créée par {{ optional($evaluation->createdBy)->name ?? '—' }}
            </small>
        </div>
    </td>
    <td>
        <span class="badge bg-light text-dark">
            <i class="fas fa-chalkboard-teacher me-1"></i>{{ $evaluation->classe->name ?? '—' }}
        </span>
    </td>
    <td>
        <span class="d-inline-flex align-items-center gap-2">
            <i class="fas fa-book text-muted"></i>
            {{ $evaluation->matiere->name ?? '—' }}
        </span>
    </td>
    <td>
        <span class="d-inline-flex align-items-center gap-2">
            {!! $typeIcon !!}
            <span>{{ ucfirst($evaluation->type) }}</span>
        </span>
    </td>
    <td>
        @php
            $evaluationDateFuture = $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture();
            $durationMinutes = (int) ($evaluation->duree_minutes ?? 0);
            $durationLabel = null;
            if ($durationMinutes > 0) {
                $hours = intdiv($durationMinutes, 60);
                $minutes = $durationMinutes % 60;
                if ($hours > 0) {
                    $durationLabel = $minutes > 0
                        ? $hours . 'H' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT)
                        : $hours . 'H';
                } else {
                    $durationLabel = $minutes . ' mn';
                }
            }
        @endphp
        <div class="d-flex flex-column gap-1">
            @if($durationLabel)
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="far fa-clock text-muted"></i>
                    <span>{{ $durationLabel }} ({{ $durationMinutes }} mn)</span>
                </span>
            @endif
            <span class="text-muted">
                {{ $evaluation->date_evaluation?->format('d/m/Y') ?? '—' }}
            </span>
        </div>
    </td>
    <td>
        <div class="d-flex align-items-center flex-wrap gap-2">
            @can('evaluations.edit')
            <button
                type="button"
                class="btn btn-sm {{ $evaluation->notes_published ? 'btn-success' : 'btn-outline-secondary' }}"
                data-evaluation-action="toggle-notes"
                data-url="{{ route('esbtp.evaluations.toggle-notes-published', $evaluation) }}"
                data-method="PATCH"
                {{ !$evaluation->canPublishNotes() && !$evaluation->notes_published ? 'disabled' : '' }}
            >
                @if($evaluation->notes_published)
                    <i class="fas fa-check-circle me-1"></i>Notes publiées
                @else
                    <i class="fas fa-eye-slash me-1"></i>Notes non publiées
                @endif
            </button>
            @endcan

            @php
                $notesDisabled = !$evaluation->is_published || $evaluationDateFuture;
                $notesDisabledReason = null;
                if (!$evaluation->is_published) {
                    $notesDisabledReason = "Publiez l'évaluation pour saisir les notes";
                } elseif ($evaluationDateFuture) {
                    $notesDisabledReason = "La saisie est disponible après la date d'évaluation";
                }
            @endphp
            @can('notes.create')
            <a href="{{ $notesDisabled ? '#' : route('esbtp.notes.saisie-rapide', $evaluation) }}"
               class="btn btn-sm btn-outline-primary {{ $notesDisabled ? 'disabled' : '' }}"
               title="{{ $notesDisabled ? $notesDisabledReason : 'Gérer la saisie rapide' }}"
               aria-disabled="{{ $notesDisabled ? 'true' : 'false' }}"
               tabindex="{{ $notesDisabled ? '-1' : '0' }}">
                <i class="fas fa-pen-to-square me-1"></i>
                {{ ($evaluation->notes_count ?? 0) > 0 ? 'Gérer (' . $evaluation->notes_count . ')' : 'Saisir' }}
            </a>
            @if($notesDisabled && $notesDisabledReason)
                <small class="text-muted d-block">{{ $notesDisabledReason }}</small>
            @endif
            @endcan
        </div>
    </td>
    <td>
        <div class="evaluation-actions-wrapper d-inline-flex align-items-center gap-2" data-evaluation-actions="{{ $evaluation->id }}">
            <div class="d-flex align-items-center flex-wrap gap-1 evaluation-actions-buttons">
                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-info" title="Voir les détails">
                    <i class="fas fa-eye"></i>
                </a>
                @can('evaluations.edit')
                    @if($evaluation->isEditable())
                        <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                            <i class="fas fa-pen-to-square"></i>
                        </a>
                    @endif
                    @if($evaluation->status === \App\Models\ESBTPEvaluation::STATUS_CANCELLED)
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success"
                            data-evaluation-action="restore"
                            data-url="{{ route('esbtp.evaluations.restore', $evaluation) }}"
                            data-method="PATCH"
                            title="Réactiver l'évaluation"
                        >
                            <i class="fas fa-arrow-rotate-left"></i>
                        </button>
                    @elseif(!$evaluation->is_published)
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success"
                            data-evaluation-action="restore"
                            data-url="{{ route('esbtp.evaluations.restore', $evaluation) }}"
                            data-method="PATCH"
                            title="Activer l'évaluation"
                        >
                            <i class="fas fa-circle-play"></i>
                        </button>
                    @else
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            data-evaluation-action="cancel"
                            data-url="{{ route('esbtp.evaluations.cancel', $evaluation) }}"
                            data-method="PATCH"
                            title="Annuler l'évaluation"
                        >
                            <i class="fas fa-circle-xmark"></i>
                        </button>
                    @endif
                @endcan
                @canany(['evaluations.edit', 'admin.access'])
                    @if($evaluation->isDeletable())
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            data-evaluation-action="delete"
                            data-url="{{ route('esbtp.evaluations.destroy', $evaluation) }}"
                            data-method="DELETE"
                            title="Supprimer"
                        >
                            <i class="fas fa-trash-can"></i>
                        </button>
                    @endif
                @endcanany
            </div>
            <div class="evaluation-actions-spinner" aria-hidden="true">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </td>
</tr>
