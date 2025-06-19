@extends('layouts.app')

@section('title', 'Gestion des enseignants')

@section('content')
<div class="container-fluid px-4">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="fas fa-chalkboard-teacher fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Gestion des enseignants</h1>
                <div class="text-white-50">Liste et gestion des enseignants de l'établissement</div>
            </div>
        </div>
        <a href="{{ route('esbtp.teachers.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
            <i class="fas fa-plus"></i> Ajouter un enseignant
        </a>
    </div>

    <div class="container-fluid animate-fade-in-up">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-md-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass mb-4">
                    <div class="card-body p-0">
                        @if(session('success'))
                            <div class="alert alert-success d-flex align-items-center glass-alert mb-4">
                                <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                                <div>{{ session('success') }}</div>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger d-flex align-items-center glass-alert mb-4">
                                <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                                <div>{{ session('error') }}</div>
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-hover align-middle premium-table mb-0">
                                <thead class="sticky-top bg-gradient-primary text-white rounded-top-4">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Nom d'utilisateur</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($teachers as $teacher)
                                        <tr>
                                            <td>{{ $teacher->id }}</td>
                                            <td>{{ $teacher->name }}</td>
                                            <td>{{ $teacher->email }}</td>
                                            <td>{{ $teacher->username }}</td>
                                            <td>
                                                @if($teacher->is_active)
                                                    <span class="badge bg-success px-3 py-2">Actif</span>
                                                @else
                                                    <span class="badge bg-danger px-3 py-2">Inactif</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    <a href="{{ route('esbtp.teachers.show', $teacher->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.teachers.edit', $teacher->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $teacher->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                <!-- Modal de suppression -->
                                                <div class="modal fade" id="deleteModal{{ $teacher->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $teacher->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel{{ $teacher->id }}">Confirmer la suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Êtes-vous sûr de vouloir supprimer l'enseignant <strong>{{ $teacher->name }}</strong> ?
                                                                <p class="text-danger mt-2">Attention, cette action est irréversible !</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <form action="{{ route('esbtp.teachers.destroy', $teacher->id) }}" method="POST" class="d-inline">
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
                                            <td colspan="6" class="text-center">Aucun enseignant trouvé</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            {{ $teachers->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 