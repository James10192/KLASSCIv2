@extends('layouts.app')

@section('title', 'Gestion des Notes | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .class-card-header {
        display: flex;
        align-items: flex-start;
        gap: var(--space-sm);
        margin-bottom: var(--space-sm);
    }
    .class-header-text {
        flex: 1;
        min-width: 0;
    }
    .class-title {
        font-weight: 700;
        color: var(--text-primary);
        font-size: var(--text-normal);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .class-code {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: 2px;
    }
    .class-badges {
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: flex-end;
    }
    .class-meta {
        margin-bottom: var(--space-md);
    }
    .meta-line {
        font-size: var(--text-small);
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    .meta-sub {
        display: block;
        color: var(--text-muted);
        margin-left: 16px;
    }
    .notes-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-sm);
        padding: var(--space-sm);
        background: rgba(248, 250, 252, 0.8);
        border-radius: var(--radius-small);
        margin-bottom: var(--space-md);
    }
    .notes-kpi-grid + .notes-kpi-grid {
        margin-top: var(--space-sm);
    }
    .notes-kpi-item {
        text-align: center;
    }
    .notes-kpi-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .notes-kpi-value {
        font-weight: 700;
        color: var(--text-primary);
    }
    .notes-averages {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
        margin-bottom: var(--space-md);
    }
    .avg-chip {
        padding: 4px 8px;
        border-radius: 999px;
        font-size: var(--text-small);
        background: rgba(59, 130, 246, 0.12);
        color: #1e3a8a;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
    .avg-chip.highlight {
        background: rgba(16, 185, 129, 0.12);
        color: #065f46;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .class-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f3f4f6;
        padding-top: var(--space-md);
    }
    .notes-hint {
        font-size: var(--text-small);
        color: var(--text-muted);
    }
    .notes-action-text {
        margin-top: 4px;
    }
    .period-row th {
        background: #eef2f7;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
    }
    .period-cell.semester-1 {
        background: rgba(59, 130, 246, 0.1);
        color: #1e3a8a;
    }
    .period-cell.semester-2 {
        background: rgba(245, 158, 11, 0.12);
        color: #92400e;
    }
    .period-cell.summary {
        background: rgba(16, 185, 129, 0.12);
        color: #065f46;
    }
    .evaluation-period {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 6px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        margin-top: 4px;
    }
    .evaluation-period.semester-1 {
        background: rgba(59, 130, 246, 0.15);
        color: #1e3a8a;
    }
    .evaluation-period.semester-2 {
        background: rgba(245, 158, 11, 0.2);
        color: #92400e;
    }
</style>
@endpush

@section('page_title', 'Gestion des Notes')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-graduation-cap me-2"></i>Gestion des Notes</h1>
                <p class="header-subtitle">Saisie et gestion des notes par classe et matière</p>
            </div>
            <div class="header-actions">
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

        <!-- Filtres avancés -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres de recherche
                </div>
                <form method="GET" action="{{ route('esbtp.notes.index') }}" id="filtersForm">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md); margin-bottom: var(--space-md);">
                        <div>
                            <label for="search" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Recherche</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom ou code de classe..." class="form-control" style="width: 100%;">
                        </div>
                        <div>
                            <label for="filiere_id" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Filière</label>
                            <select name="filiere_id" id="filiere_id" class="form-control" style="width: 100%;">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="niveau_id" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Niveau</label>
                            <select name="niveau_id" id="niveau_id" class="form-control" style="width: 100%;">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="statut" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Statut</label>
                            <select name="statut" id="statut" class="form-control" style="width: 100%;">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('statut') == 'active' ? 'selected' : '' }}>Actives</option>
                                <option value="inactive" {{ request('statut') == 'inactive' ? 'selected' : '' }}>Inactives</option>
                            </select>
                        </div>
                        <div>
                            <label for="capacite" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Capacité</label>
                            <select name="capacite" id="capacite" class="form-control" style="width: 100%;">
                                <option value="">Toutes</option>
                                <option value="disponible" {{ request('capacite') == 'disponible' ? 'selected' : '' }}>Disponibles</option>
                                <option value="pleine" {{ request('capacite') == 'pleine' ? 'selected' : '' }}>Pleines</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--space-md); align-items: center; flex-wrap: wrap;">
                        <button type="submit" class="btn-acasi primary">
                            <i class="fas fa-search me-1"></i>Filtrer
                        </button>
                        <button type="button" id="reset-filters-btn" class="btn-acasi secondary">
                            <i class="fas fa-times me-1"></i>Réinitialiser
                        </button>
                        <div style="margin-left: auto; font-size: var(--text-small); color: var(--text-muted);">
                            <i class="fas fa-list me-1"></i><span id="classes-count">{{ count($classes) }}</span> classe(s) trouvée(s)
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des classes en grid moderne -->
        <div class="card-moderne" style="padding: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-list me-2"></i>Classes disponibles pour la saisie des notes
                <span class="section-subtitle">{{ count($classes) }} classes trouvées</span>
            </div>
            <div id="classes-results" style="margin-top: var(--space-lg);">
                <div class="resultats-grid" id="classes-grid">
                    @include('esbtp.notes.partials.classes-items', ['classes' => $classes, 'classStatsById' => $classStatsById])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sélection Classe et Matière -->
<div class="modal fade notes-management-modal" id="classSelectionModal" tabindex="-1" aria-labelledby="classSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content notes-modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="classSelectionModalLabel">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Gestion des Notes - <span id="selectedClassLabel">Sélectionnez une classe</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="notes-modal-intro">
                    <div class="intro-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="intro-title">Saisie intelligente des notes</div>
                        <div class="intro-subtitle">Choisissez une matiere, creez des evaluations et saisissez les notes en temps reel.</div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label fw-bold">Matière</label>
                            <select class="form-select" id="matiereSelect">
                                <option value="">-- Sélectionner une matière --</option>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}">{{ $matiere->name ?? $matiere->nom ?? 'Matière sans nom' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-bold">Période</label>
                            <select class="form-select" id="periodeFilter">
                                <option value="all">Toutes</option>
                                <option value="semestre1">Semestre 1</option>
                                <option value="semestre2">Semestre 2</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-bold">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary w-100" onclick="createEvaluation()" id="createEvaluationBtn">
                                        <i class="fas fa-plus me-1"></i> Créer évaluation
                                    </button>
                                </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive notes-grid-wrapper">
                    <table class="table table-bordered table-hover notes-grid-table" id="notesGrid">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 160px; min-width: 160px;">Étudiants</th>
                                <!-- Colonnes d'évaluations seront ajoutées dynamiquement ici -->
                                <th class="notes-average-col" style="min-width: 90px;">Moyenne</th>
                                <th class="notes-appreciation-col" style="min-width: 110px;">Appréciation</th>
                            </tr>
                        </thead>
                        <tbody id="studentsRows">
                            <!-- Rows étudiants seront ajoutées dynamiquement ici -->
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                                    Sélectionnez d'abord une classe et une matière pour afficher les notes
                                </td>
                            </tr>
                        </tbody>
                        <tfoot id="classAveragesRow" style="display: none;">
                            <!-- Row moyennes classe sera ajoutée dynamiquement ici -->
                        </tfoot>
                    </table>
                </div>

                <div class="mt-2">
                    <div class="alert alert-info alert-sm py-2 px-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <small>Les notes sont automatiquement enregistrées à chaque modification.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer notes-modal-footer">
                <a href="#" class="btn btn-outline-primary disabled" id="exportBlankPdfBtn" target="_blank" rel="noopener" aria-disabled="true" tabindex="-1">
                    <i class="fas fa-file-pdf me-1"></i>Exporter feuille vierge (PDF)
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
                <button type="button" class="btn btn-success" id="saveAllNotesBtn" style="display: none;">
                    <i class="fas fa-save me-1"></i>Enregistrer toutes les notes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal création d'évaluation (autonome, sans embed AJAX) -->
