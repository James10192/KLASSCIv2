@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Rapport des présences enseignants</h2>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('esbtp.attendance-codes.report') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                           value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                           value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4">
                    <label for="enseignant_id" class="form-label">Enseignant</label>
                    <select class="form-select" id="enseignant_id" name="enseignant_id">
                        <option value="">Tous les enseignants</option>
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}"
                                {{ request('enseignant_id') == $enseignant->id ? 'selected' : '' }}>
                                {{ $enseignant->nom }} {{ $enseignant->prenoms }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('esbtp.attendance-codes.report') }}" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des présences</h5>
            <a href="{{ route('esbtp.attendance-codes.report', array_merge(request()->all(), ['format' => 'excel'])) }}"
               class="btn btn-success">
                Exporter Excel
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Enseignant</th>
                            <th>Heure d'arrivée</th>
                            <th>Statut</th>
                            <th>IP</th>
                            <th>Appareil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->date->format('d/m/Y') }}</td>
                            <td>{{ $attendance->enseignant->nom }} {{ $attendance->enseignant->prenoms }}</td>
                            <td>{{ $attendance->time_in->format('H:i') }}</td>
                            <td>
                                @if($attendance->status === 'present')
                                    <span class="badge bg-success">Présent</span>
                                @elseif($attendance->status === 'late')
                                    <span class="badge bg-warning">En retard</span>
                                @else
                                    <span class="badge bg-danger">Absent</span>
                                @endif
                            </td>
                            <td>{{ $attendance->ip_address }}</td>
                            <td>{{ Str::limit($attendance->device_info, 30) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $attendances->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
