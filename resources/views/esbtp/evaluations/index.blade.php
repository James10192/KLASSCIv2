@extends('layouts.app')

@section('title', 'Liste des évaluations - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

<div class="modal fade coeff-modal" id="coefficientsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-sliders-h me-2"></i>Paramètres des coefficients</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="coefficientsModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted mt-2">Chargement des coefficients...</div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                <input type="search"
                       class="search-bar"
                       id="evaluations-search"
                       placeholder="Rechercher une évaluation..."
                       autocomplete="off"
                       value="{{ $filters['search'] ?? '' }}">
                <button type="button" class="btn-acasi secondary" id="coeff-settings-btn">
                    <i class="fas fa-sliders-h"></i>Coefficients
                </button>
                <a href="{{ route('esbtp.evaluations.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle évaluation
                </a>
            </div>
        </div>
        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Évaluations</div>
                <div class="kpi-value" data-summary-key="total" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalEvaluations }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-file-alt"></i>
                    Toutes les évaluations
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Évaluations Publiées</div>
                <div class="kpi-value" data-summary-key="published" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $evaluationsPubliees }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Actives
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Examens</div>
                <div class="kpi-value" data-summary-key="examens" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $examens }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Examens officiels
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Devoirs</div>
                <div class="kpi-value" data-summary-key="devoirs" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $devoirs }}</div>
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

        <div class="alert alert-info d-flex align-items-start gap-2">
            <i class="fas fa-lightbulb mt-1"></i>
            <div>
                <strong>Bon à savoir :</strong>
                une évaluation non publiée reste en <strong>Brouillon</strong> (invisible aux étudiants) et la saisie des notes est désactivée.
                Après publication, le statut évolue automatiquement (<strong>Planifiée</strong>, <strong>En cours</strong>, <strong>Terminée</strong>) selon la date et la durée.
            </div>
        </div>

        <!-- Section de gestion des liens externes (pour admins/secrétaires uniquement) -->
        @if(auth()->check() && auth()->user() && !auth()->user()->hasAnyPermission(['can_teach', 'can_view_student_features']))
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
                <div class="alert alert-light border d-flex align-items-start gap-3 mb-4" style="background: #f8fafc;">
                    <div class="mt-1 text-primary">
                        <i class="fas fa-lightbulb fa-lg"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold mb-2">Repères rapides</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge rounded-pill" style="background: rgba(4, 83, 203, 0.12); color: #0f172a; border: 1px solid rgba(4, 83, 203, 0.35);">
                                Publier
                            </span>
                            <span class="small text-muted">rend l'évaluation visible et active la saisie des notes.</span>
                            <span class="badge rounded-pill" style="background: rgba(100, 116, 139, 0.14); color: #334155; border: 1px solid rgba(100, 116, 139, 0.35);">
                                Masquer
                            </span>
                            <span class="small text-muted">cache l'évaluation et masque aussi les notes publiées.</span>
                            <span class="badge rounded-pill" style="background: rgba(245, 158, 11, 0.12); color: #92400e; border: 1px solid rgba(245, 158, 11, 0.35);">
                                Annuler
                            </span>
                            <span class="small text-muted">stoppe l'évaluation (aucune saisie possible).</span>
                        </div>
                        <div class="mt-2 small text-muted">
                            Utilisez les cases à cocher pour activer la barre d'actions groupées en bas de page.
                        </div>
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

                    <form id="evaluations-filter-form" class="filters-grid mb-4" autocomplete="off">
                        <div class="filter-field">
                            <label for="classe_filter" class="form-label text-uppercase text-muted small fw-semibold">Classe</label>
                            <select class="form-select filter-select" name="classe_id" id="classe_filter">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ ($filters['classe_id'] ?? null) == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="matiere_filter" class="form-label text-uppercase text-muted small fw-semibold">Matière</label>
                            <select class="form-select filter-select" name="matiere_id" id="matiere_filter">
                                <option value="">Toutes les matières</option>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" {{ ($filters['matiere_id'] ?? null) == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="type_filter" class="form-label text-uppercase text-muted small fw-semibold">Type</label>
                            <select class="form-select filter-select" name="type" id="type_filter">
                                <option value="">Tous les types</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ ($filters['type'] ?? null) === $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="date_debut_filter" class="form-label text-uppercase text-muted small fw-semibold">Date début</label>
                            <input type="date"
                                   class="form-control filter-select"
                                   name="date_debut"
                                   id="date_debut_filter"
                                   value="{{ $filters['date_debut'] ?? '' }}">
                        </div>

                        <div class="filter-field">
                            <label for="date_fin_filter" class="form-label text-uppercase text-muted small fw-semibold">Date fin</label>
                            <input type="date"
                                   class="form-control filter-select"
                                   name="date_fin"
                                   id="date_fin_filter"
                                   value="{{ $filters['date_fin'] ?? '' }}">
                        </div>

                        <div class="filters-actions">
                            <button type="button" class="btn btn-outline-secondary w-100" id="evaluations-clear-filters">
                                <i class="fas fa-undo me-1"></i>Réinitialiser
                            </button>
                        </div>

                        <input type="hidden" name="search" id="filter-search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="per_page" id="filter-per-page" value="{{ $filters['per_page'] ?? 15 }}">
                        <input type="hidden" name="page" id="filter-page" value="{{ request('page', 1) }}">
                    </form>

                    <div id="evaluations-results"
                         data-refresh-url="{{ route('esbtp.evaluations.index') }}"
                         data-row-url-template="{{ route('esbtp.evaluations.refresh-row', ['evaluation' => '__ID__']) }}"
                         data-publish-url-template="{{ route('esbtp.evaluations.toggle-published', ['evaluation' => '__ID__']) }}"
                         data-notes-url-template="{{ route('esbtp.evaluations.toggle-notes-published', ['evaluation' => '__ID__']) }}"
                         data-cancel-url-template="{{ route('esbtp.evaluations.cancel', ['evaluation' => '__ID__']) }}"
                         data-delete-url-template="{{ route('esbtp.evaluations.destroy', ['evaluation' => '__ID__']) }}"
                         data-summary='@json($summary)'
                    >
                        @include('esbtp.evaluations.partials.results', ['evaluations' => $evaluations])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="evaluations-bulk-bar" class="evaluations-bulk-bar" style="display: none;">
    <div class="d-flex align-items-center gap-4 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-check-circle fa-lg"></i>
            <span>
                <strong id="evaluations-selected-count">0</strong>
                évaluation(s) sélectionnée(s)
            </span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-light btn-sm" id="evaluations-bulk-publish">
                <i class="fas fa-eye me-1"></i>Publier
            </button>
            <button type="button" class="btn btn-light btn-sm" id="evaluations-bulk-unpublish">
                <i class="fas fa-eye-slash me-1"></i>Masquer
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="evaluations-bulk-publish-notes">
                <i class="fas fa-clipboard-check me-1"></i>Publier notes
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="evaluations-bulk-cancel">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="evaluations-bulk-delete">
                <i class="fas fa-trash me-1"></i>Supprimer
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="evaluations-bulk-clear">
                <i class="fas fa-times-circle me-1"></i>Annuler la sélection
            </button>
        </div>
    </div>
