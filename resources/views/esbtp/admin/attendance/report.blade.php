@extends('layouts.app')

@section('title', 'Rapports d\'émargement')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .attendance-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-elevated);
    }
    
    .attendance-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 120px;
        height: 100%;
        background: rgba(255,255,255,0.15);
        transform: skewX(-15deg);
        transform-origin: top;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
        max-width: 100%;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }

    .stat-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: var(--shadow-card);
    }

    .stat-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }

    .stat-card .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: var(--space-md);
        color: white;
    }

    .stat-card .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: var(--space-sm);
    }

    .stat-card .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin: 0;
    }

    .stat-card .stat-description {
        color: var(--text-muted);
        font-size: 0.8rem;
        margin-top: var(--space-xs);
    }

    .icon-primary { background: var(--primary); }
    .icon-success { background: var(--success); }
    .icon-warning { background: var(--warning); }
    .icon-accent { background: var(--accent-blue); }

    .filters-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-xl);
    }

    .data-table-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: var(--shadow-card);
    }

    .page-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .page-subtitle {
        opacity: 0.95;
        margin: var(--space-sm) 0 0;
        position: relative;
        z-index: 1;
        color: rgba(255,255,255,0.9);
        font-size: 1rem;
    }

    .stats-section {
        /* Permettre au contenu de s'afficher naturellement */
        overflow: visible;
    }

    .chart-container {
        position: relative;
        height: 350px !important;
        max-height: 350px !important;
        width: 100%;
        overflow: visible;
    }

    #dailyStatsChart {
        max-height: 320px !important;
        height: 320px !important;
        width: 100% !important;
    }

    .stat-mini-card {
        background: white;
        border-radius: var(--radius-medium);
        padding: 1.2rem;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.2s ease;
        height: 100%;
    }

    .stat-mini-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .stat-mini-card .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: block;
    }

    .stat-mini-card .stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .stat-summary-card {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: var(--radius-medium);
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .rate-display {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
    }

    /* Styles pour les badges de statut */
    .status-badge {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
        text-decoration: none;
    }

    .status-badge.bg-success {
        background-color: #198754 !important;
        color: white !important;
    }

    .status-badge.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .status-badge.bg-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .status-badge.bg-secondary {
        background-color: #6c757d !important;
        color: white !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header moderne -->
    <div class="attendance-header">
        <h1 class="page-title">
            <i class="fas fa-chart-line me-3"></i>
            Rapport d'Émargement des Enseignants
        </h1>
        <p class="page-subtitle">Analyse détaillée des présences et statistiques d'émargement</p>
    </div>

    <!-- Statistiques principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number text-primary">{{ $stats['total'] }}</div>
            <p class="stat-label">Total Émargements</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-success">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-number text-success">{{ $stats['attendance_rate'] }}%</div>
            <p class="stat-label">Taux de Présence</p>
            <div class="stat-description">
                Présents: {{ $stats['present'] }} | 
                En retard: {{ $stats['late'] }} | 
                Absents: {{ $stats['absent'] }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-accent">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-number text-info">{{ $stats['validation_rate'] }}%</div>
            <p class="stat-label">Taux de Validation</p>
            <div class="stat-description">
                Validés: {{ $stats['validated'] }} | 
                En attente: {{ $stats['pending'] }} | 
                Rejetés: {{ $stats['rejected'] }}
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-card">
        <h5 class="mb-3">
            <i class="fas fa-filter me-2"></i>
            Filtres de Recherche
        </h5>
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
                                                {{ $enseignant->name }}
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
    </div>

    <!-- Table des données -->
    <div class="data-table-card">
        <h5 class="mb-3">
            <i class="fas fa-table me-2"></i>
            Liste des Émargements
        </h5>
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
                                        <td>{{ $attendance->teacher->name }}</td>
                                        <td>{{ $attendance->course->matiere->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($attendance->status === 'present')
                                                <span class="status-badge bg-success">Présent</span>
                                            @elseif($attendance->status === 'late')
                                                <span class="status-badge bg-warning">En retard</span>
                                            @elseif($attendance->status === 'absent')
                                                <span class="status-badge bg-danger">Absent</span>
                                            @elseif($attendance->status === 'not_signed')
                                                <span class="status-badge bg-secondary">Non émargé</span>
                                            @elseif($attendance->status === 'signed')
                                                <span class="status-badge bg-success">Émargé</span>
                                            @elseif($attendance->status === 'fait')
                                                <span class="status-badge bg-success">Fait</span>
                                            @else
                                                <span class="status-badge bg-secondary">{{ $attendance->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $attendance->marked_at ? $attendance->marked_at->format('H:i') : 'N/A' }}</td>
                                        <td>{{ $attendance->code }}</td>
                                        <td>
                                            @if($attendance->validated_at)
                                                <span class="status-badge bg-success">Validé</span>
                                            @else
                                                <span class="status-badge bg-warning">En attente</span>
                                            @endif
                                        </td>
                                        <td>{{ $attendance->validated_at ? 'Auto-validé' : 'N/A' }}</td>
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

    <!-- Statistiques détaillées en bas de page -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="data-table-card stats-section">
                <div class="row">
                    <!-- Graphique des tendances -->
                    <div class="col-lg-8">
                        <div class="chart-container bg-white p-3 rounded shadow-sm">
                            <h6 class="mb-3 text-dark fw-bold">
                                <i class="fas fa-line-chart me-2 text-primary"></i>
                                Évolution des Émargements par Jour
                            </h6>
                            <canvas id="dailyStatsChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Résumé par statut -->
                    <div class="col-lg-4">
                        <h6 class="mb-3 text-dark fw-bold">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>
                            Répartition par Statut
                        </h6>
                        
                        <!-- Mini cards pour les statistiques -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="stat-mini-card">
                                    <span class="stat-value text-success">{{ $stats['present'] }}</span>
                                    <div class="stat-label">Présents</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini-card">
                                    <span class="stat-value text-warning">{{ $stats['late'] }}</span>
                                    <div class="stat-label">En Retard</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini-card">
                                    <span class="stat-value text-danger">{{ $stats['absent'] }}</span>
                                    <div class="stat-label">Absents</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini-card">
                                    <span class="stat-value text-info">{{ $stats['validated'] }}</span>
                                    <div class="stat-label">Validés</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Résumé des taux -->
                        <div class="stat-summary-card">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="rate-display text-success">{{ $stats['attendance_rate'] }}%</div>
                                    <div class="small text-muted fw-medium">Taux de Présence</div>
                                </div>
                                <div class="col-6">
                                    <div class="rate-display text-info">{{ $stats['validation_rate'] }}%</div>
                                    <div class="small text-muted fw-medium">Taux de Validation</div>
                                </div>
                            </div>
                        </div>
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
                        <dd class="col-sm-8">{{ $attendance->teacher->name }}</dd>

                        <dt class="col-sm-4">Matière</dt>
                        <dd class="col-sm-8">{{ $attendance->course->matiere->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">{{ $attendance->created_at->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">{{ ucfirst($attendance->status) }}</dd>

                        <dt class="col-sm-4">Code</dt>
                        <dd class="col-sm-8">{{ $attendance->code }}</dd>

                        <dt class="col-sm-4">Validation</dt>
                        <dd class="col-sm-8">{{ $attendance->validated_at ? 'Validé' : 'En attente' }}</dd>

                        <dt class="col-sm-4">Validé par</dt>
                        <dd class="col-sm-8">{{ $attendance->validated_at ? 'Auto-validé' : 'N/A' }}</dd>

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
