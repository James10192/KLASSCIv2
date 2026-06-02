@php
    $typeIcons = [
        'examen'   => 'fa-file-circle-check',
        'devoir'   => 'fa-pen-ruler',
        'tp'       => 'fa-flask-vial',
        'projet'   => 'fa-diagram-project',
        'oral'     => 'fa-microphone-lines',
        'controle' => 'fa-list-check',
    ];
    $typeIcon = $typeIcons[$evaluation->type] ?? 'fa-clipboard';

    $evaluationDateFuture = $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture();
    $durationMinutes = (int) ($evaluation->duree_minutes ?? 0);
    $durationLabel = null;
    if ($durationMinutes > 0) {
        $hours = intdiv($durationMinutes, 60);
        $minutes = $durationMinutes % 60;
        if ($hours > 0) {
            $durationLabel = $minutes > 0
                ? $hours.'H'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT)
                : $hours.'H';
        } else {
            $durationLabel = $minutes.' mn';
        }
    }

    $notesDisabled = !$evaluation->is_published || $evaluationDateFuture;
    $notesDisabledReason = null;
    if (!$evaluation->is_published) {
        $notesDisabledReason = "Publiez l'évaluation pour saisir les notes";
    } elseif ($evaluationDateFuture) {
        $notesDisabledReason = "La saisie est disponible après la date d'évaluation";
    }
@endphp

