@extends('layouts.app')

@section('title', 'Modifier Secrétaire - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .edit-header {
        background: linear-gradient(135deg, var(--warning), var(--secondary));
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
    }

    .edit-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }

    .secretary-form {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
    }

    .form-section {
        margin-bottom: var(--space-xl);
        padding-bottom: var(--space-lg);
        border-bottom: 1px solid var(--border);
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .section-title {
        color: var(--primary);
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .section-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 1.1rem;
    }

    .form-group {
        margin-bottom: var(--space-lg);
    }

    .form-label {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .form-control, .form-select {
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        background: var(--surface);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        transform: translateY(-1px);
    }

    .form-help {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }

    .action-buttons {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
        padding-top: var(--space-xl);
        border-top: 1px solid var(--border);
    }

    .current-avatar {
        width: 100px;
        height: 100px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        margin: 0 auto var(--space-md);
        border: 4px solid rgba(var(--primary-rgb), 0.2);
    }

    .info-card {
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        border-left: 4px solid var(--info);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="edit-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div style="display: flex; align-items: center; gap: var(--space-lg); position: relative; z-index: 2;">
                        <div class="current-avatar">
                            {{ strtoupper(substr($secretaire->name ?? 'S', 0, 2)) }}
                        </div>
                        <div>
                            <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">
                                <i class="fas fa-user-edit me-2"></i>Modifier le Secrétaire
                            </h1>
                            <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">
                                Modification du profil de <strong>{{ $secretaire->name }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <div style="position: relative; z-index: 2;">
                        <a href="{{ route('esbtp.secretaires.show', $secretaire->id) }}" class="btn-acasi secondary" style="margin-right: var(--space-md);">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi">
                            <i class="fas fa-list"></i> Liste
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-lg" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-medium); padding: var(--space-lg);">
            <div style="display: flex; align-items: center; gap: var(--space-md);">
                <i class="fas fa-exclamation-triangle fa-2x" style="color: var(--danger);"></i>
                <div>
                    <h6 style="color: var(--danger); margin: 0 0 var(--space-sm) 0;">Erreurs de validation</h6>
                    <ul style="margin: 0; color: var(--danger);">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="info-card">
            <div style="display: flex; align-items: center; gap: var(--space-sm);">
                <i class="fas fa-info-circle" style="color: var(--info);"></i>
                <div>
                    <strong>Information :</strong> Vous pouvez modifier les informations du secrétaire. Le mot de passe est optionnel.
                </div>
            </div>
        </div>

        <div class="secretary-form">
            <form action="{{ route('esbtp.secretaires.update', $secretaire->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        Informations Personnelles
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $secretaire->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $secretaire->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="text" class="form-control @error('telephone') is-invalid @enderror" id="telephone" name="telephone" value="{{ old('telephone', $secretaire->telephone) }}">
                                @error('telephone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="adresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control @error('adresse') is-invalid @enderror" id="adresse" name="adresse" value="{{ old('adresse', $secretaire->adresse) }}">
                                @error('adresse')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        Compte Utilisateur
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $secretaire->username) }}" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-help">Laissez vide pour conserver le mot de passe actuel.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                    <button type="submit" class="btn-acasi success">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
</script>
@endpush
