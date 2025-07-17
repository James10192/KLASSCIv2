@extends('layouts.app')

@section('title', 'Nouvelle Inscription')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
    /* Variables CSS pour la cohérence */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.18);
        --shadow-soft: 0 8px 32px rgba(31, 38, 135, 0.37);
        --shadow-hover: 0 15px 35px rgba(31, 38, 135, 0.5);
        --border-radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Glassmorphism pour les conteneurs principaux */
    .card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-soft);
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
    }

    /* Styles ultra-modernes pour Choices.js */
    .choices {
        margin-bottom: 0;
        font-size: 14px;
        position: relative;
    }

    .choices__inner {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 2px solid transparent;
        border-radius: 12px;
        font-size: 14px;
        min-height: 48px;
        padding: 12px 16px 8px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .choices__inner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--primary-gradient);
        opacity: 0;
        transition: var(--transition);
        z-index: -1;
    }

    .choices__inner:focus-within {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .choices__inner:focus-within::before {
        opacity: 0.1;
    }

    .choices__list--dropdown {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
        padding: 16px 20px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        margin: 4px 8px;
    }

    .choices__item--selectable::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--primary-gradient);
        transition: var(--transition);
        z-index: -1;
    }

    .choices__item--selectable:hover {
        color: white;
        transform: translateX(5px);
    }

    .choices__item--selectable:hover::before {
        left: 0;
    }

    .choices__item--selectable.is-highlighted {
        background: var(--primary-gradient);
        color: white;
        transform: translateX(5px);
    }

    .choices__placeholder {
        color: #8b9dc3;
        opacity: 1;
        font-style: italic;
    }

    .choices__input {
        background-color: transparent;
        border: 0;
        font-size: 14px;
        margin-bottom: 0;
        padding: 0;
        color: #2d3748;
    }

    .choices__input:focus {
        outline: 0;
    }

    /* Bouton d'ajout ultra-moderne avec glassmorphism */
    .add-parent-container {
        display: flex;
        justify-content: center;
        margin: 3rem 0;
        padding: 2rem;
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 2px dashed rgba(102, 126, 234, 0.3);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .add-parent-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--primary-gradient);
        opacity: 0;
        transition: var(--transition);
        z-index: -1;
    }

    .add-parent-container:hover {
        border-color: #667eea;
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
    }

    .add-parent-container:hover::before {
        opacity: 0.1;
    }

    .btn-add-parent {
        background: var(--primary-gradient);
        border: none;
        color: white;
        padding: 16px 40px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: var(--transition);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }

    .btn-add-parent::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transition: var(--transition);
        transform: translate(-50%, -50%);
    }

    .btn-add-parent:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-add-parent:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.6);
        color: white;
    }

    .btn-add-parent:active {
        transform: translateY(-1px) scale(1.02);
    }

    .btn-add-parent i {
        margin-right: 12px;
        font-size: 18px;
        transition: var(--transition);
        position: relative;
        z-index: 1;
    }

    .btn-add-parent:hover i {
        transform: rotate(180deg) scale(1.2);
    }

    /* Boutons de suppression stylés */
    .remove-parent {
        background: var(--danger-gradient);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 500;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
        position: relative;
        overflow: hidden;
    }

    .remove-parent::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transition: var(--transition);
        transform: translate(-50%, -50%);
    }

    .remove-parent:hover::before {
        width: 200px;
        height: 200px;
    }

    .remove-parent:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 8px 25px rgba(250, 112, 154, 0.6);
        color: white;
    }

    /* Cartes de parents avec effet glassmorphism */
    .parent-item {
        transition: var(--transition);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-soft);
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        position: relative;
    }

    .parent-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
        transform: scaleX(0);
        transition: var(--transition);
    }

    .parent-item:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: var(--shadow-hover);
    }

    .parent-item:hover::before {
        transform: scaleX(1);
    }

    .parent-item .card-body {
        padding: 2rem;
        background: transparent;
    }

    /* Titres avec effet néon */
    .section-title {
        color: #2d3748;
        font-weight: 700;
        margin-bottom: 1.5rem;
        position: relative;
        padding-left: 20px;
        font-size: 1.5rem;
    }

    .section-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 30px;
        background: var(--primary-gradient);
        border-radius: 3px;
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    }

    .section-title::after {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 30px;
        background: var(--primary-gradient);
        border-radius: 3px;
        filter: blur(10px);
        opacity: 0.7;
    }

    /* Checkboxes personnalisées */
    .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid #667eea;
        border-radius: 6px;
        transition: var(--transition);
        position: relative;
    }

    .form-check-input:checked {
        background: var(--primary-gradient);
        border-color: #667eea;
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }

    .form-check-label {
        font-weight: 500;
        color: #4a5568;
        margin-left: 8px;
        transition: var(--transition);
    }

    .form-check:hover .form-check-label {
        color: #667eea;
    }

    /* Inputs avec effet glassmorphism */
    .form-control {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 12px 16px;
        transition: var(--transition);
        font-size: 14px;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.95);
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    /* Labels stylés */
    .form-label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* États d'erreur avec style moderne */
    .choices.is-invalid .choices__inner {
        border-color: #fa709a;
        box-shadow: 0 0 0 0.25rem rgba(250, 112, 154, 0.25);
    }

    .form-control.is-invalid {
        border-color: #fa709a;
        box-shadow: 0 0 0 0.25rem rgba(250, 112, 154, 0.25);
    }

    /* États de chargement et messages */
    .choices__list--dropdown .choices__item--loading,
    .choices__list--dropdown .choices__item--no-results {
        padding: 20px;
        text-align: center;
        color: #8b9dc3;
        font-style: italic;
        background: rgba(139, 157, 195, 0.1);
        border-radius: 8px;
        margin: 8px;
    }

    /* Animations avancées */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .parent-item {
        animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-add-parent {
        animation: float 3s ease-in-out infinite;
    }

    /* Boutons principaux stylés */
    .btn-primary {
        background: var(--primary-gradient);
        border: none;
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 600;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .btn-primary:hover::before {
        left: 100%;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        color: white;
    }

    .btn-secondary {
        background: rgba(108, 117, 125, 0.1);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(108, 117, 125, 0.3);
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 600;
        transition: var(--transition);
        color: #6c757d;
    }

    .btn-secondary:hover {
        background: rgba(108, 117, 125, 0.2);
        transform: translateY(-2px);
        color: #495057;
    }

    /* Alertes modernes */
    .alert {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        border-left: 4px solid;
        box-shadow: var(--shadow-soft);
    }

    .alert-info {
        border-left-color: #4facfe;
        background: rgba(79, 172, 254, 0.1);
    }

    .alert-warning {
        border-left-color: #fee140;
        background: rgba(254, 225, 64, 0.1);
    }

    .alert-danger {
        border-left-color: #fa709a;
        background: rgba(250, 112, 154, 0.1);
    }

    .alert-success {
        border-left-color: #00f2fe;
        background: rgba(0, 242, 254, 0.1);
    }

    /* Responsive design amélioré */
    @media (max-width: 768px) {
        .add-parent-container {
            margin: 2rem 0;
            padding: 1.5rem;
        }

        .btn-add-parent {
            padding: 14px 30px;
            font-size: 14px;
        }

        .section-title {
            font-size: 1.25rem;
        }

        .parent-item .card-body {
            padding: 1.5rem;
        }
    }

    /* Micro-interactions pour les icônes */
    .fas, .far {
        transition: var(--transition);
    }

    .card-title .fas:hover {
        transform: scale(1.2) rotate(5deg);
        color: #667eea;
    }

    /* Effet de survol pour les conteneurs */
    .container-fluid {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    /* Scrollbar personnalisée */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-gradient);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Nouvelle Inscription</h1>
                <p class="header-subtitle">Enregistrement d'un nouvel étudiant</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
                <form id="inscriptionForm" method="POST" action="{{ route('esbtp.inscriptions.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Informations personnelles -->
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <h5 class="section-title">Informations personnelles</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Nom</label>
                                <input type="text" class="form-control @error('nom') is-invalid @enderror" name="nom" value="{{ old('nom') }}" required>
                                @error('nom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Prénom(s)</label>
                                <input type="text" class="form-control @error('prenoms') is-invalid @enderror" name="prenoms" value="{{ old('prenoms') }}" required>
                                @error('prenoms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Genre</label>
                                <select class="form-control @error('genre') is-invalid @enderror" name="genre" required>
                                    <option value="">Sélectionner</option>
                                    <option value="M" {{ old('genre') == 'M' ? 'selected' : '' }}>Masculin</option>
                                    <option value="F" {{ old('genre') == 'F' ? 'selected' : '' }}>Féminin</option>
                                </select>
                                @error('genre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Date de naissance</label>
                                <input type="date" class="form-control @error('date_naissance') is-invalid @enderror" name="date_naissance" value="{{ old('date_naissance') }}" required>
                                @error('date_naissance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Lieu de naissance</label>
                                <input type="text" class="form-control @error('lieu_naissance') is-invalid @enderror" name="lieu_naissance" value="{{ old('lieu_naissance') }}" required>
                                @error('lieu_naissance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Nationalité</label>
                                <input type="text" class="form-control @error('nationalite') is-invalid @enderror" name="nationalite" value="{{ old('nationalite') }}" required>
                                @error('nationalite')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Téléphone</label>
                                <input type="tel" class="form-control @error('telephone') is-invalid @enderror" name="telephone" value="{{ old('telephone') }}" required placeholder="+225 XX XX XXX XXX">
                                @error('telephone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Photo <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif" required>
                                <small class="form-text text-muted">Formats acceptés: JPEG, PNG, JPG, GIF. Taille max: 2MB</small>
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Ville de résidence</label>
                                <input type="text" class="form-control @error('ville') is-invalid @enderror" name="ville" value="{{ old('ville') }}" required>
                                @error('ville')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Commune de résidence</label>
                                <input type="text" class="form-control @error('commune') is-invalid @enderror" name="commune" value="{{ old('commune') }}" required>
                                @error('commune')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Informations académiques -->
                    <div class="row mt-4">
                        <div class="col-md-12 mb-4">
                            <h5 class="section-title">Informations académiques</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Sélectionnez une classe. La filière, le niveau d'études et l'année universitaire seront automatiquement associés.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            @include('components.forms.class-selector')
                        </div>
                    </div>

                    <!-- Informations du parent -->
                    <div class="row mt-5">
                        <div class="col-md-12 mb-4">
                            <h5 class="section-title">Informations du/des parent(s)/tuteur(s)</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Ajoutez les informations des parents ou tuteurs de l'étudiant. Vous pouvez rechercher des parents existants ou en créer de nouveaux.
                        </div>
                        </div>
                    </div>

                    <!-- Container pour les parents -->
                    <div id="parents-container">
                        <!-- Premier parent (toujours présent) -->
                        <div class="parent-item card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-title mb-0 text-primary">
                                        <i class="fas fa-user-tie me-2"></i>Parent/Tuteur Principal
                                    </h6>
                                </div>

                                <input type="hidden" name="parents[0][type]" value="nouveau">

                                <div class="form-check mb-3">
                                    <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_0">
                                    <label class="form-check-label" for="parent_existant_0">
                                        <i class="fas fa-search me-1"></i>Sélectionner un parent existant
                                    </label>
                                </div>

                                <!-- Section pour parent existant -->
                                <div class="parent-existant-section" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-search me-1"></i>Rechercher un parent
                                        </label>
                                        <select class="form-control parent-select" id="parent_id_0" name="parents[0][parent_id]">
                                            <option></option>
                                    </select>
                                    </div>
                                    <!-- Champ relation pour parent existant -->
                                    <div class="form-group mt-2">
                                        <label class="form-label fw-bold">Relation avec l'étudiant</label>
                                        <select class="form-control" name="parents[0][relation]" data-required="true">
                                            <option value="Père">Père</option>
                                            <option value="Mère">Mère</option>
                                            <option value="Tuteur">Tuteur</option>
                                            <option value="Autre">Autre</option>
                                    </select>
                                    </div>
                                </div>

                                <!-- Section pour nouveau parent -->
                                <div class="parent-nouveau-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Nom</label>
                                                <input type="text" class="form-control" name="parents[0][nom]" data-required="true">
                                        </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Prénom(s)</label>
                                                <input type="text" class="form-control" name="parents[0][prenoms]" data-required="true">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Téléphone</label>
                                                <input type="tel" class="form-control" name="parents[0][telephone]" data-required="true" placeholder="+225 XX XX XXX XXX">
                                        </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Email</label>
                                                <input type="email" class="form-control" name="parents[0][email]">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Profession</label>
                                                <input type="text" class="form-control" name="parents[0][profession]">
                                        </div>
                                    </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Relation</label>
                                                <select class="form-control" name="parents[0][relation]" data-required="true">
                                                    <option value="Père">Père</option>
                                                    <option value="Mère">Mère</option>
                                                    <option value="Tuteur">Tuteur</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label fw-bold">Adresse</label>
                                        <textarea class="form-control" name="parents[0][adresse]" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton pour ajouter un parent supplémentaire -->
                    <div class="add-parent-container">
                        <button type="button" id="add-parent-btn" class="btn btn-add-parent">
                            <i class="fas fa-plus"></i>
                            Ajouter un parent/tuteur
                        </button>
                    </div>

                    <!-- Template pour un nouveau parent (caché par défaut) -->
                    <div id="parent-template" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-title mb-0 text-primary">
                                        <i class="fas fa-user-friends me-2"></i>Parent/Tuteur
                                    </h6>
                                    <button type="button" class="btn btn-sm remove-parent">
                                        <i class="fas fa-times me-1"></i> Supprimer
                                    </button>
                                </div>

                                <input type="hidden" name="parents[template][type]" value="nouveau">

                                <div class="form-check mb-3">
                                    <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_template">
                                    <label class="form-check-label" for="parent_existant_template">
                                        <i class="fas fa-search me-1"></i>Sélectionner un parent existant
                                    </label>
                                </div>

                                <!-- Section pour parent existant -->
                                <div class="parent-existant-section" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-search me-1"></i>Rechercher un parent
                                        </label>
                                        <select class="form-control parent-select" id="parent_id_template" name="parents[template][parent_id]">
                                            <option></option>
                                    </select>
                                    </div>
                                    <!-- Champ relation pour parent existant (template) -->
                                    <div class="form-group mt-2">
                                        <label class="form-label fw-bold">Relation avec l'étudiant</label>
                                        <select class="form-control" name="parents[template][relation]">
                                            <option value="Père">Père</option>
                                            <option value="Mère">Mère</option>
                                            <option value="Tuteur">Tuteur</option>
                                            <option value="Autre">Autre</option>
                                    </select>
                                    </div>
                                </div>

                                <!-- Section pour nouveau parent -->
                                <div class="parent-nouveau-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Nom</label>
                                                <input type="text" class="form-control" name="parents[template][nom]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Prénom(s)</label>
                                                <input type="text" class="form-control" name="parents[template][prenoms]">
                                            </div>
                                        </div>
                                        </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Téléphone</label>
                                                <input type="tel" class="form-control" name="parents[template][telephone]" placeholder="+225 XX XX XXX XXX">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Email</label>
                                                <input type="email" class="form-control" name="parents[template][email]">
                                            </div>
                                        </div>
                                        </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Profession</label>
                                                <input type="text" class="form-control" name="parents[template][profession]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Relation</label>
                                                <select class="form-control" name="parents[template][relation]">
                                                <option value="Père">Père</option>
                                                <option value="Mère">Mère</option>
                                                <option value="Tuteur">Tuteur</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                        </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label fw-bold">Adresse</label>
                                        <textarea class="form-control" name="parents[template][adresse]" rows="2"></textarea>
                                        </div>
                                    </div>
                                        </div>
                                    </div>
                                </div>

                    <!-- Section des frais et variants -->
                    <div class="row mt-4">
                        <div class="col-md-12 mb-4">
                            <h5 class="font-weight-bold">Frais d'inscription et options</h5>
                            <hr>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Configuration des frais :</strong> Sélectionnez les options pour chaque catégorie de frais. 
                                Les frais obligatoires sont pré-sélectionnés selon votre filière et niveau d'études.
                            </div>
                        </div>
                    </div>

                    <!-- Conteneur dynamique pour les frais -->
                    <div id="fraisContainer">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Chargement des frais...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Veuillez d'abord sélectionner une classe pour voir les frais applicables</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé des montants -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-calculator me-2"></i>
                                        Résumé des frais
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="resumeFrais">
                                        <div class="text-center text-muted py-3">
                                            Sélectionnez une classe et configurez les frais pour voir le résumé
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons de soumission -->
                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer l'inscription
                            </button>
                            <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<!-- Choices.js (bibliothèque pour les selects modernes) -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let parentIndex = 1; // Le premier parent a l'index 0

    // Fonction pour charger les parents existants
    function loadParentsExistants(selectElement) {
        if (!selectElement) return;
        
        fetch('/esbtp/api/parents/search', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.parents) {
                // Vider le select
                selectElement.innerHTML = '<option value="">Sélectionner un parent</option>';
                
                // Ajouter les options
                data.parents.forEach(parent => {
                    const option = document.createElement('option');
                    option.value = parent.id;
                    option.textContent = `${parent.nom} ${parent.prenoms} - ${parent.telephone}`;
                    selectElement.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des parents:', error);
        });
    }

    // Gestion des checkboxes "parent existant" - Version simplifiée et robuste
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('parent-existant-checkbox')) {
            const parentItem = e.target.closest('.parent-item, .card');
            const existantSection = parentItem.querySelector('.parent-existant-section');
            const nouveauSection = parentItem.querySelector('.parent-nouveau-section');
            const typeInput = parentItem.querySelector('input[name*="[type]"]');

            if (e.target.checked) {
                // Afficher section parent existant, masquer nouveau
                if (existantSection) {
                    existantSection.style.display = 'block';
                    
                    // Charger les parents existants
                    const selectElement = existantSection.querySelector('.parent-select');
                    if (selectElement) {
                        loadParentsExistants(selectElement);
                    }
                }
                if (nouveauSection) {
                    nouveauSection.style.display = 'none';
                }
                if (typeInput) typeInput.value = 'existant';
            } else {
                // Afficher section nouveau parent, masquer existant
                if (existantSection) {
                    existantSection.style.display = 'none';
                }
                if (nouveauSection) {
                    nouveauSection.style.display = 'block';
                }
                if (typeInput) typeInput.value = 'nouveau';
            }
        }
    });

    // Gestion de l'ajout de parents supplémentaires
    document.addEventListener('click', function(e) {
        if (e.target.id === 'add-parent-btn' || e.target.closest('#add-parent-btn')) {
            e.preventDefault();
            addNewParent();
        }
        
        // Gestion de la suppression de parents
        if (e.target.classList.contains('remove-parent') || e.target.closest('.remove-parent')) {
            e.preventDefault();
            const parentCard = e.target.closest('.card');
            if (parentCard) {
                parentCard.remove();
            }
        }
    });

    function addNewParent() {
        const template = document.getElementById('parent-template');
        const parentsContainer = document.getElementById('parents-container');
        
        if (!template || !parentsContainer) {
            console.error('Template ou container des parents introuvable');
            return;
        }

        // Cloner le template
        const newParent = template.cloneNode(true);
        newParent.id = '';
        newParent.style.display = 'block';
        
        // Mettre à jour les noms des champs avec l'index approprié
        const inputs = newParent.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace('[template]', `[${parentIndex}]`);
            }
            if (input.id) {
                input.id = input.id.replace('_template', `_${parentIndex}`);
            }
        });
        
        // Mettre à jour les labels
        const labels = newParent.querySelectorAll('label[for]');
        labels.forEach(label => {
            if (label.getAttribute('for')) {
                label.setAttribute('for', label.getAttribute('for').replace('_template', `_${parentIndex}`));
            }
        });
        
        // Ajouter une classe pour l'animation
        newParent.classList.add('parent-item');
        
        // Ajouter le nouvel élément avant le bouton d'ajout
        const addButton = document.querySelector('.add-parent-container');
        if (addButton) {
            addButton.parentNode.insertBefore(newParent, addButton);
        } else {
            parentsContainer.appendChild(newParent);
        }
        
        // Incrémenter l'index pour le prochain parent
        parentIndex++;
        
        // Faire défiler vers le nouveau parent
        newParent.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Gestion du chargement des frais quand une classe est sélectionnée
    document.addEventListener('change', function(e) {
        if (e.target.id === 'classe_id') {
            const classeId = e.target.value;
            const fraisContainer = document.getElementById('fraisContainer');
            
            if (classeId && fraisContainer) {
                // Afficher le loader
                fraisContainer.innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement des frais...</span>
                                </div>
                                <p class="mt-2 text-muted">Chargement des frais pour cette classe...</p>
                            </div>
                        </div>
                    </div>
                `;
                
                // Charger les frais
                fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erreur HTTP ! Statut: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            updateFraisContainer(data.frais);
                            updateResumeFrais();
                        } else {
                            fraisContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Erreur lors du chargement des frais : ${data.message}
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des frais:', error);
                        fraisContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Erreur lors du chargement des frais. Veuillez réessayer.
                            </div>
                        `;
                    });
            } else if (fraisContainer) {
                // Réinitialiser le conteneur si aucune classe n'est sélectionnée
                fraisContainer.innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement des frais...</span>
                                </div>
                                <p class="mt-2 text-muted">Veuillez d'abord sélectionner une classe pour voir les frais applicables</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
    });

    function updateFraisContainer(fraisData) {
        const fraisContainer = document.getElementById('fraisContainer');
        if (!fraisContainer) return;
        
        let html = '';
        
        // Séparer les frais obligatoires et optionnels
        const fraisObligatoires = fraisData.filter(f => f.is_mandatory);
        const fraisOptionnels = fraisData.filter(f => !f.is_mandatory);
        
        // Frais obligatoires
        if (fraisObligatoires.length > 0) {
            html += `
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="fas fa-star"></i> Frais obligatoires
                        </h6>
                    </div>
                </div>
            `;
            
            fraisObligatoires.forEach(frais => {
                html += generateFraisHTML(frais);
            });
        }
        
        // Frais optionnels
        if (fraisOptionnels.length > 0) {
            html += `
                <div class="row mb-4 mt-4">
                    <div class="col-md-12">
                        <h6 class="fw-bold text-info mb-3">
                            <i class="fas fa-plus-circle"></i> Frais optionnels
                        </h6>
                    </div>
                </div>
            `;
            
            fraisOptionnels.forEach(frais => {
                html += generateFraisHTML(frais);
            });
        }
        
        if (html === '') {
            html = `
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    Aucun frais configuré pour cette classe.
                </div>
            `;
        }
        
        fraisContainer.innerHTML = html;
    }

    function generateFraisHTML(frais) {
        const category = frais.category;
        const variants = frais.variants;
        const defaultAmount = frais.default_amount;
        const isMandatory = frais.is_mandatory;
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title d-flex justify-content-between">
                                <span>
                                    <i class="fas fa-${isMandatory ? 'star' : 'plus-circle'}"></i>
                                    ${category.name}
                                </span>
                                ${isMandatory ? '<span class="badge bg-danger">Obligatoire</span>' : '<span class="badge bg-info">Optionnel</span>'}
                            </h6>
                            <p class="card-text text-muted">${category.description || ''}</p>
                            
                            <div class="frais-options">
                                <div class="form-check mb-2">
                                    <input class="form-check-input frais-option" type="radio" 
                                           name="frais[${category.id}][variant_id]" 
                                           value="default" 
                                           id="frais_${category.id}_default"
                                           ${isMandatory ? 'checked' : ''}>
                                    <label class="form-check-label" for="frais_${category.id}_default">
                                        Option standard - ${defaultAmount.toLocaleString()} FCFA
                                    </label>
                                </div>
        `;
        
        // Ajouter les variants
        variants.forEach(variant => {
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input frais-option" type="radio" 
                           name="frais[${category.id}][variant_id]" 
                           value="${variant.id}" 
                           id="frais_${category.id}_${variant.id}">
                    <label class="form-check-label" for="frais_${category.id}_${variant.id}">
                        ${variant.name} - ${variant.amount.toLocaleString()} FCFA
                        ${variant.description ? `<small class="text-muted d-block">${variant.description}</small>` : ''}
                    </label>
                </div>
            `;
        });
        
        // Si ce n'est pas obligatoire, ajouter une option "Ne pas souscrire"
        if (!isMandatory) {
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input frais-option" type="radio" 
                           name="frais[${category.id}][variant_id]" 
                           value="" 
                           id="frais_${category.id}_none"
                           checked>
                    <label class="form-check-label" for="frais_${category.id}_none">
                        <em>Ne pas souscrire à ce frais</em>
                    </label>
                </div>
            `;
        }
        
        html += `
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        return html;
    }

    function updateResumeFrais() {
        const resumeContainer = document.getElementById('resumeFrais');
        if (!resumeContainer) return;
        
        const selectedOptions = document.querySelectorAll('.frais-option:checked');
        let totalAmount = 0;
        let resumeHTML = '';
        
        selectedOptions.forEach(option => {
            if (option.value && option.value !== '') {
                const label = option.closest('.form-check').querySelector('label').textContent;
                const match = label.match(/(\d+(?:\.\d{3})*)/);
                if (match) {
                    const amount = parseInt(match[1].replace(/\./g, ''));
                    totalAmount += amount;
                    resumeHTML += `<div class="d-flex justify-content-between">
                        <span>${label.split(' - ')[0]}</span>
                        <span>${amount.toLocaleString()} FCFA</span>
                    </div>`;
                }
            }
        });
        
        if (resumeHTML) {
            resumeHTML += `
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span>${totalAmount.toLocaleString()} FCFA</span>
                </div>
            `;
            resumeContainer.innerHTML = resumeHTML;
        } else {
            resumeContainer.innerHTML = '<div class="text-center text-muted py-3">Aucun frais sélectionné</div>';
        }
    }

    // Écouter les changements dans les options de frais
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('frais-option')) {
            updateResumeFrais();
        }
    });
});
</script>
@endpush