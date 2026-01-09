@extends('layouts.app')

@section('title', 'Bulletins des étudiants | KLASSCI')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête de la page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h1 class="fw-bold mb-0"><i class="fas fa-file-alt text-primary me-2"></i>Bulletins de notes</h1>
                    <p class="text-muted">Consultez les bulletins de notes de vos enfants.</p>
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

    <!-- Aide à la compréhension -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 border-start border-info border-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle text-info me-2"></i>Information</h5>
                    <p class="card-text">Les bulletins sont générés à la fin de chaque période d'évaluation. Vous pouvez consulter et télécharger les bulletins de vos enfants ici.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des bulletins par étudiant -->
    @if(count($etudiantsBulletins) > 0)
        @foreach($etudiantsBulletins as $etudiantId => $data)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-graduate me-2 text-primary"></i>
                                    {{ $data['etudiant']->nom }} {{ $data['etudiant']->prenoms }}
                                    <span class="badge bg-secondary ms-2">{{ $data['etudiant']->matricule }}</span>
                                </h5>
                                <a href="{{ route('parent.bulletins.student', $etudiantId) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list me-1"></i>Voir tous les bulletins
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($data['bulletins']) > 0)
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
                                            @foreach($data['bulletins']->take(3) as $bulletin)
                                            <tr>
                                                <td>{{ $bulletin->periode }}</td>
                                                <td>{{ $bulletin->classe->nom }}</td>
                                                <td>{{ $bulletin->anneeUniversitaire->annee_debut }}-{{ $bulletin->anneeUniversitaire->annee_fin }}</td>
                                                <td><span class="badge bg-primary">{{ number_format($bulletin->moyenne_generale, 2) }}/20</span></td>
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
                                @if(count($data['bulletins']) > 3)
                                    <div class="text-center mt-3">
                                        <a href="{{ route('parent.bulletins.student', $etudiantId) }}" class="btn btn-sm btn-link">
                                            Voir les {{ count($data['bulletins']) - 3 }} autres bulletins <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Aucun bulletin n'est disponible pour cet étudiant.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>Vous n'avez aucun étudiant associé à votre compte parent.
        </div>
    @endif
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