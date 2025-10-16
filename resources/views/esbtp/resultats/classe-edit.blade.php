@extends('layouts.app')

@section('title', 'Édition Groupée - ' . $classe->name . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .student-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .btn-action {
        min-width: 140px;
        padding: 0.65rem 1.25rem !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    .loading-overlay.active {
        display: flex;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Édition Groupée des Résultats</h1>
                <p class="header-subtitle">{{ $classe->name }} - {{ $classe->filiere->name ?? '' }} - {{ $classe->niveau->name ?? '' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.resultats.classes') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux classes
                </a>
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

        <!-- Filtres -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres d'affichage
                </div>
                <div class="main-card-subtitle">Sélectionnez la période et l'année académique</div>
            </div>
            <div class="main-card-body">
                <form method="GET" action="{{ route('esbtp.resultats.classe.edit', $classe->id) }}" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="annee_universitaire_id" class="form-label text-muted text-uppercase" style="font-size: 12px; font-weight: 600;">Année Académique</label>
                            <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-select">
                                @foreach($annees_universitaires as $annee)
                                    <option value="{{ $annee->id }}" {{ $annee->id == $annee_universitaire_id ? 'selected' : '' }}>
                                        {{ $annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin) }}
                                        @if($annee->is_current) (Année courante) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="semestre" class="form-label text-muted text-uppercase" style="font-size: 12px; font-weight: 600;">Semestre</label>
                            <select name="semestre" id="semestre" class="form-select">
                                <option value="">Toutes les périodes</option>
                                <option value="1" {{ $semestre == 1 ? 'selected' : '' }}>Premier Semestre</option>
                                <option value="2" {{ $semestre == 2 ? 'selected' : '' }}>Deuxième Semestre</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check" style="margin-top: 32px;">
                                <input class="form-check-input" type="checkbox" name="include_all_statuses" id="include_all_statuses" {{ $include_all_statuses ? 'checked' : '' }}>
                                <label class="form-check-label" for="include_all_statuses">
                                    Inclure tous les statuts
                                </label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn-acasi primary w-100">
                                <i class="fas fa-sync-alt"></i>Actualiser
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- KPIs -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiants</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $kpis['total_students'] }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user-graduate"></i>
                    Inscrits actifs
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $kpis['total_matieres'] }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-book"></i>
                    Configurées
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Moyennes saisies</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $kpis['total_resultats'] }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-chart-line"></i>
                    Résultats enregistrés
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Taux de complétion</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $kpis['completion_rate'] }}%</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-chart-pie"></i>
                    Moyennes / Total
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-bolt"></i>
                    Actions d'édition groupée
                </div>
                <div class="main-card-subtitle">
                    <span id="selected-count">0</span> étudiant(s) sélectionné(s)
                </div>
            </div>
            <div class="main-card-body">
                <div class="d-flex gap-3 flex-wrap">
                    <button class="btn-acasi primary btn-action" onclick="openMoyennesModal()" id="btnMoyennes">
                        <i class="fas fa-calculator"></i>Éditer Moyennes
                    </button>
                    <button class="btn-acasi info btn-action" onclick="openProfesseursModal()" id="btnProfesseurs">
                        <i class="fas fa-chalkboard-teacher"></i>Assigner Professeurs
                    </button>
                    <button class="btn-acasi warning btn-action" onclick="openAbsencesModal()" id="btnAbsences">
                        <i class="fas fa-calendar-times"></i>Éditer Absences
                    </button>
                    <button class="btn-acasi secondary btn-action" onclick="openMatieresModal()" id="btnMatieres">
                        <i class="fas fa-cog"></i>Config. Matières
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Sélectionnez un ou plusieurs étudiants ci-dessous pour activer les actions d'édition groupée
                    </small>
                </div>
            </div>
        </div>

        <!-- Liste des étudiants -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-users"></i>
                    Liste des étudiants
                </div>
                <div class="main-card-subtitle">{{ $students->count() }} étudiant(s)</div>
            </div>
            <div class="main-card-body" style="position: relative;">
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>

                @if($students->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" class="student-checkbox" id="selectAll">
                                    </th>
                                    <th>Matricule</th>
                                    <th>Nom complet</th>
                                    <th>Genre</th>
                                    <th class="text-center">Moyennes</th>
                                    <th class="text-center">Absences</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="student-checkbox student-select"
                                                   value="{{ $student->id }}"
                                                   data-name="{{ $student->nom }} {{ $student->prenoms }}"
                                                   data-matricule="{{ $student->matricule }}">
                                        </td>
                                        <td>{{ $student->matricule }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $student->nom }} {{ $student->prenoms }}</div>
                                            <small class="text-muted">{{ $student->user->email ?? '' }}</small>
                                        </td>
                                        <td>{{ $student->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                                        <td class="text-center">
                                            @php
                                                $studentResultats = $resultats->get($student->id);
                                                $moyennesCount = $studentResultats ? $studentResultats->count() : 0;
                                            @endphp
                                            <span class="badge {{ $moyennesCount > 0 ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $moyennesCount }} / {{ $matieres->count() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $studentBulletin = $absences->get($student->id);
                                                $totalHeures = $studentBulletin ? $studentBulletin->total_absences : 0;
                                            @endphp
                                            <span class="badge {{ $totalHeures > 0 ? 'bg-warning' : 'bg-success' }}">
                                                {{ $totalHeures }}h
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('esbtp.resultats.etudiant', $student->id) }}?annee_universitaire_id={{ $annee_universitaire_id }}&semestre={{ $semestre }}"
                                               class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"
                                               title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>Aucun étudiant trouvé avec les filtres sélectionnés.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modals will be included here -->
@include('esbtp.resultats.modals.edit-moyennes')
@include('esbtp.resultats.modals.edit-professeurs')
@include('esbtp.resultats.modals.edit-absences')
@include('esbtp.resultats.modals.edit-matieres')

@endsection

@push('scripts')
<script>
    // Global variables
    let selectedStudents = [];
    const classeId = {{ $classe->id }};
    const anneeUniversitaireId = {{ $annee_universitaire_id }};
    const semestre = {{ $semestre ?? 'null' }};
    const periode = semestre ? 'semestre' + semestre : null;

    $(document).ready(function() {
        initializeCheckboxes();
    });

    // Initialize checkbox functionality
    function initializeCheckboxes() {
        // Select all checkbox
        $('#selectAll').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.student-select').prop('checked', isChecked);
            updateSelectedStudents();
        });

        // Individual checkboxes
        $('.student-select').on('change', function() {
            updateSelectedStudents();

            // Update "select all" checkbox
            const totalCheckboxes = $('.student-select').length;
            const checkedCheckboxes = $('.student-select:checked').length;
            $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
    }

    // Update selected students array and UI
    function updateSelectedStudents() {
        selectedStudents = [];
        $('.student-select:checked').each(function() {
            selectedStudents.push({
                id: $(this).val(),
                name: $(this).data('name'),
                matricule: $(this).data('matricule')
            });
        });

        $('#selected-count').text(selectedStudents.length);

        // Enable/disable action buttons
        const hasSelection = selectedStudents.length > 0;
        $('#btnMoyennes').prop('disabled', !hasSelection);
        $('#btnAbsences').prop('disabled', !hasSelection);

        // Professeurs and Matieres don't require student selection
        $('#btnProfesseurs').prop('disabled', false);
        $('#btnMatieres').prop('disabled', false);
    }

    // Show loading overlay
    function showLoading() {
        $('#loadingOverlay').addClass('active');
    }

    // Hide loading overlay
    function hideLoading() {
        $('#loadingOverlay').removeClass('active');
    }

    // Display toast notification
    function showToast(message, type = 'success') {
        const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
        const toast = $(`
            <div class="toast align-items-center text-white ${bgColor} border-0" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);

        $('body').append(toast);
        const toastInstance = new bootstrap.Toast(toast[0]);
        toastInstance.show();

        // Remove from DOM after hiding
        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }

    // Update mode card styles based on selection
    function updateModeCardStyles() {
        const selectedMode = $('input[name="editMode"]:checked').val();

        // Reset both cards to default state (gris transparent)
        $('#labelModeByMatiere').css({
            'background': 'transparent',
            'border-color': '#dee2e6',
            'box-shadow': '0 2px 8px rgba(0, 0, 0, 0.05)',
            'transform': 'none'
        });
        $('#labelModeByMatiere').find('h6').css('color', '#6c757d');
        $('#labelModeByMatiere').find('small').css('color', '#6c757d');
        $('#labelModeByMatiere').find('.d-flex > div').css({
            'background': 'linear-gradient(135deg, #6c757d, #495057)'
        });

        $('#labelModeByStudent').css({
            'background': 'transparent',
            'border-color': '#dee2e6',
            'box-shadow': '0 2px 8px rgba(0, 0, 0, 0.05)',
            'transform': 'none'
        });
        $('#labelModeByStudent').find('h6').css('color', '#6c757d');
        $('#labelModeByStudent').find('small').css('color', '#6c757d');
        $('#labelModeByStudent').find('.d-flex > div').css({
            'background': 'linear-gradient(135deg, #6c757d, #495057)'
        });

        // Apply active state to selected card (bleu avec fond bleu)
        if (selectedMode === 'matiere') {
            $('#labelModeByMatiere').css({
                'background': 'linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)',
                'border-color': '#0d6efd',
                'box-shadow': '0 8px 20px rgba(13, 110, 253, 0.4)',
                'transform': 'translateY(-4px)'
            });
            $('#labelModeByMatiere').find('h6').css('color', 'white');
            $('#labelModeByMatiere').find('small').css('color', 'white');
            $('#labelModeByMatiere').find('.d-flex > div').css({
                'background': 'rgba(255, 255, 255, 0.2)'
            });
        } else {
            $('#labelModeByStudent').css({
                'background': 'linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)',
                'border-color': '#0d6efd',
                'box-shadow': '0 8px 20px rgba(13, 110, 253, 0.4)',
                'transform': 'translateY(-4px)'
            });
            $('#labelModeByStudent').find('h6').css('color', 'white');
            $('#labelModeByStudent').find('small').css('color', 'white');
            $('#labelModeByStudent').find('.d-flex > div').css({
                'background': 'rgba(255, 255, 255, 0.2)'
            });
        }
    }

    // Modal opening functions
    function openMoyennesModal() {
        if (selectedStudents.length === 0) {
            showToast('Veuillez sélectionner au moins un étudiant', 'error');
            return;
        }

        // Populate mode display
        $('#studentModeCount').text(selectedStudents.length);

        // Setup mode switching
        $('input[name="editMode"]').on('change', function() {
            updateModeCardStyles();

            if ($(this).val() === 'matiere') {
                $('#modeByMatiereContent').show();
                $('#modeByStudentContent').hide();
            } else {
                $('#modeByMatiereContent').hide();
                $('#modeByStudentContent').show();
                populateStudentAccordion();
            }
        });

        // Initialize mode card styles
        updateModeCardStyles();

        // Setup matiere selection
        $('#selectMatiere').on('change', function() {
            const matiereId = $(this).val();
            if (matiereId) {
                const matiereName = $(this).find('option:selected').data('name');
                const matiereCoeff = $(this).find('option:selected').data('coeff');

                $('#selectedMatiereName').text(matiereName);
                $('#selectedMatiereCoeff').text(matiereCoeff);
                $('#studentsGradesTable').show();

                populateGradesTable(matiereId);
            } else {
                $('#studentsGradesTable').hide();
            }
        });

        $('#modalEditMoyennes').modal('show');
    }

    function populateGradesTable(matiereId) {
        showLoading();

        // Fetch existing moyennes for selected students and matiere
        $.ajax({
            url: '{{ route("esbtp.resultats.get-moyennes") }}',
            method: 'GET',
            data: {
                classe_id: classeId,
                annee_universitaire_id: anneeUniversitaireId,
                semestre: semestre,
                matiere_id: matiereId,
                etudiant_ids: selectedStudents.map(s => s.id)
            },
            success: function(response) {
                const tbody = $('#gradesTableBody');
                tbody.empty();

                selectedStudents.forEach(student => {
                    const resultat = response.resultats.find(r => r.etudiant_id == student.id);
                    const moyenne = resultat ? resultat.moyenne : '';
                    const type = resultat ? resultat.type : null;
                    const isManual = type === 'manuel';
                    const isCalculated = type === 'calculé';

                    let badgeHtml = '<span class="badge bg-secondary">Non saisi</span>';
                    if (isManual) {
                        badgeHtml = '<span class="badge bg-warning">Manuel</span>';
                    } else if (isCalculated) {
                        badgeHtml = '<span class="badge bg-info">Calculé</span>';
                    }

                    tbody.append(`
                        <tr>
                            <td><strong>${student.matricule}</strong></td>
                            <td>${student.name}</td>
                            <td>
                                <input type="number" class="form-control" min="0" max="20" step="0.01"
                                       name="moyenne_${student.id}"
                                       data-student-id="${student.id}"
                                       data-matiere-id="${matiereId}"
                                       value="${moyenne}"
                                       placeholder="0.00">
                            </td>
                            <td>
                                ${badgeHtml}
                            </td>
                        </tr>
                    `);
                });

                hideLoading();
            },
            error: function() {
                hideLoading();
                showToast('Erreur lors du chargement des moyennes', 'error');
            }
        });
    }

    function populateStudentAccordion() {
        const accordion = $('#studentAccordion');
        accordion.empty();
        showLoading();

        const matieres = @json($matieres);
        const matiereIds = matieres.map(m => m.id);

        // Fetch all existing moyennes for selected students and all matières
        $.ajax({
            url: '{{ route("esbtp.resultats.get-moyennes") }}',
            method: 'GET',
            data: {
                classe_id: classeId,
                annee_universitaire_id: anneeUniversitaireId,
                semestre: semestre,
                matiere_ids: matiereIds,
                etudiant_ids: selectedStudents.map(s => s.id)
            },
            success: function(response) {
                selectedStudents.forEach((student, index) => {
                    let matieresHtml = '';

                    matieres.forEach((matiere, mIndex) => {
                        // Find existing resultat for this student and matiere
                        const resultat = response.resultats.find(r =>
                            r.etudiant_id == student.id && r.matiere_id == matiere.id
                        );
                        const moyenne = resultat ? resultat.moyenne : '';
                        const type = resultat ? resultat.type : null;
                        const isManual = type === 'manuel';
                        const isCalculated = type === 'calculé';

                        let badgeHtml = '';
                        if (isManual) {
                            badgeHtml = '<span class="badge bg-warning ms-2" style="font-size: 0.65rem;">Manuel</span>';
                        } else if (isCalculated) {
                            badgeHtml = '<span class="badge bg-info ms-2" style="font-size: 0.65rem;">Calculé</span>';
                        }

                        matieresHtml += `
                            <div class="row mb-3 align-items-center" style="
                                background: ${mIndex % 2 === 0 ? '#f8f9fa' : 'white'};
                                padding: 0.75rem;
                                border-radius: 8px;
                                border: 1px solid #e9ecef;
                            ">
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="
                                            width: 35px;
                                            height: 35px;
                                            border-radius: 50%;
                                            background: linear-gradient(135deg, #0d6efd, #0a58ca);
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            color: white;
                                            font-weight: bold;
                                            font-size: 0.75rem;
                                        ">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="font-size: 0.9rem; color: #2d3748;">
                                                ${matiere.name} ${badgeHtml}
                                            </div>
                                            <small class="text-muted">Coeff: ${matiere.pivot.coefficient ?? 1}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="input-group">
                                        <span class="input-group-text" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 2px solid ${isManual ? '#ffc107' : (isCalculated ? '#0dcaf0' : '#dee2e6')}; border-right: none;">
                                            <i class="fas fa-chart-line" style="color: ${isManual ? '#ffc107' : (isCalculated ? '#0dcaf0' : '#0d6efd')};"></i>
                                        </span>
                                        <input type="number" class="form-control" min="0" max="20" step="0.01"
                                               name="student_${student.id}_matiere_${matiere.id}"
                                               data-student-id="${student.id}"
                                               data-matiere-id="${matiere.id}"
                                               value="${moyenne}"
                                               placeholder="0.00"
                                               style="font-weight: 600; color: #495057; border: 2px solid ${isManual ? '#ffc107' : (isCalculated ? '#0dcaf0' : '#dee2e6')}; border-left: none; border-right: none;">
                                        <span class="input-group-text" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 2px solid ${isManual ? '#ffc107' : (isCalculated ? '#0dcaf0' : '#dee2e6')}; border-left: none;">/ 20</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    accordion.append(`
                        <div class="accordion-item" style="
                            border: 2px solid #e3f2fd;
                            border-radius: 12px;
                            margin-bottom: 1rem;
                            overflow: hidden;
                            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
                        ">
                            <h2 class="accordion-header" id="heading${index}">
                                <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}"
                                        style="
                                            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                                            color: #0d6efd;
                                            font-weight: 600;
                                            font-size: 1rem;
                                            padding: 1.25rem;
                                            border: none;
                                        ">
                                    <div class="d-flex align-items-center gap-3 w-100">
                                        <div style="
                                            width: 45px;
                                            height: 45px;
                                            border-radius: 50%;
                                            background: linear-gradient(135deg, #0d6efd, #0a58ca);
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            color: white;
                                            font-size: 1.25rem;
                                        ">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div>${student.name}</div>
                                            <small style="color: #6c757d; font-weight: 400;">Matricule: ${student.matricule}</small>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#studentAccordion">
                                <div class="accordion-body" style="padding: 1.5rem; background: white;">
                                    ${matieresHtml}
                                </div>
                            </div>
                        </div>
                    `);
                });

                hideLoading();
            },
            error: function() {
                hideLoading();
                showToast('Erreur lors du chargement des moyennes', 'error');
            }
        });
    }

    function openProfesseursModal() {
        $('#modalEditProfesseurs').modal('show');
    }

    function openAbsencesModal() {
        if (selectedStudents.length === 0) {
            showToast('Veuillez sélectionner au moins un étudiant', 'error');
            return;
        }

        $('#absencesStudentCount').text(selectedStudents.length);
        showLoading();

        // Fetch existing absences for selected students
        $.ajax({
            url: '{{ route("esbtp.resultats.get-absences") }}',
            method: 'GET',
            data: {
                classe_id: classeId,
                annee_universitaire_id: anneeUniversitaireId,
                semestre: semestre,
                etudiant_ids: selectedStudents.map(s => s.id)
            },
            success: function(response) {
                const tbody = $('#absencesTableBody');
                tbody.empty();

                selectedStudents.forEach(student => {
                    const bulletin = response.bulletins.find(b => b.etudiant_id == student.id);
                    const justifiees = bulletin ? bulletin.absences_justifiees : '';
                    const nonJustifiees = bulletin ? bulletin.absences_non_justifiees : '';
                    const isManual = bulletin ? bulletin.absences_type === 'manuel' : false;
                    const isCalculated = bulletin ? bulletin.absences_type === 'calculé' : false;

                    tbody.append(`
                        <tr>
                            <td><strong>${student.matricule}</strong></td>
                            <td>
                                ${student.name}
                                ${isManual ? '<br><span class="badge bg-warning mt-1">Manuel</span>' : ''}
                                ${isCalculated ? '<br><span class="badge bg-info mt-1">Calculé</span>' : ''}
                            </td>
                            <td>
                                <input type="number" class="form-control" min="0" step="0.5"
                                       name="absences_justifiees_${student.id}"
                                       data-student-id="${student.id}"
                                       value="${justifiees}"
                                       placeholder="0">
                            </td>
                            <td>
                                <input type="number" class="form-control" min="0" step="0.5"
                                       name="absences_non_justifiees_${student.id}"
                                       data-student-id="${student.id}"
                                       value="${nonJustifiees}"
                                       placeholder="0">
                            </td>
                        </tr>
                    `);
                });

                hideLoading();
                $('#modalEditAbsences').modal('show');
            },
            error: function() {
                hideLoading();
                showToast('Erreur lors du chargement des absences', 'error');
            }
        });
    }

    function openMatieresModal() {
        $('#modalEditMatieres').modal('show');

        // Setup coefficient calculation
        $('input[name^="coeff_"]').on('input', function() {
            let total = 0;
            $('input[name^="coeff_"]').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#totalCoefficient').text(total.toFixed(1));
        });
    }

    // Save functions
    function saveMoyennes() {
        showLoading();

        const mode = $('input[name="editMode"]:checked').val();
        const moyennes = [];

        if (mode === 'matiere') {
            const matiereId = $('#selectMatiere').val();
            if (!matiereId) {
                hideLoading();
                showToast('Veuillez sélectionner une matière', 'error');
                return;
            }

            $('input[name^="moyenne_"]').each(function() {
                const value = $(this).val();
                if (value) {
                    moyennes.push({
                        etudiant_id: $(this).data('student-id'),
                        matiere_id: $(this).data('matiere-id'),
                        moyenne: parseFloat(value)
                    });
                }
            });
        } else {
            $('input[name^="student_"]').each(function() {
                const value = $(this).val();
                if (value) {
                    moyennes.push({
                        etudiant_id: $(this).data('student-id'),
                        matiere_id: $(this).data('matiere-id'),
                        moyenne: parseFloat(value)
                    });
                }
            });
        }

        if (moyennes.length === 0) {
            hideLoading();
            showToast('Aucune moyenne à enregistrer', 'error');
            return;
        }

        $.ajax({
            url: '{{ route("esbtp.resultats.bulk-update-moyennes") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                classe_id: classeId,
                annee_universitaire_id: anneeUniversitaireId,
                semestre: semestre,
                moyennes: moyennes
            },
            success: function(response) {
                hideLoading();
                $('#modalEditMoyennes').modal('hide');
                showToast(response.message || 'Moyennes enregistrées avec succès', 'success');
                setTimeout(() => window.location.reload(), 1500);
            },
            error: function(xhr) {
                hideLoading();
                const message = xhr.responseJSON?.message || 'Erreur lors de l\'enregistrement';
                showToast(message, 'error');
            }
        });
    }

    function saveProfesseurs() {
        showLoading();

        const professeurs = {};
        $('select[name^="professeur_"]').each(function() {
            const matiereId = $(this).data('matiere-id');
            const enseignantId = $(this).val();
            if (enseignantId) {
                professeurs[matiereId] = enseignantId;
            }
        });

        if (Object.keys(professeurs).length === 0) {
            hideLoading();
            showToast('Veuillez assigner au moins un enseignant', 'error');
            return;
        }

        $.ajax({
            url: '{{ route("esbtp.resultats.bulk-update-professeurs") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                classe_id: classeId,
                annee_universitaire_id: anneeUniversitaireId,
                semestre: semestre,
                professeurs: professeurs
            },
            success: function(response) {
                hideLoading();
                $('#modalEditProfesseurs').modal('hide');
                showToast(response.message || 'Professeurs assignés avec succès', 'success');
                setTimeout(() => window.location.reload(), 1500);
            },
            error: function(xhr) {
                hideLoading();
                const message = xhr.responseJSON?.message || 'Erreur lors de l\'assignation';
                showToast(message, 'error');
            }
        });
    }

    function saveAbsences() {
        showLoading();

        const absences = [];
        selectedStudents.forEach(student => {
            const justifiees = $(`input[name="absences_justifiees_${student.id}"]`).val();
            const nonJustifiees = $(`input[name="absences_non_justifiees_${student.id}"]`).val();

            if (justifiees || nonJustifiees) {
                absences.push({
                    etudiant_id: student.id,
                    absences_justifiees: parseFloat(justifiees) || 0,
                    absences_non_justifiees: parseFloat(nonJustifiees) || 0
                });
            }
        });

        if (absences.length === 0) {
            hideLoading();
            showToast('Aucune absence à enregistrer', 'error');
            return;
        }

        $.ajax({
            url: '{{ route("esbtp.resultats.bulk-update-absences") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                classe_id: classeId,
                annee_universitaire_id: anneeUniversitaireId,
                semestre: semestre,
                absences: absences
            },
            success: function(response) {
                hideLoading();
                $('#modalEditAbsences').modal('hide');
                showToast(response.message || 'Absences enregistrées avec succès', 'success');
                setTimeout(() => window.location.reload(), 1500);
            },
            error: function(xhr) {
                hideLoading();
                const message = xhr.responseJSON?.message || 'Erreur lors de l\'enregistrement';
                showToast(message, 'error');
            }
        });
    }

    function saveMatieres() {
        showLoading();

        const coefficients = {};
        $('input[name^="coeff_"]').each(function() {
            const matiereId = $(this).data('matiere-id');
            const coefficient = parseFloat($(this).val());
            if (coefficient >= 0) {
                coefficients[matiereId] = coefficient;
            }
        });

        if (Object.keys(coefficients).length === 0) {
            hideLoading();
            showToast('Aucun coefficient à modifier', 'error');
            return;
        }

        $.ajax({
            url: '{{ route("esbtp.resultats.bulk-update-coefficients") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                classe_id: classeId,
                coefficients: coefficients
            },
            success: function(response) {
                hideLoading();
                $('#modalEditMatieres').modal('hide');
                showToast(response.message || 'Coefficients mis à jour avec succès', 'success');
                setTimeout(() => window.location.reload(), 1500);
            },
            error: function(xhr) {
                hideLoading();
                const message = xhr.responseJSON?.message || 'Erreur lors de la mise à jour';
                showToast(message, 'error');
            }
        });
    }
</script>
@endpush
