@extends('layouts.app')

@section('title', 'Mon Profil')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ============================================================
   PAGE MON PROFIL — Design Premium KLASSCI 2025
   ============================================================ */

.profile-page {
    --card-radius: 20px;
    --card-shadow: 0 2px 16px rgba(4, 83, 203, 0.07);
    --card-shadow-hover: 0 8px 32px rgba(4, 83, 203, 0.12);
    --border: rgba(4, 83, 203, 0.08);
    --avatar-size: 110px;
}

/* ---- Hero cover card ---- */
.profile-hero-card {
    background: white;
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
}

.profile-cover {
    height: 120px;
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 55%, #3b82f6 100%);
    position: relative;
    overflow: hidden;
}

.profile-cover::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.profile-cover-actions {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    gap: 0.5rem;
}

.btn-cover-action {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 1rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.18s ease;
    text-decoration: none;
}

.btn-cover-edit {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-cover-edit:hover {
    background: rgba(255, 255, 255, 0.35);
    color: white;
}

.btn-cover-password {
    background: rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(8px);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.btn-cover-password:hover {
    background: rgba(0, 0, 0, 0.4);
    color: white;
}

/* ---- Hero body — contient avatar + identity en flex ---- */
.profile-hero-body {
    position: relative;
    padding: 0 1.75rem 1.25rem;
}

/* ---- Avatar wrap : chevauchement sur la cover ---- */
.profile-avatar-wrap {
    margin-top: -55px;
    flex-shrink: 0;
    position: relative;
    z-index: 2;
}

.profile-avatar {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
    background: white;
    display: block;
}

.profile-avatar-placeholder {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
    background: linear-gradient(135deg, #0453cb, #1b64d4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.02em;
}

/* ---- Identity layout ---- */
.profile-identity {
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
    padding-top: 0.75rem;
}

.profile-identity-info {
    flex: 1;
    padding-top: 0.5rem;
}

.profile-name {
    font-size: 1.45rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 0.35rem;
    letter-spacing: -0.025em;
    line-height: 1.15;
}

/* Ligne de meta infos (matricule + classe) */
.profile-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.6rem;
}

.profile-meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #f1f5ff;
    border: 1px solid #dbeafe;
    border-radius: 6px;
    padding: 0.18rem 0.55rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #2563eb;
}

.profile-meta-chip i {
    font-size: 0.7rem;
    opacity: 0.8;
}

.profile-meta-sep {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #cbd5e1;
    flex-shrink: 0;
}

.profile-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
    border: 1px solid rgba(5, 150, 105, 0.2);
    border-radius: 50px;
    padding: 0.22rem 0.75rem;
    font-size: 0.73rem;
    font-weight: 700;
}

.profile-status-badge.inactive {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border-color: rgba(220, 38, 38, 0.2);
}

/* ---- Section cards ---- */
.profile-section-card {
    background: white;
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border);
    margin-bottom: 1.25rem;
    overflow: hidden;
}

.profile-section-header {
    padding: 1.1rem 1.75rem;
    border-bottom: 1px solid rgba(4, 83, 203, 0.07);
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #fafbff;
}

.profile-section-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: rgba(4, 83, 203, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    color: var(--primary);
    flex-shrink: 0;
}

.profile-section-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    letter-spacing: -0.01em;
}

.profile-section-body {
    padding: 1.5rem 1.75rem;
}

/* ---- Info grid ---- */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.25rem 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.info-label i {
    font-size: 0.65rem;
    opacity: 0.7;
}

.info-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
}

.info-value.muted {
    color: var(--text-muted);
    font-weight: 400;
    font-style: italic;
}

/* ---- Inscription banner ---- */
.inscription-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    margin-bottom: 1.25rem;
}

.inscription-banner.active {
    background: rgba(5, 150, 105, 0.07);
    border: 1px solid rgba(5, 150, 105, 0.2);
}

.inscription-banner.inactive {
    background: rgba(220, 38, 38, 0.06);
    border: 1px solid rgba(220, 38, 38, 0.15);
}

.inscription-banner-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.inscription-banner.active .inscription-banner-icon {
    background: rgba(5, 150, 105, 0.15);
    color: #059669;
}

.inscription-banner.inactive .inscription-banner-icon {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
}

.inscription-banner-title {
    font-size: 0.875rem;
    font-weight: 700;
    margin-bottom: 0.1rem;
}

