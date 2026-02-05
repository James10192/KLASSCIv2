@extends('layouts.app')

@section('title', 'Nouvelle Inscription')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
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
                    <input type="hidden" name="duplicate_override" id="duplicate_override" value="0">
                    
                    <!-- Affichage des erreurs de validation -->
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation :</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div id="duplicate-warning" class="alert alert-warning d-none mt-4" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Doublons potentiels détectés.</strong>
                                <span id="duplicate-warning-text">Veuillez vérifier les informations avant de continuer.</span>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="show-duplicates-modal">
                                        Voir les doublons
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    
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
                                <select class="form-control @error('sexe') is-invalid @enderror" name="sexe" required>
                                    <option value="">Sélectionner</option>
                                    <option value="M" {{ old('sexe') == 'M' ? 'selected' : '' }}>Masculin</option>
                                    <option value="F" {{ old('sexe') == 'F' ? 'selected' : '' }}>Féminin</option>
                                </select>
                                @error('sexe')
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
                                <select class="form-control @error('nationalite') is-invalid @enderror" name="nationalite" required>
                                    @include('esbtp.partials.nationality-options', ['selected' => old('nationalite')])
                                </select>
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
                                <input type="email" class="form-control @error('email_personnel') is-invalid @enderror" name="email_personnel" value="{{ old('email_personnel') }}">
                                @error('email_personnel')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4" id="matriculeContainer">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">
                                    Matricule <span class="text-danger">*</span>
                                    <span id="matriculeMode" class="badge bg-info ms-1"></span>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('matricule') is-invalid @enderror"
                                           name="matricule" id="matriculeInput" value="{{ old('matricule') }}"
                                           placeholder="Ex: MESBTP25-0001">
                                    <button type="button" class="btn btn-outline-primary" id="generateMatriculeBtn"
                                            style="display: none;">
                                        <i class="fas fa-magic"></i> Générer
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="checkMatriculeBtn"
                                            style="display: none;">
                                        <i class="fas fa-search"></i> Vérifier
                                    </button>
                                </div>
                                <small class="form-text text-muted" id="matriculeHelp">Matricule unique de l'étudiant</small>
                                <div id="matriculeStatus" class="mt-1"></div>
                                @error('matricule')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Photo <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif">
                                <small class="form-text text-muted">Formats acceptés: JPEG, PNG, JPG, GIF. Taille max: 2MB</small>
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
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

                    <!-- Statut d'affectation MESRS -->
                    <div class="row mt-4">
                        <div class="col-md-12 mb-3">
                            <h6 class="section-subtitle">
                                <i class="fas fa-university me-2" style="color: #667eea;"></i>
                                Statut d'affectation gouvernementale
                            </h6>
                            <div class="alert alert-info" style="border-left: 4px solid #667eea;">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle me-2 mt-1" style="color: #667eea;"></i>
                                    <div>
                                        <strong>Statut d'affectation MESRS :</strong> Précisez le statut d'affectation de l'étudiant selon le système gouvernemental ivoirien.
                                        <br><small class="text-muted">Ce statut détermine la prise en charge étatique et les frais applicables.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-check-circle me-1" style="color: #28a745;"></i>
                                    Statut d'affectation
                                </label>
                                <select class="form-select @error('affectation_status') is-invalid @enderror" 
                                        name="affectation_status" 
                                        id="affectation_status" 
                                        required 
                                        onchange="updateAffectationInfo()">
                                    <option value="">Sélectionnez le statut d'affectation</option>
                                    <option value="affecté" {{ old('affectation_status') == 'affecté' ? 'selected' : '' }}>
                                        Affecté
                                    </option>
                                    <option value="réaffecté" {{ old('affectation_status') == 'réaffecté' ? 'selected' : '' }}>
                                        Réaffecté
                                    </option>
                                    <option value="non_affecté" {{ old('affectation_status') == 'non_affecté' ? 'selected' : '' }}>
                                        Non affecté
                                    </option>
                                </select>
                                @error('affectation_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Le statut influence les frais applicables selon la prise en charge étatique
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Information contextuelle sur le statut sélectionné -->
                            <div id="affectation-info" class="card border-0" style="background: rgba(255, 255, 255, 0.8); min-height: 120px;">
                                <div class="card-body">
                                    <h6 class="card-title text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Information sur le statut
                                    </h6>
                                    <p class="card-text text-muted small">
                                        Sélectionnez un statut d'affectation pour voir les détails.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations du parent -->
                    <div class="row mt-5">
                        <div class="col-md-12 mb-4">
                            <h5 class="section-title">Informations du/des parent(s)/tuteur(s)</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Ajoutez les informations des parents ou tuteurs de l'étudiant. Cette section est facultative.
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
                                        <select class="form-control" name="parents[0][relation]">
                                            <option value="">Sélectionner une relation</option>
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
                                                <input type="text" class="form-control" name="parents[0][nom]">
                                        </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Prénom(s)</label>
                                                <input type="text" class="form-control" name="parents[0][prenoms]">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Téléphone</label>
                                                <input type="tel" class="form-control" name="parents[0][telephone]" placeholder="+225 XX XX XXX XXX">
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
                                                <select class="form-control" name="parents[0][relation]">
                                                    <option value="">Sélectionner une relation</option>
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
                                            <option value="">Sélectionner une relation</option>
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
                                            <option value="">Sélectionner une relation</option>
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


<!-- Modal Doublons -->
<div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateModalLabel">Doublons potentiels détectés</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Les étudiants suivants correspondent aux informations saisies. Vérifiez qu'il ne s'agit pas de la même personne.</p>
                <div id="duplicate-modal-content">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Aucun doublon détecté.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="continue-with-duplicate">Continuer l'inscription</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Choices.js (bibliothèque pour les selects modernes) -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let parentIndex = 1; // Le premier parent a l'index 0
    let isLoadingFrais = false; // Flag pour éviter les déclenchements multiples

    const duplicateForm = document.getElementById('inscriptionForm');
    const duplicateOverrideInput = document.getElementById('duplicate_override');
    const duplicateWarning = document.getElementById('duplicate-warning');
    const duplicateWarningText = document.getElementById('duplicate-warning-text');
    const duplicateModalElement = document.getElementById('duplicateModal');
    const duplicateModalContent = document.getElementById('duplicate-modal-content');
    const showDuplicatesBtn = document.getElementById('show-duplicates-modal');
    const continueWithDuplicateBtn = document.getElementById('continue-with-duplicate');
    const duplicateCheckUrl = "{{ route('esbtp.inscriptions.duplicates') }}";
    let duplicateModalInstance = null;
    if (duplicateModalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        duplicateModalInstance = new bootstrap.Modal(duplicateModalElement);
    }
    const duplicateState = { results: [], override: false };
    const duplicateInitialData = @json(session('duplicate_suggestions', []));
    const duplicateInitialOverride = {{ old('duplicate_override', '0') === '1' ? 'true' : 'false' }};
    if (Array.isArray(duplicateInitialData) && duplicateInitialData.length) {
        duplicateState.results = duplicateInitialData;
    }
    duplicateState.override = duplicateInitialOverride;
    if (duplicateState.override && duplicateOverrideInput) {
        duplicateOverrideInput.value = '1';
    }
    let duplicateTimer = null;

    function resetDuplicateOverride() {
        duplicateState.override = false;
        if (duplicateOverrideInput) {
            duplicateOverrideInput.value = '0';
        }
    }

    function scheduleDuplicateCheck() {
        if (!duplicateCheckUrl) {
            return;
        }
        if (duplicateTimer) {
            clearTimeout(duplicateTimer);
        }
        duplicateTimer = setTimeout(runDuplicateCheck, 600);
        resetDuplicateOverride();
    }

    function runDuplicateCheck() {
        if (!duplicateForm || !duplicateCheckUrl) {
            return;
        }

        const nomField = duplicateForm.querySelector('input[name="nom"]');
        const prenomsField = duplicateForm.querySelector('input[name="prenoms"]');

        if (!nomField || !prenomsField) {
            return;
        }

        const nomValue = nomField.value.trim();
        const prenomsValue = prenomsField.value.trim();

        if (nomValue.length < 2 && prenomsValue.length < 2) {
            duplicateState.results = [];
            updateDuplicateUI();
            return;
        }

        const dateField = duplicateForm.querySelector('input[name="date_naissance"]');
        const sexeField = duplicateForm.querySelector('select[name="sexe"]');

        const params = new URLSearchParams();
        params.append('nom', nomValue);
        params.append('prenoms', prenomsValue);
        if (dateField && dateField.value) {
            params.append('date_naissance', dateField.value);
        }
        if (sexeField && sexeField.value) {
            params.append('sexe', sexeField.value);
        }

        fetch(`${duplicateCheckUrl}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.ok ? response.json() : Promise.reject(response))
        .then(data => {
            duplicateState.results = Array.isArray(data.duplicates) ? data.duplicates : [];
            resetDuplicateOverride();
            updateDuplicateUI();
        })
        .catch(() => {
            duplicateState.results = [];
            updateDuplicateUI();
        });
    }

    function updateDuplicateUI() {
        if (!duplicateWarning || !duplicateWarningText) {
            return;
        }

        if (duplicateState.results.length > 0) {
            if (duplicateState.override) {
                duplicateWarning.classList.add('d-none');
                if (duplicateOverrideInput) {
                    duplicateOverrideInput.value = '1';
                }
                return;
            }

            duplicateWarning.classList.remove('d-none');
            duplicateWarningText.textContent = `Nous avons trouvé ${duplicateState.results.length} étudiant(s) avec un profil similaire.`;
            renderDuplicateModal();
        } else {
            duplicateWarning.classList.add('d-none');
            if (duplicateOverrideInput) {
                duplicateOverrideInput.value = '0';
            }
            if (duplicateModalInstance) {
                duplicateModalInstance.hide();
            }
            if (duplicateModalContent) {
                duplicateModalContent.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>Aucun doublon détecté.
                    </div>
                `;
            }
        }
    }

    function renderDuplicateModal() {
        if (!duplicateModalContent) {
            return;
        }

        if (duplicateState.results.length === 0) {
            duplicateModalContent.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Aucun doublon détecté.
                </div>
            `;
            return;
        }

        const rows = duplicateState.results.map(item => {
            const score = Number(item.score ?? 0);
            const badgeClass = score >= 80 ? 'bg-danger' : (score >= 60 ? 'bg-warning text-dark' : 'bg-secondary');
            const tokensForDisplay = Array.isArray(item.matched_tokens) ? item.matched_tokens.map(token => token.toUpperCase()) : [];
            const matchedTokens = tokensForDisplay.length
                ? `<div class="text-muted small">Correspondances : ${tokensForDisplay.join(', ')}</div>`
                : '';
            const matricule = item.matricule ? item.matricule : 'N/A';
            const date = item.date_naissance ? item.date_naissance : 'N/A';
            const sexe = item.sexe ? item.sexe : 'N/A';

            return `
                <tr>
                    <td>
                        <div class="fw-semibold">${item.full_name ?? ''}</div>
                        <div class="text-muted small">Matricule : ${matricule}</div>
                        ${matchedTokens}
                    </td>
                    <td>${date}</td>
                    <td>${sexe}</td>
                    <td><span class="badge ${badgeClass}">${Math.round(score)}%</span></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end flex-wrap gap-2">
                            <button type="button" class="btn-acasi primary mark-duplicate" data-show-url="${item.show_url ?? '#'}">
                                <i class="fas fa-user-check me-1"></i>C'est la même personne
                            </button>
                            <button type="button" class="btn-acasi secondary view-duplicate" data-show-url="${item.show_url ?? '#'}">
                                <i class="fas fa-external-link-alt me-1"></i>Voir la fiche
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        duplicateModalContent.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Étudiant</th>
                            <th>Date de naissance</th>
                            <th>Genre</th>
                            <th>Score</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    updateDuplicateUI();

    const nomInput = duplicateForm ? duplicateForm.querySelector('input[name="nom"]') : null;
    const prenomsInput = duplicateForm ? duplicateForm.querySelector('input[name="prenoms"]') : null;
    const dateInput = duplicateForm ? duplicateForm.querySelector('input[name="date_naissance"]') : null;
    const sexeSelect = duplicateForm ? duplicateForm.querySelector('select[name="sexe"]') : null;

    if (nomInput) nomInput.addEventListener('input', scheduleDuplicateCheck);
    if (prenomsInput) prenomsInput.addEventListener('input', scheduleDuplicateCheck);
    if (dateInput) dateInput.addEventListener('change', scheduleDuplicateCheck);
    if (sexeSelect) sexeSelect.addEventListener('change', scheduleDuplicateCheck);

    if (showDuplicatesBtn) {
        showDuplicatesBtn.addEventListener('click', function() {
            renderDuplicateModal();
            if (duplicateModalInstance) {
                duplicateModalInstance.show();
            }
        });
    }

    if (continueWithDuplicateBtn) {
        continueWithDuplicateBtn.addEventListener('click', function() {
            duplicateState.override = true;
            if (duplicateOverrideInput) {
                duplicateOverrideInput.value = '1';
            }
            if (duplicateModalInstance) {
                duplicateModalInstance.hide();
            }
            if (duplicateWarning) {
                duplicateWarning.classList.add('d-none');
            }
        });
    }

    document.addEventListener('click', function(e) {
        const markButton = e.target.closest('.mark-duplicate');
        if (markButton) {
            const url = markButton.getAttribute('data-show-url');
            if (url) {
                window.location.href = url;
            }
            return;
        }

        const viewButton = e.target.closest('.view-duplicate');
        if (viewButton) {
            const url = viewButton.getAttribute('data-show-url');
            if (url) {
                window.open(url, '_blank');
            }
        }
    });

    if (duplicateForm) {
        duplicateForm.addEventListener('submit', function(e) {
            if (duplicateState.results.length > 0 && !duplicateState.override) {
                e.preventDefault();
                renderDuplicateModal();
                if (duplicateModalInstance) {
                    duplicateModalInstance.show();
                } else {
                    alert('Des doublons potentiels ont été détectés. Veuillez vérifier avant de continuer.');
                }
            }
        });
    }

    if ((nomInput && nomInput.value.trim().length > 1) || (prenomsInput && prenomsInput.value.trim().length > 1)) {
        scheduleDuplicateCheck();
    }

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
            debugError('Erreur lors du chargement des parents:', error);
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
            debugError('Template ou container des parents introuvable');
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

    // Sauvegarde des données du formulaire avant changement de classe
    function saveFormData() {
        const formData = {};
        const form = document.getElementById('inscriptionForm');
        const inputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"], select, textarea');
        
        inputs.forEach(input => {
            if (input.name && input.value) {
                formData[input.name] = input.value;
            }
        });
        
        // Sauvegarder la photo sélectionnée (juste le nom du fichier pour info)
        const photoInput = document.querySelector('input[name="photo"]');
        if (photoInput && photoInput.files.length > 0) {
            formData['photo_filename'] = photoInput.files[0].name;
        }
        
        return formData;
    }
    
    // Restauration des données du formulaire après chargement
    function restoreFormData(formData) {
        Object.keys(formData).forEach(name => {
            const input = document.querySelector(`[name="${name}"]`);
            if (input && name !== 'photo_filename') {
                input.value = formData[name];
            }
        });
        
        // Afficher info sur la photo sélectionnée
        if (formData['photo_filename']) {
            const photoInput = document.querySelector('input[name="photo"]');
            if (photoInput && photoInput.files.length === 0) {
                // Créer un message d'info sur la photo précédemment sélectionnée
                const infoDiv = document.createElement('div');
                infoDiv.className = 'alert alert-info mt-2';
                infoDiv.innerHTML = `<small><i class="fas fa-info-circle"></i> Photo précédemment sélectionnée: ${formData['photo_filename']}. Veuillez la resélectionner si nécessaire.</small>`;
                photoInput.parentNode.appendChild(infoDiv);
            }
        }
    }

    // Gestion du chargement des frais quand une classe est sélectionnée
    document.addEventListener('change', function(e) {
        if (e.target.id === 'classe_id') {
            // Empêcher les déclenchements multiples
            if (isLoadingFrais) {
                debugLog('Chargement des frais déjà en cours, ignoré');
                return;
            }
            
            // e.preventDefault(); // Empêcher le comportement par défaut - TEMPORAIREMENT DESACTIVE
            // e.stopPropagation(); // Empêcher la propagation - TEMPORAIREMENT DESACTIVE
            
            const classeId = e.target.value;
            const fraisContainer = document.getElementById('fraisContainer');
            
            debugLog('Changement de classe détecté:', classeId);
            
            if (classeId && fraisContainer) {
                isLoadingFrais = true; // Marquer comme en cours
                
                // Sauvegarder les données du formulaire
                const savedData = saveFormData();
                debugLog('Données sauvegardées:', savedData);
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
                
                // Récupérer le statut d'affectation sélectionné
                const affectationStatus = document.getElementById('affectation_status')?.value || 'affecté';
                
                // Charger les frais avec le statut d'affectation
                fetch(`/esbtp/inscriptions/frais-by-classe/${classeId}?affectation_status=${encodeURIComponent(affectationStatus)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erreur HTTP ! Statut: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        debugLog('Données frais reçues:', data);
                        if (data.success) {
                            updateFraisContainer(data.frais, data.has_unconfigured_fees, data.configure_url);
                            updateResumeFrais();
                            
                            // Restaurer les données du formulaire après chargement des frais
                            setTimeout(() => {
                                restoreFormData(savedData);
                                debugLog('Données restaurées');
                            }, 100);
                        } else {
                            fraisContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Erreur lors du chargement des frais : ${data.message}
                                </div>
                            `;
                        }
                        
                        isLoadingFrais = false; // Réinitialiser le flag
                    })
                    .catch(error => {
                        debugError('Erreur lors du chargement des frais:', error);
                        fraisContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Erreur lors du chargement des frais. Veuillez réessayer.
                            </div>
                        `;
                        
                        // Restaurer les données même en cas d'erreur
                        setTimeout(() => {
                            restoreFormData(savedData);
                            debugLog('Données restaurées après erreur');
                        }, 100);
                        
                        isLoadingFrais = false; // Réinitialiser le flag
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
                isLoadingFrais = false; // Réinitialiser le flag
            }
        }
    });

    // Empêcher la soumission du formulaire pendant le chargement des frais
    document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
        if (isLoadingFrais) {
            e.preventDefault();
            alert('Veuillez attendre la fin du chargement des frais avant de soumettre le formulaire.');
            return false;
        }
        
        // DEBUG : Vérifier les données avant soumission
        debugLog('SUBMIT EVENT TRIGGERED!');
        debugLog('Event details:', e);
        debugLog('Target:', e.target);
        
        const form = e.target;
        const formData = new FormData(form);
        
        // Informations sur la photo
        const photoInput = form.querySelector('input[name="photo"]');
        if (photoInput && photoInput.files.length > 0) {
            const file = photoInput.files[0];
            debugLog(`Photo: ${file.name} (${file.size} bytes, ${file.type})`);
        } else {
            debugLog('Photo: AUCUNE SÉLECTIONNÉE');
        }
        
        // Informations sur les autres champs principaux
        debugLog(`Nom: ${formData.get('nom') || 'VIDE'}`);
        debugLog(`Prénom: ${formData.get('prenoms') || 'VIDE'}`);
        debugLog(`Matricule: ${formData.get('matricule') || 'VIDE'}`);
        debugLog(`Classe: ${formData.get('classe_id') || 'VIDE'}`);
        
        // Vérifier les frais sélectionnés
        const selectedFrais = document.querySelectorAll('.frais-option:checked');
        debugLog(`Frais sélectionnés: ${selectedFrais.length}`);
        
        debugLog('Form data complète:', [...formData.entries()]);
        debugLog('Photo file:', photoInput ? photoInput.files[0] : 'null');
        
        // Laisser le formulaire se soumettre normalement
    });

    function updateFraisContainer(fraisData, hasUnconfiguredFees, configureUrl) {
        const fraisContainer = document.getElementById('fraisContainer');
        if (!fraisContainer) return;
        
        let html = '';
        
        // Afficher message si des frais ne sont pas configurés
        if (hasUnconfiguredFees) {
            html += `
                <div class="alert alert-warning border-start border-warning border-4 shadow-sm mb-4">
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Configuration incomplète
                            </h6>
                            <p class="mb-2">
                                Certaines catégories de frais pour cette classe n'ont pas de variantes configurées. 
                                Les montants par défaut seront utilisés.
                            </p>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <a href="${configureUrl}" target="_blank" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-cog me-1"></i>
                                Configuration rapide
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }
        
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
        
        if (fraisData.length === 0) {
            html += `
                <div class="alert alert-info border-start border-info border-4">
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                Aucun frais configuré
                            </h6>
                            <p class="mb-0">
                                Aucune catégorie de frais n'est configurée pour cette classe (filière/niveau).
                                Veuillez d'abord configurer les frais avant de procéder à l'inscription.
                            </p>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <a href="${configureUrl}" target="_blank" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-cog me-1"></i>
                                Configurer les frais
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }
        
        fraisContainer.innerHTML = html;
    }

    function generateFraisHTML(frais) {
        const category = frais.category;
        const options = frais.options || frais.variants || []; // Support des deux noms
        const defaultAmount = frais.default_amount;
        const isMandatory = frais.is_mandatory;
        const isConfigured = frais.is_configured;
        const configurationType = frais.configuration_type;
        const categoryType = frais.category_type || 'academic';
        
        // Icônes selon le type de catégorie
        const typeIcons = {
            'academic': 'graduation-cap',
            'service': 'cogs', 
            'administrative': 'file-alt'
        };
        const icon = typeIcons[categoryType] || (isMandatory ? 'star' : 'plus-circle');
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card ${!isConfigured ? 'border-warning' : ''}">
                        <div class="card-body">
                            <h6 class="card-title d-flex justify-content-between">
                                <span>
                                    <i class="fas fa-${icon}"></i>
                                    ${category.name}
                                    ${!isConfigured ? '<i class="fas fa-exclamation-triangle text-warning ms-1" title="Pas d\'options configurées"></i>' : ''}
                                </span>
                                <div>
                                    ${isMandatory ? '<span class="badge bg-danger">Obligatoire</span>' : '<span class="badge bg-info">Optionnel</span>'}
                                    <span class="badge bg-secondary ms-1">${categoryType.charAt(0).toUpperCase() + categoryType.slice(1)}</span>
                                </div>
                            </h6>
                            <p class="card-text text-muted">${category.description || ''}</p>
                            
                            ${configurationType === 'variant' ? `
                                <div class="alert alert-success alert-sm mb-3">
                                    <small><i class="fas fa-check-circle me-1"></i>Tarif configuré pour cette classe.</small>
                                </div>
                            ` : configurationType === 'rule' ? `
                                <div class="alert alert-info alert-sm mb-3">
                                    <small><i class="fas fa-cog me-1"></i>Tarif configuré par règle de classe.</small>
                                </div>
                            ` : configurationType === 'configuration' ? `
                                <div class="alert alert-success alert-sm mb-3">
                                    <small><i class="fas fa-check-circle me-1"></i>Tarif configuré pour cette classe (${category.filiere || 'filière'} - ${category.niveau || 'niveau'}).</small>
                                </div>
                            ` : configurationType === 'global_options' ? `
                                <div class="alert alert-primary alert-sm mb-3">
                                    <small><i class="fas fa-globe me-1"></i>Options globales disponibles pour ce service.</small>
                                </div>
                            ` : `
                                <div class="alert alert-warning alert-sm mb-3">
                                    <small><i class="fas fa-info-circle me-1"></i>Montant par défaut utilisé (non configuré pour cette classe).</small>
                                </div>
                            `}
                            
                            <div class="frais-options">`;
        
        // Pour les frais obligatoires ou ayant un montant par défaut configuré
        if (isMandatory || configurationType === 'rule' || configurationType === 'variant' || configurationType === 'configuration') {
            html += `
                                <div class="form-check mb-2">
                                    <input class="form-check-input frais-option" type="radio" 
                                           name="frais[${category.id}][variant_id]" 
                                           value="default" 
                                           id="frais_${category.id}_default"
                                           ${isMandatory ? 'checked' : ''}>
                                    <label class="form-check-label" for="frais_${category.id}_default">
                                        ${configurationType === 'variant' ? 'Tarif configuré pour cette classe' : 
                                          configurationType === 'rule' ? 'Tarif configuré' : 
                                          configurationType === 'configuration' ? 'Tarif configuré pour cette classe' :
                                          'Montant par défaut'} - <strong>${(parseFloat(defaultAmount) || 0).toLocaleString()} FCFA</strong>
                                    </label>
                                </div>
            `;
        }
        
        // Ajouter les options configurées
        if (isConfigured && options.length > 0) {
            options.forEach(option => {
                // Calculer le montant total (montant de base + montant additionnel) avec sécurité
                const baseAmount = parseFloat(defaultAmount) || 0;
                const additionalAmount = parseFloat(option.additional_amount) || parseFloat(option.amount) || 0;
                let totalAmount = 0;
                
                if (configurationType === 'global_options') {
                    totalAmount = baseAmount + additionalAmount;
                } else {
                    totalAmount = additionalAmount || baseAmount;
                }
                
                // Sécurité : s'assurer que totalAmount est un nombre valide
                if (isNaN(totalAmount) || totalAmount < 0) {
                    totalAmount = 0;
                }
                
                html += `
                    <div class="form-check mb-2">
                        <input class="form-check-input frais-option" type="radio" 
                               name="frais[${category.id}][variant_id]" 
                               value="${option.id}" 
                               id="frais_${category.id}_${option.id}">
                        <label class="form-check-label" for="frais_${category.id}_${option.id}">
                            ${option.name} - <strong>${totalAmount.toLocaleString()} FCFA</strong>
                            ${option.description ? `<small class="text-muted d-block">${option.description}</small>` : ''}
                        </label>
                    </div>
                `;
            });
        }
        
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
                        <em>Ne pas souscrire à ce service</em>
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
                
                // Debug : afficher la structure pour comprendre le problème
                debugLog('DEBUG - Option sélectionnée:', option);
                debugLog('DEBUG - Label:', label);
                
                // Récupérer le nom de la catégorie depuis le titre de la card
                const fraisCard = option.closest('.card');
                debugLog('DEBUG - FraisCard trouvée:', fraisCard);
                
                // Essayer plusieurs sélecteurs pour trouver le titre
                let titleElement = fraisCard ? fraisCard.querySelector('.card-title') : null;
                if (!titleElement) {
                    titleElement = fraisCard ? fraisCard.querySelector('h6') : null;
                }
                if (!titleElement) {
                    titleElement = fraisCard ? fraisCard.querySelector('.card-header h6') : null;
                }
                
                debugLog('DEBUG - TitleElement:', titleElement);
                debugLog('DEBUG - TitleElement text:', titleElement ? titleElement.textContent : 'null');
                
                // Extraire le nom de la catégorie plus robustement
                let categoryName = 'Frais';
                if (titleElement && titleElement.textContent) {
                    // Nettoyer le texte en supprimant les icônes et texte extra
                    let text = titleElement.textContent.trim();
                    // Supprimer les icônes Font Awesome (peuvent apparaître comme des caractères étranges)
                    text = text.replace(/[\uF000-\uF8FF]|\uD83C[\uDF00-\uDFFF]|\uD83D[\uDC00-\uDE4F]/g, '');
                    // Prendre seulement la première ligne/partie avant les badges
                    const parts = text.split(/\s+/);
                    if (parts.length >= 2 && (parts[0] === 'Frais' || parts[0] === 'frais')) {
                        categoryName = parts.slice(0, 3).join(' '); // "Frais de inscription" par exemple
                    } else {
                        categoryName = text.split('\n')[0].trim();
                    }
                }
                
                debugLog('DEBUG - CategoryName final:', categoryName);
                
                // Alternative : essayer d'extraire l'ID de catégorie du name de l'input
                const nameAttr = option.getAttribute('name');
                debugLog('DEBUG - Name attribute:', nameAttr);
                
                // Si on n'a pas trouvé le nom via le DOM, utiliser une méthode alternative
                if (categoryName === 'Frais' && nameAttr) {
                    const categoryIdMatch = nameAttr.match(/frais\[(\d+)\]/);
                    if (categoryIdMatch) {
                        const categoryId = categoryIdMatch[1];
                        // Essayer de trouver le nom via le data-attribute ou créer un nom générique
                        categoryName = `Catégorie ${categoryId}`;
                        debugLog('DEBUG - CategoryName via name attr:', categoryName);
                    }
                }
                
                // Améliorer la regex pour gérer différents formats de nombres
                const match = label.match(/(\d+(?:[.,\s]\d{3})*)/);
                if (match) {
                    // Nettoyer le nombre en supprimant tous les séparateurs
                    const cleanNumber = match[1].replace(/[.,\s]/g, '');
                    const amount = parseInt(cleanNumber) || 0;
                    totalAmount += amount;
                    resumeHTML += `<div class="d-flex justify-content-between">
                        <span>${categoryName}</span>
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
                    <span>${(totalAmount || 0).toLocaleString()} FCFA</span>
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
    
    // Fonction pour mettre à jour les informations sur le statut d'affectation
    window.updateAffectationInfo = function() {
        const select = document.getElementById('affectation_status');
        const infoDiv = document.getElementById('affectation-info');
        const value = select.value;
        
        let content = '';
        
        switch(value) {
            case 'affecté':
                content = `
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Étudiant Affecté par l'État
                        </h6>
                        <div class="text-success small mb-2">
                            <strong>Plateforme :</strong> bac.mesrs-ci.net
                        </div>
                        <p class="card-text small text-muted mb-1">
                            • Affectation officielle par le MESRS après le BAC
                        </p>
                        <p class="card-text small text-muted mb-1">
                            • Éligible à une subvention étatique
                        </p>
                        <p class="card-text small text-success">
                            <i class="fas fa-coins me-1"></i>
                            <strong>Frais réduits applicables</strong>
                        </p>
                    </div>
                `;
                break;
            case 'réaffecté':
                content = `
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="fas fa-sync-alt me-1"></i>
                            Étudiant Réaffecté par la DOB
                        </h6>
                        <div class="text-warning small mb-2">
                            <strong>Organisme :</strong> Direction de l'Orientation et des Bourses
                        </div>
                        <p class="card-text small text-muted mb-1">
                            • Initialement affecté dans un autre établissement
                        </p>
                        <p class="card-text small text-muted mb-1">
                            • Réaffectation officielle après demande
                        </p>
                        <p class="card-text small text-warning">
                            <i class="fas fa-coins me-1"></i>
                            <strong>Subvention étatique maintenue</strong>
                        </p>
                    </div>
                `;
                break;
            case 'non_affecté':
                content = `
                    <div class="card-body">
                        <h6 class="card-title text-danger">
                            <i class="fas fa-times-circle me-1"></i>
                            Étudiant Non Affecté
                        </h6>
                        <div class="text-danger small mb-2">
                            <strong>Statut :</strong> Inscription directe
                        </div>
                        <p class="card-text small text-muted mb-1">
                            • Non retenu dans le système public d'affectation
                        </p>
                        <p class="card-text small text-muted mb-1">
                            • Inscription directe dans l'établissement
                        </p>
                        <p class="card-text small text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Tarif complet sans subvention</strong>
                        </p>
                    </div>
                `;
                break;
            default:
                content = `
                    <div class="card-body">
                        <h6 class="card-title text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Information sur le statut
                        </h6>
                        <p class="card-text text-muted small">
                            Sélectionnez un statut d'affectation pour voir les détails.
                        </p>
                    </div>
                `;
        }
        
        infoDiv.innerHTML = content;
        
        // Recharger les frais si une classe est déjà sélectionnée
        if (value && document.getElementById('classe_id') && document.getElementById('classe_id').value) {
            loadFraisForClasse();
        }
    };
    
    // Fonction pour recharger les frais selon la classe et le statut d'affectation
    window.loadFraisForClasse = function() {
        const classeSelect = document.getElementById('classe_id');
        const affectationSelect = document.getElementById('affectation_status');

        if (!classeSelect || !classeSelect.value) {
            debugLog('Aucune classe sélectionnée pour recharger les frais');
            return;
        }

        // Déclencher l'événement change sur la classe pour recharger les frais
        const changeEvent = new Event('change', { bubbles: true });
        classeSelect.dispatchEvent(changeEvent);
    };

    function initClasseAffectationState() {
        const classeSelect = document.getElementById('classe_id');
        const affectationSelect = document.getElementById('affectation_status');

        if (affectationSelect && affectationSelect.value) {
            updateAffectationInfo();
        }

        if (classeSelect && classeSelect.value) {
            loadFraisForClasse();
        }
    }

    initClasseAffectationState();

    // ========================
    // GESTION DES MATRICULES
    // ========================

    const matriculeInput = document.getElementById('matriculeInput');
    const matriculeContainer = document.getElementById('matriculeContainer');
    const generateBtn = document.getElementById('generateMatriculeBtn');
    const checkBtn = document.getElementById('checkMatriculeBtn');
    const matriculeStatus = document.getElementById('matriculeStatus');
    const matriculeMode = document.getElementById('matriculeMode');
    const matriculeHelp = document.getElementById('matriculeHelp');
    const genreSelect = document.querySelector('select[name="sexe"]');
    const classeSelect = document.getElementById('classe_id');

    // Charger le mode de génération des matricules
    let currentMatriculeMode = 'automatique'; // Par défaut
    let niveauConfig = null;

    fetch('/esbtp/matricule-config/mode-info', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        currentMatriculeMode = data.mode || 'automatique';
        updateMatriculeUI();
    })
    .catch(error => {
        debugLog('Erreur lors du chargement du mode matricule:', error);
        updateMatriculeUI();
    });

    function updateMatriculeUI() {
        if (currentMatriculeMode === 'automatique') {
            // MODE AUTO : Cacher complètement le container
            if (matriculeContainer) {
                matriculeContainer.style.display = 'none';
            }
            // Vider l'input (sera rempli automatiquement au submit)
            matriculeInput.value = '';
        } else {
            // MODE MANUEL : Afficher le container avec les contrôles
            if (matriculeContainer) {
                matriculeContainer.style.display = 'block';
            }
            matriculeMode.textContent = 'MANUEL';
            matriculeMode.className = 'badge bg-warning ms-1';
            matriculeHelp.textContent = 'Saisissez manuellement le matricule (vérification anti-doublon)';
            generateBtn.style.display = 'none';
            checkBtn.style.display = 'block';
            matriculeInput.readOnly = false;
            matriculeInput.placeholder = 'Ex: MESBTP25-0001';
        }
    }

    // Génération automatique
    generateBtn.addEventListener('click', function() {
        const genre = genreSelect ? genreSelect.value : null;

        if (!genre) {
            showMatriculeStatus('Veuillez d\'abord sélectionner le genre/sexe', 'warning');
            return;
        }

        if (!niveauConfig) {
            showMatriculeStatus('Niveau d\'études non configuré. Contactez l\'équipe technique.', 'danger');
            return;
        }

        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';

        fetch('/esbtp/matricule-config/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                niveau_etude_code: niveauConfig.code,
                genre: genre,
                annee: new Date().getFullYear()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                matriculeInput.value = data.matricule;
                showMatriculeStatus('Matricule généré avec succès', 'success');
            } else {
                showMatriculeStatus(data.message || 'Erreur lors de la génération', 'danger');
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            showMatriculeStatus('Erreur de connexion', 'danger');
        })
        .finally(() => {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-magic"></i> Générer';
        });
    });

    // Vérification manuelle
    checkBtn.addEventListener('click', checkMatriculeManuel);

    // Vérification en temps réel pour le mode manuel
    if (currentMatriculeMode === 'manuel') {
        let checkTimeout;
        matriculeInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            if (this.value.length >= 3) {
                checkTimeout = setTimeout(checkMatriculeManuel, 500);
            }
        });
    }

    function checkMatriculeManuel() {
        const matricule = matriculeInput.value.trim();

        if (!matricule) {
            showMatriculeStatus('', '');
            return;
        }

        checkBtn.disabled = true;
        checkBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('/esbtp/matricule-config/check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ matricule: matricule })
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                showMatriculeStatus('❌ Ce matricule existe déjà', 'danger');
            } else {
                showMatriculeStatus('✅ Matricule disponible', 'success');
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            showMatriculeStatus('Erreur de vérification', 'warning');
        })
        .finally(() => {
            checkBtn.disabled = false;
            checkBtn.innerHTML = '<i class="fas fa-search"></i> Vérifier';
        });
    }

    function showMatriculeStatus(message, type) {
        if (!message) {
            matriculeStatus.innerHTML = '';
            return;
        }

        const alertClass = {
            'success': 'alert-success',
            'danger': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        matriculeStatus.innerHTML = `<small class="alert ${alertClass} p-1 m-0">${message}</small>`;
    }

    // Fonction async pour générer automatiquement le matricule (mode AUTO)
    async function generateMatriculeAuto() {
        const genre = genreSelect ? genreSelect.value : null;

        if (!genre) {
            debugLog('Genre non renseigné pour la génération auto');
            return null;
        }

        if (!niveauConfig) {
            debugLog('Niveau config non trouvé pour la génération auto');
            return null;
        }

        try {
            const response = await fetch('/esbtp/matricule-config/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    niveau_etude_code: niveauConfig.code,
                    genre: genre,
                    annee: new Date().getFullYear()
                })
            });

            const data = await response.json();

            if (data.success && data.matricule) {
                debugLog('Matricule généré avec succès:', data.matricule);
                return data.matricule;
            } else {
                debugError('Erreur lors de la génération:', data.message || 'Erreur inconnue');
                return null;
            }
        } catch (error) {
            debugError('Erreur réseau lors de la génération du matricule:', error);
            return null;
        }
    }

    // Fonction async pour vérifier la disponibilité du matricule (mode MANUEL)
    async function checkMatriculeDisponible() {
        const matricule = matriculeInput.value.trim();

        if (!matricule) {
            debugLog('Aucun matricule à vérifier');
            return false;
        }

        try {
            const response = await fetch('/esbtp/matricule-config/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ matricule: matricule })
            });

            const data = await response.json();

            if (data.exists) {
                debugLog('Matricule existe déjà:', matricule);
                return false;
            } else {
                debugLog('Matricule disponible:', matricule);
                return true;
            }
        } catch (error) {
            debugError('Erreur lors de la vérification du matricule:', error);
            return false;
        }
    }

    // Détecter le niveau d'études depuis la classe sélectionnée
    if (classeSelect) {
        classeSelect.addEventListener('change', function() {
            // Logique pour détecter le niveau d'études de la classe sélectionnée
            // et vérifier s'il y a une configuration de matricule
            const classeId = this.value;

            if (classeId && currentMatriculeMode === 'automatique') {
                fetch(`/esbtp/api/classes/${classeId}/niveau-config`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    niveauConfig = data.niveau_config;

                    if (!niveauConfig) {
                        showMatriculeStatus('⚠️ Niveau non configuré pour génération automatique', 'warning');
                        generateBtn.disabled = true;
                    } else {
                        showMatriculeStatus('', '');
                        generateBtn.disabled = false;
                    }
                })
                .catch(error => {
                    debugError('Erreur:', error);
                    niveauConfig = null;
                });
            }
        });
    }

    // ========================================
    // EVENT LISTENER SUBMIT - GÉNÉRATION AUTO & VÉRIFICATION MANUELLE
    // ========================================

    const inscriptionForm = document.getElementById('inscriptionForm');

    if (inscriptionForm) {
        inscriptionForm.addEventListener('submit', async function(e) {
            debugLog('🚀 Soumission formulaire - Mode:', currentMatriculeMode);

            // ====================================
            // MODE AUTOMATIQUE
            // ====================================
            if (currentMatriculeMode === 'automatique') {
                // Vérifications pré-requis
                if (!genreSelect || !genreSelect.value) {
                    e.preventDefault();
                    alert('⚠️ Veuillez sélectionner le genre/sexe avant de soumettre le formulaire.');
                    if (genreSelect) genreSelect.focus();
                    return;
                }

                if (!niveauConfig) {
                    e.preventDefault();
                    alert('⚠️ La classe sélectionnée n\'a pas de configuration de matricule.\n\nContactez l\'équipe technique ou sélectionnez une autre classe.');
                    if (classeSelect) classeSelect.focus();
                    return;
                }

                // Vider le champ matricule — le serveur génère avec logique de retry
                matriculeInput.value = '';
                debugLog('📤 Mode AUTO : matricule laissé vide, génération serverside avec retry');
                // Laisser le submit se poursuivre naturellement
            }

            // ====================================
            // MODE MANUEL
            // ====================================
            else if (currentMatriculeMode === 'manuel') {
                e.preventDefault(); // Bloquer le submit pour vérifier le matricule

                const matricule = matriculeInput.value.trim();

                // Vérification que le matricule est renseigné
                if (!matricule) {
                    alert('⚠️ Le matricule est obligatoire.\n\nVeuillez saisir un matricule avant de soumettre le formulaire.');
                    matriculeInput.focus();
                    return;
                }

                // Vérification de disponibilité
                debugLog('⏳ Vérification disponibilité du matricule:', matricule);
                const isAvailable = await checkMatriculeDisponible();

                if (isAvailable) {
                    // ✅ Matricule disponible
                    debugLog('✅ Matricule disponible, soumission du formulaire');
                    inscriptionForm.submit();
                } else {
                    // ❌ Matricule déjà existant
                    alert('❌ Ce matricule existe déjà dans la base de données.\n\n' +
                          'Veuillez en saisir un autre.\n\n' +
                          'Matricule saisi: ' + matricule);
                    matriculeInput.focus();
                    matriculeInput.select();
                    debugError('Matricule déjà existant:', matricule);

                    // Afficher le message d'erreur visuel
                    showMatriculeStatus('❌ Ce matricule existe déjà', 'danger');
                }
            }
        });

        debugLog('✅ Event listener submit configuré pour le formulaire inscription');
    } else {
        debugError('❌ Formulaire #inscriptionForm introuvable');
    }
});
</script>
@endpush