<div class="modal fade" id="evaluationCreateModal" tabindex="-1"
     aria-labelledby="evaluationCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content eval-modal-content">

            {{-- Header --}}
            <div class="modal-header eval-modal-header">
                <div>
                    <h5 class="modal-title text-white mb-1" id="evaluationCreateModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Créer une évaluation
                    </h5>
                    <p id="evalModal_context" class="eval-modal-context mb-0"></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            {{-- Bandeau publication automatique --}}
            <div class="eval-autopublish-notice">
                <i class="fas fa-check-circle eval-autopublish-icon"></i>
                <span><strong>Publication automatique</strong> — L'évaluation sera publiée immédiatement pour permettre la saisie des notes.</span>
            </div>

            {{-- Body --}}
            <div class="modal-body eval-modal-body">
                <form id="evaluationCreateForm"
                      action="{{ route('esbtp.evaluations.store') }}" method="POST"
                      novalidate>
                    @csrf
                    <input type="hidden" name="embed"        value="1">
                    <input type="hidden" name="is_published" value="1">
                    <input type="hidden" id="evalModal_classe_id"  name="classe_id">
                    <input type="hidden" id="evalModal_matiere_id" name="matiere_id">

                    {{-- Zone d'erreurs globales --}}
                    <div id="evalModal_errors" class="eval-errors-zone" style="display:none;"></div>

                    {{-- Section 1 : Informations générales --}}
                    <div class="eval-section">
                        <div class="eval-section-header">
                            <span class="eval-section-num">1</span>
                            <span class="eval-section-label">Informations générales</span>
                        </div>
                        <div class="eval-section-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="eval_titre">
                                    Titre <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="eval_titre" name="titre"
                                       class="form-control"
                                       placeholder="Ex : Devoir de mathématiques n°2"
                                       maxlength="255" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_type">
                                        Type <span class="text-danger">*</span>
                                    </label>
                                    <select id="eval_type" name="type" class="form-select" required>
                                        <option value="">— Choisir —</option>
                                        @foreach($evaluationTypes as $typeKey => $typeLabel)
                                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_periode">
                                        Période <span class="text-danger">*</span>
                                    </label>
                                    <select id="eval_periode" name="periode" class="form-select" required>
                                        <option value="">— Choisir —</option>
                                        <option value="semestre1">Semestre 1</option>
                                        <option value="semestre2">Semestre 2</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2 : Date & Horaires --}}
                    <div class="eval-section">
                        <div class="eval-section-header">
                            <span class="eval-section-num">2</span>
                            <span class="eval-section-label">Date &amp; Horaires</span>
                        </div>
                        <div class="eval-section-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="eval_date">
                                    Date d'évaluation <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="eval_date" name="date_evaluation"
                                       class="form-control"
                                       max="{{ date('Y-m-d') }}" required>
                                <div class="form-text">
                                    <i class="fas fa-calendar-check me-1 text-muted"></i>
                                    Seules les dates passées ou d'aujourd'hui sont acceptées (évaluation déjà réalisée).
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="row g-3 align-items-end">
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold" for="eval_heure_debut">
                                        Heure de début <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" id="eval_heure_debut" name="heure_debut"
                                           class="form-control" value="08:00" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold" for="eval_heure_fin">
                                        Heure de fin <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" id="eval_heure_fin" name="heure_fin"
                                           class="form-control" value="10:00" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold" for="eval_duree">
                                        Durée <span class="text-muted fw-normal">(min)</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" id="eval_duree" name="duree_minutes"
                                               class="form-control" min="0" max="720"
                                               placeholder="Auto">
                                        <span class="input-group-text eval-duree-badge" id="evalDureeBadge">120 min</span>
                                    </div>
                                    <div class="form-text">Calculée automatiquement</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3 : Barème & Coefficient --}}
                    <div class="eval-section">
                        <div class="eval-section-header">
                            <span class="eval-section-num">3</span>
                            <span class="eval-section-label">Barème &amp; Coefficient</span>
                        </div>
                        <div class="eval-section-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_bareme">
                                        Barème <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" id="eval_bareme" name="bareme"
                                           class="form-control" value="20"
                                           min="1" step="0.5" required>
                                    <div class="form-text">Note maximale (ex : 20)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_coefficient">
                                        Coefficient <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" id="eval_coefficient" name="coefficient"
                                           class="form-control" value="1"
                                           min="0.1" max="10" step="0.1" required>
                                    <div class="form-text">Poids dans la moyenne (0,1 – 10)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4 : Description --}}
                    <div class="eval-section">
                        <div class="eval-section-header">
                            <span class="eval-section-num">4</span>
                            <span class="eval-section-label">
                                Description
                                <span class="eval-optional-badge">optionnelle</span>
                            </span>
                        </div>
                        <div class="eval-section-body">
                            <textarea id="eval_description" name="description"
                                      class="form-control" rows="3"
                                      placeholder="Chapitres couverts, instructions pour les étudiants…"></textarea>
                        </div>
                    </div>

                    {{-- Section 5 : Enseignant (non-enseignants seulement) --}}
                    @if(auth()->check() && !auth()->user()->hasRole(['teacher', 'enseignant']))
                    <div class="eval-section">
                        <div class="eval-section-header">
                            <span class="eval-section-num">5</span>
                            <span class="eval-section-label">
                                Enseignant
                                <span class="eval-optional-badge">optionnel</span>
                            </span>
                        </div>
                        <div class="eval-section-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_enseignant_id">
                                        Enseignant plateforme
                                    </label>
                                    <select id="eval_enseignant_id" name="enseignant_id" class="form-select">
                                        <option value="">— Sélectionner —</option>
                                        @foreach($enseignants as $enseignant)
                                            <option value="{{ $enseignant->id }}">{{ $enseignant->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Peut se connecter et saisir les notes</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_enseignant_ext">
                                        Enseignant externe
                                    </label>
                                    <input type="text" id="eval_enseignant_ext"
                                           name="enseignant_externe_nom"
                                           class="form-control" placeholder="Nom complet">
                                    <div class="form-text">Sans compte sur la plateforme</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                </form>
            </div>

            {{-- Footer --}}
            <div class="modal-footer eval-modal-footer">
                <button type="button" class="btn btn-light border fw-semibold" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" id="evalModal_submit" class="btn eval-submit-btn">
                    <span id="evalModal_submitSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                    <i class="fas fa-save me-2" id="evalModal_submitIcon"></i>Créer l'évaluation
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal info : comment changer d'année académique -->
<div class="modal fade" id="yearChangeInfoModal" tabindex="-1" aria-labelledby="yearChangeInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: #fff;">
                <h5 class="modal-title" id="yearChangeInfoModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Changer d'année académique
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3" style="font-size: 0.92rem;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Pourquoi l'année est-elle verrouillée ici ?</strong><br>
                    La page de saisie des notes affiche uniquement les évaluations de <strong>l'année académique courante</strong>. Ce choix est intentionnel pour éviter toute confusion entre les notes de différentes années.
                </div>
                <p class="mb-2" style="font-size: 0.92rem;">Pour changer d'année académique, rendez-vous dans la gestion des années universitaires et cliquez sur <strong>"Définir comme courante"</strong> sur l'année souhaitée.</p>
                <div class="d-flex align-items-center gap-2 mt-3 p-3 rounded" style="background: #f1f5f9; border-left: 4px solid #0453cb;">
                    <i class="fas fa-cog text-primary"></i>
                    <div>
                        <div style="font-weight: 600; font-size: 0.88rem;">Année courante</div>
                        <div style="font-size: 0.85rem; color: #64748b;">{{ $anneeAcademique }}</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8fafc;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
                @if(auth()->user()->hasRole('superAdmin'))
                <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i>Gérer les années
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Variables globales
let currentClassId = null;
let currentClassname = '';
let currentMatiereId = null;
let currentMatiereName = '';
let currentPeriodeFilter = 'all';
let evaluationsData = {}; // Stocke les évaluations par matière
let notesData = {}; // Stocke les notes existantes
let currentEvaluations = [];
const blankPdfUrlTemplate = '{{ route("esbtp.notes.saisie-rapide-blank.pdf", ["classe" => ":classId"]) }}';

// Initialisation
$(document).ready(function() {
    const filtersForm = document.getElementById('filtersForm');
    const classesGrid = document.getElementById('classes-grid');
    const classesCountSpan = document.getElementById('classes-count');
    const resetFiltersBtn = document.getElementById('reset-filters-btn');
    const filterInputs = filtersForm ? filtersForm.querySelectorAll('select, input[name="search"]') : [];
    const exportBlankPdfBtn = document.getElementById('exportBlankPdfBtn');

    function fetchClasses() {
        if (!filtersForm || !classesGrid) return;

        const formData = new FormData(filtersForm);
        formData.set('classes_ajax', '1');
        const params = new URLSearchParams(formData);

        fetch(`${filtersForm.action}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error('Erreur lors du chargement des classes.');
                }
                classesGrid.innerHTML = data.html;
                if (classesCountSpan && typeof data.total !== 'undefined') {
                    classesCountSpan.textContent = data.total;
                }
            })
            .catch(error => {
                console.error(error);
                alert('Impossible de charger les classes.');
            });
    }

    if (filtersForm) {
        filtersForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchClasses();
        });
    }

    filterInputs.forEach((input) => {
        input.addEventListener('change', fetchClasses);
        if (input.getAttribute('name') === 'search') {
            input.addEventListener('input', function() {
                clearTimeout(window.notesSearchDebounce);
                window.notesSearchDebounce = setTimeout(fetchClasses, 300);
            });
        }
    });

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function(event) {
            event.preventDefault();
            if (!filtersForm) return;
            filtersForm.reset();
            fetchClasses();
        });
    }

    // Sélection de classe depuis les cartes
    $(document).on('click', '.class-card', function(e) {
        if ($(e.target).closest('.class-select-btn').length) {
            return;
        }

        const classId = $(this).attr('data-classe-id');
        const classLabel = $(this).attr('data-class-label');
        selectClass(classId, classLabel);
    });

    $(document).on('click', '.class-select-btn', function(e) {
        e.stopPropagation();
        const card = $(this).closest('.class-card');
        const classId = card.attr('data-classe-id');
        const classLabel = card.attr('data-class-label');
        selectClass(classId, classLabel);
    });

    // Gestion de la sélection de matière
    $('#matiereSelect').on('change', function() {
        currentMatiereId  = $(this).val();
        currentMatiereName = $(this).find('option:selected').text().trim();
        if (currentClassId && currentMatiereId) {
            loadEvaluationsAndNotes();
        }
    });

    $('#periodeFilter').on('change', function() {
        currentPeriodeFilter = $(this).val();
        if (currentClassId && currentMatiereId) {
            buildNotesGrid();
        }
    });

    // Initialiser le modal
    $('#classSelectionModal').on('shown.bs.modal', function() {
        if (currentClassId) {
            $('#selectedClassLabel').text(currentClassname);
        }
    });

    $('#evaluationCreateModal').on('shown.bs.modal', function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const lastBackdrop = backdrops[backdrops.length - 1];
        if (lastBackdrop) {
            lastBackdrop.classList.add('evaluation-backdrop');
        }
    });

    // Nettoyage backdrop après fermeture du modal principal notes
    $('#classSelectionModal').on('hidden.bs.modal', function() {
        setTimeout(function() {
            document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 150);
    });

    $(document).on('click', '#exportBlankPdfBtn.disabled', function(event) {
        event.preventDefault();
    });
});


// Fonction pour sélectionner une classe
function selectClass(classId, className) {
    currentClassId = classId;
    currentClassname = className;
    
    // Mettre à jour l'UI
    $('#selectedClassLabel').text(className);
    updateBlankPdfLink();
    
    // Réinitialiser la sélection de matière
    $('#matiereSelect').val('');
    currentMatiereId = null;
    
    // Vider le tableau
    $('#studentsRows').html(`
        <tr>
            <td colspan="10" class="text-center text-muted py-5">
                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                Sélectionnez une matière pour afficher les notes
            </td>
        </tr>
    `);
    
    // Montrer le modal
    $('#classSelectionModal').modal('show');
}

function updateBlankPdfLink() {
    const btn = document.getElementById('exportBlankPdfBtn');
    if (!btn) {
        return;
    }

    if (!currentClassId) {
        btn.setAttribute('href', '#');
        btn.classList.add('disabled');
        btn.setAttribute('aria-disabled', 'true');
        btn.setAttribute('tabindex', '-1');
        return;
    }

    const url = blankPdfUrlTemplate.replace(':classId', currentClassId);
    btn.setAttribute('href', url);
    btn.classList.remove('disabled');
    btn.removeAttribute('aria-disabled');
    btn.setAttribute('tabindex', '0');
}

// Fonction pour charger les évaluations et notes
function loadEvaluationsAndNotes() {
    if (!currentClassId || !currentMatiereId) return;

    // Afficher un indicateur de chargement
    $('#studentsRows').html(`
        <tr>
            <td colspan="10" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3">Chargement des données...</p>
            </td>
        </tr>
    `);

    // Appel AJAX pour récupérer les évaluations et notes
    $.ajax({
        url: '{{ route("esbtp.notes.evaluations.by-class-matiere", ["classId" => ":classId", "matiereId" => ":matiereId"]) }}'
            .replace(':classId', currentClassId)
            .replace(':matiereId', currentMatiereId),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            evaluationsData = response.evaluations || {};
            notesData = response.notes || {};
            
            // Reconstruire le tableau
            buildNotesGrid();
        },
        error: function(xhr) {
            console.error('Erreur lors du chargement des données:', xhr);
            $('#studentsRows').html(`
                <tr>
                    <td colspan="10" class="text-center text-danger py-5">
                        <i class="fas fa-exclamation-circle fa-2x mb-3 d-block"></i>
                        Erreur lors du chargement des données
                    </td>
                </tr>
            `);
        }
    });
}

function normalizePeriode(periode) {
    if (!periode) {
        return 'semestre1';
    }
    if (periode === '1' || periode === 1 || periode === 'semestre1') {
        return 'semestre1';
    }
    if (periode === '2' || periode === 2 || periode === 'semestre2') {
        return 'semestre2';
    }
    return periode;
}

// Fonction pour construire la grille des notes
function buildNotesGrid() {
    const evaluations = Object.values(evaluationsData);
    const filteredEvaluations = currentPeriodeFilter === 'all'
        ? evaluations
        : evaluations.filter(evaluation => normalizePeriode(evaluation.periode) === currentPeriodeFilter);

    const sortedEvaluations = [...filteredEvaluations].sort((a, b) => {
        const periodA = normalizePeriode(a.periode) === 'semestre2' ? 2 : 1;
        const periodB = normalizePeriode(b.periode) === 'semestre2' ? 2 : 1;
        if (periodA !== periodB) {
            return periodA - periodB;
        }
        const dateA = a.date_evaluation ? new Date(a.date_evaluation) : null;
        const dateB = b.date_evaluation ? new Date(b.date_evaluation) : null;
        if (dateA && dateB) {
            return dateA - dateB;
        }
        return 0;
    });

    currentEvaluations = sortedEvaluations;
    
    // Récupérer les étudiants de la classe
    $.ajax({
        url: '{{ route("esbtp.notes.classes.students", ["classe" => ":classId"]) }}'.replace(':classId', currentClassId),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!response.success) {
                console.error('Erreur API:', response.message);
                return;
            }
            
            const students = response.students || [];
            
            // Construire l'en-tête du tableau avec les évaluations
            const thead = $('#notesGrid thead');
            thead.empty();

            const periodCounts = {
                semestre1: sortedEvaluations.filter(evaluation => normalizePeriode(evaluation.periode) === 'semestre1').length,
                semestre2: sortedEvaluations.filter(evaluation => normalizePeriode(evaluation.periode) === 'semestre2').length,
            };

            const periodRow = $('<tr class="period-row"></tr>');
            periodRow.append('<th class="notes-student-col" style="width: 160px; min-width: 160px; position: sticky; left: 0; top: 0; z-index: 8; background: #ffffff;">Étudiants</th>');
            if (periodCounts.semestre1 > 0) {
                periodRow.append(`<th colspan="${periodCounts.semestre1}" class="period-cell semester-1 text-center">Semestre 1</th>`);
            }
            if (periodCounts.semestre2 > 0) {
                periodRow.append(`<th colspan="${periodCounts.semestre2}" class="period-cell semester-2 text-center">Semestre 2</th>`);
            }
            periodRow.append('<th colspan="2" class="period-cell summary text-center">Synthèse</th>');
            thead.append(periodRow);

            const evalRow = $('<tr></tr>');
            evalRow.append('<th class="notes-student-col" style="width: 160px; min-width: 160px; position: sticky; left: 0; top: 0; z-index: 7; background: #ffffff;">Étudiants</th>');

            sortedEvaluations.forEach(evaluation => {
                const periodKey = normalizePeriode(evaluation.periode);
                const periodLabel = periodKey === 'semestre2' ? 'S2' : 'S1';
                const header = `
                    <th id="evalHeader${evaluation.id}" class="evaluation-header" style="min-width: 130px;">
                        <div class="evaluation-title">${evaluation.titre || 'Éval'}</div>
                        <div class="evaluation-controls">
                            <div class="evaluation-control">
                                <span class="control-label">Barème</span>
                                <input type="number" value="${evaluation.bareme || 20}"
                                       class="form-control form-control-sm bareme-input"
                                       data-eval-id="${evaluation.id}"
                                       title="Barème">
                            </div>
                            <div class="evaluation-control">
                                <span class="control-label">Coeff</span>
                                <input type="number" value="${evaluation.coefficient || 1}"
                                       class="form-control form-control-sm coeff-input"
                                       data-eval-id="${evaluation.id}"
                                       title="Coefficient">
                            </div>
                        </div>
                        <div class="evaluation-type">${evaluation.type || 'Devoir'}</div>
                        <div class="evaluation-period ${periodKey}">${periodLabel}</div>
                    </th>
                `;
                evalRow.append(header);
            });

            evalRow.append('<th class="notes-average-col" style="min-width: 90px; position: sticky; right: 110px; top: 0; z-index: 6; background: #f8fafc;">Moyenne</th>');
            evalRow.append('<th class="notes-appreciation-col" style="min-width: 110px; position: sticky; right: 0; top: 0; z-index: 7; background: #f8fafc;">Appréciation</th>');

            thead.append(evalRow);
            
            // Construire les lignes des étudiants
            const tbody = $('#studentsRows');
            tbody.empty();
            
            students.forEach(student => {
                const row = $(`
                    <tr data-student-id="${student.id}">
                        <td class="fw-medium notes-student-col">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="fas fa-user-graduate text-primary"></i>
                                </div>
                                <div>
                                    ${student.nom} ${student.prenoms}
                                    <br>
                                    <small class="text-muted">${student.matricule || ''}</small>
                                </div>
                            </div>
                        </td>
                    </tr>
                `);
                
                // Ajouter les colonnes d'évaluations
                sortedEvaluations.forEach(evaluation => {
                    const note = notesData[student.id]?.[evaluation.id] || '';
                    const isAbsent = notesData[student.id]?.[evaluation.id + '_absent'] || false;
                    
                    const noteCell = `
                        <td class="text-center">
                            <div class="position-relative">
                                <input type="number" 
                                       class="form-control note-input" 
                                       value="${note}"
                                       data-student-id="${student.id}"
                                       data-eval-id="${evaluation.id}"
                                       step="0.25"
                                       min="0"
                                       max="${evaluation.bareme || 20}"
                                       style="text-align: center;"
                                       onchange="saveNote(${student.id}, ${evaluation.id}, this.value)">
                                <div class="form-check form-check-inline position-absolute" style="top: 5px; right: 5px;">
                                    <input class="form-check-input absence-checkbox" 
                                           type="checkbox" 
                                           id="absent-${student.id}-${evaluation.id}"
                                           data-student-id="${student.id}"
                                           data-eval-id="${evaluation.id}"
                                           ${isAbsent ? 'checked' : ''}
                                           onchange="toggleAbsence(${student.id}, ${evaluation.id}, this.checked)">
                                    <label class="form-check-label small" for="absent-${student.id}-${evaluation.id}" title="Absent">
                                        <i class="fas fa-user-slash"></i>
                                    </label>
                                </div>
                            </div>
                        </td>
                    `;
                    row.append(noteCell);
                });
                
                // Colonnes moyenne et appréciation
                row.append('<td class="text-center fw-bold average-cell notes-average-col">--</td>');
                row.append('<td class="text-center notes-appreciation-col"><span class="badge bg-secondary appreciation-badge">--</span></td>');
                
                tbody.append(row);
            });
            
            // Construire la ligne des moyennes de classe
            buildClassAveragesRow(sortedEvaluations);
            
            // Calculer les moyennes initiales
            calculateAllAverages();
            
            // Afficher le bouton d'enregistrement
            $('#saveAllNotesBtn').show();
        },
        error: function(xhr) {
            console.error('Erreur lors du chargement des étudiants:', xhr);
        }
    });
}

// Fonction pour construire la ligne des moyennes de classe
function buildClassAveragesRow(evaluations) {
    const tfoot = $('#classAveragesRow');
    tfoot.empty().show();
    
    const row = $('<tr class="bg-light fw-bold"></tr>');
    row.append('<td class="text-end notes-student-col">Moyenne Classe</td>');
    
    evaluations.forEach(evaluation => {
        row.append(`<td class="text-center class-avg-${evaluation.id}">--</td>`);
    });
    
    row.append('<td class="text-center class-overall-avg notes-average-col">--</td>');
    row.append('<td class="notes-appreciation-col"></td>');
    
    tfoot.append(row);
}

// Fonction pour créer une évaluation
function createEvaluation() {
    if (!currentClassId || !currentMatiereId) {
        alert('Veuillez d\'abord sélectionner une classe et une matière.');
        return;
    }

    // Pré-remplir les champs cachés
    document.getElementById('evalModal_classe_id').value  = currentClassId;
    document.getElementById('evalModal_matiere_id').value = currentMatiereId;

    // Afficher le contexte (classe + matière) dans le header
    const matiereName = currentMatiereName || 'Matière sélectionnée';
    const contextEl = document.getElementById('evalModal_context');
    if (contextEl) {
        contextEl.textContent = currentClassname
            ? `${currentClassname} — ${matiereName}`
            : matiereName;
    }

    // Réinitialiser le formulaire et masquer les erreurs précédentes
    const form = document.getElementById('evaluationCreateForm');
    if (form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        // Réinitialiser seulement les champs texte/select/textarea (garder les hidden)
        ['eval_titre','eval_type','eval_periode','eval_date',
         'eval_description','eval_enseignant_id','eval_enseignant_ext'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = (el.tagName === 'SELECT') ? '' : '';
        });
        document.getElementById('eval_heure_debut').value = '08:00';
        document.getElementById('eval_heure_fin').value   = '10:00';
        document.getElementById('eval_bareme').value      = '20';
        document.getElementById('eval_coefficient').value = '1';
        document.getElementById('eval_duree').value       = '';
        evalUpdateDuree();
    }
    const errorsEl = document.getElementById('evalModal_errors');
    if (errorsEl) { errorsEl.style.display = 'none'; errorsEl.innerHTML = ''; }

    // Afficher le modal
    const modalEl = document.getElementById('evaluationCreateModal');
    if (window.bootstrap && window.bootstrap.Modal) {
        new window.bootstrap.Modal(modalEl).show();
    } else {
        $(modalEl).modal('show');
    }
}

// Fonction pour sauvegarder une note
function saveNote(studentId, evaluationId, noteValue) {
    const isAbsent = $(`#absent-${studentId}-${evaluationId}`).is(':checked');

    // Si non absent et note vide, ne pas envoyer (attendre que l'utilisateur saisisse une note)
    if (!isAbsent && (noteValue === '' || noteValue === null || noteValue === undefined)) {
        return;
    }

    $.ajax({
        url: '{{ route("esbtp.notes.save-ajax") }}',
        method: 'POST',
        dataType: 'json',
        data: {
            _token: '{{ csrf_token() }}',
            etudiant_id: studentId,
            evaluation_id: evaluationId,
            note: isAbsent ? 0 : noteValue,
            is_absent: isAbsent ? 'on' : ''
        },
        success: function(response) {
            if (!response.success) {
                alert(response.message || 'Erreur lors de la sauvegarde de la note.');
                return;
            }
            // Animation de succès
            triggerRowHighlight(studentId);

            // Mettre à jour les données locales
            if (!notesData[studentId]) notesData[studentId] = {};
            notesData[studentId][evaluationId] = isAbsent ? 0 : noteValue;
            notesData[studentId][evaluationId + '_absent'] = isAbsent;

            // Recalculer les moyennes
            calculateStudentAverage(studentId);
            calculateClassAverages();
        },
        error: function(xhr) {
            console.error('Erreur lors de la sauvegarde:', xhr.responseJSON || xhr);
            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : (xhr.responseJSON && xhr.responseJSON.errors)
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : 'Erreur lors de la sauvegarde de la note.';
            alert(msg);
        }
    });
}

// Fonction pour basculer l'état d'absence
function toggleAbsence(studentId, evaluationId, isAbsent) {
    const input = $(`input[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);

    if (isAbsent) {
        // Cochage : désactiver l'input et sauvegarder l'absence
        input.val('0').prop('disabled', true);
        saveNote(studentId, evaluationId, 0);
    } else {
        // Décochage : réactiver l'input et le vider
        // NE PAS sauvegarder immédiatement : attendre que l'utilisateur saisisse une note
        input.val('').prop('disabled', false).attr('placeholder', 'Note...').focus();
    }
}

// Fonction pour calculer toutes les moyennes
function calculateAllAverages() {
    $('tr[data-student-id]').each(function() {
        const studentId = $(this).data('student-id');
        calculateStudentAverage(studentId);
    });
    calculateClassAverages();
}

// Fonction pour calculer la moyenne d'un étudiant
function calculateStudentAverage(studentId) {
    const row = $(`tr[data-student-id="${studentId}"]`);
    const noteInputs = row.find('.note-input');
    
    let totalPoints = 0;
    let totalCoefficients = 0;
    let hasNotes = false;
    
    noteInputs.each(function() {
        const evalId = $(this).data('eval-id');
        const noteValue = parseFloat($(this).val()) || 0;
        const isAbsent = $(`#absent-${studentId}-${evalId}`).is(':checked');
        const coeffInput = $(`.coeff-input[data-eval-id="${evalId}"]`);
        const coefficient = parseFloat(coeffInput.val()) || 1;
        const baremeInput = $(`.bareme-input[data-eval-id="${evalId}"]`);
        const bareme = parseFloat(baremeInput.val()) || 20;
        
        if (!isAbsent && !isNaN(noteValue) && noteValue > 0) {
            // Normaliser la note sur 20
            const normalizedNote = (noteValue / bareme) * 20;
            totalPoints += normalizedNote * coefficient;
            totalCoefficients += coefficient;
            hasNotes = true;
        }
    });
    
    const averageCell = row.find('.average-cell');
    const appreciationBadge = row.find('.appreciation-badge');
    
    if (hasNotes && totalCoefficients > 0) {
        const moyenne = totalPoints / totalCoefficients;
        averageCell.text(moyenne.toFixed(2));
        
        // Déterminer l'appréciation
        let appreciation = '';
        let badgeClass = '';
        
        if (moyenne >= 16) {
            appreciation = 'Excellent';
            badgeClass = 'bg-success';
        } else if (moyenne >= 14) {
            appreciation = 'Très bien';
            badgeClass = 'bg-info';
        } else if (moyenne >= 12) {
            appreciation = 'Bien';
            badgeClass = 'bg-primary';
        } else if (moyenne >= 10) {
            appreciation = 'Passable';
            badgeClass = 'bg-warning';
        } else {
            appreciation = 'Insuffisant';
            badgeClass = 'bg-danger';
        }
        
        appreciationBadge.text(appreciation).removeClass().addClass(`badge ${badgeClass}`);
    } else {
        averageCell.text('--');
        appreciationBadge.text('--').removeClass().addClass('badge bg-secondary');
    }
}

// Fonction pour calculer les moyennes de classe
function calculateClassAverages() {
    const evaluations = currentEvaluations.length ? currentEvaluations : Object.values(evaluationsData);
    
    evaluations.forEach(evaluation => {
        const evalId = evaluation.id;
        const noteInputs = $(`.note-input[data-eval-id="${evalId}"]`);
        
        let total = 0;
        let count = 0;
        
        noteInputs.each(function() {
            const studentId = $(this).data('student-id');
            const isAbsent = $(`#absent-${studentId}-${evalId}`).is(':checked');
            const noteValue = parseFloat($(this).val()) || 0;
            const baremeInput = $(`.bareme-input[data-eval-id="${evalId}"]`);
            const bareme = parseFloat(baremeInput.val()) || 20;
            
            if (!isAbsent && !isNaN(noteValue)) {
                // Normaliser sur 20
                const normalizedNote = (noteValue / bareme) * 20;
                total += normalizedNote;
                count++;
            }
        });
        
        const avgCell = $(`.class-avg-${evalId}`);
        if (count > 0) {
            avgCell.text((total / count).toFixed(2));
        } else {
            avgCell.text('--');
        }
    });
    
    // Calculer la moyenne générale de la classe
    const students = $('tr[data-student-id]');
    let classTotal = 0;
    let classCount = 0;
    
    students.each(function() {
        const avgText = $(this).find('.average-cell').text();
        if (avgText !== '--') {
            classTotal += parseFloat(avgText);
            classCount++;
        }
    });
    
    const overallAvgCell = $('.class-overall-avg');
    if (classCount > 0) {
        overallAvgCell.text((classTotal / classCount).toFixed(2));
    } else {
        overallAvgCell.text('--');
    }
}

// Fonction pour déclencher l'animation de surbrillance
function triggerRowHighlight(studentId) {
    const row = $(`tr[data-student-id="${studentId}"]`);
    row.addClass('highlight-success');
    
    setTimeout(function() {
        row.removeClass('highlight-success');
    }, 2000);
}

// Fonction pour enregistrer toutes les notes
$('#saveAllNotesBtn').on('click', function() {
    const btn = $(this);
    const originalText = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...').prop('disabled', true);
    
    // Simuler l'enregistrement de toutes les notes
    let savedCount = 0;
    const totalNotes = $('.note-input').length;
    
    $('.note-input').each(function() {
        const studentId = $(this).data('student-id');
        const evaluationId = $(this).data('eval-id');
        const noteValue = $(this).val();
        
        // Simuler l'enregistrement
        setTimeout(() => {
            savedCount++;
            if (savedCount === totalNotes) {
                btn.html('<i class="fas fa-check me-1"></i> Toutes les notes enregistrées').prop('disabled', false);
                setTimeout(() => {
                    btn.html(originalText);
                }, 2000);
            }
        }, 100);
    });
});

// ── Auto-calcul durée depuis heure_debut / heure_fin ──
function evalUpdateDuree() {
    const debut = document.getElementById('eval_heure_debut');
    const fin   = document.getElementById('eval_heure_fin');
    const duree = document.getElementById('eval_duree');
    const badge = document.getElementById('evalDureeBadge');
    if (!debut || !fin) return;
    const [dh, dm] = debut.value.split(':').map(Number);
    const [fh, fm] = fin.value.split(':').map(Number);
    if (isNaN(dh) || isNaN(fh)) return;
    let diff = (fh * 60 + fm) - (dh * 60 + dm);
    if (diff <= 0) diff += 24 * 60; // passage minuit
    if (duree && !duree.value) duree.value = diff;
    if (badge) badge.textContent = `${diff} min`;
}

document.addEventListener('DOMContentLoaded', function () {
    const debutEl = document.getElementById('eval_heure_debut');
    const finEl   = document.getElementById('eval_heure_fin');
    if (debutEl) debutEl.addEventListener('change', evalUpdateDuree);
    if (finEl)   finEl.addEventListener('change', evalUpdateDuree);
    evalUpdateDuree();

    // Bouton "Créer l'évaluation" dans le footer du modal
    const submitBtn = document.getElementById('evalModal_submit');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            const form = document.getElementById('evaluationCreateForm');
            if (form) form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        });
    }
});

// Gestion de la soumission du formulaire d'évaluation
$(document).on('submit', '#evaluationCreateForm', function (e) {
    e.preventDefault();

    const form     = $(this);
    const formData = new FormData(this);
    const spinner  = document.getElementById('evalModal_submitSpinner');
    const icon     = document.getElementById('evalModal_submitIcon');
    const btn      = document.getElementById('evalModal_submit');

    // État chargement
    if (spinner) spinner.classList.remove('d-none');
    if (icon)    icon.classList.add('d-none');
    if (btn)     btn.disabled = true;

    $.ajax({
        url:         form.attr('action'),
        method:      'POST',
        data:        formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response && response.success === false) {
                showEvaluationErrors(response.errors || {});
                evalResetSubmitBtn();
                return;
            }
            closeEvaluationModal();
            loadEvaluationsAndNotes();
            showSuccessMessage('Évaluation créée avec succès !');
        },
        error: function (xhr) {
            evalResetSubmitBtn();
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                showEvaluationErrors(xhr.responseJSON.errors);
                return;
            }
            console.error('Erreur lors de la création:', xhr);
            alert('Erreur lors de la création de l\'évaluation.');
        }
    });
});

