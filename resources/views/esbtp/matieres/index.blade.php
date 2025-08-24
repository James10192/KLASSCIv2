@extends('layouts.app')

@section('title', 'Liste des matières')

@section('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
<style>
    .gap-1 {
        gap: 0.25rem !important;
    }
    
    /* Modal enhancements */
    .form-check:hover {
        background-color: rgba(var(--primary-rgb), 0.05) !important;
        border-radius: 6px;
    }
    
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    #combinations-preview .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .badge {
        font-size: 0.75rem;
    }
    
    .badge-link {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .badge-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .btn-group .btn {
        margin-right: 2px;
    }
    
    .btn-group .btn:last-child {
        margin-right: 0;
    }
    
    .modal-xl {
        max-width: 1200px;
    }
    
    .combinations-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
</style>
@endsection

@section('content')
<div class="main-content">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-book me-2"></i>Gestion des Matières</h1>
            <p class="header-subtitle">Liste des matières disponibles dans votre établissement</p>
        </div>
        <div class="header-actions">
            <input type="text" class="search-bar" placeholder="Rechercher une matière..." id="searchInput">
        </div>
    </div>

    <!-- Action Bar -->
    <div class="card-moderne mb-lg">
        <div class="p-lg">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2" id="bulk-actions" style="display: none;">
                    <button id="btn-attach-selected" class="btn-acasi secondary d-none">
                        <i class="fas fa-link"></i> Attacher
                    </button>
                    <button id="btn-edit-selected" class="btn-acasi secondary d-none">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    <button id="btn-delete-selected" class="btn-acasi secondary d-none">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('esbtp.matieres.attach-to-classes') }}" class="btn-acasi secondary">
                        <i class="fas fa-link"></i> Attacher aux classes
                    </a>
                    <a href="{{ route('esbtp.matieres.create') }}" class="btn-acasi primary">
                        <i class="fas fa-plus"></i> Ajouter une matière
                    </a>
                </div>
            </div>
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

    <!-- Filtres avancés -->
    <div class="main-card mb-4">
        <div class="main-card-header">
            <div class="main-card-title">
                <i class="fas fa-filter"></i>
                Filtres avancés
            </div>
            <div class="main-card-subtitle">Filtrer et rechercher parmi les matières</div>
        </div>
        <div class="main-card-body">
            <form id="filtersForm" method="GET" action="{{ route('esbtp.matieres.index') }}" class="row g-3">
                <!-- Recherche globale -->
                <div class="col-12">
                    <div class="form-group-moderne">
                        <label for="global-search" class="form-label-moderne">
                            <i class="fas fa-search me-1"></i>Recherche globale
                        </label>
                        <input type="text" id="global-search" class="form-input-moderne" 
                               placeholder="Tapez pour rechercher dans toutes les colonnes (code, nom, coefficient, heures, filières, niveaux...)">
                    </div>
                </div>

                <!-- Filière -->
                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-filiere" class="form-label-moderne">
                            <i class="fas fa-graduation-cap me-1"></i>Filière
                        </label>
                        <select name="filiere_filter" id="filter-filiere" class="form-select-moderne" onchange="document.getElementById('filtersForm').submit()">
                            <option value="">Toutes les filières</option>
                            @foreach(\App\Models\ESBTPFiliere::where('is_active', true)->get() as $filiere)
                                <option value="{{ $filiere->id }}" {{ request('filiere_filter') == $filiere->id ? 'selected' : '' }}>{{ $filiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Niveau -->
                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-niveau" class="form-label-moderne">
                            <i class="fas fa-layer-group me-1"></i>Niveau
                        </label>
                        <select name="niveau_filter" id="filter-niveau" class="form-select-moderne" onchange="document.getElementById('filtersForm').submit()">
                            <option value="">Tous les niveaux</option>
                            @foreach(\App\Models\ESBTPNiveauEtude::where('is_active', true)->get() as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau_filter') == $niveau->id ? 'selected' : '' }}>{{ $niveau->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Statut -->
                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-statut" class="form-label-moderne">
                            <i class="fas fa-toggle-on me-1"></i>Statut
                        </label>
                        <select name="statut_filter" id="filter-statut" class="form-select-moderne" onchange="document.getElementById('filtersForm').submit()">
                            <option value="">Tous les statuts</option>
                            <option value="1" {{ request('statut_filter') == '1' ? 'selected' : '' }}>Actif</option>
                            <option value="0" {{ request('statut_filter') == '0' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>
                </div>

                <!-- Coefficient -->
                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-coefficient-min" class="form-label-moderne">
                            <i class="fas fa-sort-numeric-up me-1"></i>Coefficient min.
                        </label>
                        <input type="number" id="filter-coefficient-min" class="form-input-moderne" placeholder="Ex: 1" min="0" step="0.1">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-coefficient-max" class="form-label-moderne">
                            <i class="fas fa-sort-numeric-down me-1"></i>Coefficient max.
                        </label>
                        <input type="number" id="filter-coefficient-max" class="form-input-moderne" placeholder="Ex: 5" min="0" step="0.1">
                    </div>
                </div>

                <!-- Volume horaire -->
                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-heures-min" class="form-label-moderne">
                            <i class="fas fa-clock me-1"></i>Heures min.
                        </label>
                        <input type="number" id="filter-heures-min" class="form-input-moderne" placeholder="Ex: 10" min="0">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-heures-max" class="form-label-moderne">
                            <i class="fas fa-clock me-1"></i>Heures max.
                        </label>
                        <input type="number" id="filter-heures-max" class="form-input-moderne" placeholder="Ex: 100" min="0">
                    </div>
                </div>

                <!-- Actions des filtres -->
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <span id="results-count">{{ $matieres->count() }} matière(s) affichée(s)</span>
                        </small>
                        <div class="d-flex gap-2">
                            <button type="button" id="clear-filters" class="btn-acasi secondary">
                                <i class="fas fa-eraser me-1"></i>Effacer les filtres
                            </button>
                            <button type="button" id="apply-filters" class="btn-acasi primary">
                                <i class="fas fa-search me-1"></i>Appliquer les filtres
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Résumé des filtres actifs -->
            <div id="active-filters" class="mt-3" style="display: none;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>Filtres actifs :
                    </small>
                </div>
                <div id="filters-summary" class="d-flex flex-wrap gap-1"></div>
            </div>
        </div>
    </div>

    <!-- Matières Table -->
    <div class="card-moderne">
        <div class="main-card-header">
            <h3 class="main-card-title">
                <i class="fas fa-table"></i>Liste des Matières
            </h3>
            <p class="main-card-subtitle">{{ $matieres->count() }} matière(s) trouvée(s)</p>
        </div>
        <div class="main-card-body">
            <div class="table-responsive">
                <table class="table datatable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                    <label class="form-check-label" for="select-all"></label>
                                </div>
                            </th>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Coefficient</th>
                            <th>Total heures</th>
                            <th>Filières</th>
                            <th>Niveaux</th>
                            <th>Statut</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                            <tbody>
                                @foreach($matieres as $matiere)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input matiere-checkbox" type="checkbox" id="matiere-{{ $matiere->id }}" value="{{ $matiere->id }}">
                                                <label class="form-check-label" for="matiere-{{ $matiere->id }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge primary">{{ $matiere->code }}</span>
                                        </td>
                                        <td>
                                            <div class="font-semibold color-primary">{{ $matiere->name }}</div>
                                        </td>
                                        <td>
                                            <span class="font-bold color-accent">{{ $matiere->coefficient_default }}</span>
                                        </td>
                                        <td>
                                            <span class="font-bold color-primary">{{ $matiere->total_heures_default }}h</span>
                                        </td>
                                        <td>
                                            @if($matiere->filieres->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($matiere->filieres as $filiere)
                                                        <span class="badge bg-primary text-white" title="{{ $filiere->name }}">
                                                            {{ $filiere->code ?? Str::limit($filiere->name, 8) }}
                                                        </span>
                                                    @endforeach
                                                    @if($matiere->filieres->count() > 3)
                                                        <span class="badge bg-info text-white" title="{{ $matiere->filieres->count() }} filières au total">
                                                            +{{ $matiere->filieres->count() - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-minus me-1"></i>Aucune
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($matiere->niveaux->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($matiere->niveaux as $niveau)
                                                        <span class="badge bg-info text-white" title="{{ $niveau->name }}">
                                                            {{ $niveau->code ?? Str::limit($niveau->name, 8) }}
                                                        </span>
                                                    @endforeach
                                                    @if($matiere->niveaux->count() > 3)
                                                        <span class="badge bg-warning text-dark" title="{{ $matiere->niveaux->count() }} niveaux au total">
                                                            +{{ $matiere->niveaux->count() - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="fas fa-link me-1"></i>
                                                        {{ $matiere->filieres->count() * $matiere->niveaux->count() }} combinaison(s)
                                                    </small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-minus me-1"></i>Aucun
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($matiere->is_active)
                                                <span class="badge success">
                                                    <i class="fas fa-check-circle me-1"></i>Actif
                                                </span>
                                            @else
                                                <span class="badge danger">
                                                    <i class="fas fa-times-circle me-1"></i>Inactif
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('esbtp.matieres.show', $matiere->id) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Voir" 
                                                   style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#configureModal"
                                                        data-matiere-id="{{ $matiere->id }}"
                                                        data-matiere-name="{{ $matiere->name }}"
                                                        title="Configurer liaisons"
                                                        style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-link"></i>
                                                </button>
                                                <a href="{{ route('esbtp.matieres.edit', $matiere->id) }}" 
                                                   class="btn btn-sm btn-outline-warning" 
                                                   title="Modifier"
                                                   style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        data-toggle="modal" 
                                                        data-target="#deleteModal{{ $matiere->id }}" 
                                                        title="Supprimer"
                                                        style="padding: 4px 8px; border-radius: 4px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Modal de suppression -->
                                            <div class="modal fade" id="deleteModal{{ $matiere->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $matiere->id }}" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $matiere->id }}">Confirmation de suppression</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Êtes-vous sûr de vouloir supprimer la matière <strong>{{ $matiere->name }}</strong> ?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                            <form action="{{ route('esbtp.matieres.destroy', $matiere->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de configuration des liaisons -->
<div class="modal fade" id="configureModal" tabindex="-1" aria-labelledby="configureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="configureModalLabel" style="font-weight: 600;">
                        <i class="fas fa-link me-2"></i>Configuration des liaisons
                    </h4>
                    <p class="mb-0" style="opacity: 0.9; font-size: 0.9rem;">
                        Matière : <span id="modal-matiere-name" style="font-weight: 500;"></span>
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <form id="configureLiaisonsForm">
                    @csrf
                    <input type="hidden" id="modal-matiere-id" name="matiere_id">
                    
                    <div class="row">
                        <!-- Filières disponibles -->
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-graduation-cap"></i>Filières
                                    </h3>
                                    <p class="main-card-subtitle">Sélectionnez les filières concernées</p>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group">
                                        <div id="filieres-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                            @foreach(\App\Models\ESBTPFiliere::where('is_active', true)->get() as $filiere)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input filiere-checkbox" type="checkbox" 
                                                       value="{{ $filiere->id }}" id="filiere-{{ $filiere->id }}" name="filieres[]"
                                                       style="margin-top: 0.35rem;">
                                                <label class="form-check-label" for="filiere-{{ $filiere->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $filiere->name }}</span>
                                                            @if($filiere->code)
                                                                <span class="badge secondary ms-2">{{ $filiere->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Niveaux disponibles -->
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-layer-group"></i>Niveaux d'étude
                                    </h3>
                                    <p class="main-card-subtitle">Sélectionnez les niveaux concernés</p>
                                </div>
                                <div class="main-card-body">
                                    <div class="form-group">
                                        <div id="niveaux-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                            @foreach(\App\Models\ESBTPNiveauEtude::where('is_active', true)->get() as $niveau)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input niveau-checkbox" type="checkbox" 
                                                       value="{{ $niveau->id }}" id="niveau-{{ $niveau->id }}" name="niveaux[]"
                                                       style="margin-top: 0.35rem;">
                                                <label class="form-check-label" for="niveau-{{ $niveau->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $niveau->name }}</span>
                                                            @if($niveau->code)
                                                                <span class="badge secondary ms-2">{{ $niveau->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sélection des matières (pour les combinaisons vides) -->
                    <div class="row mt-4" id="matieres-selection-container" style="display: none;">
                        <div class="col-12">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-book"></i>Matières disponibles
                                    </h3>
                                    <p class="main-card-subtitle">Sélectionnez les matières à ajouter à cette combinaison</p>
                                </div>
                                <div class="main-card-body">
                                    <div id="matieres-list" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                        <!-- Les matières seront chargées ici dynamiquement -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aperçu des combinaisons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-eye"></i>Aperçu des combinaisons
                                    </h3>
                                    <p class="main-card-subtitle">Combinaisons filières/niveaux sélectionnées</p>
                                </div>
                                <div class="main-card-body">
                                    <div id="combinations-preview" class="card-moderne" style="background: #e7f3ff; border: 1px solid #0ea5e9; padding: 1.5rem; border-radius: 8px;">
                                        <div class="d-flex align-items-center" style="color: #0369a1;">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <span>Sélectionnez des filières et des niveaux pour voir les combinaisons possibles.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-light); padding: 1.5rem 2rem; background: var(--bg-light); border-radius: 0 0 12px 12px;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <small>Les modifications seront sauvegardées immédiatement</small>
                    </div>
                    <div>
                        <button type="button" class="btn-acasi secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="button" class="btn-acasi primary" id="save-liaisons-btn">
                            <i class="fas fa-save me-1"></i>Enregistrer les liaisons
                        </button>
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
        // Initialisation de DataTables
        var table = $('.datatable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json"
            }
        });

        // Gestion de la barre de recherche
        $('#searchInput').on('input', function() {
            table.search(this.value).draw();
        });

        // Gestion de la sélection de toutes les cases à cocher
        $('#select-all').on('change', function() {
            $('.matiere-checkbox').prop('checked', $(this).prop('checked'));
            updateActionButtons();
        });

        // Gestion de la sélection individuelle
        $(document).on('change', '.matiere-checkbox', function() {
            updateActionButtons();

            // Si toutes les cases sont cochées, cocher "Sélectionner tout"
            if ($('.matiere-checkbox:checked').length === $('.matiere-checkbox').length) {
                $('#select-all').prop('checked', true);
            } else {
                $('#select-all').prop('checked', false);
            }
        });

        // Mise à jour de l'affichage des boutons d'action
        function updateActionButtons() {
            var selectedCount = $('.matiere-checkbox:checked').length;
            const bulkActions = $('#bulk-actions');

            if (selectedCount > 0) {
                bulkActions.show();
                $('#btn-attach-selected').removeClass('d-none');
                $('#btn-delete-selected').removeClass('d-none');

                // Le bouton Modifier n'est visible que si une seule matière est sélectionnée
                if (selectedCount === 1) {
                    $('#btn-edit-selected').removeClass('d-none');
                } else {
                    $('#btn-edit-selected').addClass('d-none');
                }
            } else {
                bulkActions.hide();
            }
        }

        // Action du bouton Attacher
        $('#btn-attach-selected').on('click', function() {
            var selectedIds = [];
            $('.matiere-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                // Rediriger vers la page d'attachement avec les IDs sélectionnés
                window.location.href = "{{ route('esbtp.matieres.attach-to-classes') }}?matieres=" + selectedIds.join(',');
            }
        });

        // Action du bouton Modifier
        $('#btn-edit-selected').on('click', function() {
            var selectedId = $('.matiere-checkbox:checked').first().val();
            if (selectedId) {
                window.location.href = "{{ url('esbtp/matieres') }}/" + selectedId + "/edit";
            }
        });

        // Action du bouton Supprimer
        $('#btn-delete-selected').on('click', function() {
            var selectedIds = [];
            $('.matiere-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0 && confirm('Êtes-vous sûr de vouloir supprimer les matières sélectionnées ?')) {
                // Créer un formulaire pour soumettre la suppression
                var form = $('<form>', {
                    'method': 'POST',
                    'action': "{{ route('esbtp.matieres.bulk-delete') }}"
                });

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': "{{ csrf_token() }}"
                }));

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));

                // Ajouter les IDs des matières sélectionnées
                selectedIds.forEach(function(id) {
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'matieres[]',
                        'value': id
                    }));
                });

                // Ajouter le formulaire au document et le soumettre
                $('body').append(form);
                form.submit();
            }
        });

        // ===== GESTION DES FILTRES AVANCÉS =====
        
        // Fonction pour mettre à jour le compteur de résultats
        function updateResultsCount() {
            const visibleRows = table.rows({ search: 'applied' }).count();
            const totalRows = table.rows().count();
            $('#results-count').text(`${visibleRows} matière(s) affichée(s) sur ${totalRows}`);
        }

        // Recherche globale en temps réel
        $('#global-search').on('input', function() {
            const searchValue = this.value;
            
            // Appliquer la recherche DataTables
            table.search(searchValue).draw();
            
            // Mettre à jour le compteur
            updateResultsCount();
        });

        // Fonction pour appliquer les filtres avancés
        function applyAdvancedFilters() {
            const filters = {
                filiere: $('#filter-filiere').val(),
                niveau: $('#filter-niveau').val(),
                statut: $('#filter-statut').val(),
                coefficientMin: $('#filter-coefficient-min').val(),
                coefficientMax: $('#filter-coefficient-max').val(),
                heuresMin: $('#filter-heures-min').val(),
                heuresMax: $('#filter-heures-max').val()
            };

            // Filtrer les lignes du tableau
            table.rows().every(function() {
                const row = this.node();
                let showRow = true;

                // Filtre par filière
                if (filters.filiere) {
                    const filiereCol = $(row).find('td:eq(5)');
                    const filiereText = filiereCol.text();
                    const filiereSelected = $('#filter-filiere option:selected').text();
                    if (!filiereText.includes(filiereSelected) && !filiereText.includes('Aucune')) {
                        showRow = false;
                    }
                }

                // Filtre par niveau
                if (filters.niveau) {
                    const niveauCol = $(row).find('td:eq(6)');
                    const niveauText = niveauCol.text();
                    const niveauSelected = $('#filter-niveau option:selected').text();
                    if (!niveauText.includes(niveauSelected) && !niveauText.includes('Aucun')) {
                        showRow = false;
                    }
                }

                // Filtre par statut
                if (filters.statut !== '') {
                    const statutCol = $(row).find('td:eq(7)');
                    const isActive = statutCol.find('.badge.success').length > 0;
                    const filterActive = filters.statut === '1';
                    if (isActive !== filterActive) {
                        showRow = false;
                    }
                }

                // Filtre par coefficient
                const coefficientCol = $(row).find('td:eq(3)');
                const coefficient = parseFloat(coefficientCol.text()) || 0;
                
                if (filters.coefficientMin && coefficient < parseFloat(filters.coefficientMin)) {
                    showRow = false;
                }
                if (filters.coefficientMax && coefficient > parseFloat(filters.coefficientMax)) {
                    showRow = false;
                }

                // Filtre par heures
                const heuresCol = $(row).find('td:eq(4)');
                const heures = parseFloat(heuresCol.text().replace('h', '')) || 0;
                
                if (filters.heuresMin && heures < parseFloat(filters.heuresMin)) {
                    showRow = false;
                }
                if (filters.heuresMax && heures > parseFloat(filters.heuresMax)) {
                    showRow = false;
                }

                // Afficher ou masquer la ligne
                if (showRow) {
                    $(row).show();
                } else {
                    $(row).hide();
                }
            });

            // Mettre à jour le résumé des filtres
            updateFiltersDisplay(filters);
            updateResultsCount();
        }

        // Fonction pour mettre à jour l'affichage des filtres actifs
        function updateFiltersDisplay(filters) {
            const activeFiltersDiv = $('#active-filters');
            const summaryDiv = $('#filters-summary');
            const activeFilters = [];

            // Compiler les filtres actifs
            if (filters.filiere) {
                activeFilters.push({
                    type: 'Filière',
                    value: $('#filter-filiere option:selected').text(),
                    id: 'filiere'
                });
            }
            if (filters.niveau) {
                activeFilters.push({
                    type: 'Niveau',
                    value: $('#filter-niveau option:selected').text(),
                    id: 'niveau'
                });
            }
            if (filters.statut !== '') {
                activeFilters.push({
                    type: 'Statut',
                    value: filters.statut === '1' ? 'Actif' : 'Inactif',
                    id: 'statut'
                });
            }
            if (filters.coefficientMin) {
                activeFilters.push({
                    type: 'Coeff. min',
                    value: filters.coefficientMin,
                    id: 'coefficient-min'
                });
            }
            if (filters.coefficientMax) {
                activeFilters.push({
                    type: 'Coeff. max',
                    value: filters.coefficientMax,
                    id: 'coefficient-max'
                });
            }
            if (filters.heuresMin) {
                activeFilters.push({
                    type: 'Heures min',
                    value: filters.heuresMin + 'h',
                    id: 'heures-min'
                });
            }
            if (filters.heuresMax) {
                activeFilters.push({
                    type: 'Heures max',
                    value: filters.heuresMax + 'h',
                    id: 'heures-max'
                });
            }

            if (activeFilters.length > 0) {
                // Afficher les filtres actifs
                let summaryHtml = '';
                activeFilters.forEach(filter => {
                    summaryHtml += `
                        <span class="badge bg-primary text-white d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                            <strong>${filter.type}:</strong> ${filter.value}
                            <button type="button" class="btn-close btn-close-white btn-sm remove-filter" 
                                    data-filter="${filter.id}" style="font-size: 0.5rem; padding: 0; margin-left: 4px;">
                            </button>
                        </span>
                    `;
                });
                summaryDiv.html(summaryHtml);
                activeFiltersDiv.show();
            } else {
                activeFiltersDiv.hide();
            }
        }

        // Événement pour appliquer les filtres
        $('#apply-filters').on('click', function() {
            applyAdvancedFilters();
        });

        // Événement pour effacer tous les filtres
        $('#clear-filters').on('click', function() {
            $('#filtersForm')[0].reset();
            table.search('').columns().search('').draw();
            table.rows().every(function() {
                $(this.node()).show();
            });
            $('#active-filters').hide();
            updateResultsCount();
        });

        // Événement pour supprimer un filtre individuel
        $(document).on('click', '.remove-filter', function() {
            const filterId = $(this).data('filter');
            $(`#filter-${filterId}`).val('');
            applyAdvancedFilters();
        });

        // Application automatique des filtres lors des changements
        $('#filtersForm select:not(#global-search), #filtersForm input:not(#global-search)').on('change input', function() {
            // Délai pour éviter trop d'appels
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(applyAdvancedFilters, 500);
        });

        // Initialiser le compteur
        updateResultsCount();

        // ===== GESTION DU MODAL DE CONFIGURATION DES LIAISONS =====
        
        // Ouvrir le modal et charger les données de la matière
        $('#configureModal').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const matiereId = button.getAttribute('data-matiere-id');
            const matiereName = button.getAttribute('data-matiere-name');
            const isEmptyCombo = button.getAttribute('data-empty-combo');
            const filiereId = button.getAttribute('data-filiere-id');
            const niveauId = button.getAttribute('data-niveau-id');
            
            // Déterminer le mode du modal
            if (isEmptyCombo === 'true') {
                // Mode ajout à combinaison vide
                $('#modal-matiere-name').text(`Combinaison ${button.getAttribute('data-filiere-name')} + ${button.getAttribute('data-niveau-name')}`);
                $('#modal-matiere-id').val('empty-combo');
                $('#configureModalLabel').html('<i class="fas fa-plus me-2"></i>Ajouter matières à la combinaison');
                
                // Pré-sélectionner la filière et le niveau
                $('.filiere-checkbox, .niveau-checkbox').prop('checked', false);
                if (filiereId) $(`#filiere-${filiereId}`).prop('checked', true);
                if (niveauId) $(`#niveau-${niveauId}`).prop('checked', true);
                
                // Changer le texte du bouton
                $('#save-liaisons-btn').html('<i class="fas fa-plus me-1"></i>Ajouter les matières');
                
                // Afficher toutes les matières disponibles dans un nouveau conteneur
                loadAvailableMatieres(filiereId, niveauId);
                
                updateCombinationsPreview();
            } else {
                // Mode configuration normale
                $('#modal-matiere-name').text(matiereName);
                $('#modal-matiere-id').val(matiereId);
                $('#configureModalLabel').html('<i class="fas fa-link me-2"></i>Configuration des liaisons');
                $('#save-liaisons-btn').html('<i class="fas fa-save me-1"></i>Enregistrer les liaisons');
                
                // Réinitialiser les checkboxes
                $('.filiere-checkbox, .niveau-checkbox').prop('checked', false);
                updateCombinationsPreview();
                
                // Charger les liaisons existantes
                loadExistingLiaisons(matiereId);
                
                // Masquer la sélection de matières si elle existe
                $('#matieres-selection-container').hide();
            }
        });

        // Fonction pour charger les liaisons existantes
        function loadExistingLiaisons(matiereId) {
            fetch(`/esbtp/matieres/${matiereId}/liaisons`)
                .then(response => response.json())
                .then(data => {
                    console.log('Liaisons existantes:', data);
                    
                    // Cocher les filières existantes
                    if (data.filieres) {
                        data.filieres.forEach(filiereId => {
                            $(`#filiere-${filiereId}`).prop('checked', true);
                        });
                    }
                    
                    // Cocher les niveaux existants
                    if (data.niveaux) {
                        data.niveaux.forEach(niveauId => {
                            $(`#niveau-${niveauId}`).prop('checked', true);
                        });
                    }
                    
                    updateCombinationsPreview();
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des liaisons:', error);
                });
        }

        // Fonction pour charger les matières disponibles pour une combinaison vide
        function loadAvailableMatieres(filiereId, niveauId) {
            const matieresListDiv = $('#matieres-list');
            const matieresContainer = $('#matieres-selection-container');
            
            // Afficher le conteneur
            matieresContainer.show();
            
            // Afficher un loader pendant le chargement
            matieresListDiv.html(`
                <div class="d-flex justify-content-center align-items-center py-4">
                    <div class="spinner-border text-primary me-2" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <span>Chargement des matières disponibles...</span>
                </div>
            `);
            
            fetch(`/esbtp/matieres/available-for-combination?filiere_id=${filiereId}&niveau_id=${niveauId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.matieres.length > 0) {
                        let matieresHtml = '';
                        data.matieres.forEach(matiere => {
                            matieresHtml += `
                                <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                    <input class="form-check-input matiere-checkbox" type="checkbox" 
                                           value="${matiere.id}" id="matiere-${matiere.id}" name="selected_matieres[]"
                                           style="margin-top: 0.35rem;">
                                    <label class="form-check-label" for="matiere-${matiere.id}" style="cursor: pointer; width: 100%;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="font-semibold color-dark">${matiere.name}</span>
                                                ${matiere.code ? `<span class="badge secondary ms-2">${matiere.code}</span>` : ''}
                                            </div>
                                            <div class="text-muted small">
                                                ${matiere.coefficient ? `Coeff: ${matiere.coefficient}` : ''} 
                                                ${matiere.total_heures ? `• ${matiere.total_heures}h` : ''}
                                            </div>
                                        </div>
                                        ${matiere.description ? `<small class="text-muted d-block mt-1">${matiere.description}</small>` : ''}
                                    </label>
                                </div>
                            `;
                        });
                        matieresListDiv.html(matieresHtml);
                    } else {
                        matieresListDiv.html(`
                            <div class="d-flex align-items-center justify-content-center py-4 text-muted">
                                <i class="fas fa-info-circle me-2"></i>
                                <span>Aucune matière disponible pour cette combinaison</span>
                            </div>
                        `);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des matières:', error);
                    matieresListDiv.html(`
                        <div class="d-flex align-items-center justify-content-center py-4 text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span>Erreur lors du chargement des matières</span>
                        </div>
                    `);
                });
        }

        // Mise à jour de l'aperçu des combinaisons
        function updateCombinationsPreview() {
            const selectedFilieres = [];
            const selectedNiveaux = [];
            
            $('.filiere-checkbox:checked').each(function() {
                const label = $(this).next('label').find('strong').text();
                selectedFilieres.push({
                    id: $(this).val(),
                    name: label
                });
            });
            
            $('.niveau-checkbox:checked').each(function() {
                const label = $(this).next('label').find('strong').text();
                selectedNiveaux.push({
                    id: $(this).val(),
                    name: label
                });
            });
            
            const previewDiv = $('#combinations-preview');
            
            if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                previewDiv.html(`
                    <div class="d-flex align-items-center" style="color: #0369a1;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span>Sélectionnez au moins une filière et un niveau pour voir les combinaisons possibles.</span>
                    </div>
                `).css({
                    'background': '#e7f3ff',
                    'border': '1px solid #0ea5e9',
                    'padding': '1.5rem',
                    'border-radius': '8px'
                });
                return;
            }
            
            let combinationsHtml = `
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-circle me-2" style="color: #059669;"></i>
                    <strong style="color: #047857;">${selectedFilieres.length * selectedNiveaux.length} combinaison(s) sélectionnée(s)</strong>
                </div>
                <div class="d-flex flex-wrap gap-2">
            `;
            
            selectedFilieres.forEach(filiere => {
                selectedNiveaux.forEach(niveau => {
                    combinationsHtml += `
                        <span class="badge primary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                            <i class="fas fa-link me-1"></i>
                            ${filiere.name} ↔ ${niveau.name}
                        </span>
                    `;
                });
            });
            
            combinationsHtml += '</div>';
            
            previewDiv.html(combinationsHtml).css({
                'background': '#d1fae5',
                'border': '1px solid #10b981',
                'padding': '1.5rem',
                'border-radius': '8px'
            });
        }

        // Écouter les changements dans les checkboxes
        $(document).on('change', '.filiere-checkbox, .niveau-checkbox', updateCombinationsPreview);

        // Sauvegarde des liaisons
        $('#save-liaisons-btn').on('click', function() {
            const matiereId = $('#modal-matiere-id').val();
            const saveBtn = $(this);
            const originalText = saveBtn.html();
            
            if (matiereId === 'empty-combo') {
                // Mode ajout de matières à combinaison vide
                const selectedMatieres = $('.matiere-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                const selectedFilieres = $('.filiere-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                const selectedNiveaux = $('.niveau-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (selectedMatieres.length === 0) {
                    alert('Veuillez sélectionner au moins une matière.');
                    return;
                }
                
                if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                    alert('Veuillez sélectionner au moins une filière et un niveau.');
                    return;
                }
                
                // Désactiver le bouton pendant la sauvegarde
                saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Ajout en cours...');
                
                fetch('/esbtp/matieres/add-to-combination', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify({
                        matiere_ids: selectedMatieres,
                        filiere_ids: selectedFilieres,
                        niveau_ids: selectedNiveaux
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fermer le modal
                        $('#configureModal').modal('hide');
                        
                        // Recharger la page pour voir les changements
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Erreur lors de l\'ajout');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'ajout des matières: ' + error.message);
                })
                .finally(() => {
                    // Réactiver le bouton
                    saveBtn.prop('disabled', false).html(originalText);
                });
                
            } else {
                // Mode configuration normale des liaisons
                const selectedFilieres = $('.filiere-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                const selectedNiveaux = $('.niveau-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                    alert('Veuillez sélectionner au moins une filière et un niveau.');
                    return;
                }
                
                // Désactiver le bouton pendant la sauvegarde
                saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...');
                
                fetch(`/esbtp/matieres/${matiereId}/update-liaisons`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify({
                        filieres: selectedFilieres,
                        niveaux: selectedNiveaux
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fermer le modal
                        $('#configureModal').modal('hide');
                        
                        // Afficher un message de succès
                        const alertDiv = $(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                        $('.card-body').prepend(alertDiv);
                        
                        // Recharger la page après 2 secondes
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la sauvegarde');
                })
                .finally(() => {
                    // Réactiver le bouton
                    saveBtn.prop('disabled', false).html(originalText);
                });
            }
        });
    });
</script>
@endsection
