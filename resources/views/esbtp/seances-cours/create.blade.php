@extends('layouts.app')

@section('title', 'Ajouter une séance - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<style>
    .session-type-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .session-type-card:hover {
        transform: translateY(-5px);
    }
    .session-type-card.selected {
        border-color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }
    .color-picker {
        width: 40px;
        height: 40px;
        padding: 0;
        border: none;
        border-radius: 50%;
        cursor: pointer;
    }
    .recurrence-days {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .day-checkbox {
        display: none;
    }
    .day-label {
        padding: 8px 16px;
        border-radius: 20px;
        background-color: var(--bs-gray-200);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .day-checkbox:checked + .day-label {
        background-color: var(--bs-primary);
        color: white;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-plus-circle me-2"></i>Nouvelle Séance de Cours</h1>
                <p class="header-subtitle">Créer une nouvelle séance pour {{ $emploiTemps->classe->name }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à l'emploi du temps
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fs-4"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Erreur de validation</h5>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        <form action="{{ route('esbtp.seances-cours.store') }}" method="POST" id="sessionForm">
            @csrf
            <input type="hidden" name="emploi_temps_id" value="{{ $emploiTemps->id }}">

            <div class="form-sections">
                <!-- Section 1: Type de séance -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-clipboard-list"></i>
                            Type de séance
                        </div>
                        <div class="main-card-subtitle">Sélectionnez le type de séance à programmer</div>
                    </div>
                    <div class="main-card-body">
                        <div class="session-types-container">
                            @foreach($sessionTypes as $type => $label)
                            <div class="session-type-card" data-type="{{ $type }}" onclick="selectSessionType('{{ $type }}')">
                                <div class="session-type-icon">
                                    @if($type === 'course')
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    @elseif($type === 'homework')
                                        <i class="fas fa-clipboard-check"></i>
                                    @elseif($type === 'break')
                                        <i class="fas fa-coffee"></i>
                                    @else
                                        <i class="fas fa-utensils"></i>
                                    @endif
                                </div>
                                <div class="session-type-label">{{ $label }}</div>
                            </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="type" id="sessionType" required>
                    </div>
                </div>

                <!-- Section 2: Informations de base -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Informations temporelles
                        </div>
                        <div class="main-card-subtitle">Jour, horaires et récurrence</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="jour" class="form-label">Jour <span class="text-danger">*</span></label>
                                <select name="jour" id="jour" class="form-select @error('jour') error @enderror" required>
                                    <option value="">Sélectionner un jour</option>
                                    @foreach($joursSemaine as $value => $label)
                                        <option value="{{ $value }}" {{ old('jour', $request->jour) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('jour')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="heure_debut" class="form-label">Heure de début <span class="text-danger">*</span></label>
                                <input type="time" class="form-input @error('heure_debut') error @enderror" 
                                       id="heure_debut" name="heure_debut"
                                       value="{{ old('heure_debut', $request->heure_debut) }}" required>
                                @error('heure_debut')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="heure_fin" class="form-label">Heure de fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-input @error('heure_fin') error @enderror" 
                                       id="heure_fin" name="heure_fin"
                                       value="{{ old('heure_fin') }}" required>
                                @error('heure_fin')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label">Couleur</label>
                                <div class="color-picker-wrapper">
                                    <input type="color" name="color" id="color" class="color-picker"
                                        value="{{ old('color', $defaultColors['course']) }}">
                                    <span class="color-label" id="colorLabel"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-options">
                            <div class="form-check-custom">
                                <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring"
                                    {{ old('is_recurring') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_recurring">
                                    <i class="fas fa-repeat me-2"></i>Séance récurrente
                                </label>
                            </div>
                        </div>

                        <!-- Recurrence Days -->
                        <div id="recurrenceDays" class="recurrence-section" style="display: none;">
                            <label class="form-label">Jours de récurrence</label>
                            <div class="days-selector">
                                @foreach($joursSemaine as $value => $label)
                                    <div class="day-option">
                                        <input type="checkbox" class="day-checkbox" name="recurrence_days[]"
                                            id="day_{{ $value }}" value="{{ $value }}"
                                            {{ in_array($value, old('recurrence_days', [])) ? 'checked' : '' }}>
                                        <label class="day-label" for="day_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Matières et Enseignants -->
                <div class="main-card" id="courseFields" style="display: none;">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-graduation-cap"></i>
                            Matière et Enseignant
                        </div>
                        <div class="main-card-subtitle">Configuration pédagogique de la séance</div>
                    </div>
                    <div class="main-card-body">
                        @if($planificationData['planifications_configurees'])
                            <!-- Contexte de la classe -->
                            <div class="context-card">
                                <div class="context-header">
                                    <i class="fas fa-school"></i>
                                    <span>{{ $emploiTemps->classe->name }}</span>
                                </div>
                                <div class="context-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Filière</span>
                                        <span class="stat-value">{{ $emploiTemps->classe->filiere->name }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Niveau</span>
                                        <span class="stat-value">{{ $emploiTemps->classe->niveau->name }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Matières</span>
                                        <span class="stat-value">{{ $matieres->count() }} configurées</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Volume</span>
                                        <span class="stat-value">{{ $planificationData['heures_totales'] }}h totales</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="matiere_id" class="form-label">Matière <span class="text-danger">*</span></label>
                                    <select name="matiere_id" id="matiere_id" class="form-select @error('matiere_id') error @enderror" onchange="updateTeachersForSubject()" required>
                                        <option value="">Sélectionner une matière</option>
                                        @foreach($matieres as $matiere)
                                            <option value="{{ $matiere['matiere']->id }}" 
                                                    data-heures-restantes="{{ $matiere['heures_restantes'] }}"
                                                    data-volume-total="{{ $matiere['volume_horaire_total'] }}"
                                                    data-enseignants="{{ ($matiere['enseignants_selectables'] ?? collect())->pluck('id')->toJson() }}"
                                                    {{ old('matiere_id') == $matiere['matiere']->id ? 'selected' : '' }}>
                                                {{ $matiere['matiere']->name }} 
                                                ({{ $matiere['heures_restantes'] }}h restantes / {{ $matiere['volume_horaire_total'] }}h)
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="matiere-info" class="form-info" style="display: none;">
                                        <i class="fas fa-clock"></i>
                                        <span id="heures-restantes-text"></span>
                                    </div>
                                    @error('matiere_id')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group" id="teacherFieldGroup">
                                    <label for="teacher_id" class="form-label">Enseignant assigné <span class="text-danger">*</span></label>
                                    <select name="teacher_id" id="teacher_id" class="form-select @error('teacher_id') error @enderror" onchange="showTeacherAvailability()" required>
                                        <option value="">Sélectionner d'abord une matière</option>
                                    </select>
                                    <div id="teacher-info" class="form-info" style="display: none;">
                                        <i class="fas fa-check-circle"></i>
                                        <span id="teacher-assignment-text"></span>
                                    </div>
                                    @error('teacher_id')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="salle" class="form-label">Salle</label>
                                    <input type="text" class="form-input @error('salle') error @enderror" 
                                           id="salle" name="salle" value="{{ old('salle') }}"
                                           placeholder="Ex: Salle A101">
                                    @error('salle')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Grille de disponibilité de l'enseignant sélectionné -->
                            <div id="teacher-availability" class="availability-section" style="display: none;">
                                <div class="availability-header">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Disponibilité de <span id="selected-teacher-name">l'enseignant</span></span>
                                </div>
                                
                                <!-- Légende des couleurs -->
                                <div class="availability-legend mb-3">
                                    <h6 class="legend-title"><i class="fas fa-palette me-2"></i>Légende :</h6>
                                    <div class="legend-items">
                                        <div class="legend-item">
                                            <div class="legend-color preferred"></div>
                                            <span>Préféré</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color available"></div>
                                            <span>Disponible</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color unavailable"></div>
                                            <span>Non disponible</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color occupied"></div>
                                            <span>Occupé (autre séance)</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color selected-time"></div>
                                            <span>Créneaux sélectionnés</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="availability-grid-container">
                                    <div id="availability-grid" class="teacher-availability-grid"></div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-exclamation-triangle fs-4"></i>
                                    </div>
                                    <div>
                                        <h6>Configuration requise</h6>
                                        <p class="mb-2">{{ $planificationData['message_configuration'] }}</p>
                                        @if($planificationData['lien_configuration'])
                                            <a href="{{ $planificationData['lien_configuration'] }}" class="btn-acasi primary small">
                                                <i class="fas fa-cog"></i>Configurer maintenant
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Section 4: Informations complémentaires -->
                <div class="main-card" id="homeworkFields" style="display: none;">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-clipboard-check"></i>
                            Informations sur le devoir
                        </div>
                        <div class="main-card-subtitle">Détails spécifiques au devoir</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="homework_description" class="form-label">Description du devoir</label>
                                <textarea class="form-input @error('homework_description') error @enderror" 
                                          id="homework_description" name="homework_description" rows="4"
                                          placeholder="Décrivez le devoir à donner...">{{ old('homework_description') }}</textarea>
                                @error('homework_description')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="homework_due_date" class="form-label">Date de remise</label>
                                <input type="date" class="form-input @error('homework_due_date') error @enderror" 
                                       id="homework_due_date" name="homework_due_date"
                                       value="{{ old('homework_due_date') }}">
                                @error('homework_due_date')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="evaluation-slot-card" id="homeworkTimingInfo" style="display: none;">
                            <div class="slot-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="slot-content">
                                <div class="slot-title">Créneau de l'évaluation générée</div>
                                <div class="slot-times">
                                    <span class="slot-label">Début</span>
                                    <span class="slot-time" id="homeworkStartTime">--:--</span>
                                    <span class="slot-separator">•</span>
                                    <span class="slot-label">Fin</span>
                                    <span class="slot-time" id="homeworkEndTime">--:--</span>
                                </div>
                                <div class="slot-duration">
                                    <i class="fas fa-hourglass-half"></i>
                                    Durée calculée : <span id="homeworkDuration">0</span> minutes
                                    <small>(utilisée pour l'évaluation automatique)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions du formulaire -->
                <div class="form-actions">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>
                        Enregistrer la séance
                    </button>
                    <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-times"></i>
                        Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
const currentTeacherId = "{{ old('teacher_id') }}";

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // Initialize Flatpickr for time inputs
    flatpickr("input[type=time]", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        locale: "fr"
    });

    // Initialize Flatpickr for date inputs
    flatpickr("input[type=date]", {
        locale: "fr",
        minDate: "today"
    });

    // Handle recurring checkbox
    document.getElementById('is_recurring').addEventListener('change', function() {
        document.getElementById('recurrenceDays').style.display = this.checked ? 'block' : 'none';
    });

    // Handle color picker
    document.getElementById('color').addEventListener('input', function(e) {
        document.getElementById('colorLabel').textContent = e.target.value;
    });

    // Écouter les changements d'horaires et de jour pour mettre à jour la grille
    const heureDebutInput = document.getElementById('heure_debut');
    const heureFinInput = document.getElementById('heure_fin');

    heureDebutInput.addEventListener('change', () => {
        updateSelectedTimeInGrid();
        updateHomeworkTimingInfo();
    });
    heureFinInput.addEventListener('change', () => {
        updateSelectedTimeInGrid();
        updateHomeworkTimingInfo();
    });
    document.getElementById('jour').addEventListener('change', updateSelectedTimeInGrid);

    const initialType = "{{ old('type', $request->type ?? 'course') }}";
    if (initialType) {
        selectSessionType(initialType);
    }
    updateHomeworkTimingInfo();

    // Pré-remplir les enseignants si une matière est déjà sélectionnée
    const matiereSelect = document.getElementById('matiere_id');
    if (matiereSelect && matiereSelect.value) {
        updateTeachersForSubject();
        if (currentTeacherId && document.getElementById('sessionType').value === 'course') {
            const teacherSelect = document.getElementById('teacher_id');
            teacherSelect.value = currentTeacherId;
            showTeacherAvailability();
        }
    }
});

function selectSessionType(type) {
    // Update hidden input
    document.getElementById('sessionType').value = type;

    // Update UI
    document.querySelectorAll('.session-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');

    // Show/hide relevant fields
    document.getElementById('courseFields').style.display =
        (type === 'course' || type === 'homework') ? 'block' : 'none';
    document.getElementById('homeworkFields').style.display =
        (type === 'homework') ? 'block' : 'none';

    // Update color picker with default color
    const defaultColors = @json($defaultColors);
    document.getElementById('color').value = defaultColors[type];
    document.getElementById('colorLabel').textContent = defaultColors[type];

    // Update required fields
    const teacherField = document.getElementById('teacher_id');
    const teacherGroup = document.getElementById('teacherFieldGroup');
    const teacherInfo = document.getElementById('teacher-info');
    const teacherAvailability = document.getElementById('teacher-availability');
    const matiereField = document.getElementById('matiere_id');
    const salleField = document.getElementById('salle');
    const homeworkDescField = document.getElementById('homework_description');
    const homeworkDueDateField = document.getElementById('homework_due_date');

    const requiresTeacher = type === 'course';
    const requiresSalle = (type === 'course' || type === 'homework');

    if (teacherField) {
        teacherField.required = requiresTeacher;
        teacherField.disabled = !requiresTeacher;
        if (!requiresTeacher) {
            if (typeof $ !== 'undefined') {
                $('#teacher_id').val(null).trigger('change.select2');
                $('#teacher_id').prop('disabled', true).trigger('change.select2');
            } else {
                teacherField.value = '';
            }
            if (teacherInfo) {
                teacherInfo.style.display = 'none';
            }
            if (teacherAvailability) {
                teacherAvailability.style.display = 'none';
            }
        } else if (typeof $ !== 'undefined') {
            $('#teacher_id').prop('disabled', false).trigger('change.select2');
        }
    }

    if (teacherGroup) {
        teacherGroup.style.display = requiresTeacher ? 'block' : 'none';
    }

    matiereField.required = type === 'course' || type === 'homework';
    salleField.required = requiresSalle;

    if (type === 'homework') {
        homeworkDescField.required = true;
        homeworkDueDateField.required = true;
    } else {
        homeworkDescField.required = false;
        homeworkDueDateField.required = false;
    }

    if (typeof updateTeachersForSubject === 'function') {
        updateTeachersForSubject();
    }

    updateHomeworkTimingInfo();
}

// Fonction pour mettre à jour les créneaux sélectionnés dans la grille
function updateSelectedTimeInGrid() {
    const jourSelect = document.getElementById('jour');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');
    
    // Nettoyer les anciennes sélections
    document.querySelectorAll('.availability-cell.selected-time').forEach(cell => {
        cell.classList.remove('selected-time');
    });
    
    // Si tous les champs sont remplis, surligner les créneaux
    if (jourSelect.value && heureDebut.value && heureFin.value) {
        const selectedDay = parseInt(jourSelect.value);
        const startHour = parseInt(heureDebut.value.split(':')[0]);
        const endHour = parseInt(heureFin.value.split(':')[0]);
        
        // Mapping jour numérique vers index de colonne dans la grille
        const dayMapping = {
            1: 0, // Lundi -> colonne 0
            2: 1, // Mardi -> colonne 1  
            3: 2, // Mercredi -> colonne 2
            4: 3, // Jeudi -> colonne 3
            5: 4, // Vendredi -> colonne 4
            6: 5  // Samedi -> colonne 5
        };
        
        const dayColumnIndex = dayMapping[selectedDay];
        if (dayColumnIndex !== undefined) {
            // Parcourir chaque ligne d'heure pour surligner les cellules correspondantes
            for (let hour = startHour; hour < endHour; hour++) {
                if (hour >= 8 && hour < 18) {
                    const rowIndex = hour - 8; // 8h = row 0
                    const timeRows = document.querySelectorAll('.availability-time-row');
                    if (timeRows[rowIndex]) {
                        const cells = timeRows[rowIndex].querySelectorAll('.availability-cell');
                        if (cells[dayColumnIndex]) {
                            cells[dayColumnIndex].classList.add('selected-time');
                        }
                    }
                }
            }
        }
    }

    updateHomeworkTimingInfo();
}

function updateHomeworkTimingInfo() {
    const infoBox = document.getElementById('homeworkTimingInfo');
    if (!infoBox) {
        return;
    }

    const currentType = document.getElementById('sessionType').value;
    if (currentType !== 'homework') {
        infoBox.style.display = 'none';
        return;
    }

    const heureDebut = document.getElementById('heure_debut').value;
    const heureFin = document.getElementById('heure_fin').value;

    if (!heureDebut || !heureFin) {
        infoBox.style.display = 'none';
        return;
    }

    const [startHour, startMinute] = heureDebut.split(':').map(Number);
    const [endHour, endMinute] = heureFin.split(':').map(Number);

    let startTotal = startHour * 60 + startMinute;
    let endTotal = endHour * 60 + endMinute;
    if (endTotal <= startTotal) {
        endTotal += 24 * 60;
    }

    const duration = endTotal - startTotal;
    document.getElementById('homeworkStartTime').textContent = heureDebut;
    document.getElementById('homeworkEndTime').textContent = heureFin;
    document.getElementById('homeworkDuration').textContent = duration;
    infoBox.style.display = 'flex';
}

// Form validation
document.getElementById('sessionForm').addEventListener('submit', function(e) {
    const type = document.getElementById('sessionType').value;
    if (!type) {
        e.preventDefault();
        debugAlert('Veuillez sélectionner un type de séance');
        return;
    }

    const startTime = document.getElementById('heure_debut').value;
    const endTime = document.getElementById('heure_fin').value;
    if (startTime && endTime && startTime >= endTime) {
        e.preventDefault();
        debugAlert('L\'heure de fin doit être postérieure à l\'heure de début');
        return;
    }

    // Validation de disponibilité de l'enseignant
    if (type === 'course' && !validateTeacherAvailability()) {
        e.preventDefault();
        return;
    }
});

// Fonction pour valider la disponibilité de l'enseignant
function validateTeacherAvailability() {
    const teacherSelect = document.getElementById('teacher_id');
    const jourSelect = document.getElementById('jour');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');
    const currentType = document.getElementById('sessionType').value;

    if (currentType !== 'course') {
        return true;
    }
    
    if (!teacherSelect.value || !jourSelect.value || !heureDebut.value || !heureFin.value) {
        return true; // Pas assez d'infos pour valider
    }
    
    const teacherId = teacherSelect.value;
    const selectedDay = parseInt(jourSelect.value);
    const startHour = parseInt(heureDebut.value.split(':')[0]);
    const endHour = parseInt(heureFin.value.split(':')[0]);
    
    const availabilityData = @json($availabilityData ?? []);
    
    if (!availabilityData[teacherId]) {
        debugAlert('❌ Aucune disponibilité configurée pour cet enseignant.\n\nVeuillez configurer ses disponibilités avant de programmer cette séance.');
        return false;
    }
    
    // Mapping jour numérique vers clé jour
    const dayMapping = {
        1: 'monday',
        2: 'tuesday', 
        3: 'wednesday',
        4: 'thursday',
        5: 'friday',
        6: 'saturday'
    };
    
    const dayKey = dayMapping[selectedDay];
    if (!dayKey || !availabilityData[teacherId][dayKey]) {
        debugAlert('❌ L\'enseignant n\'est pas disponible ce jour-là.\n\nVeuillez choisir un autre jour ou un autre enseignant.');
        return false;
    }
    
    // Vérifier chaque heure du créneau
    const teacherDayAvailability = availabilityData[teacherId][dayKey];
    for (let hour = startHour; hour < endHour; hour++) {
        const hourIndex = hour - 8; // 8h = index 0
        if (hourIndex >= 0 && hourIndex < teacherDayAvailability.length) {
            const status = teacherDayAvailability[hourIndex];
            const jourNoms = {1: 'lundi', 2: 'mardi', 3: 'mercredi', 4: 'jeudi', 5: 'vendredi', 6: 'samedi'};
            
            if (status === 'unavailable') {
                debugAlert(`❌ L'enseignant n'est pas disponible ${jourNoms[selectedDay]} à ${hour}:00.\n\nVeuillez ajuster les horaires ou choisir un autre enseignant.`);
                return false;
            } else if (status === 'occupied') {
                debugAlert(`❌ L'enseignant a déjà une séance programmée ${jourNoms[selectedDay]} à ${hour}:00 dans un autre emploi du temps.\n\nVeuillez choisir un autre créneau.`);
                return false;
            }
        }
    }
    
    return true; // Tout est OK
}

// Fonction pour mettre à jour les enseignants selon la matière sélectionnée
function updateTeachersForSubject() {
    const matiereSelect = document.getElementById('matiere_id');
    const teacherSelect = document.getElementById('teacher_id');
    const matiereInfo = document.getElementById('matiere-info');
    const heuresRestantesText = document.getElementById('heures-restantes-text');
    const teacherInfo = document.getElementById('teacher-info');
    const teacherAvailability = document.getElementById('teacher-availability');
    const currentType = document.getElementById('sessionType').value;
    const requiresTeacher = currentType === 'course';
    
    // Reset teacher select
    teacherSelect.innerHTML = requiresTeacher
        ? '<option value="">Sélectionner un enseignant</option>'
        : '<option value="">Aucun enseignant requis pour un devoir</option>';
    
    if (matiereSelect.value) {
        const selectedOption = matiereSelect.options[matiereSelect.selectedIndex];
        const enseignantsIds = JSON.parse(selectedOption.dataset.enseignants || '[]');
        const heuresRestantes = selectedOption.dataset.heuresRestantes;
        const volumeTotal = selectedOption.dataset.volumeTotal;
        
        // Afficher les informations sur la matière
        heuresRestantesText.textContent = `${heuresRestantes}h restantes sur ${volumeTotal}h`;
        matiereInfo.style.display = 'block';
        
        if (!requiresTeacher) {
            if (teacherInfo) {
                teacherInfo.style.display = 'none';
            }
            if (teacherAvailability) {
                teacherAvailability.style.display = 'none';
            }
            return;
        }
        
        // Ajouter les enseignants assignés à cette matière
        const allTeachers = @json($teachers->keyBy('id'));
        enseignantsIds.forEach(rawId => {
            const teacherId = rawId?.toString();
            if (teacherId && allTeachers[teacherId]) {
                const teacher = allTeachers[teacherId];
                const teacherName = teacher?.user?.name 
                    ?? teacher?.name 
                    ?? teacher?.matricule 
                    ?? `Enseignant ${teacherId}`;
                const option = new Option(teacherName, teacherId);
                if (currentTeacherId && teacherId === currentTeacherId) {
                    option.selected = true;
                }
                teacherSelect.add(option);
            }
        });
        
        if (enseignantsIds.length === 0) {
            teacherSelect.innerHTML = '<option value="">Aucun enseignant assigné à cette matière</option>';
        }
    } else {
        matiereInfo.style.display = 'none';
        teacherSelect.innerHTML = '<option value="">Sélectionner d\'abord une matière</option>';
    }
    
    // Reset teacher availability
    document.getElementById('teacher-availability').style.display = 'none';
}

// Fonction pour afficher la disponibilité de l'enseignant sélectionné
function showTeacherAvailability() {
    const teacherSelect = document.getElementById('teacher_id');
    const teacherAvailability = document.getElementById('teacher-availability');
    const selectedTeacherName = document.getElementById('selected-teacher-name');
    const availabilityGrid = document.getElementById('availability-grid');
    const teacherInfo = document.getElementById('teacher-info');
    const teacherAssignmentText = document.getElementById('teacher-assignment-text');
    const currentType = document.getElementById('sessionType').value;
    
    if (currentType !== 'course') {
        teacherAvailability.style.display = 'none';
        teacherInfo.style.display = 'none';
        return;
    }
    
    if (teacherSelect.value) {
        const teacherId = teacherSelect.value;
        const teacherName = teacherSelect.options[teacherSelect.selectedIndex].text;
        const availabilityData = @json($availabilityData);
        
        selectedTeacherName.textContent = teacherName;
        teacherAssignmentText.textContent = `Enseignant assigné: ${teacherName}`;
        teacherInfo.style.display = 'block';
        
        debugLog('🔍 Availability data for teacher', teacherId, ':', availabilityData[teacherId]);
        
        if (availabilityData[teacherId]) {
            // Construire la grille de disponibilité
            const rawAvailability = availabilityData[teacherId];
            let gridHtml = '';
            
            // Header avec les jours
            gridHtml += '<div class="availability-header-row">';
            gridHtml += '<div class="time-header">Heure</div>';
            const dayHeaders = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            for (let i = 0; i < dayHeaders.length; i++) {
                gridHtml += `<div class="day-header">${dayHeaders[i]}</div>`;
            }
            gridHtml += '</div>';
            
            // Créer les lignes pour chaque heure (8h-18h)
            for (let hour = 8; hour < 18; hour++) {
                gridHtml += '<div class="availability-time-row">';
                gridHtml += `<div class="time-label">${hour}:00</div>`;
                
                // Clés des jours dans l'ordre: monday, tuesday, wednesday, thursday, friday, saturday
                const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                
                for (let dayKey of dayKeys) {
                    let cellClass = 'unavailable';
                    let cellTitle = 'Non disponible';
                    
                    // Le format est: rawAvailability[dayKey][hourIndex] = 'available'/'preferred'/'unavailable'/'occupied'
                    if (rawAvailability[dayKey]) {
                        const hourIndex = hour - 8; // 8h = index 0
                        if (hourIndex >= 0 && hourIndex < rawAvailability[dayKey].length) {
                            const status = rawAvailability[dayKey][hourIndex];
                            if (status === 'occupied') {
                                cellClass = 'occupied';
                                cellTitle = 'Occupé par une autre séance';
                            } else if (status === 'preferred') {
                                cellClass = 'preferred';
                                cellTitle = 'Préféré';
                            } else if (status === 'available') {
                                cellClass = 'available';
                                cellTitle = 'Disponible';
                            }
                        }
                    }
                    
                    gridHtml += `<div class="availability-cell ${cellClass}" title="${cellTitle}"></div>`;
                }
                gridHtml += '</div>';
            }
            
            availabilityGrid.innerHTML = gridHtml;
            teacherAvailability.style.display = 'block';
            
            // Mettre à jour les créneaux sélectionnés après construction de la grille
            setTimeout(updateSelectedTimeInGrid, 100);
        } else {
            availabilityGrid.innerHTML = '<div class="no-availability">Aucune disponibilité configurée pour cet enseignant</div>';
            teacherAvailability.style.display = 'block';
        }
    } else {
        teacherAvailability.style.display = 'none';
        teacherInfo.style.display = 'none';
    }
}
</script>

<style>
/* === SECTIONS AVEC ESPACEMENT === */
.form-sections .main-card {
    margin-bottom: var(--space-xl);
}

/* Styles pour les types de séance */
.session-types-container {
    display: flex;
    gap: var(--space-lg);
    flex-wrap: wrap;
    justify-content: space-between;
}

.session-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--space-lg);
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--radius-medium);
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--surface);
    flex: 1;
    min-width: 120px;
    text-align: center;
    box-shadow: var(--shadow-card);
}

.session-type-card:hover {
    background: var(--primary);
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.session-type-card:hover .session-type-icon,
.session-type-card:hover .session-type-label {
    color: white;
}

.session-type-card.selected {
    background: var(--primary);
    border-color: var(--primary);
}

.session-type-card.selected .session-type-icon,
.session-type-card.selected .session-type-label {
    color: white;
}

.evaluation-slot-card {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    padding: var(--space-lg);
    border: 1px solid rgba(30, 64, 175, 0.12);
    border-radius: var(--radius-medium);
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.06), rgba(59, 130, 246, 0.08));
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
    margin-top: var(--space-lg);
}

.slot-icon {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-circle);
    background: rgba(59, 130, 246, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--primary);
}

.slot-content {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.slot-title {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 1.05rem;
}

.slot-times {
    display: flex;
    align-items: baseline;
    gap: var(--space-sm);
    font-size: 0.95rem;
}

.slot-label {
    font-weight: 600;
    color: var(--primary);
}

.slot-time {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--text-primary);
}

.slot-separator {
    color: rgba(15, 23, 42, 0.4);
    font-weight: 600;
}

.slot-duration {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    color: rgba(15, 23, 42, 0.7);
    font-size: 0.9rem;
}

.slot-duration small {
    color: rgba(15, 23, 42, 0.6);
    font-style: italic;
}

.session-type-icon {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: var(--space-sm);
    transition: color 0.3s ease;
}

.session-type-label {
    font-weight: 600;
    color: var(--text-primary);
    transition: color 0.3s ease;
}

/* === CONTEXTE DE CLASSE - DESIGN MODERNE === */
.context-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
    border: 1px solid rgba(30, 58, 138, 0.08);
    border-radius: var(--radius-medium);
    padding: var(--space-xl);
    margin-bottom: var(--space-xl);
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.04);
    position: relative;
    overflow: hidden;
}

.context-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 50%, var(--accent-blue) 100%);
}

