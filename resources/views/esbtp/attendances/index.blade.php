@extends('layouts.app')

@section('title', 'Gestion des présences')

@php
// Définir la fonction getInitials localement si elle n'existe pas
if (!function_exists('getInitials')) {
    function getInitials($name) {
        if (empty($name)) {
            return 'NA';
        }
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word[0])) {
                $initials .= strtoupper($word[0]);
                if (strlen($initials) >= 2) break;
            }
        }
        return $initials ?: 'NA';
    }
}
@endphp

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Présents</h6>
                            <h2 class="mt-2 mb-0">{{ $stats['present'] ?? 0 }}</h2>
                        </div>
                        <i class="fas fa-user-check fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Absents</h6>
                            <h2 class="mt-2 mb-0">{{ $stats['absent'] ?? 0 }}</h2>
                        </div>
                        <i class="fas fa-user-times fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Retards</h6>
                            <h2 class="mt-2 mb-0">{{ $stats['retard'] ?? 0 }}</h2>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Excusés</h6>
                            <h2 class="mt-2 mb-0">{{ $stats['excuse'] ?? 0 }}</h2>
                        </div>
                        <i class="fas fa-notes-medical fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-calendar-check"></i>
            </span> Gestion des présences
        </h3>

        </div>

        <!-- Tableau de bord des statistiques -->
        <div class="row">
            <!-- Total des présences -->


            <!-- Présents -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Présents</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $statsPresent }} ({{ $statsPresentPercent }}%)</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $statsPresentPercent }}%" aria-valuenow="{{ $statsPresentPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absents -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Absents</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $statsAbsent }} ({{ $statsAbsentPercent }}%)</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $statsAbsentPercent }}%" aria-valuenow="{{ $statsAbsentPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Retards -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Retards</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $statsRetard }} ({{ $statsRetardPercent }}%)</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $statsRetardPercent }}%" aria-valuenow="{{ $statsRetardPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Excusés -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Excusés</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $statsExcuse }} ({{ $statsExcusePercent }}%)</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $statsExcusePercent }}%" aria-valuenow="{{ $statsExcusePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-notes-medical fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total des filtres appliqués (visible seulement si des filtres sont appliqués) -->
            @if(request()->hasAny(['classe_id', 'matiere_id', 'date_debut', 'date_fin', 'statut']))
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-dark shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                    Total après filtres</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $filteredTotal }}</div>
                                <div class="small text-muted">Résultats filtrés</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-filter fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Graphique des tendances 7 derniers jours -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Tendance des 7 derniers jours</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="attendanceChart" style="height: 250px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                        <div class="d-flex justify-content-between mb-4">
                            <h4 class="card-title mb-0">Liste des présences</h4>
                            <div>
                        <a href="{{ route('esbtp.attendances.create') }}" class="btn btn-gradient-primary btn-sm">
                            <i class="mdi mdi-plus"></i> Marquer des présences
                        </a>
                        <a href="{{ route('esbtp.attendances.rapport-form') }}" class="btn btn-gradient-info btn-sm">
                            <i class="mdi mdi-file-chart"></i> Générer un rapport
                        </a>
                            </div>
                    </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Filtres</h5>
                            </div>
                            <div class="card-body">
                        <form action="{{ route('esbtp.attendances.index') }}" method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="classe_id" class="form-label">Classe</label>
                                        <select name="classe_id" id="classe_id" class="form-select">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                                    <div class="col-md-3">
                                        <label for="matiere_id" class="form-label">Matière</label>
                                        <select name="matiere_id" id="matiere_id" class="form-select">
                                            <option value="">Toutes les matières</option>
                                            @foreach($matieres as $matiere)
                                                <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                                    {{ $matiere->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                            <div class="col-md-3">
                                <label for="etudiant_id" class="form-label">Étudiant</label>
                                        <select name="etudiant_id" id="etudiant_id" class="form-select">
                                    <option value="">Tous les étudiants</option>
                                    @foreach($etudiants as $etudiant)
                                        <option value="{{ $etudiant->id }}" {{ request('etudiant_id') == $etudiant->id ? 'selected' : '' }}>
                                            {{ $etudiant->nom_complet }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                                    <div class="col-md-2">
                                        <label for="date_debut" class="form-label">Date début</label>
                                        <input type="date" class="form-control" id="date_debut" name="date_debut"
                                               value="{{ request('date_debut') }}">
                                    </div>

                            <div class="col-md-2">
                                        <label for="date_fin" class="form-label">Date fin</label>
                                        <input type="date" class="form-control" id="date_fin" name="date_fin"
                                               value="{{ request('date_fin') }}">
                            </div>

                            <div class="col-md-2">
                                <label for="statut" class="form-label">Statut</label>
                                        <select name="statut" id="statut" class="form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="present" {{ request('statut') == 'present' ? 'selected' : '' }}>Présent</option>
                                    <option value="absent" {{ request('statut') == 'absent' ? 'selected' : '' }}>Absent</option>
                                            <option value="retard" {{ request('statut') == 'retard' ? 'selected' : '' }}>Retard</option>
                                    <option value="excuse" {{ request('statut') == 'excuse' ? 'selected' : '' }}>Excusé</option>
                                </select>
                            </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>Filtrer
                                </button>
                                        <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-undo me-1"></i>Réinitialiser
                                        </a>
                            </div>
                        </form>
                            </div>
                    </div>

                        <!-- Statistiques par étudiant -->
                        @if(count($statsParEtudiant) > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Statistiques par étudiant</h5>
                            </div>
                            <div class="card-body">
                    <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Étudiant</th>
                                                <th class="text-center">Présences</th>
                                                <th class="text-center">Absences</th>
                                                <th class="text-center">Retards</th>
                                                <th class="text-center">Excusés</th>
                                                <th class="text-center">Total</th>
                                                <th class="text-center">Taux de présence</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statsParEtudiant as $stat)
                                                <tr>
                                                    <td>{{ $stat['etudiant']->nom_complet }}</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-success">{{ $stat['present'] }}</span>
                                                        <div class="progress progress-sm mt-1">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $stat['present_percent'] }}%" aria-valuenow="{{ $stat['present_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-danger">{{ $stat['absent'] }}</span>
                                                        <div class="progress progress-sm mt-1">
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $stat['absent_percent'] }}%" aria-valuenow="{{ $stat['absent_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-warning">{{ $stat['retard'] }}</span>
                                                        <div class="progress progress-sm mt-1">
                                                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $stat['retard_percent'] }}%" aria-valuenow="{{ $stat['retard_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-info">{{ $stat['excuse'] }}</span>
                                                        <div class="progress progress-sm mt-1">
                                                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $stat['excuse_percent'] }}%" aria-valuenow="{{ $stat['excuse_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">{{ $stat['total'] }}</td>
                                                    <td class="text-center">
                                                        <strong>{{ $stat['present_percent'] }}%</strong>
                                                        <div class="progress progress-sm mt-1">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $stat['present_percent'] }}%" aria-valuenow="{{ $stat['present_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                        <th>Matière</th>
                                    <th>Date</th>
                                        <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $attendance)
                                    <tr data-absence-id="{{ $attendance->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $attendance->etudiant->user->name ?? 'N/A' }}</td>
                                        <td>{{ $attendance->seanceCours->emploiTemps->classe->name ?? 'N/A' }}</td>
                                        <td>{{ $attendance->seanceCours->matiere->name ?? 'N/A' }}</td>
                                        <td>{{ $attendance->date ? $attendance->date->format('d/m/Y') : 'N/A' }}</td>
                                        <td>
                                            @switch($attendance->statut)
                                                @case('present')
                                                    <span class="badge bg-success">Présent</span>
                                                    @break
                                                @case('absent')
                                                    @if($attendance->justified_at)
                                                        <span class="badge bg-warning text-dark">Absence justifiée en attente</span>
                                                    @else
                                                        <span class="badge bg-danger">Absent</span>
                                                    @endif
                                                    @break
                                                @case('retard')
                                                    <span class="badge bg-warning text-dark">Retard</span>
                                                    @break
                                                @case('excuse')
                                                    <span class="badge bg-info">Excusé</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $attendance->statut }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @canany(['edit_attendances', 'edit attendances'])
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('esbtp.attendances.edit', $attendance) }}" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    @if($attendance->justified_at && $attendance->statut == 'absent')
                                                        <button type="button" class="btn btn-outline-warning btn-justify"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#justificationModal{{ $attendance->id }}"
                                                            data-absence-id="{{ $attendance->id }}"
                                                            data-student-name="{{ $attendance->etudiant->user->name ?? 'N/A' }}"
                                                            data-class-name="{{ $attendance->seanceCours->emploiTemps->classe->name ?? 'N/A' }}"
                                                            data-subject-name="{{ $attendance->seanceCours->matiere->name ?? 'N/A' }}"
                                                            data-date="{{ $attendance->date ? $attendance->date->format('d/m/Y') : 'N/A' }}"
                                                            data-justified-at="{{ $attendance->justified_at ? $attendance->justified_at->format('d/m/Y H:i') : 'N/A' }}"
                                                            data-justification="{{ $attendance->commentaire }}"
                                                            data-document-path="{{ $attendance->document_path }}">
                                                            <i class="fas fa-balance-scale"></i>
                                                        </button>
                                                    @endif

                                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $attendance->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Modal de confirmation de suppression -->
                                                <div class="modal fade" id="deleteModal{{ $attendance->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $attendance->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="deleteModalLabel{{ $attendance->id }}">Confirmation de suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Êtes-vous sûr de vouloir supprimer cette présence ?</p>
                                                                <p><strong>Étudiant :</strong> {{ $attendance->etudiant->user->name ?? 'N/A' }}</p>
                                                                <p><strong>Date :</strong> {{ $attendance->date ? $attendance->date->format('d/m/Y') : 'N/A' }}</p>
                                                                <p><strong>Matière :</strong> {{ $attendance->seanceCours->matiere->name ?? 'N/A' }}</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <form action="{{ route('esbtp.attendances.destroy', $attendance) }}" method="POST">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modal pour traiter les justifications d'absence -->
                                                @if($attendance->justified_at && $attendance->statut == 'absent')
                                                <div class="modal fade" id="justificationModal{{ $attendance->id }}" tabindex="-1" aria-labelledby="justificationModalLabel{{ $attendance->id }}" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="justificationModalLabel{{ $attendance->id }}">Traiter la justification d'absence</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p><strong>Étudiant :</strong> <span id="justification-student-name{{ $attendance->id }}">{{ $attendance->etudiant->nom ?? 'N/A' }}</span></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p><strong>Classe :</strong> <span id="justification-class-name{{ $attendance->id }}">{{ $attendance->seanceCours->emploiTemps->classe->name ?? 'N/A' }}</span></p>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p><strong>Matière :</strong> <span id="justification-subject-name{{ $attendance->id }}">{{ $attendance->seanceCours->matiere->name ?? 'N/A' }}</span></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p><strong>Date :</strong> <span id="justification-date{{ $attendance->id }}">{{ $attendance->date ? $attendance->date->format('d/m/Y') : 'N/A' }}</span></p>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p><strong>Justifié le :</strong> <span id="justification-justified-at{{ $attendance->id }}">{{ $attendance->justified_at ? $attendance->justified_at->format('d/m/Y H:i') : 'N/A' }}</span></p>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <div class="row mb-3">
                                                                    <div class="col-12">
                                                                        <h6>Justification de l'étudiant :</h6>
                                                                        <div class="p-3 bg-light border rounded" id="justification-text{{ $attendance->id }}">{{ $attendance->commentaire ?? 'Aucune justification fournie.' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3" id="justification-document-row{{ $attendance->id }}" @if(!$attendance->document_path) style="display: none;" @endif>
                                                                    <div class="col-12">
                                                                        <h6>Document justificatif :</h6>
                                                                        <div class="p-3">
                                                                            @if($attendance->document_path)
                                                                                <a href="{{ asset('storage/' . $attendance->document_path) }}" id="justification-document-link{{ $attendance->id }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                                    <i class="fas fa-file-pdf"></i> Voir le document
                                                                                </a>

                                                                                @php
                                                                                    $ext = pathinfo(storage_path('app/public/' . $attendance->document_path), PATHINFO_EXTENSION);
                                                                                @endphp

                                                                                @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif']))
                                                                                    <div class="mt-3">
                                                                                        <img src="{{ asset('storage/' . $attendance->document_path) }}" alt="Justificatif" class="img-fluid thumbnail" style="max-height: 200px;">
                                                                                    </div>
                                                                                @endif
                                                                            @else
                                                                                <div class="alert alert-info">
                                                                                    Aucun document justificatif n'a été fourni.
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <form id="justificationForm{{ $attendance->id }}" action="{{ route('esbtp.attendances.process-justification', $attendance->id) }}" method="POST">
                                                                    @csrf
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Décision :</label>
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="radio" name="decision" id="decision-approve{{ $attendance->id }}" value="approve" checked>
                                                                            <label class="form-check-label text-success" for="decision-approve{{ $attendance->id }}">
                                                                                <i class="fas fa-check"></i> Approuver la justification (absence excusée)
                                                                            </label>
                                                                        </div>
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="radio" name="decision" id="decision-reject{{ $attendance->id }}" value="reject">
                                                                            <label class="form-check-label text-danger" for="decision-reject{{ $attendance->id }}">
                                                                                <i class="fas fa-times"></i> Rejeter la justification (maintenir absence non excusée)
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="admin_comment{{ $attendance->id }}" class="form-label">Commentaire (optionnel) :</label>
                                                                        <textarea class="form-control" id="admin_comment{{ $attendance->id }}" name="admin_comment" rows="3" placeholder="Raison de la décision..."></textarea>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="button" class="btn btn-primary" id="submit-justification{{ $attendance->id }}">Enregistrer la décision</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            @else
                                                <span class="text-muted"><i class="fas fa-lock"></i> Non autorisé</span>
                                            @endcanany
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune présence enregistrée</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $attendances->links() }}
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
$(document).ready(function() {
    // Initialiser Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Confirmation de suppression
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        if (confirm('Êtes-vous sûr de vouloir supprimer cette présence ?')) {
            this.submit();
        }
    });

    // Graphique des tendances
    var ctx = document.getElementById('attendanceChart').getContext('2d');
    var data = {
        labels: @json(array_keys($statsParJour)),
        datasets: [
            {
                label: 'Présent',
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: 'rgba(40, 167, 69, 1)',
                data: @json(array_map(function($day) { return $day['present'] ?? 0; }, $statsParStatus)),
                borderWidth: 2,
                tension: 0.3
            },
            {
                label: 'Absent',
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                borderColor: 'rgba(220, 53, 69, 1)',
                data: @json(array_map(function($day) { return $day['absent'] ?? 0; }, $statsParStatus)),
                borderWidth: 2,
                tension: 0.3
            },
            {
                label: 'Retard',
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                borderColor: 'rgba(255, 193, 7, 1)',
                data: @json(array_map(function($day) { return $day['retard'] ?? 0; }, $statsParStatus)),
                borderWidth: 2,
                tension: 0.3
            },
            {
                label: 'Excusé',
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                borderColor: 'rgba(23, 162, 184, 1)',
                data: @json(array_map(function($day) { return $day['excuse'] ?? 0; }, $statsParStatus)),
                borderWidth: 2,
                tension: 0.3
            }
        ]
    };

    var options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                usePointStyle: true,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + ' étudiants';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    };

    var myChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: options
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des boutons de soumission des formulaires de justification
        document.querySelectorAll('[id^="submit-justification"]').forEach(button => {
            button.addEventListener('click', function() {
                // Extraire l'ID de l'absence du bouton
                const buttonId = this.id;
                const absenceId = buttonId.replace('submit-justification', '');

                // Soumettre le formulaire correspondant
                document.getElementById('justificationForm' + absenceId).submit();
            });
        });

        // Highlight d'une absence spécifique si demandé dans l'URL (depuis une notification)
        const urlParams = new URLSearchParams(window.location.search);
        const highlightParam = urlParams.get('highlight');

        if (highlightParam && highlightParam.startsWith('absence_')) {
            const absenceId = highlightParam.replace('absence_', '');
            const row = document.querySelector(`tr[data-absence-id="${absenceId}"]`);

            if (row) {
                // Faire défiler jusqu'à la ligne
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Ajouter une classe de surbrillance temporaire
                row.classList.add('highlight-row');

                // Clignotement de la ligne pendant quelques secondes
                setTimeout(() => {
                    row.classList.add('highlight-animation');
                }, 500);

                // Retirer la surbrillance après quelques secondes
                setTimeout(() => {
                    row.classList.remove('highlight-row');
                    row.classList.remove('highlight-animation');
                }, 5000);
            }
        }
    });
</script>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df;
}
.border-left-success {
    border-left: 4px solid #1cc88a;
}
.border-left-danger {
    border-left: 4px solid #e74a3b;
}
.border-left-warning {
    border-left: 4px solid #f6c23e;
}
.border-left-info {
    border-left: 4px solid #36b9cc;
}
.progress-sm {
    height: 8px;
}
.chart-area {
    position: relative;
    height: 100%;
    width: 100%;
}
.text-xs {
    font-size: 0.7rem;
}
.attendance-row {
    transition: all 0.2s ease-in-out;
}
.attendance-row:hover {
    background-color: rgba(0, 0, 0, 0.03);
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}
.avatar {
    width: 28px;
    height: 28px;
    font-size: 12px;
}
.smaller {
    font-size: 0.75rem;
}
.bg-gradient-light {
    background: linear-gradient(to right, #f8f9fa, #ffffff);
}

/* Pagination styling */
.pagination {
    --bs-pagination-padding-x: 0.75rem;
    --bs-pagination-padding-y: 0.375rem;
    --bs-pagination-font-size: 0.9rem;
    --bs-pagination-color: var(--esbtp-green);
    --bs-pagination-bg: #fff;
    --bs-pagination-border-width: 1px;
    --bs-pagination-border-color: #dee2e6;
    --bs-pagination-border-radius: 0.375rem;
    --bs-pagination-hover-color: var(--esbtp-green-dark);
    --bs-pagination-hover-bg: #e9ecef;
    --bs-pagination-hover-border-color: #dee2e6;
    --bs-pagination-focus-color: var(--esbtp-green-dark);
    --bs-pagination-focus-bg: #e9ecef;
    --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(1, 99, 47, 0.25);
    --bs-pagination-active-color: #fff;
    --bs-pagination-active-bg: var(--esbtp-green);
    --bs-pagination-active-border-color: var(--esbtp-green);
    --bs-pagination-disabled-color: #6c757d;
    --bs-pagination-disabled-bg: #fff;
    --bs-pagination-disabled-border-color: #dee2e6;
}

/* Action buttons styling */
.d-flex.justify-content-center .btn {
    padding: 0.25rem 0.5rem;
    margin: 0 0.15rem;
    border-radius: 0.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    transition: all 0.2s;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
}

.action-buttons .btn {
    margin: 0;
    padding: 0.25rem 0.5rem;
    min-width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.action-buttons .btn-outline-info:hover {
    background-color: #17a2b8;
    color: white;
}

.action-buttons .btn-outline-primary:hover {
    background-color: var(--esbtp-green);
    color: white;
}

.action-buttons .btn-outline-danger:hover {
    background-color: #dc3545;
    color: white;
}

.d-flex.justify-content-center .btn i {
    font-size: 0.875rem;
}

.d-flex.justify-content-center .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.d-flex.justify-content-center .btn-outline-info:hover {
    background-color: #17a2b8;
    color: white;
}

.d-flex.justify-content-center .btn-outline-primary:hover {
    background-color: var(--esbtp-green);
    color: white;
}

.d-flex.justify-content-center .btn-outline-danger:hover {
    background-color: #dc3545;
    color: white;
}

/* Avatar Circles with Initials */
.avatar-circle {
    width: 40px;
    height: 40px;
    background-color: var(--esbtp-green);
    border-radius: 50%;
    color: white;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
}

.avatar-circle .initials {
    font-size: 16px;
    line-height: 1;
    position: relative;
}

.highlight-row {
    background-color: #fff3cd !important;
}

.highlight-animation {
    animation: highlight-pulse 1s infinite alternate;
}

@keyframes highlight-pulse {
    from { background-color: #fff3cd; }
    to { background-color: #ffe69c; }
}

.badge-justified {
    background-color: #6c757d;
    color: white;
}
</style>
@endsection