</div>

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

@push('styles')
<style>
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}

.filter-field .form-label {
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.filter-select {
    background-color: #fff;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 0.5rem;
    padding: 0.55rem 0.75rem;
    font-size: 0.95rem;
    box-shadow: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.filter-select:focus {
    border-color: rgba(37, 99, 235, 0.7);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

.filters-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.evaluations-bulk-bar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: white;
    padding: 15px 30px;
    border-radius: 50px;
    box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4);
    z-index: 1050;
    animation: slideUp 0.3s ease-out;
}

.evaluations-bulk-bar .btn {
    border-radius: 999px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.evaluations-bulk-bar .btn-light {
    color: #0f172a;
}

.evaluations-bulk-bar .btn.bulk-action-animate {
    position: relative;
    overflow: hidden;
}

.evaluations-bulk-bar .btn.bulk-action-animate::after {
    content: "✅";
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%) scale(0.4);
    opacity: 0;
    font-size: 1rem;
    animation: bulkActionCheck 0.9s ease forwards;
}

.evaluations-bulk-bar .btn.bulk-action-animate::before {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.18);
    opacity: 0;
    animation: bulkActionFlash 0.5s ease forwards;
}

.evaluations-bulk-bar.is-processing {
    opacity: 0.7;
    pointer-events: none;
    filter: saturate(0.85);
}

.evaluations-bulk-bar.is-processing::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 50px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    animation: bulkPulse 1.2s ease-in-out infinite;
}

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

@keyframes bulkPulse {
    0% {
        transform: scale(1);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.02);
        opacity: 0.3;
    }
    100% {
        transform: scale(1);
        opacity: 0.6;
    }
}

@keyframes bulkActionCheck {
    0% {
        opacity: 0;
        transform: translateY(-50%) scale(0.4);
    }
    50% {
        opacity: 1;
        transform: translateY(-50%) scale(1.05);
    }
    100% {
        opacity: 0;
        transform: translateY(-50%) scale(0.8);
    }
}

@keyframes bulkActionFlash {
    0% {
        opacity: 0;
    }
    40% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}

#evaluations-results .table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

.evaluation-actions-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 32px;
}

