@extends('layouts.app')

@section('title', 'Liste des classes - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des classes</h5>
                    @if(auth()->user()->hasRole('superAdmin'))
                    <a href="{{ route('esbtp.classes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i>Ajouter une classe
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Nom</th>
                                    <th>Filière</th>
                                    <th>Niveau d'études</th>
                                    <th>Année universitaire</th>
                                    <th>Capacité</th>
                                    <th>Étudiants inscrits</th>
                                    <th>Places disponibles</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($classes as $classe)
                                    <tr>
                                        <td>{{ $classe->code }}</td>
                                        <td>{{ $classe->name }}</td>
                                        <td>
                                            @if ($classe->filiere)
                                                {{ $classe->filiere->name }}
                                                @if ($classe->filiere->parent)
                                                    <br><small class="text-muted">Option de {{ $classe->filiere->parent->name }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Non assignée</span>
                                            @endif
                                        </td>
                                        <td>{{ $classe->niveau ? $classe->niveau->name : 'Non assigné' }}</td>
                                        <td>{{ $classe->annee ? $classe->annee->name : 'Non assignée' }}</td>
                                        <td>{{ $classe->places_totales }} places</td>
                                        <td>{{ $classe->nombre_etudiants }} étudiants</td>
                                        <td>
                                            @if ($classe->places_disponibles > 0)
                                                <span class="badge bg-success">{{ $classe->places_disponibles }} places</span>
                                            @else
                                                <span class="badge bg-danger">Complète</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($classe->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="btn btn-sm btn-info" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                @if(auth()->user()->hasRole('superAdmin'))
                                                <a href="{{ route('esbtp.classes.edit', ['classe' => $classe->id]) }}" class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('esbtp.classes.destroy', ['classe' => $classe->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette classe?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif

                                                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                                <a href="{{ route('esbtp.api.classes.matieres.api', ['id' => $classe->id]) }}" class="btn btn-sm btn-primary" title="Gérer les matières">
                                                    <i class="fas fa-book"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">Aucune classe trouvée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json"
            }
        });
    });
</script>
@endsection
