@extends('layouts.app')

@section('title', 'Modifier l\'évaluation : ' . $evaluation->titre . ' - ESBTP-yAKRO')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier l'évaluation</h1>
                <p class="header-subtitle">{{ $evaluation->titre }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-eye"></i>Voir les détails
                </a>
                <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <!-- Matières statiques (fallback) -->
        <div id="matiere-data" data-matieres="{{ json_encode($matieres) }}" style="display: none;"></div>

        <!-- Main Card -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-form"></i>
                    Formulaire de modification
                </div>
                <div class="main-card-subtitle">Modifiez les informations de l'évaluation</div>
            </div>
            
            <div class="main-card-body">
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

                <form action="{{ route('esbtp.evaluations.update', $evaluation) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <!-- Informations générales -->
                        <div class="col-md-6">
                            <div class="main-card mb-4">
                                <div class="main-card-header" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.05));">
                                    <div class="main-card-title">
                                        <i class="fas fa-info-circle"></i>
                                        Informations générales
                                    </div>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-heading"></i>
                                            Titre de l'évaluation <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-input-moderne @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre', $evaluation->titre) }}" required>
                                        @error('titre')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-tags"></i>
                                            Type d'évaluation <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select-moderne @error('type') is-invalid @enderror" id="type" name="type" required>
                                            <option value="">Sélectionner un type</option>
                                            @foreach($types as $typeKey => $typeValue)
                                                <option value="{{ $typeKey }}" {{ old('type', $evaluation->type) == $typeKey ? 'selected' : '' }}>{{ $typeValue }}</option>
                                            @endforeach
                                        </select>
                                        @error('type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-calendar"></i>
                                            Période <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select-moderne @error('periode') is-invalid @enderror" id="periode" name="periode" required>
                                            <option value="">Sélectionner une période</option>
                                            <option value="semestre1" {{ old('periode', $evaluation->periode) == 'semestre1' ? 'selected' : '' }}>Semestre 1</option>
                                            <option value="semestre2" {{ old('periode', $evaluation->periode) == 'semestre2' ? 'selected' : '' }}>Semestre 2</option>
                                        </select>
                                        @error('periode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-calendar-day"></i>
                                            Date de l'évaluation <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-input-moderne @error('date_evaluation') is-invalid @enderror" id="date_evaluation" name="date_evaluation" value="{{ old('date_evaluation', $evaluation->date_evaluation ? date('Y-m-d', strtotime($evaluation->date_evaluation)) : '') }}" required>
                                        @error('date_evaluation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-clock"></i>
                                            Durée (en minutes)
                                        </label>
                                        <input type="number" class="form-input-moderne @error('duree_minutes') is-invalid @enderror" id="duree_minutes" name="duree_minutes" value="{{ old('duree_minutes', $evaluation->duree_minutes) }}" min="1">
                                        @error('duree_minutes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paramètres de notation -->
                        <div class="col-md-6">
                            <div class="main-card mb-4">
                                <div class="main-card-header" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));">
                                    <div class="main-card-title">
                                        <i class="fas fa-calculator"></i>
                                        Paramètres de notation
                                    </div>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-users"></i>
                                            Classe <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select-moderne select2 @error('classe_id') is-invalid @enderror" id="classe_id" name="classe_id" required>
                                            <option value="">Sélectionner une classe</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id }}" {{ old('classe_id', $evaluation->classe_id) == $classe->id ? 'selected' : '' }}>
                                                    {{ $classe->name }} ({{ $classe->filiere->name }} - {{ $classe->niveau->name }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('classe_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-book"></i>
                                            Matière <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select-moderne select2 @error('matiere_id') is-invalid @enderror" id="matiere_id" name="matiere_id" required>
                                            <option value="">Sélectionner une matière</option>
                                            @foreach($matieres as $matiere)
                                                <option value="{{ $matiere->id }}" {{ old('matiere_id', $evaluation->matiere_id) == $matiere->id ? 'selected' : '' }}>
                                                    {{ $matiere->nom ?? $matiere->name ?? 'Matière ' . $matiere->id }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('matiere_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-weight"></i>
                                            Coefficient <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-input-moderne @error('coefficient') is-invalid @enderror" id="coefficient" name="coefficient" value="{{ old('coefficient', $evaluation->coefficient) }}" step="0.1" min="0.1" required>
                                        @error('coefficient')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-chart-bar"></i>
                                            Barème <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-input-moderne @error('bareme') is-invalid @enderror" id="bareme" name="bareme" value="{{ old('bareme', $evaluation->bareme) }}" step="0.1" min="1" required>
                                        <small class="text-muted mt-1">Nombre de points total pour cette évaluation (généralement 20).</small>
                                        @error('bareme')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description et options -->
                        <div class="col-12">
                            <div class="main-card mb-4">
                                <div class="main-card-header" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05));">
                                    <div class="main-card-title">
                                        <i class="fas fa-cogs"></i>
                                        Description et options
                                    </div>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group-moderne">
                                        <label class="form-label-moderne">
                                            <i class="fas fa-align-left"></i>
                                            Description
                                        </label>
                                        <textarea class="form-textarea-moderne @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $evaluation->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $evaluation->is_published) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_published">
                                            <i class="fas fa-eye me-1"></i>Publier l'évaluation
                                        </label>
                                        <small class="form-text text-muted d-block">Une évaluation publiée est visible par les enseignants et permet la saisie des notes.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes existantes -->
                        @if($evaluation->notes->count() > 0)
                        <div class="col-12">
                            <div class="main-card mb-4">
                                <div class="main-card-header" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));">
                                    <div class="main-card-title">
                                        <i class="fas fa-graduation-cap"></i>
                                        Notes existantes
                                    </div>
                                </div>
                                <div class="main-card-body">
                                    <div class="alert alert-info d-flex align-items-center">
                                        <i class="fas fa-info-circle me-3"></i>
                                        <div>
                                            Cette évaluation a déjà <strong>{{ $evaluation->notes->count() }}</strong> notes enregistrées.
                                            La modification de certains paramètres (barème, coefficient) peut affecter les calculs des moyennes et des bulletins.
                                        </div>
                                    </div>
                                    <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="btn-acasi primary">
                                        <i class="fas fa-pen"></i>Accéder à la saisie des notes
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Actions -->
                        <div class="col-12">
                            <div class="main-card">
                                <div class="main-card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn-acasi danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i>Supprimer l'évaluation
                                        </button>
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
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Êtes-vous sûr de vouloir supprimer cette évaluation ?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Cette action est irréversible.</strong>
                </div>

                @if($evaluation->notes->count() > 0)
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette évaluation a <strong>{{ $evaluation->notes->count() }}</strong> notes associées qui seront également supprimées.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>Annuler
                </button>
                <form action="{{ route('esbtp.evaluations.destroy', $evaluation) }}" method="POST" class="d-inline">
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
        // Initialisation de Select2
        $('.select2').select2({
            theme: 'bootstrap-5'
        });

        // Stocker l'ID de la matière actuelle
        const initialMatiereId = '{{ old('matiere_id', $evaluation->matiere_id) }}';
        console.log('Matière actuelle ID:', initialMatiereId);

        // Fonction pour réinitialiser le select des matières avec les données statiques
        function resetMatiereSelect() {
            const $matiereSelect = $('#matiere_id');
            $matiereSelect.empty().append('<option value="">Sélectionner une matière</option>');

            // Charger les matières statiques comme fallback
            const staticMatieres = JSON.parse($('#matiere-data').attr('data-matieres'));
            if (staticMatieres && staticMatieres.length > 0) {
                console.log('Utilisation des matières statiques:', staticMatieres.length);
                staticMatieres.forEach(function(matiere) {
                    const id = matiere.id;
                    const name = matiere.nom || matiere.name || ('Matière ' + id);
                    const selected = (id == initialMatiereId) ? 'selected' : '';
                    $matiereSelect.append(`<option value="${id}" ${selected}>${name}</option>`);

                    if (id == initialMatiereId) {
                        console.log('Matière correspondante trouvée dans les données statiques:', name);
                    }
                });
            }

            $matiereSelect.prop('disabled', false).trigger('change');
        }

        // Chargement des matières en fonction de la classe sélectionnée
        function loadMatieres(classeId) {
            const $matiereSelect = $('#matiere_id');
            $matiereSelect.prop('disabled', true);

            // Log pour le débogage
            console.log('Chargement des matières pour la classe ID:', classeId);
            console.log('Matière actuelle:', initialMatiereId);

            // Premier essai avec l'API principale
            $.ajax({
                url: `/esbtp/api/classes/${classeId}/matieres`,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('Matières reçues de l\'API:', data);
                    updateMatiereSelect(data);
                },
                error: function(xhr, status, error) {
                    console.error('Erreur lors du chargement des matières:', error);
                    console.error('Statut:', status);
                    console.error('Réponse:', xhr.responseText);

                    // En cas d'erreur, essayer l'API de fallback
                    $.ajax({
                        url: `/esbtp/api/classes/${classeId}/matieres`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            console.log('Matières reçues de l\'API de fallback:', data);
                            updateMatiereSelect(data);
                        },
                        error: function(xhr) {
                            console.error('L\'API de fallback a également échoué:', xhr);
                            // Utiliser les matières statiques en dernier recours
                            resetMatiereSelect();
                            console.log('Utilisation des matières statiques comme dernier recours');
                        }
                    });
                }
            });
        }

        function updateMatiereSelect(data) {
            const $matiereSelect = $('#matiere_id');
            const currentMatiereId = initialMatiereId;

            console.log('Mise à jour du select des matières avec la matière ID:', currentMatiereId);

            $matiereSelect.empty().append('<option value="">Sélectionner une matière</option>');

            if (Array.isArray(data) && data.length > 0) {
                console.log('Mise à jour avec', data.length, 'matières');
                data.forEach(function(matiere) {
                    const id = matiere.id;
                    const name = matiere.nom || matiere.name || ('Matière ' + id);
                    const selected = (id == currentMatiereId) ? 'selected' : '';
                    $matiereSelect.append(`<option value="${id}" ${selected}>${name}</option>`);

                    // Log pour le débogage
                    if (id == currentMatiereId) {
                        console.log('Matière correspondante trouvée dans l\'API:', name);
                    }
                });
            } else {
                console.warn('Aucune matière reçue de l\'API ou format incorrect');
                // Utiliser les matières statiques comme fallback
                resetMatiereSelect();
            }

            $matiereSelect.prop('disabled', false).trigger('change');
        }

        // Événement de changement de classe
        $('#classe_id').on('change', function() {
            const classeId = $(this).val();
            if (classeId) {
                loadMatieres(classeId);
            } else {
                resetMatiereSelect();
            }
        });

        // Chargement initial des matières
        const initialClasseId = $('#classe_id').val();
        if (initialClasseId) {
            loadMatieres(initialClasseId);
        } else {
            // Si pas de classe sélectionnée, utiliser les matières statiques
            resetMatiereSelect();
        }
    });
</script>
@endsection
