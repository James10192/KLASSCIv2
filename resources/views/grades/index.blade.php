@extends('layouts.app')

@section('title', 'Gestion des Notes')

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
                                <h2 class="fw-bold mb-0">Gestion des notes</h2>
                                <div>
                                    <a href="{{ route('grades.create') }}" class="btn btn-success px-4 me-2">
                                        <i class="fas fa-plus-circle me-2"></i> Ajouter des notes
                                    </a>
                                    <a href="{{ route('grades.bulletin.select') }}" class="btn btn-primary px-4">
                                        <i class="fas fa-file-alt me-2"></i> Bulletins
                                    </a>
                                </div>
                            </div>
                            <p class="text-muted mb-4">Gérez les notes des étudiants, consultez les statistiques et générez des bulletins de notes.</p>
                            
                            <div class="d-flex gap-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-2">
                                        <i class="fas fa-graduation-cap text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalStudents ?? 0 }}</h6>
                                        <small class="text-muted">Étudiants</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-success-light rounded-circle p-2 me-2">
                                        <i class="fas fa-book text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalSubjects ?? 0 }}</h6>
                                        <small class="text-muted">Matières</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-info-light rounded-circle p-2 me-2">
                                        <i class="fas fa-chart-line text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $averageGrade ?? 'N/A' }}</h6>
                                        <small class="text-muted">Moyenne</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/grades-concept-illustration_114360-5958.jpg" alt="Grades" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Search and Filter Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <form action="{{ route('grades.index') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="class_id" class="form-label small text-muted">Classe</label>
                            <select class="form-select border-0 bg-light" id="class_id" name="class_id">
                                <option value="">Toutes les classes</option>
                                @foreach($classes ?? [] as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="subject_id" class="form-label small text-muted">Matière</label>
                            <select class="form-select border-0 bg-light" id="subject_id" name="subject_id">
                                <option value="">Toutes les matières</option>
                                @foreach($subjects ?? [] as $subject)
                                    <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="semester" class="form-label small text-muted">Semestre</label>
                            <select class="form-select border-0 bg-light" id="semester" name="semester">
                                <option value="">Tous les semestres</option>
                                <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Semestre 1</option>
                                <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semestre 2</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-grid w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i> Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques des notes -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Moyenne générale</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $averageGrade ?? 'N/A' }}</h2>
                        </div>
                        <div class="stat-icon bg-primary-light rounded-circle p-3">
                            <i class="fas fa-chart-line text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ ($averageGrade ?? 0) > 0 ? (($averageGrade ?? 0) / 20) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Sur 20 points</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Excellents</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $excellentCount ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-success-light rounded-circle p-3">
                            <i class="fas fa-trophy text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($totalStudents ?? 0) > 0 ? (($excellentCount ?? 0) / ($totalStudents ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Notes > 16/20</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">À améliorer</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $needImprovementCount ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-warning-light rounded-circle p-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ ($totalStudents ?? 0) > 0 ? (($needImprovementCount ?? 0) / ($totalStudents ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Notes entre 8 et 10/20</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">En difficulté</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $failingCount ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-danger-light rounded-circle p-3">
                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ ($totalStudents ?? 0) > 0 ? (($failingCount ?? 0) / ($totalStudents ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Notes < 8/20</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des notes -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-list text-primary me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Liste des notes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">#</th>
                                    <th scope="col">Étudiant</th>
                                    <th scope="col">Classe</th>
                                    <th scope="col">Matière</th>
                                    <th scope="col">Semestre</th>
                                    <th scope="col">Note</th>
                                    <th scope="col">Appréciation</th>
                                    <th scope="col" class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grades ?? [] as $grade)
                                <tr>
                                    <td class="ps-4">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary-light text-primary me-2">
                                                {{ strtoupper(substr($grade->student->name ?? 'E', 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $grade->student->name ?? 'N/A' }}</h6>
                                                <small class="text-muted">ID: {{ $grade->student->student_id ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $grade->class->name ?? 'N/A' }}</td>
                                    <td>{{ $grade->subject->name ?? 'N/A' }}</td>
                                    <td>Semestre {{ $grade->semester }}</td>
                                    <td>
                                        @if(($grade->score ?? 0) >= 16)
                                            <span class="badge bg-success-light text-success">{{ $grade->score ?? 'N/A' }}/20</span>
                                        @elseif(($grade->score ?? 0) >= 14)
                                            <span class="badge bg-info-light text-info">{{ $grade->score ?? 'N/A' }}/20</span>
                                        @elseif(($grade->score ?? 0) >= 10)
                                            <span class="badge bg-primary-light text-primary">{{ $grade->score ?? 'N/A' }}/20</span>
                                        @elseif(($grade->score ?? 0) >= 8)
                                            <span class="badge bg-warning-light text-warning">{{ $grade->score ?? 'N/A' }}/20</span>
                                        @else
                                            <span class="badge bg-danger-light text-danger">{{ $grade->score ?? 'N/A' }}/20</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($grade->score ?? 0) >= 16)
                                            <span class="text-success">Excellent</span>
                                        @elseif(($grade->score ?? 0) >= 14)
                                            <span class="text-info">Très bien</span>
                                        @elseif(($grade->score ?? 0) >= 12)
                                            <span class="text-primary">Bien</span>
                                        @elseif(($grade->score ?? 0) >= 10)
                                            <span class="text-secondary">Passable</span>
                                        @elseif(($grade->score ?? 0) >= 8)
                                            <span class="text-warning">À améliorer</span>
                                        @else
                                            <span class="text-danger">Insuffisant</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('grades.show', $grade->id) }}">
                                                        <i class="fas fa-eye text-primary me-2"></i> Voir
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('grades.edit', $grade->id) }}">
                                                        <i class="fas fa-edit text-warning me-2"></i> Modifier
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('grades.student', $grade->student_id) }}">
                                                        <i class="fas fa-user-graduate text-info me-2"></i> Toutes les notes
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-3">
                                                <i class="fas fa-graduation-cap fa-3x text-muted"></i>
                                            </div>
                                            <h5 class="text-muted">Aucune note trouvée</h5>
                                            <p class="text-muted small mb-0">Ajustez vos filtres ou ajoutez des notes pour voir les résultats</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-0">Affichage de {{ $grades->firstItem() ?? 0 }} à {{ $grades->lastItem() ?? 0 }} sur {{ $grades->total() ?? 0 }} notes</p>
                        </div>
                        <div>
                            {{ $grades->withQueryString()->links() }}
                        </div>
                    </div>
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
    
    .bg-info-light {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    .bg-secondary-light {
        background-color: rgba(108, 117, 125, 0.1);
    }
    
    .icon-box {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Style pour les cartes statistiques */
    .stat-card {
        transition: all 0.3s ease;
        border-radius: 15px;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    /* Style pour les avatars */
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    /* Style pour la pagination */
    .pagination {
        margin-bottom: 0;
    }
    
    .page-item.active .page-link {
        background-color: var(--esbtp-green);
        border-color: var(--esbtp-green);
    }
    
    .page-link {
        color: var(--esbtp-green);
    }
    
    /* Animation pour les lignes du tableau */
    tbody tr {
        transition: all 0.2s ease;
    }
    
    tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
</style>
@endsection 