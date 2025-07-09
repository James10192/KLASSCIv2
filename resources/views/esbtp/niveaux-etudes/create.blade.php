@extends('layouts.app')

@section('title', 'Créer un nouveau niveau d\'étude - ESBTP-yAKRO')

@section('styles')
<style>
.form-wizard {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
    border-radius: 0 0 25px 25px;
    position: relative;
    overflow: hidden;
}

.form-wizard::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(255,255,255,0.1) 10px,
        rgba(255,255,255,0.1) 20px
    );
    animation: slide 20s linear infinite;
}

@keyframes slide {
    0% { transform: translateX(-50%) translateY(-50%) rotate(0deg); }
    100% { transform: translateX(-50%) translateY(-50%) rotate(360deg); }
}

.form-container {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.form-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
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
    background: linear-gradient(90deg, #667eea, #764ba2);
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
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    background: white;
    transform: translateY(-2px);
}

.form-control:hover {
    border-color: #764ba2;
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
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-primary-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.6);
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

.form-check {
    margin-bottom: 15px;
}

.form-check-input {
    width: 20px;
    height: 20px;
    border: 2px solid #667eea;
    border-radius: 6px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.form-check-input:checked {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 10px;
    position: relative;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.step.active {
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6); }
}

.floating-help {
    position: fixed;
    bottom: 30px;
    left: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border: none;
    font-size: 1.5rem;
    box-shadow: 0 8px 25px rgba(67, 233, 123, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
}

.floating-help:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(67, 233, 123, 0.6);
}

.field-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #667eea;
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

.card-preview {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);
}

.form-floating {
    position: relative;
}

.form-floating > .form-control {
    height: calc(3.5rem + 2px);
    padding: 1rem 0.75rem;
}

.form-floating > label {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    padding: 1rem 0.75rem;
    pointer-events: none;
    border: 1px solid transparent;
    transform-origin: 0 0;
    transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
    opacity: 0.65;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}
</style>
@endsection

@section('content')
<!-- Header Section -->
<div class="form-wizard">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 mb-0">
                    <i class="fas fa-plus-circle me-3"></i>
                    Créer un Nouveau Niveau d'Étude
                </h1>
                <p class="lead mb-0 mt-2 opacity-75">Ajoutez un nouveau niveau d'étude à votre établissement</p>
            </div>
            <div class="col-lg-4 text-end">
                <div class="progress-indicator">
                    <div class="step active">1</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="form-container">
                <!-- Form Header -->
                <div class="text-center mb-4">
                    <h4 class="mb-2">Informations du Niveau d'Étude</h4>
                    <p class="text-muted">Remplissez les informations ci-dessous pour créer un nouveau niveau d'étude</p>
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
                <form action="{{ route('esbtp.niveaux-etudes.store') }}" method="POST" id="niveauForm" novalidate>
                        @csrf

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
                                       value="{{ old('name') }}"
                                       placeholder="Ex: BTS Première Année"
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
                                       value="{{ old('code') }}"
                                       placeholder="Ex: BTS1"
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
                                        name="type">
                                    <option value="">-- Sélectionner un type --</option>
                                    <option value="BTS" {{ old('type') == 'BTS' ? 'selected' : '' }}>BTS</option>
                                    <option value="Licence" {{ old('type') == 'Licence' ? 'selected' : '' }}>Licence</option>
                                    <option value="Master" {{ old('type') == 'Master' ? 'selected' : '' }}>Master</option>
                                    <option value="Doctorat" {{ old('type') == 'Doctorat' ? 'selected' : '' }}>Doctorat</option>
                                    <option value="Diplôme" {{ old('type') == 'Diplôme' ? 'selected' : '' }}>Diplôme</option>
                                    <option value="Certificat" {{ old('type') == 'Certificat' ? 'selected' : '' }}>Certificat</option>
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
                                        name="year">
                                    <option value="">-- Sélectionner une année --</option>
                                    <option value="1" {{ old('year') == '1' ? 'selected' : '' }}>1ère année</option>
                                    <option value="2" {{ old('year') == '2' ? 'selected' : '' }}>2ème année</option>
                                    <option value="3" {{ old('year') == '3' ? 'selected' : '' }}>3ème année</option>
                                    <option value="4" {{ old('year') == '4' ? 'selected' : '' }}>4ème année</option>
                                    <option value="5" {{ old('year') == '5' ? 'selected' : '' }}>5ème année</option>
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
                                       value="{{ old('libelle') }}"
                                       placeholder="Ex: Brevet de Technicien Supérieur - Première Année">
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
                                          placeholder="Description détaillée du niveau d'étude...">{{ old('description') }}</textarea>
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
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <i class="fas fa-toggle-on me-1"></i>
                                        Niveau actif
                                    </label>
                                </div>
                                <div class="form-text">Décochez pour désactiver temporairement ce niveau</div>
                                </div>
                            </div>
                        </div>

                    <!-- Preview Card -->
                    <div class="card-preview" id="previewCard" style="display: none;">
                        <h6 class="mb-3">
                            <i class="fas fa-eye me-2"></i>
                            Aperçu du niveau d'étude
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div><strong>Nom :</strong> <span id="preview-name">-</span></div>
                                <div><strong>Code :</strong> <span id="preview-code">-</span></div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>Type :</strong> <span id="preview-type">-</span></div>
                                <div><strong>Année :</strong> <span id="preview-year">-</span></div>
                            </div>
                        </div>
                        <div class="mt-2" id="preview-description-container" style="display: none;">
                            <strong>Description :</strong> <span id="preview-description">-</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="{{ route('esbtp.niveaux-etudes.index') }}"
                           class="btn btn-secondary-modern me-3">
                            <i class="fas fa-arrow-left me-2"></i>
                            Retour
                        </a>
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-save me-2"></i>
                            Créer le niveau
                        </button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>

<!-- Floating Help Button -->
<button class="floating-help" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Aide">
    <i class="fas fa-question"></i>
</button>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
    // Animation d'entrée pour le formulaire
    $('.form-container').css('opacity', '0').css('transform', 'translateY(30px)');
    setTimeout(() => {
        $('.form-container').animate({
            opacity: 1
        }, 800).css('transform', 'translateY(0)');
    }, 300);

    // Auto-génération du code à partir du nom
    $('#name').on('input', function() {
        let name = $(this).val();
        let code = name.replace(/\s+/g, '').substring(0, 6).toUpperCase();
        $('#code').val(code);
        updatePreview();
    });

    // Mise à jour de l'aperçu en temps réel
    function updatePreview() {
        let name = $('#name').val() || '-';
        let code = $('#code').val() || '-';
        let type = $('#type').val() || '-';
        let year = $('#year').val() ? $('#year option:selected').text() : '-';
        let description = $('#description').val();

        $('#preview-name').text(name);
        $('#preview-code').text(code);
        $('#preview-type').text(type);
        $('#preview-year').text(year);

        if (description) {
            $('#preview-description').text(description);
            $('#preview-description-container').show();
        } else {
            $('#preview-description-container').hide();
        }

        // Afficher l'aperçu si au moins le nom est rempli
        if (name !== '-') {
            $('#previewCard').slideDown();
        } else {
            $('#previewCard').slideUp();
        }
    }

    // Event listeners pour la mise à jour de l'aperçu
    $('#name, #code, #type, #year, #description').on('input change', updatePreview);

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

    // Tooltip pour le bouton d'aide
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Confirmation avant soumission
    $('#niveauForm').on('submit', function(e) {
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
                confirmButtonColor: '#667eea'
            });
            return false;
        }

        // Animation du bouton pendant la soumission
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Création...');
    });

    // Auto-focus sur le premier champ
    $('#name').focus();
    });
</script>

<!-- SweetAlert2 pour les notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
