@extends('layouts.app')

@section('title', 'Ajouter une matière - ESBTP-yAKRO')

@section('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="main-content">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-plus-circle me-2"></i>Ajouter une Matière</h1>
            <p class="header-subtitle">Créez une nouvelle matière avec ses paramètres et associations</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.matieres.index') }}" class="btn-acasi secondary">
                <i class="fas fa-list me-1"></i>Liste des matières
            </a>
        </div>
    </div>

    <!-- Error Alert -->
    @if(session('error'))
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--danger);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle color-danger me-2"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Pre-selection Info Alert -->
    @if(isset($preselectedFiliereId) || isset($preselectedNiveauId))
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--success);">
            <div class="p-lg">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle color-success me-2 mt-1"></i>
                    <div>
                        <strong>Combinaison pré-sélectionnée</strong>
                        <p class="mb-0 mt-1">Cette matière sera automatiquement associée à :
                            @if(isset($preselectedFiliereId))
                                @php
                                    $selectedFiliere = $filieres->firstWhere('id', $preselectedFiliereId);
                                @endphp
                                <strong>{{ $selectedFiliere ? $selectedFiliere->name : "Filière ID $preselectedFiliereId" }}</strong>
                            @endif
                            @if(isset($preselectedFiliereId) && isset($preselectedNiveauId)) et @endif
                            @if(isset($preselectedNiveauId))
                                @php
                                    $selectedNiveau = $niveauxEtudes->firstWhere('id', $preselectedNiveauId);
                                @endphp
                                <strong>{{ $selectedNiveau ? $selectedNiveau->name : "Niveau ID $preselectedNiveauId" }}</strong>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Form Container -->
    <form action="{{ route('esbtp.matieres.store') }}" method="POST">
        @csrf

        <!-- Informations générales -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-info-circle"></i>Informations générales
                        </h3>
                        <p class="main-card-subtitle">Code et nom de la matière</p>
                    </div>
                    <div class="main-card-body">
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
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-sliders-h"></i>Paramètres d'évaluation
                        </h3>
                        <p class="main-card-subtitle">Coefficient et volume horaire</p>
                    </div>
                    <div class="main-card-body">
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

        <!-- Associations -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-link"></i>Associations
                        </h3>
                        <p class="main-card-subtitle">Filières et niveaux d'étude</p>
                    </div>
                    <div class="main-card-body">
                                        <!-- Filières associées (multi-sélection) -->
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-graduation-cap me-1"></i>Filières
                                                <small class="text-muted">(Sélection multiple autorisée)</small>
                                            </label>
                                            <div class="border rounded p-3 @error('filieres') is-invalid @enderror" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($filieres as $filiere)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input filiere-check" type="checkbox" 
                                                           value="{{ $filiere->id }}" 
                                                           id="create_filiere_{{ $filiere->id }}" 
                                                           name="filieres[]"
                                                           {{ in_array($filiere->id, old('filieres', isset($preselectedFiliereId) ? [$preselectedFiliereId] : [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="create_filiere_{{ $filiere->id }}">
                                                        <strong>{{ $filiere->name }}</strong>
                                                        @if($filiere->code)
                                                            <small class="text-muted">({{ $filiere->code }})</small>
                                                        @endif
                                                        @if(isset($preselectedFiliereId) && $filiere->id == $preselectedFiliereId)
                                                            <span class="badge bg-success ms-2"><i class="fas fa-star"></i> Pré-sélectionnée</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                            @error('filieres')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Niveaux d'étude associés (multi-sélection) -->
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-layer-group me-1"></i>Niveaux d'étude
                                                <small class="text-muted">(Sélection multiple autorisée)</small>
                                            </label>
                                            <div class="border rounded p-3 @error('niveaux') is-invalid @enderror" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($niveauxEtudes as $niveau)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input niveau-check" type="checkbox" 
                                                           value="{{ $niveau->id }}" 
                                                           id="create_niveau_{{ $niveau->id }}" 
                                                           name="niveaux[]"
                                                           {{ in_array($niveau->id, old('niveaux', isset($preselectedNiveauId) ? [$preselectedNiveauId] : [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="create_niveau_{{ $niveau->id }}">
                                                        <strong>{{ $niveau->name }}</strong>
                                                        @if($niveau->code)
                                                            <small class="text-muted">({{ $niveau->code }})</small>
                                                        @endif
                                                        @if(isset($preselectedNiveauId) && $niveau->id == $preselectedNiveauId)
                                                            <span class="badge bg-success ms-2"><i class="fas fa-star"></i> Pré-sélectionné</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                            @error('niveaux')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Aperçu des combinaisons -->
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-eye me-1"></i>Aperçu des combinaisons
                                            </label>
                                            <div id="create-combinations-preview" class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Sélectionnez des filières et des niveaux pour voir les combinaisons possibles.
                                            </div>
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
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-align-left"></i>Description et options
                        </h3>
                        <p class="main-card-subtitle">Informations complémentaires</p>
                    </div>
                    <div class="main-card-body">
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

        <!-- Action Buttons -->
        <div class="card-moderne">
            <div class="p-lg text-center">
                <button type="reset" class="btn-acasi secondary me-2">
                    <i class="fas fa-undo me-1"></i>Réinitialiser
                </button>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save me-1"></i>Enregistrer la matière
                </button>
            </div>
        </div>
    </form>
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

        // ===== GESTION DE L'APERÇU DES COMBINAISONS =====
        
        // Fonction pour mettre à jour l'aperçu des combinaisons
        function updateCreateCombinationsPreview() {
            const selectedFilieres = [];
            const selectedNiveaux = [];
            
            $('.filiere-check:checked').each(function() {
                const label = $(this).next('label').find('strong').text();
                selectedFilieres.push({
                    id: $(this).val(),
                    name: label
                });
            });
            
            $('.niveau-check:checked').each(function() {
                const label = $(this).next('label').find('strong').text();
                selectedNiveaux.push({
                    id: $(this).val(),
                    name: label
                });
            });
            
            const previewDiv = $('#create-combinations-preview');
            
            if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                previewDiv.html(`
                    <i class="fas fa-info-circle me-2"></i>
                    Sélectionnez au moins une filière et un niveau pour voir les combinaisons possibles.
                `).removeClass('alert-success').addClass('alert-info');
                return;
            }
            
            let combinationsHtml = `
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <strong>${selectedFilieres.length * selectedNiveaux.length} combinaison(s) sélectionnée(s)</strong>
                </div>
                <div class="row">
            `;
            
            selectedFilieres.forEach(filiere => {
                selectedNiveaux.forEach(niveau => {
                    combinationsHtml += `
                        <div class="col-md-4 mb-2">
                            <div class="badge bg-primary text-wrap p-2">
                                <i class="fas fa-link me-1"></i>
                                ${filiere.name} ↔ ${niveau.name}
                            </div>
                        </div>
                    `;
                });
            });
            
            combinationsHtml += '</div>';
            
            previewDiv.html(combinationsHtml).removeClass('alert-info').addClass('alert-success');
        }

        // Écouter les changements dans les checkboxes
        $(document).on('change', '.filiere-check, .niveau-check', updateCreateCombinationsPreview);
        
        // Mise à jour initiale
        updateCreateCombinationsPreview();
    });
</script>
@endsection
