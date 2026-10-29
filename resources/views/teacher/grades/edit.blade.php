@extends('layouts.app')

@section('title', 'Saisie des notes - {{ $evaluation->title }}')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-white mb-0">Saisie des notes - {{ $evaluation->title }}</h6>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('grades.index') }}" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
                            <span class="alert-text">{{ session('success') }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span>
                            <span class="alert-text">{{ session('error') }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations de l'évaluation</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Classe:</strong></p>
                                            <p class="text-muted">{{ $evaluation->class->nom }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Matière:</strong></p>
                                            <p class="text-muted">{{ $evaluation->subject->nom }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Type:</strong></p>
                                            <p class="text-muted">{{ ucfirst($evaluation->type) }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Date:</strong></p>
                                            <p class="text-muted">{{ date('d/m/Y', strtotime($evaluation->date)) }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Semestre:</strong></p>
                                            <p class="text-muted">Semestre {{ $evaluation->semester }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Barème:</strong></p>
                                            <p class="text-muted">{{ $evaluation->total_points }} points</p>
                                        </div>
                                    </div>
                                    @if($evaluation->description)
                                        <div class="row">
                                            <div class="col-12">
                                                <p class="mb-1"><strong>Description:</strong></p>
                                                <p class="text-muted">{{ $evaluation->description }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Récapitulatif</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-3">
                                            <div class="info-box text-center">
                                                <div class="icon-box rounded-circle bg-primary mb-1">
                                                    <i class="fas fa-users text-white"></i>
                                                </div>
                                                <h5 class="font-weight-bold mb-0">{{ count($students) }}</h5>
                                                <p class="text-xs text-muted mb-0">Étudiants</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box text-center">
                                                <div class="icon-box rounded-circle bg-success mb-1">
                                                    <i class="fas fa-check-circle text-white"></i>
                                                </div>
                                                <h5 class="font-weight-bold mb-0">{{ $gradesCount }}</h5>
                                                <p class="text-xs text-muted mb-0">Notes saisies</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box text-center">
                                                <div class="icon-box rounded-circle bg-info mb-1">
                                                    <i class="fas fa-calculator text-white"></i>
                                                </div>
                                                <h5 class="font-weight-bold mb-0">{{ $averageGrade ? number_format($averageGrade, 2) : '0.00' }}</h5>
                                                <p class="text-xs text-muted mb-0">Moyenne</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box text-center">
                                                <div class="icon-box rounded-circle bg-warning mb-1">
                                                    <i class="fas fa-percent text-white"></i>
                                                </div>
                                                <h5 class="font-weight-bold mb-0">{{ $gradesCount > 0 ? round(($gradesCount / count($students)) * 100) : 0 }}%</h5>
                                                <p class="text-xs text-muted mb-0">Complété</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-wrapper">
                                        <div class="progress mb-3">
                                            <div class="progress-bar bg-gradient-success" role="progressbar" 
                                                 aria-valuenow="{{ $gradesCount > 0 ? round(($gradesCount / count($students)) * 100) : 0 }}" 
                                                 aria-valuemin="0" aria-valuemax="100" 
                                                 style="width: {{ $gradesCount > 0 ? round(($gradesCount / count($students)) * 100) : 0 }}%;">
                                                {{ $gradesCount > 0 ? round(($gradesCount / count($students)) * 100) : 0 }}%
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="statistic-card border p-3 rounded">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="text-uppercase text-muted text-xs mb-0">Note minimale</h6>
                                                        <h4 class="font-weight-bolder mb-0">{{ $minGrade ?? 'N/A' }}</h4>
                                                    </div>
                                                    <div class="icon bg-danger-light text-danger rounded-circle">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="statistic-card border p-3 rounded">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="text-uppercase text-muted text-xs mb-0">Note maximale</h6>
                                                        <h4 class="font-weight-bolder mb-0">{{ $maxGrade ?? 'N/A' }}</h4>
                                                    </div>
                                                    <div class="icon bg-success-light text-success rounded-circle">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form action="{{ route('grades.update', $evaluation->id) }}" method="POST" id="gradesForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="card border">
                            <div class="card-header bg-light">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-0">Saisie des notes ({{ count($students) }} étudiants)</h6>
                                    </div>
                                    <div class="col-auto">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-secondary" id="resetAllBtn" title="Réinitialiser toutes les notes">
                                                <i class="fas fa-redo me-1"></i> Réinitialiser
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" id="fillEmptyBtn" title="Remplir les notes vides avec une valeur par défaut">
                                                <i class="fas fa-magic me-1"></i> Remplir vides
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" id="markAllPresentBtn" title="Marquer tous les étudiants comme présents">
                                                <i class="fas fa-user-check me-1"></i> Tous présents
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">N°</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Étudiant</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Numéro d'inscription</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 150px;">Note / {{ $evaluation->total_points }}</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Commentaire (optionnel)</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center" style="width: 100px;">Absence</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($students as $index => $student)
                                                <tr class="{{ isset($studentAbsences[$student->id]) && $studentAbsences[$student->id] ? 'table-secondary' : '' }}">
                                                    <td class="ps-3">
                                                        <p class="text-xs font-weight-bold mb-0">{{ $index + 1 }}</p>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex px-2 py-1">
                                                            <div>
                                                                @if($student->user->profile_photo_path)
                                                                    <img src="{{ Storage::url($student->user->profile_photo_path) }}" class="avatar avatar-sm me-3">
                                                                @else
                                                                    <div class="avatar avatar-sm bg-gradient-secondary me-3">
                                                                        {{ strtoupper(substr($student->user->name, 0, 1)) }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="d-flex flex-column justify-content-center">
                                                                <h6 class="mb-0 text-sm">{{ $student->user->name }}</h6>
                                                                <p class="text-xs text-secondary mb-0">{{ $student->user->email }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0">{{ $student->registration_number }}</p>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                               class="form-control form-control-sm grade-input @error('grades.' . $student->id) is-invalid @enderror"
                                                               name="grades[{{ $student->id }}]"
                                                               id="grade_{{ $student->id }}"
                                                               value="{{ old('grades.' . $student->id, $studentGrades[$student->id] ?? '') }}"
                                                               min="0"
                                                               max="{{ $evaluation->total_points }}"
                                                               step="0.01"
                                                               inputmode="decimal"
                                                               lang="fr"
                                                               {{ isset($studentAbsences[$student->id]) && $studentAbsences[$student->id] ? 'disabled' : '' }}>
                                                        @error('grades.' . $student->id)
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td>
                                                        <input type="text" 
                                                               class="form-control form-control-sm comment-input" 
                                                               name="comments[{{ $student->id }}]" 
                                                               id="comment_{{ $student->id }}" 
                                                               value="{{ old('comments.' . $student->id, $studentComments[$student->id] ?? '') }}" 
                                                               placeholder="Commentaire optionnel" 
                                                               maxlength="255" 
                                                               {{ isset($studentAbsences[$student->id]) && $studentAbsences[$student->id] ? 'disabled' : '' }}>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-flex justify-content-center">
                                                            <input class="form-check-input absence-toggle" 
                                                                   type="checkbox" 
                                                                   role="switch" 
                                                                   id="absence_{{ $student->id }}" 
                                                                   name="absences[{{ $student->id }}]" 
                                                                   value="1" 
                                                                   data-student-id="{{ $student->id }}"
                                                                   {{ isset($studentAbsences[$student->id]) && $studentAbsences[$student->id] ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i> Les notes sont enregistrées sur {{ $evaluation->total_points }} points
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Enregistrer les notes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle absences
        const absenceToggles = document.querySelectorAll('.absence-toggle');
        absenceToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const studentId = this.getAttribute('data-student-id');
                const gradeInput = document.getElementById('grade_' + studentId);
                const commentInput = document.getElementById('comment_' + studentId);
                const row = this.closest('tr');
                
                if (this.checked) {
                    // Student is absent
                    gradeInput.disabled = true;
                    gradeInput.value = '';
                    commentInput.disabled = true;
                    if (commentInput.value === '') {
                        commentInput.value = 'Absent';
                    }
                    row.classList.add('table-secondary');
                } else {
                    // Student is present
                    gradeInput.disabled = false;
                    commentInput.disabled = false;
                    if (commentInput.value === 'Absent') {
                        commentInput.value = '';
                    }
                    row.classList.remove('table-secondary');
                }
            });
        });
        
        // Reset all grades
        document.getElementById('resetAllBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Êtes-vous sûr de vouloir réinitialiser toutes les notes ?')) {
                const gradeInputs = document.querySelectorAll('.grade-input:not(:disabled)');
                gradeInputs.forEach(input => {
                    input.value = '';
                });
                
                const commentInputs = document.querySelectorAll('.comment-input:not(:disabled)');
                commentInputs.forEach(input => {
                    if (input.value !== 'Absent') {
                        input.value = '';
                    }
                });
            }
        });
        
        // Fill empty grades
        document.getElementById('fillEmptyBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const defaultGrade = prompt('Veuillez entrer la note à appliquer aux champs vides (max: ' + {{ $evaluation->total_points }} + ')');
            
            if (defaultGrade !== null) {
                const numericGrade = parseFloat(defaultGrade);
                
                if (isNaN(numericGrade)) {
                    alert('Veuillez entrer une valeur numérique.');
                    return;
                }
                
                if (numericGrade < 0 || numericGrade > {{ $evaluation->total_points }}) {
                    alert('La note doit être comprise entre 0 et ' + {{ $evaluation->total_points }});
                    return;
                }
                
                const emptyGradeInputs = document.querySelectorAll('.grade-input:not(:disabled)');
                emptyGradeInputs.forEach(input => {
                    if (input.value === '') {
                        input.value = numericGrade;
                    }
                });
            }
        });
        
        // Mark all students as present
        document.getElementById('markAllPresentBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Voulez-vous marquer tous les étudiants comme présents ?')) {
                const absenceToggles = document.querySelectorAll('.absence-toggle');
                absenceToggles.forEach(toggle => {
                    if (toggle.checked) {
                        toggle.checked = false;
                        
                        // Trigger the change event to update UI
                        const event = new Event('change');
                        toggle.dispatchEvent(event);
                    }
                });
            }
        });
        
        // Navigate between inputs with arrow keys
        const inputs = document.querySelectorAll('input[type="number"], input[type="text"]');
        inputs.forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowDown' || e.key === 'Enter') {
                    e.preventDefault();
                    const currentRow = this.closest('tr');
                    const nextRow = currentRow.nextElementSibling;
                    if (nextRow) {
                        const nextInput = nextRow.querySelector(this.tagName);
                        if (nextInput && !nextInput.disabled) {
                            nextInput.focus();
                            nextInput.select();
                        }
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const currentRow = this.closest('tr');
                    const prevRow = currentRow.previousElementSibling;
                    if (prevRow) {
                        const prevInput = prevRow.querySelector(this.tagName);
                        if (prevInput && !prevInput.disabled) {
                            prevInput.focus();
                            prevInput.select();
                        }
                    }
                }
            });
        });
        
        // Form validation before submit
        document.getElementById('gradesForm').addEventListener('submit', function(e) {
            const gradeInputs = document.querySelectorAll('.grade-input:not(:disabled)');
            let hasError = false;
            
            gradeInputs.forEach(input => {
                if (input.value !== '') {
                    const value = parseFloat(input.value);
                    if (value < 0 || value > {{ $evaluation->total_points }}) {
                        input.classList.add('is-invalid');
                        hasError = true;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                }
            });
            
            if (hasError) {
                e.preventDefault();
                alert('Certaines notes sont invalides. Veuillez vérifier que toutes les notes sont comprises entre 0 et ' + {{ $evaluation->total_points }});
            }
        });
    });
</script>

<style>
    .icon-box {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .statistic-card {
        transition: all 0.3s ease;
    }
    
    .statistic-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .statistic-card .icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .bg-danger-light {
        background-color: rgba(253, 57, 57, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(20, 164, 77, 0.1);
    }
    
    .table > tbody > tr.table-secondary {
        --bs-table-bg: rgba(233, 236, 239, 0.6);
    }
    
    .grade-input:focus {
        background-color: #fff8e0;
    }
</style>
@endsection 