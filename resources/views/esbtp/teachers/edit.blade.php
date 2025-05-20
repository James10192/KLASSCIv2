@extends('layouts.app')

@section('title', 'Modifier l\'enseignant')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Modifier l'enseignant</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="{{ route('esbtp.teachers.index') }}">Enseignants</a></li>
        <li class="breadcrumb-item active">Modifier</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-edit me-1"></i>
            Informations de l'enseignant
        </div>
        <div class="card-body">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('esbtp.teachers.update', $teacher->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Informations de base utilisateur -->
                <h4 class="mb-3">Informations d'identification</h4>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $teacher->user->name) }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $teacher->user->email) }}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $teacher->user->username) }}" required>
                            @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $teacher->user->phone) }}">
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                            <small class="text-muted">Laissez vide pour conserver le mot de passe actuel</small>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $teacher->user->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Compte actif</label>
                </div>

                <hr>

                <!-- Informations professionnelles -->
                <h4 class="mb-3">Informations professionnelles</h4>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="employee_id" class="form-label">Numéro d'employé</label>
                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" value="{{ old('employee_id', $teacher->employee_id) }}">
                            @error('employee_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="department_id" class="form-label">Département <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                <option value="">Sélectionner un département</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $teacher->department_id) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="laboratory_id" class="form-label">Laboratoire</label>
                            <select class="form-select @error('laboratory_id') is-invalid @enderror" id="laboratory_id" name="laboratory_id">
                                <option value="">Sélectionner un laboratoire</option>
                                @foreach($laboratories as $laboratory)
                                    <option value="{{ $laboratory->id }}" {{ old('laboratory_id', $teacher->laboratory_id) == $laboratory->id ? 'selected' : '' }}>
                                        {{ $laboratory->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('laboratory_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <select class="form-select @error('grade') is-invalid @enderror" id="grade" name="grade">
                                <option value="">Sélectionner un grade</option>
                                <option value="Professeur" {{ old('grade', $teacher->grade) == 'Professeur' ? 'selected' : '' }}>Professeur</option>
                                <option value="Maître de conférences" {{ old('grade', $teacher->grade) == 'Maître de conférences' ? 'selected' : '' }}>Maître de conférences</option>
                                <option value="Assistant" {{ old('grade', $teacher->grade) == 'Assistant' ? 'selected' : '' }}>Assistant</option>
                                <option value="Vacataire" {{ old('grade', $teacher->grade) == 'Vacataire' ? 'selected' : '' }}>Vacataire</option>
                                <option value="Autre" {{ old('grade', $teacher->grade) == 'Autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                            @error('grade')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="">Sélectionner un statut</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $teacher->status) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="teaching_hours_due" class="form-label">Heures dues</label>
                                    <input type="number" class="form-control @error('teaching_hours_due') is-invalid @enderror" id="teaching_hours_due" name="teaching_hours_due" value="{{ old('teaching_hours_due', $teacher->teaching_hours_due) }}">
                                    @error('teaching_hours_due')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="teaching_hours_done" class="form-label">Heures effectuées</label>
                                    <input type="number" class="form-control @error('teaching_hours_done') is-invalid @enderror" id="teaching_hours_done" name="teaching_hours_done" value="{{ old('teaching_hours_done', $teacher->teaching_hours_done) }}">
                                    @error('teaching_hours_done')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="office_location" class="form-label">Emplacement du bureau</label>
                            <input type="text" class="form-control @error('office_location') is-invalid @enderror" id="office_location" name="office_location" value="{{ old('office_location', $teacher->office_location) }}">
                            @error('office_location')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="specialties" class="form-label">Spécialités (séparées par des virgules)</label>
                            <input type="text" class="form-control @error('specialties') is-invalid @enderror" id="specialties" name="specialties" value="{{ old('specialties', is_array($teacher->specialties) ? implode(', ', $teacher->specialties) : $teacher->specialties) }}">
                            @error('specialties')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-group mb-3">
                            <label for="bio" class="form-label">Biographie</label>
                            <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="3">{{ old('bio', $teacher->bio) }}</textarea>
                            @error('bio')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-group mb-3">
                            <label for="research_interests" class="form-label">Intérêts de recherche (séparés par des virgules)</label>
                            <input type="text" class="form-control @error('research_interests') is-invalid @enderror" id="research_interests" name="research_interests" value="{{ old('research_interests', is_array($teacher->research_interests) ? implode(', ', $teacher->research_interests) : $teacher->research_interests) }}">
                            @error('research_interests')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="website" class="form-label">Site web</label>
                            <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website', $teacher->website) }}">
                            @error('website')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="{{ route('esbtp.teachers.index') }}" class="btn btn-secondary me-md-2">Annuler</a>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // JavaScript for form validation
    (function() {
        'use strict';

        // Fetch all the forms we want to apply custom validation styles to
        const forms = document.querySelectorAll('.needs-validation');

        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            }, false);
        });

        // Handle department selection affecting laboratories
        const departmentSelect = document.getElementById('department_id');
        const laboratorySelect = document.getElementById('laboratory_id');

        if (departmentSelect && laboratorySelect) {
            departmentSelect.addEventListener('change', function() {
                const departmentId = this.value;

                // Clear current options
                laboratorySelect.innerHTML = '<option value="">Sélectionner un laboratoire</option>';

                if (departmentId) {
                    fetch(`/api/departments/${departmentId}/laboratories`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(lab => {
                                const option = document.createElement('option');
                                option.value = lab.id;
                                option.textContent = lab.name;
                                laboratorySelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Error fetching laboratories:', error));
                }
            });
        }
    })();
</script>
@endsection
