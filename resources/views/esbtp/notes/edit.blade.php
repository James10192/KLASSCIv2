@extends('layouts.app')

@section('title', 'Modifier une note - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Modifier la note</h5>
                    <div>
                        <a href="{{ route('esbtp.evaluations.show', $note->evaluation) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour aux détails de l'évaluation
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations sur l'évaluation</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th width="30%">Titre :</th>
                                                <td>{{ $note->evaluation->titre }}</td>
                                            </tr>
                                            <tr>
                                                <th>Type :</th>
                                                <td>{{ ucfirst($note->evaluation->type) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Date :</th>
                                                <td>{{ date('d/m/Y', strtotime($note->evaluation->date_evaluation)) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Classe :</th>
                                                <td>{{ $note->evaluation->classe ? $note->evaluation->classe->name : 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Matière :</th>
                                                <td>{{ $note->evaluation->matiere ? $note->evaluation->matiere->name : 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Barème :</th>
                                                <td>{{ $note->evaluation->bareme }} points</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations sur l'étudiant</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th width="30%">Matricule :</th>
                                                <td>{{ $note->etudiant->matricule }}</td>
                                            </tr>
                                            <tr>
                                                <th>Nom :</th>
                                                <td>{{ $note->etudiant->nom }}</td>
                                            </tr>
                                            <tr>
                                                <th>Prénom :</th>
                                                <td>{{ $note->etudiant->prenom }}</td>
                                            </tr>
                                            <tr>
                                                <th>Classe :</th>
                                                <td>{{ $note->etudiant->classe ? $note->etudiant->classe->name : 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Statut :</th>
                                                <td>
                                                    @if($note->etudiant->active)
                                                        <span class="badge bg-success">Actif</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactif</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('esbtp.notes.update', $note) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Informations de la note</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="note" class="form-label">Note <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number"
                                                       class="form-control @error('note') is-invalid @enderror"
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
                                            <div class="form-text">Note équivalente sur 20 : <span id="note_sur_20">{{ $note->evaluation->bareme > 0 ? number_format(($note->note * 20) / $note->evaluation->bareme, 2) : 0 }}</span>/20</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex flex-column h-100 justify-content-center">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_absent" id="is_absent" class="form-check-input"
                                                    {{ old('is_absent', $note->is_absent) ? 'checked' : '' }}>
                                                <label for="is_absent" class="form-check-label">
                                                    <i class="fas fa-user-slash text-danger me-1"></i>Étudiant absent
                                                </label>
                                            </div>
                                            <div class="form-text mt-1">
                                                <i class="fas fa-info-circle text-info me-1"></i> Cochez cette case si l'étudiant était absent lors de l'évaluation
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="date_saisie" class="form-label">Date de saisie</label>
                                    <input type="date"
                                           class="form-control @error('date_saisie') is-invalid @enderror"
                                           id="date_saisie"
                                           name="date_saisie"
                                           value="{{ old('date_saisie', $note->created_at ? date('Y-m-d', strtotime($note->created_at)) : date('Y-m-d')) }}">
                                    @error('date_saisie')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="commentaire" class="form-label">Commentaire</label>
                                    <textarea class="form-control @error('commentaire') is-invalid @enderror"
                                              id="commentaire"
                                              name="commentaire"
                                              rows="3">{{ old('commentaire', $note->commentaire) }}</textarea>
                                    @error('commentaire')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-1"></i>Supprimer la note
                                </button>
                                <div>
                                    <button type="reset" class="btn btn-secondary me-2">
                                        <i class="fas fa-undo me-1"></i>Annuler les modifications
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Enregistrer les modifications
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.notes.destroy', $note) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
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
            console.log('Updating required state - Absent:', isAbsent);

            noteInput.prop('required', !isAbsent);
            noteInput.prop('disabled', isAbsent);

            console.log('Required state after update:', noteInput.prop('required'));
            console.log('Disabled state after update:', noteInput.prop('disabled'));

            if (isAbsent) {
                noteInput.val('');
                $('#note_sur_20').text('--');
                // Remove validation error if present
                noteInput.removeClass('is-invalid');
                noteInput.next('.invalid-feedback').remove();
            }
        }

        // Initial state
        console.log('Setting initial state');
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
            console.log('Absent checkbox changed');
            updateRequiredState();
            if ($(this).is(':checked')) {
                $('#note_sur_20').text('--');
            } else {
                updateNoteSur20();
            }
        });

        // Reset button handler
        $('button[type="reset"]').click(function() {
            console.log('Form reset triggered');
            setTimeout(updateRequiredState, 0);
        });
    });
</script>
@endsection
