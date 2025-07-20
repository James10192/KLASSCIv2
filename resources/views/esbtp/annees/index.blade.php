@extends('layouts.app')

@section('title', 'Liste des années universitaires ESBTP')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Années universitaires ESBTP</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.annees-universitaires.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Nouvelle année universitaire
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

                    @if($annees->isEmpty())
                        <div class="alert alert-info">
                            Aucune année universitaire n'a été créée. <a href="{{ route('esbtp.annees-universitaires.create') }}">Créer une année universitaire</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Date de rentrée</th>
                                        <th>Date de fin</th>
                                        <th>Description</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($annees as $annee)
                                        <tr class="{{ optional($annee)->is_current ? 'table-success' : '' }}">
                                            <td>{{ $annee->name }}</td>
                                            <td>{{ $annee->start_date ? (is_a($annee->start_date, 'Carbon\Carbon') ? $annee->start_date->format('d/m/Y') : (\Carbon\Carbon::parse($annee->start_date)->format('d/m/Y'))) : '-' }}</td>
                                            <td>{{ $annee->end_date ? (is_a($annee->end_date, 'Carbon\Carbon') ? $annee->end_date->format('d/m/Y') : (\Carbon\Carbon::parse($annee->end_date)->format('d/m/Y'))) : '-' }}</td>
                                            <td>{{ Str::limit($annee->description, 100) }}</td>
                                            <td>
                                                @if(optional($annee)->is_current)
                                                    <span class="badge badge-success">Année en cours</span>
                                                @endif

                                                @if($annee->is_active)
                                                    <span class="badge badge-info">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('esbtp.annees-universitaires.show', $annee) }}" class="btn btn-info btn-sm" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.annees-universitaires.edit', $annee) }}" class="btn btn-primary btn-sm" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if(!optional($annee)->is_current)
                                                        <form action="{{ route('esbtp.annees-universitaires.set-current', $annee) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-warning btn-sm" title="Définir comme année en cours">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal{{ $annee->id }}" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Modal de confirmation de suppression -->
                                                <div class="modal fade" id="deleteModal{{ $annee->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $annee->id }}" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel{{ $annee->id }}">Confirmation de suppression</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Êtes-vous sûr de vouloir supprimer l'année universitaire <strong>{{ $annee->name }}</strong> ?
                                                                <br><br>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i> Cette action est irréversible.
                                                                    <ul class="mt-2">
                                                                        <li>Si des étudiants sont inscrits pour cette année universitaire, vous ne pourrez pas la supprimer.</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                                <form action="{{ route('esbtp.annees-universitaires.destroy', $annee) }}" method="POST">
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
