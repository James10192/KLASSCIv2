@extends('layouts.app')

@section('title', 'Modifier une note - KLASSCI')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier la note</h1>
                <p class="header-subtitle">{{ $note->etudiant->nom }} {{ $note->etudiant->prenom }} - {{ $note->evaluation->titre }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.evaluations.show', $note->evaluation) }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour à l'évaluation
                </a>
            </div>
        </div>
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="main-card h-100">
                    <div class="main-card-header" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.05));">
                        <div class="main-card-title">
                            <i class="fas fa-file-alt"></i>
                            Informations sur l'évaluation
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="info-item">
                            <div class="info-label">Titre</div>
                            <div class="info-value">{{ $note->evaluation->titre }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Type</div>
                            <div class="info-value">
                                @php
                                    $typeIcons = [
                                        'examen' => '<i class="fas fa-file-alt color-primary me-1"></i>',
                                        'devoir' => '<i class="fas fa-pencil-alt color-success me-1"></i>',
                                        'tp' => '<i class="fas fa-flask color-warning me-1"></i>',
                                        'projet' => '<i class="fas fa-project-diagram color-accent me-1"></i>',
                                        'controle' => '<i class="fas fa-tasks color-neutral me-1"></i>',
                                    ];
                                    $icon = $typeIcons[$note->evaluation->type] ?? '<i class="fas fa-file-alt color-primary me-1"></i>';
                                @endphp
                                {!! $icon !!} {{ ucfirst($note->evaluation->type) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date</div>
                            <div class="info-value">
                                <i class="far fa-calendar-alt color-neutral me-1"></i>
                                {{ date('d/m/Y', strtotime($note->evaluation->date_evaluation)) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Classe</div>
                            <div class="info-value">
                                <i class="fas fa-users color-neutral me-1"></i>
                                {{ $note->evaluation->classe ? $note->evaluation->classe->name : 'N/A' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Matière</div>
                            <div class="info-value">
                                <i class="fas fa-book color-neutral me-1"></i>
                                {{ $note->evaluation->matiere ? $note->evaluation->matiere->name : 'N/A' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Barème</div>
                            <div class="info-value">
                                <i class="fas fa-calculator color-neutral me-1"></i>
                                {{ $note->evaluation->bareme }} points
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="main-card h-100">
                    <div class="main-card-header" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));">
                        <div class="main-card-title">
                            <i class="fas fa-user-graduate"></i>
                            Informations sur l'étudiant
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="info-item">
                            <div class="info-label">Matricule</div>
                            <div class="info-value">{{ $note->etudiant->matricule }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nom</div>
                            <div class="info-value">{{ $note->etudiant->nom }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Prénom</div>
                            <div class="info-value">{{ $note->etudiant->prenom }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Classe</div>
                            <div class="info-value">
                                <i class="fas fa-users color-neutral me-1"></i>
                                {{ $note->etudiant->classe ? $note->etudiant->classe->name : 'N/A' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Statut</div>
                            <div class="info-value">
                                @if($note->etudiant->active)
                                    <span class="status-badge success">Actif</span>
                                @else
                                    <span class="status-badge danger">Inactif</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('esbtp.notes.update', $note) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="main-card">
                <div class="main-card-header" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05));">
                    <div class="main-card-title">
                        <i class="fas fa-edit"></i>
                        Informations de la note
                    </div>
                </div>
                <div class="main-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-star"></i>
                                    Note <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-input-moderne @error('note') is-invalid @enderror"
                                           id="note"
                                           name="note"
                                           value="{{ old('note', $note->note) }}"
                                           min="0"
                                           max="{{ $note->evaluation->bareme }}"
                                           step="0.25"
                                           {{ old('is_absent', $note->is_absent) ? 'disabled' : '' }}>
                                    <span class="input-group-text">/ {{ $note->evaluation->bareme }}</span>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted mt-1">Note équivalente sur 20 : <span id="note_sur_20">{{ $note->evaluation->bareme > 0 ? number_format(($note->note * 20) / $note->evaluation->bareme, 2) : 0 }}</span>/20</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column h-100 justify-content-center">
                                <div class="form-check form-switch mb-3">
                                    <input type="checkbox" name="is_absent" id="is_absent" class="form-check-input"
                                        {{ old('is_absent', $note->is_absent) ? 'checked' : '' }}>
                                    <label for="is_absent" class="form-check-label">
                                        <i class="fas fa-user-slash color-danger me-1"></i>Étudiant absent
                                    </label>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle color-accent me-1"></i>Cochez cette case si l'étudiant était absent lors de l'évaluation
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-calendar"></i>
                            Date de saisie
                        </label>
                        <input type="date"
                               class="form-input-moderne @error('date_saisie') is-invalid @enderror"
                               id="date_saisie"
                               name="date_saisie"
                               value="{{ old('date_saisie', $note->created_at ? date('Y-m-d', strtotime($note->created_at)) : date('Y-m-d')) }}">
                        @error('date_saisie')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-comment"></i>
                            Commentaire
                        </label>
                        <textarea class="form-textarea-moderne @error('commentaire') is-invalid @enderror"
                                  id="commentaire"
                                  name="commentaire"
                                  rows="3">{{ old('commentaire', $note->commentaire) }}</textarea>
                        @error('commentaire')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        @can('notes.delete')
                        <button type="button" class="btn-acasi danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i>Supprimer la note
                        </button>
                        @endcan
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn-acasi secondary">
                                <i class="fas fa-undo"></i>Annuler les modifications
                            </button>
                            <button type="submit" class="btn-acasi success">
                                <i class="fas fa-save"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette note ?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cette action est irréversible et pourrait affecter les calculs de moyennes et les bulletins.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>Annuler
                </button>
                <form action="{{ route('esbtp.notes.destroy', $note) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-acasi danger">
                        <i class="fas fa-trash"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize required state on page load
        const noteInput = $('#note');
        const absentCheckbox = $('#is_absent');

        function updateRequiredState() {
            const isAbsent = absentCheckbox.is(':checked');
            debugLog('Updating required state - Absent:', isAbsent);

            noteInput.prop('required', !isAbsent);
            noteInput.prop('disabled', isAbsent);

            debugLog('Required state after update:', noteInput.prop('required'));
            debugLog('Disabled state after update:', noteInput.prop('disabled'));

            if (isAbsent) {
                noteInput.val('');
                $('#note_sur_20').text('--');
                // Remove validation error if present
                noteInput.removeClass('is-invalid');
                noteInput.next('.invalid-feedback').remove();
            }
        }

        // Initial state
        debugLog('Setting initial state');
        updateRequiredState();

        // Calcul automatique de la note sur 20
        function updateNoteSur20() {
            const note = parseFloat($('#note').val()) || 0;
            const bareme = {{ $note->evaluation->bareme }};
            const noteSur20 = (note * 20) / bareme;
            $('#note_sur_20').text(noteSur20.toFixed(2));
        }

        $('#note').on('input', updateNoteSur20);

        // Gestion de la case à cocher "Absent"
        $('#is_absent').change(function() {
            debugLog('Absent checkbox changed');
            updateRequiredState();
            if ($(this).is(':checked')) {
                $('#note_sur_20').text('--');
            } else {
                updateNoteSur20();
            }
        });

        // Reset button handler
        $('button[type="reset"]').click(function() {
            debugLog('Form reset triggered');
            setTimeout(updateRequiredState, 0);
        });
    });
</script>
@endsection
