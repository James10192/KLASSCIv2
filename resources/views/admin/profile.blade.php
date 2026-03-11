@extends('layouts.app')

@section('title', 'Mon Profil - Admin')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-cog me-2"></i>Mon Profil Administrateur</h1>
                <p class="header-subtitle">Gérez vos informations personnelles et professionnelles</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Layout principal -->
        <div class="profile-layout">

            <!-- ===== COLONNE GAUCHE ===== -->
            <div class="profile-col-left">

                <!-- Carte identité -->
                <div class="main-card profile-identity-card">
                    <div class="main-card-body" style="padding: 32px 24px; text-align: center;">

                        <!-- Avatar avec bouton caméra -->
                        <div class="profile-avatar-wrapper">
                            <div class="profile-avatar-ring">
                                <div class="profile-avatar-container">
                                    @if($user->profile_photo_path)
                                        <img src="{{ Storage::url($user->profile_photo_path) }}"
                                             alt="{{ $user->name }}"
                                             class="profile-avatar-img"
                                             id="profileAvatarImg">
                                    @else
                                        <div class="profile-avatar-initials" id="profileAvatarImg">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(strrchr($user->name, ' ') ?: $user->name, 1, 1)) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <label for="profile_photo"
                                   class="profile-camera-btn"
                                   data-bs-toggle="tooltip"
                                   title="Changer la photo"
                                   id="editPhotoBtn">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>

                        <!-- Nom et email -->
                        <h4 class="profile-name">{{ $user->name }}</h4>
                        <p class="profile-email">{{ $user->email }}</p>

                        <!-- Badge rôle -->
                        <div class="profile-role-badge">
                            <i class="fas fa-shield-alt me-1"></i>
                            {{ $user->roles->first()->name ?? 'Utilisateur' }}
                        </div>

                        <!-- Statut compte -->
                        <div class="profile-status-row">
                            @if($user->is_active ?? true)
                                <span class="profile-status active"><i class="fas fa-circle"></i> Compte actif</span>
                            @else
                                <span class="profile-status inactive"><i class="fas fa-circle"></i> Compte inactif</span>
                            @endif
                        </div>

                        <button type="button" class="dept-btn-submit w-100 mt-3" onclick="showEditModal()">
                            <i class="fas fa-edit"></i> Modifier le profil
                        </button>
                    </div>
                </div>

                <!-- Carte infos système -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-server"></i>
                            Informations système
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="prof-info-row">
                            <div class="prof-info-icon"><i class="fas fa-hashtag"></i></div>
                            <div class="prof-info-content">
                                <div class="prof-info-label">ID du compte</div>
                                <div class="prof-info-value"><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:0.85rem;color:#0453cb;">#{{ $user->id }}</code></div>
                            </div>
                        </div>
                        <div class="prof-info-row">
                            <div class="prof-info-icon"><i class="fas fa-calendar-plus"></i></div>
                            <div class="prof-info-content">
                                <div class="prof-info-label">Compte créé le</div>
                                <div class="prof-info-value">{{ $user->created_at->format('d/m/Y à H:i') }}</div>
                            </div>
                        </div>
                        <div class="prof-info-row" style="border-bottom:none;">
                            <div class="prof-info-icon"><i class="fas fa-sign-in-alt"></i></div>
                            <div class="prof-info-content">
                                <div class="prof-info-label">Dernière connexion</div>
                                <div class="prof-info-value">
                                    {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y à H:i') : 'Jamais enregistrée' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ===== COLONNE DROITE ===== -->
            <div class="profile-col-right">

                <!-- Carte informations professionnelles -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-briefcase"></i>
                            Informations professionnelles
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="prof-grid-2">
                            <div class="prof-info-block">
                                <div class="prof-info-label">Poste</div>
                                <div class="prof-info-value">{{ $user->position ?? 'Directeur des Études' }}</div>
                            </div>
                            <div class="prof-info-block">
                                <div class="prof-info-label">Département</div>
                                <div class="prof-info-value">{{ $user->department ?? 'Administration' }}</div>
                            </div>
                            <div class="prof-info-block">
                                <div class="prof-info-label">ID Employé</div>
                                <div class="prof-info-value {{ ($user->employee_id ?? null) ? '' : 'muted' }}">
                                    {{ $user->employee_id ?? 'Non défini' }}
                                </div>
                            </div>
                            <div class="prof-info-block">
                                <div class="prof-info-label">Bureau</div>
                                <div class="prof-info-value {{ ($user->office_location ?? null) ? '' : 'muted' }}">
                                    {{ $user->office_location ?? 'Non défini' }}
                                </div>
                            </div>
                            <div class="prof-info-block" style="grid-column: 1 / -1;">
                                <div class="prof-info-label">Date de nomination</div>
                                <div class="prof-info-value {{ ($user->appointment_date ?? null) ? '' : 'muted' }}">
                                    {{ isset($user->appointment_date) && $user->appointment_date ? $user->appointment_date->format('d/m/Y') : 'Non définie' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte rôles et permissions -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-shield-alt"></i>
                            Rôles et permissions
                        </div>
                    </div>
                    <div class="main-card-body">
                        <!-- Rôles -->
                        <div class="prof-section-block">
                            <div class="prof-section-title">Rôles assignés</div>
                            <div class="prof-badges-wrap">
                                @forelse($user->roles as $role)
                                    <span class="prof-badge-role">
                                        <i class="fas fa-user-shield me-1"></i>{{ $role->name }}
                                    </span>
                                @empty
                                    <span class="prof-info-value muted">Aucun rôle assigné</span>
                                @endforelse
                            </div>
                        </div>
                        <!-- Permissions -->
                        <div class="prof-section-block" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                            <div class="prof-section-title">
                                Permissions clés
                                @if($user->getAllPermissions()->count() > 10)
                                    <span class="prof-perm-count">+{{ $user->getAllPermissions()->count() - 10 }} autres</span>
                                @endif
                            </div>
                            <div class="prof-badges-wrap" style="gap: 6px;">
                                @forelse($user->getAllPermissions()->take(10) as $permission)
                                    <span class="prof-badge-perm">{{ $permission->name }}</span>
                                @empty
                                    <span class="prof-info-value muted">Aucune permission directe</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte actions rapides -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-bolt"></i>
                            Actions rapides
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="prof-actions-grid">
                            <button type="button" class="prof-action-btn" onclick="showPasswordModal()">
                                <div class="prof-action-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="prof-action-text">
                                    <div class="prof-action-title">Changer le mot de passe</div>
                                    <div class="prof-action-sub">Sécurisez votre accès</div>
                                </div>
                                <i class="fas fa-chevron-right prof-action-arrow"></i>
                            </button>
                            <a href="{{ route('settings.index') }}" class="prof-action-btn">
                                <div class="prof-action-icon" style="background: rgba(94,145,222,0.1); color: #5e91de;">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="prof-action-text">
                                    <div class="prof-action-title">Paramètres système</div>
                                    <div class="prof-action-sub">Configuration globale</div>
                                </div>
                                <i class="fas fa-chevron-right prof-action-arrow"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Input caché pour photo -->
        <input type="file" class="d-none" id="profile_photo" name="profile_photo" accept="image/*">

    </div>
</div>

<!-- Modal modification profil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(4,83,203,0.15);">
            <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 20px 24px;">
                    <h5 class="modal-title" style="font-weight: 700; color: #1e293b;">
                        <i class="fas fa-user-edit me-2" style="color: #0453cb;"></i>Modifier mon profil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-moderne"><i class="fas fa-user me-1"></i> Prénom</label>
                            <input type="text" class="form-input-moderne" name="first_name"
                                   value="{{ old('first_name', $user->first_name ?? '') }}" placeholder="Votre prénom">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-moderne"><i class="fas fa-user me-1"></i> Nom</label>
                            <input type="text" class="form-input-moderne" name="last_name"
                                   value="{{ old('last_name', $user->last_name ?? '') }}" placeholder="Votre nom">
                        </div>
                        <div class="col-12">
                            <label class="form-label-moderne"><i class="fas fa-id-card me-1"></i> Nom d'affichage</label>
                            <input type="text" class="form-input-moderne" name="name"
                                   value="{{ old('name', $user->name) }}" placeholder="Nom affiché dans l'app">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-moderne"><i class="fas fa-envelope me-1"></i> Email</label>
                            <input type="email" class="form-input-moderne" name="email"
                                   value="{{ old('email', $user->email) }}" placeholder="votre@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-moderne"><i class="fas fa-phone me-1"></i> Téléphone</label>
                            <input type="text" class="form-input-moderne" name="phone"
                                   value="{{ old('phone', $user->phone ?? '') }}" placeholder="+225 XX XX XX XX">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label-moderne"><i class="fas fa-map-marker-alt me-1"></i> Adresse</label>
                            <input type="text" class="form-input-moderne" name="address"
                                   value="{{ old('address', $user->address ?? '') }}" placeholder="Votre adresse">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-moderne"><i class="fas fa-city me-1"></i> Ville</label>
                            <input type="text" class="form-input-moderne" name="city"
                                   value="{{ old('city', $user->city ?? '') }}" placeholder="Ville">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                    <button type="button" class="dept-btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="dept-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal changement mot de passe -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(4,83,203,0.15);">
            <form action="{{ route('admin.password.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 20px 24px;">
                    <h5 class="modal-title" style="font-weight: 700; color: #1e293b;">
                        <i class="fas fa-key me-2" style="color: #0453cb;"></i>Modifier le mot de passe
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label class="form-label-moderne"><i class="fas fa-lock me-1"></i> Mot de passe actuel</label>
                        <input type="password" class="form-input-moderne" name="current_password" required placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-moderne"><i class="fas fa-lock me-1"></i> Nouveau mot de passe</label>
                        <input type="password" class="form-input-moderne" name="password" required placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-moderne"><i class="fas fa-lock me-1"></i> Confirmer le mot de passe</label>
                        <input type="password" class="form-input-moderne" name="password_confirmation" required placeholder="••••••••">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                    <button type="button" class="dept-btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="dept-btn-submit">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ===== PROFIL ADMIN — LAYOUT ===== */
.profile-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 24px;
    align-items: start;
}
.profile-col-right {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
@media (max-width: 992px) {
    .profile-layout { grid-template-columns: 1fr; }
}

/* Avatar */
.profile-avatar-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}
.profile-avatar-ring {
    width: 104px; height: 104px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    padding: 3px;
    margin: 0 auto;
    display: flex;
}
.profile-avatar-container {
    width: 100%; height: 100%;
    border-radius: 50%;
    overflow: hidden;
    background: #f8faff;
    display: flex; align-items: center; justify-content: center;
}
.profile-avatar-img {
    width: 100%; height: 100%; object-fit: cover;
}
.profile-avatar-initials {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 800;
    color: #0453cb;
    background: linear-gradient(135deg, rgba(4,83,203,0.08) 0%, rgba(94,145,222,0.12) 100%);
    letter-spacing: 2px;
}
.profile-camera-btn {
    position: absolute;
    bottom: 2px; right: 2px;
    width: 30px; height: 30px;
    background: #fff;
    border-radius: 50%;
    border: 2px solid #e8eef8;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    color: #0453cb; font-size: 0.75rem;
}
.profile-camera-btn:hover {
    background: #0453cb; color: #fff; border-color: #0453cb;
    transform: scale(1.1);
}

.profile-name {
    font-size: 1.15rem; font-weight: 800; color: #1e293b;
    margin: 0 0 4px 0;
}
.profile-email {
    font-size: 0.85rem; color: #64748b; margin: 0 0 12px 0;
}
.profile-role-badge {
    display: inline-flex; align-items: center;
    padding: 4px 14px;
    background: linear-gradient(135deg, rgba(4,83,203,0.08) 0%, rgba(94,145,222,0.08) 100%);
    color: #0453cb;
    border: 1px solid rgba(4,83,203,0.15);
    border-radius: 20px;
    font-size: 0.78rem; font-weight: 700; letter-spacing: 0.3px;
    margin-bottom: 10px;
}
.profile-status-row { margin-bottom: 4px; }
.profile-status {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.8rem; font-weight: 600;
}
.profile-status.active { color: #10b981; }
.profile-status.active i { font-size: 0.5rem; }
.profile-status.inactive { color: #f59e0b; }
.profile-status.inactive i { font-size: 0.5rem; }

/* Info rows colonne gauche */
.prof-info-row {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}
.prof-info-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(4,83,203,0.07);
    display: flex; align-items: center; justify-content: center;
    color: #0453cb; font-size: 0.8rem; flex-shrink: 0;
}
.prof-info-content { flex: 1; }
.prof-info-label {
    font-size: 0.7rem; font-weight: 600; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px;
}
.prof-info-value { font-size: 0.88rem; font-weight: 600; color: #1e293b; }
.prof-info-value.muted { font-weight: 400; color: #94a3b8; font-style: italic; }

/* Grille 2 colonnes infos pro */
.prof-grid-2 {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 0;
}
.prof-info-block {
    padding: 14px 16px;
    border-bottom: 1px solid #f8fafc;
    border-right: 1px solid #f8fafc;
}
.prof-info-block:nth-child(even) { border-right: none; }
.prof-info-block:nth-last-child(-n+2) { border-bottom: none; }

/* Badges rôles / permissions */
.prof-section-block { }
.prof-section-title {
    font-size: 0.78rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-bottom: 10px;
    display: flex; align-items: center; gap: 8px;
}
.prof-perm-count {
    font-size: 0.72rem; background: #f1f5f9; color: #64748b;
    padding: 2px 8px; border-radius: 10px; font-weight: 500;
    letter-spacing: 0;
}
.prof-badges-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
.prof-badge-role {
    display: inline-flex; align-items: center;
    padding: 5px 14px;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: #fff; border-radius: 20px;
    font-size: 0.8rem; font-weight: 600;
}
.prof-badge-perm {
    display: inline-block;
    padding: 3px 10px;
    background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 6px; font-size: 0.75rem; font-weight: 500;
}

/* Actions rapides */
.prof-actions-grid { display: flex; flex-direction: column; gap: 12px; }
.prof-action-btn {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 18px;
    background: #f8faff;
    border: 1px solid #e8eef8;
    border-radius: 12px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    color: #1e293b;
}
.prof-action-btn:hover {
    background: #eef3fb;
    border-color: rgba(4,83,203,0.2);
    transform: translateX(3px);
    text-decoration: none; color: #1e293b;
    box-shadow: 0 4px 12px rgba(4,83,203,0.08);
}
.prof-action-icon {
    width: 44px; height: 44px; border-radius: 10px;
    background: rgba(4,83,203,0.1); color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.prof-action-text { flex: 1; }
.prof-action-title { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
.prof-action-sub { font-size: 0.77rem; color: #94a3b8; margin-top: 2px; }
.prof-action-arrow { color: #c7d5e8; font-size: 0.8rem; }

/* Boutons partagés avec departments */
.dept-btn-submit {
    display: inline-flex; align-items: center; gap: 9px;
    padding: 11px 28px;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: #fff; border: none; border-radius: 8px;
    font-weight: 700; font-size: 0.95rem; cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 14px rgba(4, 83, 203, 0.3);
    text-decoration: none;
    justify-content: center;
}
.dept-btn-submit.w-100 { width: 100%; }
.dept-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(4, 83, 203, 0.4);
    color: #fff; text-decoration: none;
}
.dept-btn-cancel {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; background: transparent;
    color: #64748b; border: 2px solid #cbd5e1;
    border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}
.dept-btn-cancel:hover {
    background: #f1f5f9; border-color: #94a3b8; color: #475569;
    transform: translateY(-1px); text-decoration: none;
}
</style>
@endpush

@push('scripts')
<script>
    function showEditModal() {
        new bootstrap.Modal(document.getElementById('editProfileModal')).show();
    }
    function showPasswordModal() {
        new bootstrap.Modal(document.getElementById('passwordModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Tooltips Bootstrap
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        // Upload photo profil
        const profilePhotoInput = document.getElementById('profile_photo');
        const editPhotoBtn = document.getElementById('editPhotoBtn');
        const avatarContainer = document.querySelector('.profile-avatar-container');

        if (editPhotoBtn && profilePhotoInput) {
            editPhotoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                profilePhotoInput.click();
            });
        }

        if (profilePhotoInput) {
            profilePhotoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // Prévisualisation immédiate
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarContainer.innerHTML = `<img src="${e.target.result}" class="profile-avatar-img">`;
                    };
                    reader.readAsDataURL(this.files[0]);
                    uploadProfilePhoto(this.files[0]);
                }
            });
        }

        function uploadProfilePhoto(file) {
            const formData = new FormData();
            formData.append('profile_photo', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('_method', 'PUT');

            const icon = editPhotoBtn.querySelector('i');
            icon.className = 'fas fa-spinner fa-spin';

            fetch('{{ route("admin.profile.update") }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                showMsg(data.success ? 'Photo mise à jour avec succès !' : 'Erreur lors de la mise à jour', data.success ? 'success' : 'danger');
            })
            .catch(() => showMsg('Erreur réseau', 'danger'))
            .finally(() => { icon.className = 'fas fa-camera'; });
        }

        function showMsg(message, type) {
            const div = document.createElement('div');
            div.className = `alert alert-${type} alert-dismissible fade show`;
            div.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            const header = document.querySelector('.dashboard-header');
            header.parentNode.insertBefore(div, header.nextSibling);
            setTimeout(() => div.remove(), 4000);
        }
    });
</script>
@endpush
