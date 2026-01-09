@extends('layouts.app')

@section('title', 'Bulletins de ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' | KLASSCI')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête de la page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="fw-bold mb-0">
                                <i class="fas fa-file-alt text-primary me-2"></i>Bulletins de {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                            </h1>
                            <p class="text-muted">
                                Matricule: <span class="fw-bold">{{ $etudiant->matricule }}</span> | 
                                Classe actuelle: 
                                @if($etudiant->classeActive())
                                    <span class="fw-bold">{{ $etudiant->classeActive()->nom }}</span>
                                @else
                                    <span class="badge bg-warning">Non inscrit</span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('parent.bulletins') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    @endif

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtrer les bulletins</h5>
                    <form action="{{ route('parent.bulletins.student', $etudiant->id) }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="annee_universitaire_id" class="form-label">Année universitaire</label>
                            <select class="form-select" id="annee_universitaire_id" name="annee_universitaire_id">
                                <option value="">Toutes les années</option>
                                @foreach($anneesUniversitaires as $annee)
                                    <option value="{{ $annee->id }}" {{ request('annee_universitaire_id') == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="periode" class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode">
                                <option value="">Toutes les périodes</option>
                                <option value="Semestre 1" {{ request('periode') == 'Semestre 1' ? 'selected' : '' }}>Semestre 1</option>
                                <option value="Semestre 2" {{ request('periode') == 'Semestre 2' ? 'selected' : '' }}>Semestre 2</option>
                                <option value="Trimestre 1" {{ request('periode') == 'Trimestre 1' ? 'selected' : '' }}>Trimestre 1</option>
                                <option value="Trimestre 2" {{ request('periode') == 'Trimestre 2' ? 'selected' : '' }}>Trimestre 2</option>
                                <option value="Trimestre 3" {{ request('periode') == 'Trimestre 3' ? 'selected' : '' }}>Trimestre 3</option>
                                <option value="Annuel" {{ request('periode') == 'Annuel' ? 'selected' : '' }}>Annuel</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Filtrer
                            </button>
                            <a href="{{ route('parent.bulletins.student', $etudiant->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-1"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des bulletins -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des bulletins</h5>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($bulletins) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Période</th>
                                        <th>Classe</th>
                                        <th>Année universitaire</th>
                                        <th>Moyenne générale</th>
                                        <th>Rang</th>
                                        <th>Date de création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bulletins as $bulletin)
                                    <tr>
                                        <td>{{ $bulletin->periode }}</td>
                                        <td>{{ $bulletin->classe->nom }}</td>
                                        <td>{{ $bulletin->anneeUniversitaire->annee_debut }}-{{ $bulletin->anneeUniversitaire->annee_fin }}</td>
                                        <td>
                                            <span class="badge {{ $bulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }}">
                                                {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                            </span>
                                        </td>
                                        <td>{{ $bulletin->rang }}/{{ $bulletin->effectif }}</td>
                                        <td>{{ $bulletin->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('parent.bulletins.show', $bulletin->id) }}" class="btn btn-sm btn-outline-primary" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('parent.bulletins.pdf', $bulletin->id) }}" class="btn btn-sm btn-outline-danger" title="Télécharger le PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $bulletins->links() }}
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>Aucun bulletin n'est disponible pour cet étudiant avec les filtres sélectionnés.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endsection 