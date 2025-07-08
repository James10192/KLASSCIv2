@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rapport des Émargements</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateCodeModal">
                            Générer Code du Jour
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Filtres -->
                    <form action="{{ route('esbtp.teacher-attendance.report') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ request('date', date('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="teacher">Enseignant</label>
                                    <select class="form-control select2" id="teacher" name="teacher">
                                        <option value="">Tous les enseignants</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ request('teacher') == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Statut</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">Tous les statuts</option>
                                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Présent</option>
                                        <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                        <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>En retard</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrer</button>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau des émargements -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Enseignant</th>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Date</th>
                                    <th>Heure d'émargement</th>
                                    <th>Statut</th>
                                    <th>Localisation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->teacher->name }}</td>
                                        <td>{{ $attendance->course->name }}</td>
                                        <td>{{ $attendance->course->emploiTemps->first()->classe->name }}</td>
                                        <td>{{ $attendance->validated_at->format('d/m/Y') }}</td>
                                        <td>{{ $attendance->validated_at->format('H:i') }}</td>
                                        <td>
                                            @if($attendance->status === 'present')
                                                <span class="badge bg-success">Présent</span>
                                            @elseif($attendance->status === 'late')
                                                <span class="badge bg-warning">En retard</span>
                                            @else
                                                <span class="badge bg-danger">Absent</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->latitude && $attendance->longitude)
                                                <a href="https://www.google.com/maps?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank">
                                                    Voir sur la carte
                                                </a>
                                            @else
                                                Non disponible
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailsModal{{ $attendance->id }}">
                                                Détails
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Détails -->
                                    <div class="modal fade" id="detailsModal{{ $attendance->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Détails de l'émargement</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Enseignant:</strong> {{ $attendance->teacher->name }}</p>
                                                    <p><strong>Matière:</strong> {{ $attendance->course->name }}</p>
                                                    <p><strong>Classe:</strong> {{ $attendance->course->emploiTemps->first()->classe->name }}</p>
                                                    <p><strong>Date:</strong> {{ $attendance->validated_at->format('d/m/Y H:i') }}</p>
                                                    <p><strong>IP:</strong> {{ $attendance->ip_address }}</p>
                                                    <p><strong>Appareil:</strong> {{ $attendance->device_info }}</p>
                                                    @if($attendance->latitude && $attendance->longitude)
                                                        <p><strong>Précision GPS:</strong> {{ round($attendance->accuracy) }}m</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Aucun émargement trouvé</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $attendances->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Génération de Code -->
<div class="modal fade" id="generateCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.teacher-attendance.generate-code') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Générer un nouveau code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Voulez-vous générer un nouveau code d'émargement ?</p>
                    <p class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Attention : Cela désactivera l'ancien code s'il en existe un.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de Select2 pour les filtres
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap4'
        });
    }
});
</script>
@endpush
@endsection
