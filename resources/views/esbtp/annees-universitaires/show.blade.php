@extends('layouts.app')

@section('title', 'Détails de l\'année universitaire : ' . $anneesUniversitaire->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails de l'année universitaire</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Afficher un message de succès s'il existe -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-check"></i> Succès!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="text-center mb-4">
                        <h2>{{ $anneesUniversitaire->name }}</h2>
                        <div class="btn-group">
                            <a href="{{ route('esbtp.annees-universitaires.edit', $anneesUniversitaire) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                            @if(!$anneesUniversitaire->is_current)
                                <form action="{{ route('esbtp.annees-universitaires.set-current', $anneesUniversitaire) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-calendar-check"></i> Définir comme année en cours
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="far fa-calendar-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Période</span>
                                    <span class="info-box-number">{{ $anneesUniversitaire->start_date ? $anneesUniversitaire->start_date->format('d/m/Y') : '-' }} au {{ $anneesUniversitaire->end_date->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box {{ $anneesUniversitaire->is_current ? 'bg-success' : 'bg-secondary' }}">
                                <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Statut courant</span>
                                    <span class="info-box-number">{{ $anneesUniversitaire->is_current ? 'Année en cours' : 'Année non courante' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box {{ $anneesUniversitaire->is_active ? 'bg-success' : 'bg-danger' }}">
                                <span class="info-box-icon"><i class="fas fa-toggle-on"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Statut</span>
                                    <span class="info-box-number">{{ $anneesUniversitaire->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Description</h3>
                        </div>
                        <div class="card-body">
                            @if($anneesUniversitaire->description)
                                <p>{{ $anneesUniversitaire->description }}</p>
                            @else
                                <p class="text-muted">Aucune description disponible.</p>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Inscriptions</h3>
                        </div>
                        <div class="card-body">
                            <p>Nombre total d'inscriptions pour cette année : <strong>{{ $anneesUniversitaire->inscriptions->count() }}</strong></p>
                            @if($anneesUniversitaire->inscriptions->count() > 0)
                                <a href="{{ route('esbtp.inscriptions.index', ['annee_universitaire_id' => $anneesUniversitaire->id]) }}" class="btn btn-info">
                                    <i class="fas fa-list"></i> Voir les inscriptions
                                </a>
                            @else
                                <p class="text-muted">Aucune inscription pour cette année universitaire.</p>
                            @endif
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
            <div class="modal-header bg-danger">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette année universitaire?</p>
                <p><strong>{{ $anneesUniversitaire->name }}</strong></p>

                @if($anneesUniversitaire->inscriptions->count() > 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Cette année universitaire possède <strong>{{ $anneesUniversitaire->inscriptions->count() }}</strong> inscriptions. La suppression ne sera pas possible tant que des inscriptions sont associées à cette année.
                    </div>
                @endif

                <p class="text-danger mb-0"><i class="fas fa-exclamation-circle"></i> Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.annees-universitaires.destroy', $anneesUniversitaire) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" {{ $anneesUniversitaire->inscriptions->count() > 0 ? 'disabled' : '' }}>
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