function evalResetSubmitBtn() {
    const spinner = document.getElementById('evalModal_submitSpinner');
    const icon    = document.getElementById('evalModal_submitIcon');
    const btn     = document.getElementById('evalModal_submit');
    if (spinner) spinner.classList.add('d-none');
    if (icon)    icon.classList.remove('d-none');
    if (btn)     btn.disabled = false;
}

// Champs avec erreur → is-invalid Bootstrap
const evalFieldMap = {
    titre:           'eval_titre',
    type:            'eval_type',
    periode:         'eval_periode',
    date_evaluation: 'eval_date',
    heure_debut:     'eval_heure_debut',
    heure_fin:       'eval_heure_fin',
    bareme:          'eval_bareme',
    coefficient:     'eval_coefficient',
    classe_id:       null,
    matiere_id:      null,
};

function showEvaluationErrors(errors) {
    // Nettoyer les anciennes erreurs par champ
    Object.values(evalFieldMap).forEach(id => {
        if (!id) return;
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('is-invalid');
            const fb = el.parentElement.querySelector('.invalid-feedback');
            if (fb) fb.textContent = '';
        }
    });

    // Bannière globale
    const zone = document.getElementById('evalModal_errors');
    const errorList = Object.values(errors || {}).flat()
        .map(msg => `<li>${msg}</li>`).join('');
    zone.innerHTML = `
        <div class="alert alert-danger border-start border-danger border-4 mb-0">
            <div class="d-flex gap-3">
                <i class="fas fa-exclamation-circle fs-5 mt-1 flex-shrink-0"></i>
                <div>
                    <strong class="d-block mb-1">Veuillez corriger les erreurs suivantes :</strong>
                    <ul class="mb-0 ps-3">${errorList || '<li>Vérifiez les champs obligatoires.</li>'}</ul>
                </div>
            </div>
        </div>`;
    zone.style.display = 'block';

    // Marquer les champs concernés
    Object.entries(errors || {}).forEach(([field, messages]) => {
        const inputId = evalFieldMap[field];
        if (!inputId) return;
        const el = document.getElementById(inputId);
        if (!el) return;
        el.classList.add('is-invalid');
        const fb = el.parentElement.querySelector('.invalid-feedback');
        if (fb) fb.textContent = (messages || [])[0] || '';
    });

    // Scroll vers le haut du modal body
    const modalBody = document.querySelector('#evaluationCreateModal .modal-body');
    if (modalBody) modalBody.scrollTop = 0;
}