.context-header {
    display: flex;
    align-items: center;
    margin-bottom: var(--space-lg);
    padding-bottom: var(--space-md);
    border-bottom: 1px solid rgba(30, 58, 138, 0.1);
}

.context-header i {
    font-size: 1.5rem;
    color: var(--primary);
    margin-right: var(--space-md);
    padding: var(--space-sm);
    background: rgba(30, 58, 138, 0.1);
    border-radius: var(--radius-circle);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.context-header span {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.context-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--space-lg);
}

.stat-item {
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(30, 58, 138, 0.06);
    border-radius: var(--radius-small);
    padding: var(--space-lg);
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
}

.stat-item:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(30, 58, 138, 0.12);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.08);
}

.stat-label {
    display: block;
    font-size: var(--text-small);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-secondary);
    margin-bottom: var(--space-sm);
    font-weight: 600;
}

.stat-value {
    display: block;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

/* Couleurs spécifiques par stat */
.stat-item:nth-child(1) {
    border-left: 3px solid var(--primary);
}

.stat-item:nth-child(2) {
    border-left: 3px solid var(--secondary);
}

.stat-item:nth-child(3) {
    border-left: 3px solid var(--accent-blue);
}

.stat-item:nth-child(4) {
    border-left: 3px solid var(--success);
}

/* Styles pour les disponibilités */
.availability-section {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.availability-header {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.availability-header i {
    margin-right: 0.5rem;
    color: var(--success-color);
}

.teacher-availability-grid {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #dee2e6;
}

.availability-header-row,
.availability-time-row {
    display: grid;
    grid-template-columns: 60px repeat(6, 1fr);
    gap: 1px;
    margin-bottom: 1px;
}

.time-header,
.day-header,
.time-label,
.availability-cell {
    padding: 8px;
    text-align: center;
    font-size: 0.8rem;
    border: 1px solid #e9ecef;
}

.time-header,
.day-header {
    background: #f8f9fa;
    font-weight: 600;
    color: var(--text-dark);
}

.time-label {
    background: #f8f9fa;
    font-weight: 500;
    color: var(--text-muted);
    font-size: 0.75rem;
}

.availability-cell {
    height: 32px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.availability-cell.available {
    background: #10b981;
    border-color: #059669;
    color: white;
}

.availability-cell.available:hover {
    background: #059669;
    transform: scale(1.05);
}

.availability-cell.preferred {
    background: #3b82f6;
    border-color: #2563eb;
    color: white;
    position: relative;
}

.availability-cell.preferred::after {
    content: '⭐';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 10px;
}

.availability-cell.preferred:hover {
    background: #2563eb;
    transform: scale(1.05);
}

.availability-cell.unavailable {
    background: #ef4444;
    border-color: #dc2626;
    color: white;
}

.availability-cell.unavailable:hover {
    background: #dc2626;
}

.availability-cell.occupied {
    background: #5e91de;
    border-color: #7c3aed;
    color: white;
    position: relative;
}

.availability-cell.occupied::after {
    content: '🚫';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 10px;
    color: white;
}

.availability-cell.occupied:hover {
    background: #7c3aed;
    transform: scale(1.05);
}

.availability-cell.selected-time {
    position: relative;
    animation: pulse 2s infinite;
}

.availability-cell.selected-time::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(245, 158, 11, 0.6);
    border: 2px solid #f59e0b;
    border-radius: inherit;
    z-index: 1;
}

.availability-cell.selected-time::after {
    content: '📅';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 10px;
    z-index: 2;
    color: #d97706;
    font-weight: bold;
}

.availability-cell.selected-time:hover::before {
    background: rgba(245, 158, 11, 0.8);
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.8); }
    70% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

.no-availability {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
    font-style: italic;
}

/* Styles pour la légende des disponibilités */
.availability-legend {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
}

.legend-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.75rem;
}

.legend-items {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.1);
    position: relative;
}

