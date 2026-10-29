@extends('layouts.app')

@section('title', 'Mes Classes')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Mes Classes</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Mes Classes</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        @forelse($classes as $classe)
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary">{{ $classe->nom }}</h5>
                        <span class="badge bg-primary">{{ $classe->niveau->nom ?? 'Niveau' }}</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="mb-1"><strong>Filière:</strong> {{ $classe->filiere->nom ?? 'Non définie' }}</p>
                            <p class="mb-1"><strong>Année universitaire:</strong> {{ $classe->annee_universitaire->nom ?? 'Non définie' }}</p>
                            <p class="mb-3"><strong>Effectif:</strong> {{ $classe->effectif ?? 0 }} étudiants</p>
                            
                            <h6 class="mb-2 font-weight-bold">Matières enseignées:</h6>
                            <ul class="list-group list-group-flush mb-3">
                                @if(isset($classeSeances[$classe->id]))
                                    @php
                                        $matieres = collect($classeSeances[$classe->id])->pluck('matiere')->unique('id');
                                    @endphp
                                    
                                    @forelse($matieres as $matiere)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $matiere->nom }}
                                            <span class="badge bg-info rounded-pill">
                                                {{ collect($classeSeances[$classe->id])->where('matiere_id', $matiere->id)->count() }} séance(s)
                                            </span>
                                        </li>
                                    @empty
                                        <li class="list-group-item">Aucune matière assignée</li>
                                    @endforelse
                                @else
                                    <li class="list-group-item">Aucune matière assignée</li>
                                @endif
                            </ul>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('attendances.mark') }}?classe_id={{ $classe->id }}" class="btn btn-outline-primary">
                                <i class="fas fa-clipboard-check me-2"></i>Faire l'appel
                            </a>
                            <a href="{{ route('grades.create') }}?classe_id={{ $classe->id }}" class="btn btn-outline-success">
                                <i class="fas fa-graduation-cap me-2"></i>Noter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chalkboard fa-4x text-gray-300 mb-3"></i>
                        <h5>Aucune classe assignée</h5>
                        <p class="text-muted">Vous n'avez pas encore de classes assignées.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection 