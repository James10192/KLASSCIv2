@extends('layouts.app')

@section('title', 'Départements ESBTP')
@section('page_title', 'Gestion des Départements')

@section('content')
<div class="main-content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-lg" role="alert">
            <strong><i class="fas fa-check-circle"></i></strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dashboard-header mb-xl">
        <div class="header-content">
            <h1 class="header-title">Gestion des Départements</h1>
            <p class="header-subtitle">Administration des départements de l'ESBTP</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.departments.create') }}" class="btn-acasi primary">
                <i class="fas fa-plus-circle"></i> Nouveau Département
            </a>
        </div>
    </div>

    <div class="card-moderne">
        <div class="p-lg">

            <div class="section-title mb-md">Liste des Départements</div>
            
            <ul class="nav nav-tabs mb-lg" id="departmentTabs" role="tablist" style="border-bottom: 2px solid var(--primary);">
                <li class="nav-item">
                    <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#active" role="tab" 
                       style="color: var(--primary); border-color: var(--primary) var(--primary) transparent;">
                        Actifs <span class="badge success">{{ $activeDepartments->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="inactive-tab" data-bs-toggle="tab" href="#inactive" role="tab"
                       style="color: var(--text-secondary);">
                        Inactifs <span class="badge warning">{{ $inactiveDepartments->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="archived-tab" data-bs-toggle="tab" href="#archived" role="tab"
                       style="color: var(--text-secondary);">
                        Archivés <span class="badge danger">{{ $archivedDepartments->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="departmentTabsContent">
                <!-- Active Departments -->
                <div class="tab-pane fade show active" id="active" role="tabpanel">
                    @if($activeDepartments->isEmpty())
                        <div class="text-center p-xl">
                            <i class="fas fa-building fa-3x color-neutral mb-lg"></i>
                            <h3 class="color-neutral">Aucun département actif</h3>
                            <p class="color-neutral">Aucun département actif n'a été trouvé dans le système.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover" style="border-collapse: separate; border-spacing: 0; border-radius: var(--radius-medium); overflow: hidden; box-shadow: var(--shadow-card);">
                                <thead style="background-color: var(--primary); color: white;">
                                    <tr>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Code</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Nom</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Chef de Département</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Contact</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody style="background-color: var(--surface);">
                                    @foreach($activeDepartments as $department)
                                        <tr style="border-bottom: 1px solid #f3f4f6;">
                                            <td style="padding: var(--space-md); font-weight: 600; color: var(--primary);">{{ $department->code }}</td>
                                            <td style="padding: var(--space-md); font-weight: 500;">{{ $department->name }}</td>
                                            <td style="padding: var(--space-md);">{{ $department->head_name ?: 'Non défini' }}</td>
                                            <td style="padding: var(--space-md); font-size: var(--text-small);">
                                                @if($department->email)
                                                    <div><strong>Email:</strong> {{ $department->email }}</div>
                                                @endif
                                                @if($department->phone)
                                                    <div><strong>Tél:</strong> {{ $department->phone }}</div>
                                                @endif
                                            </td>
                                            <td style="padding: var(--space-md);">
                                                <div style="display: flex; gap: var(--space-xs);">
                                                    <a href="{{ route('esbtp.departments.show', $department) }}" class="btn-acasi secondary" style="padding: var(--space-xs) var(--space-sm);">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-acasi" style="background-color: var(--warning); color: white; padding: var(--space-xs) var(--space-sm);">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-acasi" style="background-color: var(--danger); color: white; padding: var(--space-xs) var(--space-sm);" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?')">
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
                        <div class="text-center p-xl">
                            <i class="fas fa-pause-circle fa-3x color-warning mb-lg"></i>
                            <h3 class="color-warning">Aucun département inactif</h3>
                            <p class="color-neutral">Aucun département inactif n'a été trouvé dans le système.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover" style="border-collapse: separate; border-spacing: 0; border-radius: var(--radius-medium); overflow: hidden; box-shadow: var(--shadow-card);">
                                <thead style="background-color: var(--warning); color: white;">
                                    <tr>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Code</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Nom</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Chef de Département</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Contact</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody style="background-color: var(--surface);">
                                    @foreach($inactiveDepartments as $department)
                                        <tr style="border-bottom: 1px solid #f3f4f6;">
                                            <td style="padding: var(--space-md); font-weight: 600; color: var(--warning);">{{ $department->code }}</td>
                                            <td style="padding: var(--space-md); font-weight: 500;">{{ $department->name }}</td>
                                            <td style="padding: var(--space-md);">{{ $department->head_name ?: 'Non défini' }}</td>
                                            <td style="padding: var(--space-md); font-size: var(--text-small);">
                                                @if($department->email)
                                                    <div><strong>Email:</strong> {{ $department->email }}</div>
                                                @endif
                                                @if($department->phone)
                                                    <div><strong>Tél:</strong> {{ $department->phone }}</div>
                                                @endif
                                            </td>
                                            <td style="padding: var(--space-md);">
                                                <div style="display: flex; gap: var(--space-xs);">
                                                    <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-acasi" style="background-color: var(--warning); color: white; padding: var(--space-xs) var(--space-sm);">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-acasi" style="background-color: var(--danger); color: white; padding: var(--space-xs) var(--space-sm);" onclick="return confirm('Êtes-vous sûr de vouloir archiver ce département ?')">
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
                        <div class="text-center p-xl">
                            <i class="fas fa-archive fa-3x color-danger mb-lg"></i>
                            <h3 class="color-danger">Aucun département archivé</h3>
                            <p class="color-neutral">Aucun département archivé n'a été trouvé dans le système.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover" style="border-collapse: separate; border-spacing: 0; border-radius: var(--radius-medium); overflow: hidden; box-shadow: var(--shadow-card);">
                                <thead style="background-color: var(--danger); color: white;">
                                    <tr>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Code</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Nom</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Chef de Département</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Contact</th>
                                        <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody style="background-color: var(--surface);">
                                    @foreach($archivedDepartments as $department)
                                        <tr style="border-bottom: 1px solid #f3f4f6;">
                                            <td style="padding: var(--space-md); font-weight: 600; color: var(--danger);">{{ $department->code }}</td>
                                            <td style="padding: var(--space-md); font-weight: 500;">{{ $department->name }}</td>
                                            <td style="padding: var(--space-md);">{{ $department->head_name ?: 'Non défini' }}</td>
                                            <td style="padding: var(--space-md); font-size: var(--text-small);">
                                                @if($department->email)
                                                    <div><strong>Email:</strong> {{ $department->email }}</div>
                                                @endif
                                                @if($department->phone)
                                                    <div><strong>Tél:</strong> {{ $department->phone }}</div>
                                                @endif
                                            </td>
                                            <td style="padding: var(--space-md);">
                                                <div style="display: flex; gap: var(--space-xs);">
                                                    <form action="{{ route('esbtp.departments.restore', $department->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn-acasi" style="background-color: var(--success); color: white; padding: var(--space-xs) var(--space-sm);" onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce département ?')">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('esbtp.departments.force-delete', $department->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-acasi" style="background-color: var(--danger); color: white; padding: var(--space-xs) var(--space-sm);" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce département ?')">
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
@endsection
