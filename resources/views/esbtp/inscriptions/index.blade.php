@extends('layouts.app')

@section('title', 'Gestion des Inscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<!-- Debug: Afficher les données de session -->
@if(session('inscriptions_problemes'))
    <script>console.log('Inscriptions avec problèmes:', @json(session('inscriptions_problemes')));</script>
@endif

<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-graduate me-2"></i>Gestion des Inscriptions</h1>
                <p class="header-subtitle">Consultez et gérez toutes les inscriptions de l'établissement</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" placeholder="Rechercher une inscription...">
                @can('inscriptions.create')
                <a href="{{ route('esbtp.inscriptions.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle Inscription
                </a>
                @endcan
            </div>
        </div>

        <!-- Filtre année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar-alt me-2"></i>Année Académique Active
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeEnCours->id ?? '' }}" selected>
                                {{ $anneeEnCours->name ?? 'Aucune année définie' }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les inscriptions affichées correspondent à l'année académique courante. 
                        @if($inscriptions->isEmpty())
                            <strong class="text-warning">Aucune inscription trouvée pour cette année.</strong>
                        @endif
                    </small>
                </div>
                @if($inscriptions->isEmpty())
                    <div class="mt-3">
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Aucune inscription pour l'année {{ $anneeEnCours->name ?? 'courante' }}</strong><br>
                                <small>Il y a {{ \App\Models\ESBTPInscription::count() }} inscriptions au total dans la base, mais aucune pour l'année académique active. 
                                Utilisez le bouton "Changer d'année" pour consulter les inscriptions d'autres années.</small>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Section principale des inscriptions -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres de recherche
                </div>
                <div class="main-card-subtitle">Filtrer les inscriptions par critères spécifiques</div>
            </div>

            <div class="main-card-body">
            <form method="GET" action="{{ route('esbtp.inscriptions.index') }}">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search">Recherche par nom ou matricule</label>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="filiere">Filière</label>
                        <select class="form-select" id="filiere" name="filiere">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $fil)
                                <option value="{{ $fil->id }}" {{ request('filiere') == $fil->id ? 'selected' : '' }}>
                                    {{ $fil->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="niveau">Niveau d'études</label>
                        <select class="form-select" id="niveau" name="niveau">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niv)
                                <option value="{{ $niv->id }}" {{ request('niveau') == $niv->id ? 'selected' : '' }}>
                                    {{ $niv->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="annee">Année universitaire</label>
                        <select class="form-select" id="annee" name="annee">
                            <option value="">Toutes les années</option>
                            @foreach($annees as $an)
                                <option value="{{ $an->id }}" {{ request('annee') == $an->id ? 'selected' : '' }}>
                                    {{ $an->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="status">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Toutes</option>
                            <option value="active" {{ request('status') == 'active' || request('status') == '' ? 'selected' : '' }}>Actives</option>
                            <option value="en_attente" {{ request('status') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="annulée" {{ request('status') == 'annulée' ? 'selected' : '' }}>Annulées</option>
                            <option value="terminée" {{ request('status') == 'terminée' ? 'selected' : '' }}>Terminées</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn-acasi primary">Filtrer</button>
                    </div>
                </div>
            </form>
            </div>
        </div>

        <!-- Liste des inscriptions -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des inscriptions
                </div>
                <div class="main-card-subtitle">Gestion complète de toutes les inscriptions de l'établissement</div>
            </div>

            <div class="main-card-body">
                @if($inscriptions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                @if(auth()->user()->hasRole('superAdmin'))
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all-inscriptions" class="form-check-input">
                                </th>
                                @endif
                                <th>N° Inscription</th>
                                <th>Matricule</th>
                                <th>Étudiant</th>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Année Universitaire</th>
                                <th>Statut</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    <tbody>
                        @foreach($inscriptions as $inscription)
                        @php
                            $hasProbleme = session('inscriptions_problemes') && isset(session('inscriptions_problemes')[$inscription->id]);
                            $problemeInfo = $hasProbleme ? session('inscriptions_problemes')[$inscription->id] : null;
                            $problemeClass = $hasProbleme ? ($problemeInfo['type'] === 'error' ? 'table-danger' : 'table-warning') : '';
                        @endphp
                        <tr class="{{ $problemeClass }}">
                            @if(auth()->user()->hasRole('superAdmin'))
                            <td>
                                @if($inscription->status == 'pending' || $inscription->status == 'en_attente')
                                <input type="checkbox" class="form-check-input inscription-checkbox"
                                       value="{{ $inscription->id }}"
                                       data-inscription-id="{{ $inscription->id }}">
                                @endif
                            </td>
                            @endif
                            <td style="max-width: 180px;">
                                @if($hasProbleme)
                                    <span class="badge {{ $problemeInfo['type'] === 'error' ? 'bg-danger' : 'bg-warning text-dark' }} d-inline-flex align-items-start"
                                          style="cursor: help; font-size: 0.75rem; white-space: normal; word-wrap: break-word; text-align: left; line-height: 1.3;"
                                          data-bs-toggle="tooltip"
                                          data-bs-placement="right"
                                          title="{{ $problemeInfo['message'] }}">
                                        <i class="fas {{ $problemeInfo['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle' }} me-1 mt-1" style="flex-shrink: 0;"></i>
                                        <span style="word-break: break-word;">{{ $problemeInfo['message'] }}</span>
                                    </span>
                                @else
                                    {{ $inscription->numero_inscription }}
                                @endif
                            </td>
                            <td>{{ $inscription->etudiant->matricule ?? 'N/A' }}</td>
                            <td>{{ $inscription->etudiant->nom ?? '' }} {{ $inscription->etudiant->prenoms ?? '' }}</td>
                            <td>{{ $inscription->filiere->name ?? ($inscription->filiere->nom ?? 'N/A') }}</td>
                            <td>{{ $inscription->niveau->name ?? ($inscription->niveau->nom ?? 'N/A') }}</td>
                            <td>{{ $inscription->anneeUniversitaire->name ?? ($inscription->anneeUniversitaire->annee_scolaire ?? 'N/A') }}</td>
                            <td>
                                @if($inscription->status == 'pending' || $inscription->status == 'en_attente')
                                    <span class="badge bg-warning text-dark px-3 py-2">En attente</span>
                                @elseif($inscription->status == 'validated' || $inscription->status == 'active')
                                    <span class="badge bg-success px-3 py-2">Validée</span>
                                @elseif($inscription->status == 'cancelled')
                                    <span class="badge bg-danger px-3 py-2">Annulée</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2">{{ ucfirst($inscription->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $inscription->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="d-flex">
                                    @can('inscriptions.view')
                                    <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('edit inscriptions')
                                    @if($inscription->status == 'pending')
                                    <a href="{{ route('esbtp.inscriptions.edit', $inscription->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @endcan

                                    @if($inscription->status == 'pending')
                                        @can('valider inscriptions')
                                        <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1 valider-btn" 
                                                data-id="{{ $inscription->id }}" title="Valider l'inscription">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <form id="valider-form-{{ $inscription->id }}" action="{{ route('esbtp.inscriptions.valider', $inscription->id) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        @endcan
                                    @endif

                                    @if($inscription->status == 'pending')
                                        @can('annuler inscriptions')
                                        <button type="button" class="btn btn-warning btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1 annuler-btn" 
                                                data-id="{{ $inscription->id }}" data-bs-toggle="modal" 
                                                data-bs-target="#annulerModal{{ $inscription->id }}" title="Annuler l'inscription">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        
                                        <!-- Modal d'annulation -->
                                        <div class="modal fade" id="annulerModal{{ $inscription->id }}" tabindex="-1" aria-labelledby="annulerModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="annulerModalLabel">Annulation d'inscription</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Êtes-vous sûr de vouloir annuler l'inscription de <strong>{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenom }}</strong> ?</p>
                                                        <form action="{{ route('esbtp.inscriptions.annuler', $inscription->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="form-group">
                                                                <label for="motif">Motif d'annulation</label>
                                                                <textarea class="form-control" id="motif" name="motif" rows="3" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-warning">Confirmer l'annulation</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        @endcan
                                    @endif

                                    @can('delete inscriptions')
                                    <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 delete-btn" 
                                            data-id="{{ $inscription->id }}" data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal{{ $inscription->id }}" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    
                                    <!-- Modal de suppression -->
                                    <div class="modal fade" id="deleteModal{{ $inscription->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Êtes-vous sûr de vouloir supprimer définitivement cette inscription ?</p>
                                                    <p class="text-danger"><strong>Attention:</strong> Cette action est irréversible et supprimera toutes les données associées.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                    <form action="{{ route('esbtp.inscriptions.destroy', $inscription->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
                <div class="mt-3">
                    {{ $inscriptions->appends(request()->query())->links() }}
                </div>
                @else
                <div class="alert alert-info">
                    Aucune inscription ne correspond à vos critères de recherche.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Barre d'actions groupées pour validation (visible uniquement pour superAdmin) -->
@if(auth()->user()->hasRole('superAdmin'))
<div id="bulk-actions-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
     background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: white; padding: 15px 30px;
     border-radius: 50px; box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4); z-index: 1050;
     animation: slideUp 0.3s ease-out;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
            <span id="selected-count" style="font-weight: 600; font-size: 1.1rem;">0</span>
            <span style="opacity: 0.9;">inscription(s) sélectionnée(s)</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-light btn-sm" onclick="bulkValiderInscriptions()"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <i class="fas fa-check-double me-1"></i>Valider la sélection
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="clearInscriptionSelection()"
                    style="padding: 8px 20px; border-radius: 25px; font-weight: 600;">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
        </div>
    </div>
</div>

<style>
@keyframes slideUp {
    from {
        bottom: -100px;
        opacity: 0;
    }
    to {
        bottom: 20px;
        opacity: 1;
    }
}
</style>
@endif

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close btn-close" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les inscriptions d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les inscriptions affichées se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des inscriptions dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Actuellement :</strong><br>
                    • Année courante = {{ $anneeEnCours->name ?? 'Non définie' }}<br>
                    • Inscriptions visibles = {{ $inscriptions->count() }} sur {{ \App\Models\ESBTPInscription::count() }} au total
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Styles pour le filtre année */
.year-selector {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    color: #374151;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
}

/* Variables CSS pour compatibilité */
:root {
    --space-sm: 0.5rem;
    --space-md: 1rem;
    --space-lg: 1.5rem;
    --text-small: 12px;
    --text-secondary: #6b7280;
}

.card-moderne {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.p-lg {
    padding: 1.5rem;
}

.mb-lg {
    margin-bottom: 1.5rem;
}

.mb-md {
    margin-bottom: 1rem;
}

.mt-3 {
    margin-top: 1rem;
}

.me-1 {
    margin-right: 0.25rem;
}

.me-2 {
    margin-right: 0.5rem;
}

.text-muted {
    color: #6b7280;
}

.text-warning {
    color: #f59e0b;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid transparent;
}

.alert-warning {
    background-color: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}

.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}
</style>
@endpush

@push('scripts')
<script>
function showYearChangeInfo() {
    console.log('Tentative ouverture modal');
    
    // Essayer avec différentes méthodes Bootstrap
    if (typeof bootstrap !== 'undefined') {
        // Bootstrap 5
        const modal = new bootstrap.Modal(document.getElementById('yearChangeModal'));
        modal.show();
    } else if (typeof $ !== 'undefined' && $.fn.modal) {
        // Bootstrap 4 avec jQuery
        $('#yearChangeModal').modal('show');
    } else {
        // Fallback - afficher directement
        const modal = document.getElementById('yearChangeModal');
        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Ajouter backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modal-backdrop';
            document.body.appendChild(backdrop);
        }
    }
}

// Fermer le modal manuellement si nécessaire
function closeYearModal() {
    const modal = document.getElementById('yearChangeModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Supprimer backdrop
        const backdrop = document.getElementById('modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
}

// Gérer les événements de fermeture
document.addEventListener('DOMContentLoaded', function() {
    // Fermeture avec X
    const closeButton = document.querySelector('#yearChangeModal .close');
    if (closeButton) {
        closeButton.addEventListener('click', closeYearModal);
    }
    
    // Fermeture avec bouton Fermer
    const cancelButton = document.querySelector('#yearChangeModal .btn-secondary');
    if (cancelButton) {
        cancelButton.addEventListener('click', closeYearModal);
    }
    
    // Fermeture en cliquant sur le backdrop
    const modal = document.getElementById('yearChangeModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeYearModal();
            }
        });
    }
});

$(document).ready(function() {
    // Initialiser les menus déroulants avec select2
    $('#filiere, #niveau, #annee, #status').select2({
        placeholder: 'Sélectionnez une option',
        allowClear: true
    });

    // Initialiser les tooltips Bootstrap pour les messages d'erreur
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// ============================================================================
// GESTION DES ACTIONS GROUPÉES POUR LES INSCRIPTIONS
// ============================================================================

// Fonction pour mettre à jour le compteur et afficher/masquer la barre d'actions
function updateInscriptionSelectionCount() {
    const checkboxes = document.querySelectorAll('.inscription-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');

    if (selectedCountSpan) {
        selectedCountSpan.textContent = count;
    }

    if (bulkBar) {
        if (count > 0) {
            bulkBar.style.display = 'block';
        } else {
            bulkBar.style.display = 'none';
        }
    }
}

// Sélectionner/désélectionner toutes les inscriptions
const selectAllCheckbox = document.getElementById('select-all-inscriptions');
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.inscription-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateInscriptionSelectionCount();
    });
}

// Écouter les changements sur chaque checkbox individuelle
document.querySelectorAll('.inscription-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        updateInscriptionSelectionCount();

        // Mettre à jour le checkbox "Tout sélectionner"
        const allCheckboxes = document.querySelectorAll('.inscription-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.inscription-checkbox:checked');
        const selectAll = document.getElementById('select-all-inscriptions');

        if (selectAll) {
            selectAll.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
        }
    });
});

// Fonction pour effacer la sélection
function clearInscriptionSelection() {
    document.querySelectorAll('.inscription-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    const selectAll = document.getElementById('select-all-inscriptions');
    if (selectAll) {
        selectAll.checked = false;
    }
    updateInscriptionSelectionCount();
}

// Fonction pour valider les inscriptions sélectionnées
function bulkValiderInscriptions() {
    const checkboxes = document.querySelectorAll('.inscription-checkbox:checked');
    const inscriptionIds = Array.from(checkboxes).map(cb => cb.value);

    if (inscriptionIds.length === 0) {
        alert('Veuillez sélectionner au moins une inscription à valider.');
        return;
    }

    const confirmMessage = `Êtes-vous sûr de vouloir valider ${inscriptionIds.length} inscription(s) ?\n\nLe système va automatiquement :\n• Valider les inscriptions avec paiements validés\n• Auto-valider les paiements en attente si nécessaire\n• Ignorer les inscriptions sans paiements\n• Envoyer les notifications aux étudiants concernés`;

    if (!confirm(confirmMessage)) {
        return;
    }

    // Créer et soumettre le formulaire
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("esbtp.inscriptions.bulk-valider") }}';

    // Ajouter le token CSRF
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Ajouter les IDs sélectionnés
    inscriptionIds.forEach(function(id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'inscription_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    // Ajouter le formulaire au body et le soumettre
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush 