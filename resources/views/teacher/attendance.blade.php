@extends('layouts.app')

@section('title', 'Gestion des présences - Enseignant')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des présences</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Présences</li>
    </ol>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.attendance') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="classe" class="form-label">Classe</label>
                            <select class="form-select" id="classe" name="classe_id">
                                <option value="">Toutes les classes</option>
                                @foreach($classeStats as $stat)
                                    <option value="{{ $stat->classe }}" {{ request('classe_id') == $stat->classe ? 'selected' : '' }}>
                                        {{ $stat->classe }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_start" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_start" name="date_start" value="{{ request('date_start', now()->subMonth()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_end" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_end" name="date_end" value="{{ request('date_end', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Filtrer
                            </button>
                            <a href="{{ route('teacher.attendance') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt me-1"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Statistiques de présence par classe -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Taux de présence par classe
                </div>
                <div class="card-body">
                    @if($classeStats->count() > 0)
                        @foreach($classeStats as $stat)
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold">{{ $stat->classe }}</span>
                                    <span>
                                        @php
                                            $presenceRate = $stat->total > 0 ? round((($stat->presents + $stat->retards) / $stat->total) * 100, 1) : 0;
                                        @endphp
                                        {{ $presenceRate }}%
                                    </span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $stat->presents / $stat->total * 100 }}%" 
                                         aria-valuenow="{{ $stat->presents }}" aria-valuemin="0" aria-valuemax="{{ $stat->total }}">
                                    </div>
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: {{ $stat->retards / $stat->total * 100 }}%" 
                                         aria-valuenow="{{ $stat->retards }}" aria-valuemin="0" aria-valuemax="{{ $stat->total }}">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">
                                        <span class="text-success">{{ $stat->presents }}</span> présences, 
                                        <span class="text-warning">{{ $stat->retards }}</span> retards, 
                                        <span class="text-danger">{{ $stat->absents }}</span> absences
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                            <p class="mb-0">Aucune donnée de présence disponible.</p>
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
                        <a href="{{ route('esbtp.attendances.create') }}" class="btn btn-primary">
                            <i class="fas fa-clipboard-check me-1"></i> Saisir des présences
                        </a>
                        <a href="{{ route('esbtp.attendances.rapport-form') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-alt me-1"></i> Générer un rapport
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des séances avec présences -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-list me-1"></i>
                    Séances avec présences enregistrées
                </div>
                <div class="card-body">
                    @if($seances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Classe</th>
                                        <th>Matière</th>
                                        <th>Horaire</th>
                                        <th>Statistiques</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($seances as $seance)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($seance->date)->format('d/m/Y') }}</td>
                                            <td>{{ $seance->emploiTemps->classe->name ?? 'Non définie' }}</td>
                                            <td>{{ $seance->matiere->name ?? 'Non définie' }}</td>
                                            {{-- `format('H:i')` : l'accesseur du modèle rend un Carbon daté d'aujourd'hui,
                                                 donc la découpe à cinq caractères affichait « 2026- » à l'enseignant.
                                                 Piège #14 de klassci-debugging-discipline. --}}
                                            <td>{{ optional($seance->heure_debut)->format('H:i') ?? '--:--' }} - {{ optional($seance->heure_fin)->format('H:i') ?? '--:--' }}</td>
                                            <td>
                                                @php
                                                    $attendances = $seance->attendances;
                                                    $presents = $attendances->where('status', 'present')->count();
                                                    $absents = $attendances->where('status', 'absent')->count();
                                                    $retards = $attendances->where('status', 'late')->count();
                                                    $total = $attendances->count();
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success me-1">{{ $presents }}</span>
                                                    <span class="badge bg-danger me-1">{{ $absents }}</span>
                                                    <span class="badge bg-warning me-1">{{ $retards }}</span>
                                                    <div class="progress flex-grow-1" style="height: 8px;">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                             style="width: {{ $total > 0 ? ($presents / $total * 100) : 0 }}%">
                                                        </div>
                                                        <div class="progress-bar bg-warning" role="progressbar" 
                                                             style="width: {{ $total > 0 ? ($retards / $total * 100) : 0 }}%">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {{-- La feuille de présence d'une séance s'ouvre par la saisie,
                                                     qui pré-charge les présences déjà enregistrées. --}}
                                                <a href="{{ route('esbtp.attendances.create', [
                                                        'classe_id' => $seance->emploiTemps->classe_id ?? null,
                                                        'seance_id' => $seance->id,
                                                    ]) }}" class="btn btn-sm btn-primary" title="Voir et modifier la feuille">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $seances->links() }}
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                            <p class="mb-0">Aucune séance avec présences enregistrées pour les filtres sélectionnés.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when select changes
        document.getElementById('classe').addEventListener('change', function() {
            this.form.submit();
        });
    });
</script>
@endsection 