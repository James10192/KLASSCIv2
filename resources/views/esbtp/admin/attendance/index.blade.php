@extends('layouts.app')

@section('title', 'Gestion des émargements')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Code quotidien -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Code du jour</h5>
                    @if($dailyCode && $dailyCode->isValid())
                        <button class="btn btn-danger btn-sm cancel-code" data-code-id="{{ $dailyCode->id }}">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($dailyCode && $dailyCode->isValid())
                        <div class="text-center">
                            <h1 class="display-4 code-display">{{ $dailyCode->code }}</h1>
                            <p class="text-muted">Valide jusqu'à {{ $dailyCode->valid_until->format('H:i') }}</p>
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" style="width: {{ ($dailyCode->getRemainingValidityInMinutes() / (24*60)) * 100 }}%"></div>
                            </div>
                        </div>
                        @if($codeStats)
                            <div class="row text-center mt-3">
                                <div class="col">
                                    <h5>{{ $codeStats['total'] }}</h5>
                                    <small class="text-muted">Tentatives</small>
                                </div>
                                <div class="col">
                                    <h5>{{ $codeStats['successful'] }}</h5>
                                    <small class="text-muted">Réussies</small>
                                </div>
                                <div class="col">
                                    <h5>{{ $codeStats['success_rate'] }}%</h5>
                                    <small class="text-muted">Taux succès</small>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center">
                            <p class="mb-3">Aucun code actif</p>
                            <button class="btn btn-primary generate-code">
                                <i class="fas fa-plus"></i> Générer un code
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="col-md-8">
            <div class="row">
                <div class="col-sm-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Émargements aujourd'hui</h5>
                            <h2 class="mb-0">{{ $todayAttendances->count() }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">À l'heure</h5>
                            <h2 class="mb-0">{{ $todayAttendances->where('status', 'present')->count() }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">En retard</h5>
                            <h2 class="mb-0">{{ $todayAttendances->where('status', 'late')->count() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des émargements -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Émargements du jour</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Enseignant</th>
                            <th>Cours</th>
                            <th>Heure</th>
                            <th>Statut</th>
                            <th>Validation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($todayAttendances as $attendance)
                            <tr>
                                <td>{{ $attendance->enseignant->name }}</td>
                                <td>
                                    {{ $attendance->emploiDuTemps->matiere->nom }}
                                    ({{ $attendance->emploiDuTemps->classe->nom }})
                                </td>
                                <td>{{ $attendance->signed_at->format('H:i') }}</td>
                                <td>
                                    <span class="badge bg-{{ $attendance->status === 'present' ? 'success' : 'warning' }}">
                                        {{ $attendance->status === 'present' ? 'À l\'heure' : 'En retard' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $attendance->validation_status === 'validated' ? 'success' : ($attendance->validation_status === 'rejected' ? 'danger' : 'info') }}">
                                        {{ $attendance->validation_status === 'validated' ? 'Validé' : ($attendance->validation_status === 'rejected' ? 'Rejeté' : 'En attente') }}
                                    </span>
                                </td>
                                <td>
                                    @if($attendance->validation_status === 'pending')
                                        <button class="btn btn-success btn-sm validate-attendance" data-attendance-id="{{ $attendance->id }}">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm reject-attendance" data-attendance-id="{{ $attendance->id }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-info btn-sm view-details" data-attendance-id="{{ $attendance->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Aucun émargement aujourd'hui</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de validation -->
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validation de l'émargement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="validationForm">
                    <div class="mb-3">
                        <label class="form-label">Notes de validation</label>
                        <textarea class="form-control" name="validation_notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmValidation">Valider</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de l'émargement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="attendanceDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/teacher-attendance.js') }}"></script>
@endpush
