@extends('layouts.app')

@section('title', 'Liste des filières - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-graduation-cap me-2"></i>Gestion des Filières</h1>
                <p class="header-subtitle">Consultez et gérez toutes les filières de l'établissement</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" placeholder="Rechercher une filière...">
                <a href="{{ route('esbtp.filieres.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle Filière
                </a>
            </div>
        </div>

        <!-- Section principale des filières -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des filières
                </div>
                <div class="main-card-subtitle">Gestion complète de toutes les filières de l'établissement</div>
            </div>

            <div class="main-card-body">
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
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Nom</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Niveaux</th>
                                <th>Matières</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                            <tbody>
                                @forelse($filieres as $filiere)
                                    <tr>
                                        <td>{{ $filiere->name }}</td>
                                        <td>{{ $filiere->code }}</td>
                                        <td>
                                            @if($filiere->parent_id)
                                                <span class="badge bg-info">Option</span>
                                            @else
                                                <span class="badge bg-primary">Principale</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $filiere->niveaux->count() }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $filiere->matieres->count() }}</span>
                                        </td>
                                        <td>
                                            @if($filiere->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('esbtp.filieres.show', $filiere->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.filieres.edit', $filiere->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if(($filiere->classes ? $filiere->classes->count() : 0) == 0 && ($filiere->options ? $filiere->options->count() : 0) == 0)
                                                <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $filiere->id }}" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @else
                                                <button type="button" class="btn btn-secondary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Suppression impossible - Filière utilisée" disabled>
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                                @endif
                                            </div>
                                            
                                            <!-- Modal de suppression -->
                                            @if(($filiere->classes ? $filiere->classes->count() : 0) == 0 && ($filiere->options ? $filiere->options->count() : 0) == 0)
                                            <div class="modal fade" id="deleteModal{{ $filiere->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Êtes-vous sûr de vouloir supprimer définitivement la filière <strong>{{ $filiere->name }}</strong> ?</p>
                                                            <p class="text-danger"><strong>Attention:</strong> Cette action est irréversible.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="{{ route('esbtp.filieres.destroy', $filiere->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Aucune filière trouvée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                    </table>
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
