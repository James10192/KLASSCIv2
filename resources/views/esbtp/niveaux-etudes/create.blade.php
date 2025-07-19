@extends('layouts.app')

@section('title', 'Créer un nouveau niveau d\'étude - ESBTP-yAKRO')

@section('content')
<div class="main-content">
    <!-- Header -->
    <div class="dashboard-header mb-xl">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-plus-circle"></i>
                Créer un Nouveau Niveau d'Étude
            </h1>
            <p class="header-subtitle">Ajoutez un nouveau niveau d'étude à votre établissement</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card-moderne p-xl">
        <div class="text-center mb-lg">
            <div class="section-title">Informations du Niveau d'Étude</div>
            <p style="color: var(--text-secondary);">Remplissez les informations ci-dessous pour créer un nouveau niveau d'étude</p>
        </div>
        <!-- Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-lg" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); border-radius: var(--radius-medium); padding: var(--space-md);">
                <div style="color: var(--success); font-weight: 600;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-lg" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-medium); padding: var(--space-md);">
                <div style="color: var(--danger); font-weight: 600;">
                    <i class="fas fa-exclamation-circle"></i> <strong>Erreur :</strong> Veuillez corriger les erreurs ci-dessous.
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('esbtp.niveaux-etudes.store') }}" method="POST" id="niveauForm" novalidate>
            @csrf

            <div class="row">
                <!-- Nom -->
                <div class="col-md-6 mb-lg">
                    <label for="name" style="color: var(--text-primary); font-weight: 600; margin-bottom: var(--space-sm); display: block;">
                        <i class="fas fa-graduation-cap" style="color: var(--primary);"></i>
                        Nom du niveau *
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Ex: BTS Première Année"
                           style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface);"
                           required>
                    @error('name')
                        <div class="invalid-feedback" style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                    @enderror
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Le nom complet du niveau d'étude</div>
                </div>

                <!-- Code -->
                <div class="col-md-6 mb-lg">
                    <label for="code" style="color: var(--text-primary); font-weight: 600; margin-bottom: var(--space-sm); display: block;">
                        <i class="fas fa-code" style="color: var(--primary);"></i>
                        Code du niveau *
                    </label>
                    <input type="text"
                           class="form-control @error('code') is-invalid @enderror"
                           id="code"
                           name="code"
                           value="{{ old('code') }}"
                           placeholder="Ex: BTS1"
                           style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface);"
                           required>
                    @error('code')
                        <div class="invalid-feedback" style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                    @enderror
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Code court pour identifier le niveau</div>
                </div>

                <!-- Type -->
                <div class="col-md-6 mb-lg">
                    <label for="type" style="color: var(--text-primary); font-weight: 600; margin-bottom: var(--space-sm); display: block;">
                        <i class="fas fa-tag" style="color: var(--primary);"></i>
                        Type de formation
                    </label>
                    <select class="form-select @error('type') is-invalid @enderror"
                            id="type"
                            name="type"
                            style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface);">
                        <option value="">-- Sélectionner un type --</option>
                        <option value="BTS" {{ old('type') == 'BTS' ? 'selected' : '' }}>BTS</option>
                        <option value="Licence" {{ old('type') == 'Licence' ? 'selected' : '' }}>Licence</option>
                        <option value="Master" {{ old('type') == 'Master' ? 'selected' : '' }}>Master</option>
                        <option value="Doctorat" {{ old('type') == 'Doctorat' ? 'selected' : '' }}>Doctorat</option>
                        <option value="Diplôme" {{ old('type') == 'Diplôme' ? 'selected' : '' }}>Diplôme</option>
                        <option value="Certificat" {{ old('type') == 'Certificat' ? 'selected' : '' }}>Certificat</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback" style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                    @enderror
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Type de formation ou de diplôme</div>
                </div>

                <!-- Année -->
                <div class="col-md-6 mb-lg">
                    <label for="year" style="color: var(--text-primary); font-weight: 600; margin-bottom: var(--space-sm); display: block;">
                        <i class="fas fa-calendar-alt" style="color: var(--primary);"></i>
                        Année d'étude
                    </label>
                    <select class="form-select @error('year') is-invalid @enderror"
                            id="year"
                            name="year"
                            style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface);">
                        <option value="">-- Sélectionner une année --</option>
                        <option value="1" {{ old('year') == '1' ? 'selected' : '' }}>1ère année</option>
                        <option value="2" {{ old('year') == '2' ? 'selected' : '' }}>2ème année</option>
                        <option value="3" {{ old('year') == '3' ? 'selected' : '' }}>3ème année</option>
                        <option value="4" {{ old('year') == '4' ? 'selected' : '' }}>4ème année</option>
                        <option value="5" {{ old('year') == '5' ? 'selected' : '' }}>5ème année</option>
                    </select>
                    @error('year')
                        <div class="invalid-feedback" style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                    @enderror
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Année du cursus d'étude</div>
                </div>

                <!-- Libellé -->
                <div class="col-12 mb-lg">
                    <label for="libelle" style="color: var(--text-primary); font-weight: 600; margin-bottom: var(--space-sm); display: block;">
                        <i class="fas fa-align-left" style="color: var(--primary);"></i>
                        Libellé complet
                    </label>
                    <input type="text"
                           class="form-control @error('libelle') is-invalid @enderror"
                           id="libelle"
                           name="libelle"
                           value="{{ old('libelle') }}"
                           placeholder="Ex: Brevet de Technicien Supérieur - Première Année"
                           style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface);">
                    @error('libelle')
                        <div class="invalid-feedback" style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                    @enderror
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Libellé détaillé du niveau d'étude (optionnel)</div>
                </div>

                <!-- Description -->
                <div class="col-12 mb-lg">
                    <label for="description" style="color: var(--text-primary); font-weight: 600; margin-bottom: var(--space-sm); display: block;">
                        <i class="fas fa-file-alt" style="color: var(--primary);"></i>
                        Description
                    </label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description"
                              name="description"
                              rows="4"
                              placeholder="Description détaillée du niveau d'étude..."
                              style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface); resize: vertical;">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback" style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                    @enderror
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Description détaillée du niveau d'étude (optionnel)</div>
                </div>

                <!-- Statut -->
                <div class="col-12 mb-lg">
                    <div style="display: flex; align-items: center; gap: var(--space-sm);">
                        <input class="form-check-input"
                               type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               style="width: 20px; height: 20px; border: 2px solid var(--primary); border-radius: var(--radius-small); accent-color: var(--primary);">
                        <label for="is_active" style="color: var(--text-primary); font-weight: 500; cursor: pointer;">
                            <i class="fas fa-toggle-on" style="color: var(--success);"></i>
                            Niveau actif
                        </label>
                    </div>
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Décochez pour désactiver temporairement ce niveau</div>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="card-moderne mt-lg" id="previewCard" style="display: none; background-color: var(--primary); color: white; padding: var(--space-lg);">
                <h6 style="margin-bottom: var(--space-md); color: white;">
                    <i class="fas fa-eye"></i>
                    Aperçu du niveau d'étude
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <div style="margin-bottom: var(--space-xs);"><strong>Nom :</strong> <span id="preview-name">-</span></div>
                        <div style="margin-bottom: var(--space-xs);"><strong>Code :</strong> <span id="preview-code">-</span></div>
                    </div>
                    <div class="col-md-6">
                        <div style="margin-bottom: var(--space-xs);"><strong>Type :</strong> <span id="preview-type">-</span></div>
                        <div style="margin-bottom: var(--space-xs);"><strong>Année :</strong> <span id="preview-year">-</span></div>
                    </div>
                </div>
                <div id="preview-description-container" style="display: none; margin-top: var(--space-sm);">
                    <strong>Description :</strong> <span id="preview-description">-</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-xl">
                <a href="{{ route('esbtp.niveaux-etudes.index') }}"
                   class="btn-acasi secondary"
                   style="margin-right: var(--space-md);">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save"></i>
                    Créer le niveau
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
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

        // Effets de focus avec dashboard-moderne styles
        $('.form-control, .form-select').on('focus', function() {
            $(this).css({
                'border-color': 'var(--primary)',
                'box-shadow': '0 0 0 3px rgba(30, 58, 138, 0.1)',
                'transform': 'translateY(-1px)'
            });
        }).on('blur', function() {
            $(this).css({
                'border-color': '#e5e7eb',
                'box-shadow': 'none',
                'transform': 'translateY(0)'
            });
        });

        // Validation en temps réel
        $('.form-control, .form-select').on('blur', function() {
            let field = $(this);
            if (field.prop('required') && !field.val()) {
                field.css('border-color', 'var(--danger)');
            } else {
                field.css('border-color', '#e5e7eb');
            }
        });

        // Confirmation avant soumission
        $('#niveauForm').on('submit', function(e) {
            let requiredFields = ['name', 'code'];
            let hasErrors = false;

            requiredFields.forEach(function(fieldName) {
                let field = $('#' + fieldName);
                if (!field.val()) {
                    field.css('border-color', 'var(--danger)');
                    hasErrors = true;
                }
            });

            if (hasErrors) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
                return false;
            }

            // Animation du bouton pendant la soumission
            let submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');
        });

        // Auto-focus sur le premier champ
        $('#name').focus();
    });
</script>
@endsection
