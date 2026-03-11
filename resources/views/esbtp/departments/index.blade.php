@extends('layouts.app')

@section('title', 'Départements')
@section('page_title', 'Gestion des Départements')

@push('styles')
<style>
/* ===== DEPARTMENTS INDEX — PREMIUM REDESIGN ===== */

/* --- Tab Pills --- */
.dept-tabs-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.dept-tab-pills {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    border-radius: 12px;
    padding: 4px;
    border: none;
    flex-wrap: wrap;
}

.dept-tab-pills .nav-item {
    list-style: none;
}

.dept-tab-pills .nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    border: none;
    background: transparent;
    transition: all 0.2s ease;
    white-space: nowrap;
    cursor: pointer;
}

.dept-tab-pills .nav-link:hover {
    color: #1e293b;
    background: rgba(255,255,255,0.6);
}

.dept-tab-pills .nav-link.active {
    background: #fff;
    color: #0453cb;
    box-shadow: 0 1px 4px rgba(4, 83, 203, 0.15), 0 0 0 1px rgba(4, 83, 203, 0.08);
}

.dept-tab-pills .tab-pill-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1;
}

.dept-tab-pills .nav-link.active .tab-pill-badge.badge-success {
    background: #dcfce7;
    color: #16a34a;
}
.dept-tab-pills .nav-link:not(.active) .tab-pill-badge.badge-success {
    background: #e2e8f0;
    color: #64748b;
}

.dept-tab-pills .nav-link.active .tab-pill-badge.badge-warning {
    background: #fef3c7;
    color: #d97706;
}
.dept-tab-pills .nav-link:not(.active) .tab-pill-badge.badge-warning {
    background: #e2e8f0;
    color: #64748b;
}

.dept-tab-pills .nav-link.active .tab-pill-badge.badge-danger {
    background: #fee2e2;
    color: #dc2626;
}
.dept-tab-pills .nav-link:not(.active) .tab-pill-badge.badge-danger {
    background: #e2e8f0;
    color: #64748b;
}

/* --- Search Bar --- */
.dept-search-bar {
    position: relative;
    min-width: 220px;
    max-width: 300px;
    flex: 1;
}

.dept-search-bar input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.875rem;
    color: #1e293b;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
}

.dept-search-bar input:focus {
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
}

.dept-search-bar input::placeholder {
    color: #94a3b8;
}

.dept-search-bar .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.8rem;
    pointer-events: none;
}

/* --- Table Premium --- */
.dept-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.dept-table thead tr {
    background: linear-gradient(135deg, #f8faff 0%, #eef3fb 100%);
    border-top: 2px solid #0453cb;
}

.dept-table thead th {
    padding: 13px 16px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.dept-table tbody tr {
    transition: background 0.15s ease;
    border-bottom: 1px solid #f1f5f9;
}

.dept-table tbody tr:hover {
    background: #f8faff;
}

.dept-table tbody tr:last-child {
    border-bottom: none;
}

.dept-table td {
    padding: 14px 16px;
    vertical-align: middle;
    font-size: 0.875rem;
    color: #1e293b;
}

/* --- Department Code Badge --- */
.dept-code-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    font-family: 'Courier New', monospace;
    border: 1.5px solid;
}

.dept-code-badge.code-active {
    background: #eff6ff;
    color: #0453cb;
    border-color: rgba(4, 83, 203, 0.2);
}

.dept-code-badge.code-inactive {
    background: #fffbeb;
    color: #d97706;
    border-color: rgba(217, 119, 6, 0.2);
}

.dept-code-badge.code-archived {
    background: #fef2f2;
    color: #dc2626;
    border-color: rgba(220, 38, 38, 0.2);
}

/* --- Department Avatar --- */
.dept-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.8rem;
    letter-spacing: -0.02em;
    flex-shrink: 0;
}

.dept-avatar.avatar-active {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: #fff;
}

.dept-avatar.avatar-inactive {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    color: #fff;
}

.dept-avatar.avatar-archived {
    background: linear-gradient(135deg, #94a3b8 0%, #cbd5e1 100%);
    color: #fff;
}

.dept-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.dept-name-info .dept-name {
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
}

.dept-name-info .dept-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
}

.dept-status-badge.status-inactive {
    background: #fef3c7;
    color: #d97706;
}

.dept-status-badge.status-archived {
    background: #fee2e2;
    color: #dc2626;
}

/* --- Contact Cell --- */
.dept-contact-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #475569;
    line-height: 1.5;
}

.dept-contact-item i {
    width: 14px;
    color: #94a3b8;
    flex-shrink: 0;
}

/* --- Head column --- */
.dept-head-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: #374151;
}

.dept-head-cell i {
    color: #94a3b8;
    font-size: 0.8rem;
}

/* --- Empty State Premium --- */
.dept-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 56px 24px;
    text-align: center;
}

.dept-empty-icon-wrap {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 20px;
}

