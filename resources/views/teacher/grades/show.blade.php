@extends('layouts.app')

@section('title', 'Détails de l\'évaluation - {{ $evaluation->title }}')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-white mb-0">Détails de l'évaluation - {{ $evaluation->title }}</h6>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('grades.index') }}" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                            <a href="{{ route('grades.edit', $evaluation->id) }}" class="btn btn-sm btn-info ms-2">
                                <i class="fas fa-edit me-1"></i> Modifier les notes
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
                    
                    <!-- Evaluation Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations générales</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Titre</p>
                                            <h6>{{ $evaluation->title }}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Type</p>
                                            <h6><span class="badge bg-primary">{{ ucfirst($evaluation->type) }}</span></h6>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Classe</p>
                                            <h6>{{ $evaluation->class->nom }}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Matière</p>
                                            <h6>{{ $evaluation->subject->nom }}</h6>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Date</p>
                                            <h6>{{ date('d/m/Y', strtotime($evaluation->date)) }}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Semestre</p>
                                            <h6>Semestre {{ $evaluation->semester }}</h6>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Barème</p>
                                            <h6>{{ $evaluation->total_points }} points</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Coefficient</p>
                                            <h6>{{ $evaluation->coefficient }}</h6>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Note de passage</p>
                                            <h6>{{ $evaluation->passing_grade ?? 'Non définie' }}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="text-xs text-uppercase text-muted mb-1">Statut</p>
                                            <h6>
                                                <span class="badge bg-{{ $evaluation->is_published ? 'success' : 'warning' }}">
                                                    {{ $evaluation->is_published ? 'Publié' : 'Brouillon' }}
                                                </span>
                                            </h6>
                                        </div>
                                    </div>
                                    @if($evaluation->description)
                                    <div class="row">
                                        <div class="col-12">
                                            <p class="text-xs text-uppercase text-muted mb-1">Description</p>
                                            <p class="text-sm mb-0">{{ $evaluation->description }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Statistiques</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 mb-4">
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card border p-3 rounded text-center h-100">
                                                <div class="icon-box rounded-circle bg-primary mx-auto mb-2">
                                                    <i class="fas fa-users text-white"></i>
                                                </div>
                                                <h2 class="font-weight-bold mb-0">{{ $stats['total_students'] }}</h2>
                                                <p class="text-xs text-muted mb-0">Étudiants</p>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card border p-3 rounded text-center h-100">
                                                <div class="icon-box rounded-circle bg-success mx-auto mb-2">
                                                    <i class="fas fa-check text-white"></i>
                                                </div>
                                                <h2 class="font-weight-bold mb-0">{{ $stats['present_count'] }}</h2>
                                                <p class="text-xs text-muted mb-0">Présents</p>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card border p-3 rounded text-center h-100">
                                                <div class="icon-box rounded-circle bg-danger mx-auto mb-2">
                                                    <i class="fas fa-times text-white"></i>
                                                </div>
                                                <h2 class="font-weight-bold mb-0">{{ $stats['absent_count'] }}</h2>
                                                <p class="text-xs text-muted mb-0">Absents</p>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="stat-card border p-3 rounded text-center h-100">
                                                <div class="icon-box rounded-circle bg-warning mx-auto mb-2">
                                                    <i class="fas fa-minus-circle text-white"></i>
                                                </div>
                                                <h2 class="font-weight-bold mb-0">{{ $stats['exempt_count'] }}</h2>
                                                <p class="text-xs text-muted mb-0">Exemptés</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="progress-card border p-3 rounded">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">Complétion des notes</h6>
                                                    <span class="text-sm font-weight-bold">{{ $stats['completion_percentage'] }}%</span>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-gradient-success" role="progressbar" 
                                                         style="width: {{ $stats['completion_percentage'] }}%" 
                                                         aria-valuenow="{{ $stats['completion_percentage'] }}" 
                                                         aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <span class="text-xs text-muted">{{ $stats['grades_entered'] }} notes saisies</span>
                                                    <span class="text-xs text-muted">{{ $stats['total_students'] }} étudiants total</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="grade-stat-card border p-3 rounded text-center h-100">
                                                <div class="grade-value mb-1">
                                                    <span class="display-6 font-weight-bold">{{ number_format($stats['avg_grade'], 2) }}</span>
                                                    <span class="text-muted text-sm">/ {{ $evaluation->total_points }}</span>
                                                </div>
                                                <div class="progress mb-2" style="height: 6px;">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                         style="width: {{ ($stats['avg_grade'] / $evaluation->total_points) * 100 }}%" 
                                                         aria-valuenow="{{ $stats['avg_grade'] }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $evaluation->total_points }}"></div>
                                                </div>
                                                <p class="text-xs text-muted mb-0">Moyenne</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="grade-stat-card border p-3 rounded text-center h-100">
                                                <div class="grade-value mb-1">
                                                    <span class="display-6 font-weight-bold text-success">{{ number_format($stats['max_grade'], 2) }}</span>
                                                    <span class="text-muted text-sm">/ {{ $evaluation->total_points }}</span>
                                                </div>
                                                <div class="progress mb-2" style="height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: {{ ($stats['max_grade'] / $evaluation->total_points) * 100 }}%" 
                                                         aria-valuenow="{{ $stats['max_grade'] }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $evaluation->total_points }}"></div>
                                                </div>
                                                <p class="text-xs text-muted mb-0">Note max</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="grade-stat-card border p-3 rounded text-center h-100">
                                                <div class="grade-value mb-1">
                                                    <span class="display-6 font-weight-bold text-danger">{{ number_format($stats['min_grade'], 2) }}</span>
                                                    <span class="text-muted text-sm">/ {{ $evaluation->total_points }}</span>
                                                </div>
                                                <div class="progress mb-2" style="height: 6px;">
                                                    <div class="progress-bar bg-danger" role="progressbar" 
                                                         style="width: {{ ($stats['min_grade'] / $evaluation->total_points) * 100 }}%" 
                                                         aria-valuenow="{{ $stats['min_grade'] }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $evaluation->total_points }}"></div>
                                                </div>
                                                <p class="text-xs text-muted mb-0">Note min</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Grades Table -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="mb-0">Notes des étudiants</h6>
                                </div>
                                <div class="col-auto">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="searchStudentInput" placeholder="Rechercher un étudiant...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-items-center mb-0" id="studentGradesTable">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">N°</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Étudiant</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Numéro d'inscription</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Note / {{ $evaluation->total_points }}</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Statut</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Commentaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($evaluation->class->students as $index => $student)
                                            @php
                                                $grade = $studentGrades[$student->id] ?? null;
                                                $status = $grade ? $grade->status : 'non évalué';
                                                $gradeValue = $grade ? $grade->grade : null;
                                                $comment = $grade ? $grade->comment : null;
                                                
                                                // Calculer le pourcentage pour la barre de progression
                                                $percentage = $gradeValue !== null ? ($gradeValue / $evaluation->total_points) * 100 : 0;
                                                
                                                // Déterminer la classe de couleur
                                                $colorClass = 'bg-secondary';
                                                if ($gradeValue !== null) {
                                                    if ($evaluation->passing_grade && $gradeValue >= $evaluation->passing_grade) {
                                                        $colorClass = 'bg-success';
                                                    } elseif ($evaluation->passing_grade && $gradeValue < $evaluation->passing_grade) {
                                                        $colorClass = 'bg-danger';
                                                    } else {
                                                        $colorClass = $percentage >= 70 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                                                    }
                                                }
                                            @endphp
                                            <tr class="{{ $status === 'absent' ? 'table-secondary' : '' }}">
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
                                                    @if($status === 'absent')
                                                        <span class="badge bg-secondary">Absent</span>
                                                    @elseif($status === 'exempt')
                                                        <span class="badge bg-info">Exempté</span>
                                                    @elseif($gradeValue !== null)
                                                        <div class="d-flex align-items-center">
                                                            <span class="me-2 text-sm font-weight-bold">{{ number_format($gradeValue, 2) }}</span>
                                                            <div>
                                                                <div class="progress" style="width: 80px; height: 5px;">
                                                                    <div class="progress-bar {{ $colorClass }}" role="progressbar" 
                                                                         style="width: {{ $percentage }}%" 
                                                                         aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted text-xs">Non évalué</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($status === 'absent')
                                                        <span class="badge bg-secondary">Absent</span>
                                                    @elseif($status === 'exempt')
                                                        <span class="badge bg-info">Exempté</span>
                                                    @elseif($status === 'present')
                                                        <span class="badge bg-success">Présent</span>
                                                    @else
                                                        <span class="badge bg-secondary">Non évalué</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <p class="text-xs text-muted mb-0">{{ $comment ?: '-' }}</p>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="fas fa-user-graduate fa-3x text-secondary mb-3"></i>
                                                        <h6 class="text-secondary">Aucun étudiant dans cette classe</h6>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Student search functionality
        const searchInput = document.getElementById('searchStudentInput');
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('studentGradesTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const studentName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const studentRegistration = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                
                if (studentName.includes(searchTerm) || studentRegistration.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>

<style>
    .icon-box {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-card, .grade-stat-card, .progress-card {
        transition: all 0.3s ease;
    }
    
    .stat-card:hover, .grade-stat-card:hover, .progress-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .grade-value {
        font-family: 'Courier New', monospace;
    }
    
    .display-6 {
        font-size: 1.8rem;
        font-weight: 600;
    }
    
    .table > tbody > tr.table-secondary {
        --bs-table-bg: rgba(233, 236, 239, 0.6);
    }
</style>
@endsection 