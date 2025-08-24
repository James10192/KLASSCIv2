@extends('layouts.app')

@section('title', 'Départements ESBTP')
@section('page_title', 'Gestion des Départements')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-building me-2"></i>Gestion des Départements</h1>
                <p class="header-subtitle">Administration des départements de l'ESBTP</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.departments.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouveau Département
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des Départements
                </div>
                <div class="main-card-subtitle">{{ $activeDepartments->count() + $inactiveDepartments->count() + $archivedDepartments->count() }} départements au total</div>
            </div>
            <div class="main-card-body">
                <ul class="nav nav-tabs mb-4" id="departmentTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#active" role="tab">
                            <i class="fas fa-check-circle me-1"></i>Actifs 
                            <span class="status-badge success ms-2">{{ $activeDepartments->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="inactive-tab" data-bs-toggle="tab" href="#inactive" role="tab">
                            <i class="fas fa-pause-circle me-1"></i>Inactifs 
                            <span class="status-badge warning ms-2">{{ $inactiveDepartments->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="archived-tab" data-bs-toggle="tab" href="#archived" role="tab">
                            <i class="fas fa-archive me-1"></i>Archivés 
                            <span class="status-badge danger ms-2">{{ $archivedDepartments->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="departmentTabsContent">
                    <!-- Active Departments -->
                    <div class="tab-pane fade show active" id="active" role="tabpanel">
                        @if($activeDepartments->isEmpty())
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-building fs-1 mb-3 d-block"></i>
                                    <h3 class="color-neutral">Aucun département actif</h3>
                                    <p class="color-neutral">Aucun département actif n'a été trouvé dans le système.</p>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-top-0 py-3">Code</th>
                                            <th class="border-top-0 py-3">Nom du Département</th>
                                            <th class="border-top-0 py-3">Chef de Département</th>
                                            <th class="border-top-0 py-3">Contact</th>
                                            <th class="border-top-0 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeDepartments as $department)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold color-primary">{{ $department->code }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="fas fa-building fs-4 color-primary"></i>
                                                        </div>
                                                        <div>
                                                            <span class="fw-medium">{{ $department->name }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($department->head_name)
                                                        <i class="fas fa-user color-neutral me-1"></i>
                                                        {{ $department->head_name }}
                                                    @else
                                                        <span class="text-muted">Non défini</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($department->email || $department->phone)
                                                        @if($department->email)
                                                            <div class="small">
                                                                <i class="fas fa-envelope color-neutral me-1"></i>
                                                                {{ $department->email }}
                                                            </div>
                                                        @endif
                                                        @if($department->phone)
                                                            <div class="small">
                                                                <i class="fas fa-phone color-neutral me-1"></i>
                                                                {{ $department->phone }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Aucun contact</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <a href="{{ route('esbtp.departments.show', $department) }}" class="btn-acasi secondary btn-sm" data-bs-toggle="tooltip" title="Voir les détails">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-acasi warning btn-sm" data-bs-toggle="tooltip" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-acasi danger btn-sm" data-bs-toggle="tooltip" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Inactive Departments -->
                    <div class="tab-pane fade" id="inactive" role="tabpanel">
                        @if($inactiveDepartments->isEmpty())
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-pause-circle fs-1 mb-3 d-block color-warning"></i>
                                    <h3 class="color-warning">Aucun département inactif</h3>
                                    <p class="color-neutral">Aucun département inactif n'a été trouvé dans le système.</p>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-top-0 py-3">Code</th>
                                            <th class="border-top-0 py-3">Nom du Département</th>
                                            <th class="border-top-0 py-3">Chef de Département</th>
                                            <th class="border-top-0 py-3">Contact</th>
                                            <th class="border-top-0 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($inactiveDepartments as $department)
                                            <tr class="opacity-75">
                                                <td>
                                                    <span class="fw-bold color-warning">{{ $department->code }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="fas fa-building fs-4 color-warning"></i>
                                                        </div>
                                                        <div>
                                                            <span class="fw-medium">{{ $department->name }}</span>
                                                            <div><small class="status-badge warning">Inactif</small></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($department->head_name)
                                                        <i class="fas fa-user color-neutral me-1"></i>
                                                        {{ $department->head_name }}
                                                    @else
                                                        <span class="text-muted">Non défini</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($department->email || $department->phone)
                                                        @if($department->email)
                                                            <div class="small">
                                                                <i class="fas fa-envelope color-neutral me-1"></i>
                                                                {{ $department->email }}
                                                            </div>
                                                        @endif
                                                        @if($department->phone)
                                                            <div class="small">
                                                                <i class="fas fa-phone color-neutral me-1"></i>
                                                                {{ $department->phone }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Aucun contact</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-acasi warning btn-sm" data-bs-toggle="tooltip" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-acasi danger btn-sm" data-bs-toggle="tooltip" title="Archiver" onclick="return confirm('Êtes-vous sûr de vouloir archiver ce département ?')">
                                                                <i class="fas fa-archive"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Archived Departments -->
                    <div class="tab-pane fade" id="archived" role="tabpanel">
                        @if($archivedDepartments->isEmpty())
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-archive fs-1 mb-3 d-block color-danger"></i>
                                    <h3 class="color-danger">Aucun département archivé</h3>
                                    <p class="color-neutral">Aucun département archivé n'a été trouvé dans le système.</p>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-top-0 py-3">Code</th>
                                            <th class="border-top-0 py-3">Nom du Département</th>
                                            <th class="border-top-0 py-3">Chef de Département</th>
                                            <th class="border-top-0 py-3">Contact</th>
                                            <th class="border-top-0 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($archivedDepartments as $department)
                                            <tr class="opacity-50">
                                                <td>
                                                    <span class="fw-bold color-danger">{{ $department->code }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="fas fa-building fs-4 color-danger"></i>
                                                        </div>
                                                        <div>
                                                            <span class="fw-medium">{{ $department->name }}</span>
                                                            <div><small class="status-badge danger">Archivé</small></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($department->head_name)
                                                        <i class="fas fa-user color-neutral me-1"></i>
                                                        {{ $department->head_name }}
                                                    @else
                                                        <span class="text-muted">Non défini</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($department->email || $department->phone)
                                                        @if($department->email)
                                                            <div class="small">
                                                                <i class="fas fa-envelope color-neutral me-1"></i>
                                                                {{ $department->email }}
                                                            </div>
                                                        @endif
                                                        @if($department->phone)
                                                            <div class="small">
                                                                <i class="fas fa-phone color-neutral me-1"></i>
                                                                {{ $department->phone }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Aucun contact</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <form action="{{ route('esbtp.departments.restore', $department->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn-acasi success btn-sm" data-bs-toggle="tooltip" title="Restaurer" onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce département ?')">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('esbtp.departments.force-delete', $department->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-acasi danger btn-sm" data-bs-toggle="tooltip" title="Supprimer définitivement" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce département ?')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialisation des tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
