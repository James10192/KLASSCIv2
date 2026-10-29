@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Emploi du temps') }} - {{ $class->name }}</h5>
                    <div>
                        <a href="{{ route('timetables.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> {{ __('Retour') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Sélection de section -->
                    @if($sections->count() > 0)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="section_filter">{{ __('Filtrer par section') }}</label>
                                <select id="section_filter" class="form-control">
                                    <option value="all">{{ __('Toutes les sections') }}</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Emploi du temps par jour -->
                    <div class="timetable-container">
                        @php
                            $days = ['monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi', 'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche'];
                        @endphp

                        @foreach($days as $dayKey => $dayName)
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">{{ $dayName }}</h6>
                                </div>
                                <div class="card-body">
                                    @if(isset($timetableByDay[$dayKey]) && count($timetableByDay[$dayKey]) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Section') }}</th>
                                                        <th>{{ __('Horaire') }}</th>
                                                        <th>{{ __('Matière') }}</th>
                                                        <th>{{ __('Enseignant') }}</th>
                                                        <th>{{ __('Salle') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($timetableByDay[$dayKey] as $entry)
                                                        <tr class="section-row" data-section="{{ $entry->section_id }}">
                                                            <td>{{ $entry->section->name ?? 'Non définie' }}</td>
                                                            <td>{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</td>
                                                            <td>{{ $entry->subject->name ?? 'Non définie' }}</td>
                                                            <td>{{ $entry->teacher->user->name ?? 'Non défini' }}</td>
                                                            <td>{{ $entry->room_number ?? 'Non définie' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            {{ __('Aucun cours programmé pour ce jour.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Filtrage par section
        $('#section_filter').change(function() {
            var sectionId = $(this).val();

            if (sectionId === 'all') {
                $('.section-row').show();
            } else {
                $('.section-row').hide();
                $('.section-row[data-section="' + sectionId + '"]').show();
            }

            // Masquer les jours sans cours après filtrage
            $('.card').each(function() {
                var visibleRows = $(this).find('.section-row:visible').length;
                if (visibleRows === 0 && sectionId !== 'all') {
                    $(this).find('.alert').show();
                } else {
                    $(this).find('.alert').hide();
                }
            });
        });
    });
</script>
@endsection
