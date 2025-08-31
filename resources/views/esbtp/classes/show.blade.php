@extends('layouts.app')

@section('title', 'Détails de la classe ' . $classe->name . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chalkboard-teacher me-2"></i>Détails de la classe</h1>
                <p class="header-subtitle">{{ $classe->name }}</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn-acasi primary">
                    <i class="fas fa-book"></i>Gérer les matières
                </a>
                @endif

                @if(auth()->user()->hasRole('superAdmin'))
                <a href="{{ route('esbtp.classes.edit', ['classe' => $classe->id]) }}" class="btn-acasi warning">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                @endif

                <a href="{{ route('esbtp.student.classes.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Filtre année académique -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres d'affichage
                </div>
                <div class="main-card-subtitle">Année académique courante</div>
            </div>
            <div class="main-card-body">
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label for="annee_academique" class="form-label text-muted text-uppercase" style="font-size: 12px; font-weight: 600;">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="form-select" style="background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn-acasi info" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                            <i class="fas fa-info-circle"></i>Changer d'année
                        </button>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les étudiants affichés dans cette classe correspondent à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Capacité Total</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $classe->places_totales }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    Places disponibles
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiants Inscrits</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $classe->nombre_etudiants }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user-graduate"></i>
                    Année courante
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Taux d'Occupation</div>
                @php
                    $pourcentage = $classe->places_totales > 0 ? round(($classe->nombre_etudiants / $classe->places_totales) * 100, 1) : 0;
                @endphp
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $pourcentage }}%</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-chart-pie"></i>
                    {{ $classe->places_disponibles }} places libres
                </div>
            </div>
        </div>

        <!-- Informations de la classe -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-info-circle"></i>
                    Informations générales
                </div>
                <div class="main-card-subtitle">Détails et caractéristiques de la classe</div>
            </div>
            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-hover align-middle mb-0">
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
                                <th>Statut</th>
                                <td>
                                    @if ($classe->is_active)
                                        <span class="badge bg-success px-3 py-2">Active</span>
                                    @else
                                        <span class="badge bg-danger px-3 py-2">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $classe->description ?: 'Aucune description' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Informations Complémentaires</h6>
                            <ul class="mb-0">
                                <li><strong>Code classe :</strong> {{ $classe->code }}</li>
                                <li><strong>Création :</strong> {{ $classe->created_at->format('d/m/Y') }}</li>
                                <li><strong>Dernière modification :</strong> {{ $classe->updated_at->format('d/m/Y') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matières enseignées -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-book"></i>
                    Matières enseignées
                </div>
                <div class="main-card-subtitle">Liste des matières configurées pour cette classe</div>
            </div>
            <div class="main-card-body">
                @if($classe->matieres->count() > 0)
                    <div class="d-flex justify-content-end mb-3">
                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                        <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn-acasi primary">
                            <i class="fas fa-cog"></i>Gérer les matières
                        </a>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
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
                                        <td class="text-center">
                                            <span class="badge bg-info px-3 py-2">{{ $matiere->pivot->coefficient ?? $matiere->coefficient_default }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success px-3 py-2">{{ $matiere->pivot->total_heures ?? $matiere->total_heures_default }}h</span>
                                        </td>
                                        <td>{{ $matiere->uniteEnseignement ? $matiere->uniteEnseignement->name : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-book me-2"></i> Aucune matière n'est encore configurée pour cette classe.
                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                            <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="alert-link">Ajouter des matières</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Liste des étudiants -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-users"></i>
                    Liste des étudiants inscrits
                </div>
                <div class="main-card-subtitle">{{ $classe->nombre_etudiants }} étudiant(s) inscrit(s) dans cette classe pour l'année courante</div>
            </div>
            <div class="main-card-body">
                @if($classe->etudiants->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable">
                            <thead class="bg-light">
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
                                            <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $etudiant->id]) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>Aucun étudiant inscrit dans cette classe pour l'année courante.
                    </div>
                @endif
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
    
    // Fonction pour afficher le modal d'information sur le changement d'année
    function showYearChangeInfo() {
        $('#yearChangeModal').modal('show');
    }
</script>

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les étudiants affichés dans cette classe se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des étudiants dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple pour cette classe :</strong><br>
                    • Année courante = 2024-2025 → Voir {{ $classe->etudiants->count() }} étudiants<br>
                    • Changement vers 2023-2024 → Voir les étudiants inscrits en 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
