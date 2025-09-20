@extends('layouts.app')

@section('title', 'Gestion des Notes | ESBTP-yAKRO')

@section('page_title', 'Gestion des Notes')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-graduation-cap me-2"></i>Gestion des Notes</h1>
                <p class="header-subtitle">Liste et gestion des notes des étudiants</p>
            </div>
            <div class="header-actions">
                @if((auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('teacher') || auth()->user()->hasRole('enseignant') || auth()->user()->can('create_grade')) && !auth()->user()->hasRole('coordinateur'))
                <a href="{{ route('esbtp.notes.create') }}" class="btn-acasi primary me-2">
                    <i class="fas fa-plus-circle"></i>Ajouter une note
                </a>
                @endif
                <a href="{{ route('esbtp.notes.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-sync"></i>Actualiser
                </a>
            </div>
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Information année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar me-2"></i>Contexte d'affichage
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
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
                        Les notes affichées correspondent aux évaluations créées dans l'année courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="main-card mb-4">
            <div class="main-card-header" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05));">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres de recherche
                </div>
            </div>
            <div class="main-card-body">
                <form action="{{ route('esbtp.notes.index') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-users"></i>
                                    Classe
                                </label>
                                <select class="form-select-moderne" id="classe_id" name="classe_id">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group-moderne">
                                <label class="form-label-moderne">
                                    <i class="fas fa-book"></i>
                                    Matière
                                </label>
                                <select class="form-select-moderne" id="matiere_id" name="matiere_id">
                                    <option value="">Toutes les matières</option>
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                            {{ $matiere->name ?? $matiere->nom ?? 'Matière sans nom' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-search"></i>Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des notes -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des notes
                </div>
                <div class="main-card-subtitle">{{ count($notes) }} notes trouvées</div>
            </div>
            <div class="main-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0 dataTable" id="notesTable">
                        <thead class="bg-light">
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Classe</th>
                                            <th>Matière</th>
                                            <th>Évaluation</th>
                                            <th>Note</th>
                                            <th>Date</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($notes as $note)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="fas fa-user-graduate fs-4 color-primary"></i>
                                                        </div>
                                                        <div>
                                                            <span class="fw-medium d-block">{{ $note->etudiant->nom }} {{ $note->etudiant->prenoms }}</span>
                                                            <small class="text-muted">{{ $note->etudiant->matricule ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $note->evaluation && $note->evaluation->classe ? $note->evaluation->classe->name : 'N/A' }}</td>
                                                <td>{{ $note->evaluation && $note->evaluation->matiere ? $note->evaluation->matiere->name : 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $typeIcons = [
                                                            'examen' => '<i class="fas fa-file-alt color-primary me-1"></i>',
                                                            'devoir' => '<i class="fas fa-pencil-alt color-success me-1"></i>',
                                                            'tp' => '<i class="fas fa-flask color-warning me-1"></i>',
                                                            'projet' => '<i class="fas fa-project-diagram color-accent me-1"></i>',
                                                            'controle' => '<i class="fas fa-tasks color-neutral me-1"></i>',
                                                            'rattrapage' => '<i class="fas fa-redo color-danger me-1"></i>',
                                                        ];
                                                        $type = $note->evaluation ? $note->evaluation->type : '';
                                                        $icon = $typeIcons[$type] ?? '<i class="fas fa-question-circle color-neutral me-1"></i>';
                                                    @endphp
                                                    <div>
                                                        <span class="d-block">{!! $icon !!} {{ $note->evaluation ? $note->evaluation->titre : 'N/A' }}</span>
                                                        @if($note->evaluation)
                                                            <small class="text-muted">{{ date('d/m/Y', strtotime($note->evaluation->date_evaluation)) }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($note->is_absent)
                                                        <span class="status-badge danger">
                                                            <i class="fas fa-user-slash me-1"></i> Absent
                                                        </span>
                                                    @else
                                                        <span class="status-badge success">
                                                            {{ $note->note }}/{{ $note->evaluation->bareme }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <i class="far fa-calendar-alt text-muted me-1"></i>
                                                    {{ $note->created_at->format('d/m/Y') }}
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <a href="{{ route('esbtp.notes.show', $note->id) }}" class="btn-acasi secondary btn-sm" data-bs-toggle="tooltip" title="Voir les détails">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if((auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('teacher') || auth()->user()->hasRole('enseignant') || auth()->user()->can('edit_grades')) && !auth()->user()->hasRole('coordinateur'))
                                                        <a href="{{ route('esbtp.notes.edit', $note->id) }}" class="btn-acasi warning btn-sm" data-bs-toggle="tooltip" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @endif
                                                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->can('delete_grades'))
                                                        <button type="button" class="btn-acasi danger btn-sm" onclick="confirmDelete('{{ $note->id }}')" data-bs-toggle="tooltip" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="my-4 text-muted">
                                                        <i class="fas fa-info-circle fs-1 mb-3 d-block"></i>
                                                        <p class="mb-0">Aucune note trouvée</p>
                                                        @if((auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('teacher') || auth()->user()->hasRole('enseignant') || auth()->user()->can('create_grade')) && !auth()->user()->hasRole('coordinateur'))
                                                        <p class="small">Utilisez le bouton "Ajouter une note" pour commencer</p>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette note ? Cette action est irréversible.</p>
                <p class="mb-0 small text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    La suppression peut affecter les calculs de moyennes et de bulletins.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>Annuler
                </button>
                <button type="button" class="btn-acasi danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de suppression caché -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les notes affichées se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois.
                    Changer l'année courante affecte l'affichage des notes dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les notes des évaluations de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les notes des évaluations de 2023-2024
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });

    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
});
</script>

@push('styles')
<style>
    /* Styles spécifiques pour DataTables avec framework moderne */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
        color: white !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: rgba(30, 58, 138, 0.1) !important;
        border-color: var(--primary) !important;
        color: var(--primary) !important;
    }

    /* Style pour les hover du tableau */
    .table-hover tbody tr:hover {
        background-color: rgba(30, 58, 138, 0.05);
    }

    /* Animation pour les boutons d'action */
    .btn-acasi.btn-sm {
        transition: transform 0.2s;
    }

    .btn-acasi.btn-sm:hover {
        transform: translateY(-2px);
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialisation des tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Initialisation de Select2
        $('.form-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Initialisation de DataTables avec configuration en français
        $('#notesTable').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json"
            },
            order: [[ 5, "desc" ]],
            responsive: true,
            pageLength: 25,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                'copy', 'excel', 'pdf'
            ]
        });
    });

    // Gestion de la suppression
    let noteIdToDelete;

    function confirmDelete(noteId) {
        noteIdToDelete = noteId;
        $('#deleteModal').modal('show');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        var form = document.getElementById('delete-form');
        form.action = "/esbtp/notes/" + noteIdToDelete;
        form.submit();
    });

    // Animation des badges au survol
    document.querySelectorAll('.badge').forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.2s';
        });
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
</script>
@endpush