function closeEvaluationModal() {
    const modalEl = document.getElementById('evaluationCreateModal');
    if (!modalEl) return;

    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
        const inst = window.bootstrap.Modal.getInstance(modalEl);
        if (inst) inst.hide();
    } else {
        $('#evaluationCreateModal').modal('hide');
    }
    evalResetSubmitBtn();
}

// Fonction pour afficher un message de succès
function showSuccessMessage(message) {
    const alert = $(`
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `);
    
    $('.dashboard-header').after(alert);
    
    setTimeout(() => {
        alert.alert('close');
    }, 5000);
}

function showYearChangeInfo() {
    const modalEl = document.getElementById('yearChangeInfoModal');
    if (!modalEl) return;
    if (window.bootstrap && window.bootstrap.Modal) {
        new window.bootstrap.Modal(modalEl).show();
    } else {
        $(modalEl).modal('show');
    }
}
</script>
@endpush

@push('styles')
<style>
.border-active {
    border-left: 4px solid var(--success);
    cursor: pointer;
}

.border-inactive {
    border-left: 4px solid var(--neutral);
    cursor: pointer;
}

.classe-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-circle);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: var(--space-sm);
}

.bg-success {
    background: var(--success);
}

.bg-inactive {
    background: var(--neutral);
}

.classe-icon i {
    color: white;
    font-size: 16px;
}

