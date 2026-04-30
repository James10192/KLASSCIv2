@extends('layouts.app')

@section('title', 'Détails du Cycle')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
<div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Détails du Cycle</h1>
                    </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('esbtp.cycles.index') }}">Cycles</a></li>
                        <li class="breadcrumb-item active">Détails</li>
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
                            <h3 class="card-title">Informations du Cycle</h3>
                            <div class="card-tools">
                                @can('cycles.edit')
                                <a href="{{ route('esbtp.cycles.edit', $cycle->id) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                @endcan
                                @if(!$cycle->trashed())
                                    @can('cycles.delete')
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('{{ $cycle->id }}')">
                                        <i class="fas fa-trash"></i> Archiver
                                    </button>
                                    <form id="delete-form-{{ $cycle->id }}" action="{{ route('esbtp.cycles.destroy', $cycle->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endcan
                                @else
                                    @can('cycles.restore')
                                    <button type="button" class="btn btn-success btn-sm" onclick="confirmRestore('{{ $cycle->id }}')">
                                        <i class="fas fa-undo"></i> Restaurer
                                    </button>
                                    <form id="restore-form-{{ $cycle->id }}" action="{{ route('esbtp.cycles.restore', $cycle->id) }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                    @endcan
                                    @can('cycles.force_delete')
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmForceDelete('{{ $cycle->id }}')">
                                        <i class="fas fa-times"></i> Supprimer définitivement
                                    </button>
                                    <form id="force-delete-form-{{ $cycle->id }}" action="{{ route('esbtp.cycles.force-delete', $cycle->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endcan
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-graduation-cap"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Nom du Cycle</span>
                                            <span class="info-box-number">{{ $cycle->name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-code"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Code</span>
                                            <span class="info-box-number">{{ $cycle->code }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Durée</span>
                                            <span class="info-box-number">{{ $cycle->duration_years }} an(s)</span>
                                        </div>
                                                        </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-award"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Diplôme Délivré</span>
                                            <span class="info-box-number">{{ $cycle->diploma_awarded }}</span>
                                        </div>
                                    </div>
                                </div>
                        </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Description</h3>
                                        </div>
                                        <div class="card-body">
                                            {{ $cycle->description ?: 'Aucune description disponible.' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Statistiques</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="small-box bg-info">
                                                        <div class="inner">
                                                            <h3>{{ $cycle->specialties->count() }}</h3>
                                                            <p>Spécialités</p>
                                                        </div>
                                                        <div class="icon">
                                                            <i class="fas fa-book"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="small-box bg-success">
                                                        <div class="inner">
                                                            <h3>{{ $cycle->students->count() }}</h3>
                                                            <p>Étudiants</p>
                                                        </div>
                                                        <div class="icon">
                                                            <i class="fas fa-users"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="small-box bg-warning">
                                                        <div class="inner">
                                                            <h3>{{ $cycle->classes->count() }}</h3>
                                                            <p>Classes</p>
                                                        </div>
                                                        <div class="icon">
                                                            <i class="fas fa-chalkboard"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="small-box bg-danger">
                                                        <div class="inner">
                                                            <h3>{{ $cycle->teachers->count() }}</h3>
                                                            <p>Enseignants</p>
                                                        </div>
                                                        <div class="icon">
                                                            <i class="fas fa-chalkboard-teacher"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                </div>
                        <div class="card-footer">
                            <a href="{{ route('esbtp.cycles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Retour à la liste
                            </a>
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
