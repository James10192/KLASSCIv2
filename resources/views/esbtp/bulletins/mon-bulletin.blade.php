@extends('layouts.app')

@section('title', 'Mes Bulletins')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Mes Bulletins</h1>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if($bulletins->isEmpty())
                <div class="alert alert-info">
                    <h4><i class="icon fas fa-info"></i> Information</h4>
                    Aucun bulletin n'a encore été généré pour vous.
                    <br><br>
                    Veuillez contacter l'administration pour plus d'informations.
                </div>
            @else
                <div class="row">
                    @foreach($bulletins as $bulletin)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        {{ ucfirst($bulletin->periode) }}
                                        {{ $bulletin->anneeUniversitaire->annee_debut }}-{{ $bulletin->anneeUniversitaire->annee_fin }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Classe :</strong> {{ $bulletin->classe->libelle ?? $bulletin->classe->name }}
                                    </div>
                                    @if(isset($bulletin->moyenne_generale))
                                    <div class="mb-2">
                                        <strong>Moyenne :</strong> 
                                        <span class="badge badge-{{ $bulletin->moyenne_generale >= 10 ? 'success' : 'warning' }}">
                                            {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                        </span>
                                    </div>
                                    @endif
                                    @if(isset($bulletin->rang))
                                    <div class="mb-2">
                                        <strong>Rang :</strong> {{ $bulletin->rang }} / {{ $bulletin->effectif_classe }}
                                    </div>
                                    @endif
                                    @if(isset($bulletin->mention))
                                    <div class="mb-3">
                                        <strong>Mention :</strong> 
                                        <span class="badge badge-secondary">{{ $bulletin->mention }}</span>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <a href="{{ route('mon-bulletin.show', $bulletin->id) }}" 
                                       class="btn btn-primary btn-block">
                                        <i class="fas fa-eye"></i> Voir le bulletin
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection