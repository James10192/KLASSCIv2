@extends('layouts.app')

@section('title', 'Résultats de recherche')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i>
                        Résultats de recherche pour "{{ $query }}"
                    </h4>
                </div>
                <div class="card-body">
                    @if(strlen($query) < 2)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Veuillez saisir au moins 2 caractères pour effectuer une recherche.
                        </div>
                    @else
                        <!-- Filtres de type -->
                        <div class="mb-4">
                            <div class="btn-group" role="group">
                                <a href="{{ route('search.results', ['q' => $query, 'type' => 'all']) }}"
                                   class="btn {{ $type === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Tous les résultats
                                </a>
                                @if($results['etudiants']->count() > 0)
                                <a href="{{ route('search.results', ['q' => $query, 'type' => 'etudiants']) }}"
                                   class="btn {{ $type === 'etudiants' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Étudiants ({{ $results['etudiants']->count() }})
                                </a>
                                @endif
                                @if($results['classes']->count() > 0)
                                <a href="{{ route('search.results', ['q' => $query, 'type' => 'classes']) }}"
                                   class="btn {{ $type === 'classes' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Classes ({{ $results['classes']->count() }})
                                </a>
                                @endif
                                @if($results['filieres']->count() > 0)
                                <a href="{{ route('search.results', ['q' => $query, 'type' => 'filieres']) }}"
                                   class="btn {{ $type === 'filieres' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Filières ({{ $results['filieres']->count() }})
                                </a>
                                @endif
                                @if($results['matieres']->count() > 0)
                                <a href="{{ route('search.results', ['q' => $query, 'type' => 'matieres']) }}"
                                   class="btn {{ $type === 'matieres' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Matières ({{ $results['matieres']->count() }})
                                </a>
                                @endif
                                @if($results['enseignants']->count() > 0)
                                <a href="{{ route('search.results', ['q' => $query, 'type' => 'enseignants']) }}"
                                   class="btn {{ $type === 'enseignants' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Enseignants ({{ $results['enseignants']->count() }})
                                </a>
                                @endif
                            </div>
                        </div>

                        <!-- Résultats -->
                        @if($type === 'all' || $type === 'etudiants')
                            @if($results['etudiants']->count() > 0)
                                <div class="mb-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-user-graduate me-2"></i>
                                        Étudiants
                                    </h5>
                                    <div class="row">
                                        @foreach($results['etudiants'] as $etudiant)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $etudiant->nom }} {{ $etudiant->prenom }}</h6>
                                                        <p class="card-text">
                                                            <small class="text-muted">{{ $etudiant->matricule }}</small><br>
                                                            @if($etudiant->classe)
                                                                <span class="badge bg-primary">{{ $etudiant->classe->nom }}</span>
                                                            @endif
                                                        </p>
                                                        <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn btn-sm btn-outline-primary">
                                                            Voir le profil
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($type === 'all' || $type === 'classes')
                            @if($results['classes']->count() > 0)
                                <div class="mb-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-users me-2"></i>
                                        Classes
                                    </h5>
                                    <div class="row">
                                        @foreach($results['classes'] as $classe)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $classe->nom }}</h6>
                                                        <p class="card-text">
                                                            @if($classe->filiere)
                                                                <span class="badge bg-info">{{ $classe->filiere->nom }}</span>
                                                            @endif
                                                            @if($classe->niveauEtude)
                                                                <span class="badge bg-secondary">{{ $classe->niveauEtude->nom }}</span>
                                                            @endif
                                                        </p>
                                                        <a href="{{ route('esbtp.classes.show', $classe->id) }}" class="btn btn-sm btn-outline-primary">
                                                            Voir la classe
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($type === 'all' || $type === 'filieres')
                            @if($results['filieres']->count() > 0)
                                <div class="mb-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-school me-2"></i>
                                        Filières
                                    </h5>
                                    <div class="row">
                                        @foreach($results['filieres'] as $filiere)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $filiere->nom }}</h6>
                                                        <p class="card-text">{{ \Str::limit($filiere->description, 100) }}</p>
                                                        <a href="{{ route('esbtp.filieres.show', $filiere->id) }}" class="btn btn-sm btn-outline-primary">
                                                            Voir la filière
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($type === 'all' || $type === 'matieres')
                            @if($results['matieres']->count() > 0)
                                <div class="mb-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-book me-2"></i>
                                        Matières
                                    </h5>
                                    <div class="row">
                                        @foreach($results['matieres'] as $matiere)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $matiere->nom }}</h6>
                                                        <p class="card-text">{{ \Str::limit($matiere->description, 100) }}</p>
                                                        <a href="{{ route('esbtp.matieres.show', $matiere->id) }}" class="btn btn-sm btn-outline-primary">
                                                            Voir la matière
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($type === 'all' || $type === 'enseignants')
                            @if($results['enseignants']->count() > 0)
                                <div class="mb-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>
                                        Enseignants
                                    </h5>
                                    <div class="row">
                                        @foreach($results['enseignants'] as $enseignant)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $enseignant->nom }} {{ $enseignant->prenom }}</h6>
                                                        <p class="card-text">
                                                            <small class="text-muted">{{ $enseignant->email }}</small>
                                                        </p>
                                                        <a href="{{ route('esbtp.enseignants.show', $enseignant->id) }}" class="btn btn-sm btn-outline-primary">
                                                            Voir le profil
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if(collect($results)->sum(function($collection) { return $collection->count(); }) === 0)
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun résultat trouvé</h5>
                                <p class="text-muted">Essayez avec d'autres mots-clés ou vérifiez l'orthographe.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
