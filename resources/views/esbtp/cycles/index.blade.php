@extends('layouts.app')

@section('title', 'Gestion des Cycles de Formation')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestion des Cycles de Formation</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Cycles de Formation</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {{ session('error') }}
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Cycles Actifs</h3>
                            <div class="card-tools">
                                @can('cycles.create')
                                <a href="{{ route('esbtp.cycles.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Nouveau Cycle
                                </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Code</th>
                                            <th>Durée (années)</th>
                                            <th>Diplôme</th>
                                            <th>Spécialités</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeCycles as $cycle)
                                            <tr>
                                                <td>{{ $cycle->id }}</td>
                                                <td>{{ $cycle->name }}</td>
                                                <td>{{ $cycle->code }}</td>
                                                <td>{{ $cycle->duration_years }} an(s)</td>
                                                <td>{{ $cycle->diploma_awarded }}</td>
                                                <td>
                                                    <span class="badge badge-info">{{ $cycle->specialties->count() }}</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        @can('cycles.view')
                                                        <a href="{{ route('esbtp.cycles.show', $cycle->id) }}" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @endcan
                                                        @can('cycles.edit')
                                                        <a href="{{ route('esbtp.cycles.edit', $cycle->id) }}" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @endcan
                                                        @can('cycles.delete')
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('{{ $cycle->id }}')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <form id="delete-form-{{ $cycle->id }}" action="{{ route('esbtp.cycles.destroy', $cycle->id) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Cycles Inactifs -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Cycles Inactifs</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Code</th>
                                            <th>Durée (années)</th>
                                            <th>Diplôme</th>
                                            <th>Spécialités</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($inactiveCycles as $cycle)
                                            <tr>
                                                <td>{{ $cycle->id }}</td>
                                                <td>{{ $cycle->name }}</td>
                                                <td>{{ $cycle->code }}</td>
                                                <td>{{ $cycle->duration_years }} an(s)</td>
                                                <td>{{ $cycle->diploma_awarded }}</td>
                                                <td>
                                                    <span class="badge badge-info">{{ $cycle->specialties->count() }}</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        @can('cycles.view')
                                                        <a href="{{ route('esbtp.cycles.show', $cycle->id) }}" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @endcan
                                                        @can('cycles.edit')
                                                        <a href="{{ route('esbtp.cycles.edit', $cycle->id) }}" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @endcan
                                                        @can('cycles.delete')
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('{{ $cycle->id }}')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <form id="delete-form-{{ $cycle->id }}" action="{{ route('esbtp.cycles.destroy', $cycle->id) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Cycles Archivés -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Cycles Archivés</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Code</th>
                                            <th>Durée (années)</th>
                                            <th>Diplôme</th>
                                            <th>Spécialités</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($archivedCycles as $cycle)
                                            <tr>
                                                <td>{{ $cycle->id }}</td>
                                                <td>{{ $cycle->name }}</td>
                                                <td>{{ $cycle->code }}</td>
                                                <td>{{ $cycle->duration_years }} an(s)</td>
                                                <td>{{ $cycle->diploma_awarded }}</td>
                                                <td>
                                                    <span class="badge badge-info">{{ $cycle->specialties->count() }}</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        @can('cycles.view')
                                                        <a href="{{ route('esbtp.cycles.show', $cycle->id) }}" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @endcan
                                                        @can('cycles.restore')
                                                        <button type="button" class="btn btn-success btn-sm" onclick="confirmRestore('{{ $cycle->id }}')">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                        <form id="restore-form-{{ $cycle->id }}" action="{{ route('esbtp.cycles.restore', $cycle->id) }}" method="POST" style="display: none;">
                                                            @csrf
                                                        </form>
                                                        @endcan
                                                        @can('cycles.force_delete')
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmForceDelete('{{ $cycle->id }}')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <form id="force-delete-form-{{ $cycle->id }}" action="{{ route('esbtp.cycles.force-delete', $cycle->id) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir archiver ce cycle ?')) {
        document.getElementById('delete-form-' + id).submit();
    }
}

function confirmRestore(id) {
    if (confirm('Êtes-vous sûr de vouloir restaurer ce cycle ?')) {
        document.getElementById('restore-form-' + id).submit();
    }
}

function confirmForceDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement ce cycle ? Cette action est irréversible.')) {
        document.getElementById('force-delete-form-' + id).submit();
    }
}
</script>
@endpush
