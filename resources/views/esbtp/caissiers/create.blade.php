@extends('layouts.app')

@section('title', 'Nouveau Caissier - KLASSCI')

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
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
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
                <h1 class="page-title"><i class="fas fa-cash-register me-2"></i>Nouveau Caissier</h1>
                <p class="text-muted mb-0">Créer un compte caissier avec accès aux pré-inscriptions et paiements</p>
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
                        <i class="fas fa-cash-register fa-2x text-white"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1">Créer un Caissier</h2>
                <p class="text-white-50 mb-0">Le caissier pourra faire des pré-inscriptions rapides et encaisser les paiements</p>
            </div>

            <div class="wizard-content">
                <form action="{{ route('esbtp.caissiers.store') }}" method="POST">
                    @csrf

                    <h5 class="fw-bold mb-3" style="color:#0453cb;"><i class="fas fa-user me-2"></i>Informations personnelles</h5>
                    <div class="form-grid">
                        <div>
                            <label class="form-label fw-semibold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="Ex: Koné Fatou" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="caissier@ecole.ci">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" name="telephone" class="form-control @error('telephone') is-invalid @enderror"
                                   value="{{ old('telephone') }}" placeholder="+225 07 00 00 00 00">
                            @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="alert alert-info rounded-3 mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Ce compte aura le rôle <strong>caissier</strong> avec accès aux pré-inscriptions, paiements, relances et consultation des étudiants/inscriptions.
                    </div>

                    <div class="alert alert-success rounded-3 mt-3">
                        <i class="fas fa-key me-2"></i>
                        Le <strong>nom d'utilisateur</strong> et le <strong>mot de passe</strong> seront générés automatiquement. Ils vous seront affichés après la création du compte.
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn btn-secondary px-4">
                            <i class="fas fa-times me-1"></i>Annuler
                        </a>
                        <button type="submit" class="btn px-4 fw-bold" style="background:linear-gradient(135deg,#0453cb,#5e91de);color:#fff;">
                            <i class="fas fa-save me-1"></i>Créer le Caissier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
