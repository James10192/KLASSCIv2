@extends('layouts.app')

@section('title', 'Gestion des enseignants')

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
                                <h2 class="fw-bold mb-0">Gestion des enseignants</h2>
                                <a href="{{ route('teachers.create') }}" class="btn btn-success px-4">
                                    <i class="fas fa-plus-circle me-2"></i> Ajouter un enseignant
                                </a>
                            </div>
                            <p class="text-muted mb-4">Gérez efficacement votre équipe pédagogique, consultez les informations des enseignants et attribuez des cours.</p>
                            
                            <div class="d-flex gap-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-2">
                                        <i class="fas fa-chalkboard-teacher text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalTeachers ?? 0 }}</h6>
                                        <small class="text-muted">Total</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-success-light rounded-circle p-2 me-2">
                                        <i class="fas fa-user-check text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $activeTeachers ?? 0 }}</h6>
                                        <small class="text-muted">Actifs</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-warning-light rounded-circle p-2 me-2">
                                        <i class="fas fa-book text-warning"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalCourses ?? 0 }}</h6>
                                        <small class="text-muted">Cours</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/teacher-concept-illustration_114360-2166.jpg" alt="Teachers" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
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
                    <form action="{{ route('teachers.index') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-0 bg-light" name="search" placeholder="Rechercher un enseignant..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select border-0 bg-light" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactifs</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select border-0 bg-light" name="sort">
                                <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nom (A-Z)</option>
                                <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nom (Z-A)</option>
                                <option value="created_at_desc" {{ request('sort') == 'created_at_desc' ? 'selected' : '' }}>Plus récents</option>
                                <option value="created_at_asc" {{ request('sort') == 'created_at_asc' ? 'selected' : '' }}>Plus anciens</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="d-grid">
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

    <!-- Teachers List -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-list text-primary me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Liste des enseignants</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">#</th>
                                    <th scope="col">Nom</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Téléphone</th>
                                    <th scope="col">Spécialité</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col" class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teachers as $teacher)
                                <tr>
                                    <td class="ps-4">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary-light text-primary me-2">
                                                {{ strtoupper(substr($teacher->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $teacher->name }}</h6>
                                                <small class="text-muted">ID: {{ $teacher->id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $teacher->email }}</td>
                                    <td>{{ $teacher->phone ?? 'N/A' }}</td>
                                    <td>{{ $teacher->specialty ?? 'N/A' }}</td>
                                    <td>
                                        @if($teacher->is_active)
                                            <span class="badge bg-success-light text-success">Actif</span>
                                        @else
                                            <span class="badge bg-danger-light text-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('teachers.show', $teacher) }}">
                                                        <i class="fas fa-eye text-primary me-2"></i> Voir
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('teachers.schedule', $teacher) }}">
                                                        <i class="fas fa-calendar-alt text-success me-2"></i> Emploi du temps
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('teachers.edit', $teacher) }}">
                                                        <i class="fas fa-edit text-warning me-2"></i> Modifier
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('teachers.destroy', $teacher) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet enseignant ?')">
                                                            <i class="fas fa-trash-alt me-2"></i> Supprimer
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-3">
                                                <i class="fas fa-user-slash fa-3x text-muted"></i>
                                            </div>
                                            <h5 class="text-muted">Aucun enseignant trouvé</h5>
                                            <p class="text-muted small mb-0">Ajoutez des enseignants ou modifiez vos critères de recherche</p>
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
                            <p class="text-muted small mb-0">Affichage de {{ $teachers->firstItem() ?? 0 }} à {{ $teachers->lastItem() ?? 0 }} sur {{ $teachers->total() ?? 0 }} enseignants</p>
                        </div>
                        <div>
                            {{ $teachers->withQueryString()->links() }}
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
    
    .icon-box {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
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