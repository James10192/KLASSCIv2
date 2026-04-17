@extends('layouts.app')

@section('title', 'Modifier une séance - ' . ($emploiTemps->classe->name ?? 'Classe'))

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

@php
    $selectedRecurrenceDays = old('recurrence_days', $seancesCour->recurrence_days ?? []);
    if (is_string($selectedRecurrenceDays)) {
        $selectedRecurrenceDays = explode(',', $selectedRecurrenceDays);
    }
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier la Séance</h1>
                <p class="header-subtitle">
                    Classe : {{ $emploiTemps->classe->name ?? 'N/A' }} —
                    Filière : {{ $emploiTemps->classe->filiere->name ?? 'N/A' }} —
                    Niveau : {{ $emploiTemps->classe->niveau->name ?? 'N/A' }}
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à l'emploi du temps
                </a>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fs-4"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Une erreur est survenue</h5>
                        <p class="mb-0">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

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

        <form action="{{ route('esbtp.seances-cours.update', $seancesCour->id) }}" method="POST" id="sessionForm">
            @csrf
            @method('PUT')

            <div class="form-sections">
                <!-- Section 1: Type de séance -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-clipboard-list"></i>
                            Type de séance
                        </div>
                        <div class="main-card-subtitle">Sélectionnez le type de séance à modifier</div>
                    </div>
                    <div class="main-card-body">
                        <div class="session-types-container">
                            @foreach($sessionTypes as $type => $label)
                            @php
                                $isCurrentType = old('type', $seancesCour->type) === $type;
                            @endphp
                            <div
                                class="session-type-card {{ $isCurrentType ? 'selected' : 'locked' }}"
                                data-type="{{ $type }}"
                                data-label="{{ $label }}"
                                onclick="return selectSessionType('{{ $type }}')"
                                aria-disabled="{{ $isCurrentType ? 'false' : 'true' }}">
                                <div class="session-type-icon mb-3">
                                    @if($type === 'course')
                                        <i class="fas fa-chalkboard-teacher fa-2x session-type-color" data-color="{{ $defaultColors[$type] }}"></i>
                                    @elseif($type === 'homework')
                                        <i class="fas fa-clipboard-check fa-2x session-type-color" data-color="{{ $defaultColors[$type] }}"></i>
                                    @elseif($type === 'break')
                                        <i class="fas fa-coffee fa-2x session-type-color" data-color="{{ $defaultColors[$type] }}"></i>
                                    @else
                                        <i class="fas fa-utensils fa-2x session-type-color" data-color="{{ $defaultColors[$type] }}"></i>
                                    @endif
                                </div>
                                <div class="session-type-label">{{ $label }}</div>
                                @unless($isCurrentType)
                                    <span class="session-type-locked-badge">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                @endunless
                            </div>
                            @endforeach
                        </div>
                        <div id="typeChangeWarning" class="type-change-warning" role="note">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <div>
                                    Cette séance est de type <strong id="typeCurrentLabel">{{ $sessionTypes[$seancesCour->type] ?? 'Cours' }}</strong>.
                                </div>
                                <small class="type-change-instructions">
                                    Pour la transformer en <span id="typeChangeTarget">un autre type</span>, supprimez d'abord cette séance puis créez-en une nouvelle.
                                </small>
                            </div>
                        </div>
                        <input type="hidden" name="type" id="sessionType" value="{{ old('type', $seancesCour->type) }}" required>
                    </div>
                </div>

                <!-- Section 2: Informations temporelles -->
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
                                        <option value="{{ $value }}" {{ (string) old('jour', $seancesCour->jour) === (string) $value ? 'selected' : '' }}>
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
                                       value="{{ old('heure_debut', optional($seancesCour->heure_debut)->format('H:i')) }}" required>
                                @error('heure_debut')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="heure_fin" class="form-label">Heure de fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-input @error('heure_fin') error @enderror"
                                       id="heure_fin" name="heure_fin"
                                       value="{{ old('heure_fin', optional($seancesCour->heure_fin)->format('H:i')) }}" required>
                                @error('heure_fin')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="form-options mt-3">
                            <div class="form-check-custom">
                                <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring"
                                    {{ old('is_recurring', $seancesCour->is_recurring) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_recurring">
                                    <i class="fas fa-repeat me-2"></i>Séance récurrente
                                </label>
                            </div>
                        </div>

                        <div id="recurrenceDays" class="recurrence-section mt-3" style="display: none;">
                            <label class="form-label">Jours de récurrence</label>
                            <div class="recurrence-days">
                                @foreach($joursSemaine as $value => $label)
                                    <div>
                                        <input type="checkbox" class="day-checkbox" name="recurrence_days[]"
                                            id="day_{{ $value }}" value="{{ $value }}"
                                            {{ in_array($value, (array) $selectedRecurrenceDays, true) ? 'checked' : '' }}>
                                        <label class="day-label" for="day_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Matière et Enseignant -->
                <div class="main-card" id="courseFields" style="display: none;">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-graduation-cap"></i>
                            Matière et Enseignant
                        </div>
                        <div class="main-card-subtitle">Configuration pédagogique de la séance</div>
                    </div>
                    <div class="main-card-body">
                        @if(($planificationData['planifications_configurees'] ?? false))
                            <div class="context-card mb-4">
                                <div class="context-header">
                                    <i class="fas fa-school"></i>
                                    <span>{{ $emploiTemps->classe->name ?? 'Classe' }}</span>
                                </div>
                                <div class="context-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Filière</span>
                                        <span class="stat-value">{{ $emploiTemps->classe->filiere->name ?? '---' }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Niveau</span>
                                        <span class="stat-value">{{ $emploiTemps->classe->niveau->name ?? '---' }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Matières</span>
                                        <span class="stat-value">{{ $matieres->count() }} configurées</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Volume</span>
                                        <span class="stat-value">{{ $planificationData['heures_totales'] ?? 0 }}h planifiées</span>
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
                                                    {{ (string) old('matiere_id', $seancesCour->matiere_id) === (string) $matiere['matiere']->id ? 'selected' : '' }}>
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
                                           id="salle" name="salle"
                                           value="{{ old('salle', $seancesCour->salle) }}"
                                           placeholder="Ex: Salle A101">
                                    @error('salle')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div id="teacher-availability" class="availability-section mt-4" style="display: none;">
                                <div class="availability-header d-flex align-items-center gap-2 mb-3">
                                    <i class="fas fa-calendar-check text-primary"></i>
                                    <span>Disponibilités de <strong id="selected-teacher-name">l'enseignant</strong></span>
                                </div>
                                <div class="availability-legend mb-3">
                                    <div class="legend-items">
                                        <div class="legend-item">
                                            <div class="legend-color preferred"><i class="fas fa-star"></i></div>
                                            <span>Préféré</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color available"><i class="fas fa-check"></i></div>
                                            <span>Disponible</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color unavailable"><i class="fas fa-ban"></i></div>
                                            <span>Non disponible</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color occupied"><i class="fas fa-lock"></i></div>
                                            <span>Occupé (autre séance)</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-color selected-time"><i class="fas fa-bullseye"></i></div>
                                            <span>Créneaux sélectionnés</span>
                                        </div>
                                    </div>
                                    <div class="availability-hint">
                                        <i class="fas fa-mouse-pointer"></i>
                                        Cliquez puis glissez sur la grille pour définir un créneau.
                                    </div>
                                </div>
                                <div class="availability-grid-container">
                                    <div id="availability-inline-error" class="availability-inline-error" style="display: none;"></div>
                                    <div class="teacher-availability-grid" id="availability-grid"></div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-exclamation-triangle fs-4"></i>
                                    </div>
                                    <div>
                                        <h6>Planification académique requise</h6>
                                        <p class="mb-2">{{ $planificationData['message_configuration'] ?? 'Aucune planification configurée pour cette classe.' }}</p>
                                        @if($planificationData['lien_configuration'] ?? false)
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

                <!-- Section 4: Informations spécifiques -->
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
                                <textarea class="form-textarea @error('homework_description') error @enderror"
                                          id="homework_description" name="homework_description" rows="4"
                                          placeholder="Décrivez le devoir à donner...">{{ old('homework_description', $seancesCour->homework_description) }}</textarea>
                                @error('homework_description')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="homework_due_date" class="form-label">Date de remise</label>
                                <input type="date" class="form-input @error('homework_due_date') error @enderror"
                                       id="homework_due_date" name="homework_due_date"
                                       value="{{ old('homework_due_date', optional($seancesCour->homework_due_date)->format('Y-m-d')) }}">
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

                <!-- Section 5: Informations complémentaires -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-info-circle"></i>
                            Détails supplémentaires
                        </div>
                        <div class="main-card-subtitle">Informations facultatives concernant la séance</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="description" class="form-label">Détails internes</label>
                                <textarea class="form-textarea @error('description') error @enderror"
                                          id="description" name="description" rows="3"
                                          placeholder="Notes internes, prérequis, matériel requis...">{{ old('description', $seancesCour->description) }}</textarea>
                                @error('description')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="priority" class="form-label">Priorité</label>
                                <input type="number" class="form-input @error('priority') error @enderror"
                                       id="priority" name="priority" min="1" max="5"
                                       value="{{ old('priority', $seancesCour->priority ?? 1) }}"
                                       placeholder="1 (faible) à 5 (forte)">
                                @error('priority')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>
                        Enregistrer les modifications
                    </button>
                    <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-times"></i>
                        Annuler
                    </a>
                </div>
            </div>
        </form>

        <!-- Danger Zone -->
        <div class="main-card danger-zone mt-4">
            <div class="main-card-header">
                <div class="main-card-title text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Zone dangereuse
                </div>
                <div class="main-card-subtitle text-danger">
                    Supprimer définitivement cette séance du planning
                </div>
            </div>
            <div class="main-card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h6 class="text-danger mb-1">Suppression définitive</h6>
                    <p class="mb-0 text-muted">
                        Cette action supprimera la séance de l'emploi du temps. Les évaluations liées seront conservées.
                    </p>
                </div>
                <button type="button" class="btn btn-danger"
                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer définitivement cette séance ?')) { document.getElementById('delete-form').submit(); }">
                    <i class="fas fa-trash-alt me-2"></i>Supprimer la séance
                </button>
            </div>
        </div>

        <form id="delete-form" action="{{ route('esbtp.seances-cours.destroy', $seancesCour->id) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<div id="seance-data"
     data-default-colors='@json($defaultColors)'
     data-session-types='@json($sessionTypes)'
     data-teachers='@json($teachers->keyBy("id"))'
     data-availability='@json($availabilityData ?? [])'
     style="display: none;"></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
const currentTeacherId = "{{ old('teacher_id', $seancesCour->teacher_id) }}";
const initialSessionType = "{{ old('type', $seancesCour->type) }}";
const seanceDataElement = document.getElementById('seance-data');
const seanceData = seanceDataElement
    ? {
        defaultColors: JSON.parse(seanceDataElement.dataset.defaultColors || '{}'),
        sessionTypeLabels: JSON.parse(seanceDataElement.dataset.sessionTypes || '{}'),
        teachers: JSON.parse(seanceDataElement.dataset.teachers || '{}'),
        availability: JSON.parse(seanceDataElement.dataset.availability || '{}')
    }
    : { defaultColors: {}, sessionTypeLabels: {}, teachers: {}, availability: {} };
const defaultColors = seanceData.defaultColors;
const sessionTypeLabels = seanceData.sessionTypeLabels;
const originalSessionType = initialSessionType || 'course';
const teachersById = seanceData.teachers;
const availabilityData = seanceData.availability;

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.session-type-color').forEach(icon => {
        const color = icon.dataset.color;
        if (color) {
            icon.style.color = color;
        }
    });

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

    const recurrenceWrapper = document.getElementById('recurrenceDays');
    const recurrenceCheckbox = document.getElementById('is_recurring');
    if (recurrenceCheckbox && recurrenceWrapper) {
        recurrenceWrapper.style.display = recurrenceCheckbox.checked ? 'block' : 'none';
        recurrenceCheckbox.addEventListener('change', function() {
            recurrenceWrapper.style.display = this.checked ? 'block' : 'none';
        });
    }

    const typeChangeWarning = document.getElementById('typeChangeWarning');
    const typeChangeTarget = document.getElementById('typeChangeTarget');
    const typeCurrentLabel = document.getElementById('typeCurrentLabel');

    if (typeCurrentLabel && sessionTypeLabels[originalSessionType]) {
        typeCurrentLabel.textContent = sessionTypeLabels[originalSessionType];
    }
    if (typeChangeTarget) {
        typeChangeTarget.textContent = 'un autre type';
    }
    if (typeChangeWarning) {
        typeChangeWarning.classList.remove('active');
    }

    const initialType = originalSessionType;
    selectSessionType(initialType, { force: true });
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

function selectSessionType(type, options = {}) {
    const { force = false } = options;

    if (!force && type !== originalSessionType) {
        showTypeChangeWarning(type);
        return false;
    }

    // Update hidden input
    document.getElementById('sessionType').value = type;

    // Update UI
    document.querySelectorAll('.session-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    const selectedCard = document.querySelector(`[data-type="${type}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }

    // Show/hide relevant fields
    document.getElementById('courseFields').style.display =
        (type === 'course' || type === 'homework') ? 'block' : 'none';
    document.getElementById('homeworkFields').style.display =
        (type === 'homework') ? 'block' : 'none';

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
    return true;
}

function showTypeChangeWarning(targetType) {
    const warning = document.getElementById('typeChangeWarning');
    const targetSpan = document.getElementById('typeChangeTarget');

    if (targetSpan) {
        const label = sessionTypeLabels[targetType] || 'un autre type';
        targetSpan.textContent = label.toLowerCase();
    }

    if (warning) {
        warning.classList.add('active');
        setTimeout(() => warning.classList.remove('active'), 2500);
    } else {
        showAvailabilityToast('Pour changer le type de cette séance, supprimez-la puis créez une nouvelle séance du type souhaité.', 'warning');
    }
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
    document.querySelectorAll('.availability-cell.selecting').forEach(cell => {
        cell.classList.remove('selecting');
    });
    clearAvailabilityErrors();
    
    // Si tous les champs sont remplis, surligner les créneaux
    if (jourSelect.value && heureDebut.value && heureFin.value) {
        const selectedDay = parseInt(jourSelect.value);
        const startHour = parseInt(heureDebut.value.split(':')[0]);
        const endHour = parseInt(heureFin.value.split(':')[0]);
        const dayColumnIndex = getDayColumnIndex(selectedDay);
        if (dayColumnIndex !== undefined) {
            // Parcourir chaque ligne d'heure pour surligner les cellules correspondantes
            for (let hour = startHour; hour < endHour; hour++) {
                if (hour >= 8 && hour < 18) {
                    const rowIndex = hour - 8; // 8h = row 0
                    const cell = getAvailabilityCell(selectedDay, hour);
                    if (cell) {
                        cell.classList.add('selected-time');
                    }
                }
            }
        }
    }

    updateHomeworkTimingInfo();
    previewTeacherAvailability();
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

function showAvailabilityToast(message, type = 'danger') {
    const containerId = 'availability-toast-container';
    let container = document.getElementById(containerId);
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1080';
        document.body.appendChild(container);
    }

    const toastId = `availability-toast-${Date.now()}`;
    const safeMessage = message.replace(/\n/g, '<br>');
    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${safeMessage}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 6000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function showFormError(message) {
    showAvailabilityToast(message, 'danger');
    debugAlert(message);
}

function setAvailabilityErrorMessage(message) {
    const inlineError = document.getElementById('availability-inline-error');
    if (!inlineError) {
        return;
    }

    if (!message) {
        inlineError.textContent = '';
        inlineError.style.display = 'none';
        return;
    }

    inlineError.textContent = message.replace(/\n/g, ' ');
    inlineError.style.display = 'flex';
}

function getDayColumnIndex(dayNumber) {
    const dayMapping = {
        1: 0,
        2: 1,
        3: 2,
        4: 3,
        5: 4,
        6: 5
    };
    return dayMapping[dayNumber];
}

function getAvailabilityCell(dayNumber, hour) {
    const dayColumnIndex = getDayColumnIndex(dayNumber);
    if (dayColumnIndex === undefined) {
        return null;
    }

    const rowIndex = hour - 8;
    const timeRows = document.querySelectorAll('.availability-time-row');
    if (!timeRows[rowIndex]) {
        return null;
    }

    const cells = timeRows[rowIndex].querySelectorAll('.availability-cell');
    return cells[dayColumnIndex] || null;
}

function clearAvailabilityErrors() {
    document.querySelectorAll('.availability-cell.error').forEach(cell => {
        cell.classList.remove('error');
    });
    setAvailabilityErrorMessage('');
}

function markAvailabilityError(dayNumber, hour, message) {
    const cell = getAvailabilityCell(dayNumber, hour);
    if (!cell) {
        return;
    }

    if (message) {
        cell.dataset.error = message;
        cell.setAttribute('title', message);
    }
    cell.classList.add('error');
    setTimeout(() => {
        cell.classList.remove('error');
        if (message) {
            delete cell.dataset.error;
            cell.removeAttribute('title');
        }
    }, 3500);
}

function markAvailabilityErrorRange(dayNumber, startHour, endHour, message) {
    for (let hour = startHour; hour < endHour; hour++) {
        markAvailabilityError(dayNumber, hour, message);
    }
}

let availabilitySelection = {
    active: false,
    day: null,
    startHour: null,
    endHour: null,
};

function bindAvailabilityGridHandlers() {
    const grid = document.getElementById('availability-grid');
    if (!grid || grid.dataset.bound === 'true') {
        return;
    }

    grid.dataset.bound = 'true';
    grid.addEventListener('mousedown', handleAvailabilityMouseDown);
    grid.addEventListener('mouseover', handleAvailabilityMouseOver);
    document.addEventListener('mouseup', handleAvailabilityMouseUp);
}

function handleAvailabilityMouseDown(event) {
    const cell = event.target.closest('.availability-cell');
    if (!cell) {
        return;
    }

    const status = cell.dataset.status;
    const day = parseInt(cell.dataset.day, 10);
    const hour = parseInt(cell.dataset.hour, 10);
    if (Number.isNaN(day) || Number.isNaN(hour)) {
        return;
    }

    clearAvailabilityErrors();

    if (status === 'unavailable' || status === 'occupied') {
        const errorMessage = status === 'occupied'
            ? 'Ce créneau est déjà occupé. Choisissez un autre horaire.'
            : 'Ce créneau est indisponible. Choisissez un autre horaire.';
        markAvailabilityError(day, hour, errorMessage);
        setAvailabilityErrorMessage(errorMessage);
        return;
    }

    availabilitySelection = {
        active: true,
        day,
        startHour: hour,
        endHour: hour,
    };

    updateAvailabilitySelectionPreview();
    event.preventDefault();
}

function handleAvailabilityMouseOver(event) {
    if (!availabilitySelection.active) {
        return;
    }

    const cell = event.target.closest('.availability-cell');
    if (!cell) {
        return;
    }

    const day = parseInt(cell.dataset.day, 10);
    const hour = parseInt(cell.dataset.hour, 10);
    if (Number.isNaN(day) || Number.isNaN(hour) || day !== availabilitySelection.day) {
        return;
    }

    availabilitySelection.endHour = hour;
    updateAvailabilitySelectionPreview();
}

function handleAvailabilityMouseUp() {
    if (!availabilitySelection.active) {
        return;
    }

    const selectedDay = availabilitySelection.day;
    const startHour = Math.min(availabilitySelection.startHour, availabilitySelection.endHour);
    const endHour = Math.max(availabilitySelection.startHour, availabilitySelection.endHour) + 1;

    availabilitySelection = {
        active: false,
        day: null,
        startHour: null,
        endHour: null,
    };

    finalizeAvailabilitySelection(selectedDay, startHour, endHour);
}

function updateAvailabilitySelectionPreview() {
    document.querySelectorAll('.availability-cell.selecting').forEach(cell => {
        cell.classList.remove('selecting');
    });

    if (!availabilitySelection.active) {
        return;
    }

    const startHour = Math.min(availabilitySelection.startHour, availabilitySelection.endHour);
    const endHour = Math.max(availabilitySelection.startHour, availabilitySelection.endHour) + 1;

    for (let hour = startHour; hour < endHour; hour++) {
        const cell = getAvailabilityCell(availabilitySelection.day, hour);
        if (cell) {
            cell.classList.add('selecting');
        }
    }
}

function finalizeAvailabilitySelection(day, startHour, endHour) {
    const invalidCells = [];
    for (let hour = startHour; hour < endHour; hour++) {
        const cell = getAvailabilityCell(day, hour);
        if (!cell) {
            continue;
        }

        const status = cell.dataset.status;
        if (status === 'unavailable' || status === 'occupied') {
            invalidCells.push({ hour, status });
        }
    }

    if (invalidCells.length > 0) {
        invalidCells.forEach(({ hour, status }) => {
            const errorMessage = status === 'occupied'
                ? 'Ce créneau est déjà occupé. Choisissez un autre horaire.'
                : 'Ce créneau est indisponible. Choisissez un autre horaire.';
            markAvailabilityError(day, hour, errorMessage);
            setAvailabilityErrorMessage(errorMessage);
        });
        return;
    }

    const jourSelect = document.getElementById('jour');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');
    if (!jourSelect || !heureDebut || !heureFin) {
        return;
    }

    jourSelect.value = day.toString();
    heureDebut.value = `${startHour.toString().padStart(2, '0')}:00`;
    heureFin.value = `${endHour.toString().padStart(2, '0')}:00`;

    jourSelect.dispatchEvent(new Event('change'));
    heureDebut.dispatchEvent(new Event('change'));
    heureFin.dispatchEvent(new Event('change'));
    updateSelectedTimeInGrid();
}

// Form validation
document.getElementById('sessionForm').addEventListener('submit', function(e) {
    const type = document.getElementById('sessionType').value;
    if (!type) {
        e.preventDefault();
        showFormError('Veuillez sélectionner un type de séance');
        return;
    }

    const startTime = document.getElementById('heure_debut').value;
    const endTime = document.getElementById('heure_fin').value;
    if (startTime && endTime && startTime >= endTime) {
        e.preventDefault();
        showFormError('L\'heure de fin doit être postérieure à l\'heure de début');
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
    
    clearAvailabilityErrors();

    if (!availabilityData[teacherId]) {
        showFormError('Aucune disponibilité configurée pour cet enseignant.\n\nVeuillez configurer ses disponibilités avant de programmer cette séance.');
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
        const errorMessage = 'L\'enseignant n\'est pas disponible ce jour-là.\n\nVeuillez choisir un autre jour ou un autre enseignant.';
        markAvailabilityErrorRange(selectedDay, startHour, endHour, errorMessage);
        showFormError(errorMessage);
        setAvailabilityErrorMessage(errorMessage);
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
                const errorMessage = `L'enseignant n'est pas disponible ${jourNoms[selectedDay]} à ${hour}:00.\n\nVeuillez ajuster les horaires ou choisir un autre enseignant.`;
                markAvailabilityError(selectedDay, hour, errorMessage);
                showFormError(errorMessage);
                setAvailabilityErrorMessage(errorMessage);
                return false;
            } else if (status === 'occupied') {
                const errorMessage = `L'enseignant a déjà une séance programmée ${jourNoms[selectedDay]} à ${hour}:00 dans un autre emploi du temps.\n\nVeuillez choisir un autre créneau.`;
                markAvailabilityError(selectedDay, hour, errorMessage);
                showFormError(errorMessage);
                setAvailabilityErrorMessage(errorMessage);
                return false;
            }
        }
    }
    
    return true; // Tout est OK
}

function previewTeacherAvailability() {
    const teacherSelect = document.getElementById('teacher_id');
    const jourSelect = document.getElementById('jour');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');
    const currentType = document.getElementById('sessionType').value;

    if (currentType !== 'course') {
        setAvailabilityErrorMessage('');
        return;
    }

    if (!teacherSelect.value || !jourSelect.value || !heureDebut.value || !heureFin.value) {
        setAvailabilityErrorMessage('');
        return;
    }

    const teacherId = teacherSelect.value;
    const selectedDay = parseInt(jourSelect.value);
    const startHour = parseInt(heureDebut.value.split(':')[0]);
    const endHour = parseInt(heureFin.value.split(':')[0]);
    const availabilityData = seanceData.availability;

    clearAvailabilityErrors();

    if (!availabilityData[teacherId]) {
        const errorMessage = 'Aucune disponibilité configurée pour cet enseignant.\n\nVeuillez configurer ses disponibilités avant de programmer cette séance.';
        markAvailabilityErrorRange(selectedDay, startHour, endHour, errorMessage);
        setAvailabilityErrorMessage(errorMessage);
        return;
    }

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
        const errorMessage = 'L\'enseignant n\'est pas disponible ce jour-là.\n\nVeuillez choisir un autre jour ou un autre enseignant.';
        markAvailabilityErrorRange(selectedDay, startHour, endHour, errorMessage);
        setAvailabilityErrorMessage(errorMessage);
        return;
    }

    const teacherDayAvailability = availabilityData[teacherId][dayKey];
    const jourNoms = {1: 'lundi', 2: 'mardi', 3: 'mercredi', 4: 'jeudi', 5: 'vendredi', 6: 'samedi'};

    for (let hour = startHour; hour < endHour; hour++) {
        const hourIndex = hour - 8;
        if (hourIndex >= 0 && hourIndex < teacherDayAvailability.length) {
            const status = teacherDayAvailability[hourIndex];
            if (status === 'unavailable') {
                const errorMessage = `L'enseignant n'est pas disponible ${jourNoms[selectedDay]} à ${hour}:00.\n\nVeuillez ajuster les horaires ou choisir un autre enseignant.`;
                markAvailabilityError(selectedDay, hour, errorMessage);
                setAvailabilityErrorMessage(errorMessage);
                return;
            }

            if (status === 'occupied') {
                const errorMessage = `L'enseignant a déjà une séance programmée ${jourNoms[selectedDay]} à ${hour}:00 dans un autre emploi du temps.\n\nVeuillez choisir un autre créneau.`;
                markAvailabilityError(selectedDay, hour, errorMessage);
                setAvailabilityErrorMessage(errorMessage);
                return;
            }
        }
    }

    setAvailabilityErrorMessage('');
}

// Fonction pour mettre à jour les enseignants selon la matière sélectionnée
function updateTeachersForSubject() {
    const matiereSelect = document.getElementById('matiere_id');
    const teacherSelect = document.getElementById('teacher_id');
    const matiereInfo = document.getElementById('matiere-info');
    const heuresRestantesText = document.getElementById('heures-restantes-text');
    const teacherInfo = document.getElementById('teacher-info');
    const teacherAvailability = document.getElementById('teacher-availability');
    const teacherGroup = document.getElementById('teacherFieldGroup');
    const currentType = document.getElementById('sessionType').value;
    const requiresTeacher = currentType === 'course';
    
    if (teacherGroup) {
        teacherGroup.style.display = requiresTeacher ? 'block' : 'none';
    }

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

        const addedTeacherIds = new Set();
        const formatTeacherName = (teacher) => {
            if (!teacher) {
                return null;
            }
            return teacher?.user?.name
                ?? teacher?.name
                ?? teacher?.matricule
                ?? `Enseignant ${teacher.id ?? ''}`;
        };

        const pushTeacherOption = (teacherId, { highlightCurrent = false } = {}) => {
            if (!teacherId || addedTeacherIds.has(teacherId)) {
                return;
            }
            const teacher = teachersById[teacherId];
            const teacherName = formatTeacherName(teacher);
            if (!teacherName) {
                return;
            }

            let label = teacherName;
            if (highlightCurrent) {
                label += ' (enseignant actuel)';
            }

            const option = new Option(label, teacherId);
            if (currentTeacherId && teacherId === currentTeacherId) {
                option.selected = true;
            }

            teacherSelect.add(option);
            addedTeacherIds.add(teacherId);
        };

        if (enseignantsIds.length > 0) {
            enseignantsIds.forEach(rawId => {
                const teacherId = rawId?.toString();
                if (teacherId && teachersById[teacherId]) {
                    pushTeacherOption(teacherId);
                }
            });
        } else {
            Object.keys(teachersById || {}).forEach(teacherId => {
                pushTeacherOption(teacherId);
            });
        }

        if (currentTeacherId && !addedTeacherIds.has(currentTeacherId) && teachersById[currentTeacherId]) {
            pushTeacherOption(currentTeacherId, { highlightCurrent: true });
        }

        if (teacherSelect.options.length <= 1) {
            teacherSelect.innerHTML = '<option value="">Aucun enseignant disponible pour cette matière</option>';
        }
    } else {
        matiereInfo.style.display = 'none';
        teacherSelect.innerHTML = requiresTeacher
            ? '<option value="">Sélectionner d\'abord une matière</option>'
            : '<option value="">Aucun enseignant requis pour un devoir</option>';
        if (teacherInfo) {
            teacherInfo.style.display = 'none';
        }
    }
    
    // Reset teacher availability
    if (teacherAvailability) {
        teacherAvailability.style.display = 'none';
    }
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
                
                dayKeys.forEach((dayKey, dayIndex) => {
                    let cellClass = 'unavailable';
                    let cellTitle = 'Non disponible';
                    let cellStatus = 'unavailable';

                    // Le format est: rawAvailability[dayKey][hourIndex] = 'available'/'preferred'/'unavailable'/'occupied'
                    if (rawAvailability[dayKey]) {
                        const hourIndex = hour - 8; // 8h = index 0
                        if (hourIndex >= 0 && hourIndex < rawAvailability[dayKey].length) {
                            const status = rawAvailability[dayKey][hourIndex];
                            if (status === 'occupied') {
                                cellClass = 'occupied';
                                cellTitle = 'Occupé par une autre séance';
                                cellStatus = 'occupied';
                            } else if (status === 'preferred') {
                                cellClass = 'preferred';
                                cellTitle = 'Préféré';
                                cellStatus = 'preferred';
                            } else if (status === 'available') {
                                cellClass = 'available';
                                cellTitle = 'Disponible';
                                cellStatus = 'available';
                            }
                        }
                    }

                    gridHtml += `<div class="availability-cell ${cellClass}" data-day="${dayIndex + 1}" data-hour="${hour}" data-status="${cellStatus}" title="${cellTitle}"></div>`;
                });
                gridHtml += '</div>';
            }
            
            availabilityGrid.innerHTML = gridHtml;
            teacherAvailability.style.display = 'block';
            bindAvailabilityGridHandlers();
            
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

.session-type-card.locked {
    cursor: not-allowed;
    opacity: 0.55;
    position: relative;
}

.session-type-card.locked:hover {
    background: var(--surface);
    border-color: rgba(0, 0, 0, 0.1);
    transform: none;
    box-shadow: var(--shadow-card);
}

.session-type-card.locked .session-type-icon,
.session-type-card.locked .session-type-label {
    color: var(--text-secondary);
}

.session-type-locked-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: var(--radius-circle);
    background: rgba(30, 64, 175, 0.12);
    color: var(--primary);
    font-size: 0.85rem;
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

.type-change-warning {
    display: flex;
    align-items: flex-start;
    gap: var(--space-sm);
    background: rgba(30, 64, 175, 0.08);
    border: 1px solid rgba(30, 64, 175, 0.2);
    border-radius: var(--radius-small);
    padding: var(--space-sm) var(--space-md);
    color: var(--primary);
    margin-top: var(--space-lg);
}

.type-change-warning.active {
    background: rgba(30, 64, 175, 0.12);
    border-color: var(--primary);
}

.type-change-warning i {
    margin-top: 2px;
}

.type-change-instructions {
    color: var(--text-secondary);
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
    background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.availability-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.availability-header i {
    color: var(--primary);
    background: rgba(59, 130, 246, 0.12);
    padding: 0.4rem;
    border-radius: 999px;
}

.teacher-availability-grid {
    background: #fff;
    border-radius: 12px;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
}

.availability-header-row,
.availability-time-row {
    display: grid;
    grid-template-columns: 60px repeat(6, 1fr);
    gap: 6px;
    margin-bottom: 6px;
}

.time-header,
.day-header,
.time-label,
.availability-cell {
    padding: 6px;
    text-align: center;
    font-size: 0.78rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.time-header,
.day-header {
    background: #f1f5f9;
    font-weight: 700;
    color: var(--text-primary);
}

.time-label {
    background: #f8fafc;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.74rem;
}

.availability-cell {
    height: 34px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: #fff;
    background: #f8fafc;
    border-color: #e2e8f0;
}

.availability-cell::after {
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 0.65rem;
    opacity: 0.9;
}

.availability-cell.available {
    background: #22c55e;
    border-color: #16a34a;
}

.availability-cell.available::after {
    content: '\f00c';
}

.availability-cell.available:hover {
    background: #16a34a;
    transform: translateY(-1px) scale(1.04);
}

.availability-cell.preferred {
    background: #2563eb;
    border-color: #1d4ed8;
}

.availability-cell.preferred::after {
    content: '\f005';
}

.availability-cell.preferred:hover {
    background: #1d4ed8;
    transform: translateY(-1px) scale(1.04);
}

.availability-cell.unavailable {
    background: #ef4444;
    border-color: #dc2626;
    cursor: not-allowed;
}

.availability-cell.unavailable::after {
    content: '\f05e';
}

.availability-cell.unavailable:hover {
    background: #dc2626;
}

.availability-cell.occupied {
    background: #64748b;
    border-color: #475569;
    cursor: not-allowed;
}

.availability-cell.occupied::after {
    content: '\f023';
}

.availability-cell.occupied:hover {
    background: #475569;
    transform: translateY(-1px) scale(1.02);
}

.availability-cell.selected-time {
    position: relative;
    animation: selectedPulse 1.8s infinite;
    transform: translateY(-1px);
    z-index: 1;
}

.availability-cell.selected-time::before {
    content: '';
    position: absolute;
    inset: 2px;
    border: 2px solid rgba(245, 158, 11, 0.9);
    border-radius: 6px;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
    z-index: 1;
}

.availability-cell.selected-time:hover::before {
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.4);
}

.availability-cell.selected-time::after {
    content: '\f140';
    color: #92400e;
}

.availability-cell.error {
    background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
    border-color: #dc2626;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.35);
    animation: cellShake 0.35s ease-in-out 2;
    z-index: 2;
}

.availability-cell.error[data-error]::before {
    content: attr(data-error);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: #fff;
    font-size: 0.7rem;
    line-height: 1.3;
    padding: 0.35rem 0.55rem;
    border-radius: 6px;
    width: max-content;
    max-width: 220px;
    white-space: normal;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    z-index: 3;
}

.availability-cell.error[data-error]::after {
    content: '\f071';
}

.availability-cell.error[data-error]:hover::before {
    opacity: 1;
}

.availability-cell.selecting {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.45);
    transform: translateY(-1px) scale(1.02);
    z-index: 1;
}

.availability-inline-error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding: 0.65rem 0.85rem;
    border-radius: 10px;
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid rgba(185, 28, 28, 0.2);
    font-size: 0.85rem;
    font-weight: 600;
}

.availability-inline-error::before {
    content: '\f071';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}

.availability-hint {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    font-size: 0.8rem;
    color: var(--text-secondary);
}

@keyframes selectedPulse {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45); }
    70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

@keyframes cellShake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-2px); }
    40% { transform: translateX(2px); }
    60% { transform: translateX(-2px); }
    80% { transform: translateX(2px); }
}

.no-availability {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
    font-style: italic;
}

/* Styles pour la légende des disponibilités */
.availability-legend {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
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
    gap: 1.25rem;
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
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.legend-color i {
    font-size: 0.6rem;
}

.legend-color.preferred {
    background: #2563eb;
    border-color: #1d4ed8;
}

.legend-color.available {
    background: #22c55e;
    border-color: #16a34a;
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
    background: #64748b;
    border-color: #475569;
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
