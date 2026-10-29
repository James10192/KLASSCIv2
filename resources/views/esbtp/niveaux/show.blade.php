@extends('layouts.app')

@section('title', 'Détails du niveau d\'études : ' . $niveau->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails du niveau d'études</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
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
                    
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h1 class="m-0">{{ $niveau->name }} <small class="text-muted">({{ $niveau->code }})</small></h1>
                                <div>
                                    <a href="{{ route('esbtp.niveaux-etudes.edit', $niveau) }}" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-graduation-cap"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Type de diplôme</span>
                                    <span class="info-box-number">{{ $niveau->type }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-sort-numeric-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Année</span>
                                    <span class="info-box-number">{{ $niveau->year }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-toggle-on"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Statut</span>
                                    <span class="info-box-number">
                                        @if($niveau->is_active)
                                            <span class="badge badge-success">Actif</span>
                                        @else
                                            <span class="badge badge-danger">Inactif</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Description</h3>
                                </div>
                                <div class="card-body">
                                    @if($niveau->description)
                                        {{ $niveau->description }}
                                    @else
                                        <em>Aucune description disponible.</em>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Étudiants inscrits dans ce niveau d'études</h3>
                                </div>
                                <div class="card-body">
                                    @if($niveau->inscriptions()->count() > 0)
                                        <p>Il y a {{ $niveau->inscriptions()->count() }} étudiant(s) inscrit(s) dans ce niveau d'études.</p>
                                        <a href="{{ route('esbtp.inscriptions.index', ['niveau_etude_id' => $niveau->id]) }}" class="btn btn-info">
                                            <i class="fas fa-list"></i> Voir les inscriptions
                                        </a>
                                    @else
                                        <div class="alert alert-info">
                                            Aucun étudiant n'est inscrit dans ce niveau d'études.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
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
@endsection