@extends('layouts.app')

@section('title', 'Saisie des Notes')

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
                                <h2 class="fw-bold mb-0">Saisie des notes</h2>
                                <a href="{{ route('grades.index') }}" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-arrow-left me-2"></i> Retour à la liste
                                </a>
                            </div>
                            <p class="text-muted mb-4">Sélectionnez une classe, une section et une matière pour saisir les notes des étudiants. Vous pourrez ensuite attribuer des notes pour chaque étudiant de la classe sélectionnée.</p>
                            
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
                                        <i class="fas fa-book text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Sélection de la matière</h6>
                                        <small class="text-muted">Choisissez la matière à évaluer</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/grades-concept-illustration_114360-5958.jpg" alt="Grade Entry" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <strong>{{ session('success') }}</strong>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                    <form id="selectionForm" action="{{ route('grades.create') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-md-4 mb-3">
                                <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-chalkboard text-muted"></i></span>
                                    <select class="form-select border-0 bg-light @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('class_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="section_id" class="form-label">Section <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-layer-group text-muted"></i></span>
                                    <select class="form-select border-0 bg-light @error('section_id') is-invalid @enderror" id="section_id" name="section_id" required>
                                        <option value="">Sélectionner une section</option>
                                        @if(request('class_id') && $sections)
                                            @foreach($sections as $section)
                                                <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
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
                            
                            <div class="col-md-4 mb-3">
                                <label for="subject_id" class="form-label">Matière <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-book text-muted"></i></span>
                                    <select class="form-select border-0 bg-light @error('subject_id') is-invalid @enderror" id="subject_id" name="subject_id" required>
                                        <option value="">Sélectionner une matière</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('subject_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="exam_id" class="form-label">Examen <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-file-alt text-muted"></i></span>
                                    <select class="form-select border-0 bg-light @error('exam_id') is-invalid @enderror" id="exam_id" name="exam_id" required>
                                        <option value="">Sélectionner un examen</option>
                                        @foreach($exams as $exam)
                                            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                                                {{ $exam->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('exam_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="semester_id" class="form-label">Semestre <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="fas fa-calendar-alt text-muted"></i></span>
                                    <select class="form-select border-0 bg-light @error('semester_id') is-invalid @enderror" id="semester_id" name="semester_id" required>
                                        <option value="">Sélectionner un semestre</option>
                                        @foreach($semesters as $semester)
                                            <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>
                                                {{ $semester->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('semester_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 d-flex align-items-end mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i> Afficher les étudiants
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($students) && count($students) > 0)
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-header bg-white p-4 border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-user-graduate me-2 text-primary"></i>
                                Liste des étudiants - {{ $selectedClass->name ?? '' }} {{ $selectedSection->name ?? '' }}
                            </h5>
                            <div class="text-muted">
                                <span class="badge bg-primary-light text-primary">{{ count($students) }} étudiants</span>
                                <span class="badge bg-success-light text-success ms-2">{{ $selectedSubject->name ?? '' }}</span>
                                <span class="badge bg-warning-light text-warning ms-2">{{ $selectedExam->name ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('grades.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                            <input type="hidden" name="section_id" value="{{ request('section_id') }}">
                            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                            <input type="hidden" name="exam_id" value="{{ request('exam_id') }}">
                            <input type="hidden" name="semester_id" value="{{ request('semester_id') }}">
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="fw-semibold">#</th>
                                            <th scope="col" class="fw-semibold">Nom de l'étudiant</th>
                                            <th scope="col" class="fw-semibold">Note (sur {{ $selectedSubject->full_mark ?? 100 }})</th>
                                            <th scope="col" class="fw-semibold">Présence</th>
                                            <th scope="col" class="fw-semibold">Commentaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $key => $student)
                                            <tr class="grade-row">
                                                <td>{{ $key + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-3 bg-light rounded-circle d-flex align-items-center justify-content-center">
                                                            @if($student->user->profile_image)
                                                                <img src="{{ asset('storage/' . $student->user->profile_image) }}" alt="{{ $student->user->name }}" class="rounded-circle" width="40" height="40">
                                                            @else
                                                                <span class="text-primary fw-bold">{{ substr($student->user->name, 0, 2) }}</span>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ $student->user->name }}</h6>
                                                            <small class="text-muted">{{ $student->registration_number }}</small>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control border-0 bg-light mark-input" 
                                                            name="marks[]" 
                                                            min="0" 
                                                            max="{{ $selectedSubject->full_mark ?? 100 }}" 
                                                            value="{{ $existingGrades[$student->id] ?? '' }}"
                                                            placeholder="Note">
                                                        <span class="input-group-text bg-light border-0">/ {{ $selectedSubject->full_mark ?? 100 }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select class="form-select border-0 bg-light" name="attendance[]">
                                                        <option value="present" {{ isset($existingAttendance[$student->id]) && $existingAttendance[$student->id] == 'present' ? 'selected' : '' }}>Présent</option>
                                                        <option value="absent" {{ isset($existingAttendance[$student->id]) && $existingAttendance[$student->id] == 'absent' ? 'selected' : '' }}>Absent</option>
                                                        <option value="late" {{ isset($existingAttendance[$student->id]) && $existingAttendance[$student->id] == 'late' ? 'selected' : '' }}>En retard</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control border-0 bg-light" 
                                                        name="comments[]" 
                                                        value="{{ $existingComments[$student->id] ?? '' }}" 
                                                        placeholder="Commentaire">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-secondary px-4" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left me-2"></i> Retour
                                </button>
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="fas fa-save me-2"></i> Enregistrer les notes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
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
    
    /* Style pour les tableaux */
    .table th {
        font-weight: 600;
        color: #495057;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .grade-row:hover {
        background-color: rgba(var(--esbtp-green-rgb), 0.05);
    }
    
    /* Avatar */
    .avatar-sm {
        width: 40px;
        height: 40px;
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
        
        // Validation des notes
        const markInputs = document.querySelectorAll('.mark-input');
        markInputs.forEach(input => {
            input.addEventListener('input', function() {
                const max = parseInt(this.getAttribute('max'));
                if (this.value > max) {
                    this.value = max;
                }
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        });
    });
</script>
@endsection 