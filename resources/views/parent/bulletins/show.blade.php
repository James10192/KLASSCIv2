@extends('layouts.app')

@section('title', 'Détails du bulletin | KLASSCI')

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
                                <i class="fas fa-file-alt text-primary me-2"></i>Bulletin de notes
                            </h1>
                            <p class="text-muted">
                                {{ $etudiant->nom }} {{ $etudiant->prenoms }} | 
                                Matricule: <span class="fw-bold">{{ $etudiant->matricule }}</span> | 
                                {{ $bulletin->classe->nom }} | 
                                {{ $bulletin->periode }} | 
                                {{ $bulletin->anneeUniversitaire->annee_debut }}-{{ $bulletin->anneeUniversitaire->annee_fin }}
                            </p>
                        </div>
                        <div class="d-flex">
                            <a href="{{ route('parent.bulletins.student', $etudiant->id) }}" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <a href="{{ route('parent.bulletins.pdf', $bulletin->id) }}" class="btn btn-danger">
                                <i class="fas fa-file-pdf me-1"></i>Télécharger PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations générales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="40%">Nom & prénom:</th>
                                        <td>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                                    </tr>
                                    <tr>
                                        <th>Matricule:</th>
                                        <td>{{ $etudiant->matricule }}</td>
                                    </tr>
                                    <tr>
                                        <th>Classe:</th>
                                        <td>{{ $bulletin->classe->nom }}</td>
                                    </tr>
                                    <tr>
                                        <th>Filière:</th>
                                        <td>{{ $bulletin->classe->filiere->nom ?? 'Non définie' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="40%">Année universitaire:</th>
                                        <td>{{ $bulletin->anneeUniversitaire->annee_debut }}-{{ $bulletin->anneeUniversitaire->annee_fin }}</td>
                                    </tr>
                                    <tr>
                                        <th>Période:</th>
                                        <td>{{ $bulletin->periode }}</td>
                                    </tr>
                                    <tr>
                                        <th>Effectif de la classe:</th>
                                        <td>{{ $bulletin->effectif }} étudiants</td>
                                    </tr>
                                    <tr>
                                        <th>Date d'émission:</th>
                                        <td>{{ $bulletin->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Résultats -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Résultats des matières</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Matière</th>
                                    <th class="text-center">Coefficient</th>
                                    <th class="text-center">Note</th>
                                    <th class="text-center">Moyenne classe</th>
                                    <th class="text-center">Note min</th>
                                    <th class="text-center">Note max</th>
                                    <th class="text-center">Rang</th>
                                    <th class="text-center">Appréciation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bulletin->resultatsMatiere as $resultat)
                                <tr>
                                    <td>{{ $resultat->matiere->nom }}</td>
                                    <td class="text-center">{{ $resultat->coefficient }}</td>
                                    <td class="text-center fw-bold {{ $resultat->note < 10 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($resultat->note, 2) }}/20
                                    </td>
                                    <td class="text-center">{{ number_format($resultat->moyenne_classe, 2) }}</td>
                                    <td class="text-center">{{ number_format($resultat->note_min, 2) }}</td>
                                    <td class="text-center">{{ number_format($resultat->note_max, 2) }}</td>
                                    <td class="text-center">{{ $resultat->rang }}/{{ $bulletin->effectif }}</td>
                                    <td class="text-center">
                                        @php
                                            $appreciation = '';
                                            if($resultat->note < 5) { 
                                                $appreciation = 'Très insuffisant';
                                                $badgeClass = 'bg-danger';
                                            } elseif($resultat->note < 10) { 
                                                $appreciation = 'Insuffisant'; 
                                                $badgeClass = 'bg-warning text-dark';
                                            } elseif($resultat->note < 12) { 
                                                $appreciation = 'Passable'; 
                                                $badgeClass = 'bg-info text-dark';
                                            } elseif($resultat->note < 14) { 
                                                $appreciation = 'Assez bien'; 
                                                $badgeClass = 'bg-success';
                                            } elseif($resultat->note < 16) { 
                                                $appreciation = 'Bien'; 
                                                $badgeClass = 'bg-primary';
                                            } else { 
                                                $appreciation = 'Très bien'; 
                                                $badgeClass = 'bg-purple';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $appreciation }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>MOYENNES GÉNÉRALES</td>
                                    <td class="text-center">{{ $bulletin->resultatsMatiere->sum('coefficient') }}</td>
                                    <td class="text-center {{ $bulletin->moyenne_generale < 10 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                    </td>
                                    <td class="text-center">{{ number_format($bulletin->moyenne_classe, 2) }}</td>
                                    <td class="text-center">{{ number_format($bulletin->moyenne_min, 2) }}</td>
                                    <td class="text-center">{{ number_format($bulletin->moyenne_max, 2) }}</td>
                                    <td class="text-center">{{ $bulletin->rang }}/{{ $bulletin->effectif }}</td>
                                    <td class="text-center">
                                        @php
                                            $appreciation = '';
                                            if($bulletin->moyenne_generale < 5) { 
                                                $appreciation = 'Très insuffisant';
                                                $badgeClass = 'bg-danger';
                                            } elseif($bulletin->moyenne_generale < 10) { 
                                                $appreciation = 'Insuffisant'; 
                                                $badgeClass = 'bg-warning text-dark';
                                            } elseif($bulletin->moyenne_generale < 12) { 
                                                $appreciation = 'Passable'; 
                                                $badgeClass = 'bg-info text-dark';
                                            } elseif($bulletin->moyenne_generale < 14) { 
                                                $appreciation = 'Assez bien'; 
                                                $badgeClass = 'bg-success';
                                            } elseif($bulletin->moyenne_generale < 16) { 
                                                $appreciation = 'Bien'; 
                                                $badgeClass = 'bg-primary';
                                            } else { 
                                                $appreciation = 'Très bien'; 
                                                $badgeClass = 'bg-purple';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $appreciation }}</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques et graphiques -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistiques</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="card-title fw-bold">Moyenne générale</h6>
                                    <h2 class="mb-0 {{ $bulletin->moyenne_generale < 10 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="card-title fw-bold">Rang</h6>
                                    <h2 class="mb-0">{{ $bulletin->rang }}/{{ $bulletin->effectif }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="card-title fw-bold">Notes > 10</h6>
                                    <h2 class="mb-0 text-success">
                                        {{ $bulletin->resultatsMatiere->where('note', '>=', 10)->count() }}/{{ $bulletin->resultatsMatiere->count() }}
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <h6 class="card-title fw-bold">Notes < 10</h6>
                                    <h2 class="mb-0 text-danger">
                                        {{ $bulletin->resultatsMatiere->where('note', '<', 10)->count() }}/{{ $bulletin->resultatsMatiere->count() }}
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Observations générales</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-bold">Décision du conseil de classe:</h6>
                        <p class="mb-0">{{ $bulletin->decision ?? 'Non définie' }}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">Observations:</h6>
                        <p class="mb-0">{{ $bulletin->observations ?? 'Aucune observation particulière' }}</p>
                    </div>
                    <div>
                        <h6 class="fw-bold">Recommandations:</h6>
                        @if($bulletin->moyenne_generale < 10)
                            <p class="mb-0">Plus d'efforts sont nécessaires pour améliorer les résultats. Un suivi régulier et un accompagnement sont recommandés.</p>
                        @elseif($bulletin->moyenne_generale < 12)
                            <p class="mb-0">Résultats satisfaisants. Continuer les efforts pour améliorer les notes dans les matières faibles.</p>
                        @elseif($bulletin->moyenne_generale < 14)
                            <p class="mb-0">Bons résultats. Poursuivre les efforts actuels et renforcer les compétences dans les matières principales.</p>
                        @else
                            <p class="mb-0">Excellents résultats. Continuer à maintenir ce niveau et approfondir les connaissances dans les domaines d'intérêt.</p>
                        @endif
                    </div>
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