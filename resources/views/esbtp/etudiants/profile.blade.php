@extends('layouts.app')

@section('title', 'Mon Profil')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour la page profil */
    .profile-container {
        --profile-primary: var(--primary);
        --profile-secondary: var(--secondary);
        --profile-surface: var(--surface);
        --profile-border: rgba(0, 0, 0, 0.08);
    }

    .profile-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--profile-border);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }

    .profile-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
    }

    .profile-header-section {
        display: flex;
        align-items: center;
        gap: var(--space-lg);
        padding-bottom: var(--space-lg);
        margin-bottom: var(--space-lg);
        border-bottom: 1px solid var(--profile-border);
    }

    .profile-photo-wrapper {
        flex-shrink: 0;
    }

    .profile-photo {
        width: 120px;
        height: 120px;
        border-radius: var(--radius-circle);
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .profile-photo-placeholder {
        width: 120px;
        height: 120px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        border: 4px solid white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .profile-header-info {
        flex: 1;
    }

    .profile-name {
        font-size: var(--text-2xl);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }

    .profile-matricule {
        color: var(--text-secondary);
        font-size: var(--text-base);
        margin-bottom: var(--space-sm);
    }

    .profile-badge {
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-xs) var(--space-md);
        background: rgba(var(--success-rgb), 0.1);
        color: var(--success);
        border-radius: var(--radius-medium);
        font-size: var(--text-sm);
        font-weight: 600;
    }

    .section-title {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .section-title i {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-circle);
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
        font-size: var(--text-sm);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: var(--space-xs);
    }

    .info-label {
        font-size: var(--text-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
    }

    .info-value {
        font-size: var(--text-base);
        color: var(--text-primary);
        font-weight: 500;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    .stat-card {
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-md);
    }

    .stat-card:hover {
        background: rgba(var(--primary-rgb), 0.1);
        transform: translateX(4px);
    }

    .stat-left {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--text-lg);
        flex-shrink: 0;
    }

    .stat-label {
        font-size: var(--text-sm);
        font-weight: 600;
        color: var(--text-secondary);
    }

    .stat-value {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--text-primary);
        text-align: right;
    }

    .alert-info-custom {
        background: rgba(var(--primary-rgb), 0.05);
        border-left: 4px solid var(--primary);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        display: flex;
        align-items: flex-start;
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    .alert-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .alert-content h4 {
        font-size: var(--text-base);
        font-weight: 700;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }

    .alert-content p {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        margin: 0;
    }

    .btn-edit-profile {
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-sm) var(--space-lg);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: var(--text-sm);
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(var(--primary-rgb), 0.25);
    }

    .btn-edit-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.35);
        color: white;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-acasi {
            padding: 0 !important;
            max-width: 100vw;
            overflow-x: hidden;
        }

        .main-content {
            padding: 1rem !important;
            max-width: 100%;
            overflow-x: hidden;
            margin: 0 auto;
            width: 100%;
        }

        * {
            max-width: 100%;
        }

        .student-header .d-flex {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: var(--space-md);
        }

        .student-header h1 {
            font-size: 1.5rem !important;
        }

        .student-header .header-subtitle {
            font-size: 0.875rem !important;
        }

        .student-header .text-end {
            text-align: left !important;
            width: 100%;
        }

        .student-header .badge {
            display: inline-block;
            width: auto;
        }

        .profile-header-section {
            flex-direction: column;
            text-align: center;
            align-items: center;
        }

        .profile-photo,
        .profile-photo-placeholder {
            width: 100px;
            height: 100px;
            font-size: 2rem;
        }

        .profile-name {
            font-size: 1.5rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: var(--space-md);
        }

        .stat-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .stat-left {
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
        }

        .stat-value {
            text-align: center;
            width: 100%;
            margin-top: var(--space-sm);
        }

        .profile-card {
            padding: 1rem;
        }

        .btn-edit-profile {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 400px) {
        .main-content {
            padding: 0.75rem !important;
        }

        .student-header h1 {
            font-size: 1.3rem !important;
        }

        .profile-photo,
        .profile-photo-placeholder {
            width: 80px;
            height: 80px;
            font-size: 1.75rem;
        }

        .profile-name {
            font-size: 1.25rem;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi profile-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-user-circle me-3"></i>
                        Mon Profil
                    </h1>
                    <p class="header-subtitle">
                        Informations personnelles et académiques
                    </p>
                </div>
                <div class="text-end d-flex gap-2 flex-wrap justify-content-end">
                    <button type="button" class="btn-edit-profile" data-bs-toggle="modal" data-bs-target="#editContactModal">
                        <i class="fas fa-pen"></i>
                        <span>Modifier mes coordonnées</span>
                    </button>
                    <button type="button" class="btn-edit-profile" style="background: linear-gradient(135deg, #475569, #64748b);" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-lock"></i>
                        <span>Mot de passe</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Carte Profil Principal -->
        <div class="profile-card">
            <div class="profile-header-section">
                <div class="profile-photo-wrapper">
                    @if($etudiant->photo)
                        <img src="{{ $etudiant->photo }}" alt="Photo de {{ $etudiant->prenoms }}" class="profile-photo">
                    @else
                        <div class="profile-photo-placeholder">
                            <span>{{ strtoupper(substr($etudiant->prenoms, 0, 1) . substr($etudiant->nom, 0, 1)) }}</span>
                        </div>
                    @endif
                </div>
                <div class="profile-header-info">
                    <h2 class="profile-name">{{ $etudiant->prenoms }} {{ $etudiant->nom }}</h2>
                    <p class="profile-matricule"><i class="fas fa-id-card me-2"></i>Matricule: {{ $etudiant->matricule }}</p>
                    <span class="profile-badge">
                        <i class="fas fa-check-circle"></i>
                        Étudiant Actif
                    </span>
                </div>
            </div>

            <h3 class="section-title">
                <i class="fas fa-user"></i>
                Informations Personnelles
            </h3>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-birthday-cake me-1"></i>Date de naissance</span>
                    <span class="info-value">{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non spécifiée' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-map-marker-alt me-1"></i>Lieu de naissance</span>
                    <span class="info-value">{{ $etudiant->lieu_naissance ?: 'Non spécifié' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-flag me-1"></i>Nationalité</span>
                    <span class="info-value">{{ $etudiant->nationalite ?: 'Non spécifiée' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-venus-mars me-1"></i>Sexe</span>
                    <span class="info-value">{{ $etudiant->sexe == 'M' ? 'Masculin' : 'Féminin' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-home me-1"></i>Adresse</span>
                    <span class="info-value">{{ $etudiant->adresse ?: 'Non spécifiée' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-phone me-1"></i>Téléphone</span>
                    <span class="info-value">{{ auth()->user()->phone ?: ($etudiant->telephone ?: 'Non spécifié') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-envelope me-1"></i>Email</span>
                    <span class="info-value">{{ auth()->user()->email ?: ($etudiant->email_personnel ?: 'Non spécifié') }}</span>
                </div>
            </div>
        </div>

        <!-- Carte Informations Académiques -->
        <div class="profile-card">
            <h3 class="section-title">
                <i class="fas fa-graduation-cap"></i>
                Informations Académiques
            </h3>

            @if($inscription)
                <div class="alert-info-custom">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <h4>Inscription Active</h4>
                        <p>Vous êtes actuellement inscrit(e) pour l'année universitaire {{ $inscription->anneeUniversitaire->name ?? 'Non spécifiée' }}.</p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-left">
                            <div class="stat-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-label">Filière</div>
                        </div>
                        <div class="stat-value">{{ $inscription->filiere->name ?? 'N/A' }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-left">
                            <div class="stat-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="stat-label">Niveau</div>
                        </div>
                        <div class="stat-value">{{ $inscription->niveau->name ?? 'N/A' }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-left">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-label">Classe</div>
                        </div>
                        <div class="stat-value">{{ $inscription->classe->name ?? 'N/A' }}</div>
                    </div>
                </div>
            @else
                <div class="alert-info-custom" style="background: rgba(var(--danger-rgb), 0.05); border-left-color: var(--danger);">
                    <div class="alert-icon" style="background: var(--danger);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <h4 style="color: var(--danger);">Aucune Inscription Active</h4>
                        <p>Vous n'avez pas d'inscription active pour cette année universitaire. Veuillez contacter l'administration.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Modifier coordonnées (email + téléphone) -->
<div class="modal fade" id="editContactModal" tabindex="-1" aria-labelledby="editContactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.mon-profil.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title" id="editContactModalLabel"><i class="fas fa-pen me-2"></i>Mes coordonnées</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                               value="{{ old('phone', auth()->user()->phone) }}" placeholder="Ex: +225 07 00 00 00 00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Changement de mot de passe -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.mon-profil.password.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel"><i class="fas fa-lock me-2"></i>Changer mon mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <label for="current_password" class="form-label fw-semibold">Mot de passe actuel <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Nouveau mot de passe <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimum 8 caractères.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirmer <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Rouvrir le bon modal si erreurs de validation
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
