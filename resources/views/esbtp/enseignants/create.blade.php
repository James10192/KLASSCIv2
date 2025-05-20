@extends('layouts.app')

@section('title', 'Ajouter un enseignant')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Ajouter un nouvel enseignant</h5>
            <a href="{{ route('esbtp.enseignants.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
            </a>
        </div>
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <form action="{{ route('esbtp.enseignants.store') }}" method="POST">
                @csrf

                <!-- Informations personnelles -->
                <h6 class="mb-3">Informations personnelles</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="firstname" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('firstname') is-invalid @enderror" id="firstname" name="firstname" value="{{ old('firstname') }}" required>
                        @error('firstname')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="lastname" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('lastname') is-invalid @enderror" id="lastname" name="lastname" value="{{ old('lastname') }}" required>
                        @error('lastname')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Informations de connexion -->
                <h6 class="mb-3">Informations de connexion</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required>
                        @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Minimum 8 caractères</small>
                    </div>

                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Numéro d'employé</label>
                        <input type="text" class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" value="{{ old('employee_id') }}">
                        @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Informations académiques -->
                <h6 class="mb-3">Informations académiques</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="department_id" class="form-label">Département</label>
                        <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                            <option value="">Sélectionner...</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('department_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="laboratory_id" class="form-label">Laboratoire</label>
                        <select class="form-select @error('laboratory_id') is-invalid @enderror" id="laboratory_id" name="laboratory_id">
                            <option value="">Sélectionner...</option>
                            @foreach($laboratories as $laboratory)
                            <option value="{{ $laboratory->id }}" {{ old('laboratory_id') == $laboratory->id ? 'selected' : '' }}>
                                {{ $laboratory->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('laboratory_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="grade" class="form-label">Grade</label>
                        <select class="form-select @error('grade') is-invalid @enderror" id="grade" name="grade">
                            <option value="">Sélectionner...</option>
                            <option value="Professeur" {{ old('grade') == 'Professeur' ? 'selected' : '' }}>Professeur</option>
                            <option value="Maître de conférences" {{ old('grade') == 'Maître de conférences' ? 'selected' : '' }}>Maître de conférences</option>
                            <option value="Assistant" {{ old('grade') == 'Assistant' ? 'selected' : '' }}>Assistant</option>
                            <option value="Vacataire" {{ old('grade') == 'Vacataire' ? 'selected' : '' }}>Vacataire</option>
                        </select>
                        @error('grade')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="">Sélectionner...</option>
                            <option value="PRAG" {{ old('status') == 'PRAG' ? 'selected' : '' }}>PRAG</option>
                            <option value="MCF" {{ old('status') == 'MCF' ? 'selected' : '' }}>MCF</option>
                            <option value="PR" {{ old('status') == 'PR' ? 'selected' : '' }}>PR</option>
                            <option value="ATER" {{ old('status') == 'ATER' ? 'selected' : '' }}>ATER</option>
                            <option value="Vacataire" {{ old('status') == 'Vacataire' ? 'selected' : '' }}>Vacataire</option>
                        </select>
                        @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="teaching_hours_due" class="form-label">Heures d'enseignement dues</label>
                        <input type="number" class="form-control @error('teaching_hours_due') is-invalid @enderror" id="teaching_hours_due" name="teaching_hours_due" value="{{ old('teaching_hours_due', 0) }}" min="0">
                        @error('teaching_hours_due')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="office_location" class="form-label">Bureau</label>
                        <input type="text" class="form-control @error('office_location') is-invalid @enderror" id="office_location" name="office_location" value="{{ old('office_location') }}">
                        @error('office_location')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Spécialités et recherche -->
                <h6 class="mb-3">Spécialités et recherche</h6>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="specialties" class="form-label">Spécialités</label>
                        <input type="text" class="form-control @error('specialties') is-invalid @enderror" id="specialties" name="specialties" value="{{ old('specialties') }}" data-role="tagsinput">
                        @error('specialties')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Séparez les spécialités par des virgules</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="bio" class="form-label">Biographie</label>
                        <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="3">{{ old('bio') }}</textarea>
                        @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="research_interests" class="form-label">Intérêts de recherche</label>
                        <input type="text" class="form-control @error('research_interests') is-invalid @enderror" id="research_interests" name="research_interests" value="{{ old('research_interests') }}" data-role="tagsinput">
                        @error('research_interests')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Séparez les intérêts par des virgules</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="website" class="form-label">Site web</label>
                        <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website') }}">
                        @error('website')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                    <a href="{{ route('esbtp.enseignants.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.7.1/dist/bootstrap-tagsinput.css" rel="stylesheet">
<style>
    .bootstrap-tagsinput {
        width: 100%;
    }
    .bootstrap-tagsinput .tag {
        margin-right: 2px;
        color: white !important;
        background-color: #0d6efd;
        padding: 0.2rem 0.6rem;
        border-radius: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.7.1/dist/bootstrap-tagsinput.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Select2 if available
        if (typeof $.fn.select2 !== 'undefined') {
            $('#department_id, #laboratory_id, #grade, #status').select2({
                placeholder: 'Sélectionner...',
                allowClear: true
            });
        }

        // Initialize TagsInput
        $('#specialties, #research_interests').tagsinput({
            trimValue: true,
            confirmKeys: [13, 44], // Enter and comma
            tagClass: 'badge bg-primary'
        });
    });
</script>
@endpush
