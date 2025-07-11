@extends('layouts.app')

@section('title', 'Détails de la classe ' . $classe->name . ' - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails de la classe: {{ $classe->name }}</h5>
                    <div>
                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn btn-primary me-2">
                            <i class="fas fa-book me-1"></i>Gérer les matières
                        </a>
                        @endif

                        @if(auth()->user()->hasRole('superAdmin'))
                        <a href="{{ route('esbtp.classes.edit', ['classe' => $classe->id]) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        @endif

                        <a href="{{ route('esbtp.student.classes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
                <div class="card-body">
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
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations générales</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">Code</th>
                                            <td>{{ $classe->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nom</th>
                                            <td>{{ $classe->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Filière</th>
                                            <td>
                                                @if ($classe->filiere)
                                                    {{ $classe->filiere->name }}
                                                    @if ($classe->filiere->parent)
                                                        <br><small class="text-muted">Option de {{ $classe->filiere->parent->name }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Non assignée</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Niveau d'études</th>
                                            <td>
                                                @if ($classe->niveau)
                                                    {{ $classe->niveau->name }} ({{ $classe->niveau->type }} - Année {{ $classe->niveau->year }})
                                                @else
                                                    <span class="text-muted">Non assigné</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Année universitaire</th>
                                            <td>{{ $classe->annee ? $classe->annee->name : 'Non assignée' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Capacité</th>
                                            <td>
                                                {{ $classe->places_totales }} places
                                                <br>
                                                <div class="progress mt-2" style="height: 10px;">
                                                    @php
                                                        $pourcentage = $classe->places_totales > 0 ? ($classe->nombre_etudiants / $classe->places_totales) * 100 : 0;
                                                        $progressClass = $pourcentage < 70 ? 'bg-success' : ($pourcentage < 90 ? 'bg-warning' : 'bg-danger');
                                                    @endphp
                                                    <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $pourcentage }}%;" aria-valuenow="{{ $pourcentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted">
                                                    {{ $classe->nombre_etudiants }} étudiants inscrits ({{ $classe->places_disponibles }} places disponibles)
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Statut</th>
                                            <td>
                                                @if ($classe->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <td>{{ $classe->description ?: 'Aucune description' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Matières enseignées</h6>
                                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                    <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-cog me-1"></i>Gérer
                                    </a>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if($classe->matieres->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Nom</th>
                                                        <th>Coef</th>
                                                        <th>Heures</th>
                                                        <th>UE</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($classe->matieres as $matiere)
                                                        <tr>
                                                            <td>{{ $matiere->code }}</td>
                                                            <td>{{ $matiere->name }}</td>
                                                            <td class="text-center">{{ $matiere->pivot->coefficient ?? $matiere->coefficient_default }}</td>
                                                            <td class="text-center">{{ $matiere->pivot->total_heures ?? $matiere->total_heures_default }}</td>
                                                            <td>{{ $matiere->uniteEnseignement ? $matiere->uniteEnseignement->name : 'N/A' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info mb-0">
                                            Aucune matière associée à cette classe.
                                            @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                                            <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="alert-link">Ajouter des matières</a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Liste des étudiants inscrits</h6>
                                </div>
                                <div class="card-body">
                                    @if($classe->etudiants->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped datatable">
                                                <thead>
                                                    <tr>
                                                        <th>Matricule</th>
                                                        <th>Nom complet</th>
                                                        <th>Genre</th>
                                                        <th>Date de naissance</th>
                                                        <th>Contact</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($classe->etudiants as $etudiant)
                                                        <tr>
                                                            <td>{{ $etudiant->matricule }}</td>
                                                            <td>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                                                            <td>{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                                                            <td>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non renseigné' }}</td>
                                                            <td>
                                                                {{ $etudiant->telephone }}<br>
                                                                <small>{{ $etudiant->email }}</small>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $etudiant->id]) }}" class="btn btn-sm btn-info">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info mb-0">
                                            Aucun étudiant inscrit dans cette classe.
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
