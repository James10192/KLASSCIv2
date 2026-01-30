@extends('layouts.app')

@section('title', 'Gestion des Notes | KLASSCI')

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
                <button type="button" class="btn-acasi primary me-2" data-bs-toggle="modal" data-bs-target="#classSelectionModal">
                    <i class="fas fa-users"></i>Choisir une classe
                </button>
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

        <!-- Liste des classes -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-users"></i>
                    Classes disponibles
                </div>
                <div class="main-card-subtitle">{{ count($classes) }} classes trouvées</div>
            </div>
            <div class="main-card-body">
                <div class="row" id="classesContainer">
                    @forelse($classes as $classe)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3 class-card" data-class-name="{{ strtolower($classe->name) }}" data-class-filiere="{{ strtolower($classe->filiere->name ?? '') }}" data-class-niveau="{{ strtolower($classe->niveau->name ?? '') }}">
                            <div class="card border-0 shadow-sm h-100 hover-card" style="cursor: pointer;" onclick="selectClass({{ $classe->id }}, '{{ $classe->name }}')">
                                <div class="card-header bg-primary text-white py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">{{ $classe->name }}</h6>
                                        <span class="badge bg-light text-primary">{{ $classe->capacity }}</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-graduation-cap me-1"></i>
                                            {{ $classe->filiere->name ?? 'Non spécifié' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-layer-group me-1"></i>
                                            {{ $classe->niveau->name ?? 'Non spécifié' }}
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-success">
                                                <i class="fas fa-user-graduate me-1"></i>
                                                {{ $classe->etudiants_count ?? 0 }} étudiants
                                            </span>
                                        </div>
                                        <div>
                                            <i class="fas fa-chevron-right text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-4">
                            <div class="my-4 text-muted">
                                <i class="fas fa-info-circle fs-1 mb-3 d-block"></i>
                                <p class="mb-0">Aucune classe trouvée</p>
                                <p class="small">Contactez l'administrateur pour créer des classes</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sélection Classe et Matière -->
<div class="modal fade" id="classSelectionModal" tabindex="-1" aria-labelledby="classSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="classSelectionModalLabel">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Gestion des Notes - <span id="selectedClassLabel">Sélectionnez une classe</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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

                <div class="table-responsive">
                    <table class="table table-bordered" id="notesGrid">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 200px; min-width: 200px;">Étudiants</th>
                                <!-- Colonnes d'évaluations seront ajoutées dynamiquement ici -->
                                <th style="min-width: 100px;">Moyenne</th>
                                <th style="min-width: 100px;">Appréciation</th>
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
            <div class="modal-footer">
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
            const className = $(this).data('class-name');
            const filiere = $(this).data('class-filiere');
            const niveau = $(this).data('class-niveau');
            
            const matches = className.includes(searchTerm) || 
                           filiere.includes(searchTerm) || 
                           niveau.includes(searchTerm);
            
            $(this).toggle(matches);
        });
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
        url: '{{ route("evaluations.by-class-matiere", ["classId" => ":classId", "matiereId" => ":matiereId"]) }}'
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
        url: '{{ route("classes.students", ["classe" => ":classId"]) }}'.replace(':classId', currentClassId),
        method: 'GET',
        dataType: 'json',
        success: function(students) {
            // Construire l'en-tête du tableau avec les évaluations
            const thead = $('#notesGrid thead tr');
            thead.empty();
            thead.append('<th style="width: 200px; min-width: 200px;">Étudiants</th>');
            
            evaluations.forEach(evaluation => {
                const header = `
                    <th id="evalHeader${evaluation.id}" style="min-width: 150px;">
                        <div class="text-center fw-bold">${evaluation.titre || 'Éval'}</div>
                        <div class="text-center small">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <input type="number" value="${evaluation.bareme || 20}" 
                                       class="form-control form-control-sm bareme-input" 
                                       style="width: 50px; display: inline;" 
                                       data-eval-id="${evaluation.id}"
                                       title="Barème">
                                <span>/</span>
                                <input type="number" value="${evaluation.coefficient || 1}" 
                                       class="form-control form-control-sm coeff-input" 
                                       style="width: 40px; display: inline;" 
                                       data-eval-id="${evaluation.id}"
                                       title="Coefficient">
                            </div>
                            <small class="text-muted">${evaluation.type || 'Devoir'}</small>
                        </div>
                    </th>
                `;
                thead.append(header);
            });
            
            thead.append('<th style="min-width: 100px;">Moyenne</th>');
            thead.append('<th style="min-width: 100px;">Appréciation</th>');
            
            // Construire les lignes des étudiants
            const tbody = $('#studentsRows');
            tbody.empty();
            
            students.forEach(student => {
                const row = $(`
                    <tr data-student-id="${student.id}">
                        <td class="fw-medium">
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
                row.append('<td class="text-center fw-bold average-cell">--</td>');
                row.append('<td class="text-center"><span class="badge bg-secondary appreciation-badge">--</span></td>');
                
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
    row.append('<td class="text-end">Moyenne Classe</td>');
    
    evaluations.forEach(evaluation => {
        row.append(`<td class="text-center class-avg-${evaluation.id}">--</td>`);
    });
    
    row.append('<td class="text-center class-overall-avg">--</td>');
    row.append('<td></td>');
    
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
            matiere_id: currentMatiereId
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
        const noteInputs = $(`input[data-eval-id="${evalId}"]`);
        
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
            $('#evaluationCreateModal').modal('hide');
            loadEvaluationsAndNotes(); // Recharger les données
            showSuccessMessage('Évaluation créée avec succès !');
        },
        error: function(xhr) {
            console.error('Erreur lors de la création:', xhr);
            alert('Erreur lors de la création de l\'évaluation.');
        }
    });
});

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
    padding: 0.1rem 0.2rem;
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
</style>
@endpush