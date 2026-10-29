@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Modifier l\'emploi du temps') }}</h5>
                    <a href="{{ route('timetables.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> {{ __('Retour') }}
                    </a>
                </div>

                <div class="card-body">
                    <!-- Affichage des erreurs de validation -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Formulaire de modification -->
                    <form action="{{ route('timetables.update', $timetable) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Classe -->
                            <div class="col-md-6 mb-3">
                                <label for="class_id" class="form-label">{{ __('Classe') }} <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id" class="form-control @error('class_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Sélectionner une classe') }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ (old('class_id', $timetable->class_id) == $class->id) ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Section -->
                            <div class="col-md-6 mb-3">
                                <label for="section_id" class="form-label">{{ __('Section') }} <span class="text-danger">*</span></label>
                                <select name="section_id" id="section_id" class="form-control @error('section_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Sélectionner une section') }}</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" {{ (old('section_id', $timetable->section_id) == $section->id) ? 'selected' : '' }}>
                                            {{ $section->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('section_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Matière -->
                            <div class="col-md-6 mb-3">
                                <label for="subject_id" class="form-label">{{ __('Matière') }} <span class="text-danger">*</span></label>
                                <select name="subject_id" id="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Sélectionner une matière') }}</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ (old('subject_id', $timetable->subject_id) == $subject->id) ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Enseignant -->
                            <div class="col-md-6 mb-3">
                                <label for="teacher_id" class="form-label">{{ __('Enseignant') }} <span class="text-danger">*</span></label>
                                <select name="teacher_id" id="teacher_id" class="form-control @error('teacher_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Sélectionner un enseignant') }}</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ (old('teacher_id', $timetable->teacher_id) == $teacher->id) ? 'selected' : '' }}>
                                            {{ $teacher->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('teacher_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Jour de la semaine -->
                            <div class="col-md-4 mb-3">
                                <label for="day_of_week" class="form-label">{{ __('Jour') }} <span class="text-danger">*</span></label>
                                <select name="day_of_week" id="day_of_week" class="form-control @error('day_of_week') is-invalid @enderror" required>
                                    <option value="">{{ __('Sélectionner un jour') }}</option>
                                    <option value="monday" {{ (old('day_of_week', $timetable->day_of_week) == 'monday') ? 'selected' : '' }}>{{ __('Lundi') }}</option>
                                    <option value="tuesday" {{ (old('day_of_week', $timetable->day_of_week) == 'tuesday') ? 'selected' : '' }}>{{ __('Mardi') }}</option>
                                    <option value="wednesday" {{ (old('day_of_week', $timetable->day_of_week) == 'wednesday') ? 'selected' : '' }}>{{ __('Mercredi') }}</option>
                                    <option value="thursday" {{ (old('day_of_week', $timetable->day_of_week) == 'thursday') ? 'selected' : '' }}>{{ __('Jeudi') }}</option>
                                    <option value="friday" {{ (old('day_of_week', $timetable->day_of_week) == 'friday') ? 'selected' : '' }}>{{ __('Vendredi') }}</option>
                                    <option value="saturday" {{ (old('day_of_week', $timetable->day_of_week) == 'saturday') ? 'selected' : '' }}>{{ __('Samedi') }}</option>
                                    <option value="sunday" {{ (old('day_of_week', $timetable->day_of_week) == 'sunday') ? 'selected' : '' }}>{{ __('Dimanche') }}</option>
                                </select>
                                @error('day_of_week')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Heure de début -->
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label">{{ __('Heure de début') }} <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" id="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', \Carbon\Carbon::parse($timetable->start_time)->format('H:i')) }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Heure de fin -->
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">{{ __('Heure de fin') }} <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" id="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', \Carbon\Carbon::parse($timetable->end_time)->format('H:i')) }}" required>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Numéro de salle -->
                            <div class="col-md-12 mb-3">
                                <label for="room_number" class="form-label">{{ __('Numéro de salle') }}</label>
                                <input type="text" name="room_number" id="room_number" class="form-control @error('room_number') is-invalid @enderror" value="{{ old('room_number', $timetable->room_number) }}">
                                @error('room_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('Mettre à jour') }}
                            </button>
                            <a href="{{ route('timetables.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> {{ __('Annuler') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Script pour charger dynamiquement les sections en fonction de la classe sélectionnée
    $(document).ready(function() {
        $('#class_id').change(function() {
            var classId = $(this).val();
            if (classId) {
                $.ajax({
                    url: '/api/sections/by-class/' + classId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#section_id').empty();
                        $('#section_id').append('<option value="">{{ __("Sélectionner une section") }}</option>');
                        $.each(data, function(key, value) {
                            $('#section_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    }
                });
            } else {
                $('#section_id').empty();
                $('#section_id').append('<option value="">{{ __("Sélectionner une section") }}</option>');
            }
        });
    });
</script>
@endsection 