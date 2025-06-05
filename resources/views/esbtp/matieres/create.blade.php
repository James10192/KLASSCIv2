@extends('layouts.app')

@section('title', 'Ajouter une matière - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ajouter une nouvelle matière</h5>
                    <a href="{{ route('esbtp.matieres.index') }}" class="btn btn-secondary">
                        <i class="fas fa-list me-1"></i>Liste des matières
                    </a>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.matieres.store') }}" method="POST">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Code de la matière -->
                                        <div class="mb-3">
                                            <label for="code" class="form-label">Code de la matière <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}">
                                            <small class="form-text text-muted">Si laissé vide, le code sera généré automatiquement.</small>
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Nom complet de la matière (nom) -->
                                        <div class="mb-3">
                                            <label for="nom" class="form-label">Nom complet de la matière <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom') }}" required>
                                            @error('nom')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Paramètres d'évaluation</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Coefficient -->
                                        <div class="mb-3">
                                            <label for="coefficient" class="form-label">Coefficient <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('coefficient') is-invalid @enderror" id="coefficient" name="coefficient" value="{{ old('coefficient', 1) }}" min="1" step="0.5" required>
                                            @error('coefficient')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Volume horaire -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_cm" class="form-label">Heures de cours magistraux</label>
                                                <input type="number" class="form-control @error('heures_cm') is-invalid @enderror" id="heures_cm" name="heures_cm" value="{{ old('heures_cm', 0) }}" min="0" step="0.5">
                                                @error('heures_cm')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_td" class="form-label">Heures de travaux dirigés</label>
                                                <input type="number" class="form-control @error('heures_td') is-invalid @enderror" id="heures_td" name="heures_td" value="{{ old('heures_td', 0) }}" min="0" step="0.5">
                                                @error('heures_td')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_tp" class="form-label">Heures de travaux pratiques</label>
                                                <input type="number" class="form-control @error('heures_tp') is-invalid @enderror" id="heures_tp" name="heures_tp" value="{{ old('heures_tp', 0) }}" min="0" step="0.5">
                                                @error('heures_tp')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_stage" class="form-label">Heures de stage</label>
                                                <input type="number" class="form-control @error('heures_stage') is-invalid @enderror" id="heures_stage" name="heures_stage" value="{{ old('heures_stage', 0) }}" min="0" step="0.5">
                                                @error('heures_stage')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="heures_perso" class="form-label">Heures de travail personnel</label>
                                            <input type="number" class="form-control @error('heures_perso') is-invalid @enderror" id="heures_perso" name="heures_perso" value="{{ old('heures_perso', 0) }}" min="0" step="0.5">
                                            @error('heures_perso')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-link me-2"></i>Associations</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Filière associée -->
                                        <div class="mb-3">
                                            <label for="filiere_id" class="form-label">Filière</label>
                                            <select class="form-select select2 @error('filiere_id') is-invalid @enderror" id="filiere_id" name="filiere_id">
                                                <option value="">Sélectionner une filière</option>
                                                @foreach($filieres as $filiere)
                                                    <option value="{{ $filiere->id }}" {{ old('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                                        {{ $filiere->name }} ({{ $filiere->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('filiere_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Niveau d'étude associé -->
                                        <div class="mb-3">
                                            <label for="niveau_etude_id" class="form-label">Niveau d'étude</label>
                                            <select class="form-select select2 @error('niveau_etude_id') is-invalid @enderror" id="niveau_etude_id" name="niveau_etude_id">
                                                <option value="">Sélectionner un niveau d'étude</option>
                                                @foreach($niveauxEtudes as $niveau)
                                                    <option value="{{ $niveau->id }}" {{ old('niveau_etude_id') == $niveau->id ? 'selected' : '' }}>
                                                        {{ $niveau->name }} ({{ $niveau->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('niveau_etude_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Type de formation -->
                                        <div class="mb-3">
                                            <label for="type_formation" class="form-label">Type de formation <span class="text-danger">*</span></label>
                                            <select class="form-select @error('type_formation') is-invalid @enderror" id="type_formation" name="type_formation" required>
                                                <option value="generale" {{ old('type_formation') == 'generale' ? 'selected' : '' }}>Formation générale</option>
                                                <option value="technologique_professionnelle" {{ old('type_formation') == 'technologique_professionnelle' ? 'selected' : '' }}>Formation technologique et professionnelle</option>
                                            </select>
                                            @error('type_formation')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Couleur -->
                                        <div class="mb-3">
                                            <label for="couleur" class="form-label">Couleur</label>
                                            <input type="color" class="form-control form-control-color @error('couleur') is-invalid @enderror" id="couleur" name="couleur" value="{{ old('couleur', '#007bff') }}">
                                            <small class="form-text text-muted">Couleur utilisée pour représenter la matière dans l'emploi du temps</small>
                                            @error('couleur')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-align-left me-2"></i>Description et options</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Description -->
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Statut -->
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">Matière active</label>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>Une matière inactive ne pourra pas être utilisée dans les emplois du temps ou les évaluations.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="reset" class="btn btn-secondary me-2">
                                    <i class="fas fa-undo me-1"></i>Réinitialiser
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer la matière
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialisation de Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Génération automatique du code
        $('#nom').on('blur', function() {
            if ($('#code').val() === '') {
                let name = $(this).val().trim().toUpperCase();
                if (name) {
                    // Créer le code à partir des 3 premières lettres de chaque mot
                    let code = name.split(/\s+/).map(word => word.substring(0, 3)).join('');
                    $('#code').val(code);
                }
            }
        });

        // Calcul automatique du total des heures
        function calculateTotalHours() {
            const cm = parseFloat($('#heures_cm').val()) || 0;
            const td = parseFloat($('#heures_td').val()) || 0;
            const tp = parseFloat($('#heures_tp').val()) || 0;
            const stage = parseFloat($('#heures_stage').val()) || 0;
            const perso = parseFloat($('#heures_perso').val()) || 0;
            const total = cm + td + tp + stage + perso;
            $('#total_heures_default').val(total);
        }

        $('#heures_cm, #heures_td, #heures_tp, #heures_stage, #heures_perso').on('input', calculateTotalHours);
        calculateTotalHours(); // Calcul initial
    });
</script>
@endsection
