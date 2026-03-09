@extends('layouts.app')

@section('title', 'Nouveau Comptable - KLASSCI')

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
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: var(--space-xl);
        text-align: center;
        position: relative;
    }
    .wizard-content { padding: var(--space-xl); }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-lg);
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1 class="page-title"><i class="fas fa-calculator me-2"></i>Nouveau Comptable</h1>
                <p class="text-muted mb-0">Créer un compte comptable avec accès au module finance</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger rounded-3 mb-4">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="form-wizard main-card">
            <div class="wizard-header">
                <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
                    <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-calculator fa-2x text-white"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1">Créer un Comptable</h2>
                <p class="text-white-50 mb-0">Le comptable aura accès au module comptabilité complet (sans suppression)</p>
            </div>

            <div class="wizard-content">
                <form action="{{ route('esbtp.comptables.store') }}" method="POST">
                    @csrf

                    <h5 class="text-success fw-bold mb-3"><i class="fas fa-user me-2"></i>Informations personnelles</h5>
                    <div class="form-grid">
                        <div>
                            <label class="form-label fw-semibold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="Ex: Koffi Ama" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="comptable@ecole.ci" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" name="telephone" class="form-control @error('telephone') is-invalid @enderror"
                                   value="{{ old('telephone') }}" placeholder="+225 07 00 00 00 00">
                            @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Département</label>
                            <select name="department" class="form-select @error('department') is-invalid @enderror">
                                <option value="">-- Sélectionner --</option>
                                <option value="Comptabilité" {{ old('department') === 'Comptabilité' ? 'selected' : '' }}>Comptabilité</option>
                                <option value="Finance" {{ old('department') === 'Finance' ? 'selected' : '' }}>Finance</option>
                                <option value="Audit" {{ old('department') === 'Audit' ? 'selected' : '' }}>Audit</option>
                            </select>
                            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h5 class="text-success fw-bold mb-3 mt-4"><i class="fas fa-lock me-2"></i>Accès & Sécurité</h5>
                    <div class="form-grid">
                        <div>
                            <label class="form-label fw-semibold">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Minimum 8 caractères" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Confirmer le mot de passe <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   placeholder="Répéter le mot de passe" required>
                        </div>
                    </div>

                    <div class="alert alert-info rounded-3 mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Ce compte aura le rôle <strong>comptable</strong> avec accès complet à la comptabilité (paiements, frais, relances, rapports) mais <strong>sans pouvoir supprimer</strong> des données.
                        Les permissions peuvent être ajustées depuis <a href="{{ route('esbtp.roles-permissions.index') }}" class="alert-link">Gestion des Permissions</a>.
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn btn-secondary px-4">
                            <i class="fas fa-times me-1"></i>Annuler
                        </a>
                        <button type="submit" class="btn btn-success px-4 fw-bold">
                            <i class="fas fa-save me-1"></i>Créer le Comptable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
