@extends('layouts.app')

@section('title', 'Créer un Département')
@section('page_title', 'Nouveau Département')

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl">
        <div class="header-content">
            <h1 class="header-title">Nouveau Département</h1>
            <p class="header-subtitle">Création d'un nouveau département ESBTP</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.departments.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-lg" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-medium); padding: var(--space-md);">
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

    <div class="card-moderne">
        <div class="p-lg">
            <div class="section-title mb-lg">Formulaire de création</div>
            
            <form action="{{ route('esbtp.departments.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <!-- Informations de base -->
                    <div class="col-md-6">
                        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: var(--space-lg); font-size: var(--title-section); text-transform: uppercase; letter-spacing: 0.5px;">Informations de base</h4>
                        
                        <div class="mb-lg">
                            <label for="name" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">
                                Nom du département <span style="color: var(--danger);">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('name')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="code" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">
                                Code du département <span style="color: var(--danger);">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code') }}" 
                                   required
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            <small style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs); display: block;">Le code doit être unique et court (ex: INFO, MECA, etc.)</small>
                            @error('code')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="description" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease; resize: vertical;">{{ old('description') }}</textarea>
                            @error('description')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Informations du responsable -->
                    <div class="col-md-6">
                        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: var(--space-lg); font-size: var(--title-section); text-transform: uppercase; letter-spacing: 0.5px;">Informations du responsable</h4>
                        
                        <div class="mb-lg">
                            <label for="head_name" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Nom du chef de département</label>
                            <input type="text" 
                                   class="form-control @error('head_name') is-invalid @enderror" 
                                   id="head_name" 
                                   name="head_name" 
                                   value="{{ old('head_name') }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('head_name')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="head_title" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Titre du chef de département</label>
                            <input type="text" 
                                   class="form-control @error('head_title') is-invalid @enderror" 
                                   id="head_title" 
                                   name="head_title" 
                                   value="{{ old('head_title') }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('head_title')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="email" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Email du département</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('email')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="phone" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Téléphone du département</label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone') }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('phone')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="office_location" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Localisation du bureau</label>
                            <input type="text" 
                                   class="form-control @error('office_location') is-invalid @enderror" 
                                   id="office_location" 
                                   name="office_location" 
                                   value="{{ old('office_location') }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('office_location')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div style="margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid #e5e7eb; display: flex; gap: var(--space-md);">
                    <button type="submit" class="btn-acasi primary" style="padding: var(--space-md) var(--space-xl);">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="{{ route('esbtp.departments.index') }}" class="btn-acasi secondary" style="padding: var(--space-md) var(--space-xl);">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
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
