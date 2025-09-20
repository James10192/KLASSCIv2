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
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Évaluations</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalEvaluations }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-file-alt"></i>
                    Toutes les évaluations
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Évaluations Publiées</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $evaluationsPubliees }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Actives
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Examens</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $examens }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Examens officiels
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Devoirs</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $devoirs }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-pencil-alt"></i>
                    Travaux dirigés
                </div>
            </div>
        </div>

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
                        Les évaluations affichées correspondent uniquement à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Section de gestion des liens externes (pour admins/secrétaires uniquement) -->
        @if(auth()->check() && auth()->user() && !auth()->user()->hasRole(['teacher', 'enseignant', 'etudiant']))
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
                            <select class="form-select" name="status" id="status_filter">
                                <option value="">Tous les statuts</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Planifiée</option>
                                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminée</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulée</option>
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
                                                <input type="hidden" name="_method" value="PATCH">
                                                <div class="d-flex align-items-center gap-2" style="min-width: 200px;">
                                                    <select name="status" class="form-select form-select-sm status-select status-{{ $evaluation->status }}" data-original-value="{{ $evaluation->status }}" style="min-width: 150px; padding-right: 30px;">
                                                        <option value="draft" {{ $evaluation->status === 'draft' ? 'selected' : '' }}>📝 Brouillon</option>
                                                        <option value="scheduled" {{ $evaluation->status === 'scheduled' ? 'selected' : '' }}>📅 Planifiée</option>
                                                        <option value="in_progress" {{ $evaluation->status === 'in_progress' ? 'selected' : '' }}>⏳ En cours</option>
                                                        <option value="completed" {{ $evaluation->status === 'completed' ? 'selected' : '' }}>✅ Terminée</option>
                                                        <option value="cancelled" {{ $evaluation->status === 'cancelled' ? 'selected' : '' }}>❌ Annulée</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-success save-status-btn" style="display: none;" title="Sauvegarder les modifications">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
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
                    <li><strong>Revenir ici</strong> : Les évaluations affichées se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois.
                    Changer l'année courante affecte l'affichage des évaluations dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les évaluations créées en 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les évaluations créées en 2023-2024
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
    padding: var(--space-xs) 30px var(--space-xs) var(--space-sm) !important;
    font-size: var(--text-small);
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 140px;
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

/* Status select colors */
.status-select.status-draft {
    border-left: 4px solid #6b7280;
    background-color: #f9fafb;
}

.status-select.status-scheduled {
    border-left: 4px solid #3b82f6;
    background-color: #eff6ff;
}

.status-select.status-in_progress {
    border-left: 4px solid #f59e0b;
    background-color: #fffbeb;
}

.status-select.status-completed {
    border-left: 4px solid #10b981;
    background-color: #ecfdf5;
}

.status-select.status-cancelled {
    border-left: 4px solid #ef4444;
    background-color: #fef2f2;
}

