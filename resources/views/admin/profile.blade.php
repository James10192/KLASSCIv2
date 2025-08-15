@extends('layouts.app')

@section('title', 'Mon Profil - Admin')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-cog me-2"></i>Mon Profil Administrateur</h1>
                <p class="header-subtitle">Gérez vos informations personnelles et professionnelles</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour au tableau de bord
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="dashboard-main-grid" style="grid-template-columns: 1fr 2fr; gap: var(--space-lg);">
            <!-- Colonne gauche: Informations personnelles -->
            <div>
                <!-- Carte Profil -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-user"></i>
                            Informations Personnelles
                        </div>
                    </div>
                    <div class="main-card-body" style="text-align: center;">
                        <div class="position-relative mb-4">
                            <div class="profile-photo-container rounded-circle overflow-hidden border shadow-sm" style="width: 120px; height: 120px; margin: 0 auto;">
                                @if($user->profile_photo_path)
                                    <img src="{{ Storage::url($user->profile_photo_path) }}" alt="{{ $user->name }}" class="w-100 h-100 object-fit-cover">
                                @else
                                    <img src="{{ asset('images/avatar.jpg') }}" alt="{{ $user->name }}" class="w-100 h-100 object-fit-cover">
                                @endif
                            </div>
                            <div class="photo-overlay">
                                <label for="profile_photo" class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 shadow-sm edit-photo-btn" data-bs-toggle="tooltip" title="Changer la photo">
                                    <i class="fas fa-camera text-primary"></i>
                                </label>
                            </div>
                        </div>

                        <h5 class="my-3">{{ $user->name }}</h5>
                        <p class="text-muted mb-1">{{ $user->email }}</p>
                        <div class="badge primary mb-3">
                            <i class="fas fa-id-badge me-1"></i>
                            {{ $user->roles->first()->name ?? 'Utilisateur' }}
                        </div>
                        
                        <button type="button" class="btn-acasi primary" onclick="showEditModal()">
                            <i class="fas fa-edit"></i> Modifier le profil
                        </button>
                    </div>
                </div>
                
                <!-- Carte Informations Système -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-cog"></i>
                            Informations Système
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <strong>ID du compte:</strong><br>
                                <span class="text-muted">{{ $user->id }}</span>
                            </div>
                            <div class="col-12">
                                <strong>Statut:</strong><br>
                                @if($user->is_active)
                                    <span class="badge success">Actif</span>
                                @else
                                    <span class="badge danger">Inactif</span>
                                @endif
                            </div>
                            <div class="col-12">
                                <strong>Compte créé le:</strong><br>
                                <span class="text-muted">{{ $user->created_at->format('d/m/Y à H:i') }}</span>
                            </div>
                            <div class="col-12">
                                <strong>Dernière connexion:</strong><br>
                                <span class="text-muted">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y à H:i') : 'Jamais' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Colonne droite: Informations détaillées -->
            <div>
                <!-- Carte Informations Professionnelles -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-briefcase"></i>
                            Informations Professionnelles
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Poste:</strong><br>
                                <span class="text-muted">{{ $user->position ?? 'Directeur des Études' }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Département:</strong><br>
                                <span class="text-muted">{{ $user->department ?? 'Administration' }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>ID Employé:</strong><br>
                                <span class="text-muted">{{ $user->employee_id ?? 'Non défini' }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Bureau:</strong><br>
                                <span class="text-muted">{{ $user->office_location ?? 'Non défini' }}</span>
                            </div>
                            <div class="col-12">
                                <strong>Date de nomination:</strong><br>
                                <span class="text-muted">{{ $user->appointment_date ? $user->appointment_date->format('d/m/Y') : 'Non définie' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte Permissions -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-shield-alt"></i>
                            Rôles et Permissions
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="mb-3">
                            <strong>Rôles:</strong><br>
                            @forelse($user->roles as $role)
                                <span class="badge primary mb-1">{{ $role->name }}</span>
                            @empty
                                <span class="text-muted">Aucun rôle assigné</span>
                            @endforelse
                        </div>
                        
                        <div>
                            <strong>Permissions clés:</strong><br>
                            @forelse($user->getAllPermissions()->take(10) as $permission)
                                <span class="badge secondary mb-1" style="font-size: 11px;">{{ $permission->name }}</span>
                            @empty
                                <span class="text-muted">Aucune permission directe</span>
                            @endforelse
                            @if($user->getAllPermissions()->count() > 10)
                                <br><small class="text-muted">et {{ $user->getAllPermissions()->count() - 10 }} autres...</small>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-bolt"></i>
                            Actions rapides
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <button type="button" class="btn-acasi secondary" style="width: 100%; justify-content: center;" onclick="showPasswordModal()">
                                    <i class="fas fa-key"></i>
                                    Changer mot de passe
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('settings.index') }}" class="btn-acasi secondary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-cog"></i>
                                    Paramètres système
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de modification du profil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Modifier mon profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden profile photo input -->
                    <input type="file" class="d-none" id="profile_photo" name="profile_photo" accept="image/*">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom d'affichage</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $user->address) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $user->city) }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de modification du mot de passe -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.password.update') }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Modifier le mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function showEditModal() {
        const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
        modal.show();
    }
    
    function showPasswordModal() {
        const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Profile photo upload
        const profilePhotoInput = document.getElementById('profile_photo');
        const profileImage = document.querySelector('.profile-photo-container img');
        const editPhotoBtn = document.querySelector('.edit-photo-btn');

        if (editPhotoBtn && profilePhotoInput) {
            editPhotoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                profilePhotoInput.click();
            });
        }

        if (profilePhotoInput && profileImage) {
            profilePhotoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileImage.src = e.target.result;
                    }
                    reader.readAsDataURL(this.files[0]);
                    
                    // Upload automatique de la photo
                    uploadProfilePhoto(this.files[0]);
                }
            });
        }
        
        function uploadProfilePhoto(file) {
            const formData = new FormData();
            formData.append('profile_photo', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('_method', 'PUT');
            
            // Afficher un indicateur de chargement
            const editPhotoBtn = document.querySelector('.edit-photo-btn');
            const originalIcon = editPhotoBtn.innerHTML;
            editPhotoBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
            
            fetch('{{ route("admin.profile.update") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès temporaire
                    showTemporaryMessage('Photo de profil mise à jour avec succès!', 'success');
                } else {
                    showTemporaryMessage('Erreur lors de la mise à jour de la photo', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showTemporaryMessage('Erreur lors de la mise à jour de la photo', 'error');
            })
            .finally(() => {
                // Restaurer l'icône originale
                editPhotoBtn.innerHTML = originalIcon;
            });
        }
        
        function showTemporaryMessage(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-dismissible fade show animate__animated animate__fadeIn`;
            alertDiv.innerHTML = `
                <i class="fas ${iconClass} me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Insérer l'alerte après le header
            const header = document.querySelector('.dashboard-header');
            header.parentNode.insertBefore(alertDiv, header.nextSibling);
            
            // Faire disparaître l'alerte après 3 secondes
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    });
</script>
@endpush