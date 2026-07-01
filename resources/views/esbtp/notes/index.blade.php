@extends('layouts.app')

@section('title', 'Gestion des Notes | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/notes-management.css') }}">
@endpush

@section('page_title', 'Gestion des Notes')

@section('content')
<div class="nm-page">

    {{-- ══ Hero ══ --}}
    <div class="nm-hero">
        <div class="nm-hero-top">
            <div>
                <div class="nm-hero-title">
                    <i class="fas fa-clipboard-check"></i>Gestion des Notes
                </div>
                <div class="nm-hero-subtitle">Saisie et gestion des notes par classe et matière</div>
            </div>
            <div class="nm-hero-actions">
                <a href="{{ route('esbtp.notes.index') }}" class="nm-hero-btn">
                    <i class="fas fa-sync-alt"></i>Actualiser
                </a>
            </div>
        </div>
        <div class="nm-hero-kpis">
            <div class="nm-hero-kpi">
                <div class="nm-hero-kpi-value">{{ count($classes) }}</div>
                <div class="nm-hero-kpi-label">Classes</div>
            </div>
            <div class="nm-hero-kpi">
                <div class="nm-hero-kpi-value">{{ $heroStats['total_configured'] }}/{{ $heroStats['total_matieres'] }}</div>
                <div class="nm-hero-kpi-label">Matières évaluées</div>
            </div>
            <div class="nm-hero-kpi">
                <div class="nm-hero-kpi-value">{{ $heroStats['avg_completion'] }}%</div>
                <div class="nm-hero-kpi-label">Complétude moy.</div>
            </div>
            <div class="nm-hero-kpi">
                <div class="nm-hero-kpi-value">{{ $heroStats['global_avg'] ? number_format($heroStats['global_avg'], 2) : '--' }}</div>
                <div class="nm-hero-kpi-label">Moyenne gén.</div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle', 'info' => 'info-circle'] as $type => $icon)
        @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endforeach

    {{-- ══ Context bar (year) ══ --}}
    <div class="nm-context-bar">
        <div class="nm-context-label"><i class="fas fa-calendar-alt me-1"></i>Année académique</div>
        <div class="nm-context-value">{{ $anneeAcademique }}</div>
        <small style="color: #94a3b8;">Les notes correspondent à l'année courante</small>
        <button type="button" class="nm-context-info-btn" onclick="showYearChangeInfo()">
            <i class="fas fa-info-circle me-1"></i>Changer
        </button>
    </div>

    {{-- ══ Filter bar ══ --}}
    <div class="nm-filter-bar">
        <div class="nm-filter-title"><i class="fas fa-filter"></i>Filtres de recherche</div>
        <form method="GET" action="{{ route('esbtp.notes.index') }}" id="filtersForm">
            <div class="nm-filter-grid">
                <div class="nm-filter-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom ou code de classe...">
                </div>
                <div class="nm-filter-group">
                    <label for="filiere_id">Filière</label>
                    <select name="filiere_id" id="filiere_id">
                        <option value="">Toutes les filières</option>
                        @foreach($filieres as $filiere)
                            <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                {{ $filiere->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="nm-filter-group">
                    <label for="niveau_id">Niveau</label>
                    <select name="niveau_id" id="niveau_id">
                        <option value="">Tous les niveaux</option>
                        @foreach($niveaux as $niveau)
                            <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                {{ $niveau->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="nm-filter-group">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut">
                        <option value="">Tous</option>
                        <option value="active" {{ request('statut') == 'active' ? 'selected' : '' }}>Actives</option>
                        <option value="inactive" {{ request('statut') == 'inactive' ? 'selected' : '' }}>Inactives</option>
                    </select>
                </div>
                <div class="nm-filter-group">
                    <label for="capacite">Capacité</label>
                    <select name="capacite" id="capacite">
                        <option value="">Toutes</option>
                        <option value="disponible" {{ request('capacite') == 'disponible' ? 'selected' : '' }}>Disponibles</option>
                        <option value="pleine" {{ request('capacite') == 'pleine' ? 'selected' : '' }}>Pleines</option>
                    </select>
                </div>
            </div>
            <div class="nm-filter-actions">
                <button type="submit" class="nm-filter-btn primary">
                    <i class="fas fa-search"></i>Filtrer
                </button>
                <button type="button" id="reset-filters-btn" class="nm-filter-btn secondary">
                    <i class="fas fa-times"></i>Réinitialiser
                </button>
                <div class="nm-filter-count">
                    <i class="fas fa-list me-1"></i><span id="classes-count">{{ count($classes) }}</span> classe(s)
                </div>
            </div>
        </form>
    </div>

    {{-- ══ Classes grid ══ --}}
    <div class="nm-section">
        <div class="nm-section-header">
            <div class="nm-section-title">
                <i class="fas fa-th-large"></i>Classes disponibles
            </div>
            <span class="nm-section-badge">{{ count($classes) }} classes</span>
        </div>
        <div id="classes-results">
            <div class="nm-cards-grid" id="classes-grid">
                @include('esbtp.notes.partials.classes-items', ['classes' => $classes, 'classStatsById' => $classStatsById])
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: Sélection Classe & Saisie Notes
     ══════════════════════════════════════════════════════ --}}
<div class="modal fade nm-notes-modal" id="classSelectionModal" tabindex="-1" aria-labelledby="classSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header nm-modal-header">
                <div class="nm-header-left">
                    <h5 class="modal-title" id="classSelectionModalLabel">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Gestion des Notes — <span id="selectedClassLabel">Sélectionnez une classe</span>
                    </h5>
                </div>
                <div class="nm-header-right">
                    {{-- Network badge (3 états : synced / syncing / offline) --}}
                    <div id="nm-network-badge" class="nm-network-badge" data-state="synced"
                         title="État de la synchronisation des notes">
                        <span class="nm-network-dot"></span>
                        <span class="nm-network-label">Synchronisé</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
            </div>
            <div class="modal-body">
                {{-- Restore draft banner (visible only if localStorage draft detected at modal open) --}}
                <div id="nm-restore-banner" class="nm-restore-banner" style="display:none;" role="alert">
                    <div class="nm-restore-banner-text">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Brouillon non sauvegardé détecté</strong>
                        <span class="text-muted ms-1">— sauvegardé localement il y a <span id="nm-restore-time">—</span></span>
                        <span class="ms-1">·</span>
                        <strong id="nm-restore-count">0</strong> note(s) en attente de synchronisation
                    </div>
                    <div class="nm-restore-banner-actions">
                        <button type="button" class="btn btn-warning" id="nm-restore-btn">
                            <i class="fas fa-undo me-1"></i>Restaurer
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="nm-restore-discard">
                            <i class="fas fa-trash me-1"></i>Ignorer
                        </button>
                    </div>
                </div>

                {{-- Intro callout --}}
                <div class="nm-modal-intro">
                    <div class="nm-modal-intro-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="nm-modal-intro-title">Saisie intelligente des notes</div>
                        <div class="nm-modal-intro-sub">Choisissez une matière, créez des évaluations et saisissez les notes en temps réel.</div>
                    </div>
                </div>

                {{-- Controls row --}}
                <div class="nm-modal-controls">
                    <div class="nm-modal-control" style="flex: 2;">
                        <label for="matiereSelect">Matière</label>
                        <select class="form-select" id="matiereSelect">
                            <option value="">-- Sélectionner une matière --</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}">{{ $matiere->name ?? $matiere->nom ?? 'Matière sans nom' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="nm-modal-control">
                        <label for="periodeFilter">Période</label>
                        <select class="form-select" id="periodeFilter">
                            <option value="all">Toutes</option>
                            <option value="semestre1">Semestre 1</option>
                            <option value="semestre2">Semestre 2</option>
                        </select>
                    </div>
                    @can('evaluations.create')
                    <div style="flex-shrink: 0;">
                        <label>&nbsp;</label>
                        <button type="button" class="nm-create-eval-btn" onclick="createEvaluation()" id="createEvaluationBtn">
                            <i class="fas fa-plus"></i>Créer évaluation
                        </button>
                    </div>
                    @endcan
                </div>

                {{-- Table toolbar : search + stats (visible when grid is loaded) --}}
                <div class="nm-table-toolbar" id="nm-table-toolbar" style="display:none;">
                    <div class="nm-search-wrapper">
                        <i class="fas fa-search nm-search-icon"></i>
                        <input type="text" id="nm-student-search"
                               class="form-control form-control-sm"
                               placeholder="Rechercher un étudiant (Ctrl+F)…"
                               autocomplete="off">
                        <button type="button" class="nm-search-clear" id="nm-search-clear"
                                style="display:none;" title="Effacer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="nm-table-stats">
                        <span class="nm-stat-pill"><strong id="nm-students-count">0</strong> étudiant(s)</span>
                        <span class="nm-stat-pill"><strong id="nm-evaluations-count">0</strong> évaluation(s)</span>
                        <span class="nm-stat-pill" id="nm-students-visible-pill" style="display:none;"><strong id="nm-students-visible">0</strong> visible(s)</span>
                    </div>
                </div>

                {{-- Notes grid --}}
                <div class="nm-grid-wrapper table-responsive notes-grid-wrapper">
                    <button type="button" class="nm-scroll-arrow" id="nm-scroll-arrow"
                            title="Défiler vers la droite" aria-label="Défiler horizontalement">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <table class="nm-grid-table table notes-grid-table" id="notesGrid">
                        <thead>
                            <tr>
                                <th class="notes-student-col">Étudiants</th>
                                <th class="notes-average-col">Moyenne</th>
                                <th class="notes-appreciation-col">Appréciation</th>
                            </tr>
                        </thead>
                        <tbody id="studentsRows">
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="fas fa-info-circle fa-2x mb-3 d-block" style="color: #94a3b8;"></i>
                                    Sélectionnez d'abord une classe et une matière pour afficher les notes
                                </td>
                            </tr>
                        </tbody>
                        <tfoot id="classAveragesRow" style="display: none;"></tfoot>
                    </table>
                </div>

                {{-- Load more (pagination > 80 students) --}}
                <div class="nm-load-more-wrap" id="nm-load-more-wrap" style="display:none;">
                    <button type="button" class="nm-load-more-btn" id="nm-load-more-btn">
                        <i class="fas fa-chevron-down"></i>
                        Charger <span id="nm-load-more-count">30</span> étudiants de plus
                    </button>
                </div>

                {{-- Auto-save info --}}
                <div class="nm-autosave-info">
                    <i class="fas fa-info-circle"></i>
                    Les notes sont automatiquement enregistrées à chaque modification —
                    raccourcis&nbsp;: <kbd>Tab</kbd>/<kbd>Shift+Tab</kbd>, <kbd>Enter</kbd>/<kbd>Shift+Enter</kbd>, <kbd>Ctrl+S</kbd>, <kbd>Esc</kbd>.
                </div>
            </div>
            <div class="modal-footer nm-modal-footer">
                {{-- Badge bulletin synchronisé : visible après chaque saveNote() success --}}
                <span id="nm-sync-badge" class="badge bg-light text-success border me-auto" style="display:none; font-weight: 500; padding: .45rem .65rem;" title="Le bulletin sera mis à jour automatiquement avec cette note">
                    <i class="fas fa-check-circle me-1"></i>Bulletin synchronisé · <span id="nm-sync-time">à l'instant</span>
                </span>
                <a href="#" class="btn btn-outline-secondary disabled" id="previewBlankPdfBtn" target="_blank" rel="noopener" aria-disabled="true" tabindex="-1" title="Aperçu PDF dans un nouvel onglet">
                    <i class="fas fa-eye me-1"></i>Aperçu PDF
                </a>
                <a href="#" class="btn btn-outline-primary disabled" id="exportBlankPdfBtn" target="_blank" rel="noopener" aria-disabled="true" tabindex="-1">
                    <i class="fas fa-file-pdf me-1"></i>Feuille vierge (PDF)
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
                <button type="button" class="btn btn-success" id="saveAllNotesBtn" style="display: none;">
                    <i class="fas fa-save me-1"></i>Enregistrer tout
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: Création évaluation
     ══════════════════════════════════════════════════════ --}}
<div class="modal fade nm-eval-modal" id="evaluationCreateModal" tabindex="-1"
     aria-labelledby="evaluationCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header nm-eval-modal-header">
                <div>
                    <h5 class="modal-title text-white mb-1" id="evaluationCreateModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Créer une évaluation
                    </h5>
                    <p id="evalModal_context" class="nm-eval-context mb-0"></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="nm-autopublish">
                <i class="fas fa-check-circle"></i>
                <span><strong>Publication automatique</strong> — L'évaluation sera publiée immédiatement.</span>
            </div>

            <div class="modal-body">
                <form id="evaluationCreateForm" action="{{ route('esbtp.evaluations.store') }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="embed" value="1">
                    <input type="hidden" name="is_published" value="1">
                    <input type="hidden" id="evalModal_classe_id" name="classe_id">
                    <input type="hidden" id="evalModal_matiere_id" name="matiere_id">

                    <div id="evalModal_errors" class="nm-eval-errors" style="display:none;"></div>

                    {{-- Section 1: Informations générales --}}
                    <div class="nm-eval-section">
                        <div class="nm-eval-section-header">
                            <span class="nm-eval-section-num">1</span>
                            <span class="nm-eval-section-label">Informations générales</span>
                        </div>
                        <div class="nm-eval-section-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="eval_titre">
                                    Titre <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="eval_titre" name="titre" class="form-control"
                                       placeholder="Ex : Devoir de mathématiques n°2" maxlength="255" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_type">Type <span class="text-danger">*</span></label>
                                    <select id="eval_type" name="type" class="form-select" required>
                                        <option value="">— Choisir —</option>
                                        @foreach($evaluationTypes as $typeKey => $typeLabel)
                                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_periode">Période <span class="text-danger">*</span></label>
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

                    {{-- Section 2: Date & Horaires --}}
                    <div class="nm-eval-section">
                        <div class="nm-eval-section-header">
                            <span class="nm-eval-section-num">2</span>
                            <span class="nm-eval-section-label">Date &amp; Horaires</span>
                        </div>
                        <div class="nm-eval-section-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="eval_date">Date d'évaluation <span class="text-danger">*</span></label>
                                <input type="date" id="eval_date" name="date_evaluation" class="form-control" max="{{ date('Y-m-d') }}" required>
                                <div class="form-text"><i class="fas fa-calendar-check me-1 text-muted"></i>Seules les dates passées ou d'aujourd'hui sont acceptées.</div>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="row g-3 align-items-end">
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold" for="eval_heure_debut">Début <span class="text-danger">*</span></label>
                                    <input type="time" id="eval_heure_debut" name="heure_debut" class="form-control" value="08:00" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold" for="eval_heure_fin">Fin <span class="text-danger">*</span></label>
                                    <input type="time" id="eval_heure_fin" name="heure_fin" class="form-control" value="10:00" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold" for="eval_duree">Durée <span class="text-muted fw-normal">(min)</span></label>
                                    <div class="input-group">
                                        <input type="number" id="eval_duree" name="duree_minutes" class="form-control" min="0" max="720" placeholder="Auto">
                                        <span class="input-group-text nm-eval-duree-badge" id="evalDureeBadge">120 min</span>
                                    </div>
                                    <div class="form-text">Calculée automatiquement</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Barème & Coefficient --}}
                    <div class="nm-eval-section">
                        <div class="nm-eval-section-header">
                            <span class="nm-eval-section-num">3</span>
                            <span class="nm-eval-section-label">Barème &amp; Coefficient</span>
                        </div>
                        <div class="nm-eval-section-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_bareme">Barème <span class="text-danger">*</span></label>
                                    <input type="number" id="eval_bareme" name="bareme" class="form-control" value="20" min="1" step="0.5" required>
                                    <div class="form-text">Note maximale (ex : 20)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_coefficient">Coefficient <span class="text-danger">*</span></label>
                                    <input type="number" id="eval_coefficient" name="coefficient" class="form-control" value="1" min="0.1" max="10" step="0.1" required>
                                    <div class="form-text">Poids dans la moyenne (0,1 – 10)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Description --}}
                    <div class="nm-eval-section">
                        <div class="nm-eval-section-header">
                            <span class="nm-eval-section-num">4</span>
                            <span class="nm-eval-section-label">Description <span class="nm-eval-optional">optionnelle</span></span>
                        </div>
                        <div class="nm-eval-section-body">
                            <textarea id="eval_description" name="description" class="form-control" rows="3"
                                      placeholder="Chapitres couverts, instructions pour les étudiants…"></textarea>
                        </div>
                    </div>

                    {{-- Section 5: Enseignant (non-enseignants seulement) --}}
                    @if(auth()->check() && !auth()->user()->can('identity.teach'))
                    <div class="nm-eval-section">
                        <div class="nm-eval-section-header">
                            <span class="nm-eval-section-num">5</span>
                            <span class="nm-eval-section-label">Enseignant <span class="nm-eval-optional">optionnel</span></span>
                        </div>
                        <div class="nm-eval-section-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_enseignant_id">Enseignant plateforme</label>
                                    <select id="eval_enseignant_id" name="enseignant_id" class="form-select">
                                        <option value="">— Sélectionner —</option>
                                        @foreach($enseignants as $enseignant)
                                            <option value="{{ $enseignant->id }}">{{ $enseignant->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Peut se connecter et saisir les notes</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" for="eval_enseignant_ext">Enseignant externe</label>
                                    <input type="text" id="eval_enseignant_ext" name="enseignant_externe_nom" class="form-control" placeholder="Nom complet">
                                    <div class="form-text">Sans compte sur la plateforme</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border fw-semibold" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" id="evalModal_save_continue" class="btn btn-outline-primary fw-semibold"
                        title="Créer cette évaluation puis garder le formulaire ouvert pour la suivante">
                    <i class="fas fa-plus me-1"></i>Créer et continuer
                </button>
                <button type="button" id="evalModal_submit" class="btn nm-eval-submit-btn">
                    <span id="evalModal_submitSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                    <i class="fas fa-save me-2" id="evalModal_submitIcon"></i>Créer l'évaluation
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: Édition rapide évaluation (titre + barème + coef)
     ══════════════════════════════════════════════════════ --}}
<div class="modal fade nm-eval-quick-modal" id="evaluationQuickEditModal" tabindex="-1"
     aria-labelledby="evaluationQuickEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evaluationQuickEditModalLabel">
                    <i class="fas fa-pen"></i>Modifier l'évaluation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="nm-eval-quick-form" novalidate>
                    <input type="hidden" id="nm-eval-quick-id" value="">
                    <div class="mb-3">
                        <label class="form-label" for="nm-eval-quick-titre">Titre</label>
                        <input type="text" id="nm-eval-quick-titre" class="form-control" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="nm-eval-quick-bareme">Barème</label>
                            <input type="number" id="nm-eval-quick-bareme" class="form-control"
                                   min="1" max="100" step="0.5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="nm-eval-quick-coefficient">Coefficient</label>
                            <input type="number" id="nm-eval-quick-coefficient" class="form-control"
                                   min="0.1" max="10" step="0.1" required>
                        </div>
                    </div>
                    <div id="nm-eval-quick-error" class="alert alert-danger mt-3 mb-0"
                         style="display:none; font-size:.82rem;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary fw-semibold" id="nm-eval-quick-save">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="nm-eval-quick-spinner"></span>
                    <i class="fas fa-save me-1" id="nm-eval-quick-icon"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: Info changement d'année
     ══════════════════════════════════════════════════════ --}}
<div class="modal fade nm-year-modal" id="yearChangeInfoModal" tabindex="-1" aria-labelledby="yearChangeInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeInfoModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Changer d'année académique
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3" style="font-size: 0.9rem;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Pourquoi l'année est-elle verrouillée ?</strong><br>
                    La page affiche uniquement les évaluations de l'année académique courante pour éviter toute confusion.
                </div>
                <p style="font-size: 0.9rem;">Pour changer d'année, rendez-vous dans la gestion des années universitaires et cliquez sur <strong>"Définir comme courante"</strong>.</p>
                <div class="nm-year-current">
                    <i class="fas fa-cog" style="color: #0453cb;"></i>
                    <div>
                        <div class="nm-year-current-label">Année courante</div>
                        <div class="nm-year-current-value">{{ $anneeAcademique }}</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
                @if(auth()->user()->can('admin.access'))
                <a href="{{ route('esbtp.annees-universitaires.index') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i>Gérer les années
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

{{-- ══════════════════════════════════════════════════════
     JAVASCRIPT — Preserved identically
     ══════════════════════════════════════════════════════ --}}