.status-loading {
    font-size: 0.75rem;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.save-status-btn {
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.save-status-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.save-status-btn:disabled {
    opacity: 0.6;
}

.status-form {
    width: 100%;
}

/* Normal styling for save button */
.save-status-btn {
    border: 1px solid #28a745;
    background-color: #28a745;
    color: white;
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
    console.log('🚀 EVALUATION STATUS SCRIPT LOADED');
    console.log('🔍 Found status selects:', $('.status-select').length);
    console.log('🔍 Found save buttons:', $('.save-status-btn').length);
    
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val()
        }
    });
    
    // Initialize save buttons - hide them initially and sync original values
    $('.save-status-btn').each(function() {
        var $btn = $(this);
        var $form = $btn.closest('form');
        var $select = $form.find('.status-select');
        var originalValue = $select.data('original-value');
        var currentValue = $select.val();
        
        // Update data-original-value to match current value (in case it was updated from server)
        $select.attr('data-original-value', currentValue);
        
        // Hide button if status hasn't changed
        if (currentValue === originalValue) {
            $btn.hide();
        } else {
            // If they're different, it means we need to sync them
            $select.attr('data-original-value', currentValue);
            $btn.hide(); // Hide since they're now the same
        }
    });
    
    // Test immediat
    setTimeout(function() {
        console.log('🔧 Testing immediate functionality...');
        $('.status-select').each(function(index) {
            var $select = $(this);
            var originalValue = $select.data('original-value');
            console.log('Select #' + index + ' - Original value:', originalValue, 'Current value:', $select.val());
        });
        
        $('.save-status-btn').each(function(index) {
            console.log('Button #' + index + ' - Display:', $(this).css('display'), 'Visible:', $(this).is(':visible'));
        });
    }, 1000);
    
    // Initialize Select2 only if available
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%'
        });
    } else {
        console.log('Select2 not available, skipping initialization');
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Status change handler - simple approach
    $(document).on('change', '.status-select', function() {
        console.log('🔄 Status changed');
        var select = this;
        var form = $(select).closest('form')[0];
        var saveBtn = $(form).find('.save-status-btn')[0];
        var originalValue = $(select).data('original-value');
        var currentValue = select.value;
        
        console.log('📊 Values - Original:', originalValue, 'Current:', currentValue);
        
        if (currentValue !== originalValue) {
            console.log('✅ Showing save button');
            $(saveBtn).show();
        } else {
            console.log('❌ Hiding save button');
            $(saveBtn).hide();
        }
    });
    
    // Debug: Add direct click handlers and check button state
    setTimeout(function() {
        $('.save-status-btn').each(function(index) {
            var $btn = $(this);
            console.log('🔧 Adding direct click handler to button #' + index);
            console.log('🔍 Button #' + index + ' state:', {
                display: $btn.css('display'),
                visibility: $btn.css('visibility'),
                opacity: $btn.css('opacity'),
                disabled: $btn.prop('disabled'),
                offset: $btn.offset(),
                width: $btn.width(),
                height: $btn.height()
            });
            
            $btn.off('click.debug').on('click.debug', function(e) {
                console.log('🎯 Direct click detected on save button #' + index);
            });
            
            // Also add mousedown/mouseup for more debugging
            $btn.on('mousedown', function() {
                console.log('🖱️ Mouse down on save button #' + index);
            });
            
            $btn.on('mouseup', function() {
                console.log('🖱️ Mouse up on save button #' + index);
            });
        });
    }, 2000);
    
    // Save button click handler
    $(document).on('click', '.save-status-btn', function(e) {
        e.preventDefault();
        console.log('💾 Save button clicked!');
        
        var btn = this;
        var form = $(btn).closest('form')[0];
        var select = $(form).find('.status-select')[0];
        var newValue = $(select).val();
        var originalValue = $(select).attr('data-original-value');
        
        console.log('📊 About to save - Original:', originalValue, 'New:', newValue);
        console.log('📋 Form action:', form.action);
        console.log('📋 Form method:', form.method);
        
        // Debug: Check if _method field exists
        var methodField = $(form).find('input[name="_method"]');
        console.log('🔍 _method field found:', methodField.length > 0);
        if (methodField.length > 0) {
            console.log('🔍 _method value:', methodField.val());
        }
        
        // Debug: Check all form inputs
        console.log('📋 All form inputs:');
        $(form).find('input, select').each(function() {
            console.log('  - ' + this.name + ': ' + this.value);
        });
        
        // Loading state
        $(btn).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $(select).prop('disabled', true);
        
        // Store the new value that will be saved
        $(select).attr('data-pending-value', newValue);
        
        console.log('🚀 Submitting form via AJAX...');
        
        // Get CSRF token from multiple sources for reliability
        var csrfToken = $(form).find('input[name="_token"]').val() || 
                        $('meta[name="csrf-token"]').attr('content') || 
                        $('input[name="_token"]').first().val();
        
        console.log('🔐 CSRF Token:', csrfToken);
        
        // Use AJAX with POST method and _method field (Laravel way)
        $.ajax({
            url: form.action,
            type: 'POST',
            data: {
                _method: 'PATCH',
                _token: csrfToken,
                status: newValue
            },
            success: function(response) {
                console.log('✅ AJAX Success:', response);
                // Reload page to show updated status
                window.location.reload();
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error:', xhr.responseText);
                // Re-enable form on error
                $(btn).prop('disabled', false).html('<i class="fas fa-save"></i>');
                $(select).prop('disabled', false);
                alert('Erreur lors de la sauvegarde: ' + xhr.responseText);
            }
        });
    });
    
    // Handle successful form submission detection
    @if(session('success'))
    console.log('✅ Status update success detected:', '{{ addslashes(session('success')) }}');
    
    // IMPORTANT: The data-original-value in HTML is already correct from server
    // We just need to hide any visible save buttons since the values are now synced
    $('.status-select').each(function() {
        var $select = $(this);
        var currentValue = $select.val();
        var originalValue = $select.attr('data-original-value');
        
        console.log('🔄 Post-save check - Original:', originalValue, 'Current:', currentValue);
        
        // Hide save button since server has updated the data-original-value correctly
        var $form = $select.closest('form');
        var $saveBtn = $form.find('.save-status-btn');
        $saveBtn.hide();
        
        console.log('✅ Save button hidden, values are synced from server');
    });
    @endif

    // Handle filters
    $('#classe_filter, #matiere_filter, #type_filter, #status_filter').change(function() {
        applyFilters();
    });

    // Reset filters
    $('#reset_filters').click(function() {
        $('#classe_filter, #matiere_filter, #type_filter, #status_filter').val('').trigger('change');
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
        const status = $('#status_filter').val();

        if (classe_id) params.set('classe_id', classe_id);
        else params.delete('classe_id');

        if (matiere_id) params.set('matiere_id', matiere_id);
        else params.delete('matiere_id');

        if (type) params.set('type', type);
        else params.delete('type');

        if (status) params.set('status', status);
        else params.delete('status');

        window.location.search = params.toString();
    }
});
</script>
@endpush
