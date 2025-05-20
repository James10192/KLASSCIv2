@extends('layouts.app')

@section('title', 'Modifier une séance')

@section('styles')
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
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Modifier une séance</h4>
                    <p class="card-subtitle mb-0">
                        Classe: {{ $emploiTemps->classe->name }} |
                        Filière: {{ $emploiTemps->classe->filiere->name }} |
                        Niveau: {{ $emploiTemps->classe->niveau->name }}
                    </p>
                </div>
                <div class="card-body">
                    <form action="{{ route('esbtp.seances-cours.update', $seancesCour->id) }}" method="POST" id="sessionForm">
                        @csrf
                        @method('PUT')

                        <!-- Session Type Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">Type de séance</h5>
                                <div class="row g-3">
                                    @foreach($sessionTypes as $type => $label)
                                    <div class="col-md-3">
                                        <div class="card session-type-card h-100 {{ $seancesCour->type === $type ? 'selected' : '' }}"
                                            data-type="{{ $type }}" onclick="selectSessionType('{{ $type }}')">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    @switch($type)
                                                        @case('course')
                                                            <i class="fas fa-chalkboard-teacher fa-2x" style="color: {{ $defaultColors[$type] }}"></i>
                                                            @break
                                                        @case('homework')
                                                            <i class="fas fa-book fa-2x" style="color: {{ $defaultColors[$type] }}"></i>
                                                            @break
                                                        @case('break')
                                                            <i class="fas fa-coffee fa-2x" style="color: {{ $defaultColors[$type] }}"></i>
                                                            @break
                                                        @case('lunch')
                                                            <i class="fas fa-utensils fa-2x" style="color: {{ $defaultColors[$type] }}"></i>
                                                            @break
                                                    @endswitch
                                                </div>
                                                <h6 class="mb-2">{{ $label }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <input type="hidden" name="type" id="sessionType" value="{{ old('type', $seancesCour->type) }}" required>
                            </div>
                        </div>

                        <!-- Common Fields -->
                        <div class="row">
                            <!-- Day Selection -->
                            <div class="col-md-6 mb-3">
                                <label for="jour" class="form-label">Jour</label>
                                <select name="jour" id="jour" class="form-select" required>
                                        <option value="">Sélectionner un jour</option>
                                    @foreach($joursSemaine as $value => $label)
                                        <option value="{{ $value }}" {{ old('jour', $seancesCour->jour) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                    </select>
                            </div>

                            <!-- Time Selection -->
                            <div class="col-md-3 mb-3">
                                <label for="heure_debut" class="form-label">Heure de début</label>
                                <input type="time" class="form-control" id="heure_debut" name="heure_debut"
                                    value="{{ old('heure_debut', $seancesCour->heure_debut->format('H:i')) }}" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="heure_fin" class="form-label">Heure de fin</label>
                                <input type="time" class="form-control" id="heure_fin" name="heure_fin"
                                    value="{{ old('heure_fin', $seancesCour->heure_fin->format('H:i')) }}" required>
                            </div>
                        </div>

                        <!-- Color and Recurrence -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Couleur</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color" id="color" class="color-picker"
                                        value="{{ old('color', $seancesCour->color) }}">
                                    <span class="ms-2 align-self-center" id="colorLabel">{{ $seancesCour->color }}</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring"
                                        {{ old('is_recurring', $seancesCour->is_recurring) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_recurring">Séance récurrente</label>
                                </div>
                                </div>
                            </div>

                        <!-- Recurrence Days -->
                        <div class="row mb-4" id="recurrenceDays" style="display: {{ $seancesCour->is_recurring ? 'block' : 'none' }};">
                            <div class="col-12">
                                <label class="form-label">Jours de récurrence</label>
                                <div class="recurrence-days">
                                    @foreach($joursSemaine as $value => $label)
                                        <div>
                                            <input type="checkbox" class="day-checkbox" name="recurrence_days[]"
                                                id="day_{{ $value }}" value="{{ $value }}"
                                                {{ in_array($value, old('recurrence_days', $seancesCour->recurrence_days ?? [])) ? 'checked' : '' }}>
                                            <label class="day-label" for="day_{{ $value }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Fields based on Session Type -->
                        <div id="courseFields" style="display: {{ in_array($seancesCour->type, ['course', 'homework']) ? 'block' : 'none' }};">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="teacher_id" class="form-label">Enseignant</label>
                                    <select name="teacher_id" id="teacher_id" class="form-select select2">
                                        <option value="">Sélectionner un enseignant</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ old('teacher_id', $seancesCour->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="matiere_id" class="form-label">Matière</label>
                                    <select name="matiere_id" id="matiere_id" class="form-select select2">
                                        <option value="">Sélectionner une matière</option>
                                        @foreach($matieres as $matiere)
                                            <option value="{{ $matiere->id }}" {{ old('matiere_id', $seancesCour->matiere_id) == $matiere->id ? 'selected' : '' }}>
                                                {{ $matiere->name }}
                                            </option>
                                        @endforeach
                                    </select>
                            </div>

                                <div class="col-md-4 mb-3">
                                    <label for="salle" class="form-label">Salle</label>
                                    <input type="text" class="form-control" id="salle" name="salle"
                                        value="{{ old('salle', $seancesCour->salle) }}">
                                </div>
                            </div>
                        </div>

                        <div id="homeworkFields" style="display: {{ $seancesCour->type === 'homework' ? 'block' : 'none' }};">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="homework_description" class="form-label">Description du devoir</label>
                                    <textarea class="form-control" id="homework_description" name="homework_description"
                                        rows="3">{{ old('homework_description', $seancesCour->homework_description) }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="homework_due_date" class="form-label">Date de remise</label>
                                    <input type="date" class="form-control" id="homework_due_date" name="homework_due_date"
                                        value="{{ old('homework_due_date', $seancesCour->homework_due_date?->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                                <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </a>
                                <button type="button" class="btn btn-danger float-end"
                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette séance ?')) {
                                        document.getElementById('delete-form').submit();
                                    }">
                                    <i class="fas fa-trash me-2"></i>Supprimer
                            </button>
                            </div>
                        </div>
                    </form>

                    <form id="delete-form" action="{{ route('esbtp.seances-cours.destroy', $seancesCour->id) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
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

    // Initialize the form with the current session type
    selectSessionType('{{ $seancesCour->type }}');
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

    // Update color picker with default color if no custom color is set
    const defaultColors = @json($defaultColors);
    const currentColor = document.getElementById('color').value;
    if (!currentColor || currentColor === '#000000') {
        document.getElementById('color').value = defaultColors[type];
        document.getElementById('colorLabel').textContent = defaultColors[type];
    }

    // Update required fields
    const teacherField = document.getElementById('teacher_id');
    const matiereField = document.getElementById('matiere_id');
    const salleField = document.getElementById('salle');
    const homeworkDescField = document.getElementById('homework_description');
    const homeworkDueDateField = document.getElementById('homework_due_date');

    if (type === 'course' || type === 'homework') {
        teacherField.required = true;
        matiereField.required = true;
        salleField.required = true;
                } else {
        teacherField.required = false;
        matiereField.required = false;
        salleField.required = false;
    }

    if (type === 'homework') {
        homeworkDescField.required = true;
        homeworkDueDateField.required = true;
                } else {
        homeworkDescField.required = false;
        homeworkDueDateField.required = false;
    }
}

// Form validation
document.getElementById('sessionForm').addEventListener('submit', function(e) {
    const type = document.getElementById('sessionType').value;
    if (!type) {
        e.preventDefault();
        alert('Veuillez sélectionner un type de séance');
        return;
    }

    const startTime = document.getElementById('heure_debut').value;
    const endTime = document.getElementById('heure_fin').value;
    if (startTime && endTime && startTime >= endTime) {
        e.preventDefault();
        alert('L\'heure de fin doit être postérieure à l\'heure de début');
        return;
    }
    });
</script>
@endpush