@push('scripts')
{{-- common.js fournit window.iiConfirm + window.showToast (utilisés pour la confirmation de fermeture). --}}
<script src="{{ asset('js/inscriptions/common.js') }}" defer></script>
<script>
// Variables globales
let currentClassId = null;
let currentClassname = '';
let currentMatiereId = null;
let currentMatiereName = '';
let currentPeriodeFilter = 'all';
let evaluationsData = {};
let notesData = {};
let currentEvaluations = [];
let cachedStudents = null;
let cachedStudentsClassId = null;
let currentLoadRequest = null;
let evalParamsCache = {};
const blankPdfUrlTemplate = '{{ route("esbtp.notes.saisie-rapide-blank.pdf", ["classe" => ":classId"]) }}';
const blankPdfPreviewUrlTemplate = '{{ route("esbtp.notes.saisie-rapide-blank.pdf-preview", ["classe" => ":classId"]) }}';

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
    $(document).on('click', '.class-card, .nm-class-card', function(e) {
        if ($(e.target).closest('.class-select-btn, .nm-card-action').length) {
            return;
        }

        const classId = $(this).attr('data-classe-id');
        const classLabel = $(this).attr('data-class-label');
        selectClass(classId, classLabel);
    });

    $(document).on('click', '.class-select-btn, .nm-card-action', function(e) {
        e.stopPropagation();
        const card = $(this).closest('.class-card, .nm-class-card');
        const classId = card.attr('data-classe-id');
        const classLabel = card.attr('data-class-label');
        selectClass(classId, classLabel);
    });

    // Invalidate eval params cache when user changes bareme/coeff
    $(document).on('change', '.bareme-input, .coeff-input', function() {
        const evalId = $(this).data('eval-id');
        if (evalId) {
            evalParamsCache[evalId] = {
                bareme: parseFloat($(`.bareme-input[data-eval-id="${evalId}"]`).val()) || 20,
                coefficient: parseFloat($(`.coeff-input[data-eval-id="${evalId}"]`).val()) || 1
            };
        }
        calculateAllAverages();
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

    // Scroll shadow detection on grid wrapper (scroll doesn't bubble — attach directly)
    $('.nm-grid-wrapper').on('scroll', function() {
        $(this).toggleClass('scrolled-x', this.scrollLeft > 0);
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
    if (currentClassId !== classId) {
        cachedStudents = null;
        cachedStudentsClassId = null;
    }
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
                <i class="fas fa-info-circle fa-2x mb-3 d-block" style="color: #94a3b8;"></i>
                Sélectionnez une matière pour afficher les notes
            </td>
        </tr>
    `);

    // Montrer le modal
    $('#classSelectionModal').modal('show');
}

function updateBlankPdfLink() {
    const downloadBtn = document.getElementById('exportBlankPdfBtn');
    const previewBtn = document.getElementById('previewBlankPdfBtn');

    [
        { btn: downloadBtn, template: blankPdfUrlTemplate },
        { btn: previewBtn, template: blankPdfPreviewUrlTemplate },
    ].forEach(({ btn, template }) => {
        if (!btn) return;

        if (!currentClassId) {
            btn.setAttribute('href', '#');
            btn.classList.add('disabled');
            btn.setAttribute('aria-disabled', 'true');
            btn.setAttribute('tabindex', '-1');
            return;
        }

        btn.setAttribute('href', template.replace(':classId', currentClassId));
        btn.classList.remove('disabled');
        btn.removeAttribute('aria-disabled');
        btn.setAttribute('tabindex', '0');
    });
}

// Fonction pour charger les évaluations et notes
function loadEvaluationsAndNotes() {
    if (!currentClassId || !currentMatiereId) return;

    // Abort any in-flight request to prevent stale data
    if (currentLoadRequest && currentLoadRequest.readyState !== 4) {
        currentLoadRequest.abort();
    }

    $('#studentsRows').html(`
        <tr>
            <td colspan="10" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3" style="color: #64748b;">Chargement des données...</p>
            </td>
        </tr>
    `);

    currentLoadRequest = $.ajax({
        url: '{{ route("esbtp.notes.evaluations.by-class-matiere", ["classId" => ":classId", "matiereId" => ":matiereId"]) }}'
            .replace(':classId', currentClassId)
            .replace(':matiereId', currentMatiereId),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            evaluationsData = response.evaluations || {};
            notesData = response.notes || {};
            buildNotesGrid();
        },
        error: function(xhr) {
            if (xhr.statusText === 'abort') return;
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
    if (!periode) return 'semestre1';
    if (periode === '1' || periode === 1 || periode === 'semestre1') return 'semestre1';
    if (periode === '2' || periode === 2 || periode === 'semestre2') return 'semestre2';
    return periode;
}

// Fonction pour construire la grille des notes
function buildNotesGrid() {
    const evaluations = Object.values(evaluationsData);

    // Cache normalizePeriode results per evaluation
    evaluations.forEach(e => { e._period = normalizePeriode(e.periode); });

    const filteredEvaluations = currentPeriodeFilter === 'all'
        ? evaluations
        : evaluations.filter(e => e._period === currentPeriodeFilter);

    const sortedEvaluations = [...filteredEvaluations].sort((a, b) => {
        const periodA = a._period === 'semestre2' ? 2 : 1;
        const periodB = b._period === 'semestre2' ? 2 : 1;
        if (periodA !== periodB) return periodA - periodB;
        const dateA = a.date_evaluation ? new Date(a.date_evaluation) : null;
        const dateB = b.date_evaluation ? new Date(b.date_evaluation) : null;
        if (dateA && dateB) return dateA - dateB;
        return 0;
    });

    currentEvaluations = sortedEvaluations;

    // Cache bareme/coeff for average calculations
    evalParamsCache = {};
    sortedEvaluations.forEach(e => {
        evalParamsCache[e.id] = {
            bareme: parseFloat(e.bareme) || 20,
            coefficient: parseFloat(e.coefficient) || 1
        };
    });

    // Use cached students if same class, otherwise fetch
    if (cachedStudents && cachedStudentsClassId === currentClassId) {
        renderNotesGrid(cachedStudents, sortedEvaluations);
        return;
    }

    $.ajax({
        url: '{{ route("esbtp.notes.classes.students", ["classe" => ":classId"]) }}'.replace(':classId', currentClassId),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!response.success) {
                console.error('Erreur API:', response.message);
                return;
            }

            cachedStudents = (response.students || []).sort((a, b) => {
                const nameA = ((a.nom || '') + ' ' + (a.prenoms || '')).toLowerCase();
                const nameB = ((b.nom || '') + ' ' + (b.prenoms || '')).toLowerCase();
                return nameA.localeCompare(nameB, 'fr');
            });
            cachedStudentsClassId = currentClassId;
            renderNotesGrid(cachedStudents, sortedEvaluations);
        },
        error: function(xhr) {
            console.error('Erreur lors du chargement des étudiants:', xhr);
        }
    });
}

function renderNotesGrid(students, sortedEvaluations) {
    // Construire l'en-tête
    const thead = $('#notesGrid thead');
    thead.empty();

    const periodCounts = {
        semestre1: sortedEvaluations.filter(e => e._period === 'semestre1').length,
        semestre2: sortedEvaluations.filter(e => e._period === 'semestre2').length,
    };

    const periodRow = $('<tr class="nm-period-row"></tr>');
    periodRow.append('<th class="notes-student-col">Étudiants</th>');
    if (periodCounts.semestre1 > 0) {
        periodRow.append(`<th colspan="${periodCounts.semestre1}" class="nm-period-s1 text-center">Semestre 1</th>`);
    }
    if (periodCounts.semestre2 > 0) {
        periodRow.append(`<th colspan="${periodCounts.semestre2}" class="nm-period-s2 text-center">Semestre 2</th>`);
    }
    periodRow.append('<th colspan="2" class="nm-period-summary text-center">Synthèse</th>');
    thead.append(periodRow);

    const evalRow = $('<tr class="nm-eval-row"></tr>');
    evalRow.append('<th class="notes-student-col">Étudiants</th>');

    sortedEvaluations.forEach(evaluation => {
        const periodLabel = evaluation._period === 'semestre2' ? 'S2' : 'S1';
        const header = `
            <th id="evalHeader${evaluation.id}" class="nm-eval-header evaluation-header">
                <div class="nm-eval-title evaluation-title">${evaluation.titre || 'Éval'}</div>
                <div class="nm-eval-controls evaluation-controls">
                    <div class="nm-eval-control evaluation-control">
                        <span class="nm-eval-control-label control-label">Bar</span>
                        <input type="number" value="${evaluation.bareme || 20}"
                               class="form-control form-control-sm bareme-input"
                               data-eval-id="${evaluation.id}" title="Barème">
                    </div>
                    <div class="nm-eval-control evaluation-control">
                        <span class="nm-eval-control-label control-label">Coef</span>
                        <input type="number" value="${evaluation.coefficient || 1}"
                               class="form-control form-control-sm coeff-input"
                               data-eval-id="${evaluation.id}" title="Coefficient">
                    </div>
                </div>
                <div class="nm-eval-type evaluation-type">${evaluation.type || 'Devoir'}</div>
                <div class="nm-eval-period evaluation-period ${evaluation._period}">${periodLabel}</div>
            </th>
        `;
        evalRow.append(header);
    });

    evalRow.append('<th class="notes-average-col">Moyenne</th>');
    evalRow.append('<th class="notes-appreciation-col">Appréciation</th>');

    thead.append(evalRow);

    // Construire les lignes des étudiants
    const tbody = $('#studentsRows');
    tbody.empty();

    students.forEach(student => {
        const initials = ((student.nom || '')[0] || '') + ((student.prenoms || '')[0] || '');
        const row = $(`
            <tr data-student-id="${student.id}">
                <td class="fw-medium notes-student-col">
                    <div class="nm-student-name">
                        <div class="nm-student-avatar">${initials.toUpperCase()}</div>
                        <div class="nm-student-info">
                            <div class="nm-student-fullname" title="${student.nom} ${student.prenoms}">${student.nom} ${student.prenoms}</div>
                            <div class="nm-student-matricule">${student.matricule || ''}</div>
                        </div>
                    </div>
                </td>
            </tr>
        `);

        sortedEvaluations.forEach(evaluation => {
            const note = notesData[student.id]?.[evaluation.id] || '';
            const isAbsent = notesData[student.id]?.[evaluation.id + '_absent'] || false;

            const noteCell = `
                <td class="text-center nm-note-cell">
                    <div class="nm-note-wrap d-flex align-items-center gap-1">
                        <input type="number"
                               class="form-control nm-note-input note-input"
                               value="${note}"
                               data-student-id="${student.id}"
                               data-eval-id="${evaluation.id}"
                               step="0.01" inputmode="decimal" lang="fr" min="0" max="${evaluation.bareme || 20}"
                               ${isAbsent ? 'disabled' : ''}
                               onchange="saveNote(${student.id}, ${evaluation.id}, this.value)">
                        <div class="nm-absence-check">
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

        row.append('<td class="text-center fw-bold average-cell notes-average-col">--</td>');
        row.append('<td class="text-center notes-appreciation-col"><span class="nm-appreciation default">--</span></td>');

        tbody.append(row);
    });

    buildClassAveragesRow(sortedEvaluations);
    calculateAllAverages();
    $('#saveAllNotesBtn').show();

    // Measure actual period row height for sticky offset
    const periodRowEl = document.querySelector('.nm-period-row th');
    if (periodRowEl) {
        const h = periodRowEl.offsetHeight;
        document.querySelector('.nm-grid-wrapper').style.setProperty('--nm-period-row-h', h + 'px');
    }

    // PR #3+#4 — Hook : signaler aux upgrades visuels (toolbar, abs toggle,
    // eval header lisible, pagination, autofocus, draft banner) que la grille
    // est prête. Le listener est défini en bas du script.
    window.dispatchEvent(new CustomEvent('nm:grid-rendered', {
        detail: { students: students, evaluations: sortedEvaluations },
    }));
}

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

function createEvaluation() {
    if (!currentClassId || !currentMatiereId) {
        alert('Veuillez d\'abord sélectionner une classe et une matière.');
        return;
    }

    document.getElementById('evalModal_classe_id').value  = currentClassId;
    document.getElementById('evalModal_matiere_id').value = currentMatiereId;

    const matiereName = currentMatiereName || 'Matière sélectionnée';
    const contextEl = document.getElementById('evalModal_context');
    if (contextEl) {
        contextEl.textContent = currentClassname
            ? `${currentClassname} — ${matiereName}`
            : matiereName;
    }

    const form = document.getElementById('evaluationCreateForm');
    if (form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
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

    const modalEl = document.getElementById('evaluationCreateModal');
    if (window.bootstrap && window.bootstrap.Modal) {
        new window.bootstrap.Modal(modalEl).show();
    } else {
        $(modalEl).modal('show');
    }
}

function saveNote(studentId, evaluationId, noteValue) {
    const isAbsent = $(`#absent-${studentId}-${evaluationId}`).is(':checked');

    if (!isAbsent && (noteValue === '' || noteValue === null || noteValue === undefined)) {
        return;
    }

    $.ajax({
        url: '{{ route("esbtp.notes.save-ajax") }}',
        method: 'POST',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
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
            // 1. Mettre à jour le state JS et recalculer la moyenne AVANT
            //    de dispatcher nm:note-saved : sinon le toast lit l'ancienne
            //    valeur de .average-cell (la lecture est synchrone à l'event).
            if (!notesData[studentId]) notesData[studentId] = {};
            notesData[studentId][evaluationId] = isAbsent ? 0 : noteValue;
            notesData[studentId][evaluationId + '_absent'] = isAbsent;

            calculateStudentAverage(studentId);
            calculateClassAverages();

            // 2. Une fois le DOM cohérent, déclencher le highlight + toast
            //    + badge bulletin synchronisé.
            triggerRowHighlight(studentId);
            markBulletinSynced();
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

/**
 * Badge "Bulletin synchronisé" — affiché dans le footer du modal après chaque
 * sauvegarde de note réussie. Le timestamp se rafraîchit toutes les 30s pour
 * afficher "il y a Xs / Xmin" en français.
 */
let nmLastSyncAt = null;
let nmSyncInterval = null;

function markBulletinSynced() {
    nmLastSyncAt = new Date();
    const badge = document.getElementById('nm-sync-badge');
    if (!badge) return;

    badge.style.display = 'inline-block';
    updateSyncTimeLabel();

    if (!nmSyncInterval) {
        nmSyncInterval = setInterval(updateSyncTimeLabel, 30000);
    }
}

function updateSyncTimeLabel() {
    const label = document.getElementById('nm-sync-time');
    if (!label || !nmLastSyncAt) return;

    const diffSec = Math.max(0, Math.floor((Date.now() - nmLastSyncAt.getTime()) / 1000));

    let txt;
    if (diffSec < 5) {
        txt = "à l'instant";
    } else if (diffSec < 60) {
        txt = `il y a ${diffSec}s`;
    } else if (diffSec < 3600) {
        const m = Math.floor(diffSec / 60);
        txt = `il y a ${m} min`;
    } else {
        const h = Math.floor(diffSec / 3600);
        txt = `il y a ${h} h`;
    }
    label.textContent = txt;
}

// Reset le badge quand on rouvre le modal sur une autre classe
$(document).on('hidden.bs.modal', '#classSelectionModal', function () {
    const badge = document.getElementById('nm-sync-badge');
    if (badge) badge.style.display = 'none';
    nmLastSyncAt = null;
    if (nmSyncInterval) {
        clearInterval(nmSyncInterval);
        nmSyncInterval = null;
    }
});

function toggleAbsence(studentId, evaluationId, isAbsent) {
    const input = $(`input.note-input[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);

    if (isAbsent) {
        input.val('0').prop('disabled', true);
        saveNote(studentId, evaluationId, 0);
    } else {
        input.val('0').prop('disabled', false).focus();
        // Sauvegarder la suppression de l'absence côté serveur (note reste à 0)
        $.ajax({
            url: '{{ route("esbtp.notes.save-ajax") }}',
            method: 'POST',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                _token: '{{ csrf_token() }}',
                etudiant_id: studentId,
                evaluation_id: evaluationId,
                note: 0,
                is_absent: ''
            },
            success: function(response) {
                if (response.success) {
                    if (!notesData[studentId]) notesData[studentId] = {};
                    notesData[studentId][evaluationId] = 0;
                    notesData[studentId][evaluationId + '_absent'] = false;
                    calculateStudentAverage(studentId);
                    calculateClassAverages();
                    triggerRowHighlight(studentId);
                }
            }
        });
    }
}

function calculateAllAverages() {
    $('tr[data-student-id]').each(function() {
        const studentId = $(this).data('student-id');
        calculateStudentAverage(studentId);
    });
    calculateClassAverages();
}

function getEvalParams(evalId) {
    // Use cache first, fallback to DOM query
    if (evalParamsCache[evalId]) return evalParamsCache[evalId];
    const bareme = parseFloat($(`.bareme-input[data-eval-id="${evalId}"]`).val()) || 20;
    const coefficient = parseFloat($(`.coeff-input[data-eval-id="${evalId}"]`).val()) || 1;
    evalParamsCache[evalId] = { bareme, coefficient };
    return evalParamsCache[evalId];
}

function calculateStudentAverage(studentId) {
    const row = $(`tr[data-student-id="${studentId}"]`);
    const noteInputs = row.find('.note-input');

    let totalPoints = 0;
    let totalCoefficients = 0;
    let hasNotes = false;

    noteInputs.each(function() {
        const evalId = $(this).data('eval-id');
        const isAbsent = $(`#absent-${studentId}-${evalId}`).is(':checked');
        const rawValue = $(this).val();
        const params = getEvalParams(evalId);

        // Garde-fou : barème invalide → ignorer (évite division par 0)
        if (!params.bareme || params.bareme <= 0) return;

        // BUG FIX : on traite la note 0 légitime (ex: 0/20) comme valide.
        // Avant : `noteValue > 0` excluait silencieusement 0 → moyenne fausse (10 et 0 → 10 au lieu de 5).
        // Aligné sur calculateClassAverages() pour cohérence des deux algos.
        if (!isAbsent && rawValue !== '') {
            const noteValue = parseFloat(rawValue);
            if (!isNaN(noteValue)) {
                const normalizedNote = (noteValue / params.bareme) * 20;
                totalPoints += normalizedNote * params.coefficient;
                totalCoefficients += params.coefficient;
                hasNotes = true;
            }
        }
    });

    const averageCell = row.find('.average-cell');
    const appreciationBadge = row.find('.nm-appreciation');

    if (hasNotes && totalCoefficients > 0) {
        const moyenne = totalPoints / totalCoefficients;
        averageCell.text(moyenne.toFixed(2));

        let appreciation = '';
        let levelClass = '';

        if (moyenne >= 16) {
            appreciation = 'Excellent';
            levelClass = 'excellent';
        } else if (moyenne >= 14) {
            appreciation = 'Très bien';
            levelClass = 'tres-bien';
        } else if (moyenne >= 12) {
            appreciation = 'Bien';
            levelClass = 'bien';
        } else if (moyenne >= 10) {
            appreciation = 'Assez bien';
            levelClass = 'assez-bien';
        } else if (moyenne >= 8) {
            appreciation = 'Passable';
            levelClass = 'passable';
        } else {
            appreciation = 'Insuffisant';
            levelClass = 'insuffisant';
        }

        appreciationBadge.text(appreciation).removeClass().addClass(`nm-appreciation ${levelClass}`);
    } else {
        averageCell.text('--');
        appreciationBadge.text('--').removeClass().addClass('nm-appreciation default');
    }
}

function calculateClassAverages() {
    const evaluations = currentEvaluations.length ? currentEvaluations : Object.values(evaluationsData);

    evaluations.forEach(evaluation => {
        const evalId = evaluation.id;
        const noteInputs = $(`.note-input[data-eval-id="${evalId}"]`);
        const params = getEvalParams(evalId);

        let total = 0;
        let count = 0;

        noteInputs.each(function() {
            const studentId = $(this).data('student-id');
            const isAbsent = $(`#absent-${studentId}-${evalId}`).is(':checked');
            const rawValue = $(this).val();

            if (!isAbsent && rawValue !== '') {
                const noteValue = parseFloat(rawValue);
                if (!isNaN(noteValue)) {
                    const normalizedNote = (noteValue / params.bareme) * 20;
                    total += normalizedNote;
                    count++;
                }
            }
        });

        const avgCell = $(`.class-avg-${evalId}`);
        if (count > 0) {
            avgCell.text((total / count).toFixed(2));
        } else {
            avgCell.text('--');
        }
    });

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

function triggerRowHighlight(studentId) {
    const row = $(`tr[data-student-id="${studentId}"]`);
    row.addClass('highlight-success');
    setTimeout(function() {
        row.removeClass('highlight-success');
    }, 2000);
    // PR #4 — Toast premium (le listener est défini en bas du script).
    window.dispatchEvent(new CustomEvent('nm:note-saved', { detail: { studentId: studentId } }));
}

$('#saveAllNotesBtn').on('click', function() {
    const btn = $(this);
    const originalText = btn.html();

    // Collect only inputs with actual values (dirty notes)
    const inputs = $('.note-input').filter(function() {
        const val = $(this).val();
        return val !== '' && val !== null && val !== undefined;
    });

    if (inputs.length === 0) {
        btn.html('<i class="fas fa-info-circle me-1"></i> Aucune note à enregistrer').prop('disabled', false);
        setTimeout(() => { btn.html(originalText); }, 2000);
        return;
    }

    btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...').prop('disabled', true);

    // Collecter toutes les notes en un seul tableau
    const notesPayload = [];
    inputs.each(function() {
        const studentId = $(this).data('student-id');
        const evalId = $(this).data('eval-id');
        notesPayload.push({
            etudiant_id: studentId,
            evaluation_id: evalId,
            note: $(this).val(),
            is_absent: $(`#absent-${studentId}-${evalId}`).is(':checked') ? 'on' : ''
        });
    });

    // Envoyer une seule requête bulk au lieu d'une par étudiant
    $.ajax({
        url: '{{ route("esbtp.notes.save-ajax-bulk") }}',
        method: 'POST',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        data: {
            _token: '{{ csrf_token() }}',
            notes: notesPayload
        },
        success: function(response) {
            if (response.success) {
                btn.html(`<i class="fas fa-check me-1"></i> ${response.saved} note(s) enregistrée(s)`).prop('disabled', false);
            } else {
                btn.html(`<i class="fas fa-exclamation-triangle me-1"></i> ${response.errors} erreur(s)`).prop('disabled', false);
            }
            // Highlight rows and recalculate averages
            notesPayload.forEach(function(entry) {
                triggerRowHighlight(entry.etudiant_id);
                if (!notesData[entry.etudiant_id]) notesData[entry.etudiant_id] = {};
                notesData[entry.etudiant_id][entry.evaluation_id] = entry.is_absent === 'on' ? 0 : entry.note;
                calculateStudentAverage(entry.etudiant_id);
            });
            calculateClassAverages();
            setTimeout(() => { btn.html(originalText); }, 2500);
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message || 'Erreur lors de la sauvegarde.';
            btn.html(`<i class="fas fa-times me-1"></i> Échec`).prop('disabled', false);
            alert(msg);
            setTimeout(() => { btn.html(originalText); }, 2500);
        }
    });
});

// Auto-calcul durée
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
    if (diff <= 0) diff += 24 * 60;
    if (duree && !duree.value) duree.value = diff;
    if (badge) badge.textContent = `${diff} min`;
}

document.addEventListener('DOMContentLoaded', function () {
    const debutEl = document.getElementById('eval_heure_debut');
    const finEl   = document.getElementById('eval_heure_fin');
    if (debutEl) debutEl.addEventListener('change', evalUpdateDuree);
    if (finEl)   finEl.addEventListener('change', evalUpdateDuree);
    evalUpdateDuree();

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
            const message = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Erreur lors de la création de l\'évaluation.';
            alert(message);
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
    Object.values(evalFieldMap).forEach(id => {
        if (!id) return;
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('is-invalid');
            const fb = el.parentElement.querySelector('.invalid-feedback');
            if (fb) fb.textContent = '';
        }
    });

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

    Object.entries(errors || {}).forEach(([field, messages]) => {
        const inputId = evalFieldMap[field];
        if (!inputId) return;
        const el = document.getElementById(inputId);
        if (!el) return;
        el.classList.add('is-invalid');
        const fb = el.parentElement.querySelector('.invalid-feedback');
        if (fb) fb.textContent = (messages || [])[0] || '';
    });

    const modalBody = document.querySelector('#evaluationCreateModal .modal-body');
    if (modalBody) modalBody.scrollTop = 0;
}

function closeEvaluationModal() {
    // PR #4 — "Créer et continuer" : si l'utilisateur a déclenché ce bouton,
    // on garde le modal ouvert et on reset le formulaire (sauf classe + matiere
    // + date + horaires + bareme + coef qu'on conserve pour l'éval suivante).
    if (window.nmEvalSaveContinueRequested) {
        window.nmEvalSaveContinueRequested = false;
        const form = document.getElementById('evaluationCreateForm');
        if (form) {
            ['eval_titre', 'eval_description', 'eval_type'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }
        const errorsEl = document.getElementById('evalModal_errors');
        if (errorsEl) { errorsEl.style.display = 'none'; errorsEl.innerHTML = ''; }
        const body = document.querySelector('#evaluationCreateModal .modal-body');
        if (body && !body.querySelector('.nm-saved-continue-toast')) {
            const t = document.createElement('div');
            t.className = 'nm-saved-continue-toast';
            t.innerHTML = '<i class="fas fa-check-circle"></i><span>Évaluation créée. Saisissez la suivante.</span>';
            body.insertBefore(t, body.firstChild);
            setTimeout(() => { if (t.parentNode) t.parentNode.removeChild(t); }, 3500);
        }
        const titreEl = document.getElementById('eval_titre');
        if (titreEl) setTimeout(() => titreEl.focus(), 50);
        evalResetSubmitBtn();
        return;
    }

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

function showSuccessMessage(message) {
    const alertEl = document.createElement('div');
    alertEl.className = 'alert alert-success alert-dismissible fade show';
    alertEl.setAttribute('role', 'alert');
    alertEl.innerHTML = `<i class="fas fa-check-circle me-2"></i>${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
    const hero = document.querySelector('.nm-hero');
    if (hero) hero.after(alertEl);
    setTimeout(() => { alertEl.remove(); }, 5000);
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

// ════════════════════════════════════════════════════════════════════════
// PR #7 — Excel Export/Import + Preview Impact (JS)
// ════════════════════════════════════════════════════════════════════════

const PR7 = {
    routes: {
        exportExcel: '{{ route("esbtp.notes.export-excel") }}',
        @can('notes.import_excel')
        importDryRun: '{{ route("esbtp.notes.import.dry-run") }}',
        importApply:  '{{ route("esbtp.notes.import.apply") }}',
        @endcan
        previewImpact: '{{ route("esbtp.notes.preview-impact") }}',
    },
    csrf: '{{ csrf_token() }}',
    selectedFile: null,
    impactDebounceTimers: {},
    impactCache: {},
};

// ── Activer/désactiver les boutons Export/Import selon classe + matière + période ──
function pr7UpdateButtons() {
    const periodeFilter = $('#periodeFilter').val();
    const validPeriode = (periodeFilter === 'semestre1' || periodeFilter === 'semestre2');
    const ready = currentClassId && currentMatiereId && validPeriode;

    const exportBtn = document.getElementById('exportExcelBtn');
    if (exportBtn) {
        exportBtn.disabled = !ready;
        exportBtn.classList.toggle('disabled', !ready);
        if (ready) {
            exportBtn.title = `Exporter les notes — ${currentClassname || ''} (${periodeFilter})`;
        } else {
            exportBtn.title = 'Sélectionnez classe, matière et période (semestre 1 ou 2)';
        }
    }

    const importBtn = document.getElementById('openImportModalBtn');
    if (importBtn) {
        importBtn.disabled = !ready;
        importBtn.classList.toggle('disabled', !ready);
    }
}

$(document).on('change', '#matiereSelect, #periodeFilter', pr7UpdateButtons);
$(document).on('click', '.class-card, .nm-class-card, .class-select-btn, .nm-card-action', function() {
    setTimeout(pr7UpdateButtons, 50);
});

// ── Bouton Export Excel ──
$(document).on('click', '#exportExcelBtn:not(:disabled):not(.disabled)', function() {
    const periode = $('#periodeFilter').val();
    if (!currentClassId || !currentMatiereId || !(periode === 'semestre1' || periode === 'semestre2')) {
        return;
    }
    const url = PR7.routes.exportExcel
        + '?classe=' + encodeURIComponent(currentClassId)
        + '&matiere=' + encodeURIComponent(currentMatiereId)
        + '&periode=' + encodeURIComponent(periode);
    window.open(url, '_blank');
});

@can('notes.import_excel')
// ── Ouvrir modal import ──
$(document).on('click', '#openImportModalBtn:not(:disabled):not(.disabled)', function() {
    const periode = $('#periodeFilter').val();
    if (!currentClassId || !currentMatiereId || !(periode === 'semestre1' || periode === 'semestre2')) {
        return;
    }
    pr7ResetImportModal();
    document.getElementById('nm-import-context').textContent =
        `${currentClassname || 'Classe'} — ${currentMatiereName || 'Matière'} — ${periode === 'semestre1' ? 'Semestre 1' : 'Semestre 2'}`;
    const modalEl = document.getElementById('nm-import-preview-modal');
    if (window.bootstrap && window.bootstrap.Modal) {
        new window.bootstrap.Modal(modalEl).show();
    } else {
        $(modalEl).modal('show');
    }
});

function pr7ResetImportModal() {
    PR7.selectedFile = null;
    const fileInput = document.getElementById('nm-import-file-input');
    if (fileInput) fileInput.value = '';
    document.getElementById('nm-import-dropzone').style.display = '';
    document.getElementById('nm-import-loading').style.display = 'none';
    document.getElementById('nm-import-preview').style.display = 'none';
    document.getElementById('nm-import-errors').style.display = 'none';
    const confirmBtn = document.getElementById('nm-import-confirm-btn');
    if (confirmBtn) confirmBtn.disabled = true;
}

// Drop zone handlers
$(document).on('click', '#nm-import-dropzone', function() {
    document.getElementById('nm-import-file-input').click();
});

$(document).on('dragover', '#nm-import-dropzone', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('is-dragover');
});

$(document).on('dragleave drop', '#nm-import-dropzone', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('is-dragover');
});

$(document).on('drop', '#nm-import-dropzone', function(e) {
    const files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
    if (files && files.length > 0) {
        pr7HandleFile(files[0]);
    }
});

$(document).on('change', '#nm-import-file-input', function(e) {
    const file = e.target.files && e.target.files[0];
    if (file) pr7HandleFile(file);
});

$(document).on('click', '#nm-import-file-clear', function() {
    pr7ResetImportModal();
});

function pr7HandleFile(file) {
    if (!file) return;

    // Validation basique côté client
    const validExt = /\.(xlsx|xls|csv)$/i;
    if (!validExt.test(file.name)) {
        alert('Format invalide. Utilisez xlsx, xls ou csv.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Fichier trop volumineux (max 5 Mo).');
        return;
    }

    PR7.selectedFile = file;
    document.getElementById('nm-import-filename').textContent = file.name;

    // Show loading
    document.getElementById('nm-import-dropzone').style.display = 'none';
    document.getElementById('nm-import-loading').style.display = '';
    document.getElementById('nm-import-preview').style.display = 'none';

    // Send dry-run
    const formData = new FormData();
    formData.append('file', file);
    formData.append('classe_id', currentClassId);
    formData.append('matiere_id', currentMatiereId);
    formData.append('periode', $('#periodeFilter').val());

    fetch(PR7.routes.importDryRun, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': PR7.csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            document.getElementById('nm-import-loading').style.display = 'none';
            if (!ok || !data.success) {
                pr7ShowError(data.message || 'Erreur lors de l\'analyse du fichier.');
                return;
            }
            pr7RenderPreview(data);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('nm-import-loading').style.display = 'none';
            pr7ShowError('Erreur réseau. Vérifiez votre connexion.');
        });
}

function pr7ShowError(message) {
    document.getElementById('nm-import-dropzone').style.display = '';
    alert(message);
}

function pr7RenderPreview(data) {
    document.getElementById('nm-import-preview').style.display = '';

    const summary = data.summary || {};
    document.getElementById('nm-import-kpi-create').textContent = summary.will_create || 0;
    document.getElementById('nm-import-kpi-update').textContent = summary.will_update || 0;
    document.getElementById('nm-import-kpi-unchanged').textContent = summary.unchanged || 0;
    document.getElementById('nm-import-kpi-error').textContent = summary.errors || 0;

    // Errors
    const errors = data.errors || [];
    const errBox = document.getElementById('nm-import-errors');
    const errList = document.getElementById('nm-import-errors-list');
    if (errors.length > 0) {
        errBox.style.display = '';
        errList.innerHTML = errors.map(e => `
            <div class="nm-import-error-item">
                <span class="err-cell">L${e.row || '?'}${e.col || ''}</span>
                ${e.matricule ? `<strong>${pr7Escape(e.matricule)}</strong>` : ''}
                ${e.evaluation ? ` — <em>${pr7Escape(e.evaluation)}</em>` : ''}
                — ${pr7Escape(e.reason || '')}
            </div>
        `).join('');
    } else {
        errBox.style.display = 'none';
    }

    // Changes table
    const changes = data.changes || [];
    document.getElementById('nm-import-changes-count').textContent = changes.length;
    const body = document.getElementById('nm-import-changes-body');
    if (changes.length === 0) {
        body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Aucun changement détecté</td></tr>';
    } else {
        body.innerHTML = changes.slice(0, 500).map(c => {
            const beforeDisp = (c.before === null || c.before === undefined) ? '<em style="color:#94a3b8;">—</em>' : pr7Escape(String(c.before));
            const afterDisp = (c.after === 'ABS' || c.is_absent)
                ? '<span class="cell-after-abs">ABS</span>'
                : `<span class="cell-after">${pr7Escape(String(c.after))}</span>`;
            return `
                <tr class="row-${c.action}">
                    <td>L${c.row || '?'}</td>
                    <td><strong>${pr7Escape(c.matricule || '')}</strong> — ${pr7Escape(c.etudiant_nom || '')}</td>
                    <td>${pr7Escape(c.evaluation || '')}</td>
                    <td><span class="cell-before">${beforeDisp}</span></td>
                    <td>${afterDisp}</td>
                    <td><span class="badge-action-${c.action}">${c.action === 'create' ? 'Créer' : 'Mettre à jour'}</span></td>
                </tr>
            `;
        }).join('');
        if (changes.length > 500) {
            body.innerHTML += `<tr><td colspan="6" class="text-center text-muted py-2"><em>… ${changes.length - 500} autres changements masqués pour l'affichage. Tous seront appliqués.</em></td></tr>`;
        }
    }

    // Confirm button : enabled seulement si pas d'erreurs ET au moins 1 change
    const confirmBtn = document.getElementById('nm-import-confirm-btn');
    confirmBtn.disabled = (errors.length > 0) || (changes.length === 0);
}

// Confirm import → apply
$(document).on('click', '#nm-import-confirm-btn:not(:disabled)', function() {
    if (!PR7.selectedFile) return;

    const btn = this;
    const spinner = document.getElementById('nm-import-confirm-spinner');
    const icon = document.getElementById('nm-import-confirm-icon');
    btn.disabled = true;
    if (spinner) spinner.classList.remove('d-none');
    if (icon) icon.classList.add('d-none');

    const formData = new FormData();
    formData.append('file', PR7.selectedFile);
    formData.append('classe_id', currentClassId);
    formData.append('matiere_id', currentMatiereId);
    formData.append('periode', $('#periodeFilter').val());

    fetch(PR7.routes.importApply, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': PR7.csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');

            if (!ok || !data.success) {
                alert(data.message || 'Erreur lors de l\'application de l\'import.');
                return;
            }

            // Toast + close + reload notes grid
            pr7Toast(data.message || 'Import réussi.', 'success');
            const modalEl = document.getElementById('nm-import-preview-modal');
            const modal = window.bootstrap ? window.bootstrap.Modal.getInstance(modalEl) : null;
            if (modal) modal.hide(); else $(modalEl).modal('hide');

            // Recharge la grille
            if (typeof loadEvaluationsAndNotes === 'function' && currentClassId && currentMatiereId) {
                loadEvaluationsAndNotes();
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            alert('Erreur réseau lors de l\'application de l\'import.');
        });
});

function pr7Toast(message, type) {
    // Délègue au stack premium nmShowToast (PR #321) — garantit cohérence visuelle
    // et anti-stacking. La fonction est hoistée donc disponible quel que soit l'ordre.
    nmShowToast(type || 'success', message);
}

@endcan

function pr7Escape(str) {
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

// ── Preview impact (debounced 500ms après dernière frappe) ──
$(document).on('focus input', '.note-input', function() {
    const $input = $(this);
    const studentId = parseInt($input.data('student-id'));
    const evalId = parseInt($input.data('eval-id'));
    const noteValue = $input.val();
    const isAbsent = $input.is(':disabled');

    if (!studentId || !evalId) return;

    const periode = $('#periodeFilter').val();
    if (!(periode === 'semestre1' || periode === 'semestre2')) {
        // Pas de période semestre claire : on ne peut pas calculer
        return;
    }

    // Debounce per-input
    const key = studentId + '_' + evalId;
    if (PR7.impactDebounceTimers[key]) {
        clearTimeout(PR7.impactDebounceTimers[key]);
    }

    // Show "calcul en cours" placeholder immédiatement
    pr7ShowImpactLoading(studentId, evalId);

    PR7.impactDebounceTimers[key] = setTimeout(() => {
        pr7FetchImpact(studentId, evalId, noteValue, isAbsent, periode);
    }, 500);
});

$(document).on('blur', '.note-input', function() {
    // Ne pas masquer la row impact au blur (pour la voir après save)
});

function pr7ShowImpactLoading(studentId, evalId) {
    const $studentRow = $('.note-input[data-student-id="' + studentId + '"]').first().closest('tr');
    if ($studentRow.length === 0) return;

    let $impactRow = $studentRow.next('.nm-impact-row[data-impact-student="' + studentId + '"]');
    if ($impactRow.length === 0) {
        $impactRow = $('<tr class="nm-impact-row" data-impact-student="' + studentId + '"><td colspan="100"><span class="nm-impact-loading"><i class="fas fa-spinner fa-spin me-1"></i>Calcul en cours…</span></td></tr>');
        $studentRow.after($impactRow);
    } else {
        $impactRow.find('td').html('<span class="nm-impact-loading"><i class="fas fa-spinner fa-spin me-1"></i>Calcul en cours…</span>');
    }
}

function pr7FetchImpact(studentId, evalId, noteValue, isAbsent, periode) {
    if (!currentClassId || !currentMatiereId) return;

    const numNote = noteValue === '' || noteValue === null ? null : parseFloat(noteValue);
    if (!isAbsent && (numNote === null || isNaN(numNote))) {
        // Cellule vide : retire la row impact
        const $studentRow = $('.note-input[data-student-id="' + studentId + '"]').first().closest('tr');
        $studentRow.next('.nm-impact-row').remove();
        return;
    }

    const formData = new FormData();
    formData.append('etudiant_id', studentId);
    formData.append('classe_id', currentClassId);
    formData.append('matiere_id', currentMatiereId);
    formData.append('periode', periode);
    formData.append('evaluation_id', evalId);
    formData.append('hypothetical_note', isAbsent ? 0 : numNote);
    formData.append('is_absent', isAbsent ? 1 : 0);

    fetch(PR7.routes.previewImpact, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': PR7.csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            pr7RenderImpact(studentId, data);
        })
        .catch(() => {
            // Silencieux : preview est best-effort
        });
}

function pr7RenderImpact(studentId, data) {
    const $studentRow = $('.note-input[data-student-id="' + studentId + '"]').first().closest('tr');
    if ($studentRow.length === 0) return;

    let $impactRow = $studentRow.next('.nm-impact-row[data-impact-student="' + studentId + '"]');
    if ($impactRow.length === 0) {
        $impactRow = $('<tr class="nm-impact-row" data-impact-student="' + studentId + '"><td colspan="100"></td></tr>');
        $studentRow.after($impactRow);
    }

    // Récup nom étudiant
    const studentName = $studentRow.find('.nm-student-fullname').text().trim() || 'Étudiant';

    // Format display values
    const matAv = data.matiere_avant !== null ? data.matiere_avant.toFixed(2) : '—';
    const matAp = data.matiere_apres !== null ? data.matiere_apres.toFixed(2) : '—';
    const genAv = data.moyenne_generale_avant !== null ? data.moyenne_generale_avant.toFixed(2) : '—';
    const genAp = data.moyenne_generale_apres !== null ? data.moyenne_generale_apres.toFixed(2) : '—';

    let mentionPart = '';
    if (data.mention_avant && data.mention_apres) {
        if (data.changed_mention) {
            const direction = (data.matiere_apres || 0) > (data.matiere_avant || 0) ? 'up' : 'down';
            mentionPart = `<span class="nm-impact-mention nm-impact-mention-${direction}">${pr7Escape(data.mention_avant)} → ${pr7Escape(data.mention_apres)}</span>`;
        } else {
            mentionPart = `<span class="nm-impact-mention">${pr7Escape(data.mention_apres)}</span>`;
        }
    }

    const html = `
        <div class="nm-impact-content">
            <span><strong>${pr7Escape(studentName)}</strong></span>
            <div class="nm-impact-block">
                <span class="nm-impact-label">moyenne matière</span>
                <span class="nm-impact-value-old">${matAv}</span>
                <i class="fas fa-arrow-right nm-impact-arrow"></i>
                <span class="nm-impact-value-new">${matAp}</span>
                ${mentionPart}
            </div>
            <div class="nm-impact-block">
                <span class="nm-impact-label">moyenne générale</span>
                <span class="nm-impact-value-old">${genAv}</span>
                <i class="fas fa-arrow-right nm-impact-arrow"></i>
                <span class="nm-impact-value-new">${genAp}</span>
            </div>
        </div>
    `;
    $impactRow.find('td').html(html);
}

// ════════════════════════════════════════════════════════════════════════
// PR #3+#4 — Robustesse saisie + UX premium modal
// ════════════════════════════════════════════════════════════════════════
//
// • localStorage autosave (anti-perte sur coupure réseau)
// • Network badge 3 états (synchronisé / sauvegarde / hors ligne)
// • Raccourcis clavier (Tab/Enter/Ctrl+S/Échap)
// • Recherche étudiant + Ctrl+F
// • Pagination "Charger plus"
// • Sticky thead (CSS) + indicateur scroll horizontal
// • Toast premium au save
// • Absent toggle switch + ligne stylée
// • Édition rapide évaluation
// • Save & Continue dans modal création
// • beforeunload + confirm fermeture
// ════════════════════════════════════════════════════════════════════════

const NM = {
    routes: {
        evalQuickUpdate: '{{ url("/esbtp/evaluations") }}',  // + /{id}/quick-update
    },
    csrf: '{{ csrf_token() }}',
    autosaveDebounceTimer: null,
    autosaveDebounceMs: 350,
    draftTtlMs: 7 * 24 * 60 * 60 * 1000,  // 7 jours
    pendingSaves: 0,
    consecutiveErrors: 0,
    networkBadge: null,
    pagination: {
        pageSize: 50,
        chunk: 30,
        rendered: 0,
        all: [],
        sortedEvaluations: [],
    },
    studentsCache: null,  // référence courante des étudiants pour la recherche
    quickEditCurrentId: null,
};

window.nmHasUnsavedChanges = false;

// Set des notes "sales" (dirty) — modifiées dans le DOM mais pas encore confirmées
// par le serveur. Clé = `${studentId}-${evaluationId}`. Source de vérité pour
// distinguer "vraiment non sauvegardé" d'un simple input pré-rempli au render.
// Sans ça, l'autosave ré-écrit en localStorage TOUS les inputs visibles (y compris
// ceux déjà confirmés serveur) → la bannière brouillon ne disparaît jamais
// (incident Marcel 04/06/2026).
window.nmDirtyNotes = new Set();
function nmDirtyKey(sid, eid) { return `${sid}-${eid}`; }
function nmMarkDirty(sid, eid) { if (sid && eid) window.nmDirtyNotes.add(nmDirtyKey(sid, eid)); }
function nmMarkClean(sid, eid) { if (sid && eid) window.nmDirtyNotes.delete(nmDirtyKey(sid, eid)); }

// ── 1. Toast premium (queue, max 3 visibles) ────────────────────────────
function nmShowToast(type, message, durationMs) {
    type = type || 'info';
    durationMs = durationMs || 3500;

    let stack = document.querySelector('.nm-toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'nm-toast-stack';
        stack.setAttribute('role', 'status');
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
    }

    // Trim queue à 3
    while (stack.children.length >= 3) {
        stack.removeChild(stack.firstChild);
    }

    const iconMap = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle',
    };
    const icon = iconMap[type] || iconMap.info;

    const toast = document.createElement('div');
    toast.className = 'nm-toast nm-toast--' + type;
    toast.innerHTML = `
        <i class="fas ${icon} nm-toast-icon" aria-hidden="true"></i>
        <div class="nm-toast-msg"></div>
        <button type="button" class="nm-toast-close" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </button>
    `;
    toast.querySelector('.nm-toast-msg').textContent = message;

    const dismiss = () => {
        if (toast.classList.contains('nm-toast--leaving')) return;
        toast.classList.add('nm-toast--leaving');
        setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 220);
    };
    toast.querySelector('.nm-toast-close').addEventListener('click', dismiss);
    stack.appendChild(toast);
    setTimeout(dismiss, durationMs);

    return toast;
}

// ── 2. Network badge state ──────────────────────────────────────────────
function nmGetBadge() {
    if (!NM.networkBadge) {
        NM.networkBadge = document.getElementById('nm-network-badge');
    }
    return NM.networkBadge;
}
function nmSetNetworkState(state) {
    const badge = nmGetBadge();
    if (!badge) return;
    badge.dataset.state = state;
    const label = badge.querySelector('.nm-network-label');
    if (!label) return;
    if (state === 'syncing') {
        label.textContent = 'Sauvegarde…';
    } else if (state === 'offline') {
        label.textContent = 'Hors ligne — brouillon local';
    } else {
        label.textContent = 'Synchronisé';
    }
}
window.addEventListener('online', function() {
    NM.consecutiveErrors = 0;
    nmSetNetworkState('synced');
    nmShowToast('success', 'Connexion rétablie. Synchronisation reprise.');
});
window.addEventListener('offline', function() {
    nmSetNetworkState('offline');
    nmShowToast('warning', 'Hors ligne — vos modifications restent sauvegardées localement.');
});

// Bridge avec saveNote/saveAllNotes existants : intercepter $.ajax
$(document).ajaxSend(function(_event, _jqxhr, settings) {
    if (typeof settings.url === 'string' && /(save-ajax|save-ajax-bulk)/.test(settings.url)) {
        NM.pendingSaves++;
        if (navigator.onLine !== false) {
            nmSetNetworkState('syncing');
        }
    }
});
$(document).ajaxSuccess(function(_event, _jqxhr, settings) {
    if (typeof settings.url === 'string' && /(save-ajax|save-ajax-bulk)/.test(settings.url)) {
        NM.pendingSaves = Math.max(0, NM.pendingSaves - 1);
        NM.consecutiveErrors = 0;
        if (NM.pendingSaves === 0 && navigator.onLine !== false) {
            nmSetNetworkState('synced');
        }
    }
});
$(document).ajaxError(function(_event, _jqxhr, settings) {
    if (typeof settings.url === 'string' && /(save-ajax|save-ajax-bulk)/.test(settings.url)) {
        NM.pendingSaves = Math.max(0, NM.pendingSaves - 1);
        NM.consecutiveErrors++;
        if (NM.consecutiveErrors >= 3 || navigator.onLine === false) {
            nmSetNetworkState('offline');
        } else if (NM.pendingSaves === 0) {
            nmSetNetworkState('synced');
        }
    }
});

// ── 3. localStorage autosave (anti-perte) ───────────────────────────────
function nmDraftKey() {
    if (!currentClassId || !currentMatiereId) return null;
    const periode = $('#periodeFilter').val() || 'all';
    return `nm_notes_draft_${currentClassId}_${currentMatiereId}_${periode}`;
}
function nmCollectDraftNotes() {
    // Ne collecter QUE les notes dirty (modifiées + pas encore confirmées serveur).
    // Sans ce filtre, l'autosave ré-écrit en localStorage TOUS les inputs visibles
    // (y compris ceux dont la valeur vient juste d'être restaurée puis sauvée),
    // ce qui ressuscite la bannière "Brouillon non sauvegardé" indéfiniment.
    const out = {};
    if (window.nmDirtyNotes.size === 0) return out;
    window.nmDirtyNotes.forEach(function(key) {
        const sep = key.indexOf('-');
        if (sep < 0) return;
        const sid = key.substring(0, sep);
        const eid = key.substring(sep + 1);
        const $i = $(`.note-input[data-student-id="${sid}"][data-eval-id="${eid}"]`);
        if ($i.length === 0) return;
        const val = $i.val();
        const isAbsent = $(`#absent-${sid}-${eid}`).is(':checked');
        if ((val !== '' && val !== null && val !== undefined) || isAbsent) {
            if (!out[eid]) out[eid] = {};
            out[eid][sid] = { note: isAbsent ? 0 : val, isAbsent: !!isAbsent };
        }
    });
    return out;
}
function nmAutosaveDraft() {
    const key = nmDraftKey();
    if (!key) return;
    const notes = nmCollectDraftNotes();
    if (Object.keys(notes).length === 0) {
        // Aucune note dirty → purger le draft ET masquer la bannière si visible.
        try { localStorage.removeItem(key); } catch (e) { /* quota */ }
        nmHideDraftBanner();
        return;
    }
    try {
        localStorage.setItem(key, JSON.stringify({
            savedAt: Date.now(),
            notes: notes,
            classLabel: currentClassname,
            matiereLabel: currentMatiereName,
        }));
    } catch (e) {
        // localStorage plein ou désactivé : silencieux
        console.warn('NM autosave failed:', e);
    }
}
function nmScheduleAutosave() {
    if (NM.autosaveDebounceTimer) clearTimeout(NM.autosaveDebounceTimer);
    NM.autosaveDebounceTimer = setTimeout(nmAutosaveDraft, NM.autosaveDebounceMs);
}
function nmPurgeOldDrafts() {
    try {
        const now = Date.now();
        const keys = [];
        for (let i = 0; i < localStorage.length; i++) {
            const k = localStorage.key(i);
            if (k && k.startsWith('nm_notes_draft_')) keys.push(k);
        }
        keys.forEach(k => {
            try {
                const obj = JSON.parse(localStorage.getItem(k) || '{}');
                if (!obj.savedAt || (now - obj.savedAt) > NM.draftTtlMs) {
                    localStorage.removeItem(k);
                }
            } catch (e) { localStorage.removeItem(k); }
        });
    } catch (e) { /* ignore */ }
}
function nmRelativeTime(timestamp) {
    const diff = Math.max(0, Date.now() - timestamp);
    const sec = Math.floor(diff / 1000);
    if (sec < 60) return 'quelques secondes';
    const min = Math.floor(sec / 60);
    if (min < 60) return `${min} min`;
    const h = Math.floor(min / 60);
    if (h < 24) return `${h} h`;
    const d = Math.floor(h / 24);
    return `${d} j`;
}
function nmCheckDraftBanner() {
    const key = nmDraftKey();
    if (!key) return;
    const banner = document.getElementById('nm-restore-banner');
    if (!banner) return;
    let raw;
    try { raw = localStorage.getItem(key); } catch (e) { return; }
    if (!raw) { banner.style.display = 'none'; return; }
    let obj;
    try { obj = JSON.parse(raw); } catch (e) { localStorage.removeItem(key); return; }
    if (!obj || !obj.notes) { banner.style.display = 'none'; return; }

    let count = 0;
    Object.values(obj.notes).forEach(byStud => count += Object.keys(byStud).length);
    if (count === 0) { banner.style.display = 'none'; return; }

    document.getElementById('nm-restore-time').textContent = nmRelativeTime(obj.savedAt || Date.now());
    document.getElementById('nm-restore-count').textContent = count;
    banner.style.display = 'flex';
}
function nmHideDraftBanner() {
    const banner = document.getElementById('nm-restore-banner');
    if (banner) banner.style.display = 'none';
}
function nmRestoreFromDraft() {
    const key = nmDraftKey();
    if (!key) return;
    let obj;
    try { obj = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) { return; }
    if (!obj || !obj.notes) return;

    let restored = 0;
    Object.entries(obj.notes).forEach(([eid, byStud]) => {
        Object.entries(byStud).forEach(([sid, payload]) => {
            const $input = $(`.note-input[data-student-id="${sid}"][data-eval-id="${eid}"]`);
            if ($input.length === 0) return;

            if (payload.isAbsent) {
                const $checkbox = $(`#absent-${sid}-${eid}`);
                $checkbox.prop('checked', true);
                $input.val('0').prop('disabled', true);
                if (typeof toggleAbsence === 'function') {
                    // ne pas re-déclencher AJAX si déjà absent
                }
                saveNote(sid, eid, 0);  // persist serveur
            } else {
                $input.val(payload.note);
                saveNote(sid, eid, payload.note);
            }
            restored++;
        });
    });
    nmHideDraftBanner();
    nmShowToast('success', `${restored} note(s) restaurée(s) depuis le brouillon local.`);
    try { localStorage.removeItem(key); } catch (e) { /* ignore */ }
}
function nmDiscardDraft() {
    const key = nmDraftKey();
    if (!key) return;
    try { localStorage.removeItem(key); } catch (e) { /* ignore */ }
    nmHideDraftBanner();
    nmShowToast('info', 'Brouillon local ignoré.');
}

$(document).on('click', '#nm-restore-btn', nmRestoreFromDraft);
$(document).on('click', '#nm-restore-discard', nmDiscardDraft);

// Hook autosave + dirty flag sur tous les inputs notes.
// On marque la note dirty AVANT que saveNote()/AJAX soit appelé : l'autosave
// suivant la persistera localement le temps que le serveur confirme.
$(document).on('input change', '.note-input, .absence-checkbox', function() {
    const $el = $(this);
    let sid = $el.data('student-id');
    let eid = $el.data('eval-id');
    if (!sid || !eid) {
        // Cas checkbox absence : on extrait depuis l'id (absent-${sid}-${eid})
        const id = $el.attr('id') || '';
        const m = id.match(/^absent-(\d+)-(\d+)$/);
        if (m) { sid = m[1]; eid = m[2]; }
    }
    if (sid && eid) nmMarkDirty(sid, eid);
    window.nmHasUnsavedChanges = true;
    nmScheduleAutosave();
});

// Quand un save serveur réussit, la note est confirmée : la marquer "clean"
// pour qu'elle ne soit plus collectée par l'autosave. Si plus aucune note
// dirty → le prochain nmAutosaveDraft purgera le draft + cachera la bannière.
$(document).ajaxSuccess(function(_event, _jqxhr, settings) {
    if (typeof settings.url === 'string' && /(save-ajax|save-ajax-bulk)/.test(settings.url)) {
        // Parser le payload pour récupérer les paires (etudiant_id, evaluation_id)
        // à marquer comme clean. Le payload peut être :
        //   - save-ajax : `etudiant_id=X&evaluation_id=Y` (1 paire)
        //   - save-ajax-bulk : `notes[0][etudiant_id]=X&notes[0][evaluation_id]=Y&notes[1]...`
        const data = settings.data || '';
        if (typeof data === 'string' && data.length) {
            const params = new URLSearchParams(data);
            // Cas simple
            const sid = params.get('etudiant_id');
            const eid = params.get('evaluation_id');
            if (sid && eid) nmMarkClean(sid, eid);
            // Cas bulk : reconstituer les paires via notes[i][etudiant_id] / notes[i][evaluation_id]
            const bulkSids = {}, bulkEids = {};
            for (const [key, val] of params.entries()) {
                let m = key.match(/^notes\[(\d+)\]\[etudiant_id\]$/);
                if (m) { bulkSids[m[1]] = val; continue; }
                m = key.match(/^notes\[(\d+)\]\[evaluation_id\]$/);
                if (m) { bulkEids[m[1]] = val; continue; }
            }
            Object.keys(bulkSids).forEach(function(idx) {
                if (bulkEids[idx]) nmMarkClean(bulkSids[idx], bulkEids[idx]);
            });
        }
        if (NM.pendingSaves === 0 && window.nmDirtyNotes.size === 0) {
            window.nmHasUnsavedChanges = false;
        }
        nmScheduleAutosave();
    }
});

// ── 4. Network indicator dispatcher (compat events custom externes) ────
window.addEventListener('nm:save-pending', () => nmSetNetworkState('syncing'));
window.addEventListener('nm:save-success', () => {
    if (NM.pendingSaves === 0 && navigator.onLine !== false) nmSetNetworkState('synced');
});
window.addEventListener('nm:save-error', () => nmSetNetworkState('offline'));

// ── 5. Toast au save (vient en plus du highlight existant) ─────────────
// `triggerRowHighlight()` (modifié plus haut) dispatch nm:note-saved après
// son comportement original. On garde le row-highlight ET on ajoute un
// toast premium avec le nom étudiant + nouvelle moyenne.
let _nmLastToastAt = 0;
let _nmToastBatch = { count: 0, names: new Set(), timer: null, lastStudentId: null };
window.addEventListener('nm:note-saved', function(e) {
    const studentId = e.detail && e.detail.studentId;
    if (!studentId) return;
    const $row = $(`tr[data-student-id="${studentId}"]`);
    if ($row.length === 0) return;
    const name = $row.find('.nm-student-fullname').text().trim() || 'Étudiant';

    // Si plusieurs saves successifs (<400ms) : agréger en 1 seul toast batch.
    // On garde la dernière ligne touchée pour relire sa moyenne quand le timer
    // s'épuise (lecture LATE, pas closure-captured) — défense en profondeur
    // au cas où d'autres saves arriveraient et modifieraient .average-cell
    // entre l'event et l'affichage du toast.
    const now = Date.now();
    _nmToastBatch.names.add(name);
    _nmToastBatch.count++;
    _nmToastBatch.lastStudentId = studentId;
    if (_nmToastBatch.timer) clearTimeout(_nmToastBatch.timer);
    _nmToastBatch.timer = setTimeout(() => {
        const total = _nmToastBatch.count;
        const namesList = Array.from(_nmToastBatch.names);
        // Relire la moyenne au moment du toast (état le plus récent du DOM).
        const $lastRow = $(`tr[data-student-id="${_nmToastBatch.lastStudentId}"]`);
        const avg = $lastRow.length ? $lastRow.find('.average-cell').text().trim() : '';
        let msg;
        if (total === 1) {
            msg = avg && avg !== '--'
                ? `Note enregistrée — ${namesList[0]} · moyenne ${avg}/20`
                : `Note enregistrée — ${namesList[0]}`;
        } else if (namesList.length <= 3) {
            msg = `${total} notes enregistrées — ${namesList.join(', ')}`;
        } else {
            msg = `${total} notes enregistrées sur ${namesList.length} étudiants`;
        }
        nmShowToast('success', msg, 2400);
        _nmToastBatch = { count: 0, names: new Set(), timer: null, lastStudentId: null };
        _nmLastToastAt = now;
    }, 400);
});

// ── 6. Recherche étudiant (filtre lignes du tableau) ────────────────────
function nmFilterStudents() {
    const q = ($('#nm-student-search').val() || '').trim().toLowerCase();
    const $rows = $('#studentsRows tr[data-student-id]');
    let visible = 0;
    if (!q) {
        $rows.show();
        // Hide les lignes impact orphelines
        $('.nm-impact-row').each(function() {
            const sid = $(this).data('impact-student');
            $(this).toggle($(`tr[data-student-id="${sid}"]`).is(':visible'));
        });
        $('#nm-students-visible-pill').hide();
        return;
    }
    $rows.each(function() {
        const $r = $(this);
        const txt = $r.find('.nm-student-fullname').text().toLowerCase()
                  + ' ' + $r.find('.nm-student-matricule').text().toLowerCase();
        if (txt.indexOf(q) !== -1) {
            $r.show(); visible++;
        } else {
            $r.hide();
        }
    });
    $('.nm-impact-row').each(function() {
        const sid = $(this).data('impact-student');
        $(this).toggle($(`tr[data-student-id="${sid}"]`).is(':visible'));
    });
    $('#nm-students-visible').text(visible);
    $('#nm-students-visible-pill').show();
}
$(document).on('input', '#nm-student-search', function() {
    clearTimeout(window._nmSearchDebounce);
    window._nmSearchDebounce = setTimeout(nmFilterStudents, 80);
    $('#nm-search-clear').toggle(!!$(this).val());
});
$(document).on('click', '#nm-search-clear', function() {
    $('#nm-student-search').val('').focus();
    $(this).hide();
    nmFilterStudents();
});

// ── 7. Pagination "Load more" ───────────────────────────────────────────
function nmEnablePaginationIfNeeded(students, sortedEvaluations) {
    NM.pagination.all = students || [];
    NM.pagination.sortedEvaluations = sortedEvaluations || [];
    NM.pagination.rendered = NM.pagination.all.length;

    const wrap = document.getElementById('nm-load-more-wrap');
    if (!wrap) return;

    if (NM.pagination.all.length > 80) {
        // Hide après les 50 premiers
        const $rows = $('#studentsRows tr[data-student-id]');
        $rows.each(function(idx) {
            if (idx >= NM.pagination.pageSize) {
                $(this).addClass('nm-row-paginated').hide();
            }
        });
        NM.pagination.rendered = Math.min(NM.pagination.pageSize, NM.pagination.all.length);
        const remaining = NM.pagination.all.length - NM.pagination.rendered;
        document.getElementById('nm-load-more-count').textContent = Math.min(NM.pagination.chunk, remaining);
        wrap.style.display = '';
    } else {
        wrap.style.display = 'none';
    }
}
$(document).on('click', '#nm-load-more-btn', function() {
    const $hidden = $('#studentsRows tr.nm-row-paginated:hidden');
    let revealed = 0;
    $hidden.each(function() {
        if (revealed >= NM.pagination.chunk) return false;
        $(this).removeClass('nm-row-paginated').show();
        revealed++;
    });
    NM.pagination.rendered += revealed;

    const remainingHidden = $('#studentsRows tr.nm-row-paginated:hidden').length;
    if (remainingHidden === 0) {
        document.getElementById('nm-load-more-wrap').style.display = 'none';
    } else {
        document.getElementById('nm-load-more-count').textContent = Math.min(NM.pagination.chunk, remainingHidden);
    }
    nmFilterStudents();
});

// ── 8. Toolbar stats (étudiants + évals counters) ───────────────────────
function nmUpdateToolbarStats(students, sortedEvaluations) {
    const toolbar = document.getElementById('nm-table-toolbar');
    if (!toolbar) return;
    if (!students || students.length === 0) {
        toolbar.style.display = 'none';
        return;
    }
    toolbar.style.display = '';
    document.getElementById('nm-students-count').textContent = students.length;
    document.getElementById('nm-evaluations-count').textContent = (sortedEvaluations || []).length;
}

// ── 9. Indicateur scroll horizontal ─────────────────────────────────────
function nmUpdateScrollIndicator() {
    const wrapper = document.querySelector('.nm-grid-wrapper');
    if (!wrapper) return;
    const overflowRight = (wrapper.scrollWidth - wrapper.scrollLeft - wrapper.clientWidth) > 4;
    wrapper.classList.toggle('has-overflow-right', overflowRight);
}
$(document).on('scroll', '.nm-grid-wrapper', nmUpdateScrollIndicator);
$(window).on('resize', nmUpdateScrollIndicator);
$(document).on('click', '#nm-scroll-arrow', function() {
    const wrapper = document.querySelector('.nm-grid-wrapper');
    if (!wrapper) return;
    wrapper.scrollBy({ left: 220, behavior: 'smooth' });
});

// ── 10. Absent toggle switch + ligne stylée ─────────────────────────────
// On remplace le markup checkbox par un Bootstrap form-switch APRÈS le
// rendu de la grille (post-renderNotesGrid hook). Conserve l'id existant
// pour rester compatible avec les sélecteurs jQuery (#absent-{s}-{e}).
function nmUpgradeAbsenceToggles() {
    $('#studentsRows tr[data-student-id]').each(function() {
        const $tr = $(this);
        const sid = $tr.data('student-id');

        // Sync row class avec n'importe quel absent coché
        const anyAbsent = $tr.find('.absence-checkbox:checked').length > 0;
        $tr.toggleClass('nm-row-absent', anyAbsent);

        // Pour chaque cellule note, transformer le markup .nm-absence-check
        $tr.find('.nm-absence-check').each(function() {
            const $wrap = $(this);
            if ($wrap.hasClass('nm-absent-switch')) return;  // déjà upgradé

            const $checkbox = $wrap.find('.absence-checkbox');
            const id = $checkbox.attr('id') || '';
            const checkedAttr = $checkbox.is(':checked') ? 'checked' : '';
            const evalId = $checkbox.data('eval-id');

            // Retire la <label><i class="fas fa-user-slash"/></label>
            $wrap.empty();
            $wrap.removeClass('nm-absence-check').addClass('form-check form-switch nm-absent-switch');
            $wrap.attr('title', "Marquer comme absent — la note sera comptée 0 dans la moyenne (décocher pour saisir une note)");
            $wrap.html(`
                <input class="form-check-input absence-checkbox" type="checkbox"
                       id="${id}" data-student-id="${sid}" data-eval-id="${evalId}" ${checkedAttr}
                       onchange="toggleAbsence(${sid}, ${evalId}, this.checked)">
                <label class="form-check-label" for="${id}">Abs</label>
            `);
        });

        // Ajoute le badge "Absent" dans la cellule student (1 seul, supprimé si plus aucune absence)
        const $studentCell = $tr.find('.notes-student-col .nm-student-info');
        $studentCell.find('.nm-row-absent-badge').remove();
        if (anyAbsent) {
            const count = $tr.find('.absence-checkbox:checked').length;
            const total = $tr.find('.absence-checkbox').length;
            const label = (count === total) ? 'Absent' : `Absent ${count}/${total}`;
            $studentCell.append(`<span class="nm-row-absent-badge"><i class="fas fa-user-slash"></i>${label}</span>`);
        }
    });
}
// Refresh row absent state quand une checkbox change (en plus du toggleAbsence existant)
$(document).on('change', '.absence-checkbox', function() {
    const $tr = $(this).closest('tr');
    setTimeout(() => nmUpgradeAbsenceToggles(), 0);  // attendre que toggleAbsence ait persisté
});

// ── 11. Eval header lisible (titre + bareme + coef + edit btn) ─────────
// Post-renderNotesGrid : transforme les inputs minuscules en affichage lisible.
function nmUpgradeEvalHeaders() {
    $('th.nm-eval-header').each(function() {
        const $th = $(this);
        if ($th.find('.nm-eval-header-block').length > 0) return;  // déjà upgradé

        const $bareme = $th.find('.bareme-input');
        const $coef = $th.find('.coeff-input');
        const evalId = $bareme.data('eval-id') || $coef.data('eval-id');
        if (!evalId) return;

        const titre = $th.find('.nm-eval-title').first().text() || 'Éval';
        const baremeVal = parseFloat($bareme.val()) || 20;
        const coefVal = parseFloat($coef.val()) || 1;

        // Format bareme : 20 → "20", 12.5 → "12,5"
        const fmtNum = (n) => {
            if (n % 1 === 0) return String(n);
            return String(n).replace('.', ',');
        };

        // Garde les inputs dans le DOM (cachés via classe --legacy) pour compat JS calcul moyennes
        $th.find('.nm-eval-controls').addClass('nm-eval-controls--legacy');

        // Garde aussi le badge type + period existants (déplacés sous le block)
        const $type = $th.find('.nm-eval-type').detach();
        const $period = $th.find('.nm-eval-period').detach();

        // Vide le titre original (sera dans le block)
        $th.find('.nm-eval-title').remove();

        const block = $(`
            <div class="nm-eval-header-block" data-eval-id="${evalId}">
                <div class="nm-eval-title" title="${titre.replace(/"/g, '&quot;')}">${titre.replace(/</g, '&lt;')}</div>
                <div class="nm-eval-meta">
                    <span class="nm-eval-bareme">/ ${fmtNum(baremeVal)}</span>
                    <span class="nm-eval-meta-sep">·</span>
                    <span class="nm-eval-coef">Coef ${fmtNum(coefVal)}</span>
                </div>
                <button type="button" class="nm-eval-edit-btn" data-eval-id="${evalId}"
                        title="Modifier titre / barème / coefficient" aria-label="Modifier l'évaluation">
                    <i class="fas fa-pen"></i>
                </button>
            </div>
        `);

        $th.prepend(block);
        if ($type && $type.length) block.append($type);
        if ($period && $period.length) block.append($period);
    });
}

// ── 12. Quick edit modal ────────────────────────────────────────────────
$(document).on('click', '.nm-eval-edit-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const evalId = parseInt($(this).data('eval-id'));
    if (!evalId) return;
    nmOpenQuickEditModal(evalId);
});
function nmOpenQuickEditModal(evalId) {
    NM.quickEditCurrentId = evalId;
    const evalData = evaluationsData[evalId] || null;
    const $bareme = $(`.bareme-input[data-eval-id="${evalId}"]`);
    const $coef = $(`.coeff-input[data-eval-id="${evalId}"]`);
    const titreNow = $(`.nm-eval-header-block[data-eval-id="${evalId}"] .nm-eval-title`).attr('title')
                   || (evalData && evalData.titre)
                   || '';

    $('#nm-eval-quick-id').val(evalId);
    $('#nm-eval-quick-titre').val(titreNow);
    $('#nm-eval-quick-bareme').val($bareme.val() || (evalData && evalData.bareme) || 20);
    $('#nm-eval-quick-coefficient').val($coef.val() || (evalData && evalData.coefficient) || 1);
    $('#nm-eval-quick-error').hide().text('');

    const modalEl = document.getElementById('evaluationQuickEditModal');
    if (window.bootstrap && window.bootstrap.Modal) {
        new window.bootstrap.Modal(modalEl).show();
    } else {
        $(modalEl).modal('show');
    }
}
$(document).on('click', '#nm-eval-quick-save', function() {
    const evalId = parseInt($('#nm-eval-quick-id').val());
    if (!evalId) return;

    const titre = ($('#nm-eval-quick-titre').val() || '').trim();
    const bareme = parseFloat($('#nm-eval-quick-bareme').val());
    const coefficient = parseFloat($('#nm-eval-quick-coefficient').val());

    // Client-side validation
    const errs = [];
    if (!titre) errs.push('Le titre est obligatoire.');
    if (isNaN(bareme) || bareme < 1 || bareme > 100) errs.push('Le barème doit être entre 1 et 100.');
    if (isNaN(coefficient) || coefficient < 0.1 || coefficient > 10) errs.push('Le coefficient doit être entre 0,1 et 10.');
    if (errs.length > 0) {
        $('#nm-eval-quick-error').text(errs.join(' ')).show();
        return;
    }

    const $btn = $(this);
    const $spinner = $('#nm-eval-quick-spinner');
    const $icon = $('#nm-eval-quick-icon');
    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');
    $icon.addClass('d-none');

    $.ajax({
        url: NM.routes.evalQuickUpdate + '/' + evalId + '/quick-update',
        method: 'PATCH',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': NM.csrf,
        },
        data: { titre, bareme, coefficient, _token: NM.csrf },
    })
        .done(function(resp) {
            if (resp && resp.success && resp.evaluation) {
                // Update DOM header
                const ev = resp.evaluation;
                $(`.bareme-input[data-eval-id="${evalId}"]`).val(ev.bareme);
                $(`.coeff-input[data-eval-id="${evalId}"]`).val(ev.coefficient);
                evalParamsCache[evalId] = { bareme: parseFloat(ev.bareme) || 20, coefficient: parseFloat(ev.coefficient) || 1 };
                if (evaluationsData[evalId]) {
                    evaluationsData[evalId].titre = ev.titre;
                    evaluationsData[evalId].bareme = ev.bareme;
                    evaluationsData[evalId].coefficient = ev.coefficient;
                }
                // Refresh visual block
                const $block = $(`.nm-eval-header-block[data-eval-id="${evalId}"]`);
                $block.find('.nm-eval-title').text(ev.titre).attr('title', ev.titre);
                const fmt = (n) => (n % 1 === 0 ? String(n) : String(n).replace('.', ','));
                $block.find('.nm-eval-bareme').text('/ ' + fmt(parseFloat(ev.bareme)));
                $block.find('.nm-eval-coef').text('Coef ' + fmt(parseFloat(ev.coefficient)));

                calculateAllAverages();

                const modalEl = document.getElementById('evaluationQuickEditModal');
                const inst = window.bootstrap ? window.bootstrap.Modal.getInstance(modalEl) : null;
                if (inst) inst.hide(); else $(modalEl).modal('hide');
                nmShowToast('success', `Évaluation « ${ev.titre} » mise à jour.`);
            } else {
                $('#nm-eval-quick-error').text((resp && resp.message) || 'Erreur lors de la mise à jour.').show();
            }
        })
        .fail(function(xhr) {
            let msg = 'Erreur lors de la mise à jour.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
            }
            $('#nm-eval-quick-error').text(msg).show();
        })
        .always(function() {
            $btn.prop('disabled', false);
            $spinner.addClass('d-none');
            $icon.removeClass('d-none');
        });
});

