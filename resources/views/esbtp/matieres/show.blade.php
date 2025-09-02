@extends('layouts.app')

@section('title', 'Matière : ' . $matiere->name . ' - ESBTP-yAKRO')

@section('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="main-content">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-eye me-2"></i>Détails de la Matière</h1>
            <p class="header-subtitle">{{ $matiere->name }} ({{ $matiere->code }})</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.matieres.index') }}" class="btn-acasi secondary me-2">
                <i class="fas fa-list me-1"></i>Liste des matières
            </a>
            <a href="{{ route('esbtp.matieres.edit', ['matiere' => $matiere->id]) }}" class="btn-acasi primary">
                <i class="fas fa-edit me-1"></i>Modifier
            </a>
        </div>
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--success);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle color-success me-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Informations générales -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card-moderne">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-info-circle"></i>Informations générales
                    </h3>
                    <p class="main-card-subtitle">Code et données de base</p>
                </div>
                <div class="main-card-body">
                                    <table class="table table-striped">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%;">Nom :</th>
                                                <td>{{ $matiere->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Code :</th>
                                                <td>{{ $matiere->code }}</td>
                                            </tr>
                                            <tr>
                                                <th>Coefficient :</th>
                                                <td>{{ $matiere->coefficient_default }}</td>
                                            </tr>
                                            <tr>
                                                <th>Volume horaire :</th>
                                                <td>{{ $matiere->total_heures_default }} heures</td>
                                            </tr>
                                            <tr>
                                                <th>Répartition :</th>
                                                <td>
                                                    <span class="badge bg-info">CM : {{ $matiere->heures_cm_default }} h</span>
                                                    <span class="badge bg-success">TD : {{ $matiere->heures_td_default }} h</span>
                                                    <span class="badge bg-warning">TP : {{ $matiere->heures_tp_default }} h</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Unité d'enseignement :</th>
                                                <td>
                                                    @if($matiere->uniteEnseignement)
                                                        <a href="{{ route('esbtp.unites-enseignement.show', ['uniteEnseignement' => $matiere->uniteEnseignement->id]) }}">
                                                            {{ $matiere->uniteEnseignement->name }} ({{ $matiere->uniteEnseignement->code }})
                                                        </a>
                                                    @else
                                                        <span class="text-muted">Non assignée</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Statut :</th>
                                                <td>
                                                    @if($matiere->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactive</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Date de création :</th>
                                                <td>{{ $matiere->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Dernière mise à jour :</th>
                                                <td>{{ $matiere->updated_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

        <div class="col-md-6">
            <div class="card-moderne">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-sliders-h"></i>Paramètres d'évaluation
                    </h3>
                    <p class="main-card-subtitle">Coefficient et volume horaire</p>
                </div>
                <div class="main-card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted">Coefficient</label>
                            <div class="h5 text-primary mb-0">{{ $matiere->coefficient_default }}</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Total heures</label>
                            <div class="h5 text-primary mb-0">{{ $matiere->total_heures_default }}h</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Répartition horaire</label>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-info">CM : {{ $matiere->heures_cm_default ?? 0 }}h</span>
                            <span class="badge bg-success">TD : {{ $matiere->heures_td_default ?? 0 }}h</span>
                            <span class="badge bg-warning">TP : {{ $matiere->heures_tp_default ?? 0 }}h</span>
                            <span class="badge bg-secondary">Stage : {{ $matiere->heures_stage_default ?? 0 }}h</span>
                            <span class="badge bg-primary">Perso : {{ $matiere->heures_perso_default ?? 0 }}h</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Type de formation</label>
                        <div>
                            @if($matiere->type_formation == 'generale')
                                <span class="badge bg-info">Formation générale</span>
                            @else
                                <span class="badge bg-warning">Formation technologique et professionnelle</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Couleur</label>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 20px; height: 20px; background-color: {{ $matiere->couleur ?? '#007bff' }}; border-radius: 4px; border: 1px solid #ddd;"></div>
                            <span class="font-monospace">{{ $matiere->couleur ?? '#007bff' }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-muted">Statut</label>
                        <div>
                            @if($matiere->is_active)
                                <span class="badge success">
                                    <i class="fas fa-check-circle me-1"></i>Active
                                </span>
                            @else
                                <span class="badge danger">
                                    <i class="fas fa-times-circle me-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Description et Associations -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card-moderne">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-align-left"></i>Description et options
                    </h3>
                    <p class="main-card-subtitle">Informations complémentaires</p>
                </div>
                <div class="main-card-body">
                    @if($matiere->description)
                        <div class="mb-3">
                            <label class="form-label text-muted">Description</label>
                            <div class="p-3 bg-light rounded">
                                {{ $matiere->description }}
                            </div>
                        </div>
                    @else
                        <div class="text-muted text-center my-3">
                            <i class="fas fa-info-circle me-1"></i>Aucune description fournie pour cette matière.
                        </div>
                    @endif

                    <div class="row mt-3">
                        <div class="col-6">
                            <label class="form-label text-muted">Date de création</label>
                            <div class="small">{{ $matiere->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Dernière mise à jour</label>
                            <div class="small">{{ $matiere->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-moderne">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-link"></i>Associations
                    </h3>
                    <p class="main-card-subtitle">Filières et niveaux d'étude associés</p>
                </div>
                <div class="main-card-body">
                    <!-- Filières associées -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-graduation-cap me-1"></i>Filières associées
                            <span class="badge bg-secondary ms-2">{{ $matiere->filieres->count() }}</span>
                        </label>
                        @if($matiere->filieres->count() > 0)
                            <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto; background: #f8f9fa;">
                                @foreach($matiere->filieres as $filiere)
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded">
                                    <div>
                                        <strong>{{ $filiere->name }}</strong>
                                        @if($filiere->code)
                                            <small class="text-muted">({{ $filiere->code }})</small>
                                        @endif
                                    </div>
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted text-center py-3 border rounded">
                                <i class="fas fa-info-circle me-1"></i>Aucune filière associée
                            </div>
                        @endif
                    </div>

                    <!-- Niveaux d'étude associés -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-layer-group me-1"></i>Niveaux d'étude associés
                            <span class="badge bg-secondary ms-2">{{ $matiere->niveaux->count() }}</span>
                        </label>
                        @if($matiere->niveaux->count() > 0)
                            <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto; background: #f8f9fa;">
                                @foreach($matiere->niveaux as $niveau)
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded">
                                    <div>
                                        <strong>{{ $niveau->name }}</strong>
                                        @if($niveau->code)
                                            <small class="text-muted">({{ $niveau->code }})</small>
                                        @endif
                                    </div>
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted text-center py-3 border rounded">
                                <i class="fas fa-info-circle me-1"></i>Aucun niveau d'étude associé
                            </div>
                        @endif
                    </div>

                    <!-- Aperçu des combinaisons -->
                    @if($matiere->filieres->count() > 0 && $matiere->niveaux->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-eye me-1"></i>Aperçu des combinaisons
                        </label>
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>{{ $matiere->filieres->count() * $matiere->niveaux->count() }} combinaison(s) active(s)</strong>
                            </div>
                            <div class="row">
                                @foreach($matiere->filieres as $filiere)
                                    @foreach($matiere->niveaux as $niveau)
                                        <div class="col-md-6 mb-2">
                                            <div class="badge bg-primary text-wrap p-2 w-100">
                                                <i class="fas fa-link me-1"></i>
                                                {{ $filiere->name }} ↔ {{ $niveau->name }}
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-eye me-1"></i>Aperçu des combinaisons
                        </label>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucune combinaison active. Associez des filières et des niveaux pour créer des combinaisons.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Relations et statistiques -->
    <div class="row mb-4">
        <div class="col-md-6">
            <!-- Enseignants associés -->
            <div class="card-moderne mb-4">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-chalkboard-teacher"></i>Enseignants
                    </h3>
                    <p class="main-card-subtitle">{{ $matiere->enseignants->count() }} enseignant(s) associé(s)</p>
                </div>
                <div class="main-card-body">
                    @if($matiere->enseignants->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    @foreach($matiere->enseignants as $enseignant)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $enseignant->user->name }}</strong>
                                                    <small class="d-block text-muted">{{ $enseignant->matricule }}</small>
                                                    @if($enseignant->specialite)
                                                        <span class="badge bg-info">{{ $enseignant->specialite }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant->id]) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle me-1"></i>Aucun enseignant associé
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Séances de cours -->
            <div class="card-moderne mb-4">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-clock"></i>Séances de cours
                    </h3>
                    <p class="main-card-subtitle">{{ $matiere->seancesCours->count() }} séance(s) programmée(s)</p>
                </div>
                <div class="main-card-body">
                    @if($matiere->seancesCours->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    @foreach($matiere->seancesCours->take(5) as $seance)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>
                                                        @if($seance->emploiTemps && $seance->emploiTemps->classe)
                                                            {{ $seance->emploiTemps->classe->name }}
                                                        @else
                                                            Séance
                                                        @endif
                                                    </strong>
                                                    <small class="d-block text-muted">
                                                        @switch($seance->jour)
                                                            @case('lundi') Lundi @break
                                                            @case('mardi') Mardi @break
                                                            @case('mercredi') Mercredi @break
                                                            @case('jeudi') Jeudi @break
                                                            @case('vendredi') Vendredi @break
                                                            @case('samedi') Samedi @break
                                                            @default {{ $seance->jour }}
                                                        @endswitch
                                                        • {{ $seance->heure_debut }} - {{ $seance->heure_fin }}
                                                    </small>
                                                    @switch($seance->type)
                                                        @case('cm')
                                                            <span class="badge bg-info">CM</span>
                                                            @break
                                                        @case('td')
                                                            <span class="badge bg-success">TD</span>
                                                            @break
                                                        @case('tp')
                                                            <span class="badge bg-warning">TP</span>
                                                            @break
                                                        @case('evaluation')
                                                            <span class="badge bg-danger">Évaluation</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ $seance->type }}</span>
                                                    @endswitch
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($matiere->seancesCours->count() > 5)
                            <div class="text-center">
                                <small class="text-muted">Et {{ $matiere->seancesCours->count() - 5 }} autre(s) séance(s)...</small>
                            </div>
                        @endif
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle me-1"></i>Aucune séance programmée
                        </div>
                    @endif
                </div>
            </div>

            <!-- Évaluations -->
            <div class="card-moderne">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-tasks"></i>Évaluations
                    </h3>
                    <p class="main-card-subtitle">{{ $matiere->evaluations->count() }} évaluation(s) créée(s)</p>
                </div>
                <div class="main-card-body">
                    @if($matiere->evaluations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    @foreach($matiere->evaluations->take(5) as $evaluation)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $evaluation->titre }}</strong>
                                                    <small class="d-block text-muted">
                                                        @if($evaluation->classe)
                                                            {{ $evaluation->classe->name }} • 
                                                        @endif
                                                        {{ $evaluation->date->format('d/m/Y') }}
                                                    </small>
                                                    @switch($evaluation->type)
                                                        @case('devoir')
                                                            <span class="badge bg-primary">Devoir</span>
                                                            @break
                                                        @case('examen')
                                                            <span class="badge bg-danger">Examen</span>
                                                            @break
                                                        @case('tp')
                                                            <span class="badge bg-warning">TP</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ $evaluation->type }}</span>
                                                    @endswitch
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($matiere->evaluations->count() > 5)
                            <div class="text-center">
                                <small class="text-muted">Et {{ $matiere->evaluations->count() - 5 }} autre(s) évaluation(s)...</small>
                            </div>
                        @endif
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle me-1"></i>Aucune évaluation créée
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card-moderne">
        <div class="p-lg text-center">
            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash me-1"></i>Supprimer la matière
            </button>
            <a href="{{ route('esbtp.matieres.edit', ['matiere' => $matiere->id]) }}" class="btn-acasi primary">
                <i class="fas fa-edit me-1"></i>Modifier la matière
            </a>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette matière ?</p>
                <p><strong>Nom :</strong> {{ $matiere->name }}</p>

                @if($matiere->seancesCours->count() > 0 || $matiere->evaluations->count() > 0)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Cette matière est liée à :
                        <ul class="mb-0 mt-1">
                            @if($matiere->seancesCours->count() > 0)
                                <li>{{ $matiere->seancesCours->count() }} séance(s) de cours</li>
                            @endif
                            @if($matiere->evaluations->count() > 0)
                                <li>{{ $matiere->evaluations->count() }} évaluation(s)</li>
                            @endif
                        </ul>
                        La suppression de cette matière pourrait causer des erreurs dans le système. Assurez-vous de supprimer ces éléments liés avant de continuer.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.matieres.destroy', ['matiere' => $matiere->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
