@extends('layouts.app')

@section('title', 'Créer Coordinateur - ESBTP-yAKRO')

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
    
    .wizard-content {
        padding: var(--space-xl);
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
    
    .wizard-actions {
        display: flex;
        justify-content: space-between;
        padding: var(--space-lg);
        background: var(--background);
        border-top: 1px solid var(--border);
        margin-top: var(--space-xl);
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="card-moderne">
            <div class="card-header-moderne">
                <h1 class="section-title">
                    <i class="fas fa-user-tie me-2"></i>
                    Créer un Coordinateur
                </h1>
                <p class="section-subtitle">Ajout d'un nouveau coordinateur pédagogique</p>
            </div>
        </div>

        <div class="form-wizard">
            <div class="wizard-header">
                <h2>Nouveau Coordinateur</h2>
                <p>Création d'un compte coordinateur avec accès pédagogique complet</p>
            </div>

            <form action="{{ route('esbtp.coordinateurs.store') }}" method="POST" id="coordinateurForm">
                @csrf
                <div class="wizard-content">
                    
                    <!-- Informations Personnelles -->
                    <div class="form-section">
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
                                    Adresse email pour se connecter au système
                                </div>
                            </div>

                            <div class="form-group-moderne">
                                <label for="telephone" class="form-label-moderne">
                                    Téléphone
                                </label>
                                <input type="tel" name="telephone" id="telephone" 
                                       class="form-input-moderne @error('telephone') is-invalid @enderror"
                                       value="{{ old('telephone') }}">
                                @error('telephone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="specialite" class="form-label-moderne">
                                    Spécialité
                                </label>
                                <input type="text" name="specialite" id="specialite" 
                                       class="form-input-moderne @error('specialite') is-invalid @enderror"
                                       value="{{ old('specialite') }}"
                                       placeholder="ex: Informatique, Gestion, Marketing">
                                @error('specialite')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-help-text">
                                    Domaine d'expertise principal
                                </div>
                            </div>

                            <div class="form-group-moderne">
                                <label for="date_naissance" class="form-label-moderne">
                                    Date de naissance
                                </label>
                                <input type="date" name="date_naissance" id="date_naissance" 
                                       class="form-input-moderne @error('date_naissance') is-invalid @enderror"
                                       value="{{ old('date_naissance') }}">
                                @error('date_naissance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group-moderne">
                            <label for="adresse" class="form-label-moderne">
                                Adresse
                            </label>
                            <textarea name="adresse" id="adresse" 
                                      class="form-textarea-moderne @error('adresse') is-invalid @enderror"
                                      placeholder="Adresse complète du coordinateur">{{ old('adresse') }}</textarea>
                            @error('adresse')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Informations de Sécurité -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            Informations de Sécurité
                        </div>
                        
                        {{-- Information sur la génération automatique des credentials --}}
                        <div style="background-color: rgba(16, 185, 129, 0.1); border-radius: var(--radius-medium); padding: var(--space-md); margin-bottom: var(--space-lg);">
                            <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
                                <i class="fas fa-info-circle" style="color: var(--success); margin-top: 2px;"></i>
                                <div>
                                    <p style="margin: 0; font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-xs);">Génération automatique des identifiants</p>
                                    <p style="margin: 0; font-size: var(--text-small); color: var(--text-secondary);">
                                        Le nom d'utilisateur et le mot de passe seront générés automatiquement lors de la création du compte. 
                                        Le coordinateur devra changer son mot de passe lors de sa première connexion.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé des privilèges -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            Privilèges du Coordinateur
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Ce coordinateur aura accès aux fonctionnalités suivantes :</strong>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Gestion des étudiants</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Gestion des enseignants</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Gestion des classes</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Présences et absences</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Emplois du temps</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Examens et évaluations</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Notes et bulletins</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Annonces et communications</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="wizard-actions">
                    <div class="actions-left">
                        <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                    </div>
                    
                    <div class="actions-right">
                        <button type="submit" class="btn-acasi success">
                            <i class="fas fa-save me-1"></i>Créer le Coordinateur
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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

// Les mots de passe sont maintenant générés automatiquement
</script>
@endpush