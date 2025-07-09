@extends('layouts.app')

@section('title', 'Modifier le niveau : ' . $niveauxEtude->name)

@section('styles')
<style>
.edit-wizard {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
    border-radius: 0 0 25px 25px;
    position: relative;
    overflow: hidden;
}

.edit-wizard::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50" cy="50" r="50"><stop offset="0" stop-color="rgba(255,255,255,.15)"/><stop offset="100" stop-color="rgba(255,255,255,0)"/></radialGradient></defs><circle cx="25" cy="25" r="25" fill="url(%23a)"/></svg>') repeat;
    opacity: 0.6;
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

.form-container {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
    border: 3px solid transparent;
    background-clip: padding-box;
}

.form-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #4facfe, #00f2fe, #4facfe);
    background-size: 200% 100%;
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { background-position: -200% 0; }
    50% { background-position: 200% 0; }
}

.form-group {
    margin-bottom: 25px;
    position: relative;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 10px;
    display: block;
    position: relative;
}

.form-label::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, #4facfe, #00f2fe);
    border-radius: 1px;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-control:focus {
    border-color: #4facfe;
    box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
    background: white;
    transform: translateY(-2px);
}

.form-control:hover {
    border-color: #00f2fe;
    background: white;
}

.form-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-select:focus {
    border-color: #4facfe;
    box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
    background: white;
}

.btn-modern {
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border: none;
}

.btn-modern::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-modern:hover::before {
    width: 300px;
    height: 300px;
}

.btn-update-modern {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
}

.btn-update-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(79, 172, 254, 0.6);
}

.btn-secondary-modern {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
}

.btn-secondary-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(108, 117, 125, 0.6);
}

.btn-danger-modern {
    background: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
}

.btn-danger-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(255, 107, 107, 0.6);
}

.form-check {
    margin-bottom: 15px;
}

.form-check-input {
    width: 20px;
    height: 20px;
    border: 2px solid #4facfe;
    border-radius: 6px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.form-check-input:checked {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border-color: #4facfe;
    transform: scale(1.1);
}

.form-check-label {
    font-weight: 500;
    color: #495057;
    margin-left: 10px;
    cursor: pointer;
}

.progress-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 30px;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 10px;
    position: relative;
    box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
}

.step.active {
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); box-shadow: 0 8px 25px rgba(79, 172, 254, 0.6); }
}

.field-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #4facfe;
    font-size: 1.2rem;
    pointer-events: none;
}

.form-group-icon {
    position: relative;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
    font-weight: 500;
}

.alert-modern {
    border: none;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 5px;
    font-style: italic;
}

.changes-indicator {
    position: fixed;
    top: 100px;
    right: 30px;
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 25px;
    box-shadow: 0 8px 25px rgba(250, 112, 154, 0.4);
    opacity: 0;
    transform: translateX(100px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.changes-indicator.show {
    opacity: 1;
    transform: translateX(0);
}

.info-card {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border: none;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
    color: #495057;
}

.comparison-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.comparison-card:hover {
    border-color: #4facfe;
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.15);
}

.original-value {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 8px;
    border-left: 4px solid #6c757d;
    margin-bottom: 10px;
}

.current-value {
    background: #e3f2fd;
    padding: 8px 12px;
    border-radius: 8px;
    border-left: 4px solid #4facfe;
}

.floating-actions {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    z-index: 1000;
}

.fab-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fab-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
}

