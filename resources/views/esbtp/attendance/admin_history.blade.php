@extends('layouts.app')

@section('title', 'Historique global des émargements enseignants')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historique global des émargements enseignants
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="teacher_id" class="form-label">Enseignant</label>
                            <select name="teacher_id" id="teacher_id" class="form-select">
                                <option value="">Tous</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                        </div>
                        <div class="col-md-2 align-self-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Filtrer
                            </button>
                        </div>
                    </form>
                    @if($attendances->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun émargement enregistré pour le moment.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Heure</th>
                                        <th>Enseignant</th>
                                        <th>Matière</th>
                                        <th>Classe</th>
                                        <th>Statut</th>
                                        <th>Code utilisé</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attendances as $attendance)
                                        <tr>
                                            <td>{{ $attendance->date ? $attendance->date->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                {{ optional($attendance->course)->start_time ? optional($attendance->course)->start_time->format('H:i') : '-' }}
                                                -
                                                {{ optional($attendance->course)->end_time ? optional($attendance->course)->end_time->format('H:i') : '-' }}
                                            </td>
                                            <td>{{ optional($attendance->teacher)->name ?? '-' }}</td>
                                            <td>{{ optional($attendance->course->subject)->name ?? '-' }}</td>
                                            <td>{{ optional($attendance->course->class)->name ?? '-' }}</td>
                                            <td>
                                                @if($attendance->status === 'fait')
                                                    <span class="badge bg-success">Émargé</span>
                                                @elseif($attendance->status === 'bloqué')
                                                    <span class="badge bg-danger">Bloqué</span>
                                                @elseif($attendance->status === 'not_signed')
                                                    <span class="badge bg-danger">Non signé</span>
                                                @else
                                                    <span class="badge bg-warning">{{ ucfirst($attendance->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($attendance->dailyCode)->code ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $attendances->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
