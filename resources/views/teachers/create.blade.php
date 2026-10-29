@extends('layouts.app')

@section('title', 'Ajouter un Enseignant')

@section('content')
<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-8 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="fw-bold mb-0">Ajouter un nouvel enseignant</h2>
                                <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-arrow-left me-2"></i> Retour à la liste
                                </a>
                            </div>
                            <p class="text-muted mb-4">Remplissez le formulaire ci-dessous pour ajouter un nouvel enseignant à l'établissement. Tous les champs marqués d'un astérisque (*) sont obligatoires.</p>
                            
                            <div class="d-flex gap-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-2">
                                        <i class="fas fa-user-tie text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Informations personnelles</h6>
                                        <small class="text-muted">Identité et contact</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-success-light rounded-circle p-2 me-2">
                                        <i class="fas fa-chalkboard-teacher text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Informations professionnelles</h6>
                                        <small class="text-muted">Matières et spécialités</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/teacher-concept-illustration_114360-2166.jpg" alt="Add Teacher" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Veuillez corriger les erreurs suivantes :</strong>
            </div>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <form action="{{ route('teachers.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Formulaire en onglets -->
                        <ul class="nav nav-tabs nav-fill mb-4" id="teacherTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active px-4 py-3" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                                    <i class="fas fa-user me-2"></i>Informations Personnelles
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-4 py-3" id="professional-tab" data-bs-toggle="tab" data-bs-target="#professional" type="button" role="tab" aria-controls="professional" aria-selected="false">
                                    <i class="fas fa-briefcase me-2"></i>Informations Professionnelles
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-3" id="teacherTabsContent">
                            <!-- Onglet Informations Personnelles -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-user text-muted"></i></span>
                                            <input type="text" class="form-control border-0 bg-light @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                        </div>
                                        @error('name')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-envelope text-muted"></i></span>
                                            <input type="email" class="form-control border-0 bg-light @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                        </div>
                                        @error('email')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                                            <input type="password" class="form-control border-0 bg-light @error('password') is-invalid @enderror" id="password" name="password" required>
                                        </div>
                                        @error('password')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                                            <input type="password" class="form-control border-0 bg-light" id="password_confirmation" name="password_confirmation" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Téléphone</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-phone text-muted"></i></span>
                                            <input type="text" class="form-control border-0 bg-light @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                        </div>
                                        @error('phone')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="date_of_birth" class="form-label">Date de naissance</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-calendar-alt text-muted"></i></span>
                                            <input type="date" class="form-control border-0 bg-light @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                                        </div>
                                        @error('date_of_birth')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label">Genre</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-venus-mars text-muted"></i></span>
                                            <select class="form-select border-0 bg-light @error('gender') is-invalid @enderror" id="gender" name="gender">
                                                <option value="">Sélectionner</option>
                                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Masculin</option>
                                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Féminin</option>
                                            </select>
                                        </div>
                                        @error('gender')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Adresse</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-map-marker-alt text-muted"></i></span>
                                            <input type="text" class="form-control border-0 bg-light @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}">
                                        </div>
                                        @error('address')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="profile_image" class="form-label">Photo de profil</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-image text-muted"></i></span>
                                            <input type="file" class="form-control border-0 bg-light @error('profile_image') is-invalid @enderror" id="profile_image" name="profile_image">
                                        </div>
                                        <div class="form-text">Formats acceptés : JPG, PNG. Taille max : 2MB</div>
                                        @error('profile_image')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Onglet Informations Professionnelles -->
                            <div class="tab-pane fade" id="professional" role="tabpanel" aria-labelledby="professional-tab">
                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="employee_id" class="form-label">Numéro d'employé <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-id-card text-muted"></i></span>
                                            <input type="text" class="form-control border-0 bg-light @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" value="{{ old('employee_id') }}" required>
                                        </div>
                                        @error('employee_id')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="joining_date" class="form-label">Date d'embauche</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-calendar-check text-muted"></i></span>
                                            <input type="date" class="form-control border-0 bg-light @error('joining_date') is-invalid @enderror" id="joining_date" name="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}">
                                        </div>
                                        @error('joining_date')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="qualification" class="form-label">Qualification</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-graduation-cap text-muted"></i></span>
                                            <input type="text" class="form-control border-0 bg-light @error('qualification') is-invalid @enderror" id="qualification" name="qualification" value="{{ old('qualification') }}">
                                        </div>
                                        @error('qualification')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="experience" class="form-label">Expérience (années)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-briefcase text-muted"></i></span>
                                            <input type="number" class="form-control border-0 bg-light @error('experience') is-invalid @enderror" id="experience" name="experience" value="{{ old('experience') }}" min="0">
                                        </div>
                                        @error('experience')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Matières enseignées</label>
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    @foreach($subjects ?? [] as $subject)
                                                        <div class="col-md-4 col-lg-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="subjects[]" value="{{ $subject->id }}" id="subject_{{ $subject->id }}" 
                                                                    {{ (is_array(old('subjects')) && in_array($subject->id, old('subjects'))) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="subject_{{ $subject->id }}">
                                                                    {{ $subject->name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        @error('subjects')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="bio" class="form-label">Biographie</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-info-circle text-muted"></i></span>
                                            <textarea class="form-control border-0 bg-light @error('bio') is-invalid @enderror" id="bio" name="bio" rows="4">{{ old('bio') }}</textarea>
                                        </div>
                                        @error('bio')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary px-4" onclick="window.history.back()">
                                <i class="fas fa-times me-2"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles pour les badges et icônes */
    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .icon-box {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Style pour les onglets */
    .nav-tabs {
        border-bottom: 1px solid #e0e0e0;
    }
    
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover {
        color: var(--esbtp-green);
        border: none;
    }
    
    .nav-tabs .nav-link.active {
        color: var(--esbtp-green);
        background-color: transparent;
        border: none;
    }
    
    .nav-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: var(--esbtp-green);
        border-radius: 3px 3px 0 0;
    }
    
    /* Style pour les formulaires */
    .form-control:focus, .form-select:focus {
        border-color: var(--esbtp-green);
        box-shadow: 0 0 0 0.25rem rgba(var(--esbtp-green-rgb), 0.25);
    }
    
    .input-group-text {
        color: #6c757d;
    }
    
    /* Animation pour les transitions d'onglets */
    .tab-pane {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Style pour les checkboxes */
    .form-check-input:checked {
        background-color: var(--esbtp-green);
        border-color: var(--esbtp-green);
    }
</style>
@endsection 