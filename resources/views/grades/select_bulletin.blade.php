@extends('layouts.app')

@section('title', 'Sélection de bulletin')

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
                                <h2 class="fw-bold mb-0">Génération de bulletin</h2>
                                <a href="{{ route('grades.index') }}" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-arrow-left me-2"></i> Retour aux notes
                                </a>
                            </div>
                            <p class="text-muted mb-4">Sélectionnez un étudiant et un semestre pour générer un bulletin de notes.</p>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 180px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/grades-concept-illustration_114360-5958.jpg" alt="Bulletin" class="img-fluid" style="max-height: 160px; opacity: 0.9;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire de sélection -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Sélection des paramètres</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('grades.bulletin', ['student' => 0, 'semester' => 0]) }}" method="GET" id="bulletinForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">Étudiant</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Sélectionnez un étudiant</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->user->name }} - {{ $student->class->name ?? 'Classe non assignée' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="semester_id" class="form-label">Semestre</label>
                                <select class="form-select" id="semester_id" name="semester_id" required>
                                    <option value="">Sélectionnez un semestre</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}">
                                            {{ $semester->name }} ({{ \Carbon\Carbon::parse($semester->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($semester->end_date)->format('d/m/Y') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-secondary px-4" onclick="window.history.back();">
                                        <i class="fas fa-times me-2"></i> Annuler
                                    </button>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-file-pdf me-2"></i> Générer le bulletin
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bulletinForm');
    const studentSelect = document.getElementById('student_id');
    const semesterSelect = document.getElementById('semester_id');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const studentId = studentSelect.value;
        const semesterId = semesterSelect.value;
        
        if (studentId && semesterId) {
            window.location.href = "{{ url('grades/bulletin') }}/" + studentId + "/" + semesterId;
        }
    });
});
</script>
@endsection 