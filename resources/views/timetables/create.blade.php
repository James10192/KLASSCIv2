@extends('layouts.app')

@section('title', 'Ajouter un emploi du temps')

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
                                <h2 class="fw-bold mb-0">Créer un emploi du temps</h2>
                                <a href="{{ route('timetables.index') }}" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-arrow-left me-2"></i> Retour à la liste
                                </a>
                            </div>
                            <p class="text-muted mb-4">Créez un nouvel emploi du temps pour une classe en définissant les cours pour chaque jour de la semaine. Vous pourrez ajouter, modifier ou supprimer des créneaux horaires selon vos besoins.</p>
                            
                            <div class="d-flex gap-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-2">
                                        <i class="fas fa-chalkboard text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Sélection de la classe</h6>
                                        <small class="text-muted">Choisissez la classe concernée</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-success-light rounded-circle p-2 me-2">
                                        <i class="fas fa-calendar-alt text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Planification des cours</h6>
                                        <small class="text-muted">Organisez les cours de la semaine</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/schedule-concept-illustration_114360-1531.jpg" alt="Timetable Creation" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
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
                    <form action="{{ route('timetables.store') }}" method="POST">
                        @csrf
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-chalkboard text-muted"></i></span>
                                    <select class="form-select border-0 bg-light @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('class_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="section_id" class="form-label">Section</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-layer-group text-muted"></i></span>
                                    <select class="form-select border-0 bg-light @error('section_id') is-invalid @enderror" id="section_id" name="section_id">
                                        <option value="">Sélectionner une section</option>
                                        @if(old('class_id'))
                                            @foreach($sections as $section)
                                                <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>
                                                    {{ $section->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('section_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-header bg-light border-0">
                                <h5 class="mb-0 fw-bold">Configuration des jours et horaires</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label d-block">Jours de la semaine <span class="text-danger">*</span></label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'] as $day)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="days[]" id="day_{{ $loop->index }}" value="{{ $day }}" 
                                                        {{ (is_array(old('days')) && in_array($day, old('days'))) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="day_{{ $loop->index }}">{{ $day }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('days')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Heures de début et de fin <span class="text-danger">*</span></label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-0"><i class="fas fa-clock text-muted"></i></span>
                                                    <input type="time" class="form-control border-0 bg-light @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ old('start_time', '08:00') }}" required>
                                                </div>
                                                @error('start_time')
                                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-0"><i class="fas fa-clock text-muted"></i></span>
                                                    <input type="time" class="form-control border-0 bg-light @error('end_time') is-invalid @enderror" id="end_time" name="end_time" value="{{ old('end_time', '18:00') }}" required>
                                                </div>
                                                @error('end_time')
                                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="period_duration" class="form-label">Durée d'une période (minutes) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-hourglass-half text-muted"></i></span>
                                            <input type="number" class="form-control border-0 bg-light @error('period_duration') is-invalid @enderror" id="period_duration" name="period_duration" value="{{ old('period_duration', 60) }}" min="30" max="120" step="5" required>
                                            <span class="input-group-text bg-light border-0">minutes</span>
                                        </div>
                                        @error('period_duration')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="break_duration" class="form-label">Durée de la pause (minutes)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-coffee text-muted"></i></span>
                                            <input type="number" class="form-control border-0 bg-light @error('break_duration') is-invalid @enderror" id="break_duration" name="break_duration" value="{{ old('break_duration', 15) }}" min="0" max="60" step="5">
                                            <span class="input-group-text bg-light border-0">minutes</span>
                                        </div>
                                        @error('break_duration')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="break_after_period" class="form-label">Pause après combien de périodes?</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fas fa-list-ol text-muted"></i></span>
                                            <input type="number" class="form-control border-0 bg-light @error('break_after_period') is-invalid @enderror" id="break_after_period" name="break_after_period" value="{{ old('break_after_period', 2) }}" min="1" max="5" step="1">
                                            <span class="input-group-text bg-light border-0">périodes</span>
                                        </div>
                                        @error('break_after_period')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">Matières disponibles</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllSubjects">
                                    <i class="fas fa-check-square me-1"></i> Tout sélectionner
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach($subjects as $subject)
                                        <div class="col-md-4 col-lg-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input subject-checkbox" type="checkbox" name="subjects[]" id="subject_{{ $subject->id }}" value="{{ $subject->id }}" 
                                                    {{ (is_array(old('subjects')) && in_array($subject->id, old('subjects'))) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="subject_{{ $subject->id }}">
                                                    {{ $subject->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('subjects')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">Enseignants disponibles</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllTeachers">
                                    <i class="fas fa-check-square me-1"></i> Tout sélectionner
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach($teachers as $teacher)
                                        <div class="col-md-4 col-lg-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input teacher-checkbox" type="checkbox" name="teachers[]" id="teacher_{{ $teacher->id }}" value="{{ $teacher->id }}" 
                                                    {{ (is_array(old('teachers')) && in_array($teacher->id, old('teachers'))) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="teacher_{{ $teacher->id }}">
                                                    {{ $teacher->user->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('teachers')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary px-4" onclick="window.history.back()">
                                <i class="fas fa-times me-2"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-calendar-plus me-2"></i> Créer l'emploi du temps
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
    
    /* Style pour les formulaires */
    .form-control:focus, .form-select:focus {
        border-color: var(--esbtp-green);
        box-shadow: 0 0 0 0.25rem rgba(var(--esbtp-green-rgb), 0.25);
    }
    
    .input-group-text {
        color: #6c757d;
    }
    
    /* Style pour les checkboxes */
    .form-check-input:checked {
        background-color: var(--esbtp-green);
        border-color: var(--esbtp-green);
    }
    
    /* Animation pour les transitions */
    .fade-in {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('class_id');
        const sectionSelect = document.getElementById('section_id');
        
        if (classSelect && sectionSelect) {
            classSelect.addEventListener('change', function() {
                const classId = this.value;
                
                // Réinitialiser le select des sections
                sectionSelect.innerHTML = '<option value="">Sélectionner une section</option>';
                
                if (classId) {
                    // Charger les sections pour cette classe
                    fetch(`/api/sections/by-class/${classId}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section.id;
                                option.textContent = section.name;
                                sectionSelect.appendChild(option);
                            });
                        })
                        .catch(error => debugError('Erreur:', error));
                }
            });
            
            // Déclencher l'événement au chargement si une classe est déjà sélectionnée
            if (classSelect.value) {
                classSelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Sélectionner/désélectionner toutes les matières
        const selectAllSubjectsBtn = document.getElementById('selectAllSubjects');
        const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');
        
        if (selectAllSubjectsBtn) {
            selectAllSubjectsBtn.addEventListener('click', function() {
                const allChecked = Array.from(subjectCheckboxes).every(checkbox => checkbox.checked);
                
                subjectCheckboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                });
                
                this.innerHTML = allChecked ? 
                    '<i class="fas fa-check-square me-1"></i> Tout sélectionner' : 
                    '<i class="fas fa-square me-1"></i> Tout désélectionner';
            });
        }
        
        // Sélectionner/désélectionner tous les enseignants
        const selectAllTeachersBtn = document.getElementById('selectAllTeachers');
        const teacherCheckboxes = document.querySelectorAll('.teacher-checkbox');
        
        if (selectAllTeachersBtn) {
            selectAllTeachersBtn.addEventListener('click', function() {
                const allChecked = Array.from(teacherCheckboxes).every(checkbox => checkbox.checked);
                
                teacherCheckboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                });
                
                this.innerHTML = allChecked ? 
                    '<i class="fas fa-check-square me-1"></i> Tout sélectionner' : 
                    '<i class="fas fa-square me-1"></i> Tout désélectionner';
            });
        }
    });
</script>
@endsection 