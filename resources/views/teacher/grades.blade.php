@extends('layouts.app')

@section('title', 'Gestion des notes - Enseignant')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des notes</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Notes</li>
    </ol>

    <!-- Actions rapides -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2 text-primary"></i>Actions rapides
                        </h5>
                        <div>
                            <a href="{{ route('esbtp.evaluations.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Nouvelle évaluation
                            </a>
                            <a href="{{ route('esbtp.notes.saisie-rapide-form') }}" class="btn btn-success ms-2">
                                <i class="fas fa-table me-2"></i>Saisie rapide des notes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Évaluations -->
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-list me-1"></i>
                    Mes évaluations
                </div>
                <div class="card-body">
                    @if($evaluations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($evaluations as $evaluation)
                                <tr>
                                    <td>{{ $evaluation->matiere->name ?? 'Non définie' }}</td>
                                    <td>{{ $evaluation->classe->name ?? 'Non définie' }}</td>
                                    <td>
                                        <span class="badge 
                                            @if($evaluation->type == 'Examen') bg-primary
                                            @elseif($evaluation->type == 'Devoir') bg-success
                                            @elseif($evaluation->type == 'TP') bg-info
                                            @else bg-secondary
                                            @endif">
                                            {{ $evaluation->type }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($evaluation->date_evaluation)->format('d/m/Y') }}</td>
                                    <td>
                                        @php
                                            $totalEtudiants = $evaluation->classe->etudiants->count() ?? 0;
                                            $notesCount = $evaluation->notes->count() ?? 0;
                                            $percentage = $totalEtudiants > 0 ? round(($notesCount / $totalEtudiants) * 100) : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $percentage < 50 ? 'bg-warning' : 'bg-success' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $percentage }}%;" 
                                                 aria-valuenow="{{ $percentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $percentage }}%
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            {{ $notesCount }} notes sur {{ $totalEtudiants }} étudiants
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation->id) }}" class="btn btn-sm btn-primary" title="Saisir des notes">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('esbtp.evaluations.show', $evaluation->id) }}" class="btn btn-sm btn-info" title="Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $evaluations->links() }}
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <p class="mb-0">Vous n'avez pas encore créé d'évaluations.</p>
                        <a href="{{ route('esbtp.evaluations.create') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-plus-circle me-1"></i> Créer une évaluation
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Notes récentes -->
        <div class="col-xl-5">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Notes récemment saisies
                </div>
                <div class="card-body">
                    @if($recentGrades->count() > 0)
                    <div class="list-group">
                        @foreach($recentGrades as $note)
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h6 class="mb-1">{{ $note->etudiant->nom ?? '' }} {{ $note->etudiant->prenoms ?? '' }}</h6>
                                <span class="badge {{ $note->valeur >= 10 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $note->valeur }}/20
                                </span>
                            </div>
                            <p class="mb-1">
                                <strong>{{ $note->evaluation->type ?? '' }}</strong> - 
                                {{ $note->evaluation->matiere->name ?? 'Matière non définie' }}
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-users me-1"></i>
                                {{ $note->evaluation->classe->name ?? 'Classe non définie' }} |
                                <i class="fas fa-clock me-1"></i>
                                {{ $note->created_at->diffForHumans() }}
                            </small>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="mb-0">Aucune note récemment saisie.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Statistiques -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Statistiques des notes
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="small text-muted mb-2">Répartition des notes</div>
                            <canvas id="gradeDistributionChart" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted mb-2">Moyennes par matière</div>
                            <canvas id="subjectAveragesChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Distribution des notes
        const gradeDistributionCtx = document.getElementById('gradeDistributionChart').getContext('2d');
        new Chart(gradeDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['0-5', '5-10', '10-15', '15-20'],
                datasets: [{
                    data: [15, 30, 40, 15], // Données fictives à remplacer par des données réelles
                    backgroundColor: ['#ef4444', '#f59e0b', '#6366f1', '#22c55e'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Moyennes par matière
        const subjectAveragesCtx = document.getElementById('subjectAveragesChart').getContext('2d');
        new Chart(subjectAveragesCtx, {
            type: 'bar',
            data: {
                labels: ['Math', 'Physique', 'Anglais', 'Programmation'], // Données fictives
                datasets: [{
                    label: 'Moyenne',
                    data: [12.5, 11.2, 14.3, 15.8], // Données fictives
                    backgroundColor: '#6366f1',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 20
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
@endsection 