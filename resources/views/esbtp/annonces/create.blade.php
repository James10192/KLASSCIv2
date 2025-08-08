@extends('layouts.app')

@section('title', 'Créer une annonce - ESBTP-yAKRO')

@push('styles')
<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

<style>
    /* Styles pour les cartes */
    .hover-card {
        transition: all 0.3s ease;
    }
    .hover-card:hover {
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
        transform: translateY(-2px);
    }

    /* Amélioration des form-controls */
    .form-control, .form-select {
        padding: 0.6rem 0.75rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--esbtp-green);
        box-shadow: 0 0 0 0.15rem rgba(1, 99, 47, 0.15);
    }

    /* Styles Choices.js modernes */
    .choices {
        margin-bottom: 0;
        font-size: 14px;
        position: relative;
    }

    .choices__inner {
        background: #ffffff;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 14px;
        min-height: 48px;
        padding: 12px 16px 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .choices__inner::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: -1;
    }

    .choices__inner:focus-within {
        border-color: var(--esbtp-green);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(1, 99, 47, 0.3);
    }

    .choices__inner:focus-within::before {
        opacity: 0.1;
    }

    .choices__list--dropdown {
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        z-index: 1050;
        overflow: hidden;
        animation: dropdownSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes dropdownSlideIn {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .choices__item--selectable {
        padding: 12px 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        border-radius: 6px;
        margin: 2px 4px;
        color: #374151;
        font-weight: 500;
        border-bottom: 1px solid #f3f4f6;
    }

    .choices__item--selectable::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--esbtp-green);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: -1;
    }

    .choices__item--selectable:hover {
        background: #f8fafc;
        color: #1e40af;
        font-weight: 600;
        transform: translateX(3px);
        border-left: 3px solid #1e40af;
    }

    .choices__item--selectable:hover::before {
        left: 0;
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        opacity: 0.1;
    }

    .choices__item--selectable.is-highlighted {
        background: #1e40af;
        color: white;
        font-weight: 600;
        transform: translateX(3px);
        border-left: 3px solid #1e40af;
    }

    .choices__list--multiple .choices__item {
        background: #1e40af;
        border: none;
        border-radius: 20px;
        color: white;
        font-size: 13px;
        font-weight: 600;
        margin: 2px 4px 2px 0;
        padding: 6px 12px;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        animation: slideInTag 0.3s ease-out;
    }

    @keyframes slideInTag {
        from {
            opacity: 0;
            transform: translateX(-20px) scale(0.8);
        }
        to {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }

    .choices__list--multiple .choices__item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4);
        background: #3b82f6;
    }

    .choices__button {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        font-size: 12px;
        height: 18px;
        width: 18px;
        margin-left: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .choices__button:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .choices__placeholder {
        color: #6b7280;
        opacity: 1;
        font-style: italic;
        font-weight: 400;
    }

    .choices__input {
        background-color: transparent;
        border: 0;
        font-size: 14px;
        margin-bottom: 0;
        padding: 0;
        color: #374151;
        font-weight: 500;
    }

    .choices__input:focus {
        outline: 0;
    }

    .choices.is-invalid .choices__inner {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }

    /* Style pour les radios personnalisés */
    .custom-radio {
        padding: 10px 15px;
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
        margin-right: 10px;
        transition: all 0.2s ease;
    }
    .custom-radio:hover {
        background-color: rgba(1, 99, 47, 0.05);
        border-color: var(--esbtp-green);
    }
    .custom-radio .form-check-input:checked ~ .form-check-label {
        color: var(--esbtp-green);
        font-weight: 500;
    }

    /* Style pour les étiquettes */
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    /* Style pour les zones de formulaire spécifiques */
    #classes_container, #etudiants_container {
        transition: all 0.3s ease;
    }

    /* Style pour l'alerte */
    .alert-danger {
        border-left: 4px solid #842029;
    }

    /* Amélioration visibilité des textes */
    .choices__list--single .choices__item--selectable {
        color: #374151;
        font-weight: 500;
    }

    .choices__item[data-choice] {
        color: #374151 !important;
        font-weight: 500;
    }

    .choices__item--choice {
        color: #374151 !important;
    }

    /* Media queries pour la responsivité */
    @media (max-width: 991.98px) {
        .sticky-top {
            position: relative;
            top: 0 !important;
        }
    }

    @media (max-width: 768px) {
        .choices__list--multiple .choices__item {
            font-size: 12px;
            padding: 4px 8px;
            margin: 1px 2px 1px 0;
        }

        .choices__button {
            height: 16px;
            width: 16px;
            font-size: 10px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <!-- En-tête de page avec style moderne -->
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="font-weight-bold text-primary mb-1">
                                <i class="fas fa-plus-circle me-2"></i>Créer une nouvelle annonce
                            </h4>
                            <p class="text-muted mb-0 small">Remplissez le formulaire ci-dessous pour créer une nouvelle annonce.</p>
                        </div>
                        <a href="{{ route('esbtp.annonces.index') }}" class="btn btn-outline-primary rounded-pill">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

                    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-lg shadow-sm mb-4" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-exclamation-triangle fs-3"></i>
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

                    <form action="{{ route('esbtp.annonces.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
            <!-- Informations générales - Colonne principale -->
            <div class="col-lg-8">
                <!-- Carte d'informations générales avec style moderne -->
                <div class="card shadow-sm border-0 rounded-lg mb-4 hover-card">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Informations générales
                        </h5>
                                    </div>
                    <div class="card-body py-4">
                        <div class="mb-4">
                                            <label for="titre" class="form-label">Titre de l'annonce <span class="text-danger">*</span></label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-heading text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0 @error('titre') is-invalid @enderror"
                                    id="titre" name="titre" value="{{ old('titre') }}" placeholder="Saisissez un titre clair et concis" required>
                            </div>
                                            @error('titre')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                            <div class="form-text text-muted small">Le titre doit être court et descriptif (max 255 caractères).</div>
                                        </div>

                        <div class="mb-4">
                                            <label for="contenu" class="form-label">Contenu <span class="text-danger">*</span></label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-align-left text-primary"></i>
                                </span>
                                <textarea class="form-control @error('contenu') is-invalid @enderror"
                                    id="contenu" name="contenu" rows="8"
                                    placeholder="Détaillez votre annonce ici..." required>{{ old('contenu') }}</textarea>
                            </div>
                                            @error('contenu')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                            <label for="piece_jointe" class="form-label d-flex align-items-center">
                                <i class="fas fa-paperclip me-2 text-primary"></i>
                                Pièce jointe (optionnel)
                            </label>
                            <div class="input-group">
                                            <input type="file" class="form-control @error('piece_jointe') is-invalid @enderror" id="piece_jointe" name="piece_jointe">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-upload"></i>
                                </span>
                            </div>
                                            @error('piece_jointe')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                            <div class="form-text text-muted small">Formats acceptés: PDF, Word, Excel, Images (max 5MB)</div>
                                    </div>
                                </div>
                            </div>

                <!-- Section des destinataires -->
                <div class="card shadow-sm border-0 rounded-lg mb-4 hover-card">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Destinataires
                        </h5>
                                    </div>
                    <div class="card-body py-4">
                        <div class="mb-4">
                            <label class="form-label mb-3">Type de destinataires <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check custom-radio">
                                    <input class="form-check-input" type="radio" name="type" id="type_globale"
                                        value="general" {{ old('type', 'general') == 'general' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="type_globale">
                                        <i class="fas fa-globe me-1 text-primary"></i>
                                        Tous les étudiants
                                    </label>
                                            </div>
                                <div class="form-check custom-radio">
                                    <input class="form-check-input" type="radio" name="type" id="type_classe"
                                        value="classe" {{ old('type') == 'classe' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="type_classe">
                                        <i class="fas fa-chalkboard me-1 text-success"></i>
                                        Classes spécifiques
                                    </label>
                                        </div>
                                <div class="form-check custom-radio">
                                    <input class="form-check-input" type="radio" name="type" id="type_etudiant"
                                        value="etudiant" {{ old('type') == 'etudiant' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="type_etudiant">
                                        <i class="fas fa-user-graduate me-1 text-info"></i>
                                        Étudiants spécifiques
                                    </label>
                                                </div>
                                            </div>
                                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Sélection par classe -->
                        <div id="classes_container" class="mb-4 bg-light p-3 rounded-3" style="display: none;">
                            <h6 class="mb-3 text-primary">
                                <i class="fas fa-filter me-2"></i>
                                Filtrer les classes
                            </h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4 mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-sitemap text-primary"></i>
                                        </span>
                                        <select class="form-select shadow-none" id="filiere_filter">
                                                        <option value="">Toutes les filières</option>
                                                        @foreach($filieres as $filiere)
                                                            <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-layer-group text-primary"></i>
                                        </span>
                                        <select class="form-select shadow-none" id="niveau_filter">
                                                        <option value="">Tous les niveaux</option>
                                                        @foreach($niveaux as $niveau)
                                                            <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="select_all_classes">
                                        <i class="fas fa-check-double me-1"></i>
                                        Sélectionner toutes les classes visibles
                                    </button>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 reset-filters">
                                        <i class="fas fa-undo me-1"></i>
                                        Réinitialiser les filtres
                                    </button>
                                                </div>
                                            </div>

                            <label for="classes" class="form-label">Classes destinataires <span class="text-danger">*</span></label>
                            <select class="form-select choices-multiple @error('classes') is-invalid @enderror"
                                id="classes" name="classes[]" multiple data-placeholder="Sélectionnez une ou plusieurs classes">
                                                @foreach($classes as $classe)
                                                    <option value="{{ $classe->id }}"
                                                        data-filiere="{{ $classe->filiere_id }}"
                                                        data-niveau="{{ $classe->niveau_id }}"
                                                        {{ (old('classes') && in_array($classe->id, old('classes'))) ? 'selected' : '' }}>
                                                        {{ $classe->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('classes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Sélection par étudiant -->
                        <div id="etudiants_container" class="mb-4 bg-light p-3 rounded-3" style="display: none;">
                            <h6 class="mb-3 text-primary">
                                <i class="fas fa-filter me-2"></i>
                                Filtrer les étudiants
                            </h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-chalkboard text-primary"></i>
                                        </span>
                                        <select class="form-select shadow-none" id="classe_etudiant_filter">
                                                        <option value="">Toutes les classes</option>
                                                        @foreach($classes as $classe)
                                                            <option value="{{ $classe->id }}">{{ $classe->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="select_all_etudiants">
                                        <i class="fas fa-check-double me-1"></i>
                                        Sélectionner tous les étudiants visibles
                                    </button>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 reset-filters">
                                        <i class="fas fa-undo me-1"></i>
                                        Réinitialiser les filtres
                                    </button>
                                                </div>
                                            </div>

                            <label for="etudiants" class="form-label">Étudiants destinataires <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-user-graduate text-primary"></i>
                                </span>
                                <select class="form-select choices-multiple @error('etudiants') is-invalid @enderror"
                                    id="etudiants" name="etudiants[]" multiple data-placeholder="Sélectionnez un ou plusieurs étudiants">
                                                @foreach($etudiants as $etudiant)
                                                    <option value="{{ $etudiant->id }}"
                                            data-classe="{{ $etudiant->classe ? $etudiant->classe->id : '' }}"
                                                        {{ (old('etudiants') && in_array($etudiant->id, old('etudiants'))) ? 'selected' : '' }}>
                                                        {{ $etudiant->matricule }} - {{ $etudiant->nom }} {{ $etudiant->prenoms }} 
                                                        @if($etudiant->classe)
                                                            ({{ $etudiant->classe->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                            </div>
                                            @error('etudiants')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                            <div class="form-text text-muted small mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Les notifications seront envoyées à tous les étudiants sélectionnés, qu'ils soient connectés ou non.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

            <!-- Colonne latérale pour les options -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 80px; z-index: 1;">
                    <!-- Carte des options de publication -->
                    <div class="card shadow-sm border-0 rounded-lg mb-4 hover-card">
                        <div class="card-header bg-white py-3 border-bottom border-light">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-cog me-2 text-primary"></i>
                                Options de publication
                            </h5>
                        </div>
                        <div class="card-body py-4">
                            <div class="mb-4">
                                <label for="status" class="form-label">Statut de publication <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-toggle-on text-primary"></i>
                                    </span>
                                    <select name="is_published" id="status" class="form-select @error('is_published') is-invalid @enderror" required>
                                        <option value="0" {{ old('is_published') == '0' ? 'selected' : '' }}>Brouillon</option>
                                        <option value="1" {{ old('is_published') == '1' ? 'selected' : '' }}>Publiée</option>
                                    </select>
                                </div>
                                @error('is_published')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted small">Les annonces en brouillon ne sont pas visibles par les destinataires.</div>
                            </div>

                            <div class="mb-4" id="date-publication-container" style="display: none;">
                                <label for="date_publication" class="form-label">Date de publication</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                    </span>
                                    <input type="datetime-local" class="form-control @error('date_publication') is-invalid @enderror"
                                        id="date_publication" name="date_publication"
                                        value="{{ old('date_publication', now()->format('Y-m-d\TH:i')) }}">
                                </div>
                                @error('date_publication')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted small">Si non spécifié, la date actuelle sera utilisée.</div>
                            </div>

                            <div class="mb-4">
                                <label for="date_expiration" class="form-label">Date d'expiration <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-hourglass-end text-primary"></i>
                                    </span>
                                    <input type="datetime-local" class="form-control @error('date_expiration') is-invalid @enderror"
                                        id="date_expiration" name="date_expiration"
                                        value="{{ old('date_expiration', now()->addMonths(1)->format('Y-m-d\TH:i')) }}">
                                </div>
                                @error('date_expiration')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted small">Après cette date, l'annonce ne sera plus visible pour les destinataires.</div>
                            </div>

                            <div class="mb-3">
                                <label for="priorite" class="form-label">Niveau d'urgence</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-exclamation-circle text-primary"></i>
                                    </span>
                                    <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite">
                                        <option value="0" {{ old('priorite') == '0' ? 'selected' : '' }}>Normale</option>
                                        <option value="1" {{ old('priorite') == '1' ? 'selected' : '' }}>Importante</option>
                                        <option value="2" {{ old('priorite') == '2' ? 'selected' : '' }}>Urgente</option>
                                    </select>
                                </div>
                                @error('priorite')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted small">
                                    Les annonces urgentes seront mises en évidence pour les destinataires.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carte d'actions -->
                    <div class="card shadow-sm border-0 rounded-lg mb-4 hover-card">
                        <div class="card-body p-4">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-2">
                                    <i class="fas fa-save me-2"></i>Enregistrer l'annonce
                            </button>
                                <button type="reset" class="btn btn-light py-2">
                                    <i class="fas fa-undo me-2"></i>Réinitialiser le formulaire
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<!-- Choices.js JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    // Variables globales pour Choices.js
    let choicesInstances = {};

    // Configuration par défaut pour Choices.js
    const defaultChoicesConfig = {
        searchEnabled: true,
        searchChoices: true,
        searchFloor: 1,
        searchResultLimit: 10,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Rechercher...",
        noResultsText: "Aucun résultat trouvé",
        noChoicesText: "Aucun choix disponible",
        itemSelectText: "Cliquer pour sélectionner",
        loadingText: "Recherche en cours...",
        removeItemButton: true,
        duplicateItemsAllowed: false,
        maxItemCount: 50,
        renderChoiceLimit: 20,
    };

    // Fonction d'initialisation de Choices.js
    function initializeChoices(selectElement, customConfig = {}) {
        const selectId = selectElement.id;
        console.log("Initialisation de Choices.js pour:", selectId);

        if (!selectElement) {
            console.error("Élément select non trouvé:", selectId);
            return null;
        }

        // Détruire l'instance existante si elle existe
        if (choicesInstances[selectId]) {
            choicesInstances[selectId].destroy();
            delete choicesInstances[selectId];
        }

        // Fusionner la configuration
        const config = { ...defaultChoicesConfig, ...customConfig };

        try {
            const choices = new Choices(selectElement, config);
            choicesInstances[selectId] = choices;
            console.log("Instance Choices.js créée avec succès pour:", selectId);
            return choices;
        } catch (error) {
            console.error("Erreur lors de la création de l'instance Choices.js:", error);
            return null;
        }
    }

    // Configuration pour les templates personnalisés
    const multipleSelectConfig = {
        callbackOnCreateTemplates: function (template) {
            return {
                item: ({ classNames }, data) => {
                    return template(`
                        <div class="${classNames.item} ${
                        data.highlighted
                            ? classNames.highlightedState
                            : classNames.itemSelectable
                    }"
                             data-item data-id="${data.id}" data-value="${data.value}">
                            <span class="choice-item-content">
                                ${data.label}
                            </span>
                            ${
                                !data.disabled
                                    ? `<button type="button" class="${classNames.button}" data-button><i class="fas fa-times"></i></button>`
                                    : ""
                            }
                        </div>
                    `);
                },
                choice: ({ classNames }, data) => {
                    return template(`
                        <div class="${classNames.item} ${classNames.itemChoice} ${
                        data.disabled
                            ? classNames.itemDisabled
                            : classNames.itemSelectable
                    }"
                             data-select-text="${
                                 this.config.itemSelectText
                             }" data-choice
                             ${
                                 data.disabled
                                     ? 'data-choice-disabled aria-disabled="true"'
                                     : "data-choice-selectable"
                             }
                             data-id="${data.id}" data-value="${data.value}">
                            <div class="choice-content">
                                ${data.label}
                            </div>
                        </div>
                    `);
                },
            };
        },
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Stocker les options originales avant initialisation de Choices.js
        const originalClassesOptions = [];
        const originalEtudiantsOptions = [];
        
        // Sauvegarder les options originales des classes
        const classesSelect = document.getElementById('classes');
        if (classesSelect) {
            Array.from(classesSelect.options).forEach(option => {
                if (option.value) {
                    originalClassesOptions.push({
                        value: option.value,
                        label: option.textContent,
                        selected: option.selected,
                        disabled: false,
                        customProperties: {
                            filiere: option.dataset.filiere,
                            niveau: option.dataset.niveau
                        }
                    });
                }
            });
        }
        
        // Sauvegarder les options originales des étudiants
        const etudiantsSelect = document.getElementById('etudiants');
        if (etudiantsSelect) {
            Array.from(etudiantsSelect.options).forEach(option => {
                if (option.value) {
                    originalEtudiantsOptions.push({
                        value: option.value,
                        label: option.textContent,
                        selected: option.selected,
                        disabled: false,
                        customProperties: {
                            classe: option.dataset.classe
                        }
                    });
                }
            });
        }

        // Initialiser Choices.js pour les sélecteurs multiples
        if (classesSelect) {
            const classesChoices = initializeChoices(classesSelect, {
                ...multipleSelectConfig,
                placeholderValue: "Sélectionnez une ou plusieurs classes...",
                maxItemCount: 20,
            });
        }

        if (etudiantsSelect) {
            const etudiantsChoices = initializeChoices(etudiantsSelect, {
                ...multipleSelectConfig,
                placeholderValue: "Sélectionnez un ou plusieurs étudiants...",
                maxItemCount: 50,
            });
        }

        // Fonction pour réinitialiser les filtres
        function resetFilters() {
            // Réinitialiser les filtres de classes
            $('#filiere_filter, #niveau_filter').val('');
            $('#classe_etudiant_filter').val('');
            
            // Restaurer manuellement toutes les options originales
            const classesChoicesInstance = choicesInstances['classes'];
            const etudiantsChoicesInstance = choicesInstances['etudiants'];
            
            if (classesChoicesInstance && originalClassesOptions.length > 0) {
                const currentClassesSelections = classesChoicesInstance.getValue(true);
                classesChoicesInstance.clearStore();
                classesChoicesInstance.setChoices(originalClassesOptions, 'value', 'label', true);
                
                // Restaurer les sélections
                currentClassesSelections.forEach(value => {
                    classesChoicesInstance.setChoiceByValue(value);
                });
            }
            
            if (etudiantsChoicesInstance && originalEtudiantsOptions.length > 0) {
                const currentEtudiantsSelections = etudiantsChoicesInstance.getValue(true);
                etudiantsChoicesInstance.clearStore();
                etudiantsChoicesInstance.setChoices(originalEtudiantsOptions, 'value', 'label', true);
                
                // Restaurer les sélections
                currentEtudiantsSelections.forEach(value => {
                    etudiantsChoicesInstance.setChoiceByValue(value);
                });
                
                // Mettre à jour le message informatif
                if ($('#etudiants-info').length) {
                    $('#etudiants-info').text(`${originalEtudiantsOptions.length} étudiant(s) disponible(s)`);
                }
            }
        }

        // Ajouter un bouton de réinitialisation (optionnel)
        $(document).on('click', '.reset-filters', function() {
            resetFilters();
        });

        // Gestion de l'affichage du champ de date de publication
        $('#status').change(function() {
            if ($(this).val() === 'scheduled') {
                $('#date-publication-container').slideDown(300);
            } else {
                $('#date-publication-container').slideUp(300);
            }
        }).trigger('change');

        // Animation pour l'affichage des conteneurs de destinataires
        $('input[name="type"]').change(function() {
            const selectedType = $('input[name="type"]:checked').val();

            $('#classes_container, #etudiants_container').slideUp(300);

            if (selectedType === 'classe') {
                setTimeout(() => {
                    $('#classes_container').slideDown(300);
                }, 300);
            } else if (selectedType === 'etudiant') {
                setTimeout(() => {
                    $('#etudiants_container').slideDown(300);
                }, 300);
            }
        }).trigger('change');

        // Filtrage amélioré des classes avec Choices.js
        $('#filiere_filter, #niveau_filter').change(function() {
            const filiereId = $('#filiere_filter').val();
            const niveauId = $('#niveau_filter').val();
            const classesChoicesInstance = choicesInstances['classes'];
            
            console.log('Filtres appliqués - Filière:', filiereId, 'Niveau:', niveauId);

            if (classesChoicesInstance && originalClassesOptions.length > 0) {
                // Conserver les sélections actuelles
                const currentSelections = classesChoicesInstance.getValue(true);
                
                // Filtrer les options originales
                const filteredChoices = originalClassesOptions.filter(option => {
                    let show = true;

                    // Appliquer les filtres seulement si des filtres sont sélectionnés
                    if (filiereId && option.customProperties.filiere) {
                        // Comparaison en string pour éviter les problèmes de type
                        if (String(option.customProperties.filiere) !== String(filiereId)) {
                            show = false;
                        }
                    }

                    if (niveauId && option.customProperties.niveau) {
                        // Comparaison en string pour éviter les problèmes de type
                        if (String(option.customProperties.niveau) !== String(niveauId)) {
                            show = false;
                        }
                    }

                    return show;
                });

                // Vider et remplir avec les nouvelles options
                classesChoicesInstance.clearStore();
                classesChoicesInstance.setChoices(filteredChoices, 'value', 'label', true);
                
                // Restaurer les sélections précédentes si elles sont toujours disponibles
                currentSelections.forEach(value => {
                    const optionExists = filteredChoices.some(choice => choice.value === value);
                    if (optionExists) {
                        classesChoicesInstance.setChoiceByValue(value);
                    }
                });
            }
        });

        // Filtrage amélioré des étudiants avec Choices.js
        $('#classe_etudiant_filter').change(function() {
            const classeId = $(this).val();
            const etudiantsChoicesInstance = choicesInstances['etudiants'];
            
            console.log('Filtre classe pour étudiants:', classeId);

            if (etudiantsChoicesInstance && originalEtudiantsOptions.length > 0) {
                // Conserver les sélections actuelles
                const currentSelections = etudiantsChoicesInstance.getValue(true);
                
                // Filtrer les options originales
                const filteredChoices = originalEtudiantsOptions.filter(option => {
                    let show = true;

                    // Appliquer le filtre seulement si une classe est sélectionnée
                    if (classeId && option.customProperties.classe) {
                        // Comparaison en string pour éviter les problèmes de type
                        if (String(option.customProperties.classe) !== String(classeId)) {
                            show = false;
                        }
                    }

                    // Debug pour voir les données
                    if (classeId) {
                        console.log('Étudiant:', option.label, 'Classe:', option.customProperties.classe, 'Filtre:', classeId, 'Affiché:', show);
                    }

                    return show;
                });

                // Vider et remplir avec les nouvelles options
                etudiantsChoicesInstance.clearStore();
                etudiantsChoicesInstance.setChoices(filteredChoices, 'value', 'label', true);
                
                // Restaurer les sélections précédentes si elles sont toujours disponibles
                currentSelections.forEach(value => {
                    const optionExists = filteredChoices.some(choice => choice.value === value);
                    if (optionExists) {
                        etudiantsChoicesInstance.setChoiceByValue(value);
                    }
                });

                // Afficher un message informatif
                const visibleCount = filteredChoices.length;
                const infoMessage = visibleCount > 0
                    ? `${visibleCount} étudiant(s) disponible(s)`
                    : "Aucun étudiant disponible avec ce filtre";

                if ($('#etudiants-info').length) {
                    $('#etudiants-info').text(infoMessage);
                } else {
                    $('<div id="etudiants-info" class="text-muted small mt-2 mb-2">' + infoMessage + '</div>').insertBefore('#etudiants');
                }
            }
        });

        // Sélection améliorée de toutes les classes
        $('#select_all_classes').click(function() {
            const classesChoicesInstance = choicesInstances['classes'];
            if (classesChoicesInstance) {
                // Sélectionner toutes les options visibles
                const availableChoices = classesChoicesInstance._currentState.choices.filter(choice => !choice.disabled);
                availableChoices.forEach(choice => {
                    classesChoicesInstance._addItem({
                        value: choice.value,
                        label: choice.label,
                        id: choice.id
                    });
                });

                // Effet visuel
            $(this).addClass('btn-success').removeClass('btn-outline-primary');
            setTimeout(() => {
                $(this).addClass('btn-outline-primary').removeClass('btn-success');
            }, 1000);
            }
        });

        // Sélection améliorée de tous les étudiants
        $('#select_all_etudiants').click(function() {
            const etudiantsChoicesInstance = choicesInstances['etudiants'];
            if (etudiantsChoicesInstance) {
                // Sélectionner toutes les options visibles
                const availableChoices = etudiantsChoicesInstance._currentState.choices.filter(choice => !choice.disabled);
                availableChoices.forEach(choice => {
                    etudiantsChoicesInstance._addItem({
                        value: choice.value,
                        label: choice.label,
                        id: choice.id
                    });
                });

                // Effet visuel
            $(this).addClass('btn-success').removeClass('btn-outline-primary');
            setTimeout(() => {
                $(this).addClass('btn-outline-primary').removeClass('btn-success');
            }, 1000);
            }
        });

        // Prévisualisation de la notification de niveau d'urgence
        $('#priorite').change(function() {
            const priorite = $(this).val();
            let bgColor = 'var(--esbtp-light-green)';
            let textColor = 'var(--esbtp-green)';

            if (priorite == 1) {
                bgColor = 'var(--esbtp-light-orange)';
                textColor = 'var(--esbtp-orange)';
            } else if (priorite == 2) {
                bgColor = '#f8d7da';
                textColor = '#842029';
            }

            $(this).css({
                'background-color': bgColor,
                'color': textColor,
                'border-color': textColor
            });

            // Revenir à la normale après 1.5 secondes
            setTimeout(() => {
                $(this).css({
                    'background-color': '',
                    'color': '',
                    'border-color': ''
                });
            }, 1500);
        });

        // Validation du formulaire avec Choices.js
        $('form').on('submit', function(e) {
            const selectedType = $('input[name="type"]:checked').val();
            let isValid = true;

            if (selectedType === 'classe') {
                const classesChoicesInstance = choicesInstances['classes'];
                if (classesChoicesInstance && classesChoicesInstance.getValue().length === 0) {
                    alert('Veuillez sélectionner au moins une classe.');
                    isValid = false;
                }
            } else if (selectedType === 'etudiant') {
                const etudiantsChoicesInstance = choicesInstances['etudiants'];
                if (etudiantsChoicesInstance && etudiantsChoicesInstance.getValue().length === 0) {
                    alert('Veuillez sélectionner au moins un étudiant.');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
