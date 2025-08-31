@extends('layouts.app')

@section('title', 'Étudiants - Enseignant')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des étudiants</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Étudiants</li>
    </ol>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('teacher.students') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="classe_id" class="form-label">Classe</label>
                    <select class="form-select" id="classe_id" name="classe_id">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('classe_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Nom, prénom, matricule..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Trier par</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nom</option>
                        <option value="matricule" {{ request('sort') == 'matricule' ? 'selected' : '' }}>Matricule</option>
                        <option value="moyenne" {{ request('sort') == 'moyenne' ? 'selected' : '' }}>Moyenne</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                    <a href="{{ route('teacher.students') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt me-1"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Liste des étudiants -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-users me-1"></i>
                        Liste des étudiants
                    </div>
                    <div>
                        <span class="badge bg-primary">{{ $students->total() }} étudiants</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Matricule</th>
                                        <th>Nom & Prénom</th>
                                        <th>Classe</th>
                                        <th>Moyenne</th>
                                        <th>Présence</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                        <tr>
                                            <td class="text-center">
                                                <img src="{{ $student->photo ? asset('storage/' . $student->photo) : asset('images/default-profile.png') }}" 
                                                     alt="Photo de {{ $student->user->name }}" 
                                                     class="rounded-circle" 
                                                     width="40" height="40">
                                            </td>
                                            <td>{{ $student->matricule }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold">{{ $student->user->name }}</span>
                                                    <small class="text-muted">{{ $student->user->email }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if($student->classe)
                                                    <span class="badge bg-info">{{ $student->classe->name }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Non assigné</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $moyenne = $student->evaluations->where('teacher_id', auth()->user()->teacher->id)->avg('note');
                                                @endphp
                                                @if($moyenne)
                                                    <span class="badge {{ $moyenne >= 10 ? 'bg-success' : 'bg-danger' }}">
                                                        {{ number_format($moyenne, 2) }}/20
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $attendance = $student->calculateAttendanceForTeacher(auth()->user()->teacher->id);
                                                    $rate = $attendance['rate'] ?? 0;
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                        <div class="progress-bar {{ $rate >= 80 ? 'bg-success' : ($rate >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                                             role="progressbar" 
                                                             style="width: {{ $rate }}%" 
                                                             aria-valuenow="{{ $rate }}" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small>{{ number_format($rate, 0) }}%</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('teacher.student.show', $student->id) }}" class="btn btn-sm btn-info" title="Détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('teacher.student.grades', $student->id) }}" class="btn btn-sm btn-primary" title="Notes">
                                                        <i class="fas fa-star"></i>
                                                    </a>
                                                    <a href="{{ route('teacher.student.attendance', $student->id) }}" class="btn btn-sm btn-warning" title="Présence">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            {{ $students->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="mb-0">Aucun étudiant trouvé pour les critères sélectionnés.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistiques et actions -->
        <div class="col-xl-4">
            <!-- Statistiques par classe -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Répartition par classe
                </div>
                <div class="card-body">
                    @if($classeStats->count() > 0)
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="classeDistributionChart"></canvas>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <p class="mb-0">Aucune donnée disponible.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Dernières notes -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-star me-1"></i>
                    Dernières notes attribuées
                </div>
                <div class="card-body">
                    @if($recentGrades->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($recentGrades as $grade)
                                <li class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold">{{ $grade->student->user->name }}</div>
                                            <small class="text-muted">{{ $grade->evaluation->title }} - {{ $grade->evaluation->matiere->name }}</small>
                                        </div>
                                        <span class="badge {{ $grade->note >= 10 ? 'bg-success' : 'bg-danger' }} fs-6">
                                            {{ number_format($grade->note, 1) }}/20
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3">
                            <a href="{{ route('teacher.grades') }}" class="btn btn-outline-primary btn-sm w-100">
                                Voir toutes les notes
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <p class="mb-0">Aucune note récente.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-bolt me-1"></i>
                    Actions rapides
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('teacher.grades.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> Créer une évaluation
                        </a>
                        <a href="{{ route('esbtp.attendances.create') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-clipboard-check me-1"></i> Saisir des présences
                        </a>
                        <a href="{{ route('teacher.export.students') }}" class="btn btn-outline-success">
                            <i class="fas fa-file-excel me-1"></i> Exporter la liste
                        </a>
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
        // Auto-submit form when select changes
        document.querySelectorAll('#classe_id, #sort').forEach(function(element) {
            element.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Setup classe distribution chart if canvas exists
        const chartCanvas = document.getElementById('classeDistributionChart');
        if (chartCanvas) {
            const chartData = {
                labels: [
                    @foreach($classeStats as $stat)
                        '{{ $stat->name }}',
                    @endforeach
                ],
                datasets: [{
                    label: 'Nombre d\'étudiants',
                    data: [
                        @foreach($classeStats as $stat)
                            {{ $stat->count }},
                        @endforeach
                    ],
                    backgroundColor: [
                        '#0453cb', '#ec4899', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9',
                        '#5e91de', '#14b8a6', '#84cc16', '#0453cb'
                    ],
                    borderWidth: 1
                }]
            };

            new Chart(chartCanvas, {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection 