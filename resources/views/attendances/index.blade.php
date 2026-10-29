@extends('layouts.app')

@section('title', 'Gestion des Présences')

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
                                <h2 class="fw-bold mb-0">Gestion des présences</h2>
                                <a href="{{ route('attendance.mark-page') }}" class="btn btn-success px-4">
                                    <i class="fas fa-clipboard-check me-2"></i> Marquer les présences
                                </a>
                            </div>
                            <p class="text-muted mb-4">Suivez la présence des étudiants, consultez les statistiques et gérez les justificatifs d'absence.</p>

                            <div class="d-flex gap-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-2">
                                        <i class="fas fa-users text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalStudents ?? 0 }}</h6>
                                        <small class="text-muted">Étudiants</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-success-light rounded-circle p-2 me-2">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $presentCount ?? 0 }}</h6>
                                        <small class="text-muted">Présents</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-danger-light rounded-circle p-2 me-2">
                                        <i class="fas fa-times-circle text-danger"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $absentCount ?? 0 }}</h6>
                                        <small class="text-muted">Absents</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/time-management-concept-illustration_114360-1013.jpg" alt="Attendance" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
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
                    <form action="{{ route('attendances.index') }}" method="GET" class="row g-3">
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
                            <label for="date" class="form-label small text-muted">Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-calendar-alt text-muted"></i>
                                </span>
                                <input type="date" class="form-control border-0 bg-light" id="date" name="date" value="{{ request('date', date('Y-m-d')) }}">
                            </div>
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

    <!-- Statistiques de présence -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Total étudiants</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $totalStudents ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-primary-light rounded-circle p-3">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Présents</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $presentCount ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-success-light rounded-circle p-3">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($presentCount ?? 0) > 0 ? (($presentCount ?? 0) / ($totalStudents ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Taux de présence: {{ ($totalStudents ?? 0) > 0 ? round((($presentCount ?? 0) / ($totalStudents ?? 1)) * 100) : 0 }}%</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Absents</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $absentCount ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-danger-light rounded-circle p-3">
                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ ($absentCount ?? 0) > 0 ? (($absentCount ?? 0) / ($totalStudents ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Taux d'absence: {{ ($totalStudents ?? 0) > 0 ? round((($absentCount ?? 0) / ($totalStudents ?? 1)) * 100) : 0 }}%</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Justifiés</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $justifiedCount ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-warning-light rounded-circle p-3">
                            <i class="fas fa-file-alt text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ ($absentCount ?? 0) > 0 ? (($justifiedCount ?? 0) / ($absentCount ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Absences justifiées: {{ ($absentCount ?? 0) > 0 ? round((($justifiedCount ?? 0) / ($absentCount ?? 1)) * 100) : 0 }}%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des présences -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-list text-primary me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Liste des présences</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">#</th>
                                    <th scope="col">Étudiant</th>
                                    <th scope="col">Classe</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col">Justification</th>
                                    <th scope="col" class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances ?? [] as $attendance)
                                <tr>
                                    <td class="ps-4">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary-light text-primary me-2">
                                                {{ strtoupper(substr($attendance->student->name ?? 'E', 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $attendance->student->name ?? 'Non défini' }}</h6>
                                                <small class="text-muted">ID: {{ $attendance->student->student_id ?? 'Non défini' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $attendance->class->name ?? 'Non définie' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y') }}</td>
                                    <td>
                                        @if($attendance->status == 'present')
                                            <span class="badge bg-success-light text-success">Présent</span>
                                        @elseif($attendance->status == 'absent')
                                            <span class="badge bg-danger-light text-danger">Absent</span>
                                        @elseif($attendance->status == 'late')
                                            <span class="badge bg-warning-light text-warning">En retard</span>
                                        @else
                                            <span class="badge bg-secondary-light text-secondary">Inconnu</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->is_justified)
                                            <span class="badge bg-info-light text-info">Justifié</span>
                                        @elseif($attendance->status == 'absent')
                                            <span class="badge bg-light text-dark">Non justifié</span>
                                        @else
                                            <span class="badge bg-light text-dark">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attendances.show', $attendance->id) }}">
                                                        <i class="fas fa-eye text-primary me-2"></i> Voir
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attendances.edit', $attendance->id) }}">
                                                        <i class="fas fa-edit text-warning me-2"></i> Modifier
                                                    </a>
                                                </li>
                                                @if($attendance->status == 'absent' && !$attendance->is_justified)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attendances.justify', $attendance->id) }}">
                                                        <i class="fas fa-file-alt text-info me-2"></i> Justifier
                                                    </a>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-3">
                                                <i class="fas fa-clipboard-list fa-3x text-muted"></i>
                                            </div>
                                            <h5 class="text-muted">Aucune présence trouvée</h5>
                                            <p class="text-muted small mb-0">Ajustez vos filtres ou marquez les présences pour aujourd'hui</p>
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
                            <p class="text-muted small mb-0">Affichage de {{ $attendances->firstItem() ?? 0 }} à {{ $attendances->lastItem() ?? 0 }} sur {{ $attendances->total() ?? 0 }} présences</p>
                        </div>
                        <div>
                            {{ $attendances->withQueryString()->links() }}
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
