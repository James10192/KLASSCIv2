@extends('layouts.app')

@section('title', 'Modifier Secrétaire - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ===================================================================
       SECRETAIRE EDIT — Premium Design — KLASSCI Design System
       Namespace: se- (secretaire-edit)
    =================================================================== */
    :root {
        --se-blue:   #0453cb;
        --se-blue-2: #5e91de;
        --se-surface: #f4f7fb;
        --se-card:   #ffffff;
        --se-border: #e2e8f0;
        --se-text:   #1e293b;
        --se-muted:  #64748b;
    }

    .se-page { background: var(--se-surface); min-height: 100vh; }

    /* Hero header */
    .se-hero {
        position: relative;
        background: linear-gradient(135deg, var(--se-blue) 0%, var(--se-blue-2) 100%);
        padding: 0;
    }
    .se-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
        pointer-events: none; overflow: hidden;
    }
    .se-hero::after {
        content: '';
        position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
        background: linear-gradient(to top, var(--se-surface) 0%, transparent 100%);
    }
    .se-hero-inner {
        position: relative; z-index: 2;
        max-width: 1280px; margin: 0 auto;
        padding: 32px 32px 28px;
        display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
    }
    .se-hero-avatar {
        width: 80px; height: 80px; border-radius: 50%;
        border: 3px solid rgba(255,255,255,.6);
        background: rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; font-weight: 700; color: rgba(255,255,255,.9);
        overflow: hidden; flex-shrink: 0;
        box-shadow: 0 4px 20px rgba(0,0,0,.22);
        backdrop-filter: blur(4px);
    }
    .se-hero-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .se-hero-text { flex: 1; min-width: 200px; color: #fff; }
    .se-hero-name { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; margin: 0 0 3px; }
    .se-hero-sub { font-size: .85rem; opacity: .8; margin: 0; }
    .se-hero-btns {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        margin-left: auto;
    }
    .se-hero-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
        text-decoration: none; cursor: pointer; border: none; transition: all .18s;
        white-space: nowrap;
    }
    .se-hero-btn.primary { background: rgba(255,255,255,.95); color: var(--se-blue); }
    .se-hero-btn.primary:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
    .se-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
    .se-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

    .se-form-wrap {
        max-width: 1280px; margin: 0 auto;
        padding: 28px 24px 60px;
    }

    .secretary-form {
        background: var(--se-card);
        border-radius: 20px;
        padding: var(--space-xl);
        box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
        border: 1px solid var(--se-border);
    }

    .form-section {
        margin-bottom: var(--space-xl);
        padding-bottom: var(--space-lg);
        border-bottom: 1px solid var(--se-border);
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .section-title {
        color: var(--se-blue);
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--se-blue) 0%, var(--se-blue-2) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .8rem;
    }

    .form-group {
        margin-bottom: var(--space-lg);
    }

    .form-label {
        font-weight: 600;
        color: var(--se-text);
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .form-control, .form-select {
        border: 2px solid var(--se-border);
        border-radius: 10px;
        padding: var(--space-md);
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        background: var(--se-card);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--se-blue);
        box-shadow: 0 0 0 3px rgba(4,83,203,.1);
        transform: translateY(-1px);
    }

    .form-help {
        font-size: var(--text-small);
        color: var(--se-muted);
        margin-top: var(--space-xs);
    }

    .action-buttons {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
        padding-top: var(--space-xl);
        border-top: 1px solid var(--se-border);
    }

    .info-card {
        background: rgba(4,83,203,.04);
        border: 1px solid rgba(4,83,203,.15);
        border-radius: 12px;
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        border-left: 4px solid var(--se-blue);
    }

    @media (max-width: 768px) {
        .se-hero-inner { padding: 24px 16px 20px; flex-direction: column; text-align: center; }
        .se-hero-btns { margin-left: 0; justify-content: center; }
        .se-form-wrap { padding: 20px 16px 40px; }
    }
</style>
@endsection

@section('content')
<div class="se-page">
    {{-- Dark Hero Header --}}
    <div class="se-hero">
        <div class="se-hero-inner">
            <div class="se-hero-avatar">
                @if($secretaire->photo_url ?? false)
                    <img src="{{ $secretaire->photo_url }}" alt="{{ $secretaire->name }}">
                @else
                    {{ strtoupper(substr($secretaire->first_name ?? $secretaire->name ?? 'S', 0, 1) . substr($secretaire->last_name ?? '', 0, 1)) }}
                @endif
            </div>
            <div class="se-hero-text">
                <h1 class="se-hero-name"><i class="fas fa-user-edit me-2"></i>Modifier le Secretaire</h1>
                <p class="se-hero-sub">Modification du profil de <strong>{{ $secretaire->first_name ?? $secretaire->name ?? '' }} {{ $secretaire->last_name ?? '' }}</strong></p>
            </div>
            <div class="se-hero-btns">
                <a href="{{ route('esbtp.secretaires.show', $secretaire->id) }}" class="se-hero-btn primary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <a href="{{ route('esbtp.personnel.unified.index') }}" class="se-hero-btn ghost">
                    <i class="fas fa-list"></i> Liste
                </a>
            </div>
        </div>
    </div>

    <div class="se-form-wrap">

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
</div><!-- /.se-page -->
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
