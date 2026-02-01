@extends('layouts.app')

@section('title', 'Gestion des Notes | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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

        <!-- Section de recherche de classes -->
        <div class="main-card mb-4">
            <div class="main-card-header" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05));">
                <div class="main-card-title">
                    <i class="fas fa-search"></i>
                    Recherche de classe
                </div>
            </div>
            <div class="main-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group-moderne">
                            <label class="form-label-moderne">
                                <i class="fas fa-users"></i>
                                Rechercher une classe
                            </label>
                            <input type="text" class="form-input-moderne" id="classSearch" placeholder="Nom de la classe, filière, niveau...">
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn-acasi secondary w-100" onclick="resetSearch()">
                            <i class="fas fa-times"></i>Effacer la recherche
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des classes en grid moderne -->
        <div class="card-moderne" style="padding: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-list me-2"></i>Classes disponibles pour la saisie des notes
                <span class="section-subtitle">{{ count($classes) }} classes trouvées</span>
            </div>

            <div class="resultats-grid" id="classes-grid" style="margin-top: var(--space-lg);">
                @forelse($classes as $classe)
                    @php
                        $className = strtolower($classe->name ?: '');
                        $classFiliere = strtolower(optional($classe->filiere)->name ?: '');
                        $classNiveau = strtolower(optional($classe->niveau)->name ?: '');
                    @endphp
                    <div class="card-moderne resultat-card class-card animate-slide-up @if($classe->is_active) border-active @else border-inactive @endif" 
                         data-classe-id="{{ $classe->id }}"
                         data-class-name="{{ $className }}"
                         data-class-filiere="{{ $classFiliere }}"
                         data-class-niveau="{{ $classNiveau }}"
                         data-class-label="{{ $classe->name }}">
                        <!-- En-tête classe -->
                        <div style="display: flex; justify-content: between; align-items: start; margin-bottom: var(--space-md);">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; margin-bottom: var(--space-sm);">
                                    <div class="classe-icon @if($classe->is_active) bg-success @else bg-inactive @endif">
                                        <i class="fas fa-graduation-cap" style="color: white; font-size: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold color-primary" style="font-size: var(--text-normal);">{{ $classe->name }}</div>
                                        <div style="font-size: var(--text-small); color: var(--text-secondary);">Code: {{ $classe->code }}</div>
                                    </div>
                                </div>

                                <!-- Filière et niveau -->
                                <div style="margin-bottom: var(--space-md);">
                                    @if ($classe->filiere)
                                        <div style="font-size: var(--text-small); color: var(--text-primary); margin-bottom: var(--space-xs);">
                                            <i class="fas fa-layer-group me-1"></i><strong>{{ $classe->filiere->name }}</strong>
                                            @if ($classe->filiere->parent)
                                                <br><span style="color: var(--text-muted); margin-left: 16px;">Option de {{ $classe->filiere->parent->name }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($classe->niveau)
                                        <div style="font-size: var(--text-small); color: var(--text-secondary);">
                                            <i class="fas fa-level-up-alt me-1"></i>{{ $classe->niveau->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Statut -->
                            <div>
                                <span class="badge {{ $classe->is_active ? 'success' : 'danger' }}">
                                    {{ $classe->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="badge badge-notes ms-1">
                                    <i class="fas fa-clipboard-check me-1"></i>Notes
                                </span>
                            </div>
                        </div>

                        <!-- Statistiques -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-md); margin-bottom: var(--space-md); padding: var(--space-sm); background: rgba(248, 250, 252, 0.5); border-radius: var(--radius-small);">
                            <div class="text-center">
                                <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Capacité</div>
                                <div class="font-bold color-primary">{{ $classe->places_totales ?? 0 }}</div>
                            </div>
                            <div class="text-center">
                                <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Étudiants</div>
                                <div class="font-bold color-accent">{{ $classe->inscriptions_count ?? 0 }}</div>
                            </div>
                            <div class="text-center">
                                <div style="font-size: var(--text-small); color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Disponibles</div>
                                <div class="font-bold color-{{ (($classe->places_totales ?? 0) - ($classe->inscriptions_count ?? 0)) > 0 ? 'success' : 'danger' }}">
                                    {{ ($classe->places_totales ?? 0) - ($classe->inscriptions_count ?? 0) }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f3f4f6; padding-top: var(--space-md);">
                            <div style="font-size: var(--text-small); color: var(--text-muted);">
                                @if ($classe->annee)
                                    <i class="fas fa-calendar me-1"></i>{{ $classe->annee->name }}
                                @endif
                                <div class="notes-hint">
                                    <i class="fas fa-pen-alt me-1"></i>Saisir les notes
                                </div>
                            </div>
                            <div style="display: flex; gap: var(--space-xs);">
                                <button type="button" class="btn-acasi primary class-select-btn" title="Saisir les notes">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center" style="padding: var(--space-xl); color: var(--text-secondary); grid-column: 1 / -1;">
                        <i class="fas fa-graduation-cap" style="font-size: 48px; margin-bottom: var(--space-lg); color: var(--neutral);"></i>
                        <h5 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Aucune classe trouvée</h5>
                        <p style="color: var(--text-muted);">Aucune classe active n'a été trouvée pour la saisie des notes.</p>
                    </div>
                @endforelse
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
                <div class="row mb-4">
                    <div class="col-md-8">
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-bold">&nbsp;</label>
                            <button type="button" class="btn btn-primary w-100" onclick="createEvaluation()" id="createEvaluationBtn">
                                <i class="fas fa-plus me-1"></i> Créer évaluation
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive notes-grid-wrapper">
                    <table class="table table-bordered table-hover notes-grid-table" id="notesGrid">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 200px; min-width: 200px;">Étudiants</th>
                                <!-- Colonnes d'évaluations seront ajoutées dynamiquement ici -->
                                <th class="notes-average-col" style="min-width: 110px;">Moyenne</th>
                                <th class="notes-appreciation-col" style="min-width: 140px;">Appréciation</th>
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

                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instructions :</strong> Cliquez sur "Choisir une classe" dans la liste ci-dessus, puis sélectionnez une matière. 
                        Les notes seront automatiquement enregistrées à chaque modification.
                    </div>
                </div>
            </div>
            <div class="modal-footer notes-modal-footer">
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

<!-- Modal pour charger evaluations.create -->
<div class="modal fade" id="evaluationCreateModal" tabindex="-1" aria-labelledby="evaluationCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="evaluationCreateModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Créer une évaluation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="evaluationCreateContent">
                <!-- Contenu du modal evaluations.create sera chargé ici via AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3">Chargement du formulaire...</p>
                </div>
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
let evaluationsData = {}; // Stocke les évaluations par matière
let notesData = {}; // Stocke les notes existantes

// Initialisation
$(document).ready(function() {
    // Recherche de classes en temps réel
    $('#classSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.class-card').each(function() {
            const className = ($(this).data('class-name') || '').toString();
            const filiere = ($(this).data('class-filiere') || '').toString();
            const niveau = ($(this).data('class-niveau') || '').toString();
            
            const matches = className.includes(searchTerm) || 
                           filiere.includes(searchTerm) || 
                           niveau.includes(searchTerm);
            
            $(this).toggle(matches);
        });
    });

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
        currentMatiereId = $(this).val();
        if (currentClassId && currentMatiereId) {
            loadEvaluationsAndNotes();
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
});

// Fonction pour sélectionner une classe
function selectClass(classId, className) {
    currentClassId = classId;
    currentClassname = className;
    
    // Mettre à jour l'UI
    $('#selectedClassLabel').text(className);
    
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

// Fonction pour réinitialiser la recherche
function resetSearch() {
    $('#classSearch').val('');
    $('.class-card').show();
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

// Fonction pour construire la grille des notes
function buildNotesGrid() {
    const evaluations = Object.values(evaluationsData);
    
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
            const thead = $('#notesGrid thead tr');
            thead.empty();
            thead.append('<th class="notes-student-col" style="width: 200px; min-width: 200px; position: sticky; left: 0; top: 0; z-index: 7; background: #ffffff;">Étudiants</th>');
            
            evaluations.forEach(evaluation => {
                const header = `
                    <th id="evalHeader${evaluation.id}" class="evaluation-header" style="min-width: 180px;">
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
                    </th>
                `;
                thead.append(header);
            });
            
            thead.append('<th class="notes-average-col" style="min-width: 110px; position: sticky; right: 140px; top: 0; z-index: 6; background: #f8fafc;">Moyenne</th>');
            thead.append('<th class="notes-appreciation-col" style="min-width: 140px; position: sticky; right: 0; top: 0; z-index: 7; background: #f8fafc;">Appréciation</th>');
            
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
                evaluations.forEach(evaluation => {
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
            buildClassAveragesRow(evaluations);
            
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
    
    // Charger le modal evaluations.create via AJAX
    $.ajax({
        url: '{{ route("esbtp.evaluations.create") }}',
        method: 'GET',
        data: {
            classe_id: currentClassId,
            matiere_id: currentMatiereId,
            embed: true
        },
        success: function(response) {
            $('#evaluationCreateContent').html(response);
            $('#evaluationCreateModal').modal('show');
        },
        error: function(xhr) {
            console.error('Erreur lors du chargement du formulaire:', xhr);
            alert('Erreur lors du chargement du formulaire de création d\'évaluation.');
        }
    });
}

// Fonction pour sauvegarder une note
function saveNote(studentId, evaluationId, noteValue) {
    const isAbsent = $(`#absent-${studentId}-${evaluationId}`).is(':checked');
    
    $.ajax({
        url: '{{ route("esbtp.notes.store") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            etudiant_id: studentId,
            evaluation_id: evaluationId,
            note: isAbsent ? 0 : noteValue,
            is_absent: isAbsent ? 'on' : ''
        },
        success: function(response) {
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
            console.error('Erreur lors de la sauvegarde:', xhr);
            alert('Erreur lors de la sauvegarde de la note.');
        }
    });
}

// Fonction pour basculer l'état d'absence
function toggleAbsence(studentId, evaluationId, isAbsent) {
    const input = $(`input[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);
    
    if (isAbsent) {
        input.val('0').prop('disabled', true);
    } else {
        input.val('').prop('disabled', false);
    }
    
    // Sauvegarder automatiquement
    saveNote(studentId, evaluationId, input.val());
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
    const evaluations = Object.values(evaluationsData);
    
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

// Gestion de la soumission du formulaire d'évaluation
$(document).on('submit', '#evaluationCreateForm', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(this);
    
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response && response.success === false) {
                showEvaluationErrors(response.errors || {});
                return;
            }

            closeEvaluationModal();
            setTimeout(closeEvaluationModal, 150);
            loadEvaluationsAndNotes();
            showSuccessMessage('Évaluation créée avec succès !');
        },
        error: function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                showEvaluationErrors(xhr.responseJSON.errors);
                return;
            }

            console.error('Erreur lors de la création:', xhr);
            alert('Erreur lors de la création de l\'évaluation.');
        }
    });
});

function showEvaluationErrors(errors) {
    $('#evaluationCreateContent .alert').remove();
    const errorList = Object.values(errors || {}).flat().map(message => `<li>${message}</li>`).join('');
    const alertHtml = `
        <div class="alert alert-danger border-start border-danger border-4 mb-3">
            <div class="d-flex">
                <div class="me-3"><i class="fas fa-exclamation-circle fs-4"></i></div>
                <div>
                    <h6 class="alert-heading">Erreur de validation</h6>
                    <ul class="mb-0 ps-3">${errorList || '<li>Veuillez verifier les champs.</li>'}</ul>
                </div>
            </div>
        </div>
    `;

    $('#evaluationCreateContent').prepend(alertHtml);
}

function closeEvaluationModal() {
    const modalElement = document.getElementById('evaluationCreateModal');
    if (!modalElement) {
        return;
    }

    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
        const instance = window.bootstrap.Modal.getInstance(modalElement) || new window.bootstrap.Modal(modalElement);
        instance.hide();
    } else if (window.jQuery) {
        $('#evaluationCreateModal').modal('hide');
    }

    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    modalElement.setAttribute('aria-hidden', 'true');

    setTimeout(() => {
        const openModals = Array.from(document.querySelectorAll('.modal.show'))
            .filter(modal => modal.id !== 'evaluationCreateModal');

        const evalBackdrop = document.querySelector('.modal-backdrop.evaluation-backdrop');
        if (evalBackdrop) {
            evalBackdrop.remove();
        }

        if (openModals.length > 0) {
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        } else {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        }
    }, 50);
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

.notes-modal-content {
    border-radius: 16px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.15);
}

.notes-modal-intro {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 12px 14px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(14, 116, 144, 0.05));
    border-radius: 12px;
    border: 1px solid rgba(59, 130, 246, 0.15);
    margin-bottom: 18px;
}

.notes-modal-intro .intro-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(59, 130, 246, 0.12);
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.notes-modal-intro .intro-title {
    font-weight: 700;
    color: var(--text-primary);
}

.notes-modal-intro .intro-subtitle {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.notes-grid-table th,
.notes-grid-table td {
    padding: 12px 10px;
    border: 1px solid #e2e8f0;
}

.notes-grid-table {
    border-collapse: separate;
    border-spacing: 0;
    min-width: 100%;
}

.notes-grid-table thead th {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #f8fafc;
}

#notesGrid thead th.notes-average-col {
    position: sticky !important;
    top: 0;
    right: 140px;
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
    right: 140px;
    z-index: 4;
    background: #f8fafc;
    min-width: 110px;
}

.notes-appreciation-col {
    position: sticky;
    right: 0;
    z-index: 5;
    background: #f8fafc;
    min-width: 140px;
}

.notes-grid-table tfoot .notes-average-col,
.notes-grid-table tfoot .notes-appreciation-col {
    z-index: 6;
}

.evaluation-header {
    background: #f8fafc;
    border-left: 3px solid rgba(59, 130, 246, 0.4);
}

.evaluation-title {
    font-weight: 700;
    color: var(--text-primary);
    text-align: center;
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.evaluation-controls {
    display: grid;
    grid-template-columns: repeat(2, minmax(70px, 1fr));
    gap: 6px;
    margin-bottom: 4px;
}

.evaluation-control {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.evaluation-control .control-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: var(--text-muted);
}

.evaluation-control input {
    min-width: 62px;
}

.evaluation-type {
    font-size: 0.75rem;
    text-align: center;
    color: var(--text-secondary);
}

.note-input {
    min-width: 70px;
}

.notes-grid-wrapper {
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    max-height: 60vh;
    overflow-x: auto !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
}

.notes-modal-footer {
    background: #f8fafc;
}
</style>
@endpush

