@php
use Illuminate\Support\Facades\Storage;
@endphp

@extends('layouts.app')

@section('title', 'Mon Profil - Enseignant')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Main content padding responsive */
    .main-content {
        padding: 1.5rem;
        max-width: 100%;
        overflow-x: hidden;
    }

    /* Grille principale responsive */
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: var(--space-lg);
    }

    @media (max-width: 992px) {
        .profile-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .main-content {
            padding: 1rem;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 0.75rem;
        }

        .profile-grid {
            gap: var(--space-sm);
        }
    }

    /* Header responsive */
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            text-align: center;
            gap: var(--space-md);
        }

        .dashboard-header h1 {
            font-size: 1.5rem;
        }

        .header-subtitle {
            font-size: 0.875rem;
        }

        .header-actions {
            width: 100%;
        }

        .header-actions .btn-acasi {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 576px) {
        .dashboard-header h1 i {
            display: none;
        }

        .dashboard-header h1 {
            font-size: 1.25rem;
        }
    }

    /* Carte profil responsive */
    @media (max-width: 576px) {
        .main-card-body img.rounded-circle {
            width: 100px !important;
            height: 100px !important;
        }

        .main-card-body h5 {
            font-size: 1.125rem;
        }

        .main-card-body p {
            font-size: 0.875rem;
        }
    }

    /* Actions rapides responsive */
    @media (max-width: 768px) {
        .row.g-3 .col-md-4,
        .row.g-3 .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .btn-acasi {
            width: 100% !important;
            min-height: 44px;
            padding: 0.625rem 1rem;
            justify-content: center !important;
        }
    }

    @media (max-width: 576px) {
        .row.g-3 {
            gap: 0.5rem !important;
        }

        .btn-acasi {
            font-size: 0.875rem;
        }

        .btn-acasi i {
            font-size: 1rem;
        }
    }

    /* Informations professionnelles responsive */
    @media (max-width: 576px) {
        .main-card-body .mb-3 {
            margin-bottom: 0.75rem !important;
            font-size: 0.875rem;
        }

        .main-card-body strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    }

    /* Modal responsive */
    @media (max-width: 768px) {
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-header,
        .modal-footer {
            padding: 0.75rem 1rem;
        }

        .modal-title {
            font-size: 1.125rem;
        }

        .form-label {
            font-size: 0.875rem;
        }

        .form-control {
            font-size: 0.875rem;
        }
    }

    /* Alertes responsive */
    @media (max-width: 768px) {
        .alert {
            font-size: 0.875rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }
    }

    /* Main card responsive */
    @media (max-width: 576px) {
        .main-card {
            margin-bottom: 1rem;
        }

        .main-card-header {
            padding: var(--space-md);
        }

        .main-card-body {
            padding: var(--space-md);
        }

        .main-card-title {
            font-size: 1rem;
        }

        .main-card-title i {
            font-size: 0.875rem;
        }
    }

    /* Grille informations compte */
    @media (max-width: 768px) {
        .row.g-3 > div {
            margin-bottom: 1rem;
        }

        .row.g-3 > div strong {
            font-size: 0.875rem;
        }

        .row.g-3 > div .text-muted {
            font-size: 0.8125rem;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chalkboard-teacher me-2"></i>Mon Profil Enseignant</h1>
                <p class="header-subtitle">Consultez et modifiez vos informations personnelles</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('teacher.dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour au tableau de bord
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="profile-grid">
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
                        <img class="rounded-circle mb-3"
                             src="{{ $teacher->user->profile_photo_path ? Storage::url($teacher->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($teacher->user->name) . '&size=120&background=0D6EFD&color=fff' }}"
                             alt="Photo de profil" style="width: 120px; height: 120px; object-fit: cover;">
                        <h5 class="my-3">{{ $teacher->user->name }}</h5>
                        <p class="text-muted mb-1">{{ $teacher->user->email }}</p>
                        <p class="text-muted mb-1">ID: {{ $teacher->employee_id }}</p>
                        <p class="text-muted mb-4">{{ $teacher->user->phone ?? 'Aucun numéro de téléphone' }}</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn-acasi primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit"></i> Modifier le profil
                            </button>
                            <button type="button" class="btn-acasi secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="fas fa-lock"></i> Changer le mot de passe
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Carte Informations Professionnelles -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-briefcase"></i>
                            Informations Professionnelles
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="mb-3">
                            <strong>Département:</strong> {{ $teacher->department->name ?? 'Non assigné' }}
                        </div>
                        <div class="mb-3">
                            <strong>Poste:</strong> {{ $teacher->designation->name ?? 'Non assigné' }}
                        </div>
                        <div class="mb-3">
                            <strong>Date d'entrée:</strong> {{ $teacher->joining_date ? \Carbon\Carbon::parse($teacher->joining_date)->format('d/m/Y') : 'Non définie' }}
                        </div>
                        <div class="mb-3">
                            <strong>Qualification:</strong> {{ $teacher->qualification ?? 'Non renseignée' }}
                        </div>
                        <div class="mb-3">
                            <strong>Expérience:</strong> {{ $teacher->experience ?? 'Non renseignée' }}
                        </div>
                        @if($teacher->subjects && $teacher->subjects->count() > 0)
                            <div class="mb-3">
                                <strong>Matières enseignées:</strong><br>
                                @foreach($teacher->subjects as $subject)
                                    <span class="badge bg-primary mb-1">{{ $subject->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Colonne droite: Informations détaillées -->
            <div>
                <!-- Carte d'info de compte -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-info-circle"></i>
                            Informations du Compte
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Nom d'utilisateur:</strong><br>
                                <span class="text-muted">{{ $teacher->user->username ?? $teacher->user->email }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Adresse email:</strong><br>
                                <span class="text-muted">{{ $teacher->user->email }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Date d'inscription:</strong><br>
                                <span class="text-muted">{{ $teacher->user->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Dernière connexion:</strong><br>
                                <span class="text-muted">{{ $teacher->user->last_login_at ?? 'Jamais' }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Statut du compte:</strong><br>
                                @if($teacher->user->is_active)
                                    <span class="badge success">Actif</span>
                                @else
                                    <span class="badge danger">Inactif</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Rôle:</strong><br>
                                @forelse($teacher->user->getRoleNames() as $role)
                                    <span class="badge primary">{{ $role }}</span>
                                @empty
                                    <span class="text-muted">Aucun rôle assigné</span>
                                @endforelse
                            </div>
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
                            <div class="col-md-4">
                                <a href="{{ route('teacher.timetable') }}" class="btn-acasi secondary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-calendar-alt"></i>
                                    Emploi du temps
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('teacher.grades') }}" class="btn-acasi secondary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-edit"></i>
                                    Saisir notes
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('esbtp.attendance.mark') }}" class="btn-acasi secondary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-user-check"></i>
                                    Émargement
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>#changePasswordModal .modal-dialog { max-width: 600px !important; width: 600px !important; }</style>
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
            <form method="POST" action="{{ route('teacher.profile.password.update') }}" id="teacherPasswordForm">
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
                            <input type="password" name="password" id="teacherPasswordForm_password" class="form-control" required minlength="8"
                                   style="border: 2px solid #e2e8f0; border-radius: 0.625rem; padding: 0.7rem 2.75rem 0.7rem 1rem; font-size: 0.95rem; transition: border-color 0.2s;"
                                   onfocus="this.style.borderColor='#0453cb'" onblur="this.style.borderColor='#e2e8f0'">
                            <button type="button" onclick="togglePwdVisibility(this)" style="position: absolute; right: 0; top: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #94a3b8;" title="Afficher/masquer">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Password strength indicator -->
                        <div style="margin-top: 0.5rem; display: flex; gap: 0.25rem;" id="teacherPasswordForm_strength">
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                            <div style="height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.3s;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.35rem;" id="teacherPasswordForm_strength_text">Min. 8 caracteres, majuscule, chiffre, symbole</div>
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
                        <div style="font-size: 0.75rem; color: #ef4444; margin-top: 0.35rem; display: none;" id="teacherPasswordForm_mismatch">
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

<!-- Modal de modification du profil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('teacher.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Modifier mon profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Affichage des erreurs de validation -->
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Erreur :</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Photo de profil -->
                    <div class="mb-4 text-center">
                        <div class="mb-3">
                            <img id="preview-image" class="rounded-circle"
                                 src="{{ $teacher->user->profile_photo_path ? Storage::url($teacher->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($teacher->user->name) . '&size=120&background=0D6EFD&color=fff' }}"
                                 alt="Photo de profil"
                                 style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;">
                        </div>
                        <div class="mb-2">
                            <label for="profile_photo" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-camera"></i> Changer la photo
                            </label>
                            <input type="file" class="d-none" id="profile_photo" name="profile_photo"
                                   accept="image/jpeg,image/png,image/jpg,image/gif"
                                   onchange="previewProfilePhoto(event)">
                        </div>
                        <small class="text-muted">Format: JPG, PNG, GIF (max 2MB)</small>
                    </div>

                    <hr>

                    <!-- Informations personnelles -->
                    <h6 class="mb-3"><i class="fas fa-user"></i> Informations personnelles</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $teacher->user->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $teacher->user->email }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ $teacher->user->phone }}">
                        </div>
                    </div>

                    <hr>

                    <!-- Informations professionnelles -->
                    <h6 class="mb-3"><i class="fas fa-briefcase"></i> Informations professionnelles</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="qualification" class="form-label">Qualification</label>
                            <input type="text" class="form-control" id="qualification" name="qualification" value="{{ $teacher->qualification }}" placeholder="Ex: Master en Informatique">
                        </div>
                        <div class="col-md-6">
                            <label for="experience" class="form-label">Expérience</label>
                            <input type="text" class="form-control" id="experience" name="experience" value="{{ $teacher->experience }}" placeholder="Ex: 5 ans">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewProfilePhoto(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

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
    initPasswordStrength('teacherPasswordForm');

    // Move modal to body to escape stacking context
    var pwdModal = document.getElementById('changePasswordModal');
    if (pwdModal) document.body.appendChild(pwdModal);
});

// Rouvrir le modal s'il y a des erreurs de validation
@if ($errors->hasAny(['current_password', 'password']))
    document.addEventListener('DOMContentLoaded', function() {
        new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
    });
@elseif ($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        new bootstrap.Modal(document.getElementById('editProfileModal')).show();
    });
@endif
</script>

@endsection 