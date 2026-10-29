@extends('layouts.app')

@section('title', 'Gestion des Sections')

@section('content')
<div class="container-fluid">
    <!-- En-tête avec bouton d'ajout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion des Sections</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSectionModal">
            <i class="fas fa-plus-circle"></i> Ajouter une Section
        </button>
                </div>

    <!-- Messages de notification -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

    <!-- Filtres de recherche -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres de recherche</h6>
        </div>
                                <div class="card-body">
                                    <form action="{{ route('sections.index') }}" method="GET" class="row g-3">
                                        <div class="col-md-4">
                    <label for="class_id" class="form-label">Classe</label>
                                            <select class="form-select" id="class_id" name="class_id">
                                                <option value="">Toutes les classes</option>
                        @foreach($classes ?? [] as $class)
                                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Nom de la section..." value="{{ request('search') }}">
                                        </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Rechercher
                                            </button>
                    <a href="{{ route('sections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </a>
                                        </div>
                                    </form>
                                </div>
    </div>

    <!-- Statistiques des sections -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total des Sections</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSections ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total des Étudiants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalStudents ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Moyenne d'Étudiants par Section</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($totalStudents, $totalSections) && $totalSections > 0 
                                   ? number_format($totalStudents / $totalSections, 1) 
                                   : 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Sections Actives</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeSections ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                            </div>
                        </div>
                    </div>

    <!-- Tableau des sections -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Sections</h6>
            <button class="btn btn-sm btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
        <div class="card-body">
                    <div class="table-responsive">
                <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Classe</th>
                                    <th>Capacité</th>
                                    <th>Étudiants</th>
                            <th>Taux d'occupation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                        @forelse($sections ?? [] as $section)
                                    <tr>
                                        <td>{{ $section->id }}</td>
                                        <td>{{ $section->name }}</td>
                                        <td>
                                            @if($section->class)
                                        <span class="badge bg-primary">{{ $section->class->name }}</span>
                                            @else
                                        <span class="badge bg-secondary">Non assignée</span>
                                            @endif
                                        </td>
                                        <td>{{ $section->capacity ?? 'Non définie' }}</td>
                                        <td>
                                    <span class="badge bg-info">{{ $section->students_count ?? 0 }} étudiants</span>
                                        </td>
                                        <td>
                                    @php
                                        $occupationRate = isset($section->capacity) && $section->capacity > 0 
                                            ? (($section->students_count ?? 0) / $section->capacity) * 100 
                                            : 0;
                                        
                                        $badgeClass = 'bg-success';
                                        if ($occupationRate > 90) {
                                            $badgeClass = 'bg-danger';
                                        } elseif ($occupationRate > 75) {
                                            $badgeClass = 'bg-warning';
                                        }
                                    @endphp
                                    <div class="progress">
                                        <div class="progress-bar {{ $badgeClass }}" role="progressbar" 
                                             style="width: {{ min(100, $occupationRate) }}%" 
                                             aria-valuenow="{{ $occupationRate }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ number_format($occupationRate, 1) }}%
                                        </div>
                                    </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                        <a href="{{ route('timetable.section', $section->id) }}" class="btn btn-sm btn-info" title="Emploi du temps">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editSectionModal{{ $section->id }}" 
                                                title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewStudentsModal{{ $section->id }}" 
                                                title="Voir les étudiants">
                                            <i class="fas fa-users"></i>
                                        </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                data-bs-target="#deleteSectionModal{{ $section->id }}" 
                                                    title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                    <!-- Modal de modification de section -->
                                    <div class="modal fade" id="editSectionModal{{ $section->id }}" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editSectionModalLabel">Modifier la Section</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('sections.update', $section->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="name{{ $section->id }}" class="form-label">Nom de la Section</label>
                                                            <input type="text" class="form-control" id="name{{ $section->id }}" name="name" value="{{ $section->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="class_id{{ $section->id }}" class="form-label">Classe</label>
                                                            <select class="form-select" id="class_id{{ $section->id }}" name="class_id">
                                                                <option value="">Aucune classe</option>
                                                                @foreach($classes ?? [] as $class)
                                                                    <option value="{{ $class->id }}" {{ $section->class_id == $class->id ? 'selected' : '' }}>
                                                                        {{ $class->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="capacity{{ $section->id }}" class="form-label">Capacité</label>
                                                            <input type="number" class="form-control" id="capacity{{ $section->id }}" name="capacity" min="1" value="{{ $section->capacity }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="description{{ $section->id }}" class="form-label">Description</label>
                                                            <textarea class="form-control" id="description{{ $section->id }}" name="description" rows="3">{{ $section->description }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal pour voir les étudiants -->
                                    <div class="modal fade" id="viewStudentsModal{{ $section->id }}" tabindex="-1" aria-labelledby="viewStudentsModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewStudentsModalLabel">Étudiants de la Section {{ $section->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @if($section->students && $section->students->count() > 0)
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-hover">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>ID</th>
                                                                        <th>Nom</th>
                                                                        <th>Email</th>
                                                                        <th>Téléphone</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($section->students as $student)
                                                                        <tr>
                                                                            <td>{{ $student->id }}</td>
                                                                            <td>{{ $student->name }}</td>
                                                                            <td>{{ $student->email }}</td>
                                                                            <td>{{ $student->phone ?? 'N/A' }}</td>
                                                                            <td>
                                                                                <a href="{{ route('students.show', $student->id) }}" class="btn btn-sm btn-info">
                                                                                    <i class="fas fa-eye"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <p class="text-center">Aucun étudiant trouvé dans cette section.</p>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                    <a href="{{ route('students.create', ['section_id' => $section->id]) }}" class="btn btn-primary">
                                                        <i class="fas fa-plus-circle"></i> Ajouter un Étudiant
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal de suppression de section -->
                                    <div class="modal fade" id="deleteSectionModal{{ $section->id }}" tabindex="-1" aria-labelledby="deleteSectionModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteSectionModalLabel">Confirmer la suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Êtes-vous sûr de vouloir supprimer la section <strong>{{ $section->name }}</strong> ?</p>
                                                            @if($section->students_count > 0 || ($section->students && $section->students->count() > 0))
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                                    Cette section contient des étudiants. La suppression déplacera ces étudiants vers la section "Non assignée".
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <form action="{{ route('sections.destroy', $section->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                <td colspan="7" class="text-center">Aucune section trouvée</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
            @if(isset($sections) && $sections->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $sections->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal d'ajout de section -->
<div class="modal fade" id="createSectionModal" tabindex="-1" aria-labelledby="createSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSectionModalLabel">Ajouter une Nouvelle Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sections.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom de la Section</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_id" class="form-label">Classe</label>
                        <select class="form-select" id="class_id" name="class_id">
                            <option value="">Aucune classe</option>
                            @foreach($classes ?? [] as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacité</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="30">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection