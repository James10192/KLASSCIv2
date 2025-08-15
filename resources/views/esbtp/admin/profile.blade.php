@extends('layouts.app')

@section('title', 'Mon Profil')

@section('page_title', 'Profil SuperAdmin')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-info">
                <h1 class="page-title">Mon Profil SuperAdmin</h1>
                <p class="page-description">Informations personnelles et permissions administrateur</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi btn-acasi-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key"></i>
                    Changer mot de passe
                </button>
                <button type="button" class="btn-acasi btn-acasi-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit"></i>
                    Modifier profil
                </button>
            </div>
        </div>

        <div class="dashboard-main-grid" style="grid-template-columns: 1fr 2fr;">
            <!-- Informations personnelles -->
            <div class="main-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-shield"></i>
                        Informations personnelles
                    </h3>
                </div>
                <div class="card-body">
                    <div class="profile-photo-section">
                        @if($user->profile_photo_path)
                            <img src="{{ Storage::url($user->profile_photo_path) }}" alt="Photo de profil" class="profile-photo">
                        @else
                            <div class="profile-photo profile-photo-placeholder">
                                <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="profile-info-basic">
                            <h2 class="profile-name">{{ $user->name }}</h2>
                            <span class="status-badge status-badge-danger">Super Administrateur</span>
                            <p class="profile-subtitle">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Nom complet</label>
                            <span>{{ $user->name }}</span>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <span>{{ $user->email }}</span>
                        </div>
                        <div class="info-item">
                            <label>Statut</label>
                            <span>
                                @if($user->is_active)
                                    <span class="status-badge status-badge-success">Actif</span>
                                @else
                                    <span class="status-badge status-badge-danger">Inactif</span>
                                @endif
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Date de création</label>
                            <span>{{ $user->created_at->format('d/m/Y à H:i') }}</span>
                        </div>
                        <div class="info-item">
                            <label>Dernière connexion</label>
                            <span>{{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y à H:i') : 'Jamais' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rôles et permissions -->
            <div class="main-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-tag"></i>
                        Rôles et permissions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid" style="grid-template-columns: 1fr;">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-label">Rôle principal</span>
                                <span class="stat-value">
                                    <span class="status-badge status-badge-danger">Super Administrateur</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4 class="section-title">
                            <i class="fas fa-key"></i>
                            Permissions accordées
                        </h4>
                        <div class="permissions-grid">
                            @foreach($user->getAllPermissions() as $permission)
                                <div class="permission-item">
                                    <span class="status-badge status-badge-info">{{ $permission->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal changement de mot de passe -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Changer mon mot de passe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm" action="{{ route('esbtp.admin.update-password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn-acasi btn-acasi-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="changePasswordForm" class="btn-acasi btn-acasi-primary">
                    <i class="fas fa-key"></i>
                    Changer mot de passe
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal édition de profil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Modifier mon profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfileForm" action="{{ route('esbtp.admin.update-profile') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Photo de profil</label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo">
                        <small class="form-text text-muted">Laissez vide pour conserver l'image actuelle.</small>
                    </div>
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn-acasi btn-acasi-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="editProfileForm" class="btn-acasi btn-acasi-primary">
                    <i class="fas fa-save"></i>
                    Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</div>
@endsection 