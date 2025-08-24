@extends('layouts.app')

@section('title', 'Créer un Département')
@section('page_title', 'Nouveau Département')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-plus-circle me-2"></i>Nouveau Département</h1>
                <p class="header-subtitle">Création d'un nouveau département ESBTP</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.departments.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-modern danger">
                <div style="color: var(--danger); font-weight: 600; margin-bottom: var(--space-sm);">
                    <i class="fas fa-exclamation-triangle"></i> Erreurs de validation
                </div>
                <ul style="margin: 0; padding-left: var(--space-lg); color: var(--danger);">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-edit"></i>
                    Formulaire de création
                </div>
                <div class="main-card-subtitle">Remplissez les informations du nouveau département</div>
            </div>
            <div class="main-card-body">
            
            <form action="{{ route('esbtp.departments.store') }}" method="POST">
                @csrf
                
                <div class="form-grid-2">
                    <!-- Informations de base -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <div class="section-card-title">
                                <i class="fas fa-info-circle"></i>
                                Informations de base
                            </div>
                        </div>
                        <div class="section-card-body">
                            <div class="form-group-moderne">
                                <label for="name" class="form-label-moderne">
                                    <i class="fas fa-building"></i>
                                    Nom du département <span style="color: var(--danger);">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required
                                       placeholder="Ex: Informatique et Réseaux">
                                @error('name')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="code" class="form-label-moderne">
                                    <i class="fas fa-code"></i>
                                    Code du département <span style="color: var(--danger);">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('code') is-invalid @enderror" 
                                       id="code" 
                                       name="code" 
                                       value="{{ old('code') }}" 
                                       required
                                       placeholder="Ex: INFO, MECA">
                                <small style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs); display: block;">Le code doit être unique et court (ex: INFO, MECA, etc.)</small>
                                @error('code')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="description" class="form-label-moderne">
                                    <i class="fas fa-align-left"></i>
                                    Description
                                </label>
                                <textarea class="form-textarea-moderne @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          placeholder="Description du département...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Informations du responsable -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <div class="section-card-title">
                                <i class="fas fa-user-tie"></i>
                                Informations du responsable
                            </div>
                        </div>
                        <div class="section-card-body">
                            <div class="form-group-moderne">
                                <label for="head_name" class="form-label-moderne">
                                    <i class="fas fa-user"></i>
                                    Nom du chef de département
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('head_name') is-invalid @enderror" 
                                       id="head_name" 
                                       name="head_name" 
                                       value="{{ old('head_name') }}"
                                       placeholder="Ex: Dr. Jean Dupont">
                                @error('head_name')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="head_title" class="form-label-moderne">
                                    <i class="fas fa-graduation-cap"></i>
                                    Titre du chef de département
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('head_title') is-invalid @enderror" 
                                       id="head_title" 
                                       name="head_title" 
                                       value="{{ old('head_title') }}"
                                       placeholder="Ex: Professeur, Docteur">
                                @error('head_title')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="email" class="form-label-moderne">
                                    <i class="fas fa-envelope"></i>
                                    Email du département
                                </label>
                                <input type="email" 
                                       class="form-input-moderne @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}"
                                       placeholder="Ex: informatique@esbtp.com">
                                @error('email')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="phone" class="form-label-moderne">
                                    <i class="fas fa-phone"></i>
                                    Téléphone du département
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone') }}"
                                       placeholder="Ex: +225 22 48 88 00">
                                @error('phone')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="office_location" class="form-label-moderne">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Localisation du bureau
                                </label>
                                <input type="text" 
                                       class="form-input-moderne @error('office_location') is-invalid @enderror" 
                                       id="office_location" 
                                       name="office_location" 
                                       value="{{ old('office_location') }}"
                                       placeholder="Ex: Bâtiment A, 2ème étage">
                                @error('office_location')
                                    <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="actions-section-premium">
                    <button type="submit" class="btn-action-premium btn-success">
                        <i class="fas fa-save"></i> Enregistrer le département
                    </button>
                    <a href="{{ route('esbtp.departments.index') }}" class="btn-action-premium btn-outline-warning">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.form-control:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1) !important;
    outline: none !important;
}

.form-control:hover {
    border-color: var(--accent-blue) !important;
}

.btn-acasi:hover {
    transform: translateY(-2px) !important;
    box-shadow: var(--shadow-hover) !important;
}

input[type="text"], input[type="email"], textarea {
    width: 100% !important;
}

/* Animation pour les labels */
label {
    transition: color 0.2s ease !important;
}

.form-control:focus + label,
.form-control:focus ~ label {
    color: var(--primary) !important;
}
</style>
@endpush

@push('scripts')
<script>
    // Auto-generate code from name
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value;
        const code = name
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '') // Remove non-alphanumeric characters
            .substring(0, 10); // Take first 10 characters
        document.getElementById('code').value = code;
    });

    // Focus effects
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = 'var(--primary)';
            this.style.boxShadow = '0 0 0 2px rgba(30, 58, 138, 0.1)';
        });
        
        input.addEventListener('blur', function() {
            this.style.borderColor = '#e5e7eb';
            this.style.boxShadow = 'none';
        });
    });
</script>
@endpush
