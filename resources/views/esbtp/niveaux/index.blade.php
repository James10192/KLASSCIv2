@extends('layouts.app')

@section('title', 'Liste des niveaux d\'études')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Niveaux d'études ESBTP</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.niveaux-etudes.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Nouveau niveau d'études
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
                    
                    @if($niveaux->isEmpty())
                        <div class="alert alert-info">
                            Aucun niveau d'études n'a été créé. <a href="{{ route('esbtp.niveaux-etudes.create') }}">Créer un niveau d'études</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Année</th>
                                        <th>Description</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($niveaux as $niveau)
                                        <tr>
                                            <td>{{ $niveau->code }}</td>
                                            <td>{{ $niveau->name }}</td>
                                            <td>{{ $niveau->type }}</td>
                                            <td>{{ $niveau->year }}</td>
                                            <td>{{ Str::limit($niveau->description, 100) }}</td>
                                            <td>
                                                @if($niveau->is_active)
                                                    <span class="badge badge-success">Actif</span>
                                                @else
                                                    <span class="badge badge-danger">Inactif</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('esbtp.niveaux-etudes.show', $niveau) }}" class="btn btn-info btn-sm" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.niveaux-etudes.edit', $niveau) }}" class="btn btn-primary btn-sm" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal{{ $niveau->id }}" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Modal de confirmation de suppression -->
                                                <div class="modal fade" id="deleteModal{{ $niveau->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $niveau->id }}" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel{{ $niveau->id }}">Confirmation de suppression</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Êtes-vous sûr de vouloir supprimer le niveau d'études <strong>{{ $niveau->name }}</strong> ?
                                                                <br><br>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i> Cette action est irréversible.
                                                                    <ul class="mt-2">
                                                                        <li>Si des étudiants sont inscrits dans ce niveau d'études, vous ne pourrez pas le supprimer.</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                                <form action="{{ route('esbtp.niveaux-etudes.destroy', $niveau) }}" method="POST">
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