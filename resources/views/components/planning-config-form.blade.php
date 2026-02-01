{{-- Composant formulaire de configuration planning adaptatif --}}
@props([
    'mode' => 'rapide', // 'rapide' ou 'avance'
    'context' => 'modal', // 'modal' ou 'page'
    'matiere' => null,
    'anneeSelectionnee' => null,
    'filiereId' => null,
    'niveauId' => null,
    'planification' => null,
    'enseignants' => collect(),
    'showModeToggle' => true
])

@php
    $isRapide = $mode === 'rapide';
    $isModal = $context === 'modal';
    $isEdit = !is_null($planification);
    $formId = $isModal ? 'configureForm' : 'planningConfigForm';
@endphp

<form id="{{ $formId }}" method="POST" 
      action="{{ $isRapide ? route('esbtp.planning-general.configure-rapide') : route('esbtp.planning-general.configure-avance') }}">
    @csrf
    
    <!-- Champs cachés -->
    <input type="hidden" name="matiere_id" value="{{ $matiere?->id }}">
    <input type="hidden" name="annee_id" value="{{ $anneeSelectionnee?->id }}">
    <input type="hidden" name="filiere_id" value="{{ $filiereId }}">
    <input type="hidden" name="niveau_id" value="{{ $niveauId }}">
    @if($isEdit)
        <input type="hidden" name="planification_id" value="{{ $planification->id }}">
        @method('PUT')
    @endif

    <!-- Toggle Mode (si activé) -->
    @if($showModeToggle && $isModal)
        <div class="config-mode-toggle mb-4">
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="config-mode" id="mode-rapide" 
                       {{ $isRapide ? 'checked' : '' }} data-mode="rapide">
                <label class="btn btn-outline-primary" for="mode-rapide">
                    <i class="fas fa-bolt me-1"></i>Configuration Rapide
                </label>

                <input type="radio" class="btn-check" name="config-mode" id="mode-avance" 
                       {{ !$isRapide ? 'checked' : '' }} data-mode="avance">
                <label class="btn btn-outline-primary" for="mode-avance">
                    <i class="fas fa-cogs me-1"></i>Configuration Avancée
                </label>
            </div>
        </div>
    @endif

    <!-- Section Informations Contextuelles -->
    <div class="config-context mb-4">
        <div class="card-moderne" style="background: var(--info-light); border: 1px solid var(--info);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle color-info me-2"></i>
                    <div>
                        <strong>Configuration pour :</strong>
                        <span class="ms-2">
                            @if($matiere)
                                <span class="badge primary">{{ $matiere->name }}</span>
                            @endif
                            @if($anneeSelectionnee)
                                <span class="badge secondary">{{ $anneeSelectionnee->name }}</span>
                            @endif
                            @if($filiereId && $niveauId)
                                <span class="badge secondary">Spécifique</span>
                            @else
                                <span class="badge secondary">Toutes combinaisons</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire Mode Rapide -->
    <div id="section-rapide" class="{{ $isRapide ? '' : 'd-none' }}">
        <div class="row">
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-clock"></i>Volume Horaire
                        </h3>
                        <p class="main-card-subtitle">Configuration de base</p>
                    </div>
                    <div class="main-card-body">
                        <div class="mb-3">
                            <label for="volume_horaire" class="form-label">
                                <i class="fas fa-clock me-1"></i>Volume horaire total (heures) <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="volume_horaire" name="volume_horaire" 
                                   value="{{ old('volume_horaire', $planification?->volume_horaire_total) }}" 
                                   min="0.5" max="500" step="0.5" required>
                            @error('volume_horaire')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="semestre" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Période
                            </label>
                            <select class="form-select" id="semestre" name="semestre">
                                <option value="1" {{ old('semestre', $planification?->semestre) == 1 ? 'selected' : '' }}>
                                    Semestre 1
                                </option>
                                <option value="2" {{ old('semestre', $planification?->semestre) == 2 ? 'selected' : '' }}>
                                    Semestre 2
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-calculator"></i>Calcul Automatique
                        </h3>
                        <p class="main-card-subtitle">Répartition suggérée</p>
                    </div>
                    <div class="main-card-body">
                        <div id="calcul-automatique" class="text-center py-3">
                            <div class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Saisissez le volume horaire pour voir la répartition suggérée
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire Mode Avancé -->
    <div id="section-avance" class="{{ !$isRapide ? '' : 'd-none' }}">
        <div class="row">
            <!-- Volume Horaire Détaillé -->
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-chart-bar"></i>Volumes Horaires Détaillés
                        </h3>
                        <p class="main-card-subtitle">Répartition CM/TD/TP</p>
                    </div>
                    <div class="main-card-body">
                        <div class="mb-3">
                            <label for="volume_horaire_total" class="form-label">
                                Volume horaire total <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="volume_horaire_total" name="volume_horaire_total" 
                                   value="{{ old('volume_horaire_total', $planification?->volume_horaire_total) }}" 
                                   min="1" max="500" step="0.5" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-4">
                                <label for="volume_horaire_cm" class="form-label">CM</label>
                                <input type="number" class="form-control" id="volume_horaire_cm" name="volume_horaire_cm" 
                                       value="{{ old('volume_horaire_cm', $planification?->volume_horaire_cm) }}" 
                                       min="0" step="0.5">
                            </div>
                            <div class="col-4">
                                <label for="volume_horaire_td" class="form-label">TD</label>
                                <input type="number" class="form-control" id="volume_horaire_td" name="volume_horaire_td" 
                                       value="{{ old('volume_horaire_td', $planification?->volume_horaire_td) }}" 
                                       min="0" step="0.5">
                            </div>
                            <div class="col-4">
                                <label for="volume_horaire_tp" class="form-label">TP</label>
                                <input type="number" class="form-control" id="volume_horaire_tp" name="volume_horaire_tp" 
                                       value="{{ old('volume_horaire_tp', $planification?->volume_horaire_tp) }}" 
                                       min="0" step="0.5">
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <small id="volume-validation" class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                La somme CM + TD + TP doit être ≤ au volume total
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Paramètres Académiques -->
            <div class="col-md-6">
                <div class="card-moderne">
                    <div class="main-card-header">
                        <h3 class="main-card-title">
                            <i class="fas fa-graduation-cap"></i>Paramètres Académiques
                        </h3>
                        <p class="main-card-subtitle">Coefficient et crédits</p>
                    </div>
                    <div class="main-card-body">
                        <div class="mb-3">
                            <label for="coefficient" class="form-label">Coefficient</label>
                            <input type="number" class="form-control" id="coefficient" name="coefficient" 
                                   value="{{ old('coefficient', $planification?->coefficient ?? $matiere?->coefficient_default) }}" 
                                   min="0.5" max="10" step="0.5">
                        </div>
                        
                        <div class="mb-3">
                            <label for="credits_ects" class="form-label">Crédits ECTS</label>
                            <input type="number" class="form-control" id="credits_ects" name="credits_ects" 
                                   value="{{ old('credits_ects', $planification?->credits_ects) }}" 
                                   min="1" max="30">
                        </div>
                        
                        <div class="mb-3">
                            <label for="enseignant_principal_id" class="form-label">Enseignant Principal</label>
                            <select class="form-select" id="enseignant_principal_id" name="enseignant_principal_id">
                                <option value="">Sélectionner un enseignant</option>
                                @foreach($enseignants as $enseignant)
                                    <option value="{{ $enseignant->id }}" 
                                            {{ old('enseignant_principal_id', $planification?->enseignant_principal_id) == $enseignant->id ? 'selected' : '' }}>
                                        {{ $enseignant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$isModal)
        <!-- Boutons pour page complète -->
        <div class="card-moderne mt-4">
            <div class="p-lg text-center">
                <button type="reset" class="btn-acasi secondary me-2">
                    <i class="fas fa-undo me-1"></i>Réinitialiser
                </button>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save me-1"></i>{{ $isEdit ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
            </div>
        </div>
    @endif
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formId = '{{ $formId }}';
    const form = document.getElementById(formId);

    const volumeInput = document.getElementById('volume_horaire');
    const volumeTotal = document.getElementById('volume_horaire_total');
    const syncRequiredFields = (mode) => {
        if (volumeInput) {
            if (mode === 'rapide') {
                volumeInput.setAttribute('required', 'required');
            } else {
                volumeInput.removeAttribute('required');
            }
        }
        if (volumeTotal) {
            if (mode === 'avance') {
                volumeTotal.setAttribute('required', 'required');
            } else {
                volumeTotal.removeAttribute('required');
            }
        }
    };
    
    // Toggle entre modes
    @if($showModeToggle && $isModal)
    const modeRadios = document.querySelectorAll('input[name="config-mode"]');
    const sectionRapide = document.getElementById('section-rapide');
    const sectionAvance = document.getElementById('section-avance');
    
    modeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const mode = this.dataset.mode;
            if (mode === 'rapide') {
                sectionRapide.classList.remove('d-none');
                sectionAvance.classList.add('d-none');
                // Modifier l'action du formulaire
                form.action = '{{ route("esbtp.planning-general.configure-rapide") }}';
            } else {
                sectionRapide.classList.add('d-none');
                sectionAvance.classList.remove('d-none');
                form.action = '{{ route("esbtp.planning-general.configure-avance") }}';
            }
            syncRequiredFields(mode);
        });
    });

    const activeMode = document.querySelector('input[name="config-mode"]:checked')?.dataset.mode || 'rapide';
    syncRequiredFields(activeMode);
    @endif
    
    // Calcul automatique pour mode rapide
    if (volumeInput) {
        volumeInput.addEventListener('input', function() {
            const volume = parseFloat(this.value) || 0;
            const calculDiv = document.getElementById('calcul-automatique');
            
            if (volume > 0) {
                const cm = Math.round(volume * 0.4 * 10) / 10;
                const td = Math.round(volume * 0.4 * 10) / 10;
                const tp = Math.round(volume * 0.2 * 10) / 10;
                
                calculDiv.innerHTML = `
                    <div class="text-success">
                        <div class="fw-bold mb-2">Répartition suggérée :</div>
                        <div class="d-flex justify-content-around">
                            <div><strong>CM:</strong> ${cm}h</div>
                            <div><strong>TD:</strong> ${td}h</div>
                            <div><strong>TP:</strong> ${tp}h</div>
                        </div>
                    </div>
                `;
            } else {
                calculDiv.innerHTML = `
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Saisissez le volume horaire pour voir la répartition suggérée
                    </div>
                `;
            }
        });
    }
    
    // Validation volumes en mode avancé
    const volumeCM = document.getElementById('volume_horaire_cm');
    const volumeTD = document.getElementById('volume_horaire_td');
    const volumeTP = document.getElementById('volume_horaire_tp');
    const validationMsg = document.getElementById('volume-validation');
    
    function validateVolumes() {
        if (!volumeTotal || !validationMsg) return;
        
        const total = parseFloat(volumeTotal.value) || 0;
        const cm = parseFloat(volumeCM?.value) || 0;
        const td = parseFloat(volumeTD?.value) || 0;
        const tp = parseFloat(volumeTP?.value) || 0;
        const somme = cm + td + tp;
        
        if (total > 0 && somme > total) {
            validationMsg.innerHTML = `
                <i class="fas fa-exclamation-triangle me-1"></i>
                <span class="text-danger">Somme (${somme}h) > Total (${total}h)</span>
            `;
        } else if (total > 0) {
            validationMsg.innerHTML = `
                <i class="fas fa-check-circle me-1"></i>
                <span class="text-success">Répartition valide (${somme}h / ${total}h)</span>
            `;
        }
    }
    
    [volumeTotal, volumeCM, volumeTD, volumeTP].forEach(input => {
        if (input) {
            input.addEventListener('input', validateVolumes);
        }
    });
    
    // Validation initiale
    validateVolumes();
});
</script>
@endpush
