@extends('layouts.app')

@section('title', 'Semestres')
@section('page_title', 'Gestion des Semestres')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Liste des Semestres</h5>
        <a href="{{ route('esbtp.semesters.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau semestre
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Année d'études</th>
                        <th>Ordre</th>
                        <th>Nombre de cours</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($semesters as $semester)
                        <tr class="{{ $semester->trashed() ? 'table-danger' : '' }}">
                            <td>{{ $semester->name }}</td>
                            <td>
                                @if($semester->studyYear)
                                    <a href="{{ route('esbtp.study-years.show', $semester->studyYear) }}">
                                        {{ $semester->studyYear->name }}
                                    </a>
                                @else
                                    <span class="text-muted">Non défini</span>
                                @endif
                            </td>
                            <td>{{ $semester->order }}</td>
                            <td>
                                <span class="badge bg-info">{{ $semester->courses_count ?? 0 }}</span>
                            </td>
                            <td>
                                @if($semester->trashed())
                                    <span class="badge bg-danger">Supprimé</span>
                                @elseif($semester->is_active)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-warning">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    @if($semester->trashed())
                                        <form action="{{ route('esbtp.semesters.restore', $semester->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-success btn-sm" title="Restaurer">
                                                <i class="fas fa-trash-restore"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('esbtp.semesters.force-delete', $semester->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer définitivement" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce semestre ? Cette action est irréversible.')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('esbtp.semesters.show', $semester) }}" class="btn btn-info btn-sm" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.semesters.edit', $semester) }}" class="btn btn-warning btn-sm" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('esbtp.semesters.destroy', $semester) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce semestre ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Aucun semestre trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($semesters->hasPages())
            <div class="mt-4">
                {{ $semesters->links() }}
            </div>
        @endif
    </div>
</div>
@endsection 