@extends('layouts.app')

@section('title', 'Marquer les présences')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-check me-2"></i>Marquer les présences</h1>
                <p class="header-subtitle">Enregistrement des présences étudiantes</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($messageErreur))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ $messageErreur }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-check"></i>
                    Marquer les présences
                </div>
                <div class="main-card-subtitle">Enregistrement des présences étudiantes pour un cours</div>
            </div>
            <div class="main-card-body">

                    <!-- Informations de débogage (visible uniquement en développement) -->
                    @if(config('app.debug') && isset($debug))
                        <div class="alert alert-secondary mb-4">
                            <h5><i class="fas fa-bug me-2"></i>Informations de débogage :</h5>
                            <pre>{{ json_encode($debug, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif

                    <!-- Guide d'utilisation -->
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle me-2"></i>Comment marquer les présences :</h5>
                        <ol class="mb-0">
                            <li>Sélectionnez une classe dans la liste déroulante</li>
                            <li>Choisissez une séance de cours parmi celles disponibles pour cette classe</li>
                            <li>La date sera automatiquement calculée en fonction de la séance choisie</li>
                            <li>Marquez les présences pour chaque étudiant et enregistrez</li>
                        </ol>
                    </div>

                    <!-- Sélection de la classe et de la séance -->
                    <div class="mb-4">
                        <form id="selectionForm" method="GET" action="{{ route('esbtp.attendances.create') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="classe_id" class="form-label">Classe</label>
                                <select name="classe_id" id="classe_id" class="form-control" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Sélectionnez d'abord une classe pour voir les séances disponibles.
                                </small>
                            </div>

                            @if(isset($classeSelectionnee) && $classeSelectionnee)
                                <div class="col-md-4" id="seance-select-container">
                                    <label for="seance_id" class="form-label">Séance de cours</label>
                                    <select name="seance_id" id="seance_id" class="form-control" required>
                                        <option value="">Sélectionner une séance</option>
                                        @foreach($seances as $seance)
                                            <option value="{{ $seance->id }}" {{ request('seance_id') == $seance->id ? 'selected' : '' }}
                                                data-date="{{ $seance->date_calculee }}"
                                                data-jour="{{ $seance->jour_nom }}">
                                                {{ $seance->matiere->name ?? 'Matière inconnue' }} - {{ $seance->heure_debut->format('H:i') }} à {{ $seance->heure_fin->format('H:i') }} ({{ $seance->jour_nom }})
                                                @if($seance->date_calculee)
                                                    - {{ \Carbon\Carbon::parse($seance->date_calculee)->format('d/m/Y') }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($seances->isEmpty())
                                        <small class="form-text text-danger">
                                            <i class="fas fa-exclamation-circle"></i> Aucune séance disponible pour cette classe. Vérifiez que l'emploi du temps est actif.
                                        </small>
                                    @else
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Sélectionnez une séance pour voir les étudiants.
                                        </small>
                                    @endif
                                </div>
                            @endif

                            @if(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee)
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" name="date" id="date" class="form-control" required value="{{ $dateSeance ?? request('date', date('Y-m-d')) }}" {{ $dateSeance ? 'readonly' : '' }}>
                                    @if($dateSeance)
                                        <small class="form-text text-info">
                                            <i class="fas fa-info-circle"></i> Cette date est automatiquement calculée en fonction du jour de la séance et de la période de l'emploi du temps.
                                        </small>
                                    @endif
                                </div>
                            @endif
                        </form>
                    </div>

                    <!-- Formulaire de saisie des présences - TOUJOURS PRESENT -->
                    <form action="{{ route('esbtp.attendances.store') }}" method="POST" id="attendanceForm" style="{{ (!request()->filled('seance_id') || !isset($etudiants) || $etudiants->count() == 0) ? 'display:none;' : '' }}">
                        @csrf
                        <input type="hidden" name="seance_cours_id" id="hidden_seance_id" value="{{ request('seance_id') }}">
                        <input type="hidden" name="date" id="hidden_date" value="{{ $dateSeance ?? request('date') }}">

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Statut</th>
                                        <th>Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody id="students-table-body">
                                    @if(request()->filled('seance_id') && isset($etudiants) && $etudiants->count() > 0)
                                        @foreach($etudiants as $etudiant)
                                            @php
                                                // Récupérer l'attendance existante pour cet étudiant (mode hybride create/update)
                                                $attendance = $existingAttendances[$etudiant->id] ?? null;
                                                $statut = $attendance ? $attendance->statut : 'present'; // Défaut: present
                                                $statutOriginal = $statut; // Pour debug
                                                // Normaliser 'late' en 'retard' pour compatibilité avec le formulaire
                                                if ($statut === 'late') {
                                                    $statut = 'retard';
                                                }
                                                $commentaire = $attendance ? $attendance->commentaire : '';
                                            @endphp
                                            <tr data-etudiant-id="{{ $etudiant->id }}" data-debug-statut="{{ $statut }}" data-debug-statut-original="{{ $statutOriginal }}">
                                                <td>{{ $etudiant->nom_complet }}</td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input"
                                                               type="radio"
                                                               name="statuts[{{ $etudiant->id }}]"
                                                               id="present_{{ $etudiant->id }}"
                                                               value="present"
                                                               {{ $statut === 'present' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-success" for="present_{{ $etudiant->id }}">Présent</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input"
                                                               type="radio"
                                                               name="statuts[{{ $etudiant->id }}]"
                                                               id="absent_{{ $etudiant->id }}"
                                                               value="absent"
                                                               {{ $statut === 'absent' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-danger" for="absent_{{ $etudiant->id }}">Absent</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input"
                                                               type="radio"
                                                               name="statuts[{{ $etudiant->id }}]"
                                                               id="retard_{{ $etudiant->id }}"
                                                               value="retard"
                                                               {{ $statut === 'retard' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-warning" for="retard_{{ $etudiant->id }}">Retard</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input"
                                                               type="radio"
                                                               name="statuts[{{ $etudiant->id }}]"
                                                               id="excuse_{{ $etudiant->id }}"
                                                               value="excuse"
                                                               {{ $statut === 'excuse' ? 'checked' : '' }}>
                                                        <label class="form-check-label text-info" for="excuse_{{ $etudiant->id }}">Excusé</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           name="commentaires[{{ $etudiant->id }}]"
                                                           class="form-control"
                                                           placeholder="Commentaire (optionnel)"
                                                           value="{{ $commentaire }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-gradient-primary">
                                    <i class="mdi mdi-content-save"></i> Enregistrer les présences
                                </button>
                                <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">Annuler</a>
                            </div>
                        </form>

                        <!-- Boutons pour marquer tous les étudiants avec onclick direct -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-success btn-sm" onclick="marquerTous('present')">
                                <i class="mdi mdi-check-all"></i> Tous présents
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="marquerTous('absent')">
                                <i class="mdi mdi-close-all"></i> Tous absents
                            </button>
                        </div>
                    @elseif(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee && isset($etudiants) && $etudiants->count() == 0)
                        <div class="alert alert-warning">
                            <i class="mdi mdi-alert-circle"></i> Aucun étudiant n'est inscrit dans cette classe.
                        </div>
                    @elseif(request()->filled('seance_id') && isset($classeSelectionnee) && $classeSelectionnee && !isset($messageErreur))
                        <div class="alert alert-info">
                            <i class="mdi mdi-information-outline"></i> Veuillez vérifier que la classe sélectionnée a des étudiants inscrits et que l'emploi du temps est correctement configuré.
                        </div>
                    @elseif(isset($classeSelectionnee) && $classeSelectionnee && !request()->filled('seance_id') && isset($seances) && $seances->isNotEmpty())
                        <div class="alert alert-info">
                            <i class="mdi mdi-information-outline"></i> Veuillez sélectionner une séance pour voir les étudiants et marquer les présences.
                        </div>
                    @elseif(!isset($classeSelectionnee) || !$classeSelectionnee)
                        <div class="alert alert-info">
                            <i class="mdi mdi-information-outline"></i> Veuillez d'abord sélectionner une classe pour commencer.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
    /* Animation "travelling light" pour le refresh des lignes */
    .student-row-highlight {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        background: linear-gradient(90deg,
            rgba(40, 167, 69, 0) 0%,
            rgba(40, 167, 69, 0.75) 50%,
            rgba(40, 167, 69, 0) 100%);
        transform: translateX(-65%) skewX(-12deg);
        opacity: 0;
    }

    .student-row-highlight.animate {
        animation: student-row-highlight-move 3.2s ease-out forwards;
    }

    @keyframes student-row-highlight-move {
        0% { opacity: 0; transform: translateX(-65%) skewX(-12deg); }
        18% { opacity: 0.92; }
        55% { opacity: 0.7; }
        100% { opacity: 0; transform: translateX(115%) skewX(-12deg); }
    }

    tbody tr {
        position: relative;
    }

    /* Loading spinner */
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-left: 8px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
    // Fonction simple pour marquer tous les étudiants avec un statut spécifique
    function marquerTous(statut) {
        console.log('Marquer tous comme ' + statut);
        var radios = document.querySelectorAll('input[type="radio"][value="' + statut + '"]');
        console.log('Nombre de boutons radio trouvés: ' + radios.length);
        for (var i = 0; i < radios.length; i++) {
            radios[i].checked = true;
        }
    }

    // Animation "travelling light"
    function triggerStudentRowHighlight(row) {
        const highlight = document.createElement('div');
        highlight.className = 'student-row-highlight';
        row.appendChild(highlight);
        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });
        setTimeout(() => {
            highlight.remove();
        }, 3200);
    }

    // Charger les séances via AJAX quand la classe change
    function loadSeances(classeId) {
        console.log('📡 [AJAX] Chargement séances pour classe:', classeId);

        const seanceSelect = document.getElementById('seance_id');
        const seanceContainer = document.getElementById('seance-select-container');

        // Afficher un loader sur le label classe
        const classeLabel = document.querySelector('label[for="classe_id"]');
        let spinner = null;
        if (classeLabel) {
            spinner = document.createElement('span');
            spinner.className = 'loading-spinner';
            classeLabel.appendChild(spinner);
        } else {
            console.warn('⚠️ Label classe introuvable pour spinner');
        }

        const url = '{{ route("esbtp.attendances.load-seances") }}?classe_id=' + classeId;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (spinner) spinner.remove();

            if (data.success) {
                console.log('✅ [AJAX] Séances reçues:', data.nbSeances);

                // Créer le conteneur de séance s'il n'existe pas
                if (!seanceContainer) {
                    const formRow = document.getElementById('selectionForm');
                    if (!formRow) {
                        console.error('❌ Formulaire introuvable #selectionForm');
                        alert('Erreur: impossible de trouver le formulaire de sélection');
                        return;
                    }
                    const newContainer = document.createElement('div');
                    newContainer.className = 'col-md-4';
                    newContainer.id = 'seance-select-container';
                    newContainer.innerHTML = `
                        <label for="seance_id" class="form-label">Séance de cours</label>
                        <select name="seance_id" id="seance_id" class="form-control" required>
                            ${data.options}
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Sélectionnez une séance pour voir les étudiants.
                        </small>
                    `;
                    formRow.appendChild(newContainer);
                } else {
                    // Remplacer les options du select existant
                    if (seanceSelect) {
                        seanceSelect.innerHTML = data.options;
                    }
                }

                // Cacher le formulaire d'attendances et le champ date jusqu'à sélection d'une séance
                const attendanceForm = document.getElementById('attendanceForm');
                if (attendanceForm) {
                    attendanceForm.style.display = 'none';
                }
                const tbody = document.getElementById('students-table-body');
                if (tbody) {
                    tbody.innerHTML = '';
                }

                // Supprimer le champ date s'il existe
                const dateInput = document.getElementById('date');
                if (dateInput) {
                    const dateContainer = dateInput.closest('.col-md-4');
                    if (dateContainer) {
                        dateContainer.remove();
                    }
                }

                // Mettre à jour l'URL
                const newUrl = '{{ route("esbtp.attendances.create") }}?classe_id=' + classeId;
                history.pushState({}, '', newUrl);

                console.log('✅ Séances chargées, attendez sélection d\'une séance');
            } else {
                console.error('❌ Erreur:', data.message);
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            if (spinner) spinner.remove();
            console.error('❌ Erreur AJAX:', error);
            alert('Une erreur est survenue lors du chargement des séances: ' + error.message);
        });
    }

    // Charger les étudiants via AJAX
    function loadStudents(classeId, seanceId) {
        const tableBody = document.getElementById('students-table-body');
        if (!tableBody) return;

        // Afficher un loader
        const label = document.querySelector('label[for="seance_id"]');
        const existingSpinner = label.querySelector('.loading-spinner');
        if (!existingSpinner) {
            const spinner = document.createElement('span');
            spinner.className = 'loading-spinner';
            label.appendChild(spinner);
        }

        const url = '{{ route("esbtp.attendances.load-students") }}?classe_id=' + classeId + '&seance_id=' + seanceId;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Retirer le spinner
            const spinner = label.querySelector('.loading-spinner');
            if (spinner) spinner.remove();

            if (data.success) {
                console.log('✅ [AJAX] Données reçues:', data);

                // Remplacer le contenu du tbody
                tableBody.innerHTML = data.html;

                // Mettre à jour les inputs hidden
                document.getElementById('hidden_seance_id').value = seanceId;
                document.getElementById('hidden_date').value = data.dateSeance;

                // IMPORTANT: Créer/Mettre à jour le champ date visible dans le formulaire de sélection
                let dateInput = document.getElementById('date');
                if (!dateInput) {
                    const dateContainer = document.createElement('div');
                    dateContainer.className = 'col-md-4';
                    dateContainer.innerHTML = `
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" required readonly value="${data.dateSeance}">
                        <small class="form-text text-info">
                            <i class="fas fa-info-circle"></i> Cette date est automatiquement calculée en fonction du jour de la séance.
                        </small>
                    `;
                    document.getElementById('selectionForm').appendChild(dateContainer);
                } else {
                    dateInput.value = data.dateSeance;
                }

                // Afficher le formulaire
                const attendanceForm = document.getElementById('attendanceForm');
                attendanceForm.style.display = '';

                // Vérifier si les boutons d'action existent, sinon les créer
                let submitButtons = attendanceForm.querySelector('.submit-buttons');
                if (!submitButtons) {
                    const tableContainer = attendanceForm.querySelector('.table-responsive');
                    submitButtons = document.createElement('div');
                    submitButtons.className = 'mt-4 submit-buttons';
                    submitButtons.innerHTML = `
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Enregistrer les présences
                        </button>
                        <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">Annuler</a>
                    `;
                    tableContainer.insertAdjacentElement('afterend', submitButtons);
                }

                // Vérifier si les boutons "marquer tous" existent, sinon les créer
                let quickButtons = attendanceForm.querySelector('.quick-action-buttons');
                if (!quickButtons) {
                    const submitButtonsContainer = attendanceForm.querySelector('.submit-buttons');
                    quickButtons = document.createElement('div');
                    quickButtons.className = 'mt-3 quick-action-buttons';
                    quickButtons.innerHTML = `
                        <button type="button" class="btn btn-success btn-sm" onclick="marquerTous('present')">
                            <i class="mdi mdi-check-all"></i> Tous présents
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="marquerTous('absent')">
                            <i class="mdi mdi-close-all"></i> Tous absents
                        </button>
                    `;
                    submitButtonsContainer.insertAdjacentElement('afterend', quickButtons);
                }

                // Cacher les messages d'info
                const alerts = document.querySelectorAll('.alert-info');
                alerts.forEach(alert => alert.style.display = 'none');

                // Animer toutes les lignes
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    setTimeout(() => {
                        triggerStudentRowHighlight(row);
                    }, index * 100);
                });

                console.log('✅ Étudiants chargés:', data.nbEtudiants, 'Mode:', data.mode);
            } else {
                console.error('❌ Erreur:', data.message);
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            const spinner = label.querySelector('.loading-spinner');
            if (spinner) spinner.remove();
            console.error('❌ Erreur AJAX:', error);
            alert('Une erreur est survenue lors du chargement des étudiants.');
        });
    }

    // PATTERN EXACT DE PAIEMENTS.INDEX - jQuery + .off().on()
    $(document).ready(function() {
        console.log('🚀 [ATTENDANCES] Script jQuery chargé');

        // 1. AJAX quand classe change - charger les séances sans reload
        $('#classe_id').off('change').on('change', function(e) {
            console.log('🔵 [ATTENDANCES] Classe changée:', $(this).val());

            // BLOQUER la soumission du formulaire
            e.preventDefault();
            e.stopImmediatePropagation();

            const classeId = $(this).val();

            if (classeId) {
                console.log('📡 [ATTENDANCES] Chargement AJAX des séances - PAS DE RELOAD!');
                // Charger les séances via AJAX
                loadSeances(classeId);
            } else {
                // Si aucune classe, cacher les séances et le formulaire
                const seanceContainer = document.getElementById('seance-select-container');
                if (seanceContainer) {
                    seanceContainer.remove();
                }
                document.getElementById('attendanceForm').style.display = 'none';
                document.getElementById('students-table-body').innerHTML = '';
            }

            return false; // Double sécurité
        });

        // 2. AJAX UNIQUEMENT quand séance change - EVENT DELEGATION car élément dynamique
        $(document).off('change', '#seance_id').on('change', '#seance_id', function(e) {
            console.log('🔵 [ATTENDANCES] Séance changée:', $(this).val());

            // BLOQUER la soumission du formulaire
            e.preventDefault();
            e.stopImmediatePropagation();

            const classeId = $('#classe_id').val();
            const seanceId = $(this).val();

            if (classeId && seanceId) {
                console.log('📡 [ATTENDANCES] Lancement AJAX - PAS DE RELOAD!');

                // Construire l'URL avec paramètres
                const newUrl = '{{ route("esbtp.attendances.create") }}?classe_id=' + classeId + '&seance_id=' + seanceId;

                // Mettre à jour l'URL dans le navigateur
                history.pushState({}, '', newUrl);

                // Charger les étudiants via AJAX (le champ date sera créé dans loadStudents)
                loadStudents(classeId, seanceId);
            }

            return false; // Double sécurité
        });

        // 3. Intercepter la soumission du formulaire pour save AJAX + refresh badge
        $(document).off('submit', '#attendanceForm').on('submit', '#attendanceForm', function(e) {
            e.preventDefault();
            console.log('🔵 [ATTENDANCES] Soumission formulaire interceptée');

            const form = $(this);
            const formData = new FormData(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();

            // Désactiver le bouton et afficher un loader
            submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Enregistrement...');

            fetch(form.attr('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => {
                // Si redirection = succès
                if (response.redirected || response.ok) {
                    console.log('✅ [ATTENDANCES] Présences enregistrées avec succès');

                    // Afficher un message de succès
                    const alertContainer = document.querySelector('.main-card-body');
                    const existingSuccess = alertContainer.querySelector('.alert-success');
                    if (existingSuccess) existingSuccess.remove();

                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success alert-dismissible fade show mb-4';
                    successAlert.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>Les présences ont été enregistrées avec succès.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    alertContainer.insertBefore(successAlert, alertContainer.firstChild);

                    // Recharger les séances pour mettre à jour le badge
                    const classeId = $('#classe_id').val();
                    const seanceId = $('#seance_id').val();

                    if (classeId && seanceId) {
                        console.log('🔄 [ATTENDANCES] Rechargement séances pour MAJ badge...');

                        // Recharger les séances
                        const url = '{{ route("esbtp.attendances.load-seances") }}?classe_id=' + classeId;
                        fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Mettre à jour les options du select séance
                                const seanceSelect = document.getElementById('seance_id');
                                if (seanceSelect) {
                                    seanceSelect.innerHTML = data.options;
                                    seanceSelect.value = seanceId; // Resélectionner la séance
                                }

                                // Recharger les étudiants pour afficher les badges "Modification"
                                console.log('🔄 [ATTENDANCES] Rechargement étudiants...');
                                loadStudents(classeId, seanceId);
                            }
                        });
                    }

                    // Réactiver le bouton
                    submitBtn.prop('disabled', false).html(originalText);

                    // Scroll vers le haut pour voir le message
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data && !data.success) {
                    console.error('❌ [ATTENDANCES] Erreur:', data.message || data.errors);
                    alert('Erreur: ' + (data.message || JSON.stringify(data.errors)));
                    submitBtn.prop('disabled', false).html(originalText);
                }
            })
            .catch(error => {
                console.error('❌ [ATTENDANCES] Erreur AJAX:', error);
                alert('Une erreur est survenue lors de l\'enregistrement.');
                submitBtn.prop('disabled', false).html(originalText);
            });

            return false;
        });

        console.log('✅ [ATTENDANCES] Event delegation configuré - ZERO RELOAD MODE');
    });
</script>
@endpush
