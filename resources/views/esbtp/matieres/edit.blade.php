@extends('layouts.app')

@section('title', 'Modifier la matière : ' . $matiere->name . ' - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Modifier la matière : {{ $matiere->name }}</h5>
                    <div>
                        <a href="{{ route('esbtp.matieres.show', $matiere) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-1"></i>Détails
                        </a>
                        <a href="{{ route('esbtp.matieres.index') }}" class="btn btn-secondary">
                            <i class="fas fa-list me-1"></i>Liste des matières
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.matieres.update', $matiere) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Nom de la matière -->
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nom de la matière <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $matiere->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Code de la matière -->
                                        <div class="mb-3">
                                            <label for="code" class="form-label">Code de la matière <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $matiere->code) }}" required>
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Nom complet (nom) -->
                                        <div class="mb-3">
                                            <label for="nom" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom', $matiere->nom) }}" required>
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
                                            <input type="number" class="form-control @error('coefficient') is-invalid @enderror" id="coefficient" name="coefficient" value="{{ old('coefficient', $matiere->coefficient) }}" min="1" step="0.5" required>
                                            @error('coefficient')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Volume horaire -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_cm" class="form-label">Heures de cours magistraux</label>
                                                <input type="number" class="form-control @error('heures_cm') is-invalid @enderror" id="heures_cm" name="heures_cm" value="{{ old('heures_cm', $matiere->heures_cm) }}" min="0" step="0.5">
                                                @error('heures_cm')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_td" class="form-label">Heures de travaux dirigés</label>
                                                <input type="number" class="form-control @error('heures_td') is-invalid @enderror" id="heures_td" name="heures_td" value="{{ old('heures_td', $matiere->heures_td) }}" min="0" step="0.5">
                                                @error('heures_td')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_tp" class="form-label">Heures de travaux pratiques</label>
                                                <input type="number" class="form-control @error('heures_tp') is-invalid @enderror" id="heures_tp" name="heures_tp" value="{{ old('heures_tp', $matiere->heures_tp) }}" min="0" step="0.5">
                                                @error('heures_tp')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="heures_stage" class="form-label">Heures de stage</label>
                                                <input type="number" class="form-control @error('heures_stage') is-invalid @enderror" id="heures_stage" name="heures_stage" value="{{ old('heures_stage', $matiere->heures_stage) }}" min="0" step="0.5">
                                                @error('heures_stage')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="heures_perso" class="form-label">Heures de travail personnel</label>
                                            <input type="number" class="form-control @error('heures_perso') is-invalid @enderror" id="heures_perso" name="heures_perso" value="{{ old('heures_perso', $matiere->heures_perso) }}" min="0" step="0.5">
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
                                                    <option value="{{ $filiere->id }}" {{ old('filiere_id', $matiere->filieres->contains($filiere->id)) ? 'selected' : '' }}>
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
                                                    <option value="{{ $niveau->id }}" {{ old('niveau_etude_id', $matiere->niveaux->contains($niveau->id)) ? 'selected' : '' }}>
                                                        {{ $niveau->name }} ({{ $niveau->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('niveau_etude_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Couleur -->
                                        <div class="mb-3">
                                            <label for="couleur" class="form-label">Couleur</label>
                                            <input type="color" class="form-control form-control-color @error('couleur') is-invalid @enderror" id="couleur" name="couleur" value="{{ old('couleur', $matiere->couleur ?? '#007bff') }}">
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
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $matiere->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Statut -->
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $matiere->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">Matière active</label>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>Une matière inactive ne pourra pas être utilisée dans les emplois du temps ou les évaluations.
                                        </small>

                                        <!-- Alertes pour les éléments liés -->
                                        @if($matiere->seancesCours->count() > 0 || $matiere->evaluations->count() > 0)
                                            <div class="alert alert-warning mt-3 mb-0">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Attention :</strong> Cette matière est liée à :
                                                <ul class="mb-0 mt-1">
                                                    @if($matiere->seancesCours->count() > 0)
                                                        <li>{{ $matiere->seancesCours->count() }} séance(s) de cours</li>
                                                    @endif
                                                    @if($matiere->evaluations->count() > 0)
                                                        <li>{{ $matiere->evaluations->count() }} évaluation(s)</li>
                                                    @endif
                                                </ul>
                                                La modification de cette matière peut affecter ces éléments.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 d-flex justify-content-between">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-1"></i>Supprimer
                                </button>
                                <div>
                                    <a href="{{ route('esbtp.matieres.show', $matiere) }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </a>
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

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette matière ?</p>
                <p><strong>Nom :</strong> {{ $matiere->name }}</p>

                @if($matiere->seancesCours->count() > 0 || $matiere->evaluations->count() > 0)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Cette matière est liée à :
                        <ul class="mb-0 mt-1">
                            @if($matiere->seancesCours->count() > 0)
                                <li>{{ $matiere->seancesCours->count() }} séance(s) de cours</li>
                            @endif
                            @if($matiere->evaluations->count() > 0)
                                <li>{{ $matiere->evaluations->count() }} évaluation(s)</li>
                            @endif
                        </ul>
                        La suppression de cette matière pourrait causer des erreurs dans le système. Assurez-vous de supprimer ces éléments liés avant de continuer.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.matieres.destroy', $matiere) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                </form>
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