.dept-empty-icon-wrap.empty-active {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #0453cb;
    border: 1px solid rgba(4, 83, 203, 0.15);
}

.dept-empty-icon-wrap.empty-inactive {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #d97706;
    border: 1px solid rgba(217, 119, 6, 0.15);
}

.dept-empty-icon-wrap.empty-archived {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, 0.15);
}

.dept-empty-state h4 {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.dept-empty-state p {
    font-size: 0.875rem;
    color: #64748b;
    max-width: 300px;
    margin: 0;
}

/* --- Row opacity for inactive/archived --- */
.dept-table tbody tr.row-inactive {
    opacity: 0.85;
}

.dept-table tbody tr.row-archived {
    opacity: 0.65;
}

/* --- No result (live filter) --- */
.dept-no-filter-result {
    display: none;
    padding: 32px 24px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.875rem;
}

/* --- Responsive --- */
@media (max-width: 768px) {
    .dept-tabs-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    .dept-search-bar {
        max-width: 100%;
    }
    .dept-table thead th:nth-child(3),
    .dept-table tbody td:nth-child(3),
    .dept-table thead th:nth-child(4),
    .dept-table tbody td:nth-child(4) {
        display: none;
    }
}

@media (max-width: 576px) {
    .dept-tab-pills {
        width: 100%;
    }
    .dept-tab-pills .nav-link {
        flex: 1;
        justify-content: center;
        padding: 9px 10px;
        font-size: 0.8rem;
    }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-building me-2"></i>Gestion des Départements</h1>
                <p class="header-subtitle">Administration des départements de l'établissement</p>
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

                <!-- Tabs + Search Bar -->
                <div class="dept-tabs-wrapper">
                    <ul class="dept-tab-pills nav" id="departmentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#active" role="tab" aria-controls="active" aria-selected="true">
                                <i class="fas fa-check-circle"></i>
                                <span>Actifs</span>
                                <span class="tab-pill-badge badge-success">{{ $activeDepartments->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="inactive-tab" data-bs-toggle="tab" href="#inactive" role="tab" aria-controls="inactive" aria-selected="false">
                                <i class="fas fa-pause-circle"></i>
                                <span>Inactifs</span>
                                <span class="tab-pill-badge badge-warning">{{ $inactiveDepartments->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="archived-tab" data-bs-toggle="tab" href="#archived" role="tab" aria-controls="archived" aria-selected="false">
                                <i class="fas fa-archive"></i>
                                <span>Archivés</span>
                                <span class="tab-pill-badge badge-danger">{{ $archivedDepartments->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="dept-search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="deptSearchInput" placeholder="Rechercher un département…" autocomplete="off">
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content" id="departmentTabsContent">

                    <!-- ===== ACTIVE DEPARTMENTS ===== -->
                    <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                        @if($activeDepartments->isEmpty())
                            <div class="dept-empty-state">
                                <div class="dept-empty-icon-wrap empty-active">
                                    <i class="fas fa-building"></i>
                                </div>
                                <h4>Aucun département actif</h4>
                                <p>Aucun département actif n'a été trouvé dans le système. Créez votre premier département.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="dept-table" id="tableActive">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom du Département</th>
                                            <th>Chef de Département</th>
                                            <th>Contact</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeDepartments as $department)
                                            <tr data-name="{{ strtolower($department->name) }} {{ strtolower($department->code) }}">
                                                <td>
                                                    <span class="dept-code-badge code-active">{{ $department->code }}</span>
                                                </td>
                                                <td>
                                                    <div class="dept-name-cell">
                                                        <div class="dept-avatar avatar-active">
                                                            {{ strtoupper(substr($department->code ?? $department->name, 0, 2)) }}
                                                        </div>
                                                        <div class="dept-name-info">
                                                            <div class="dept-name">{{ $department->name }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($department->head_name)
                                                        <div class="dept-head-cell">
                                                            <i class="fas fa-user-tie"></i>
                                                            {{ $department->head_name }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($department->email || $department->phone)
                                                        @if($department->email)
                                                            <div class="dept-contact-item">
                                                                <i class="fas fa-envelope"></i>
                                                                {{ $department->email }}
                                                            </div>
                                                        @endif
                                                        @if($department->phone)
                                                            <div class="dept-contact-item">
                                                                <i class="fas fa-phone"></i>
                                                                {{ $department->phone }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
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
                                <div class="dept-no-filter-result" id="noResultActive">
                                    <i class="fas fa-search me-2"></i>Aucun département ne correspond à votre recherche.
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- ===== INACTIVE DEPARTMENTS ===== -->
                    <div class="tab-pane fade" id="inactive" role="tabpanel" aria-labelledby="inactive-tab">
                        @if($inactiveDepartments->isEmpty())
                            <div class="dept-empty-state">
                                <div class="dept-empty-icon-wrap empty-inactive">
                                    <i class="fas fa-pause-circle"></i>
                                </div>
                                <h4>Aucun département inactif</h4>
                                <p>Tous les départements sont actifs. Les départements désactivés apparaîtront ici.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="dept-table" id="tableInactive">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom du Département</th>
                                            <th>Chef de Département</th>
                                            <th>Contact</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($inactiveDepartments as $department)
                                            <tr class="row-inactive" data-name="{{ strtolower($department->name) }} {{ strtolower($department->code) }}">
                                                <td>
                                                    <span class="dept-code-badge code-inactive">{{ $department->code }}</span>
                                                </td>
                                                <td>
                                                    <div class="dept-name-cell">
                                                        <div class="dept-avatar avatar-inactive">
                                                            {{ strtoupper(substr($department->code ?? $department->name, 0, 2)) }}
                                                        </div>
                                                        <div class="dept-name-info">
                                                            <div class="dept-name">{{ $department->name }}</div>
                                                            <span class="dept-status-badge status-inactive">
                                                                <i class="fas fa-pause" style="font-size:0.6rem;"></i> Inactif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($department->head_name)
                                                        <div class="dept-head-cell">
                                                            <i class="fas fa-user-tie"></i>
                                                            {{ $department->head_name }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($department->email || $department->phone)
                                                        @if($department->email)
                                                            <div class="dept-contact-item">
                                                                <i class="fas fa-envelope"></i>
                                                                {{ $department->email }}
                                                            </div>
                                                        @endif
                                                        @if($department->phone)
                                                            <div class="dept-contact-item">
                                                                <i class="fas fa-phone"></i>
                                                                {{ $department->phone }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
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
                                <div class="dept-no-filter-result" id="noResultInactive">
                                    <i class="fas fa-search me-2"></i>Aucun département ne correspond à votre recherche.
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- ===== ARCHIVED DEPARTMENTS ===== -->
                    <div class="tab-pane fade" id="archived" role="tabpanel" aria-labelledby="archived-tab">
                        @if($archivedDepartments->isEmpty())
                            <div class="dept-empty-state">
                                <div class="dept-empty-icon-wrap empty-archived">
                                    <i class="fas fa-archive"></i>
                                </div>
                                <h4>Aucun département archivé</h4>
                                <p>Aucun département archivé dans le système. Les départements supprimés apparaîtront ici.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="dept-table" id="tableArchived">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom du Département</th>
                                            <th>Chef de Département</th>
                                            <th>Contact</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($archivedDepartments as $department)
                                            <tr class="row-archived" data-name="{{ strtolower($department->name) }} {{ strtolower($department->code) }}">
                                                <td>
                                                    <span class="dept-code-badge code-archived">{{ $department->code }}</span>
                                                </td>
                                                <td>
                                                    <div class="dept-name-cell">
                                                        <div class="dept-avatar avatar-archived">
                                                            {{ strtoupper(substr($department->code ?? $department->name, 0, 2)) }}
                                                        </div>
                                                        <div class="dept-name-info">
                                                            <div class="dept-name">{{ $department->name }}</div>
                                                            <span class="dept-status-badge status-archived">
                                                                <i class="fas fa-archive" style="font-size:0.6rem;"></i> Archivé
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($department->head_name)
                                                        <div class="dept-head-cell">
                                                            <i class="fas fa-user-tie"></i>
                                                            {{ $department->head_name }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($department->email || $department->phone)
                                                        @if($department->email)
                                                            <div class="dept-contact-item">
                                                                <i class="fas fa-envelope"></i>
                                                                {{ $department->email }}
                                                            </div>
                                                        @endif
                                                        @if($department->phone)
                                                            <div class="dept-contact-item">
                                                                <i class="fas fa-phone"></i>
                                                                {{ $department->phone }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
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
                                <div class="dept-no-filter-result" id="noResultArchived">
                                    <i class="fas fa-search me-2"></i>Aucun département ne correspond à votre recherche.
                                </div>
                            </div>
                        @endif
                    </div>

                </div>{{-- end tab-content --}}
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

        // Live search filter
        function filterTable(q) {
            var activePane = $('#departmentTabsContent .tab-pane.active');
            var rows = activePane.find('table tbody tr');
            var noResult = activePane.find('.dept-no-filter-result');

            if (!rows.length) return;

            var visible = 0;
            rows.each(function() {
                var name = $(this).data('name') || '';
                if (!q || name.indexOf(q) !== -1) {
                    $(this).show();
                    visible++;
                } else {
                    $(this).hide();
                }
            });

            if (visible === 0 && q) {
                noResult.show();
            } else {
                noResult.hide();
            }
        }

        $('#deptSearchInput').on('input', function() {
            filterTable($(this).val().toLowerCase().trim());
        });

        // Re-apply search on tab change
        $('[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            filterTable($('#deptSearchInput').val().toLowerCase().trim());
        });
    });
</script>
@endpush