<tr data-evaluation-id="{{ $evaluation->id }}"
    data-is-published="{{ $evaluation->is_published ? '1' : '0' }}"
    data-notes-published="{{ $evaluation->notes_published ? '1' : '0' }}"
    data-status="{{ $evaluation->status }}"
    data-can-publish-notes="{{ $evaluation->canPublishNotes() ? '1' : '0' }}"
    data-show-url="{{ route('esbtp.evaluations.show', $evaluation) }}"
    class="position-relative">

    {{-- Checkbox --}}
    <td class="ev-col-check" onclick="event.stopPropagation();">
        <input class="ev-check evaluation-checkbox" type="checkbox" id="evaluation-{{ $evaluation->id }}" value="{{ $evaluation->id }}">
    </td>

    {{-- Titre + statut --}}
    <td>
        <div class="ev-titre-cell">
            <div class="ev-titre-row">
                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="ev-titre-link" onclick="event.stopPropagation();">
                    {{ $evaluation->titre }}
                </a>
                <span class="{{ $evaluation->status_badge_class }}" data-status-badge>
                    {{ $evaluation->status_label }}
                </span>
                @if(!$evaluation->is_published)
                    <span class="badge bg-secondary" title="Invisible aux étudiants tant que non publiée">
                        <i class="fas fa-eye-slash"></i>Non publiée
                    </span>
                @endif
            </div>
            <span class="ev-titre-meta">
                Créée par {{ optional($evaluation->createdBy)->name ?? '—' }}
            </span>
        </div>
    </td>

    {{-- Classe --}}
    <td>
        <span class="ev-chip">
            <i class="fas fa-chalkboard"></i>{{ $evaluation->classe->name ?? '—' }}
        </span>
    </td>

    {{-- Matière --}}
    <td>
        <span class="ev-chip ev-chip--neutral">
            <i class="fas fa-book"></i>{{ $evaluation->matiere->name ?? '—' }}
        </span>
    </td>

    {{-- Type --}}
    <td>
        <span class="ev-chip ev-chip--neutral">
            <i class="fas {{ $typeIcon }}"></i>{{ ucfirst($evaluation->type) }}
        </span>
    </td>

    {{-- Date + durée --}}
    <td>
        <div class="ev-date-cell">
            <span class="ev-date-main">{{ $evaluation->date_evaluation?->format('d/m/Y') ?? '—' }}</span>
            @if($durationLabel)
                <span class="ev-date-sub">
                    <i class="far fa-clock"></i>{{ $durationLabel }} ({{ $durationMinutes }} mn)
                </span>
            @endif
        </div>
    </td>

    {{-- Notes (publish toggle + saisie) --}}
    <td onclick="event.stopPropagation();">
        <div class="d-flex flex-column gap-1">
            @can('evaluations.edit')
            <button
                type="button"
                class="ev-notes-btn {{ $evaluation->notes_published ? 'ev-notes-btn--published' : '' }}"
                data-evaluation-action="toggle-notes"
                data-url="{{ route('esbtp.evaluations.toggle-notes-published', $evaluation) }}"
                data-method="PATCH"
                {{ !$evaluation->canPublishNotes() && !$evaluation->notes_published ? 'disabled' : '' }}>
                @if($evaluation->notes_published)
                    <i class="fas fa-check-circle"></i>Publiées
                @else
                    <i class="fas fa-eye-slash"></i>Non publiées
                @endif
            </button>
            @endcan

            @can('notes.create')
            <a href="{{ $notesDisabled ? '#' : route('esbtp.notes.saisie-rapide', $evaluation) }}"
               class="ev-notes-btn {{ $notesDisabled ? 'disabled' : '' }}"
               title="{{ $notesDisabled ? $notesDisabledReason : 'Gérer la saisie rapide' }}"
               aria-disabled="{{ $notesDisabled ? 'true' : 'false' }}"
               tabindex="{{ $notesDisabled ? '-1' : '0' }}">
                <i class="fas fa-pen-to-square"></i>
                {{ ($evaluation->notes_count ?? 0) > 0 ? 'Gérer ('.$evaluation->notes_count.')' : 'Saisir' }}
            </a>
            @endcan
        </div>
    </td>

    {{-- Actions (View / Edit / Restore-Cancel / Delete) --}}
    <td class="ev-col-actions" onclick="event.stopPropagation();">
        <div class="evaluation-actions-wrapper d-inline-flex align-items-center gap-1" data-evaluation-actions="{{ $evaluation->id }}">
            <div class="d-flex align-items-center gap-1 evaluation-actions-buttons">
                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="ev-action-btn" title="Voir les détails">
                    <i class="fas fa-eye"></i>
                </a>
                @can('evaluations.edit')
                    @if($evaluation->isEditable())
                        <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="ev-action-btn ev-action-btn--warning" title="Modifier">
                            <i class="fas fa-pen-to-square"></i>
                        </a>
                    @endif
                    @if($evaluation->status === \App\Models\ESBTPEvaluation::STATUS_CANCELLED)
                        <button type="button"
                                class="ev-action-btn ev-action-btn--success"
                                data-evaluation-action="restore"
                                data-url="{{ route('esbtp.evaluations.restore', $evaluation) }}"
                                data-method="PATCH"
                                title="Réactiver l'évaluation">
                            <i class="fas fa-arrow-rotate-left"></i>
                        </button>
                    @elseif(!$evaluation->is_published)
                        <button type="button"
                                class="ev-action-btn ev-action-btn--success"
                                data-evaluation-action="restore"
                                data-url="{{ route('esbtp.evaluations.restore', $evaluation) }}"
                                data-method="PATCH"
                                title="Activer l'évaluation">
                            <i class="fas fa-circle-play"></i>
                        </button>
                    @else
                        <button type="button"
                                class="ev-action-btn ev-action-btn--danger"
                                data-evaluation-action="cancel"
                                data-url="{{ route('esbtp.evaluations.cancel', $evaluation) }}"
                                data-method="PATCH"
                                title="Annuler l'évaluation">
                            <i class="fas fa-circle-xmark"></i>
                        </button>
                    @endif
                @endcan
                @canany(['evaluations.edit', 'admin.access'])
                    @if($evaluation->isDeletable())
                        <button type="button"
                                class="ev-action-btn ev-action-btn--danger"
                                data-evaluation-action="delete"
                                data-url="{{ route('esbtp.evaluations.destroy', $evaluation) }}"
                                data-method="DELETE"
                                title="Supprimer">
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
