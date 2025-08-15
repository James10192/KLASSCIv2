@extends('layouts.app')

@section('title', 'Profil Parent')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-info">
                <h1 class="page-title">Mon Profil Parent</h1>
                <p class="page-description">Gérez vos informations personnelles et consultez les détails de vos enfants</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('parent.dashboard') }}" class="btn-acasi btn-acasi-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour au tableau de bord
                </a>
                <button type="button" class="btn-acasi btn-acasi-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit"></i>
                    Modifier mon profil
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
                        @if(isset($user->profile_image))
                            <img src="{{ asset('storage/' . $user->profile_image) }}" alt="Photo de profil" class="profile-photo">
                        @else
                            <div class="profile-photo profile-photo-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        @endif
                        <div class="profile-info-basic">
                            <h2 class="profile-name">{{ $user->name }}</h2>
                            <span class="status-badge status-badge-info">Parent</span>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Email</label>
                            <span>{{ $user->email }}</span>
                        </div>
                        <div class="info-item">
                            <label>Téléphone</label>
                            <span>{{ $user->phone ?? 'Non renseigné' }}</span>
                        </div>
                        <div class="info-item">
                            <label>Membre depuis</label>
                            <span>{{ $user->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mes enfants -->
            <div class="main-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-child"></i>
                        Mes enfants
                    </h3>
                    <div class="card-actions">
                        <span class="status-badge status-badge-success">{{ $children->count() }} enfant(s)</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($children->count() > 0)
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Nom</th>
                                        <th>Classe</th>
                                        <th>Matricule</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($children as $child)
                                        <tr>
                                            <td>
                                                @if(isset($child->user->profile_image))
                                                    <img src="{{ asset('storage/' . $child->user->profile_image) }}" alt="Photo" class="profile-photo-sm">
                                                @else
                                                    <div class="profile-photo-sm profile-photo-placeholder">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="fw-medium">{{ $child->user->name }}</td>
                                            <td>{{ $child->class->name ?? 'Non assignée' }}</td>
                                            <td><span class="status-badge status-badge-secondary">{{ $child->registration_number }}</span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('parent.child.grades', $child->id) }}" class="btn-acasi btn-acasi-sm btn-acasi-primary" title="Notes">
                                                        <i class="fas fa-graduation-cap"></i>
                                                    </a>
                                                    <a href="{{ route('parent.child.attendance', $child->id) }}" class="btn-acasi btn-acasi-sm btn-acasi-info" title="Présences">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </a>
                                                    <a href="{{ route('parent.child.timetable', $child->id) }}" class="btn-acasi btn-acasi-sm btn-acasi-success" title="Emploi du temps">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-child"></i>
                            </div>
                            <h3 class="empty-state-title">Aucun enfant associé</h3>
                            <p class="empty-state-description">Veuillez contacter l'administration pour associer vos enfants à votre compte.</p>
                            <button type="button" class="btn-acasi btn-acasi-primary" data-bs-toggle="modal" data-bs-target="#contactAdminModal">
                                <i class="fas fa-envelope"></i>
                                Contacter l'administration
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de modification du profil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Modifier mon profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ $user->phone }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Photo de profil</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image">
                        <small class="text-muted">Formats acceptés : JPG, PNG. Max 2MB.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi btn-acasi-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="editProfileModal" class="btn-acasi btn-acasi-primary">
                    <i class="fas fa-save"></i>
                    Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de contact avec l'administration -->
<div class="modal fade" id="contactAdminModal" tabindex="-1" aria-labelledby="contactAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactAdminModalLabel">Contacter l'administration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="contactAdminForm" action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="recipient_type" value="admin">
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Sujet</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="Association d'enfants à mon compte parent" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Décrivez votre demande..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi btn-acasi-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="contactAdminForm" class="btn-acasi btn-acasi-primary">
                    <i class="fas fa-paper-plane"></i>
                    Envoyer le message
                </button>
            </div>
        </div>
    </div>
</div>
@endsection 