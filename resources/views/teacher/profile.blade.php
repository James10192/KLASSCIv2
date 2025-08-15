@extends('layouts.app')

@section('title', 'Mon Profil - Enseignant')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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
                        <img class="rounded-circle mb-3" 
                             src="{{ $teacher->user->profile_image ? asset('storage/'.$teacher->user->profile_image) : asset('images/default-avatar.png') }}" 
                             alt="Photo de profil" style="width: 120px; height: 120px; object-fit: cover;">
                        <h5 class="my-3">{{ $teacher->user->name }}</h5>
                        <p class="text-muted mb-1">{{ $teacher->user->email }}</p>
                        <p class="text-muted mb-1">ID: {{ $teacher->employee_id }}</p>
                        <p class="text-muted mb-4">{{ $teacher->user->phone ?? 'Aucun numéro de téléphone' }}</p>
                        <button type="button" class="btn-acasi primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit"></i> Modifier le profil
                        </button>
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

<!-- Modal de modification du profil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST" onsubmit="alert('Fonctionnalité de modification en cours de développement'); return false;">
                @csrf
                @method('PUT')
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Modifier mon profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $teacher->user->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $teacher->user->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ $teacher->user->phone }}">
                    </div>
                    
                    <hr>
                    <h6>Informations professionnelles</h6>
                    <div class="mb-3">
                        <label for="qualification" class="form-label">Qualification</label>
                        <input type="text" class="form-control" id="qualification" name="qualification" value="{{ $teacher->qualification }}">
                    </div>
                    <div class="mb-3">
                        <label for="experience" class="form-label">Expérience</label>
                        <input type="text" class="form-control" id="experience" name="experience" value="{{ $teacher->experience }}">
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

@endsection 