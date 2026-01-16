@extends('layouts.app')

@section('title', 'Créer un Secrétaire - KLASSCI')

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
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
        gap: var(--space-lg);
        flex-wrap: wrap;
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
                        <i class="fas fa-user-shield me-2"></i>
                        Créer un Secrétaire
                    </h1>
                    <p>Ajout d'un nouveau secrétaire académique</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-header">
                        <i class="fas fa-arrow-left"></i>
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="form-wizard">
            <div class="wizard-header">
                <h2>Nouveau Secrétaire</h2>
                <p>Création d'un compte secrétaire avec accès administratif</p>
            </div>

            <form action="{{ route('esbtp.secretaires.store') }}" method="POST">
                @csrf
                <div class="wizard-content">
                    <div class="form-section">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            Informations Personnelles
                        </div>

                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="name" class="form-label-moderne">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-input-moderne @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="email" class="form-label-moderne">Email</label>
                                <input type="email" class="form-input-moderne @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="telephone" class="form-label-moderne">Téléphone</label>
                                <input type="tel" class="form-input-moderne @error('telephone') is-invalid @enderror" id="telephone" name="telephone" value="{{ old('telephone') }}">
                                @error('telephone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="adresse" class="form-label-moderne">Adresse</label>
                                <input type="text" class="form-input-moderne @error('adresse') is-invalid @enderror" id="adresse" name="adresse" value="{{ old('adresse') }}">
                                @error('adresse')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            Informations de Sécurité
                        </div>

                        <div style="background-color: rgba(16, 185, 129, 0.1); border-radius: var(--radius-medium); padding: var(--space-md); margin-bottom: var(--space-lg);">
                            <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
                                <i class="fas fa-info-circle" style="color: var(--success); margin-top: 2px;"></i>
                                <div>
                                    <p style="margin: 0; font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-xs);">Génération automatique des identifiants</p>
                                    <p style="margin: 0; font-size: var(--text-small); color: var(--text-secondary);">
                                        Le nom d'utilisateur et le mot de passe seront générés automatiquement lors de la création du compte.
                                        Le secrétaire devra changer son mot de passe lors de sa première connexion.
                                    </p>
                                </div>
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
                            <i class="fas fa-save me-1"></i>Créer le Secrétaire
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