.badge-notes {
    background: rgba(13, 110, 253, 0.12);
    color: #0d6efd;
    border: 1px solid rgba(13, 110, 253, 0.25);
    font-weight: 600;
}

.notes-hint {
    margin-top: 6px;
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
}
.hover-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.highlight-success {
    animation: highlightAnimation 2s ease-in-out;
    background-color: rgba(40, 167, 69, 0.1) !important;
}

@keyframes highlightAnimation {
    0% { background-color: rgba(40, 167, 69, 0.3); }
    70% { background-color: rgba(40, 167, 69, 0.1); }
    100% { background-color: transparent; }
}

.note-input {
    transition: border-color 0.2s, background-color 0.2s;
}

.note-input:focus {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.bareme-input, .coeff-input {
    font-size: 0.8rem;
    padding: 0.25rem 0.35rem;
}

.absence-checkbox:checked + label {
    color: #dc3545;
}

.class-card {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

#notesGrid th {
    vertical-align: middle;
    position: relative;
}

#notesGrid th .text-center {
    line-height: 1.2;
}

#notesGrid td {
    vertical-align: middle;
}

/* Override CSS globaux qui forcent max-width sur tous les .modal-dialog */
#classSelectionModal .modal-dialog {
    max-width: 92vw !important;
    width: 92vw !important;
    margin: 2vh auto !important;
}