// ── 13. Save & Continue (modal création éval) ──────────────────────────
// Le bouton "Créer et continuer" déclenche le submit du formulaire en
// signalant via window.nmEvalSaveContinueRequested. La fonction
// closeEvaluationModal() (modifiée plus haut) lit ce flag pour décider
// si elle ferme le modal ou si elle réinitialise le formulaire pour la
// prochaine évaluation.
window.nmEvalSaveContinueRequested = false;
$(document).on('click', '#evalModal_save_continue', function() {
    window.nmEvalSaveContinueRequested = true;
    const form = document.getElementById('evaluationCreateForm');
    if (form) form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
});

// ── 14. Raccourcis clavier (Tab/Enter/Ctrl+S/Échap) ────────────────────
function nmGetGridInputs() {
    return $('#studentsRows .note-input:not(:disabled):visible').toArray();
}
function nmGetGridGeometry() {
    // Construire une grille [row][col] des inputs visibles
    const rows = [];
    $('#studentsRows tr[data-student-id]:visible').each(function() {
        const cols = [];
        $(this).find('.note-input').each(function() {
            cols.push(this);
        });
        if (cols.length) rows.push(cols);
    });
    return rows;
}
function nmFindCellPosition(rows, target) {
    for (let r = 0; r < rows.length; r++) {
        for (let c = 0; c < rows[r].length; c++) {
            if (rows[r][c] === target) return { r, c };
        }
    }
    return null;
}
function nmFocusCell(input) {
    if (!input) return;
    input.focus();
    if (typeof input.select === 'function') input.select();
}
$(document).on('keydown', '.note-input', function(e) {
    const target = this;

    // Échap : si modifs non sauvées, demander confirmation, sinon defocus
    if (e.key === 'Escape') {
        e.preventDefault();
        target.blur();
        return;
    }
    // Enter / Shift+Enter : descend / monte
    if (e.key === 'Enter') {
        e.preventDefault();
        const rows = nmGetGridGeometry();
        const pos = nmFindCellPosition(rows, target);
        if (!pos) return;
        const dir = e.shiftKey ? -1 : 1;
        const nextR = pos.r + dir;
        if (nextR >= 0 && nextR < rows.length) {
            const next = rows[nextR][pos.c] || rows[nextR][rows[nextR].length - 1];
            nmFocusCell(next);
        }
        return;
    }
    // Tab : on laisse le navigateur gérer (HTML order = colonne suivante).
    // Mais on intercepte le wrap pour passer à la ligne suivante première colonne.
    if (e.key === 'Tab') {
        const rows = nmGetGridGeometry();
        const pos = nmFindCellPosition(rows, target);
        if (!pos) return;
        if (e.shiftKey) {
            if (pos.c === 0 && pos.r > 0) {
                e.preventDefault();
                const prevRow = rows[pos.r - 1];
                nmFocusCell(prevRow[prevRow.length - 1]);
            }
        } else {
            if (pos.c === rows[pos.r].length - 1 && pos.r < rows.length - 1) {
                e.preventDefault();
                const nextRow = rows[pos.r + 1];
                nmFocusCell(nextRow[0]);
            }
        }
    }
});
// Ctrl+S / Cmd+S = sauvegarder tout
$(document).on('keydown', function(e) {
    const isCtrlS = (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S');
    if (isCtrlS && $('#classSelectionModal').is(':visible')) {
        e.preventDefault();
        const $btn = $('#saveAllNotesBtn');
        if ($btn.length && $btn.is(':visible') && !$btn.prop('disabled')) {
            $btn.trigger('click');
        }
    }
    // Ctrl+F : focus la barre de recherche (si modal ouvert)
    const isCtrlF = (e.ctrlKey || e.metaKey) && (e.key === 'f' || e.key === 'F');
    if (isCtrlF && $('#classSelectionModal').is(':visible') && $('#nm-table-toolbar').is(':visible')) {
        e.preventDefault();
        $('#nm-student-search').focus().select();
    }
});

// ── 15. Tooltip raccourcis (1ère fois) ─────────────────────────────────
function nmShowKeyboardHintIfNeeded() {
    let seen = false;
    try { seen = localStorage.getItem('nm_keyboard_hint_seen') === '1'; } catch (e) {}
    if (seen) return;
    const inputs = nmGetGridInputs();
    if (inputs.length === 0) return;
    const target = inputs[0];

    const hint = document.createElement('div');
    hint.className = 'nm-kbd-hint';
    hint.innerHTML = `
        <strong>Astuce&nbsp;:</strong>
        <kbd>Tab</kbd> / <kbd>Shift+Tab</kbd>, <kbd>Enter</kbd> pour la cellule du bas, <kbd>Ctrl+S</kbd> pour tout sauvegarder.
        <button type="button" class="nm-kbd-hint-close" aria-label="Fermer">&times;</button>
    `;
    document.body.appendChild(hint);

    const rect = target.getBoundingClientRect();
    hint.style.top = (rect.bottom + window.scrollY + 8) + 'px';
    hint.style.left = (rect.left + window.scrollX - 14) + 'px';

    const dismiss = () => {
        if (hint.parentNode) hint.parentNode.removeChild(hint);
        try { localStorage.setItem('nm_keyboard_hint_seen', '1'); } catch (e) {}
        document.removeEventListener('keydown', onTab, true);
    };
    const onTab = (e) => { if (e.key === 'Tab') dismiss(); };
    hint.querySelector('.nm-kbd-hint-close').addEventListener('click', dismiss);
    document.addEventListener('keydown', onTab, true);
    setTimeout(dismiss, 6500);
}

// ── 16. Hook nm:grid-rendered → upgrades visuels post-render ──────────
// Le original `renderNotesGrid()` dispatch un CustomEvent en fin d'exécution
// (cf. l'édition de la fonction principale plus haut dans ce fichier).
window.addEventListener('nm:grid-rendered', function(e) {
    const students = (e.detail && e.detail.students) || [];
    const sortedEvaluations = (e.detail && e.detail.evaluations) || [];

    NM.studentsCache = students;
    nmUpdateToolbarStats(students, sortedEvaluations);
    nmUpgradeAbsenceToggles();
    nmUpgradeEvalHeaders();
    nmEnablePaginationIfNeeded(students, sortedEvaluations);
    nmFilterStudents();
    nmCheckDraftBanner();

    setTimeout(() => {
        nmUpdateScrollIndicator();
        const inputs = nmGetGridInputs();
        if (inputs.length > 0) {
            // Ne pas focus si l'utilisateur a déjà tapé qqch (ex: cherche dans la barre)
            if (document.activeElement === document.body) {
                nmFocusCell(inputs[0]);
            }
            nmShowKeyboardHintIfNeeded();
        }
    }, 60);
});

// ── 17. beforeunload + confirm fermeture ────────────────────────────────
window.addEventListener('beforeunload', function(e) {
    if (window.nmHasUnsavedChanges && NM.pendingSaves > 0) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});
$('#classSelectionModal').on('hide.bs.modal', function(e) {
    if (window.nmHasUnsavedChanges && NM.pendingSaves > 0) {
        e.preventDefault();
        if (typeof window.iiConfirm === 'function') {
            window.iiConfirm({
                title: 'Modifications non enregistrées',
                message: 'Des notes sont en cours de sauvegarde. Fermer maintenant pourrait les perdre.',
                confirmLabel: 'Fermer quand même',
                cancelLabel: 'Annuler',
                danger: true,
            }).then((ok) => {
                if (ok) {
                    window.nmHasUnsavedChanges = false;
                    $('#classSelectionModal').modal('hide');
                }
            });
        } else if (confirm('Des notes sont en cours de sauvegarde. Fermer quand même ?')) {
            window.nmHasUnsavedChanges = false;
            $('#classSelectionModal').modal('hide');
        }
    }
});

// ── 18. Init au shown.bs.modal ──────────────────────────────────────────
$(document).ready(function() {
    nmPurgeOldDrafts();
});

$('#classSelectionModal').on('shown.bs.modal', function() {
    nmCheckDraftBanner();
    nmSetNetworkState(navigator.onLine === false ? 'offline' : 'synced');
    setTimeout(nmUpdateScrollIndicator, 100);
});

// Quand on change matière OU période, reload de toolbar + draft check
$(document).on('change', '#matiereSelect, #periodeFilter', function() {
    setTimeout(nmCheckDraftBanner, 250);
});

</script>
@endpush
