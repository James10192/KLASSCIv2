@extends('layouts.app')

@section('title', 'Nouveau Enseignant - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .form-wizard {
        background: var(--surface);
        border-radius: var(--radius-large);
        overflow: hidden;
        box-shadow: var(--shadow-card);
    }
    
    .wizard-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-xl);
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .wizard-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .wizard-steps {
        display: flex;
        justify-content: space-between;
        background: rgba(255,255,255,0.1);
        padding: var(--space-md);
        margin: var(--space-md) 0 0;
        border-radius: var(--radius-medium);
    }
    
    .wizard-step {
        flex: 1;
        text-align: center;
        padding: var(--space-sm);
        border-radius: var(--radius-small);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    
    .wizard-step.active {
        background: rgba(255,255,255,0.2);
        transform: scale(1.05);
    }
    
    .wizard-step.completed {
        background: rgba(76, 175, 80, 0.3);
    }
    
    .wizard-step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-xs);
        font-weight: bold;
        font-size: 0.9rem;
    }
    
    .wizard-step.active .wizard-step-number {
        background: white;
        color: var(--primary);
    }
    
    .wizard-step.completed .wizard-step-number {
        background: var(--success);
        color: white;
    }
    
    .wizard-step-title {
        font-size: 0.8rem;
        margin-bottom: var(--space-xs);
        font-weight: 600;
    }
    
    .wizard-step-desc {
        font-size: 0.7rem;
        opacity: 0.9;
    }
    
    .wizard-content {
        padding: var(--space-xl);
    }
    
    .form-section {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .form-section.active {
        display: block;
    }
    
    .form-section-title {
        color: var(--primary);
        font-size: 1.4rem;
        font-weight: 600;
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .form-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 1.2rem;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-lg);
    }
    
    .form-group-moderne {
        margin-bottom: var(--space-md);
    }
    
    .form-label-moderne {
        display: block;
        margin-bottom: var(--space-xs);
        font-weight: 500;
        color: var(--text-primary);
        font-size: 0.9rem;
    }
    
    .form-input-moderne,
    .form-select-moderne,
    .form-textarea-moderne {
        width: 100%;
        padding: var(--space-sm);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: var(--surface);
        color: var(--text-primary);
    }
    
    .form-input-moderne:focus,
    .form-select-moderne:focus,
    .form-textarea-moderne:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }
    
    .form-textarea-moderne {
        min-height: 80px;
        resize: vertical;
    }
    
    .form-help-text {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }
    
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-md);
        margin-top: var(--space-sm);
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-sm);
        background: var(--background);
        border-radius: var(--radius-small);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .checkbox-item:hover {
        background: rgba(var(--primary-rgb), 0.05);
        border-color: var(--primary);
    }
    
    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
    }
    
    .file-upload-zone {
        border: 2px dashed var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--background);
    }
    
    .file-upload-zone:hover {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.05);
    }
    
    .file-upload-zone.dragover {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.1);
    }
    
    .upload-icon {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: var(--space-sm);
    }
    
    .upload-text {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .upload-formats {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }
    
    .wizard-actions {
        display: flex;
        justify-content: space-between;
        padding: var(--space-lg);
        background: var(--background);
        border-top: 1px solid var(--border);
        margin-top: var(--space-xl);
    }
    
    .availability-grid {
        display: grid;
        grid-template-columns: 120px repeat(7, 1fr);
        gap: var(--space-sm);
        margin-top: var(--space-md);
    }
    
    .availability-header {
        font-weight: 600;
        text-align: center;
        padding: var(--space-sm);
        background: var(--primary);
        color: white;
        border-radius: var(--radius-small);
        font-size: 0.8rem;
    }
    
    .availability-time {
        font-weight: 500;
        text-align: center;
        padding: var(--space-sm);
        background: var(--surface);
        border-radius: var(--radius-small);
        font-size: 0.8rem;
    }
    
    .availability-slot {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: var(--space-xs);
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        cursor: pointer;
        transition: all 0.3s ease;
        min-height: 35px;
    }
    
    .availability-slot:hover {
        background: rgba(var(--primary-rgb), 0.1);
    }
    
    .availability-slot.available {
        background: var(--success);
        color: white;
    }
    
    .availability-slot.preferred {
        background: var(--primary);
        color: white;
    }
    
    .availability-slot.unavailable {
        background: var(--danger);
        color: white;
    }
    
    .availability-legend {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        margin-top: var(--space-md);
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.8rem;
    }
    
    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: var(--radius-small);
    }
    
    .progress-bar {
        height: 4px;
        background: var(--border);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: var(--radius-full);
        transition: width 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Header principal amélioré */
    .main-header {
        background: linear-gradient(135deg, #0453cb, #1b64d4);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-elevated);
    }

    .main-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 100px;
        height: 200%;
        background: rgba(255,255,255,0.05);
        transform: skewX(-15deg);
    }

    .header-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-left h1 {
        font-size: 2rem;
        margin: 0 0 var(--space-xs);
        font-weight: 700;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .header-left p {
        margin: 0;
        opacity: 0.95;
        font-size: 1.1rem;
        color: rgba(255,255,255,0.95);
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .header-actions {
        display: flex;
        gap: var(--space-md);
    }

    .btn-header {
        padding: var(--space-sm) var(--space-lg);
        border: 2px solid rgba(255,255,255,0.4);
        border-radius: var(--radius-full);
        color: white;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        background: rgba(255,255,255,0.1);
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .btn-header:hover {
        background: rgba(255,255,255,0.25);
        border-color: rgba(255,255,255,0.6);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        color: white;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .wizard-steps {
            flex-wrap: wrap;
        }

        .wizard-step {
            min-width: 120px;
        }

        .availability-grid {
            grid-template-columns: 80px repeat(7, 1fr);
            font-size: 0.7rem;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="main-header">
            <div class="header-content">
                <div class="header-left">
                    <h1>
                        <i class="fas fa-user-plus me-2"></i>
                        Nouveau Enseignant
                    </h1>
                    <p>Créez un profil complet pour le nouvel enseignant</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-header">
                        <i class="fas fa-arrow-left"></i>
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        <div class="form-wizard">
            <div class="wizard-header">
                <h2>Assistant de Création d'Enseignant</h2>
                <p>Suivez les étapes pour créer un profil complet</p>
                
                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <div class="wizard-step-number">1</div>
                        <div class="wizard-step-title">Informations Personnelles</div>
                        <div class="wizard-step-desc">Identité & Contact</div>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="wizard-step-number">2</div>
                        <div class="wizard-step-title">Qualifications</div>
                        <div class="wizard-step-desc">Diplômes & Expérience</div>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="wizard-step-number">3</div>
                        <div class="wizard-step-title">Informations Professionnelles</div>
                        <div class="wizard-step-desc">Contrat & Affectation</div>
                    </div>
                    <div class="wizard-step" data-step="4">
                        <div class="wizard-step-number">4</div>
                        <div class="wizard-step-title">Finalisation</div>
                        <div class="wizard-step-desc">Documents & Validation</div>
                    </div>
                </div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" style="width: 20%"></div>
            </div>

            <form action="{{ route('esbtp.enseignants.store') }}" method="POST" enctype="multipart/form-data" id="teacherForm">
                @csrf
                <div class="wizard-content">
                    
                    <!-- Étape 1: Informations Personnelles -->
                    <div class="form-section active" id="step-1">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            Informations Personnelles
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="name" class="form-label-moderne">
                                    Nom complet <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name" 
                                       class="form-input-moderne @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="email" class="form-label-moderne">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="email" id="email" 
                                       class="form-input-moderne @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-help-text">
                                    Adresse email pour les notifications et communications
                                </div>
                            </div>

                            <div class="form-group-moderne">
                                <label for="phone" class="form-label-moderne">
                                    Téléphone
                                </label>
                                <input type="tel" name="phone" id="phone" 
                                       class="form-input-moderne @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="titre_academique" class="form-label-moderne">
                                    Titre Académique
                                </label>
                                <select name="titre_academique" id="titre_academique" 
                                        class="form-select-moderne @error('titre_academique') is-invalid @enderror">
                                    <option value="">Sélectionnez un titre</option>
                                    @foreach($titres_academiques as $key => $value)
                                        <option value="{{ $key }}" {{ old('titre_academique') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('titre_academique')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Information sur la génération automatique des credentials --}}
                            <div style="background-color: rgba(16, 185, 129, 0.1); border-radius: var(--radius-medium); padding: var(--space-md); margin-bottom: var(--space-lg); grid-column: 1 / -1;">
                                <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
                                    <i class="fas fa-info-circle" style="color: var(--success); margin-top: 2px;"></i>
                                    <div>
                                        <p style="margin: 0; font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-xs);">Génération automatique des identifiants</p>
                                        <p style="margin: 0; font-size: var(--text-small); color: var(--text-secondary);">
                                            Le nom d'utilisateur et le mot de passe seront générés automatiquement lors de la création du compte. 
                                            L'enseignant devra changer son mot de passe lors de sa première connexion.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 2: Qualifications -->
                    <div class="form-section" id="step-2">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            Qualifications & Expérience
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="diplome_principal" class="form-label-moderne">
                                    Diplôme Principal
                                </label>
                                <input type="text" name="diplome_principal" id="diplome_principal" 
                                       class="form-input-moderne @error('diplome_principal') is-invalid @enderror"
                                       value="{{ old('diplome_principal') }}"
                                       placeholder="ex: Master en Informatique">
                                @error('diplome_principal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="universite_diplome" class="form-label-moderne">
                                    Université/Institut
                                </label>
                                <input type="text" name="universite_diplome" id="universite_diplome" 
                                       class="form-input-moderne @error('universite_diplome') is-invalid @enderror"
                                       value="{{ old('universite_diplome') }}"
                                       placeholder="ex: Université de Douala">
                                @error('universite_diplome')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="annee_diplome" class="form-label-moderne">
                                    Année d'obtention
                                </label>
                                <input type="number" name="annee_diplome" id="annee_diplome" 
                                       class="form-input-moderne @error('annee_diplome') is-invalid @enderror"
                                       value="{{ old('annee_diplome') }}"
                                       min="1950" max="{{ date('Y') }}">
                                @error('annee_diplome')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="grade_academique" class="form-label-moderne">
                                    Grade Académique
                                </label>
                                <select name="grade_academique" id="grade_academique" 
                                        class="form-select-moderne @error('grade_academique') is-invalid @enderror">
                                    <option value="">Sélectionnez un grade</option>
                                    @foreach($grades_academiques as $key => $value)
                                        <option value="{{ $key }}" {{ old('grade_academique') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('grade_academique')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="annees_experience_enseignement" class="form-label-moderne">
                                    Années d'expérience en enseignement
                                </label>
                                <input type="number" name="annees_experience_enseignement" id="annees_experience_enseignement" 
                                       class="form-input-moderne @error('annees_experience_enseignement') is-invalid @enderror"
                                       value="{{ old('annees_experience_enseignement') }}"
                                       min="0" max="50">
                                @error('annees_experience_enseignement')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="annees_experience_professionnelle" class="form-label-moderne">
                                    Années d'expérience professionnelle
                                </label>
                                <input type="number" name="annees_experience_professionnelle" id="annees_experience_professionnelle" 
                                       class="form-input-moderne @error('annees_experience_professionnelle') is-invalid @enderror"
                                       value="{{ old('annees_experience_professionnelle') }}"
                                       min="0" max="50">
                                @error('annees_experience_professionnelle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group-moderne">
                            <label for="specialization" class="form-label-moderne">
                                Spécialisation <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="specialization" id="specialization" 
                                   class="form-input-moderne @error('specialization') is-invalid @enderror"
                                   value="{{ old('specialization') }}" required
                                   placeholder="ex: Développement Web, Réseaux Informatiques, Base de Données">
                            @error('specialization')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-help-text">
                                Décrivez votre domaine d'expertise principal
                            </div>
                        </div>
                    </div>

                    <!-- Étape 3: Informations Professionnelles -->
                    <div class="form-section" id="step-3">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            Informations Professionnelles
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="department_id" class="form-label-moderne">
                                    Département <span class="text-danger">*</span>
                                </label>
                                <select name="department_id" id="department_id" 
                                        class="form-select-moderne @error('department_id') is-invalid @enderror" required>
                                    <option value="">Sélectionnez un département</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="laboratory_id" class="form-label-moderne">
                                    Laboratoire
                                </label>
                                <select name="laboratory_id" id="laboratory_id" 
                                        class="form-select-moderne @error('laboratory_id') is-invalid @enderror">
                                    <option value="">Aucun laboratoire</option>
                                    @foreach($laboratories as $laboratory)
                                        <option value="{{ $laboratory->id }}" {{ old('laboratory_id') == $laboratory->id ? 'selected' : '' }}>
                                            {{ $laboratory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('laboratory_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="type_contrat" class="form-label-moderne">
                                    Type de contrat <span class="text-danger">*</span>
                                </label>
                                <select name="type_contrat" id="type_contrat" 
                                        class="form-select-moderne @error('type_contrat') is-invalid @enderror" required>
                                    <option value="">Sélectionnez un type</option>
                                    @foreach($types_contrat as $key => $value)
                                        <option value="{{ $key }}" {{ old('type_contrat') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type_contrat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="statut_emploi" class="form-label-moderne">
                                    Statut d'emploi <span class="text-danger">*</span>
                                </label>
                                <select name="statut_emploi" id="statut_emploi" 
                                        class="form-select-moderne @error('statut_emploi') is-invalid @enderror" required>
                                    <option value="">Sélectionnez un statut</option>
                                    @foreach($statuts_emploi as $key => $value)
                                        <option value="{{ $key }}" {{ old('statut_emploi') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('statut_emploi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="date_embauche" class="form-label-moderne">
                                    Date d'embauche <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="date_embauche" id="date_embauche" 
                                       class="form-input-moderne @error('date_embauche') is-invalid @enderror"
                                       value="{{ old('date_embauche') }}" required>
                                @error('date_embauche')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="fin_contrat" class="form-label-moderne">
                                    Fin de contrat (si temporaire)
                                </label>
                                <input type="date" name="fin_contrat" id="fin_contrat" 
                                       class="form-input-moderne @error('fin_contrat') is-invalid @enderror"
                                       value="{{ old('fin_contrat') }}">
                                @error('fin_contrat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="taux_horaire" class="form-label-moderne">
                                    Taux horaire (FCFA)
                                </label>
                                <input type="number" name="taux_horaire" id="taux_horaire" 
                                       class="form-input-moderne @error('taux_horaire') is-invalid @enderror"
                                       value="{{ old('taux_horaire') }}"
                                       min="0" step="0.01">
                                @error('taux_horaire')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="charge_horaire_max_semaine" class="form-label-moderne">
                                    Charge horaire max/semaine
                                </label>
                                <input type="number" name="charge_horaire_max_semaine" id="charge_horaire_max_semaine" 
                                       class="form-input-moderne @error('charge_horaire_max_semaine') is-invalid @enderror"
                                       value="{{ old('charge_horaire_max_semaine', 40) }}"
                                       min="1" max="60">
                                @error('charge_horaire_max_semaine')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-help-text">
                                    Nombre d'heures maximum par semaine
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 4: Finalisation -->
                    <div class="form-section" id="step-4">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            Finalisation du Profil
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="bio" class="form-label-moderne">
                                    Biographie
                                </label>
                                <textarea name="bio" id="bio" 
                                          class="form-textarea-moderne @error('bio') is-invalid @enderror"
                                          placeholder="Décrivez votre parcours, vos centres d'intérêt...">{{ old('bio') }}</textarea>
                                @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="motivation" class="form-label-moderne">
                                    Motivation
                                </label>
                                <textarea name="motivation" id="motivation" 
                                          class="form-textarea-moderne @error('motivation') is-invalid @enderror"
                                          placeholder="Pourquoi souhaitez-vous enseigner à IFRAN ?">{{ old('motivation') }}</textarea>
                                @error('motivation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="objectifs_pedagogiques" class="form-label-moderne">
                                    Objectifs Pédagogiques
                                </label>
                                <textarea name="objectifs_pedagogiques" id="objectifs_pedagogiques" 
                                          class="form-textarea-moderne @error('objectifs_pedagogiques') is-invalid @enderror"
                                          placeholder="Quels sont vos objectifs en tant qu'enseignant ?">{{ old('objectifs_pedagogiques') }}</textarea>
                                @error('objectifs_pedagogiques')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="website" class="form-label-moderne">
                                    Site web / Portfolio
                                </label>
                                <input type="url" name="website" id="website" 
                                       class="form-input-moderne @error('website') is-invalid @enderror"
                                       value="{{ old('website') }}"
                                       placeholder="https://monsite.com">
                                @error('website')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="cv" class="form-label-moderne">
                                    CV (PDF, DOC, DOCX)
                                </label>
                                <div class="file-upload-zone" onclick="document.getElementById('cv').click()">
                                    <div class="upload-icon">
                                        <i class="fas fa-file-upload"></i>
                                    </div>
                                    <div class="upload-text">
                                        Cliquez pour télécharger votre CV
                                    </div>
                                    <div class="upload-formats">
                                        Formats acceptés: PDF, DOC, DOCX (max 2MB)
                                    </div>
                                </div>
                                <input type="file" name="cv" id="cv" accept=".pdf,.doc,.docx" style="display: none;">
                                @error('cv')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="photo" class="form-label-moderne">
                                    Photo de profil
                                </label>
                                <div class="file-upload-zone" onclick="document.getElementById('photo').click()">
                                    <div class="upload-icon">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <div class="upload-text">
                                        Cliquez pour télécharger votre photo
                                    </div>
                                    <div class="upload-formats">
                                        Formats acceptés: JPG, PNG, GIF (max 2MB)
                                    </div>
                                </div>
                                <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn-acasi secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                        <i class="fas fa-arrow-left me-1"></i>Précédent
                    </button>
                    
                    <button type="button" class="btn-acasi primary" id="nextBtn" onclick="changeStep(1)">
                        Suivant<i class="fas fa-arrow-right ms-1"></i>
                    </button>
                    
                    <button type="submit" class="btn-acasi success" id="submitBtn" style="display: none;">
                        <i class="fas fa-check me-1"></i>Créer l'enseignant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentStep = 1;
const totalSteps = 4;

function changeStep(direction) {
    const newStep = currentStep + direction;
    
    if (newStep < 1 || newStep > totalSteps) {
        return;
    }
    
    // Validation avant de passer à l'étape suivante
    if (direction > 0 && !validateStep(currentStep)) {
        return;
    }
    
    // Masquer l'étape actuelle
    document.getElementById(`step-${currentStep}`).classList.remove('active');
    document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
    
    // Marquer l'étape comme terminée si on avance
    if (direction > 0) {
        document.querySelector(`[data-step="${currentStep}"]`).classList.add('completed');
    }
    
    // Afficher la nouvelle étape
    currentStep = newStep;
    document.getElementById(`step-${currentStep}`).classList.add('active');
    document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
    
    // Mettre à jour les boutons
    updateButtons();
    
    // Mettre à jour la barre de progression
    updateProgress();
}

function updateButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
    nextBtn.style.display = currentStep < totalSteps ? 'block' : 'none';
    submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
}

function updateProgress() {
    const progress = (currentStep / totalSteps) * 100;
    document.querySelector('.progress-fill').style.width = progress + '%';
}

function validateStep(step) {
    const stepElement = document.getElementById(`step-${step}`);
    const requiredFields = stepElement.querySelectorAll('[required]');
    
    for (let field of requiredFields) {
        if (!field.value.trim()) {
            field.focus();
            field.classList.add('is-invalid');
            return false;
        } else {
            field.classList.remove('is-invalid');
        }
    }
    
    return true;
}

// Gestion des uploads de fichiers
document.getElementById('cv').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const uploadZone = this.parentElement.querySelector('.file-upload-zone');
        uploadZone.innerHTML = `
            <div class="upload-icon">
                <i class="fas fa-file-check"></i>
            </div>
            <div class="upload-text">
                ${file.name}
            </div>
            <div class="upload-formats">
                Fichier sélectionné (${(file.size / 1024 / 1024).toFixed(2)} MB)
            </div>
        `;
    }
});

document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const uploadZone = this.parentElement.querySelector('.file-upload-zone');
        const reader = new FileReader();
        reader.onload = function(e) {
            uploadZone.innerHTML = `
                <img src="${e.target.result}" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                <div class="upload-text">
                    ${file.name}
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
});

// Validation en temps réel
document.querySelectorAll('input[required], select[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
});

// Navigation par les étapes
document.querySelectorAll('.wizard-step').forEach((step, index) => {
    step.addEventListener('click', function() {
        const targetStep = index + 1;
        if (targetStep <= currentStep + 1) {
            changeStep(targetStep - currentStep);
        }
    });
});

// Gestion du type de contrat
document.getElementById('type_contrat').addEventListener('change', function() {
    const finContratField = document.getElementById('fin_contrat');
    const tauxHoraireField = document.getElementById('taux_horaire');
    
    if (this.value === 'temporaire') {
        finContratField.required = true;
        finContratField.parentElement.querySelector('.form-label-moderne').innerHTML = 
            'Fin de contrat <span class="text-danger">*</span>';
    } else {
        finContratField.required = false;
        finContratField.parentElement.querySelector('.form-label-moderne').innerHTML = 
            'Fin de contrat (si temporaire)';
    }
    
    if (this.value === 'vacataire') {
        tauxHoraireField.required = true;
        tauxHoraireField.parentElement.querySelector('.form-label-moderne').innerHTML = 
            'Taux horaire (FCFA) <span class="text-danger">*</span>';
    } else {
        tauxHoraireField.required = false;
        tauxHoraireField.parentElement.querySelector('.form-label-moderne').innerHTML = 
            'Taux horaire (FCFA)';
    }
});

// Initialisation
updateButtons();
updateProgress();
</script>
@endpush