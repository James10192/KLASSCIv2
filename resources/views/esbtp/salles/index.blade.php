@extends('layouts.app')

@section('title', 'Liste des salles de classe')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Salles de classe ESBTP</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.salles.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Nouvelle salle
                        </a>
                    </div>
                </div>
                <div class="card-body">
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
                    
                    @if($salles->isEmpty())
                        <div class="alert alert-info">
                            Aucune salle n'a été créée. <a href="{{ route('esbtp.salles.create') }}">Créer une salle</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Bâtiment</th>
                                        <th>Étage</th>
                                        <th>Capacité</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salles as $salle)
                                        <tr>
                                            <td>{{ $salle->code }}</td>
                                            <td>{{ $salle->name }}</td>
                                            <td>{{ $salle->type }}</td>
                                            <td>{{ $salle->building ?? 'Non spécifié' }}</td>
                                            <td>{{ $salle->floor }}</td>
                                            <td>{{ $salle->capacity }} places</td>
                                            <td>
                                                @if($salle->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('esbtp.salles.show', $salle) }}" class="btn btn-info btn-sm" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.salles.edit', $salle) }}" class="btn btn-primary btn-sm" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal{{ $salle->id }}" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Modal de confirmation de suppression -->
                                                <div class="modal fade" id="deleteModal{{ $salle->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $salle->id }}" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel{{ $salle->id }}">Confirmation de suppression</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Êtes-vous sûr de vouloir supprimer la salle <strong>{{ $salle->name }} ({{ $salle->code }})</strong> ?
                                                                <br><br>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i> Cette action est irréversible.
                                                                    <ul class="mt-2">
                                                                        <li>Si des cours ou des inscriptions sont liés à cette salle, vous ne pourrez pas la supprimer.</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                                <form action="{{ route('esbtp.salles.destroy', $salle) }}" method="POST">
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