.legend-color.preferred {
    background: #3b82f6;
    border-color: #2563eb;
}

.legend-color.preferred::after {
    content: '⭐';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 8px;
    color: white;
}

.legend-color.available {
    background: #10b981;
    border-color: #059669;
}

.legend-color.unavailable {
    background: #ef4444;
    border-color: #dc2626;
}

.legend-color.selected-time {
    background: #f59e0b;
    border-color: #d97706;
    position: relative;
}

.legend-color.occupied {
    background: #5e91de;
    border-color: #7c3aed;
}

.legend-color.occupied::after {
    content: '🚫';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 8px;
    color: white;
}

.legend-color.selected-time::after {
    content: '📅';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 8px;
}

.legend-item span {
    font-size: 0.85rem;
    color: #495057;
    font-weight: 500;
}

/* Sélecteur de jours pour récurrence */
.days-selector {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.day-option {
    position: relative;
}

.day-checkbox {
    display: none;
}

.day-label {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: #e9ecef;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.875rem;
    font-weight: 500;
}

.day-checkbox:checked + .day-label {
    background: var(--primary-color);
    color: white;
}

/* Color picker wrapper */
.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.color-picker {
    width: 40px;
    height: 40px;
    padding: 0;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
}

.color-label {
    font-size: 0.875rem;
    color: var(--text-muted);
}

/* === STYLES FORMULAIRES MODERNES === */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-lg);
}

