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
                <h5 class="modal-title" id="classSelectionModalLabel">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Gestion des Notes — <span id="selectedClassLabel">Sélectionnez une classe</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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

                {{-- Notes grid --}}
                <div class="nm-grid-wrapper table-responsive notes-grid-wrapper">
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

                {{-- Auto-save info --}}
                <div class="nm-autosave-info">
                    <i class="fas fa-info-circle"></i>
                    Les notes sont automatiquement enregistrées à chaque modification.
                </div>
            </div>
            <div class="modal-footer nm-modal-footer">
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
                <button type="button" id="evalModal_submit" class="btn nm-eval-submit-btn">
                    <span id="evalModal_submitSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                    <i class="fas fa-save me-2" id="evalModal_submitIcon"></i>Créer l'évaluation
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
                               step="0.25" min="0" max="${evaluation.bareme || 20}"
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
            triggerRowHighlight(studentId);

            if (!notesData[studentId]) notesData[studentId] = {};
            notesData[studentId][evaluationId] = isAbsent ? 0 : noteValue;
            notesData[studentId][evaluationId + '_absent'] = isAbsent;

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
</script>
@endpush
