@extends('layouts.app')

@section('title', 'Liste des évaluations - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-clipboard-list me-2"></i>Gestion des évaluations</h1>
                <p class="header-subtitle">Consultez et gérez toutes les évaluations de l'établissement</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" placeholder="Rechercher une évaluation...">
                <a href="{{ route('esbtp.evaluations.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle évaluation
                </a>
            </div>
        </div>
        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne bg-primary">
                <div class="kpi-title">Total Évaluations</div>
                <div class="kpi-value color-primary">{{ $totalEvaluations }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-file-alt"></i>
                    Toutes les évaluations
                </div>
            </div>
            
            <div class="kpi-card card-moderne bg-success">
                <div class="kpi-title">Évaluations Publiées</div>
                <div class="kpi-value color-success">{{ $evaluationsPubliees }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-check-circle"></i>
                    Actives
                </div>
            </div>
            
            <div class="kpi-card card-moderne bg-accent">
                <div class="kpi-title">Examens</div>
                <div class="kpi-value color-accent">{{ $examens }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-graduation-cap"></i>
                    Examens officiels
                </div>
            </div>
            
            <div class="kpi-card card-moderne bg-warning">
                <div class="kpi-title">Devoirs</div>
                <div class="kpi-value color-warning">{{ $devoirs }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-pencil-alt"></i>
                    Travaux dirigés
                </div>
            </div>
        </div>

        <!-- Section de gestion des liens externes (pour admins/secrétaires uniquement) -->
        @if(!auth()->user()->hasRole(['teacher', 'enseignant', 'etudiant']))
        <div class="main-card">
            @if($evaluationsForExternalLinks->isNotEmpty())
                @include('components.external-links-manager', ['evaluations' => $evaluationsForExternalLinks])
            @else
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-link"></i>
                        Gestion des liens externes
                    </div>
                    <div class="main-card-subtitle">Génération de liens temporaires pour enseignants externes</div>
                </div>
                <div class="main-card-body">
                    <div class="empty-state">
                        <i class="fas fa-link-slash"></i>
                        <p>
                            Aucune évaluation disponible pour la génération de liens externes.<br>
                            <small class="text-muted">Les évaluations doivent être publiées et sans enseignant assigné.</small>
                        </p>
                    </div>
                </div>
            @endif
        </div>
        @endif

        <!-- Section principale des évaluations -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des évaluations
                </div>
                <div class="main-card-subtitle">Gestion complète de toutes les évaluations de l'établissement</div>
            </div>

            <div class="main-card-body">
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

                    <!-- Filters -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select class="form-select select2" name="classe_id" id="classe_filter">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select select2" name="matiere_id" id="matiere_filter">
                                <option value="">Toutes les matières</option>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="type" id="type_filter">
                                <option value="">Tous les types</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="is_published" id="published_filter">
                                <option value="">Tous les statuts</option>
                                <option value="1" {{ request('is_published') === '1' ? 'selected' : '' }}>Publiées</option>
                                <option value="0" {{ request('is_published') === '0' ? 'selected' : '' }}>Non publiées</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-secondary w-100" id="reset_filters">
                                <i class="fas fa-undo me-1"></i>Réinitialiser
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="evaluations-table">
                            <thead class="bg-light">
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th>Titre</th>
                                    <th>Classe</th>
                                    <th>Matière</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Notes</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($evaluations as $evaluation)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input evaluation-checkbox" type="checkbox" value="{{ $evaluation->id }}">
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="text-decoration-none">
                                                {{ $evaluation->titre }}
                                            </a>
                                        </td>
                                        <td>{{ $evaluation->classe->name }}</td>
                                        <td>{{ $evaluation->matiere->name }}</td>
                                        <td>
                                            @php
                                                $typeIcons = [
                                                    'examen' => '<i class="fas fa-file-alt text-primary"></i>',
                                                    'devoir' => '<i class="fas fa-pencil-alt text-success"></i>',
                                                    'tp' => '<i class="fas fa-flask text-warning"></i>',
                                                    'projet' => '<i class="fas fa-project-diagram text-info"></i>',
                                                    'controle' => '<i class="fas fa-tasks text-secondary"></i>',
                                                    'rattrapage' => '<i class="fas fa-redo text-danger"></i>',
                                                ];
                                                $icon = $typeIcons[$evaluation->type] ?? '<i class="fas fa-file-alt text-primary"></i>';
                                            @endphp
                                            <span class="d-inline-flex align-items-center">
                                                {!! $icon !!}
                                                <span class="ms-2">{{ ucfirst($evaluation->type) }}</span>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="d-inline-flex align-items-center">
                                                <i class="far fa-calendar-alt text-secondary me-2"></i>
                                                {{ $evaluation->date_evaluation->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td>
                                            <form action="{{ route('esbtp.evaluations.update-status', $evaluation) }}" method="POST" class="d-inline status-form">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm status-select py-1 px-2" style="min-width: 120px;">
                                                    <option value="draft" {{ $evaluation->status === 'draft' ? 'selected' : '' }} class="text-secondary">Brouillon</option>
                                                    <option value="scheduled" {{ $evaluation->status === 'scheduled' ? 'selected' : '' }} class="text-primary">Planifiée</option>
                                                    <option value="in_progress" {{ $evaluation->status === 'in_progress' ? 'selected' : '' }} class="text-warning">En cours</option>
                                                    <option value="completed" {{ $evaluation->status === 'completed' ? 'selected' : '' }} class="text-success">Terminée</option>
                                                    <option value="cancelled" {{ $evaluation->status === 'cancelled' ? 'selected' : '' }} class="text-danger">Annulée</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center flex-wrap gap-1">
                                                <form action="{{ route('esbtp.evaluations.toggle-notes-published', $evaluation) }}" method="POST" class="d-inline me-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $evaluation->notes_published ? 'btn-success' : 'btn-outline-secondary' }}" {{ !$evaluation->canPublishNotes() && !$evaluation->notes_published ? 'disabled' : '' }} data-bs-toggle="tooltip" title="{{ $evaluation->notes_published ? 'Les notes sont visibles par les étudiants' : 'Les notes ne sont pas visibles par les étudiants' }}">
                                                        @if($evaluation->notes_published)
                                                            <i class="fas fa-check-circle me-1"></i>Notes publiées
                                                        @else
                                                            <i class="fas fa-eye-slash me-1"></i>Notes non publiées
                                                        @endif
                                                    </button>
                                                </form>
                                                <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="btn-manage-notes" title="Accéder à l'interface de gestion des notes">
                                                    <i class="fas fa-pen-alt"></i>{{ $evaluation->notes->count() > 0 ? 'Gérer (' . $evaluation->notes->count() . ')' : 'Saisir' }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($evaluation->isEditable())
                                                    <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                                @if($evaluation->isDeletable())
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $evaluation->id }}" title="Supprimer">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                                            Aucune évaluation trouvée
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $evaluations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Actions groupées</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success bulk-action" data-action="publish">
                        <i class="fas fa-eye me-1"></i>Publier les évaluations
                    </button>
                    <button type="button" class="btn btn-secondary bulk-action" data-action="unpublish">
                        <i class="fas fa-eye-slash me-1"></i>Masquer les évaluations
                    </button>
                    <button type="button" class="btn btn-info bulk-action" data-action="export">
                        <i class="fas fa-file-export me-1"></i>Exporter les notes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($evaluations as $evaluation)
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal{{ $evaluation->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer l'évaluation "{{ $evaluation->titre }}" ?</p>
                    <p class="text-danger mb-0"><strong>Attention :</strong> Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form action="{{ route('esbtp.evaluations.destroy', $evaluation) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection

@push('styles')
<style>
/* Styles spécifiques pour la table moderne des évaluations */
.filters-section {
    background-color: var(--background);
    padding: var(--space-md);
    border-radius: var(--radius-small);
    margin-bottom: var(--space-lg);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--space-md);
    align-items: end;
}

.filter-select {
    background-color: var(--surface);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--radius-small);
    padding: var(--space-sm) var(--space-md);
    font-size: var(--text-normal);
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1);
}

.evaluations-table-container {
    background-color: var(--surface);
    border-radius: var(--radius-medium);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-md) var(--space-lg);
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.05), rgba(30, 64, 175, 0.02));
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.bulk-actions {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.checkbox-modern {
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
    cursor: pointer;
}

.btn-acasi.small {
    padding: calc(var(--space-sm) * 0.75) var(--space-sm);
    font-size: var(--text-small);
}

.table-moderne {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--text-normal);
}

.table-moderne thead th {
    padding: var(--space-md) var(--space-sm);
    background-color: var(--background);
    color: var(--text-secondary);
    font-weight: 600;
    font-size: var(--text-small);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.05);
}

.table-row-moderne {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.table-row-moderne:hover {
    background-color: var(--background);
}

.table-row-moderne td {
    padding: var(--space-md) var(--space-sm);
    vertical-align: middle;
}

.evaluation-title-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.evaluation-title-link:hover {
    color: var(--secondary);
}

.table-text {
    color: var(--text-primary);
    font-size: var(--text-normal);
}

.type-badge {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    padding: var(--space-xs) var(--space-sm);
    background-color: rgba(0, 0, 0, 0.03);
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    font-weight: 500;
}

.date-display {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    font-size: var(--text-normal);
}

.status-select {
    background-color: var(--surface);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--radius-small);
    padding: var(--space-xs) var(--space-sm);
    font-size: var(--text-small);
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-select:focus {
    outline: none;
    border-color: var(--primary);
}

.notes-actions {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.btn-notes {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    padding: var(--space-xs) var(--space-sm);
    border: none;
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-notes.published {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.btn-notes.unpublished {
    background-color: rgba(107, 114, 128, 0.1);
    color: var(--neutral);
}

.btn-manage-notes {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    padding: var(--space-xs) var(--space-sm);
    background-color: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-manage-notes:hover {
    background-color: var(--secondary);
    color: white;
    transform: translateY(-1px);
}

.actions-group {
    display: flex;
    gap: var(--space-xs);
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: var(--radius-small);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: var(--text-small);
}

.btn-action.view {
    background-color: rgba(6, 182, 212, 0.1);
    color: var(--accent-blue);
}

.btn-action.edit {
    background-color: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.btn-action.delete {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-elevated);
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    padding: var(--space-lg);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        flex-direction: column;
        gap: var(--space-sm);
        align-items: stretch;
    }
    
    .bulk-actions {
        justify-content: center;
    }
    
    .evaluations-table-container {
        overflow-x: auto;
    }
    
    .table-moderne {
        min-width: 800px;
    }
    
    .notes-actions {
        flex-direction: row;
        flex-wrap: wrap;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        width: '100%'
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Handle status change
    $('.status-select').change(function() {
        $(this).closest('form').submit();
    });

    // Handle filters
    $('#classe_filter, #matiere_filter, #type_filter, #published_filter').change(function() {
        applyFilters();
    });

    // Reset filters
    $('#reset_filters').click(function() {
        $('#classe_filter, #matiere_filter, #type_filter, #published_filter').val('').trigger('change');
    });

    // Handle bulk selection
    $('#select-all').change(function() {
        $('.evaluation-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionsButton();
    });

    $('.evaluation-checkbox').change(function() {
        updateBulkActionsButton();
    });

    // Show bulk actions modal
    $('#bulkActionsBtn').click(function() {
        $('#bulkActionsModal').modal('show');
    });

    // Handle bulk actions
    $('.bulk-action').click(function() {
        const action = $(this).data('action');
        const selectedIds = $('.evaluation-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        // Implement bulk actions here
        console.log('Action:', action, 'Selected IDs:', selectedIds);
    });

    function updateBulkActionsButton() {
        const checkedCount = $('.evaluation-checkbox:checked').length;
        $('#bulkActionsBtn').prop('disabled', checkedCount === 0);
        $('#bulkActionsBtn').html(`<i class="fas fa-tasks me-1"></i>Actions groupées (${checkedCount})`);
    }

    function applyFilters() {
        const params = new URLSearchParams(window.location.search);

        const classe_id = $('#classe_filter').val();
        const matiere_id = $('#matiere_filter').val();
        const type = $('#type_filter').val();
        const is_published = $('#published_filter').val();

        if (classe_id) params.set('classe_id', classe_id);
        else params.delete('classe_id');

        if (matiere_id) params.set('matiere_id', matiere_id);
        else params.delete('matiere_id');

        if (type) params.set('type', type);
        else params.delete('type');

        if (is_published) params.set('is_published', is_published);
        else params.delete('is_published');

        window.location.search = params.toString();
    }
});
</script>
@endpush