#classSelectionModal .modal-content {
    max-height: 96vh !important;
    height: 96vh !important;
    display: flex !important;
    flex-direction: column !important;
    border-radius: 12px;
    overflow: hidden;
}

#classSelectionModal .modal-body {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    max-height: none !important;
    padding: 1.25rem 1.5rem;
}

#classSelectionModal .modal-header {
    flex-shrink: 0;
}

#classSelectionModal .modal-footer {
    flex-shrink: 0;
}

.notes-modal-content {
    border-radius: 12px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.15);
}

.notes-modal-intro {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 8px 12px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(14, 116, 144, 0.05));
    border-radius: 10px;
    border: 1px solid rgba(59, 130, 246, 0.15);
    margin-bottom: 12px;
    flex-shrink: 0;
}

.notes-modal-intro .intro-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: rgba(59, 130, 246, 0.12);
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.notes-modal-intro .intro-title {
    font-weight: 700;
    color: var(--text-primary);
}

.notes-modal-intro .intro-subtitle {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Filtres matière/période restent fixes en haut du modal */
#classSelectionModal .modal-body > .row {
    flex-shrink: 0;
}

/* Alerte "notes enregistrées" reste fixe en bas */
#classSelectionModal .modal-body > .mt-2 {
    flex-shrink: 0;
}

.notes-grid-table th,
.notes-grid-table td {
    padding: 8px 6px;
    border: 1px solid #e2e8f0;
}

.notes-grid-table {
    border-collapse: separate;
    border-spacing: 0;
    min-width: 100%;
}

.notes-grid-table thead th {
    position: sticky !important;
    top: 0 !important;
    z-index: 4;
    background: #f8fafc;
}

#notesGrid thead th.notes-average-col {
    position: sticky !important;
    top: 0;
    right: 110px;
    z-index: 6;
}

#notesGrid thead th.notes-appreciation-col {
    position: sticky !important;
    top: 0;
    right: 0;
    z-index: 7;
}

#notesGrid tbody td.notes-student-col,
#notesGrid tfoot td.notes-student-col {
    position: sticky;
    left: 0;
    z-index: 6;
    background: #ffffff;
}

/* Colonne étudiants — compact */
.notes-grid-table tbody .notes-student-col .d-flex {
    align-items: center;
    gap: 6px;
}
.notes-grid-table tbody .notes-student-col .me-2 {
    margin-right: 0 !important;
}
.notes-grid-table tbody .notes-student-col .fw-medium {
    font-size: 0.82rem;
    line-height: 1.3;
}
.notes-grid-table tbody .notes-student-col small {
    font-size: 0.7rem;
    opacity: 0.7;
}

.notes-grid-table tfoot td {
    position: sticky;
    bottom: 0;
    z-index: 3;
    background: #f8fafc;
    box-shadow: inset 0 1px 0 rgba(148, 163, 184, 0.4);
}


.notes-grid-table th:first-child,
.notes-grid-table td:first-child {
    position: sticky;
    left: 0;
    z-index: 5;
    background: #ffffff;
}

.notes-grid-table tfoot td:first-child {
    z-index: 6;
    background: #f8fafc;
}

.notes-average-col {
    position: sticky;
    right: 110px;
    z-index: 4;
    background: #f8fafc;
    min-width: 90px;
}

.notes-appreciation-col {
    position: sticky;
    right: 0;
    z-index: 5;
    background: #f8fafc;
    min-width: 110px;
}

.notes-grid-table tfoot .notes-average-col,
.notes-grid-table tfoot .notes-appreciation-col {
    z-index: 6;
}

.evaluation-header {
    background: #f8fafc;
    border-left: 3px solid rgba(59, 130, 246, 0.4);
    padding: 6px 5px !important;
}

.evaluation-title {
    font-weight: 700;
    color: var(--text-primary);
    text-align: center;
    margin-bottom: 3px;
    font-size: 0.82rem;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.evaluation-controls {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 2px;
}

.evaluation-control {
    display: flex;
    align-items: center;
    gap: 3px;
}

.evaluation-control .control-label {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    color: var(--text-muted);
    font-weight: 600;
}

.evaluation-control input {
    min-width: 46px;
    width: 46px;
    font-size: 0.78rem;
    padding: 0.15rem 0.25rem;
    text-align: center;
}

.evaluation-type {
    font-size: 0.7rem;
    text-align: center;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.2px;
}

.note-input {
    min-width: 56px;
    font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.3rem 0.2rem;
}

.notes-grid-wrapper {
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    flex: 1;
    min-height: 0;
    overflow-x: auto !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    position: relative;
}

/* Zebra striping */
.notes-grid-table tbody tr:nth-child(even) {
    background-color: #f8fafc;
}
.notes-grid-table tbody tr:nth-child(even) td.notes-student-col {
    background-color: #f8fafc;
}
.notes-grid-table tbody tr:nth-child(even) td.notes-average-col,
.notes-grid-table tbody tr:nth-child(even) td.notes-appreciation-col {
    background-color: #f0f4f8;
}
.notes-grid-table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.04) !important;
}
.notes-grid-table tbody tr:hover td.notes-student-col {
    background-color: rgba(59, 130, 246, 0.06) !important;
}

/* Scroll indicator bas */
.notes-grid-wrapper::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(to top, rgba(148, 163, 184, 0.18), transparent);
    pointer-events: none;
    border-radius: 0 0 12px 12px;
}

/* Badges appréciation — plus compacts et saturés */
.notes-appreciation-col .badge {
    font-size: 0.72rem;
    padding: 0.28em 0.5em;
    border-radius: 999px;
    font-weight: 600;
    white-space: nowrap;
}
.notes-appreciation-col .badge.bg-warning {
    color: #fff !important;
    background-color: #d97706 !important;
}
.notes-appreciation-col .badge.bg-info {
    color: #fff !important;
    background-color: #0891b2 !important;
}

/* Alerte compacte */
.notes-management-modal .alert-sm {
    font-size: 0.82rem;
    line-height: 1.4;
}

/* Cellules notes — centrées, compactes */
.notes-grid-table tbody td:not(.notes-student-col):not(.notes-average-col):not(.notes-appreciation-col) {
    padding: 6px 4px;
}

/* Checkbox absence — repositionné pour les petits inputs */
.notes-grid-table .position-relative .position-absolute {
    top: 2px !important;
    right: 2px !important;
}
.notes-grid-table .absence-checkbox {
    width: 13px !important;
    height: 13px !important;
}

/* Tfoot moyennes classe */
.notes-grid-table tfoot td {
    font-size: 0.82rem;
    font-weight: 700;
}

.notes-modal-footer {
    background: #f8fafc;
}

/* ── Modal Création Évaluation ────────────────────────── */
.eval-modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(4, 83, 203, 0.18);
}
.eval-modal-header {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    padding: 20px 24px;
    border-bottom: none;
    align-items: flex-start;
}
.eval-modal-context {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.72);
    font-weight: 500;
    letter-spacing: 0.2px;
}
.eval-autopublish-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ecfdf5;
    border-bottom: 1px solid #d1fae5;
    padding: 10px 24px;
    font-size: 0.84rem;
    color: #065f46;
}
.eval-autopublish-icon {
    color: #10b981;
    font-size: 1rem;
    flex-shrink: 0;
}
.eval-modal-body {
    padding: 0;
    background: #f8fafc;
}
.eval-modal-body form > div[id="evalModal_errors"] {
    margin: 16px 24px 0;
}
.eval-section {
    background: #fff;
    border-radius: 10px;
    margin: 16px 20px;
    border: 1px solid #e8edf5;
    overflow: hidden;
}
.eval-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #f1f5fb;
    border-bottom: 1px solid #e2e8f4;
}
.eval-section-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #0453cb;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.eval-section-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.eval-optional-badge {
    font-size: 0.72rem;
    font-weight: 500;
    color: #94a3b8;
    text-transform: none;
    letter-spacing: 0;
    margin-left: 4px;
}
.eval-section-body {
    padding: 16px;
}
.eval-errors-zone {
    margin: 16px 20px 0;
}
.eval-duree-badge {
    font-size: 0.78rem;
    color: #475569;
    background: #f1f5f9;
    border-color: #cbd5e1;
    white-space: nowrap;
}
.eval-modal-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 14px 20px;
    gap: 10px;
}
.eval-submit-btn {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: #fff;
    border: none;
    padding: 8px 22px;
    font-weight: 600;
    border-radius: 8px;
    transition: opacity 0.15s;
}
.eval-submit-btn:hover   { color: #fff; opacity: 0.9; }
.eval-submit-btn:disabled { opacity: 0.65; cursor: not-allowed; }
</style>
@endpush