.evaluation-actions-wrapper.is-loading .evaluation-actions-buttons {
    display: none !important;
}

.evaluation-actions-spinner {
    display: none;
    align-items: center;
    justify-content: center;
    min-width: 32px;
}

.evaluation-actions-wrapper.is-loading .evaluation-actions-spinner {
    display: inline-flex !important;
}

.evaluation-row-highlight {
    position: absolute;
    top: 0;
    left: -65%;
    width: 150%;
    height: 100%;
    pointer-events: none;
    opacity: 0;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(37, 99, 235, 0) 0%, rgba(37, 99, 235, 0.5) 50%, rgba(37, 99, 235, 0) 100%);
    z-index: 2;
}

.evaluation-row-highlight.reject {
    background: linear-gradient(90deg, rgba(220, 38, 38, 0) 0%, rgba(220, 38, 38, 0.55) 50%, rgba(220, 38, 38, 0) 100%);
}

.evaluation-row-highlight.animate {
    animation: evaluation-row-highlight-move 2.4s ease-out forwards;
}

.evaluation-row-flash {
    animation: evaluation-row-flash 0.8s ease-in-out;
}

.evaluation-row-flash.reject {
    animation-name: evaluation-row-flash-reject;
}

@keyframes evaluation-row-highlight-move {
    0% {
        opacity: 0;
        transform: translateX(-65%) skewX(-12deg);
    }
    18% {
        opacity: 0.9;
    }
    55% {
        opacity: 0.7;
    }
    100% {
        opacity: 0;
        transform: translateX(115%) skewX(-12deg);
    }
}

@keyframes evaluation-row-flash {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(59, 130, 246, 0.15);
    }
    100% {
        background-color: transparent;
    }
}

@keyframes evaluation-row-flash-reject {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(220, 38, 38, 0.2);
    }
    100% {
        background-color: transparent;
    }
}

#evaluations-results .pagination {
    margin-bottom: 0;
}

/* ── Modal coefficients ── */
.coeff-modal .modal-content {
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(4, 83, 203, 0.2);
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.25);
}

.coeff-modal .modal-header {
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.14), rgba(94, 145, 222, 0.2));
    border-bottom: 1px solid rgba(4, 83, 203, 0.2);
    color: var(--text-primary);
}

.coeff-modal .modal-body {
    background: #f8fafc;
    padding: 1.5rem;
}

/* ── En-tête du partial ── */
.coeff-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

/* ── Intro info-box ── */
.coeff-modal-intro {
    display: flex;
    gap: 0.85rem;
    align-items: flex-start;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    background: rgba(4, 83, 203, 0.08);
    border: 1px solid rgba(4, 83, 203, 0.16);
    margin-bottom: 1.5rem;
}

.coeff-modal-intro .intro-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(4, 83, 203, 0.18);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.coeff-modal-intro .intro-title {
    font-weight: 600;
    color: var(--text-primary);
}

.coeff-modal-intro .intro-text {
    color: var(--text-secondary);
    font-size: 0.85rem;
}

/* ── État vide ── */
.coeff-empty-state {
    text-align: center;
    padding: 2.5rem 1rem;
    color: var(--text-secondary);
}

/* ── Grille de cards ── */
.coeff-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 1.25rem;
}

/* ── Card individuelle ── */
.coeff-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    padding: 1.1rem 1.2rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    transition: box-shadow 0.2s;
}

.coeff-card:hover {
    box-shadow: 0 8px 28px rgba(15, 23, 42, 0.1);
}

/* Bordure colorée selon statut */
.complete-card  { border-left: 4px solid #10b981; }
.partial-card   { border-left: 4px solid #f59e0b; }
.missing-card   { border-left: 4px solid #ef4444; }
.empty-card     { border-left: 4px solid #94a3b8; }

/* ── En-tête card ── */
.coeff-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
}

.coeff-card-title {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    min-width: 0;
    flex: 1;
}

.coeff-ordinal {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(4, 83, 203, 0.12);
    color: var(--primary);
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.coeff-combo-info {
    min-width: 0;
}

.coeff-combo {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-bottom: 0.3rem;
}

.coeff-progress-text {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

/* ── Badge statut ── */
.coeff-status {
    padding: 0.3rem 0.7rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
    flex-shrink: 0;
}

.coeff-status.status-complete {
    background: rgba(16, 185, 129, 0.16);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.35);
}

.coeff-status.status-partial {
    background: rgba(245, 158, 11, 0.16);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

.coeff-status.status-missing {
    background: rgba(239, 68, 68, 0.14);
    color: #b91c1c;
    border: 1px solid rgba(239, 68, 68, 0.35);
}

.coeff-status.status-empty {
    background: rgba(148, 163, 184, 0.18);
    color: #475569;
    border: 1px solid rgba(148, 163, 184, 0.35);
}

/* ── Barre de progression ── */
.coeff-progress-bar-wrap {
    height: 4px;
    border-radius: 99px;
    background: rgba(148, 163, 184, 0.2);
    overflow: hidden;
}

.coeff-progress-bar {
    height: 100%;
    border-radius: 99px;
    transition: width 0.4s ease;
}

.coeff-progress-bar[data-status="complete"] { background: #10b981; }
.coeff-progress-bar[data-status="partial"]  { background: #f59e0b; }
.coeff-progress-bar[data-status="missing"]  { background: #ef4444; }
.coeff-progress-bar[data-status="empty"]    { background: #94a3b8; }

/* ── Corps card ── */
.coeff-card-body {
    display: flex;
    flex-direction: column;
}

/* ── Alertes ── */
.coeff-alert {
    padding: 0.65rem 0.85rem;
    border-radius: 10px;
    font-size: 0.83rem;
    margin-bottom: 0.85rem;
}

.coeff-alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #92400e;
}

.coeff-alert-empty {
    background: rgba(148, 163, 184, 0.12);
    border: 1px dashed rgba(148, 163, 184, 0.4);
    color: #475569;
}

/* ── Grille matières ── */
.coeff-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.coeff-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.55rem 0.75rem;
    border-radius: 10px;
    background: rgba(4, 83, 203, 0.04);
    border: 1px solid rgba(4, 83, 203, 0.1);
    transition: background 0.15s;
}

.coeff-row.has-value {
    background: rgba(16, 185, 129, 0.04);
    border-color: rgba(16, 185, 129, 0.18);
}

.coeff-row.missing-value {
    background: rgba(239, 68, 68, 0.04);
    border-color: rgba(239, 68, 68, 0.14);
}

.coeff-matiere-info {
    flex: 1;
    min-width: 0;
}

.coeff-matiere-name {
    font-weight: 600;
    font-size: 0.87rem;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.coeff-matiere-code {
    font-size: 0.72rem;
    color: var(--text-secondary);
    margin-top: 1px;
}

.coeff-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-shrink: 0;
}

.coeff-current-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    background: rgba(16, 185, 129, 0.14);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.3);
    white-space: nowrap;
}

.coeff-input {
    width: 80px;
    min-width: 80px;
    text-align: center;
}

/* ── Pied card ── */
.coeff-card-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 0.25rem;
    border-top: 1px solid rgba(148, 163, 184, 0.15);
}

.coeff-save-feedback {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
</style>
@endpush

<div class="modal fade coeff-modal" id="coefficientsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-sliders-h me-2"></i>Paramètres des coefficients</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="coefficientsModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted mt-2">Chargement des coefficients...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="coeffMissingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-triangle-exclamation me-2"></i>Coefficient manquant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="coeffMissingModalBody">
            </div>
        </div>
    </div>
</div>





@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filtersForm = document.getElementById('evaluations-filter-form');
    const resultsContainer = document.getElementById('evaluations-results');
    if (!filtersForm || !resultsContainer) {
        return;
    }

    const searchInput = document.getElementById('evaluations-search');
    const hiddenSearch = document.getElementById('filter-search');
    const clearFiltersBtn = document.getElementById('evaluations-clear-filters');
    const perPageInput = document.getElementById('filter-per-page');
    const pageInput = document.getElementById('filter-page');
    const perPageDefault = perPageInput ? perPageInput.value : '15';
    const FILTER_DEBOUNCE = 350;
    let filterTimer;
    const selectedIds = new Set();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const rowUrlTemplate = resultsContainer.dataset.rowUrlTemplate || '';
    const publishUrlTemplate = resultsContainer.dataset.publishUrlTemplate || '';
    const notesUrlTemplate = resultsContainer.dataset.notesUrlTemplate || '';
    const cancelUrlTemplate = resultsContainer.dataset.cancelUrlTemplate || '';
    const deleteUrlTemplate = resultsContainer.dataset.deleteUrlTemplate || '';

    const bulkBar = document.getElementById('evaluations-bulk-bar');
    const bulkCount = document.getElementById('evaluations-selected-count');
    const bulkPublishBtn = document.getElementById('evaluations-bulk-publish');
    const bulkUnpublishBtn = document.getElementById('evaluations-bulk-unpublish');
    const bulkPublishNotesBtn = document.getElementById('evaluations-bulk-publish-notes');
    const bulkCancelBtn = document.getElementById('evaluations-bulk-cancel');
    const bulkDeleteBtn = document.getElementById('evaluations-bulk-delete');
    const bulkClearBtn = document.getElementById('evaluations-bulk-clear');

    let yearModalInstance = null;
    window.showYearChangeInfo = () => {
        const modalElement = document.getElementById('yearChangeModal');
        if (!modalElement || typeof bootstrap === 'undefined') {
            return;
        }
        if (!yearModalInstance) {
            yearModalInstance = new bootstrap.Modal(modalElement);
        }
        yearModalInstance.show();
    };

    function showToast(message, type = 'success') {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
        } else {
            if (type === 'error') {
                console.error('[Evaluations]', message);
            } else {
                console.log('[Evaluations]', message);
            }
        }
    }

    function updateSummary(summary = {}) {
        const counts = summary.counts || {};
        document.querySelectorAll('[data-summary-key]').forEach((node) => {
            const key = node.getAttribute('data-summary-key');
            if (key && Object.prototype.hasOwnProperty.call(counts, key)) {
                node.textContent = counts[key];
            }
        });

        const pagination = summary.pagination || {};
        const rangeNode = resultsContainer.querySelector('[data-summary-range]');
        if (rangeNode) {
            if (pagination.total && pagination.total > 0) {
                rangeNode.textContent = `${pagination.first_item ?? 0} - ${pagination.last_item ?? 0} sur ${pagination.total} évaluation(s)`;
            } else {
                rangeNode.textContent = 'Aucune évaluation ne correspond à vos filtres.';
            }
        }
    }

    function updateBulkBar() {
        if (!bulkBar || !bulkCount) {
            return;
        }
        const count = selectedIds.size;
        bulkCount.textContent = count;
        bulkBar.style.display = count > 0 ? 'block' : 'none';
    }

    function setBulkProcessing(isProcessing) {
        if (!bulkBar) {
            return;
        }
        bulkBar.classList.toggle('is-processing', Boolean(isProcessing));
        bulkBar.querySelectorAll('button').forEach((button) => {
            button.disabled = Boolean(isProcessing);
        });
    }

    function clearSelection() {
        selectedIds.clear();
        resultsContainer.querySelectorAll('.evaluation-checkbox').forEach((checkbox) => {
            checkbox.checked = false;
        });
        const selectAll = resultsContainer.querySelector('#evaluations-select-all');
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        updateBulkBar();
    }

    function resolveActionUrl(template, id) {
        if (!template) {
            return '';
        }
        return template.replace('__ID__', id);
    }

    function getRowMeta(id) {
        const row = resultsContainer.querySelector(`tr[data-evaluation-id="${id}"]`);
        if (!row) {
            return {};
        }
        return {
            row,
            isPublished: row.dataset.isPublished === '1',
            notesPublished: row.dataset.notesPublished === '1',
            status: row.dataset.status || '',
            canPublishNotes: row.dataset.canPublishNotes === '1',
        };
    }

    function setRowLoadingState(id, isLoading) {
        const row = resultsContainer.querySelector(`tr[data-evaluation-id="${id}"]`);
        if (!row) {
            return;
        }
        row.classList.toggle('is-loading', Boolean(isLoading));
        const wrapper = row.querySelector('.evaluation-actions-wrapper');
        if (wrapper) {
            wrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
    }

    function triggerEvaluationRowHighlight(row, actionType = 'update') {
        if (!row) {
            return;
        }
        const isReject = actionType === 'delete' || actionType === 'cancel';
        row.classList.remove('evaluation-row-flash', 'reject');
        void row.offsetWidth;
        const highlight = document.createElement('div');
        highlight.className = 'evaluation-row-highlight';
        if (isReject) {
            highlight.classList.add('reject');
        }
        row.appendChild(highlight);
        requestAnimationFrame(() => highlight.classList.add('animate'));
        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };
        highlight.addEventListener('animationend', cleanup);
        row.classList.add('evaluation-row-flash');
        if (isReject) {
            row.classList.add('reject');
        }
        setTimeout(() => row.classList.remove('evaluation-row-flash', 'reject'), 1200);
    }

    function resolveRowUrl(id) {
        return rowUrlTemplate.replace('__ID__', id);
    }

    function refreshEvaluationRow(id, actionType = 'update') {
        if (!rowUrlTemplate) {
            return Promise.resolve();
        }
        setRowLoadingState(id, true);
        return fetch(resolveRowUrl(id), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success || !data.html) {
                    throw new Error(data.message || 'Réponse invalide');
                }
                const template = document.createElement('template');
                template.innerHTML = data.html.trim();
                const newRow = template.content.querySelector(`tr[data-evaluation-id="${id}"]`);
                const existingRow = resultsContainer.querySelector(`tr[data-evaluation-id="${id}"]`);
                if (existingRow && newRow) {
                    existingRow.replaceWith(newRow);
                    triggerEvaluationRowHighlight(newRow, actionType);
                }
            })
            .catch((error) => {
                showToast(error.message || 'Erreur lors de la mise à jour de la ligne', 'error');
            })
            .finally(() => {
                setRowLoadingState(id, false);
                initRowInteractions();
            });
    }

    function showResultsOverlay() {
        const overlay = document.createElement('div');
        overlay.style.position = 'absolute';
        overlay.style.inset = '0';
        overlay.style.background = 'rgba(255,255,255,0.75)';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = '10';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        resultsContainer.style.position = 'relative';
        resultsContainer.appendChild(overlay);
        return overlay;
    }

    function fetchSummaryOnly() {
        const formData = new FormData(filtersForm);
        const params = new URLSearchParams(formData);
        const url = `${resultsContainer.dataset.refreshUrl}?${params.toString()}`;
        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                updateSummary(data.summary || {});
            })
            .catch((error) => {
                showToast(error.message || 'Erreur lors de la mise à jour des statistiques', 'error');
            });
    }

    function submitFilterForm(pushHistory = true) {
        const formData = new FormData(filtersForm);
        const params = new URLSearchParams(formData);
        const url = `${resultsContainer.dataset.refreshUrl}?${params.toString()}`;
        const overlay = showResultsOverlay();

        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.html) {
                    throw new Error('Template manquant dans la réponse');
                }
                resultsContainer.innerHTML = data.html;
                resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                if (pushHistory && data.url) {
                    history.pushState({}, '', data.url);
                }
                updateSummary(data.summary || {});
                initRowInteractions();
            })
            .catch((error) => {
                showToast(error.message || 'Erreur lors du chargement des évaluations', 'error');
            })
            .finally(() => {
                overlay.remove();
            });
    }

    function handleRowSelection(event) {
        const checkbox = event.target;
        const id = Number(checkbox.value);
        if (checkbox.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        updateBulkBar();
        const selectAll = resultsContainer.querySelector('#evaluations-select-all');
        if (selectAll) {
            const total = resultsContainer.querySelectorAll('.evaluation-checkbox').length;
            selectAll.indeterminate = selectedIds.size > 0 && selectedIds.size < total;
            selectAll.checked = selectedIds.size > 0 && selectedIds.size === total;
        }
    }

    function initRowInteractions() {
        resultsContainer.querySelectorAll('.evaluation-checkbox').forEach((checkbox) => {
            checkbox.addEventListener('change', handleRowSelection);
            if (selectedIds.has(Number(checkbox.value))) {
                checkbox.checked = true;
            }
        });

        const selectAll = resultsContainer.querySelector('#evaluations-select-all');
        if (selectAll) {
            const checkboxes = resultsContainer.querySelectorAll('.evaluation-checkbox');
            selectAll.indeterminate = selectedIds.size > 0 && selectedIds.size < checkboxes.length;
            selectAll.checked = selectedIds.size > 0 && selectedIds.size === checkboxes.length;
            selectAll.addEventListener('change', () => {
                const checked = selectAll.checked;
                resultsContainer.querySelectorAll('.evaluation-checkbox').forEach((checkbox) => {
                    checkbox.checked = checked;
                    const id = Number(checkbox.value);
                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                updateBulkBar();
            });
        }

        resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const href = link.getAttribute('href');
                if (!href || href === '#') {
                    return;
                }
                const url = new URL(href, window.location.origin);
                filtersForm.querySelectorAll('input[name], select[name]').forEach((field) => {
                    const name = field.getAttribute('name');
                    if (!name) {
                        return;
                    }
                    if (url.searchParams.has(name)) {
                        field.value = url.searchParams.get(name);
                    }
                });
                if (pageInput) {
                    pageInput.value = url.searchParams.get('page') ?? '1';
                }
                submitFilterForm();
            });
        });

        resultsContainer.querySelectorAll('[data-evaluation-action]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                handleAction(button);
            });
        });

        const perPageSelect = document.getElementById('evaluations-per-page');
        if (perPageSelect) {
            perPageSelect.value = perPageInput.value || perPageSelect.value;
            perPageSelect.addEventListener('change', () => {
            perPageInput.value = perPageSelect.value;
            if (pageInput) {
                pageInput.value = '1';
            }
            submitFilterForm();
        });
    }

        updateBulkBar();
    }

    function performEvaluationAction({ action, url, method, evaluationId, refreshRow = true }) {
        if (!action || !url || !evaluationId) {
            return Promise.resolve();
        }

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        if (method !== 'GET' && csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        setRowLoadingState(evaluationId, true);

        return fetch(url, {
            method,
            headers
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.message || 'Action impossible');
                }
                if (data.message) {
                    showToast(data.message, 'success');
                }
                if (data.deleted || action === 'delete') {
                    selectedIds.delete(Number(evaluationId));
                    if (refreshRow) {
                        submitFilterForm(false);
                    }
                    return;
                }
                if (refreshRow) {
                    const highlightType = action === 'cancel' ? 'cancel' : 'update';
                    return refreshEvaluationRow(evaluationId, highlightType).then(() => fetchSummaryOnly());
                }
            })
            .catch((error) => {
                showToast(error.message || "Erreur lors du traitement de l'action", 'error');
                setRowLoadingState(evaluationId, false);
            });
    }

    function handleAction(button) {
        const action = button.getAttribute('data-evaluation-action');
        const url = button.getAttribute('data-url');
        const method = button.getAttribute('data-method') || 'POST';
        const row = button.closest('tr[data-evaluation-id]');
        const evaluationId = row ? row.getAttribute('data-evaluation-id') : null;

        if (!action || !url || !evaluationId) {
            return;
        }

        if (action === 'delete' && !window.confirm('Confirmez-vous la suppression de cette évaluation ?')) {
            return;
        }

        if (action === 'cancel' && !window.confirm("Confirmez-vous l'annulation de cette évaluation ?")) {
            return;
        }

        performEvaluationAction({ action, url, method, evaluationId, refreshRow: true });
    }

    function animateBulkButton(button) {
        if (!button) {
            return;
        }
        button.classList.remove('bulk-action-animate');
        void button.offsetWidth;
        button.classList.add('bulk-action-animate');
        setTimeout(() => button.classList.remove('bulk-action-animate'), 900);
    }

    function runBulkAction(actionKey, triggerButton = null) {
        const ids = Array.from(selectedIds);
        if (!ids.length) {
            return;
        }

        const requiresConfirm = ['delete', 'cancel'];
        if (requiresConfirm.includes(actionKey)) {
            const message = actionKey === 'delete'
                ? 'Confirmez-vous la suppression des évaluations sélectionnées ?'
                : "Confirmez-vous l'annulation des évaluations sélectionnées ?";
            if (!window.confirm(message)) {
                return;
            }
        }

        let filteredIds = ids;
        if (actionKey === 'publish') {
            filteredIds = ids.filter((id) => !getRowMeta(id).isPublished);
        } else if (actionKey === 'unpublish') {
            filteredIds = ids.filter((id) => getRowMeta(id).isPublished);
        } else if (actionKey === 'publish-notes') {
            filteredIds = ids.filter((id) => getRowMeta(id).canPublishNotes);
        } else if (actionKey === 'cancel') {
            filteredIds = ids.filter((id) => getRowMeta(id).status !== 'cancelled');
        }

        if (!filteredIds.length) {
            showToast('Aucune évaluation éligible pour cette action.', 'error');
            setBulkProcessing(false);
            return;
        }

        const actionConfig = {
            publish: { template: publishUrlTemplate, method: 'PATCH', action: 'toggle-published' },
            unpublish: { template: publishUrlTemplate, method: 'PATCH', action: 'toggle-published' },
            'publish-notes': { template: notesUrlTemplate, method: 'PATCH', action: 'toggle-notes' },
            cancel: { template: cancelUrlTemplate, method: 'PATCH', action: 'cancel' },
            delete: { template: deleteUrlTemplate, method: 'DELETE', action: 'delete' },
        };

        const config = actionConfig[actionKey];
        if (!config || !config.template) {
            return;
        }

        const shouldRefreshRow = actionKey !== 'delete';
        setBulkProcessing(true);
        filteredIds.reduce((promise, id) => {
            const url = resolveActionUrl(config.template, id);
            return promise.then(() => performEvaluationAction({
                action: config.action,
                url,
                method: config.method,
                evaluationId: id,
                refreshRow: shouldRefreshRow
            }));
        }, Promise.resolve()).then(() => {
            if (!shouldRefreshRow) {
                submitFilterForm(false);
            }
            updateBulkBar();
            animateBulkButton(triggerButton);
        }).finally(() => {
            setBulkProcessing(false);
        });
    }

    function syncFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        filtersForm.querySelectorAll('select[name], input[name]').forEach((field) => {
            const name = field.getAttribute('name');
            if (!name || name === 'search') {
                return;
            }
            field.value = params.get(name) ?? '';
        });
        const searchValue = params.get('search') ?? '';
        if (hiddenSearch) {
            hiddenSearch.value = searchValue;
        }
        if (searchInput) {
            searchInput.value = searchValue;
        }
        if (perPageInput) {
            perPageInput.value = params.get('per_page') ?? perPageDefault;
        }
        if (pageInput) {
            pageInput.value = params.get('page') ?? '1';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (hiddenSearch) {
                hiddenSearch.value = searchInput.value.trim();
            }
            if (pageInput) {
                pageInput.value = '1';
            }
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (hiddenSearch) {
                    hiddenSearch.value = searchInput.value.trim();
                }
                if (pageInput) {
                    pageInput.value = '1';
                }
                submitFilterForm();
            }
        });
    }

    filtersForm.querySelectorAll('select[name], input[type="date"]').forEach((field) => {
        field.addEventListener('change', () => {
            if (pageInput) {
                pageInput.value = '1';
            }
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });
    });

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            filtersForm.reset();
            selectedIds.clear();
            if (hiddenSearch) {
                hiddenSearch.value = '';
            }
            if (searchInput) {
                searchInput.value = '';
            }
            if (perPageInput) {
                perPageInput.value = perPageDefault;
            }
            if (pageInput) {
                pageInput.value = '1';
            }
            updateBulkBar();
            submitFilterForm();
        });
    }

    if (bulkPublishBtn) {
        bulkPublishBtn.addEventListener('click', () => runBulkAction('publish', bulkPublishBtn));
    }
    if (bulkUnpublishBtn) {
        bulkUnpublishBtn.addEventListener('click', () => runBulkAction('unpublish', bulkUnpublishBtn));
    }
    if (bulkPublishNotesBtn) {
        bulkPublishNotesBtn.addEventListener('click', () => runBulkAction('publish-notes', bulkPublishNotesBtn));
    }
    if (bulkCancelBtn) {
        bulkCancelBtn.addEventListener('click', () => runBulkAction('cancel', bulkCancelBtn));
    }
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', () => runBulkAction('delete', bulkDeleteBtn));
    }
    if (bulkClearBtn) {
        bulkClearBtn.addEventListener('click', clearSelection);
    }

    window.addEventListener('popstate', () => {
        syncFiltersFromUrl();
        submitFilterForm(false);
    });

    syncFiltersFromUrl();
    updateSummary(JSON.parse(resultsContainer.dataset.summary || '{}'));
    initRowInteractions();

    const coeffModalElement = document.getElementById('coefficientsModal');
    const coeffModalBody = document.getElementById('coefficientsModalBody');
    const coeffBtn = document.getElementById('coeff-settings-btn');
    const coeffModalUrl = "{{ route('esbtp.evaluations.coefficients.modal') }}";
    const coeffUpdateUrl = "{{ route('esbtp.evaluations.coefficients.update') }}";
    const coeffCheckUrl = "{{ route('esbtp.evaluations.coefficients.check') }}";
    const coeffYearId = "{{ $anneeUniversitaire?->id ?? '' }}";

    function openCoeffModal() {
        if (!coeffModalElement || !coeffModalBody) {
            return;
        }
        coeffModalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted mt-2">Chargement des coefficients...</div>
            </div>
        `;
        fetch(coeffModalUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Chargement impossible');
                }
                coeffModalBody.innerHTML = data.html;
            })
            .catch((error) => {
                const message = error?.message || 'Erreur de chargement des coefficients.';
                coeffModalBody.innerHTML = `<div class="alert alert-danger">${message}</div>`;
            });

        const modal = new bootstrap.Modal(coeffModalElement);
        modal.show();
    }

    if (coeffBtn) {
        coeffBtn.addEventListener('click', openCoeffModal);
    }

    coeffModalElement?.addEventListener('submit', function (event) {
        const form = event.target.closest('.coeff-card');
        if (!form) {
            return;
        }
        event.preventDefault();

        const formData = new FormData(form);
        formData.append('filiere_id', form.dataset.filiereId || '');
        formData.append('niveau_etude_id', form.dataset.niveauId || '');
        formData.append('annee_universitaire_id', coeffYearId || '');

        const saveBtn = form.querySelector('.coeff-save-btn');
        const saveFeedback = form.querySelector('.coeff-save-feedback');
        if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...'; }

        fetch(coeffUpdateUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Enregistrement impossible');
                }
                if (saveFeedback) {
                    saveFeedback.classList.remove('d-none');
                    setTimeout(() => saveFeedback.classList.add('d-none'), 2500);
                }
            })
            .catch(error => {
                const toast = document.createElement('div');
                toast.className = 'alert alert-danger mt-2 mb-0';
                toast.style.cssText = 'font-size:0.83rem;padding:0.5rem 0.75rem';
                toast.textContent = error.message;
                form.querySelector('.coeff-card-footer')?.before(toast);
                setTimeout(() => toast.remove(), 3500);
            })
            .finally(() => {
                if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Enregistrer'; }
            });
    });

    if (new URLSearchParams(window.location.search).get('open_coefficients') === '1') {
        openCoeffModal();
    }
});
</script>
@endpush
