@extends('layouts.app')

@section('title', 'Historique de mes émargements')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historique de mes émargements
                    </h5>
                </div>
                <div class="card-body">
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
                                        <th>Matière</th>
                                        <th>Classe</th>
                                        <th>Statut</th>
                                        <th>Code utilisé</th>
                                        <th>Géoloc</th>
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
                                            <td>{{ optional($attendance->course->subject)->name ?? '-' }}</td>
                                            <td>{{ optional($attendance->course->class)->name ?? '-' }}</td>
                                            <td>
                                                @if($attendance->status === 'fait')
                                                    <span class="badge bg-success">Émargé</span>
                                                @elseif($attendance->status === 'bloqué')
                                                    <span class="badge bg-danger">Bloqué</span>
                                                @else
                                                    <span class="badge bg-warning">{{ ucfirst($attendance->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($attendance->dailyCode)->code ?? '-' }}</td>
                                            <td>
                                                @if($attendance->geolocation_data)
                                                    <span class="text-success" title="Lat: {{ $attendance->geolocation_data['latitude'] ?? '?' }}, Lng: {{ $attendance->geolocation_data['longitude'] ?? '?' }}">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $attendances->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
