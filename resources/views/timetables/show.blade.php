@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Détails de l\'emploi du temps') }}</h5>
                    <div>
                        <a href="{{ route('timetables.edit', $timetable) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> {{ __('Modifier') }}
                        </a>
                        <a href="{{ route('timetables.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> {{ __('Retour') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">{{ __('Informations générales') }}</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%">{{ __('Classe') }}</th>
                                                <td>{{ $timetable->class->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Section') }}</th>
                                                <td>{{ $timetable->section->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Matière') }}</th>
                                                <td>{{ $timetable->subject->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Enseignant') }}</th>
                                                <td>{{ $timetable->teacher->user->name ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">{{ __('Horaires') }}</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%">{{ __('Jour') }}</th>
                                                <td>
                                                    @switch($timetable->day_of_week)
                                                        @case('monday')
                                                            {{ __('Lundi') }}
                                                            @break
                                                        @case('tuesday')
                                                            {{ __('Mardi') }}
                                                            @break
                                                        @case('wednesday')
                                                            {{ __('Mercredi') }}
                                                            @break
                                                        @case('thursday')
                                                            {{ __('Jeudi') }}
                                                            @break
                                                        @case('friday')
                                                            {{ __('Vendredi') }}
                                                            @break
                                                        @case('saturday')
                                                            {{ __('Samedi') }}
                                                            @break
                                                        @case('sunday')
                                                            {{ __('Dimanche') }}
                                                            @break
                                                    @endswitch
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Heure de début') }}</th>
                                                <td>{{ \Carbon\Carbon::parse($timetable->start_time)->format('H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Heure de fin') }}</th>
                                                <td>{{ \Carbon\Carbon::parse($timetable->end_time)->format('H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Durée') }}</th>
                                                <td>{{ $timetable->getDurationInMinutes() }} minutes</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('Salle') }}</th>
                                                <td>{{ $timetable->room_number ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <form action="{{ route('timetables.destroy', $timetable) }}" method="POST" onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer cet emploi du temps ?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> {{ __('Supprimer') }}
                            </button>
                        </form>
                        <a href="{{ route('timetables.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Retour à la liste') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 