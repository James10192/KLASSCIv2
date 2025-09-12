@extends('layouts.app')

@section('title', 'Modifier le niveau : ' . $niveauxEtude->name)

@section('content')
<div class="main-content">
    <!-- Header -->
    <div class="dashboard-header mb-xl" style="background-color: var(--warning); color: white; border-radius: var(--radius-medium);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div style="display: flex; align-items: center; gap: var(--space-lg);">
                    <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--accent-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; box-shadow: var(--shadow-elevated);">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">Modifier le Niveau d'Étude</h1>
                        <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">Modifiez les informations du niveau : <strong>{{ $niveauxEtude->name }}</strong></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-end">
                <div class="header-actions">
                    <a href="{{ route('esbtp.niveaux-etudes.show', $niveauxEtude) }}" class="btn-acasi secondary" style="margin-right: var(--space-md);">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
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

    <!-- Info Card -->
    <div class="card-moderne mb-lg" style="background-color: var(--accent-blue); color: white; padding: var(--space-lg);">
        <div style="display: flex; align-items: center; gap: var(--space-md);">
            <i class="fas fa-info-circle fa-2x"></i>
            <div>
                <h6 style="margin: 0; color: white; font-weight: 600;">Modification du niveau d'étude</h6>
                <p style="margin: var(--space-xs) 0 0 0; color: rgba(255,255,255,0.8); font-size: var(--text-small);">
                    Créé le {{ $niveauxEtude->created_at->format('d/m/Y à H:i') }} - 
                    Dernière modification le {{ $niveauxEtude->updated_at->format('d/m/Y à H:i') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card-moderne p-xl">
        <div class="text-center mb-lg">
            <div class="section-title">Modification du Niveau d'Étude</div>
            <p style="color: var(--text-secondary);">Modifiez les informations ci-dessous</p>
        </div>

        <!-- Form -->
        <form action="{{ route('esbtp.niveaux-etudes.update', $niveauxEtude) }}" method="POST" id="editForm" novalidate>
            @csrf
            @method('PUT')

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
                           value="{{ old('name', $niveauxEtude->name) }}"
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
                           value="{{ old('code', $niveauxEtude->code) }}"
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
                        <option value="BTS" {{ old('type', $niveauxEtude->type) == 'BTS' ? 'selected' : '' }}>BTS</option>
                        <option value="Bachelor" {{ old('type', $niveauxEtude->type) == 'Bachelor' ? 'selected' : '' }}>Bachelor</option>
                        <option value="Licence" {{ old('type', $niveauxEtude->type) == 'Licence' ? 'selected' : '' }}>Licence</option>
                        <option value="Master" {{ old('type', $niveauxEtude->type) == 'Master' ? 'selected' : '' }}>Master</option>
                        <option value="Doctorat" {{ old('type', $niveauxEtude->type) == 'Doctorat' ? 'selected' : '' }}>Doctorat</option>
                        <option value="Diplôme" {{ old('type', $niveauxEtude->type) == 'Diplôme' ? 'selected' : '' }}>Diplôme</option>
                        <option value="Certificat" {{ old('type', $niveauxEtude->type) == 'Certificat' ? 'selected' : '' }}>Certificat</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback" style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                    @enderror
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Type de formation ou de diplôme</div>
                </div>

                <!-- Année -->
                <div class="col-md-6 mb-lg">
                    <label for="niveau" style="color: var(--text-primary); font-weight: 600; margin-bottom: var(--space-sm); display: block;">
                        <i class="fas fa-calendar-alt" style="color: var(--primary);"></i>
                        Année d'étude
                    </label>
                    <select class="form-select @error('niveau') is-invalid @enderror"
                            id="niveau"
                            name="niveau"
                            style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface);">
                        <option value="">-- Sélectionner une année --</option>
                        <option value="1" {{ old('niveau', $niveauxEtude->niveau) == '1' ? 'selected' : '' }}>1ère année</option>
                        <option value="2" {{ old('niveau', $niveauxEtude->niveau) == '2' ? 'selected' : '' }}>2ème année</option>
                        <option value="3" {{ old('niveau', $niveauxEtude->niveau) == '3' ? 'selected' : '' }}>3ème année</option>
                        <option value="4" {{ old('niveau', $niveauxEtude->niveau) == '4' ? 'selected' : '' }}>4ème année</option>
                        <option value="5" {{ old('niveau', $niveauxEtude->niveau) == '5' ? 'selected' : '' }}>5ème année</option>
                    </select>
                    @error('niveau')
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
                           value="{{ old('libelle', $niveauxEtude->libelle) }}"
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
                              style="border: 2px solid #e5e7eb; border-radius: var(--radius-medium); padding: var(--space-md); font-size: var(--text-normal); transition: all 0.3s ease; background: var(--surface); resize: vertical;">{{ old('description', $niveauxEtude->description) }}</textarea>
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
                               {{ old('is_active', $niveauxEtude->is_active ?? true) ? 'checked' : '' }}
                               style="width: 20px; height: 20px; border: 2px solid var(--primary); border-radius: var(--radius-small); accent-color: var(--primary);">
                        <label for="is_active" style="color: var(--text-primary); font-weight: 500; cursor: pointer;">
                            <i class="fas fa-toggle-on" style="color: var(--success);"></i>
                            Niveau actif
                        </label>
                    </div>
                    <div style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs);">Décochez pour désactiver temporairement ce niveau</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-xl">
                <a href="{{ route('esbtp.niveaux-etudes.show', $niveauxEtude) }}"
                   class="btn-acasi secondary"
                   style="margin-right: var(--space-md);">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>

                <button type="button"
                        class="btn-acasi"
                        style="background-color: var(--danger); color: white; margin-right: var(--space-md);"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteModal">
                    <i class="fas fa-trash"></i>
                    Supprimer
                </button>

                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save"></i>
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-moderne">
            <div class="modal-header" style="background-color: var(--danger); color: white; border-radius: var(--radius-medium) var(--radius-medium) 0 0;">
                <h5 class="modal-title" style="color: white;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-lg">
                <i class="fas fa-trash fa-3x mb-lg" style="color: var(--danger);"></i>
                <h6>Êtes-vous sûr de vouloir supprimer ce niveau d'étude ?</h6>
                <p style="color: var(--text-secondary); margin: var(--space-md) 0;">{{ $niveauxEtude->name }}</p>

                @if(($niveauxEtude->filieres ? $niveauxEtude->filieres->count() : 0) > 0 || ($niveauxEtude->matieres ? $niveauxEtude->matieres->count() : 0) > 0 || ($niveauxEtude->classes ? $niveauxEtude->classes->count() : 0) > 0)
                    <div class="alert alert-warning" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); border-radius: var(--radius-medium); padding: var(--space-md);">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention :</strong> Ce niveau d'étude possède des données associées qui seront également affectées.
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="padding: var(--space-lg); border-top: 1px solid #f3f4f6;">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal" style="margin-right: var(--space-md);">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <form action="{{ route('esbtp.niveaux-etudes.destroy', $niveauxEtude) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-acasi" style="background-color: var(--danger); color: white;">
                        <i class="fas fa-trash"></i> Confirmer
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
    $('#editForm').on('submit', function(e) {
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
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mise à jour...');
    });

    // Auto-focus sur le premier champ
    $('#name').focus();
});
</script>
@endsection
