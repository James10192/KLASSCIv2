@extends('layouts.app')

@section('title', 'Gestion des Emplois du Temps')

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
                                <h2 class="fw-bold mb-0">Gestion des emplois du temps</h2>
                                <a href="{{ route('timetables.create') }}" class="btn btn-success px-4">
                                    <i class="fas fa-plus-circle me-2"></i> Ajouter un cours
                                </a>
                            </div>
                            <p class="text-muted mb-4">Gérez les emplois du temps des classes, consultez les horaires et organisez les cours.</p>

                            <div class="d-flex gap-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-2">
                                        <i class="fas fa-chalkboard-teacher text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalClasses ?? 0 }}</h6>
                                        <small class="text-muted">Classes</small>
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
                                        <i class="fas fa-clock text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalSessions ?? 0 }}</h6>
                                        <small class="text-muted">Sessions</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/time-management-concept-illustration_114360-1214.jpg" alt="Timetable" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
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
                    <form action="{{ route('timetables.index') }}" method="GET" class="row g-3">
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
                            <label for="section_id" class="form-label small text-muted">Section</label>
                            <select class="form-select border-0 bg-light" id="section_id" name="section_id">
                                <option value="">Toutes les sections</option>
                                @foreach($sections ?? [] as $section)
                                    <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                        {{ $section->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="day" class="form-label small text-muted">Jour</label>
                            <select class="form-select border-0 bg-light" id="day" name="day">
                                <option value="">Tous les jours</option>
                                <option value="Lundi" {{ request('day') == 'Lundi' ? 'selected' : '' }}>Lundi</option>
                                <option value="Mardi" {{ request('day') == 'Mardi' ? 'selected' : '' }}>Mardi</option>
                                <option value="Mercredi" {{ request('day') == 'Mercredi' ? 'selected' : '' }}>Mercredi</option>
                                <option value="Jeudi" {{ request('day') == 'Jeudi' ? 'selected' : '' }}>Jeudi</option>
                                <option value="Vendredi" {{ request('day') == 'Vendredi' ? 'selected' : '' }}>Vendredi</option>
                                <option value="Samedi" {{ request('day') == 'Samedi' ? 'selected' : '' }}>Samedi</option>
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

    <!-- Quick Access Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100 quick-access-card" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary-light rounded-circle p-3 me-3">
                            <i class="fas fa-chalkboard text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0 fw-bold">Emploi du temps par classe</h5>
                    </div>
                    <p class="card-text text-muted">Consultez l'emploi du temps complet d'une classe spécifique.</p>
                    <div class="mt-3">
                        <form id="classForm" class="row g-2">
                            <div class="col-8">
                                <select class="form-select border-0 bg-light" id="class_select" name="class_id" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes ?? [] as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="button" onclick="viewClassTimetable()" class="btn btn-primary w-100">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100 quick-access-card" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-success-light rounded-circle p-3 me-3">
                            <i class="fas fa-user-tie text-success"></i>
                        </div>
                        <h5 class="card-title mb-0 fw-bold">Emploi du temps par enseignant</h5>
                    </div>
                    <p class="card-text text-muted">Consultez l'emploi du temps d'un enseignant spécifique.</p>
                    <div class="mt-3">
                        <form id="teacherForm" class="row g-2">
                            <div class="col-8">
                                <select class="form-select border-0 bg-light" id="teacher_select" name="teacher_id" required>
                                    <option value="">Sélectionner un enseignant</option>
                                    @foreach($teachers ?? [] as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="button" onclick="viewTeacherTimetable()" class="btn btn-success w-100">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100 quick-access-card" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-info-light rounded-circle p-3 me-3">
                            <i class="fas fa-print text-info"></i>
                        </div>
                        <h5 class="card-title mb-0 fw-bold">Imprimer l'emploi du temps</h5>
                    </div>
                    <p class="card-text text-muted">Générez une version imprimable de l'emploi du temps.</p>
                    <div class="mt-3">
                        <form id="printForm" class="row g-2">
                            <div class="col-8">
                                <select class="form-select border-0 bg-light" id="print_class_select" name="class_id" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes ?? [] as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="button" onclick="printClassTimetable()" class="btn btn-info w-100 text-white">
                                    <i class="fas fa-print"></i> Imprimer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des emplois du temps -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-list text-primary me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Liste des cours</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">#</th>
                                    <th scope="col">Classe</th>
                                    <th scope="col">Section</th>
                                    <th scope="col">Jour</th>
                                    <th scope="col">Horaire</th>
                                    <th scope="col">Matière</th>
                                    <th scope="col">Enseignant</th>
                                    <th scope="col">Salle</th>
                                    <th scope="col" class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timetables ?? [] as $timetable)
                                <tr>
                                    <td class="ps-4">{{ $loop->iteration }}</td>
                                    <td>{{ $timetable->class->name ?? 'N/A' }}</td>
                                    <td>{{ $timetable->section->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge
                                            @if($timetable->day == 'Lundi') bg-primary-light text-primary
                                            @elseif($timetable->day == 'Mardi') bg-success-light text-success
                                            @elseif($timetable->day == 'Mercredi') bg-info-light text-info
                                            @elseif($timetable->day == 'Jeudi') bg-warning-light text-warning
                                            @elseif($timetable->day == 'Vendredi') bg-danger-light text-danger
                                            @else bg-secondary-light text-secondary
                                            @endif">
                                            {{ $timetable->day }}
                                        </span>
                                    </td>
                                    <td>{{ $timetable->start_time }} - {{ $timetable->end_time }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="subject-icon bg-primary-light text-primary me-2 rounded-circle p-2">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            {{ $timetable->subject->name ?? 'Non définie' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-success-light text-success me-2">
                                                {{ strtoupper(substr($timetable->teacher->name ?? 'E', 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $timetable->teacher->name ?? 'Non défini' }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-door-open me-1"></i> {{ $timetable->room ?? 'Non définie' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('timetables.show', $timetable->id) }}">
                                                        <i class="fas fa-eye text-primary me-2"></i> Voir
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('timetables.edit', $timetable->id) }}">
                                                        <i class="fas fa-edit text-warning me-2"></i> Modifier
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $timetable->id }}">
                                                        <i class="fas fa-trash text-danger me-2"></i> Supprimer
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Modal de suppression -->
                                        <div class="modal fade" id="deleteModal{{ $timetable->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Êtes-vous sûr de vouloir supprimer ce cours ?</p>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i> Cette action est irréversible.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <form action="{{ route('timetables.destroy', $timetable->id) }}" method="POST">
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
                                    <td colspan="9" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-3">
                                                <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                            </div>
                                            <h5 class="text-muted">Aucun cours trouvé</h5>
                                            <p class="text-muted small mb-0">Ajustez vos filtres ou ajoutez des cours pour voir les résultats</p>
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
                            <p class="text-muted small mb-0">Affichage de {{ $timetables->firstItem() ?? 0 }} à {{ $timetables->lastItem() ?? 0 }} sur {{ $timetables->total() ?? 0 }} cours</p>
                        </div>
                        <div>
                            {{ $timetables->withQueryString()->links() }}
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

    /* Style pour les cartes d'accès rapide */
    .quick-access-card {
        transition: all 0.3s ease;
        border-radius: 15px;
    }

    .quick-access-card:hover {
        transform: translateY(-5px);
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

    .subject-icon {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
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

<!-- Ajout des scripts JavaScript pour la redirection -->
<script>
    function viewClassTimetable() {
        const classId = document.getElementById('class_select').value;
        if (classId) {
            window.location.href = "{{ url('timetable/class') }}/" + classId;
        } else {
            alert('Veuillez sélectionner une classe');
        }
    }

    function viewTeacherTimetable() {
        const teacherId = document.getElementById('teacher_select').value;
        if (teacherId) {
            window.location.href = "{{ url('timetable/teacher') }}/" + teacherId;
        } else {
            alert('Veuillez sélectionner un enseignant');
        }
    }

    function printClassTimetable() {
        const classId = document.getElementById('print_class_select').value;
        if (classId) {
            window.location.href = "{{ url('timetable/class') }}/" + classId + "?print=1";
        } else {
            alert('Veuillez sélectionner une classe');
        }
    }
</script>
@endsection
