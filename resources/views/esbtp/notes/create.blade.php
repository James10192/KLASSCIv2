@extends('layouts.app')

@section('title', 'Ajouter une note - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-graduation-cap me-2"></i>Ajouter une nouvelle note
                    </h5>
                    <div>
                        <a href="{{ route('esbtp.notes.index') }}" class="btn btn-outline-secondary shadow-sm">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste des notes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4" role="alert">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-exclamation-circle fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="alert-heading">Erreur de validation</h5>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                        </div>
                    @endif

                    <form action="{{ route('esbtp.notes.store') }}" method="POST">
                        @csrf
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Informations de base
                                </h6>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="evaluation_id" class="form-label text-muted mb-1">Évaluation <span class="text-danger">*</span></label>
                                        <select name="evaluation_id" id="evaluation_id" class="form-select shadow-sm @error('evaluation_id') is-invalid @enderror" required>
                                            <option value="">-- Sélectionner une évaluation --</option>
                                            @foreach ($evaluations as $evaluation)
                                                <option value="{{ $evaluation->id }}" {{ old('evaluation_id') == $evaluation->id ? 'selected' : '' }}>
                                                    {{ $evaluation->titre }} - {{ $evaluation->matiere ? $evaluation->matiere->name : 'N/A' }}
                                                    ({{ $evaluation->classe ? $evaluation->classe->name : 'N/A' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text"><i class="fas fa-lightbulb text-warning me-1"></i> L'évaluation détermine la matière et la classe</div>
                                        @error('evaluation_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="etudiant_id" class="form-label text-muted mb-1">Étudiant <span class="text-danger">*</span></label>
                                        <select name="etudiant_id" id="etudiant_id" class="form-select shadow-sm @error('etudiant_id') is-invalid @enderror" required>
                                            <option value="">-- Sélectionner un étudiant --</option>
                                            @foreach ($etudiants as $etudiant)
                                                <option value="{{ $etudiant->id }}" {{ old('etudiant_id') == $etudiant->id ? 'selected' : '' }}>
                                                    {{ $etudiant->nom }} {{ $etudiant->prenoms }} ({{ $etudiant->matricule ?? 'N/A' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('etudiant_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-clipboard-check me-2"></i>Détails de la note
                                </h6>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="note" class="form-label text-muted mb-1">Note <span class="text-danger">*</span></label>
                                        <div class="input-group shadow-sm">
                                            <input type="text" name="note" id="note" class="form-control @error('note') is-invalid @enderror"
                                                value="{{ old('note') }}" placeholder="Exemple: 15.5" required>
                                            <span class="input-group-text">/20</span>
                                        </div>
                                        @error('note')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <div class="d-flex flex-column h-100 justify-content-center">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_absent" id="is_absent" class="form-check-input"
                                                    {{ old('is_absent') ? 'checked' : '' }}>
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

                                <div class="row g-3 mt-1">
                                    <div class="col-12">
                                        <label for="commentaire" class="form-label text-muted mb-1">Commentaire (optionnel)</label>
                                        <textarea name="commentaire" id="commentaire" rows="3"
                                            class="form-control shadow-sm @error('commentaire') is-invalid @enderror"
                                            placeholder="Ajoutez un commentaire concernant cette note...">{{ old('commentaire') }}</textarea>
                                        @error('commentaire')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info border-start border-info border-4 shadow-sm" role="alert">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <i class="fas fa-info-circle fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="alert-heading">À propos de la saisie des notes</h6>
                                            <p class="mb-0">
                                                Veuillez vérifier que les informations saisies sont correctes avant de soumettre le formulaire.
                                                Si l'étudiant était absent, cochez la case "Étudiant absent" et la note sera automatiquement considérée comme 0.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('esbtp.notes.index') }}" class="btn btn-outline-secondary me-md-2 shadow-sm">
                                <i class="fas fa-times me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="fas fa-save me-1"></i>Enregistrer la note
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Initialisation de Select2 pour les listes déroulantes
        $('.form-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Gestion de l'état absent
        $('#is_absent').change(function() {
            if($(this).is(':checked')) {
                $('#note').val('0').attr('readonly', true);
            } else {
                $('#note').val('').attr('readonly', false);
            }
        });

        // Vérifier si la case est déjà cochée au chargement
        if($('#is_absent').is(':checked')) {
            $('#note').val('0').attr('readonly', true);
        }

        // Filtrer les étudiants en fonction de l'évaluation sélectionnée
        $('#evaluation_id').change(function() {
            var evaluationId = $(this).val();
            if(evaluationId) {
                $.ajax({
                    url: '/esbtp/api/evaluations/' + evaluationId + '/etudiants',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#etudiant_id').empty();
                        $('#etudiant_id').append('<option value="">-- Sélectionner un étudiant --</option>');
                        $.each(data, function(key, value) {
                            $('#etudiant_id').append('<option value="' + value.id + '">' + value.nom + ' ' + value.prenoms + ' (' + (value.matricule || 'N/A') + ')</option>');
                        });
                        $('#etudiant_id').trigger('change');
                    }
                });
            } else {
                $('#etudiant_id').empty();
                $('#etudiant_id').append('<option value="">-- Sélectionner un étudiant --</option>');
            }
        });
    });
</script>
@endsection
