@extends('layouts.app')

@section('title', 'Liste des évaluations - ESBTP-yAKRO')

@section('page_title', 'Gestion des évaluations')

@section('content')
<div class="container-fluid">
    <!-- Statistics Dashboard -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Évaluations</h6>
                            <h2 class="mb-0">{{ $totalEvaluations }}</h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Évaluations Publiées</h6>
                            <h2 class="mb-0">{{ $evaluationsPubliees }}</h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Examens</h6>
                            <h2 class="mb-0">{{ $examens }}</h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Devoirs</h6>
                            <h2 class="mb-0">{{ $devoirs }}</h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section de gestion des liens externes (pour admins/secrétaires uniquement) -->
    @if(!auth()->user()->hasRole(['teacher', 'enseignant', 'etudiant']) && $evaluationsForExternalLinks->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            @include('components.external-links-manager', ['evaluations' => $evaluationsForExternalLinks])
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-list me-2"></i>Liste des évaluations
                    </h5>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" id="bulkActionsBtn" disabled>
                            <i class="fas fa-tasks me-1"></i>Actions groupées
                        </button>
                        <a href="{{ route('esbtp.evaluations.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i>Nouvelle évaluation
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
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
                                                <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Accéder à l'interface de gestion des notes">
                                                    <i class="fas fa-pen-alt me-1"></i>{{ $evaluation->notes->count() > 0 ? 'Gérer les notes (' . $evaluation->notes->count() . ')' : 'Saisir les notes' }}
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
    :root {
        --esbtp-green: #01632f;
        --esbtp-green-dark: #014a23;
        --esbtp-orange: #f29400;
    }

    .card {
        border: none;
        transition: transform 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .status-select {
        border-color: #dee2e6;
        border-radius: 4px;
        font-size: 0.825rem;
    }

    .table th {
        font-weight: 600;
        font-size: 0.825rem;
        color: #495057;
    }

    .table td {
        font-size: 0.875rem;
    }

    .pagination {
        --bs-pagination-active-bg: var(--esbtp-green);
        --bs-pagination-active-border-color: var(--esbtp-green);
    }

    .btn-primary {
        background-color: var(--esbtp-green);
        border-color: var(--esbtp-green);
    }

    .btn-primary:hover {
        background-color: var(--esbtp-green-dark);
        border-color: var(--esbtp-green-dark);
    }

    .text-primary {
        color: var(--esbtp-green) !important;
    }

    .bg-primary {
        background-color: var(--esbtp-green) !important;
    }

    .select2-container--default .select2-selection--single {
        height: 38px;
        display: flex;
        align-items: center;
    }

    .form-check-input:checked {
        background-color: var(--esbtp-green);
        border-color: var(--esbtp-green);
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
