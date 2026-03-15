@extends('layouts.app')

@section('title', 'Années d\'études')
@section('page_title', 'Gestion des Années d\'études')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Liste des Années d'études</h5>
        <a href="{{ route('esbtp.study-years.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle année d'études
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
                        <th>Intitulé</th>
                        <th>Cycle</th>
                        <th>Spécialité</th>
                        <th>Semestres</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studyYears as $studyYear)
                        <tr class="{{ $studyYear->trashed() ? 'table-danger' : '' }}">
                            <td>{{ $studyYear->name }}</td>
                            <td>{{ $studyYear->cycle->name ?? 'Non défini' }}</td>
                            <td>{{ $studyYear->specialty->name ?? 'Non défini' }}</td>
                            <td>
                                <span class="badge bg-info">{{ $studyYear->semesters_count ?? $studyYear->semesters->count() }} semestre(s)</span>
                            </td>
                            <td>
                                @if($studyYear->trashed())
                                    <span class="badge bg-danger">Supprimé</span>
                                @elseif($studyYear->is_active)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-warning">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    @if($studyYear->trashed())
                                        <form action="{{ route('esbtp.study-years.restore', $studyYear->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-success btn-sm" title="Restaurer">
                                                <i class="fas fa-trash-restore"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('esbtp.study-years.force-delete', $studyYear->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer définitivement" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette année d\'études ? Cette action est irréversible.')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('esbtp.study-years.show', $studyYear) }}" class="btn btn-info btn-sm" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.study-years.edit', $studyYear) }}" class="btn btn-warning btn-sm" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('esbtp.study-years.destroy', $studyYear) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette année d\'études ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Aucune année d'études trouvée</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($studyYears->hasPages())
            <div class="mt-4">
                {{ $studyYears->links() }}
            </div>
        @endif
    </div>
</div>
@endsection 