.form-group {
    margin-bottom: var(--space-lg);
}

.form-label {
    display: block;
    margin-bottom: var(--space-sm);
    font-weight: 600;
    font-size: var(--text-small);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: var(--space-md);
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--radius-small);
    font-size: var(--text-normal);
    font-family: inherit;
    transition: all 0.2s ease;
    background-color: var(--surface);
    color: var(--text-primary);
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
}

.form-input.error, .form-select.error, .form-textarea.error {
    border-color: var(--danger);
}

.form-error {
    margin-top: var(--space-xs);
    font-size: var(--text-small);
    color: var(--danger);
    font-weight: 500;
}

.form-info {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-top: var(--space-sm);
    padding: var(--space-sm);
    background: rgba(6, 182, 212, 0.1);
    border-radius: var(--radius-small);
    font-size: var(--text-small);
    color: var(--accent-blue);
}

/* === BOUTONS D'ACTIONS === */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-md);
    padding-top: var(--space-lg);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    margin-top: var(--space-xl);
}

/* Utiliser les boutons du dashboard-moderne.css */
.btn-acasi {
    display: inline-flex;
    align-items: center;
    padding: var(--space-sm) var(--space-lg);
    border: none;
    border-radius: var(--radius-small);
    font-size: var(--text-normal);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    gap: var(--space-xs);
}

