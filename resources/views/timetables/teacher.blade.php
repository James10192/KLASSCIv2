@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Emploi du temps de l\'enseignant') }} - {{ $teacher->user->name }}</h5>
                    <div>
                        <a href="{{ route('timetables.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> {{ __('Retour') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
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
                                                        <th>{{ __('Horaire') }}</th>
                                                        <th>{{ __('Classe') }}</th>
                                                        <th>{{ __('Section') }}</th>
                                                        <th>{{ __('Matière') }}</th>
                                                        <th>{{ __('Salle') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($timetableByDay[$dayKey] as $entry)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</td>
                                                            <td>{{ $entry->class->name }}</td>
                                                            <td>{{ $entry->section->name }}</td>
                                                            <td>{{ $entry->subject->name }}</td>
                                                            <td>{{ $entry->room_number ?? 'N/A' }}</td>
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

                    <!-- Résumé des heures de cours -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('Résumé des heures de cours') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Jour') }}</th>
                                            <th>{{ __('Nombre de cours') }}</th>
                                            <th>{{ __('Heures totales') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalHours = 0;
                                            $totalClasses = 0;
                                        @endphp
                                        @foreach($days as $dayKey => $dayName)
                                            @php
                                                $dayClasses = isset($timetableByDay[$dayKey]) ? count($timetableByDay[$dayKey]) : 0;
                                                $dayHours = 0;
                                                
                                                if (isset($timetableByDay[$dayKey])) {
                                                    foreach ($timetableByDay[$dayKey] as $entry) {
                                                        $start = \Carbon\Carbon::parse($entry->start_time);
                                                        $end = \Carbon\Carbon::parse($entry->end_time);
                                                        $dayHours += $start->diffInMinutes($end) / 60;
                                                    }
                                                }
                                                
                                                $totalClasses += $dayClasses;
                                                $totalHours += $dayHours;
                                            @endphp
                                            <tr>
                                                <td>{{ $dayName }}</td>
                                                <td>{{ $dayClasses }}</td>
                                                <td>{{ number_format($dayHours, 1) }} h</td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-primary">
                                            <th>{{ __('Total') }}</th>
                                            <th>{{ $totalClasses }}</th>
                                            <th>{{ number_format($totalHours, 1) }} h</th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 