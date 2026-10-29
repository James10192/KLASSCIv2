@extends('layouts.app')

@section('title', 'Créer une évaluation')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-white mb-0">Créer une nouvelle évaluation</h6>
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
                    
                    <form action="{{ route('grades.store') }}" method="POST" id="createEvaluationForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <!-- General Information Card -->
                                <div class="card border mb-4">
                                    <div class="card-header bg-light py-3">
                                        <h6 class="mb-0 fw-bold">Informations générales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="title" class="form-label">Titre de l'évaluation <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                                                @error('title')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                                <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                                                    <option value="">Sélectionner une classe</option>
                                                    @foreach($classes as $classe)
                                                        <option value="{{ $classe->id }}" {{ old('class_id') == $classe->id ? 'selected' : '' }}>
                                                            {{ $classe->nom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('class_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="subject_id" class="form-label">Matière <span class="text-danger">*</span></label>
                                                <select class="form-select @error('subject_id') is-invalid @enderror" id="subject_id" name="subject_id" required>
                                                    <option value="">Sélectionner une matière</option>
                                                    @foreach($subjects as $subject)
                                                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                                            {{ $subject->nom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('subject_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="type" class="form-label">Type d'évaluation <span class="text-danger">*</span></label>
                                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                                    <option value="">Sélectionner un type</option>
                                                    @foreach($evaluationTypes as $value => $label)
                                                        <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="date" class="form-label">Date de l'évaluation <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                                @error('date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="semester" class="form-label">Semestre <span class="text-danger">*</span></label>
                                                <select class="form-select @error('semester') is-invalid @enderror" id="semester" name="semester" required>
                                                    <option value="">Sélectionner un semestre</option>
                                                    <option value="1" {{ old('semester') == '1' ? 'selected' : '' }}>Semestre 1</option>
                                                    <option value="2" {{ old('semester') == '2' ? 'selected' : '' }}>Semestre 2</option>
                                                </select>
                                                @error('semester')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-12 mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                                @error('description')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Décrivez brièvement cette évaluation (optionnel).</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Scoring and Settings Card -->
                                <div class="card border mb-4">
                                    <div class="card-header bg-light py-3">
                                        <h6 class="mb-0 fw-bold">Notation et paramètres</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="total_points" class="form-label">Barème total <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('total_points') is-invalid @enderror" id="total_points" name="total_points" value="{{ old('total_points', 20) }}" min="1" max="100" required>
                                            @error('total_points')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Nombre maximum de points pour cette évaluation.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="coefficient" class="form-label">Coefficient <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('coefficient') is-invalid @enderror" id="coefficient" name="coefficient" value="{{ old('coefficient', 1) }}" min="1" max="10" required>
                                            @error('coefficient')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Poids de cette évaluation dans la moyenne.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="passing_grade" class="form-label">Note de passage</label>
                                            <input type="number" class="form-control @error('passing_grade') is-invalid @enderror" id="passing_grade" name="passing_grade" value="{{ old('passing_grade') }}" min="0" step="0.25">
                                            @error('passing_grade')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Seuil de réussite (optionnel). Laissez vide si non applicable.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="school_year_id" class="form-label">Année scolaire <span class="text-danger">*</span></label>
                                            <select class="form-select @error('school_year_id') is-invalid @enderror" id="school_year_id" name="school_year_id" required>
                                                <option value="">Sélectionner une année scolaire</option>
                                                @foreach($schoolYears as $schoolYear)
                                                    <option value="{{ $schoolYear->id }}" {{ old('school_year_id', $currentSchoolYear ? $currentSchoolYear->id : '') == $schoolYear->id ? 'selected' : '' }}>
                                                        {{ $schoolYear->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('school_year_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_published">Publier immédiatement</label>
                                            <div class="form-text">Si activé, les étudiants pourront voir leurs notes dès qu'elles seront saisies.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Buttons Card -->
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Créer l'évaluation
                                            </button>
                                            <a href="{{ route('grades.index') }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-2"></i>Annuler
                                            </a>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>Après avoir créé l'évaluation, vous pourrez saisir les notes des étudiants.
                                            </small>
                                        </div>
                                    </div>
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
        // Update subject options when class changes
        const classSelect = document.getElementById('class_id');
        const subjectSelect = document.getElementById('subject_id');
        
        classSelect.addEventListener('change', function() {
            const classId = this.value;
            
            if (classId) {
                // Clear current options
                subjectSelect.innerHTML = '<option value="">Chargement des matières...</option>';
                
                // Fetch subjects for this class
                fetch(`/subjects-by-class/${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        subjectSelect.innerHTML = '<option value="">Sélectionner une matière</option>';
                        
                        if (data.length > 0) {
                            data.forEach(subject => {
                                const option = document.createElement('option');
                                option.value = subject.id;
                                option.textContent = subject.nom;
                                subjectSelect.appendChild(option);
                            });
                        } else {
                            subjectSelect.innerHTML = '<option value="">Aucune matière disponible pour cette classe</option>';
                        }
                    })
                    .catch(error => {
                        debugError('Error fetching subjects:', error);
                        subjectSelect.innerHTML = '<option value="">Erreur lors du chargement des matières</option>';
                    });
            } else {
                // Reset if no class is selected
                subjectSelect.innerHTML = '<option value="">Sélectionner une matière</option>';
                
                // Add all subjects back
                @foreach($subjects as $subject)
                    const option = document.createElement('option');
                    option.value = {{ $subject->id }};
                    option.textContent = '{{ $subject->nom }}';
                    subjectSelect.appendChild(option);
                @endforeach
            }
        });
        
        // Form validation
        const form = document.getElementById('createEvaluationForm');
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Check required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Validate passing grade if provided
            const passingGradeField = document.getElementById('passing_grade');
            const totalPointsField = document.getElementById('total_points');
            
            if (passingGradeField.value.trim() !== '') {
                const passingGrade = parseFloat(passingGradeField.value);
                const totalPoints = parseFloat(totalPointsField.value);
                
                if (isNaN(passingGrade) || passingGrade < 0 || passingGrade > totalPoints) {
                    passingGradeField.classList.add('is-invalid');
                    if (!passingGradeField.nextElementSibling || !passingGradeField.nextElementSibling.classList.contains('invalid-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.classList.add('invalid-feedback');
                        feedback.textContent = `La note de passage doit être comprise entre 0 et ${totalPoints}.`;
                        passingGradeField.insertAdjacentElement('afterend', feedback);
                    }
                    isValid = false;
                } else {
                    passingGradeField.classList.remove('is-invalid');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs dans le formulaire avant de soumettre.');
            }
        });
    });
</script>
@endsection 