.fab-save {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.fab-reset {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.fab-delete {
    background: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);
}
</style>
@endsection

@section('content')
<!-- Header Section -->
<div class="edit-wizard">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 mb-0">
                    <i class="fas fa-edit me-3"></i>
                    Modifier le Niveau d'Étude
                </h1>
                <p class="lead mb-0 mt-2 opacity-75">Modifiez les informations du niveau : <strong>{{ $niveauxEtude->name }}</strong></p>
            </div>
            <div class="col-lg-4 text-end">
                <div class="progress-indicator">
                    <div class="step active">
                        <i class="fas fa-edit"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Changes Indicator -->
<div class="changes-indicator" id="changesIndicator">
    <i class="fas fa-exclamation-circle me-2"></i>
    <span>Modifications détectées</span>
</div>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <!-- Information Card -->
            <div class="info-card">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Modification du niveau d'étude</h6>
                        <p class="mb-0 small">
                            Créé le {{ $niveauxEtude->created_at->format('d/m/Y à H:i') }} -
                            Dernière modification le {{ $niveauxEtude->updated_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-container">
                <!-- Form Header -->
                <div class="text-center mb-4">
                    <h4 class="mb-2">Informations du Niveau d'Étude</h4>
                    <p class="text-muted">Modifiez les informations ci-dessous</p>
                </div>

                <!-- Success/Error Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Erreur :</strong> Veuillez corriger les erreurs ci-dessous.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Form -->
                <form action="{{ route('esbtp.niveaux-etudes.update', $niveauxEtude) }}" method="POST" id="editForm" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Nom -->
                        <div class="col-md-6">
                            <div class="form-group form-group-icon">
                                <label for="name" class="form-label required">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    Nom du niveau
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $niveauxEtude->name) }}"
                                       placeholder="Ex: BTS Première Année"
                                       data-original="{{ $niveauxEtude->name }}"
                                       required>
                                <i class="fas fa-signature field-icon"></i>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Le nom complet du niveau d'étude</div>
                            </div>
                        </div>

                        <!-- Code -->
                        <div class="col-md-6">
                            <div class="form-group form-group-icon">
                                <label for="code" class="form-label required">
                                    <i class="fas fa-code me-1"></i>
                                    Code du niveau
                                </label>
                                <input type="text"
                                       class="form-control @error('code') is-invalid @enderror"
                                       id="code"
                                       name="code"
                                       value="{{ old('code', $niveauxEtude->code) }}"
                                       placeholder="Ex: BTS1"
                                       data-original="{{ $niveauxEtude->code }}"
                                       required>
                                <i class="fas fa-hashtag field-icon"></i>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Code court pour identifier le niveau</div>
                            </div>
                        </div>

                        <!-- Type -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type" class="form-label">
                                    <i class="fas fa-tag me-1"></i>
                                    Type de formation
                                </label>
                                <select class="form-select @error('type') is-invalid @enderror"
                                        id="type"
                                        name="type"
                                        data-original="{{ $niveauxEtude->type }}">
                                    <option value="">-- Sélectionner un type --</option>
                                    <option value="BTS" {{ old('type', $niveauxEtude->type) == 'BTS' ? 'selected' : '' }}>BTS</option>
                                    <option value="Licence" {{ old('type', $niveauxEtude->type) == 'Licence' ? 'selected' : '' }}>Licence</option>
                                    <option value="Master" {{ old('type', $niveauxEtude->type) == 'Master' ? 'selected' : '' }}>Master</option>
                                    <option value="Doctorat" {{ old('type', $niveauxEtude->type) == 'Doctorat' ? 'selected' : '' }}>Doctorat</option>
                                    <option value="Diplôme" {{ old('type', $niveauxEtude->type) == 'Diplôme' ? 'selected' : '' }}>Diplôme</option>
                                    <option value="Certificat" {{ old('type', $niveauxEtude->type) == 'Certificat' ? 'selected' : '' }}>Certificat</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Type de formation ou de diplôme</div>
                            </div>
                        </div>

                        <!-- Année -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="year" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Année d'étude
                                </label>
                                <select class="form-select @error('year') is-invalid @enderror"
                                        id="year"
                                        name="year"
                                        data-original="{{ $niveauxEtude->year }}">
                                    <option value="">-- Sélectionner une année --</option>
                                    <option value="1" {{ old('year', $niveauxEtude->year) == '1' ? 'selected' : '' }}>1ère année</option>
                                    <option value="2" {{ old('year', $niveauxEtude->year) == '2' ? 'selected' : '' }}>2ème année</option>
                                    <option value="3" {{ old('year', $niveauxEtude->year) == '3' ? 'selected' : '' }}>3ème année</option>
                                    <option value="4" {{ old('year', $niveauxEtude->year) == '4' ? 'selected' : '' }}>4ème année</option>
                                    <option value="5" {{ old('year', $niveauxEtude->year) == '5' ? 'selected' : '' }}>5ème année</option>
                                </select>
                                @error('year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Année du cursus d'étude</div>
                            </div>
                        </div>

                        <!-- Libellé -->
                        <div class="col-12">
                            <div class="form-group form-group-icon">
                                <label for="libelle" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>
                                    Libellé complet
                                </label>
                                <input type="text"
                                       class="form-control @error('libelle') is-invalid @enderror"
                                       id="libelle"
                                       name="libelle"
                                       value="{{ old('libelle', $niveauxEtude->libelle) }}"
                                       placeholder="Ex: Brevet de Technicien Supérieur - Première Année"
                                       data-original="{{ $niveauxEtude->libelle }}">
                                <i class="fas fa-text-width field-icon"></i>
                                @error('libelle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Libellé détaillé du niveau d'étude (optionnel)</div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="description" class="form-label">
                                    <i class="fas fa-file-alt me-1"></i>
                                    Description
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description"
                                          name="description"
                                          rows="4"
                                          placeholder="Description détaillée du niveau d'étude..."
                                          data-original="{{ $niveauxEtude->description }}">{{ old('description', $niveauxEtude->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Description détaillée du niveau d'étude (optionnel)</div>
                            </div>
                        </div>

                        <!-- Statut -->
                        <div class="col-12">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           {{ old('is_active', $niveauxEtude->is_active ?? true) ? 'checked' : '' }}
                                           data-original="{{ $niveauxEtude->is_active ?? 1 }}">
                                    <label class="form-check-label" for="is_active">
                                        <i class="fas fa-toggle-on me-1"></i>
                                        Niveau actif
                                    </label>
                                </div>
                                <div class="form-text">Décochez pour désactiver temporairement ce niveau</div>
                            </div>
                        </div>
                    </div>

                    <!-- Comparison Cards -->
                    <div id="comparisonSection" style="display: none;">
                        <h5 class="mt-4 mb-3">
                            <i class="fas fa-exchange-alt me-2"></i>
                            Modifications détectées
                        </h5>
                        <div id="comparisonCards"></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="{{ route('esbtp.niveaux-etudes.show', $niveauxEtude) }}"
                           class="btn btn-secondary-modern me-2">
                            <i class="fas fa-arrow-left me-2"></i>
                            Retour
                        </a>

                        <button type="button"
                                class="btn btn-danger-modern me-2"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-2"></i>
                            Supprimer
                        </button>

                        <button type="button"
                                class="btn btn-secondary-modern me-2"
                                id="resetBtn">
                            <i class="fas fa-undo me-2"></i>
                            Réinitialiser
                        </button>

                        <button type="submit" class="btn btn-update-modern">
                            <i class="fas fa-save me-2"></i>
                            Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Buttons -->
<div class="floating-actions">
    <button type="button" class="fab-btn fab-save" title="Enregistrer" onclick="document.getElementById('editForm').submit();">
        <i class="fas fa-save"></i>
    </button>
    <button type="button" class="fab-btn fab-reset" title="Réinitialiser" id="fabReset">
        <i class="fas fa-undo"></i>
    </button>
    <button type="button" class="fab-btn fab-delete" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal">
        <i class="fas fa-trash"></i>
    </button>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-trash fa-4x text-danger mb-3"></i>
                <h6>Êtes-vous sûr de vouloir supprimer ce niveau d'étude ?</h6>
                <p class="text-muted mb-3">{{ $niveauxEtude->name }}</p>

                @if($niveauxEtude->filieres->count() > 0 || $niveauxEtude->matieres->count() > 0 || $niveauxEtude->classes->count() > 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Ce niveau d'étude possède des données associées qui seront également affectées.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <form action="{{ route('esbtp.niveaux-etudes.destroy', $niveauxEtude) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Confirmer
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
    let hasChanges = false;

    // Animation d'entrée pour le formulaire
    $('.form-container').css('opacity', '0').css('transform', 'translateY(30px)');
    setTimeout(() => {
        $('.form-container').animate({
            opacity: 1
        }, 800).css('transform', 'translateY(0)');
    }, 300);

    // Détecter les changements
    function checkForChanges() {
        hasChanges = false;
        let changes = [];

        $('input, select, textarea').each(function() {
            let field = $(this);
            let original = field.data('original');
            let current = field.is(':checkbox') ? (field.is(':checked') ? '1' : '0') : field.val();

            if (String(original) !== String(current)) {
                hasChanges = true;
                changes.push({
                    name: field.attr('name'),
                    label: field.closest('.form-group').find('.form-label').text().replace('*', '').trim(),
                    original: original || 'Vide',
                    current: current || 'Vide'
                });
            }
        });

        // Afficher l'indicateur de changements
        if (hasChanges) {
            $('#changesIndicator').addClass('show');
            showComparison(changes);
        } else {
            $('#changesIndicator').removeClass('show');
            hideComparison();
        }
    }

    // Afficher la comparaison
    function showComparison(changes) {
        let html = '';
        changes.forEach(function(change) {
            html += `
                <div class="comparison-card">
                    <h6>${change.label}</h6>
                    <div class="original-value">
                        <small class="text-muted">Valeur actuelle :</small><br>
                        <strong>${change.original}</strong>
                    </div>
                    <div class="current-value">
                        <small class="text-muted">Nouvelle valeur :</small><br>
                        <strong>${change.current}</strong>
                    </div>
                </div>
            `;
        });

        $('#comparisonCards').html(html);
        $('#comparisonSection').slideDown();
    }

    // Masquer la comparaison
    function hideComparison() {
        $('#comparisonSection').slideUp();
    }

    // Event listeners pour détecter les changements
    $('input, select, textarea').on('input change', function() {
        setTimeout(checkForChanges, 100);
    });

    // Réinitialiser le formulaire
    function resetForm() {
        $('input, select, textarea').each(function() {
            let field = $(this);
            let original = field.data('original');

            if (field.is(':checkbox')) {
                field.prop('checked', original == '1');
            } else {
                field.val(original || '');
            }
        });

        checkForChanges();

        Swal.fire({
            icon: 'success',
            title: 'Formulaire réinitialisé',
            text: 'Les valeurs originales ont été restaurées.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Event listeners pour les boutons de réinitialisation
    $('#resetBtn, #fabReset').on('click', resetForm);

    // Validation en temps réel
    $('.form-control, .form-select').on('blur', function() {
        let field = $(this);
        if (field.prop('required') && !field.val()) {
            field.addClass('is-invalid');
        } else {
            field.removeClass('is-invalid');
        }
    });

    // Animation pour les champs focus
    $('.form-control, .form-select').on('focus', function() {
        $(this).parent('.form-group').find('.form-label').addClass('text-primary');
    }).on('blur', function() {
        $(this).parent('.form-group').find('.form-label').removeClass('text-primary');
    });

    // Confirmation avant soumission
    $('#editForm').on('submit', function(e) {
        if (!hasChanges) {
            e.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'Aucune modification',
                text: 'Aucune modification n\'a été détectée.',
                confirmButtonColor: '#4facfe'
            });
            return false;
        }

        let requiredFields = ['name', 'code'];
        let hasErrors = false;

        requiredFields.forEach(function(fieldName) {
            let field = $('#' + fieldName);
            if (!field.val()) {
                field.addClass('is-invalid');
                hasErrors = true;
            }
        });

        if (hasErrors) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Erreur de validation',
                text: 'Veuillez remplir tous les champs obligatoires.',
                confirmButtonColor: '#4facfe'
            });
            return false;
        }

        // Animation du bouton pendant la soumission
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mise à jour...');
    });

    // Warning avant de quitter la page avec des changements non sauvegardés
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Tooltip pour les boutons
    $('[title]').tooltip();

    // Animation pour les floating buttons
    $('.fab-btn').hover(
        function() {
            $(this).find('i').addClass('fa-spin');
        },
        function() {
            $(this).find('i').removeClass('fa-spin');
        }
    );

    // Check initial
    checkForChanges();
});
</script>

<!-- SweetAlert2 pour les notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
