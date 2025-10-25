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
                                                <th>Volume horaire :</th>
                                                <td>
                                                    @if(isset($planifications) && $planifications->count() > 0)
                                                        @foreach($planifications as $planification)
                                                            <div class="mb-2">
                                                                <strong>{{ $planification->filiere->name ?? 'N/A' }} - {{ $planification->niveauEtude->name ?? 'N/A' }} :</strong>
                                                                <span class="text-primary">{{ $planification->volume_horaire_total ?? 0 }}h</span>
                                                                <small class="text-success d-block">(Planning général)</small>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">Aucune configuration dans le planning général</span>
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
                    <p class="main-card-subtitle">
                        @if(isset($parametresPlanning) && $parametresPlanning['planifications_count'] > 0)
                            Valeurs du planning général ({{ $anneeUniversitaireCourante->name ?? 'année courante' }})
                        @else
                            Valeurs par défaut (aucune planification configurée)
                        @endif
                    </p>
                </div>
                <div class="main-card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Volume horaire par combinaison</label>
                        @if(isset($planifications) && $planifications->count() > 0)
                            @foreach($planifications as $planification)
                                <div class="mb-2">
                                    <strong>{{ $planification->filiere->name ?? 'N/A' }} - {{ $planification->niveauEtude->name ?? 'N/A' }} :</strong>
                                    <span class="text-primary">{{ $planification->volume_horaire_total ?? 0 }}h</span>
                                    <small class="text-success d-block">(Planning général)</small>
                                </div>
                            @endforeach
                        @else
                            <div class="text-muted">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Aucune configuration dans le planning général -
                                <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="text-primary">Configurer maintenant</a>
                            </div>
                        @endif
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
                    <p class="main-card-subtitle">{{ $enseignantsAssignes->count() }} enseignant(s) assigné(s) via planification</p>
                </div>
                <div class="main-card-body">
                    <!-- Message d'information pour rediriger vers le planning général -->
                    <div class="mb-3 p-3" style="background-color: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                        <h6 class="mb-2"><i class="fas fa-info-circle text-primary me-1"></i>Gestion des enseignants</h6>
                        <p class="mb-2 text-muted" style="font-size: 0.9rem;">
                            Les affectations d'enseignants sont gérées via le module <strong>Planning Général</strong>
                            pour assurer une planification cohérente et centralisée.
                        </p>
                        <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>Aller au Planning Général
                        </a>
                    </div>

                    @if($enseignantsAssignes->count() > 0)
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Enseignant</th>
                                        <th>Filière</th>
                                        <th>Niveau</th>
                                        <th>Volume horaire</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enseignantsAssignes as $assignation)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $assignation['enseignant']->name }}</strong>
                                                    <small class="d-block text-muted">{{ $assignation['enseignant']->email }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $assignation['filiere']->name }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $assignation['niveau']->name }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $assignation['volume_horaire'] }}h</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $assignation['enseignant']->esbtpTeacher->id ?? 1]) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="btn btn-sm btn-outline-warning" title="Gérer dans le Planning Général">
                                                        <i class="fas fa-cog"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle me-1"></i>Aucun enseignant assigné pour l'année {{ $anneeUniversitaireCourante->name ?? 'courante' }}
                            <div class="mt-2">
                                <a href="{{ route('esbtp.planning-general.repartition-matieres') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus me-1"></i>Configurer dans le Planning Général
                                </a>
                            </div>
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
                    <p class="main-card-subtitle">
                        {{ isset($seances) ? $seances->count() : 0 }} séance(s) programmée(s)
                        @if(isset($anneeUniversitaireCourante))
                            ({{ $anneeUniversitaireCourante->name }})
                        @endif
                    </p>
                </div>
                <div class="main-card-body">
                    @if(isset($seances) && $seances->count() > 0)
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-hover">
                                <tbody>
                                    @foreach($seances->take(10) as $seance)
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
                        @if($seances->count() > 10)
                            <div class="text-center">
                                <small class="text-muted">Et {{ $seances->count() - 10 }} autre(s) séance(s)...</small>
                            </div>
                        @endif
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle me-1"></i>
                            @if(isset($anneeUniversitaireCourante))
                                Aucune séance programmée pour l'année {{ $anneeUniversitaireCourante->name }}
                            @else
                                Aucune séance programmée pour l'année courante
                            @endif
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
                    <p class="main-card-subtitle">
                        {{ isset($evaluations) ? $evaluations->count() : 0 }} évaluation(s)
                        @if(isset($anneeUniversitaireCourante))
                            ({{ $anneeUniversitaireCourante->name }})
                        @else
                            (année courante)
                        @endif
                    </p>
                </div>
                <div class="main-card-body">
                    @if(isset($evaluations) && $evaluations->count() > 0)
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-hover">
                                <tbody>
                                    @foreach($evaluations->take(5) as $evaluation)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $evaluation->titre }}</strong>
                                                    <small class="d-block text-muted">
                                                        @if($evaluation->classe)
                                                            {{ $evaluation->classe->name }} •
                                                        @endif
                                                        {{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format('d/m/Y') : 'Date non définie' }}
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
                        @if($evaluations->count() > 5)
                            <div class="text-center">
                                <small class="text-muted">Et {{ $evaluations->count() - 5 }} autre(s) évaluation(s)...</small>
                            </div>
                        @endif
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-info-circle me-1"></i>
                            @if(isset($anneeUniversitaireCourante))
                                Aucune évaluation pour l'année {{ $anneeUniversitaireCourante->name }}
                            @else
                                Aucune évaluation créée pour cette année universitaire
                            @endif
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