.btn-acasi.primary {
    background-color: var(--primary);
    color: white;
}

.btn-acasi.primary:hover {
    background-color: var(--secondary);
    transform: translateY(-1px);
    box-shadow: var(--shadow-elevated);
}

.btn-acasi.secondary {
    background-color: transparent;
    color: var(--primary);
    border: 1px solid var(--primary);
}

.btn-acasi.secondary:hover {
    background-color: var(--primary);
    color: white;
}

.recurrence-section {
    margin-top: var(--space-lg);
    padding: var(--space-lg);
    background: rgba(6, 182, 212, 0.05);
    border-radius: var(--radius-small);
    border: 1px solid rgba(6, 182, 212, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .session-types-container {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .session-type-card {
        flex-direction: row;
        justify-content: flex-start;
        text-align: left;
        padding: 1rem;
    }
    
    .session-type-icon {
        margin-right: 1rem;
        margin-bottom: 0;
        font-size: 1.5rem;
    }
    
    .context-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .availability-header-row,
    .availability-time-row {
        grid-template-columns: 50px repeat(7, 1fr);
    }
    
    .time-label,
    .availability-cell {
        padding: 4px;
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .session-types-container {
        gap: 0.5rem;
    }
    
    .session-type-card {
        padding: 0.75rem;
        min-width: auto;
    }
    
    .session-type-icon {
        font-size: 1.25rem;
        margin-right: 0.75rem;
    }
    
    .session-type-label {
        font-size: 0.875rem;
    }
}
</style>
@endpush
