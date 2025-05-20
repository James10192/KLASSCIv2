@extends('layouts.app')

@section('title', 'Rapports d\'émargement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rapport d'Émargement des Enseignants</h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('esbtp.admin.attendance.report') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">Date de début</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date"
                                           value="{{ $startDate->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">Date de fin</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                           value="{{ $endDate->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="enseignant_id">Enseignant</label>
                                    <select class="form-control" id="enseignant_id" name="enseignant_id">
                                        <option value="">Tous les enseignants</option>
                                        @foreach($enseignants as $enseignant)
                                            <option value="{{ $enseignant->id }}"
                                                {{ request('enseignant_id') == $enseignant->id ? 'selected' : '' }}>
                                                {{ $enseignant->nom_complet }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="matiere_id">Matière</label>
                                    <select class="form-control" id="matiere_id" name="matiere_id">
                                        <option value="">Toutes les matières</option>
                                        @foreach($matieres as $matiere)
                                            <option value="{{ $matiere->id }}"
                                                {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                                {{ $matiere->nom }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Statut de présence</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">Tous les statuts</option>
                                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Présent</option>
                                        <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>En retard</option>
                                        <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="validation_status">Statut de validation</label>
                                    <select class="form-control" id="validation_status" name="validation_status">
                                        <option value="">Tous les statuts</option>
                                        <option value="pending" {{ request('validation_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="validated" {{ request('validation_status') == 'validated' ? 'selected' : '' }}>Validé</option>
                                        <option value="rejected" {{ request('validation_status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2">Filtrer</button>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown">
                                        Exporter
                                    </button>
                                    <div class="dropdown-menu">
                                        <button type="submit" class="dropdown-item" name="export_format" value="csv">CSV</button>
                                        <button type="submit" class="dropdown-item" name="export_format" value="pdf">PDF</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Total des émargements</h5>
                                    <p class="card-text display-4">{{ $stats['total'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Taux de présence</h5>
                                    <p class="card-text display-4">{{ $stats['attendance_rate'] }}%</p>
                                    <p class="mb-0">
                                        Présents: {{ $stats['present'] }} |
                                        En retard: {{ $stats['late'] }} |
                                        Absents: {{ $stats['absent'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Taux de validation</h5>
                                    <p class="card-text display-4">{{ $stats['validation_rate'] }}%</p>
                                    <p class="mb-0">
                                        Validés: {{ $stats['validated'] }} |
                                        En attente: {{ $stats['pending'] }} |
                                        Rejetés: {{ $stats['rejected'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Statistiques journalières</h5>
                                    <canvas id="dailyStatsChart" width="100%" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Enseignant</th>
                                    <th>Matière</th>
                                    <th>Status</th>
                                    <th>Heure d'arrivée</th>
                                    <th>Code</th>
                                    <th>Validation</th>
                                    <th>Validé par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $attendance->enseignant->nom_complet }}</td>
                                        <td>{{ $attendance->matiere->nom }}</td>
                                        <td>
                                            <span class="badge badge-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'late' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($attendance->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $attendance->marked_at ? $attendance->marked_at->format('H:i') : 'N/A' }}</td>
                                        <td>{{ $attendance->code }}</td>
                                        <td>
                                            <span class="badge badge-{{ $attendance->validation_status === 'validated' ? 'success' : ($attendance->validation_status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($attendance->validation_status) }}
                                            </span>
                                        </td>
                                        <td>{{ $attendance->validator ? $attendance->validator->name : 'N/A' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info"
                                                    data-toggle="modal"
                                                    data-target="#detailsModal{{ $attendance->id }}">
                                                Détails
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($attendances as $attendance)
    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal{{ $attendance->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de l'émargement</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-4">Enseignant</dt>
                        <dd class="col-sm-8">{{ $attendance->enseignant->nom_complet }}</dd>

                        <dt class="col-sm-4">Matière</dt>
                        <dd class="col-sm-8">{{ $attendance->matiere->nom }}</dd>

                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">{{ $attendance->created_at->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">{{ ucfirst($attendance->status) }}</dd>

                        <dt class="col-sm-4">Code</dt>
                        <dd class="col-sm-8">{{ $attendance->code }}</dd>

                        <dt class="col-sm-4">Validation</dt>
                        <dd class="col-sm-8">{{ ucfirst($attendance->validation_status) }}</dd>

                        <dt class="col-sm-4">Validé par</dt>
                        <dd class="col-sm-8">{{ $attendance->validator ? $attendance->validator->name : 'N/A' }}</dd>

                        <dt class="col-sm-4">Commentaires</dt>
                        <dd class="col-sm-8">{{ $attendance->comments ?: 'Aucun commentaire' }}</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize daily stats chart
    const dailyStats = @json($stats['daily_stats']);
    const dates = Object.keys(dailyStats);
    const presents = dates.map(date => dailyStats[date].present);
    const lates = dates.map(date => dailyStats[date].late);
    const absents = dates.map(date => dailyStats[date].absent);

    const ctx = document.getElementById('dailyStatsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Présents',
                    data: presents,
                    borderColor: 'rgb(40, 167, 69)',
                    fill: false
                },
                {
                    label: 'En retard',
                    data: lates,
                    borderColor: 'rgb(255, 193, 7)',
                    fill: false
                },
                {
                    label: 'Absents',
                    data: absents,
                    borderColor: 'rgb(220, 53, 69)',
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
@endpush
