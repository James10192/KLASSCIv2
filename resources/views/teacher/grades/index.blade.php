@extends('layouts.app')

@section('title', 'Gestion des notes')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-white mb-0">Gestion des notes</h6>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('grades.create') }}" class="btn btn-sm btn-light">
                                <i class="fas fa-plus me-1"></i> Créer une évaluation
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

                    <!-- Filters -->
                    <div class="card border mb-4">
                        <div class="card-header bg-light py-3">
                            <h6 class="mb-0 fw-bold">Filtres</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('grades.index') }}" method="GET" id="filter-form">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="classe_id" class="form-label">Classe</label>
                                        <select class="form-select" id="classe_id" name="classe_id" onchange="document.getElementById('filter-form').submit()">
                                            <option value="">Toutes les classes</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id }}" {{ $classeId == $classe->id ? 'selected' : '' }}>
                                                    {{ $classe->nom }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="subject_id" class="form-label">Matière</label>
                                        <select class="form-select" id="subject_id" name="subject_id" onchange="document.getElementById('filter-form').submit()">
                                            <option value="">Toutes les matières</option>
                                            @foreach($subjects as $subject)
                                                <option value="{{ $subject->id }}" {{ $subjectId == $subject->id ? 'selected' : '' }}>
                                                    {{ $subject->nom }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="type" class="form-label">Type</label>
                                        <select class="form-select" id="type" name="type" onchange="document.getElementById('filter-form').submit()">
                                            <option value="">Tous les types</option>
                                            <option value="devoir" {{ $type == 'devoir' ? 'selected' : '' }}>Devoir</option>
                                            <option value="controle" {{ $type == 'controle' ? 'selected' : '' }}>Contrôle</option>
                                            <option value="examen" {{ $type == 'examen' ? 'selected' : '' }}>Examen</option>
                                            <option value="tp" {{ $type == 'tp' ? 'selected' : '' }}>Travaux Pratiques</option>
                                            <option value="projet" {{ $type == 'projet' ? 'selected' : '' }}>Projet</option>
                                            <option value="oral" {{ $type == 'oral' ? 'selected' : '' }}>Examen Oral</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="semester" class="form-label">Semestre</label>
                                        <select class="form-select" id="semester" name="semester" onchange="document.getElementById('filter-form').submit()">
                                            <option value="">Tous les semestres</option>
                                            <option value="1" {{ $semester == '1' ? 'selected' : '' }}>Semestre 1</option>
                                            <option value="2" {{ $semester == '2' ? 'selected' : '' }}>Semestre 2</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="school_year_id" class="form-label">Année scolaire</label>
                                        <select class="form-select" id="school_year_id" name="school_year_id" onchange="document.getElementById('filter-form').submit()">
                                            <option value="">Toutes les années</option>
                                            @foreach($schoolYears as $schoolYear)
                                                <option value="{{ $schoolYear->id }}" {{ $schoolYearId == $schoolYear->id ? 'selected' : '' }}>
                                                    {{ $schoolYear->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-end">
                                        <a href="{{ route('grades.index') }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Effacer les filtres
                                        </a>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-filter me-1"></i> Filtrer
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Evaluations Table -->
                    <div class="card border">
                        <div class="card-header bg-light py-3">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="mb-0 fw-bold">Évaluations ({{ $evaluations->total() }})</h6>
                                </div>
                                <div class="col-auto">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Rechercher...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-items-center mb-0" id="evaluationsTable">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Titre</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Classe</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Matière</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Type</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Semestre</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Coefficient</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Statut</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Notes</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($evaluations as $evaluation)
                                        <tr>
                                            <td class="ps-3">
                                                <h6 class="mb-0 text-sm">{{ $evaluation->title }}</h6>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $evaluation->class->nom }}</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $evaluation->subject->nom }}</p>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ ucfirst($evaluation->type) }}</span>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ date('d/m/Y', strtotime($evaluation->date)) }}</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">S{{ $evaluation->semester }}</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $evaluation->coefficient }}</p>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $evaluation->is_published ? 'success' : 'warning' }}">
                                                    {{ $evaluation->is_published ? 'Publié' : 'Brouillon' }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $gradeCount = $evaluation->grades->count();
                                                    $studentCount = $evaluation->class->students->count();
                                                    $percentage = $studentCount > 0 ? round(($gradeCount / $studentCount) * 100) : 0;
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2 text-xs font-weight-bold">{{ $percentage }}%</span>
                                                    <div>
                                                        <div class="progress" style="width: 80px;">
                                                            <div class="progress-bar bg-{{ $percentage == 100 ? 'success' : ($percentage >= 50 ? 'info' : 'warning') }}" 
                                                                 role="progressbar" style="width: {{ $percentage }}%" 
                                                                 aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="text-xxs text-muted">{{ $gradeCount }}/{{ $studentCount }} étudiants</span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('grades.show', $evaluation->id) }}" class="btn btn-link text-info p-1" data-bs-toggle="tooltip" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('grades.edit', $evaluation->id) }}" class="btn btn-link text-primary p-1" data-bs-toggle="tooltip" title="Modifier les notes">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-link text-danger p-1" data-bs-toggle="modal" data-bs-target="#deleteEvaluationModal" 
                                                            onclick="setupDeleteModal({{ $evaluation->id }}, '{{ $evaluation->title }}', '{{ $evaluation->class->nom }}', '{{ $evaluation->subject->nom }}', '{{ date('d/m/Y', strtotime($evaluation->date)) }}')"
                                                            data-bs-toggle="tooltip" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-graduation-cap fa-3x text-secondary mb-3"></i>
                                                    <h6 class="text-secondary">Aucune évaluation trouvée</h6>
                                                    <p class="text-xs text-muted">Créez votre première évaluation en cliquant sur le bouton "Créer une évaluation".</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer py-3">
                            <div class="d-flex justify-content-center">
                                {{ $evaluations->appends(request()->except('page'))->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Delete Modal -->
@include('teacher.grades.destroy')
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('evaluationsTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const title = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const classe = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const subject = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const type = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || classe.includes(searchTerm) || 
                    subject.includes(searchTerm) || type.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                boundary: document.body
            });
        });
    });
</script>
@endsection 