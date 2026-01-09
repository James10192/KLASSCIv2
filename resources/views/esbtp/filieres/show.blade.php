@extends('layouts.app')

@section('title', 'Filière : ' . $filiere->name . ' - KLASSCI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails de la filière : {{ $filiere->name }}</h5>
                    <div>
                        <a href="{{ route('esbtp.filieres.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-list me-1"></i>Liste des filières
                        </a>
                        <a href="{{ route('esbtp.filieres.edit', $filiere) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%;">Nom :</th>
                                                <td>{{ $filiere->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Code :</th>
                                                <td>{{ $filiere->code }}</td>
                                            </tr>
                                            <tr>
                                                <th>Type :</th>
                                                <td>
                                                    @if($filiere->type == 'technique')
                                                        <span class="badge bg-info">Technique</span>
                                                    @elseif($filiere->type == 'professionnelle')
                                                        <span class="badge bg-success">Professionnelle</span>
                                                    @elseif($filiere->type == 'universitaire')
                                                        <span class="badge bg-primary">Universitaire</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $filiere->type }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Statut :</th>
                                                <td>
                                        @if($filiere->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactive</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Filière parente :</th>
                                                <td>
                                                    @if($filiere->parent)
                                                        <a href="{{ route('esbtp.filieres.show', $filiere->parent) }}">
                                                            {{ $filiere->parent->name }} ({{ $filiere->parent->code }})
                                                        </a>
                                        @else
                                                        <span class="text-muted">Aucune (filière principale)</span>
                                        @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Date de création :</th>
                                                <td>{{ $filiere->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Dernière mise à jour :</th>
                                                <td>{{ $filiere->updated_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-align-left me-2"></i>Description</h6>
                                </div>
                                <div class="card-body">
                                    @if($filiere->description)
                                        <div class="p-3 bg-light rounded">
                                        {{ $filiere->description }}
                                        </div>
                                    @else
                                        <p class="text-muted text-center my-3">
                                            <i class="fas fa-info-circle me-1"></i>Aucune description fournie pour cette filière.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">

                            <!-- Niveaux d'études -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-layer-group me-2"></i>Niveaux d'études ({{ $filiere->niveauxEtudes ? $filiere->niveauxEtudes->count() : 0 }})</h6>
                                </div>
                                <div class="card-body">
                                    @if($filiere->niveauxEtudes && $filiere->niveauxEtudes->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Nom</th>
                                                        <th>Code</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($filiere->niveauxEtudes as $niveau)
                                                        <tr>
                                                            <td>{{ $niveau->name }}</td>
                                                            <td>{{ $niveau->code }}</td>
                                                            <td>
                                                                <a href="{{ route('esbtp.niveaux-etudes.show', $niveau) }}" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted text-center my-3">
                                            <i class="fas fa-info-circle me-1"></i>Aucun niveau d'étude associé à cette filière.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">

                            <!-- Classes -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>Classes ({{ $filiere->classes ? $filiere->classes->count() : 0 }})</h6>
                                    <a href="{{ route('esbtp.classes.create', ['filiere_id' => $filiere->id]) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i>Ajouter une classe
                                    </a>
                                </div>
                                <div class="card-body">
                                    @if($filiere->classes && $filiere->classes->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Nom</th>
                                                        <th>Niveau</th>
                                                        <th>Formation</th>
                                                        <th>Année académique</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($filiere->classes as $classe)
                                                        <tr>
                                                            <td>{{ $classe->name }}</td>
                                                            <td>{{ $classe->niveauEtude->name ?? '-' }}</td>
                                                            <td>{{ $classe->formation->name ?? '-' }}</td>
                                                            <td>{{ $classe->anneeAcademique->name ?? '-' }}</td>
                                                            <td>
                                                                <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted text-center my-3">
                                            <i class="fas fa-info-circle me-1"></i>Aucune classe associée à cette filière.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-1"></i>Supprimer la filière
                        </button>
                        <a href="{{ route('esbtp.filieres.edit', $filiere) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette filière ?</p>
                <p><strong>Nom :</strong> {{ $filiere->name }}</p>

                @if($filiere->options && $filiere->options->count() > 0 || $filiere->classes && $filiere->classes->count() > 0)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Cette filière est liée à :
                        <ul class="mb-0 mt-1">
                            @if($filiere->options && $filiere->options->count() > 0)
                                <li>{{ $filiere->options->count() }} option(s)</li>
                            @endif
                            @if($filiere->classes && $filiere->classes->count() > 0)
                                <li>{{ $filiere->classes->count() }} classe(s)</li>
                            @endif
                        </ul>
                        La suppression de cette filière pourrait causer des erreurs dans le système. Assurez-vous de supprimer ou de réaffecter ces éléments avant de continuer.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.filieres.destroy', $filiere) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
