@extends('layouts.app')

@section('title', 'Mon Profil')

@section('page_title', 'Mon Profil Étudiant')

@push('styles')
<style>
    /* Responsive fixes for mobile (390x844) */
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

        .dashboard-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 1rem;
        }

        .dashboard-header h1 {
            font-size: 1.5rem !important;
        }

        .dashboard-header .page-description {
            font-size: 0.875rem !important;
        }

        .header-actions {
            width: 100%;
        }

        .header-actions .btn-acasi {
            width: 100%;
            font-size: 0.875rem;
        }

        .dashboard-main-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }

        .main-card {
            max-width: 100%;
        }

        .stats-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }

        .info-grid {
            grid-template-columns: 1fr !important;
        }

        .profile-photo {
            width: 100px !important;
            height: 100px !important;
        }

        .profile-name {
            font-size: 1.25rem !important;
        }

        .profile-subtitle {
            font-size: 0.8rem !important;
        }

        .card-title {
            font-size: 1rem !important;
        }

        .stat-item {
            padding: 1rem !important;
        }

        .stat-value {
            font-size: 1rem !important;
        }

        .stat-label {
            font-size: 0.75rem !important;
        }

        .alert {
            padding: 0.75rem !important;
            font-size: 0.85rem;
        }

        .alert-title {
            font-size: 0.95rem !important;
        }

        .alert-message {
            font-size: 0.8rem !important;
        }
    }

    @media (max-width: 400px) {
        .main-content {
            padding: 0.75rem !important;
        }

        .dashboard-header h1 {
            font-size: 1.3rem !important;
        }

        .profile-photo {
            width: 80px !important;
            height: 80px !important;
        }

        .profile-name {
            font-size: 1.1rem !important;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-info">
                <h1 class="page-title">Mon Profil</h1>
                <p class="page-description">Informations personnelles et académiques</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi btn-acasi-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-pen"></i>
                    Demander une modification
                </button>
            </div>
        </div>

        <div class="dashboard-main-grid" style="grid-template-columns: 1fr 2fr;">
            <!-- Informations personnelles -->
            <div class="main-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle"></i>
                        Informations personnelles
                    </h3>
                </div>
                <div class="card-body">
                    <div class="profile-photo-section">
                        @if($etudiant->photo)
                            <img src="{{ $etudiant->photo }}" alt="Photo de {{ $etudiant->prenoms }}" class="profile-photo">
                        @else
                            <div class="profile-photo profile-photo-placeholder">
                                <span>{{ strtoupper(substr($etudiant->prenoms, 0, 1) . substr($etudiant->nom, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="profile-info-basic">
                            <h2 class="profile-name">{{ $etudiant->prenoms }} {{ $etudiant->nom }}</h2>
                            <span class="status-badge status-badge-success">Étudiant</span>
                            <p class="profile-subtitle">Matricule: {{ $etudiant->matricule }}</p>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Date de naissance</label>
                            <span>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non spécifiée' }}</span>
                        </div>
                        <div class="info-item">
                            <label>Lieu de naissance</label>
                            <span>{{ $etudiant->lieu_naissance ?: 'Non spécifié' }}</span>
                        </div>
                        <div class="info-item">
                            <label>Nationalité</label>
                            <span>{{ $etudiant->nationalite ?: 'Non spécifiée' }}</span>
                        </div>
                        <div class="info-item">
                            <label>Sexe</label>
                            <span>{{ $etudiant->sexe == 'M' ? 'Masculin' : 'Féminin' }}</span>
                        </div>
                        <div class="info-item">
                            <label>Adresse</label>
                            <span>{{ $etudiant->adresse ?: 'Non spécifiée' }}</span>
                        </div>
                        <div class="info-item">
                            <label>Téléphone</label>
                            <span>{{ $etudiant->telephone ?: 'Non spécifié' }}</span>
                        </div>
                        <div class="info-item">
                            <label>Email personnel</label>
                            <span>{{ $etudiant->email_personnel ?: 'Non spécifié' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations académiques -->
            <div class="dashboard-content-area">
                <div class="main-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-graduation-cap"></i>
                            Informations académiques
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($inscription)
                            <div class="alert alert-success">
                                <div class="alert-content">
                                    <i class="fas fa-check-circle alert-icon"></i>
                                    <div>
                                        <h4 class="alert-title">Inscription active</h4>
                                        <p class="alert-message">Vous êtes actuellement inscrit(e) pour l'année universitaire {{ $inscription->anneeUniversitaire->name ?? 'Non spécifiée' }}.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Filière</span>
                                        <span class="stat-value">{{ $inscription->filiere->name ?? 'Non spécifiée' }}</span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Niveau</span>
                                        <span class="stat-value">{{ $inscription->niveau->name ?? 'Non spécifié' }}</span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-chalkboard"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Classe</span>
                                        <span class="stat-value">{{ $inscription->classe->name ?? 'Non spécifiée' }}</span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Date d'inscription</span>
                                        <span class="stat-value">{{ \Carbon\Carbon::parse($inscription->date_inscription)->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <div class="alert-content">
                                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                                    <div>
                                        <h4 class="alert-title">Aucune inscription active</h4>
                                        <p class="alert-message">Vous n'avez pas d'inscription active pour l'année universitaire en cours. Veuillez contacter l'administration pour plus d'informations.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="main-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i>
                            Historique des inscriptions
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($etudiant->inscriptions->count() > 0)
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Année universitaire</th>
                                            <th>Filière</th>
                                            <th>Niveau</th>
                                            <th>Classe</th>
                                            <th>Statut</th>
                                            <th>Date d'inscription</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($etudiant->inscriptions as $inscription)
                                        <tr>
                                            <td>{{ $inscription->anneeUniversitaire->name ?? 'Non spécifiée' }}</td>
                                            <td>{{ $inscription->filiere->name ?? 'Non spécifiée' }}</td>
                                            <td>{{ $inscription->niveau->name ?? 'Non spécifié' }}</td>
                                            <td>{{ $inscription->classe->name ?? 'Non spécifiée' }}</td>
                                            <td>
                                                @if($inscription->statut == 'active')
                                                    <span class="status-badge status-badge-success">Active</span>
                                                @elseif($inscription->statut == 'completed')
                                                    <span class="status-badge status-badge-info">Terminée</span>
                                                @elseif($inscription->statut == 'cancelled')
                                                    <span class="status-badge status-badge-danger">Annulée</span>
                                                @else
                                                    <span class="status-badge status-badge-secondary">{{ ucfirst($inscription->statut) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($inscription->date_inscription)->format('d/m/Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3 class="empty-state-title">Aucun historique</h3>
                                <p class="empty-state-description">Aucun historique d'inscription disponible.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour demande de modification -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Demander une modification de profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Les modifications de profil doivent être validées par l'administration avant d'être appliquées.
                </div>
                <form>
                    <div class="mb-3">
                        <label for="modification_type" class="form-label">Type de modification</label>
                        <select class="form-select" id="modification_type" name="modification_type">
                            <option value="">Sélectionner le type de modification</option>
                            <option value="contact">Informations de contact</option>
                            <option value="personnel">Informations personnelles</option>
                            <option value="photo">Photo de profil</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modification_details" class="form-label">Détails de la modification demandée</label>
                        <textarea class="form-control" id="modification_details" name="modification_details" rows="4" placeholder="Décrivez précisément les modifications souhaitées..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="justification" class="form-label">Justification</label>
                        <textarea class="form-control" id="justification" name="justification" rows="3" placeholder="Expliquez pourquoi cette modification est nécessaire..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi btn-acasi-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn-acasi btn-acasi-primary">Envoyer la demande</button>
            </div>
        </div>
    </div>
</div>
@endsection
