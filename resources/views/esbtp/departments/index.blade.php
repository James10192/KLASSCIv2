@extends('layouts.app')

@section('title', 'Départements ESBTP')
@section('page_title', 'Gestion des Départements')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des Départements</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.departments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Nouveau Département
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <ul class="nav nav-tabs" id="departmentTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#active" role="tab">
                                Actifs <span class="badge bg-primary">{{ $activeDepartments->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="inactive-tab" data-bs-toggle="tab" href="#inactive" role="tab">
                                Inactifs <span class="badge bg-warning">{{ $inactiveDepartments->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="archived-tab" data-bs-toggle="tab" href="#archived" role="tab">
                                Archivés <span class="badge bg-danger">{{ $archivedDepartments->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="departmentTabsContent">
                        <!-- Active Departments -->
                        <div class="tab-pane fade show active" id="active" role="tabpanel">
                            @if($activeDepartments->isEmpty())
                                <div class="alert alert-info">Aucun département actif trouvé.</div>
                            @else
        <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                                                <th>Code</th>
                        <th>Nom</th>
                                                <th>Chef de Département</th>
                                                <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                                            @foreach($activeDepartments as $department)
                                                <tr>
                                                    <td>{{ $department->code }}</td>
                                                    <td>{{ $department->name }}</td>
                                                    <td>{{ $department->head_name ?: 'Non défini' }}</td>
                                                    <td>
                                                        @if($department->email)
                                                            <strong>Email:</strong> {{ $department->email }}<br>
                                @endif
                                                        @if($department->phone)
                                                            <strong>Tél:</strong> {{ $department->phone }}
                                @endif
                            </td>
                            <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('esbtp.departments.show', $department) }}" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?')">
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
                                <div class="alert alert-info">Aucun département inactif trouvé.</div>
                                    @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Nom</th>
                                                <th>Chef de Département</th>
                                                <th>Contact</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inactiveDepartments as $department)
                                                <tr>
                                                    <td>{{ $department->code }}</td>
                                                    <td>{{ $department->name }}</td>
                                                    <td>{{ $department->head_name ?: 'Non défini' }}</td>
                                                    <td>
                                                        @if($department->email)
                                                            <strong>Email:</strong> {{ $department->email }}<br>
                                                        @endif
                                                        @if($department->phone)
                                                            <strong>Tél:</strong> {{ $department->phone }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir archiver ce département ?')">
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
                                <div class="alert alert-info">Aucun département archivé trouvé.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Nom</th>
                                                <th>Chef de Département</th>
                                                <th>Contact</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($archivedDepartments as $department)
                                                <tr>
                                                    <td>{{ $department->code }}</td>
                                                    <td>{{ $department->name }}</td>
                                                    <td>{{ $department->head_name ?: 'Non défini' }}</td>
                                                    <td>
                                                        @if($department->email)
                                                            <strong>Email:</strong> {{ $department->email }}<br>
                                                        @endif
                                                        @if($department->phone)
                                                            <strong>Tél:</strong> {{ $department->phone }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <form action="{{ route('esbtp.departments.restore', $department->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce département ?')">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            </form>
                                                            <form action="{{ route('esbtp.departments.force-delete', $department->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce département ?')">
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
</div>
@endsection
