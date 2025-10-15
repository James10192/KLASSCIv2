@php
    $typeIcons = [
        'examen' => '<i class="fas fa-file-alt text-primary"></i>',
        'devoir' => '<i class="fas fa-pencil-alt text-success"></i>',
        'tp' => '<i class="fas fa-flask text-warning"></i>',
        'projet' => '<i class="fas fa-project-diagram text-info"></i>',
        'oral' => '<i class="fas fa-comments text-secondary"></i>',
        'controle' => '<i class="fas fa-tasks text-secondary"></i>',
    ];
    $typeIcon = $typeIcons[$evaluation->type] ?? '<i class="fas fa-clipboard text-muted"></i>';
@endphp

<tr data-evaluation-id="{{ $evaluation->id }}" class="position-relative">
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
                    <span class="badge bg-secondary">
                        <i class="fas fa-eye-slash me-1"></i>Brouillon interne
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
        <div class="d-flex flex-column">
            <span class="d-inline-flex align-items-center gap-2">
                <i class="far fa-calendar-alt text-muted"></i>
                {{ $evaluation->date_evaluation?->format('d/m/Y') ?? '—' }}
            </span>
            @if($evaluation->duree_minutes)
                <small class="text-muted">
                    <i class="far fa-clock me-1"></i>{{ $evaluation->duree_minutes }} min
                </small>
            @endif
        </div>
    </td>
    <td>
        <div class="d-flex align-items-center flex-wrap gap-2">
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

            <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="btn btn-sm btn-outline-primary" title="Gérer la saisie rapide">
                <i class="fas fa-pen-alt me-1"></i>
                {{ ($evaluation->notes_count ?? 0) > 0 ? 'Gérer (' . $evaluation->notes_count . ')' : 'Saisir' }}
            </a>
        </div>
    </td>
    <td>
        <div class="evaluation-actions-wrapper d-inline-flex align-items-center gap-2" data-evaluation-actions="{{ $evaluation->id }}">
            <div class="d-flex align-items-center flex-wrap gap-1 evaluation-actions-buttons">
                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-info" title="Voir les détails">
                    <i class="fas fa-eye"></i>
                </a>
                @if($evaluation->isEditable())
                    <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                        <i class="fas fa-edit"></i>
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
                        <i class="fas fa-undo"></i>
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
                        <i class="fas fa-play"></i>
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
                        <i class="fas fa-times"></i>
                    </button>
                @endif
                @if($evaluation->isDeletable())
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        data-evaluation-action="delete"
                        data-url="{{ route('esbtp.evaluations.destroy', $evaluation) }}"
                        data-method="DELETE"
                        title="Supprimer"
                    >
                        <i class="fas fa-trash-alt"></i>
                    </button>
                @endif
            </div>
            <div class="evaluation-actions-spinner" aria-hidden="true">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </td>
</tr>