.inscription-banner.active .inscription-banner-title { color: #047857; }
.inscription-banner.inactive .inscription-banner-title { color: #b91c1c; }

.inscription-banner-text {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin: 0;
}

/* ---- Académique stats ---- */
.acad-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.acad-item {
    background: #f8fafc;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.05);
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.acad-item-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.acad-item-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0453cb, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    color: white;
    flex-shrink: 0;
}

.acad-item-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.acad-item-value {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

/* ---- Responsive ---- */
@media (max-width: 768px) {
    .profile-cover { height: 90px; }
    .profile-cover-actions { top: 0.6rem; right: 0.75rem; }
    .btn-cover-action span { display: none; }
    .profile-hero-body { padding: 0 1.25rem 1rem; }
    .profile-avatar-wrap { margin-top: -44px; }
    .profile-avatar { width: 88px; height: 88px; }
    .profile-avatar-placeholder { width: 88px; height: 88px; font-size: 1.8rem; }
    .profile-section-body { padding: 1.25rem; }
    .profile-section-header { padding: 0.9rem 1.25rem; }
    .acad-grid { grid-template-columns: 1fr; gap: 0.75rem; }
    .acad-item { flex-direction: row; align-items: center; gap: 0.75rem; }
    .acad-item-header { flex-shrink: 0; }
    .info-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
}

@media (max-width: 480px) {
    .profile-identity { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
    .profile-name { font-size: 1.2rem; }
    .info-grid { grid-template-columns: 1fr; }
    .acad-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
    $photoUrl = $etudiant->photo_url ?? null;
    $initials = strtoupper(substr($etudiant->prenoms ?? 'E', 0, 1) . substr($etudiant->nom ?? '', 0, 1));
    $isActif = ($etudiant->statut ?? 'actif') === 'actif';
@endphp

<div class="dashboard-acasi profile-page">
    <div class="main-content">

        {{-- ===== HERO CARD ===== --}}
        <div class="profile-hero-card">
            {{-- Cover --}}
            <div class="profile-cover">
                <div class="profile-cover-actions">
                    <button type="button" class="btn-cover-action btn-cover-edit"
                            data-bs-toggle="modal" data-bs-target="#editContactModal">
                        <i class="fas fa-pen"></i>
                        <span>Modifier</span>
                    </button>
                    <button type="button" class="btn-cover-action btn-cover-password"
                            data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-lock"></i>
                        <span>Mot de passe</span>
                    </button>
                </div>
            </div>

            {{-- Hero body : avatar flottant + identité --}}
            <div class="profile-hero-body">
                <div class="profile-identity">
                    <div class="profile-avatar-wrap">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Photo de {{ $etudiant->prenoms }}" class="profile-avatar"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="profile-avatar-placeholder" style="display:none;">{{ $initials }}</div>
                        @else
                            <div class="profile-avatar-placeholder">{{ $initials }}</div>
                        @endif
                    </div>
                    <div class="profile-identity-info">
                        <h2 class="profile-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h2>
                        <div class="profile-meta">
                            @if($etudiant->matricule)
                            <span class="profile-meta-chip">
                                <i class="fas fa-fingerprint"></i>
                                {{ $etudiant->matricule }}
                            </span>
                            @endif
                            @if(isset($inscription) && $inscription && $inscription->classe)
                            <span class="profile-meta-sep"></span>
                            <span class="profile-meta-chip">
                                <i class="fas fa-users"></i>
                                {{ $inscription->classe->name }}
                            </span>
                            @endif
                        </div>
                        <span class="profile-status-badge {{ $isActif ? '' : 'inactive' }}">
                            <i class="fas fa-{{ $isActif ? 'check-circle' : 'times-circle' }}"></i>
                            {{ $isActif ? 'Étudiant Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== INFORMATIONS PERSONNELLES ===== --}}
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="profile-section-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h3 class="profile-section-title">Informations Personnelles</h3>
            </div>
            <div class="profile-section-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-birthday-cake"></i>Date de naissance</span>
                        <span class="info-value {{ !$etudiant->date_naissance ? 'muted' : '' }}">
                            {{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non spécifiée' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i>Lieu de naissance</span>
                        <span class="info-value {{ !$etudiant->lieu_naissance ? 'muted' : '' }}">
                            {{ $etudiant->lieu_naissance ?: 'Non spécifié' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-flag"></i>Nationalité</span>
                        <span class="info-value {{ !$etudiant->nationalite ? 'muted' : '' }}">
                            {{ $etudiant->nationalite ?: 'Non spécifiée' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-venus-mars"></i>Sexe</span>
                        <span class="info-value">
                            @if($etudiant->sexe == 'M') Masculin
                            @elseif($etudiant->sexe == 'F') Féminin
                            @else <span class="muted">Non spécifié</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-home"></i>Adresse</span>
                        <span class="info-value {{ !$etudiant->adresse ? 'muted' : '' }}">
                            {{ $etudiant->adresse ?: 'Non spécifiée' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-phone"></i>Téléphone</span>
                        <span class="info-value {{ !(auth()->user()->phone ?: $etudiant->telephone) ? 'muted' : '' }}">
                            {{ auth()->user()->phone ?: ($etudiant->telephone ?: 'Non spécifié') }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-envelope"></i>Email</span>
                        <span class="info-value {{ !(auth()->user()->email ?: $etudiant->email_personnel) ? 'muted' : '' }}">
                            {{ auth()->user()->email ?: ($etudiant->email_personnel ?: 'Non spécifié') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== INFORMATIONS ACADÉMIQUES ===== --}}
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="profile-section-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 class="profile-section-title">Informations Académiques</h3>
            </div>
            <div class="profile-section-body">
                @if($inscription)
                    <div class="inscription-banner active">
                        <div class="inscription-banner-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <div class="inscription-banner-title">Inscription Active</div>
                            <p class="inscription-banner-text">
                                Vous êtes inscrit(e) pour l'année universitaire
                                <strong>{{ $inscription->anneeUniversitaire->name ?? 'N/A' }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="acad-grid">
                        <div class="acad-item">
                            <div class="acad-item-header">
                                <div class="acad-item-icon"><i class="fas fa-book-open"></i></div>
                                <span class="acad-item-label">Filière</span>
                            </div>
                            <div class="acad-item-value">{{ $inscription->filiere->name ?? 'N/A' }}</div>
                        </div>
                        <div class="acad-item">
                            <div class="acad-item-header">
                                <div class="acad-item-icon"><i class="fas fa-layer-group"></i></div>
                                <span class="acad-item-label">Niveau</span>
                            </div>
                            <div class="acad-item-value">{{ $inscription->niveau->name ?? 'N/A' }}</div>
                        </div>
                        <div class="acad-item">
                            <div class="acad-item-header">
                                <div class="acad-item-icon"><i class="fas fa-users"></i></div>
                                <span class="acad-item-label">Classe</span>
                            </div>
                            <div class="acad-item-value">{{ $inscription->classe->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                @else
                    <div class="inscription-banner inactive">
                        <div class="inscription-banner-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="inscription-banner-title">Aucune Inscription Active</div>
                            <p class="inscription-banner-text">
                                Vous n'avez pas d'inscription active cette année. Veuillez contacter l'administration.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- ===== MODAL MODIFIER COORDONNÉES ===== --}}
<div class="modal fade" id="editContactModal" tabindex="-1" aria-labelledby="editContactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.mon-profil.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editContactModalLabel">
                        <i class="fas fa-pen me-2"></i>Mes coordonnées
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->hasAny(['email', 'phone']))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                @foreach ($errors->only(['email', 'phone']) as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <p class="text-muted small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Les autres informations (nom, classe, filière…) sont gérées par l'administration.
                    </p>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email', auth()->user()->email) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">Téléphone</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               value="{{ old('phone', auth()->user()->phone) }}"
                               placeholder="Ex: +225 07 00 00 00 00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>#changePasswordModal .modal-dialog { max-width: 600px !important; width: 600px !important; }</style>
{{-- ===== PREMIUM PASSWORD CHANGE MODAL ===== --}}
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: none; border-radius: 1rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <!-- Header with blue gradient -->
            <div style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); padding: 1.5rem 1.75rem; position: relative;">
                <div style="position: absolute; inset: 0; background-image: url(&quot;data:image/svg+xml,%3Csvg width='20' height='20' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='1.5' cy='1.5' r='1' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E&quot;); background-size: 20px 20px;"></div>
                <div style="position: relative; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 44px; height: 44px; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.2);">
                            <i class="fas fa-shield-alt" style="color: white; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <h5 style="color: white; margin: 0; font-weight: 700; font-size: 1.1rem;">Modifier le mot de passe</h5>
                            <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 0.8rem;">Securisez votre compte</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Body -->
            <form method="POST" action="{{ route('esbtp.mon-profil.password.update') }}" id="studentPasswordForm">
                @csrf
                @method('PUT')
                <div style="padding: 1.75rem;">
                    <!-- Error display -->
                    @if($errors->has('current_password') || $errors->has('password'))
                        <div style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; display: flex; align-items: start; gap: 0.5rem;">
                            <i class="fas fa-exclamation-circle" style="color: #ef4444; margin-top: 2px;"></i>
                            <div style="font-size: 0.85rem; color: #ef4444;">
                                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Current password -->
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.5rem;">
                            <i class="fas fa-lock" style="margin-right: 0.35rem; color: #0453cb;"></i>Mot de passe actuel
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="current_password" class="form-control" required
                                   style="border: 2px solid #e2e8f0; border-radius: 0.625rem; padding: 0.7rem 2.75rem 0.7rem 1rem; font-size: 0.95rem; transition: border-color 0.2s;"
                                   onfocus="this.style.borderColor='#0453cb'" onblur="this.style.borderColor='#e2e8f0'">
                            <button type="button" onclick="togglePwdVisibility(this)" style="position: absolute; right: 0; top: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #94a3b8;" title="Afficher/masquer">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div style="height: 1px; background: #e2e8f0; margin: 1.25rem 0;"></div>

                    <!-- New password -->
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.5rem;">
                            <i class="fas fa-key" style="margin-right: 0.35rem; color: #0453cb;"></i>Nouveau mot de passe
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="studentPasswordForm_password" class="form-control" required minlength="8"
                                   style="border: 2px solid #e2e8f0; border-radius: 0.625rem; padding: 0.7rem 2.75rem 0.7rem 1rem; font-size: 0.95rem; transition: border-color 0.2s;"
                                   onfocus="this.style.borderColor='#0453cb'" onblur="this.style.borderColor='#e2e8f0'">
                            <button type="button" onclick="togglePwdVisibility(this)" style="position: absolute; right: 0; top: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #94a3b8;" title="Afficher/masquer">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Password strength indicator -->
                        <div style="margin-top: 0.5rem; display: flex; gap: 0.25rem;" id="studentPasswordForm_strength">
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.35rem;" id="studentPasswordForm_strength_text">Min. 8 caracteres, majuscule, chiffre, symbole</div>
                    </div>

                    <!-- Confirm password -->
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.5rem;">
                            <i class="fas fa-check-double" style="margin-right: 0.35rem; color: #0453cb;"></i>Confirmer le mot de passe
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="password_confirmation" class="form-control" required minlength="8"
                                   style="border: 2px solid #e2e8f0; border-radius: 0.625rem; padding: 0.7rem 2.75rem 0.7rem 1rem; font-size: 0.95rem; transition: border-color 0.2s;"
                                   onfocus="this.style.borderColor='#0453cb'" onblur="this.style.borderColor='#e2e8f0'">
                            <button type="button" onclick="togglePwdVisibility(this)" style="position: absolute; right: 0; top: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #94a3b8;" title="Afficher/masquer">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div style="font-size: 0.75rem; color: #ef4444; margin-top: 0.35rem; display: none;" id="studentPasswordForm_mismatch">
                            <i class="fas fa-times-circle"></i> Les mots de passe ne correspondent pas
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding: 1rem 1.75rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                            style="padding: 0.6rem 1.25rem; border-radius: 0.5rem; font-weight: 500; font-size: 0.9rem; border: 1px solid #e2e8f0; color: #64748b; background: white;">
                        Annuler
                    </button>
                    <button type="submit"
                            style="padding: 0.6rem 1.5rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.9rem; border: none; color: white; background: linear-gradient(135deg, #0453cb, #5e91de); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-shield-alt"></i> Mettre a jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Toggle password visibility
function togglePwdVisibility(btn) {
    const input = btn.parentElement.querySelector('input');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Password strength indicator
function initPasswordStrength(formPrefix) {
    const pwdInput = document.getElementById(formPrefix + '_password');
    if (!pwdInput) return;

    const bars = document.querySelectorAll('#' + formPrefix + '_strength div');
    const text = document.getElementById(formPrefix + '_strength_text');

    pwdInput.addEventListener('input', function() {
        const val = this.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const colors = ['#ef4444', '#f59e0b', '#0453cb', '#10b981'];
        const labels = ['Faible', 'Moyen', 'Bon', 'Excellent'];

        bars.forEach((bar, i) => {
            bar.style.background = i < score ? colors[Math.min(score-1, 3)] : '#e2e8f0';
        });

        if (val.length > 0) {
            text.textContent = labels[Math.min(score-1, 3)] || 'Trop court';
            text.style.color = colors[Math.min(score-1, 3)] || '#94a3b8';
        } else {
            text.textContent = 'Min. 8 caracteres, majuscule, chiffre, symbole';
            text.style.color = '#94a3b8';
        }
    });

    // Password mismatch check
    const confirmInput = pwdInput.closest('form').querySelector('input[name="password_confirmation"]');
    const mismatch = document.getElementById(formPrefix + '_mismatch');
    if (confirmInput && mismatch) {
        confirmInput.addEventListener('input', function() {
            if (this.value && this.value !== pwdInput.value) {
                mismatch.style.display = 'block';
                this.style.borderColor = '#ef4444';
            } else {
                mismatch.style.display = 'none';
                this.style.borderColor = '#e2e8f0';
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initPasswordStrength('studentPasswordForm');

    // Move modal to body to escape stacking context
    var pwdModal = document.getElementById('changePasswordModal');
    if (pwdModal) document.body.appendChild(pwdModal);
});

// Rouvrir le modal s'il y a des erreurs de validation
@if ($errors->hasAny(['current_password', 'password']))
    document.addEventListener('DOMContentLoaded', function() {
        new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
    });
@elseif ($errors->hasAny(['email', 'phone']))
    document.addEventListener('DOMContentLoaded', function() {
        new bootstrap.Modal(document.getElementById('editContactModal')).show();
    });
@endif
</script>
@endpush
@endsection
