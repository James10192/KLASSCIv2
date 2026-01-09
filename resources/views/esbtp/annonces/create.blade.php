@extends('layouts.app')

@section('title', 'Créer une annonce - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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
        z-index: 9999 !important;
        overflow: visible;
        animation: dropdownSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: absolute !important;
        max-height: 300px;
        overflow-y: auto;
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

    /* Styles ACASI pour les radio buttons */
    .form-radio-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .form-radio-option {
        display: flex;
        align-items: center;
        padding: 16px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        gap: 12px;
    }
    
    .form-radio-option:hover {
        border-color: #0453cb;
        background: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    }
    
    .form-radio-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .form-radio-check {
        width: 20px;
        height: 20px;
        border: 2px solid #d1d5db;
        border-radius: 50%;
        position: relative;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    
    .form-radio-check::after {
        content: '';
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #0453cb;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        transition: transform 0.2s ease;
    }
    
    .form-radio-option input[type="radio"]:checked + .form-radio-check {
        border-color: #0453cb;
    }
    
    .form-radio-option input[type="radio"]:checked + .form-radio-check::after {
        transform: translate(-50%, -50%) scale(1);
    }
    
    .form-radio-option input[type="radio"]:checked ~ .form-radio-label {
        color: #0453cb;
        font-weight: 600;
    }
    
    .form-radio-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        transition: color 0.3s ease;
    }
    
    .form-radio-label i {
        font-size: 16px;
        width: 20px;
        text-align: center;
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

    /* Styles ACASI pour tous les éléments de formulaire */
    .form-input, .form-textarea, .form-file, .form-select-single, .form-select-multiple {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        background: white;
        font-size: 14px;
        color: #374151;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .form-select-single, .form-select-multiple {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 48px;
    }

    .form-input:focus, .form-textarea:focus, .form-file:focus, .form-select-single:focus, .form-select-multiple:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        transform: translateY(-1px);
    }

    .form-input.error, .form-textarea.error, .form-file.error, .form-select-single.error, .form-select-multiple.error {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .form-input::placeholder, .form-textarea::placeholder {
        color: #9ca3af;
        font-weight: 400;
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
        line-height: 1.5;
    }

    .form-file {
        padding: 16px;
        border-style: dashed;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        cursor: pointer;
        position: relative;
    }

    .form-file:hover {
        border-color: #0453cb;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 14px;
        display: block;
    }

    .required {
        color: #ef4444;
        margin-left: 4px;
    }

    .form-help {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
        font-weight: 400;
    }

    .error-message {
        font-size: 12px;
        color: #ef4444;
        margin-top: 6px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .error-message::before {
        content: "⚠";
        font-size: 14px;
    }

    /* Styles spécifiques pour choices.js avec ACASI */
    .choices {
        position: relative;
        z-index: 1;
    }

    .choices .choices__inner {
        background: white !important;
        border: 2px solid #e5e7eb !important;
        border-radius: 12px !important;
        padding: 8px 12px !important;
        min-height: 48px !important;
        transition: all 0.3s ease !important;
    }

    .choices.is-focused .choices__inner {
        border-color: #0453cb !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
    }

    .choices.is-open {
        z-index: 9999 !important;
    }

    .choices .choices__list--multiple .choices__item {
        background: #0453cb !important;
        border-radius: 8px !important;
        padding: 6px 12px !important;
        margin: 2px 4px 2px 0 !important;
        color: white !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        border: none !important;
    }

    /* Assurer que les conteneurs parents permettent l'overflow */
    .dashboard-acasi .main-content {
        overflow: visible !important;
    }

    .main-card-body {
        overflow: visible !important;
        position: relative;
    }

    .main-card {
        overflow: visible !important;
        position: relative;
    }

    .form-group {
        overflow: visible !important;
        position: relative;
        z-index: 1;
    }

    /* Spécifiquement pour les conteneurs de sélection */
    #classes_container, #etudiants_container {
        overflow: visible !important;
        z-index: 100;
        position: relative;
    }

    /* Assurer que le dropdown Choices.js peut déborder */
    .choices[data-type*="select-multiple"] {
        z-index: 1000;
        position: relative;
    }

    .choices[data-type*="select-multiple"].is-open {
        z-index: 10000 !important;
    }

    /* Container pour éviter les conflits de z-index */
    .row {
        position: relative;
        z-index: 1;
    }

    .col-lg-8, .col-lg-4 {
        position: static;
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
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-plus-circle me-2"></i>Créer une annonce</h1>
                <p class="header-subtitle">Diffusion d'informations aux étudiants et au personnel</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-modern error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Erreur de validation</h4>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('esbtp.annonces.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-lg-8">
                    <!-- Informations générales -->
                    <div class="main-card mb-4">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-info-circle"></i>
                                Informations générales
                            </div>
                        </div>
                        <div class="main-card-body">
                            <div class="form-group">
                                <label for="titre" class="form-label">Titre de l'annonce <span class="required">*</span></label>
                                <input type="text" id="titre" name="titre" class="form-input @error('titre') error @enderror" 
                                       value="{{ old('titre') }}" placeholder="Titre clair et concis" required>
                                @error('titre')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="contenu" class="form-label">Contenu <span class="required">*</span></label>
                                <textarea id="contenu" name="contenu" class="form-textarea @error('contenu') error @enderror" 
                                          rows="6" placeholder="Contenu détaillé de l'annonce..." required>{{ old('contenu') }}</textarea>
                                @error('contenu')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="piece_jointe" class="form-label">Pièce jointe (optionnel)</label>
                                <input type="file" id="piece_jointe" name="piece_jointe" class="form-file @error('piece_jointe') error @enderror">
                                @error('piece_jointe')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                <div class="form-help">Formats acceptés: PDF, Word, Excel, Images (max 5MB)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Destinataires -->
                    <div class="main-card mb-4">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-users"></i>
                                Destinataires
                            </div>
                        </div>
                        <div class="main-card-body">
                            <div class="form-group">
                                <label class="form-label">Type de destinataires <span class="required">*</span></label>
                                <div class="form-radio-group">
                                    <label class="form-radio-option">
                                        <input type="radio" name="type" value="general" 
                                               {{ old('type', 'general') == 'general' ? 'checked' : '' }} required>
                                        <span class="form-radio-check"></span>
                                        <span class="form-radio-label">
                                            <i class="fas fa-globe"></i>
                                            Tous les étudiants
                                        </span>
                                    </label>
                                    <label class="form-radio-option">
                                        <input type="radio" name="type" value="classe" 
                                               {{ old('type') == 'classe' ? 'checked' : '' }} required>
                                        <span class="form-radio-check"></span>
                                        <span class="form-radio-label">
                                            <i class="fas fa-chalkboard"></i>
                                            Classes spécifiques
                                        </span>
                                    </label>
                                    <label class="form-radio-option">
                                        <input type="radio" name="type" value="etudiant" 
                                               {{ old('type') == 'etudiant' ? 'checked' : '' }} required>
                                        <span class="form-radio-check"></span>
                                        <span class="form-radio-label">
                                            <i class="fas fa-user-graduate"></i>
                                            Étudiants spécifiques
                                        </span>
                                    </label>
                                </div>
                                @error('type')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Sélection par classe -->
                            <div id="classes_container" class="form-group" style="display: none;">
                                <label for="classes" class="form-label">Classes destinataires <span class="required">*</span></label>
                                <select class="form-select-multiple @error('classes') error @enderror"
                                    id="classes" name="classes[]" multiple>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}"
                                            data-filiere="{{ $classe->filiere_id }}"
                                            data-niveau="{{ $classe->niveau_etude_id }}"
                                            {{ (old('classes') && in_array($classe->id, old('classes'))) ? 'selected' : '' }}>
                                            {{ $classe->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('classes')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Sélection par étudiant -->
                            <div id="etudiants_container" class="form-group" style="display: none;">
                                <label for="etudiants" class="form-label">Étudiants destinataires <span class="required">*</span></label>
                                <select class="form-select-multiple @error('etudiants') error @enderror"
                                    id="etudiants" name="etudiants[]" multiple>
                                    @foreach($etudiants as $etudiant)
                                        <option value="{{ $etudiant->id }}"
                                                data-classe="{{ $etudiant->classe ? $etudiant->classe->id : '' }}"
                                                {{ (old('etudiants') && in_array($etudiant->id, old('etudiants'))) ? 'selected' : '' }}>
                                            {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('etudiants')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Options de publication -->
                    <div class="main-card mb-4">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-cog"></i>
                                Options de publication
                            </div>
                        </div>
                        <div class="main-card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Mode brouillon automatique :</strong> Votre annonce sera sauvegardée en brouillon. Cliquez sur "Envoyer l'annonce" pour la publier aux destinataires.
                            </div>

                            <!-- Champ caché pour définir le statut en brouillon par défaut -->
                            <input type="hidden" name="is_published" value="0">

                            <div class="form-group">
                                <label for="date_expiration" class="form-label">Date d'expiration <span class="required">*</span></label>
                                <input type="datetime-local" id="date_expiration" name="date_expiration" 
                                       class="form-input @error('date_expiration') error @enderror"
                                       value="{{ old('date_expiration', now()->addMonths(1)->format('Y-m-d\TH:i')) }}">
                                @error('date_expiration')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="priorite" class="form-label">Niveau d'urgence</label>
                                <select id="priorite" name="priorite" class="form-select-single @error('priorite') error @enderror">
                                    <option value="0" {{ old('priorite') == '0' ? 'selected' : '' }}>Normale</option>
                                    <option value="1" {{ old('priorite') == '1' ? 'selected' : '' }}>Importante</option>
                                    <option value="2" {{ old('priorite') == '2' ? 'selected' : '' }}>Urgente</option>
                                </select>
                                @error('priorite')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="main-card mb-4">
                        <div class="main-card-body">
                            <div class="form-actions">
                                <button type="submit" name="action" value="save_draft" class="btn-acasi secondary me-2">
                                    <i class="fas fa-save"></i>Sauvegarder en brouillon
                                </button>
                                <button type="submit" name="action" value="publish" class="btn-acasi primary">
                                    <i class="fas fa-paper-plane"></i>Envoyer l'annonce
                                </button>
                                <button type="reset" class="btn-acasi outline ms-2">
                                    <i class="fas fa-undo"></i>Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
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
        position: 'bottom',
        flip: true,
        allowHTML: true
    };

    // Fonction d'initialisation de Choices.js
    function initializeChoices(selectElement, customConfig = {}) {
        const selectId = selectElement.id;
        debugLog("Initialisation de Choices.js pour:", selectId);

        if (!selectElement) {
            debugError("Élément select non trouvé:", selectId);
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
            debugLog("Instance Choices.js créée avec succès pour:", selectId);
            return choices;
        } catch (error) {
            debugError("Erreur lors de la création de l'instance Choices.js:", error);
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
        });

        // Déclencher le changement initial
        $('input[name="type"]:checked').trigger('change');

        // Filtrage amélioré des classes avec Choices.js
        $('#filiere_filter, #niveau_filter').change(function() {
            const filiereId = $('#filiere_filter').val();
            const niveauId = $('#niveau_filter').val();
            const classesChoicesInstance = choicesInstances['classes'];
            
            debugLog('Filtres appliqués - Filière:', filiereId, 'Niveau:', niveauId);

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
            
            debugLog('Filtre classe pour étudiants:', classeId);

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
                        debugLog('Étudiant:', option.label, 'Classe:', option.customProperties.classe, 'Filtre:', classeId, 'Affiché:', show);
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
