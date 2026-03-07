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

/* ---- Avatar + identity ---- */
.profile-identity {
    padding: 0 1.75rem 1.5rem;
    display: flex;
    align-items: flex-end;
    gap: 1.25rem;
    margin-top: calc(var(--avatar-size) / -2);
}

.profile-avatar-wrap {
    flex-shrink: 0;
    position: relative;
}

.profile-avatar {
    width: var(--avatar-size);
    height: var(--avatar-size);
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    background: white;
    display: block;
}

.profile-avatar-placeholder {
    width: var(--avatar-size);
    height: var(--avatar-size);
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    background: linear-gradient(135deg, #0453cb, #1b64d4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.02em;
}

.profile-identity-info {
    flex: 1;
    padding-bottom: 0.25rem;
}

.profile-name {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-primary);
    margin: 0 0 0.2rem;
    letter-spacing: -0.02em;
}

.profile-matricule {
    font-size: 0.82rem;
    color: var(--text-secondary);
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.profile-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
    border: 1px solid rgba(5, 150, 105, 0.2);
    border-radius: 50px;
    padding: 0.2rem 0.7rem;
    font-size: 0.75rem;
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
    .profile-identity { padding: 0 1.25rem 1.25rem; gap: 1rem; }
    --avatar-size: 88px;
    .profile-section-body { padding: 1.25rem; }
    .profile-section-header { padding: 0.9rem 1.25rem; }
    .acad-grid { grid-template-columns: 1fr; gap: 0.75rem; }
    .acad-item { flex-direction: row; align-items: center; gap: 0.75rem; }
    .acad-item-header { flex-shrink: 0; }
    .info-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
}

@media (max-width: 480px) {
    .profile-identity { flex-direction: column; align-items: flex-start; margin-top: -44px; }
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

            {{-- Avatar + identité --}}
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
                    <h2 class="profile-name">{{ $etudiant->prenoms }} {{ $etudiant->nom }}</h2>
                    <p class="profile-matricule">
                        <i class="fas fa-id-card"></i>
                        {{ $etudiant->matricule ?? 'N/A' }}
                    </p>
                    <span class="profile-status-badge {{ $isActif ? '' : 'inactive' }}">
                        <i class="fas fa-{{ $isActif ? 'check-circle' : 'times-circle' }}"></i>
                        {{ $isActif ? 'Étudiant Actif' : 'Inactif' }}
                    </span>
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

{{-- ===== MODAL MOT DE PASSE ===== --}}
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.mon-profil.password.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-lock me-2"></i>Changer mon mot de passe
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->hasAny(['current_password', 'password']))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                @foreach ($errors->only(['current_password', 'password']) as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">
                            Mot de passe actuel <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" id="current_password"
                               name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">
                            Nouveau mot de passe <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" id="password"
                               name="password" required minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimum 8 caractères.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold">
                            Confirmer <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" id="password_confirmation"
                               name="password_confirmation" required minlength="8" autocomplete="new-password">
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

@push('scripts')
<script>
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
