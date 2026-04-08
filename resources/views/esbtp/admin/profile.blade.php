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

<!-- Premium Password Change Modal -->
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
            <form method="POST" action="{{ route('esbtp.admin.update-password') }}" id="esbtpAdminPasswordForm">
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
                            <input type="password" name="password" id="esbtpAdminPasswordForm_password" class="form-control" required minlength="8"
                                   style="border: 2px solid #e2e8f0; border-radius: 0.625rem; padding: 0.7rem 2.75rem 0.7rem 1rem; font-size: 0.95rem; transition: border-color 0.2s;"
                                   onfocus="this.style.borderColor='#0453cb'" onblur="this.style.borderColor='#e2e8f0'">
                            <button type="button" onclick="togglePwdVisibility(this)" style="position: absolute; right: 0; top: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #94a3b8;" title="Afficher/masquer">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Password strength indicator -->
                        <div style="margin-top: 0.5rem; display: flex; gap: 0.25rem;" id="esbtpAdminPasswordForm_strength">
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.35rem;" id="esbtpAdminPasswordForm_strength_text">Min. 8 caracteres, majuscule, chiffre, symbole</div>
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
                        <div style="font-size: 0.75rem; color: #ef4444; margin-top: 0.35rem; display: none;" id="esbtpAdminPasswordForm_mismatch">
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
    initPasswordStrength('esbtpAdminPasswordForm');
});
</script>
@endpush
@endsection