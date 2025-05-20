@extends('layouts.app')

@section('title', 'Émargement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Formulaire d'émargement -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Signer ma présence</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form id="attendanceForm" action="{{ route('esbtp.teacher.attendance.sign') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Code d'émargement</label>
                            <div class="input-group">
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                       name="code" maxlength="6" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Signer
                                </button>
                            </div>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <input type="hidden" name="accuracy" id="accuracy">
                    </form>

                    <div id="locationStatus" class="alert alert-info d-none">
                        <i class="fas fa-location-arrow me-1"></i>
                        Récupération de votre position...
                    </div>
                </div>
            </div>

            <!-- Cours du jour -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Mes cours aujourd'hui</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Horaire</th>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todayCourses as $course)
                                    <tr>
                                        <td>{{ $course->heure_debut->format('H:i') }} - {{ $course->heure_fin->format('H:i') }}</td>
                                        <td>{{ $course->matiere->nom }}</td>
                                        <td>{{ $course->classe->nom }}</td>
                                        <td>
                                            @if($course->attendance)
                                                <span class="badge bg-{{ $course->attendance->status === 'present' ? 'success' : 'warning' }}">
                                                    {{ $course->attendance->status === 'present' ? 'À l\'heure' : 'En retard' }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Non émargé</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Aucun cours aujourd'hui</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques et derniers émargements -->
        <div class="col-md-6">
            <!-- Statistiques -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mes statistiques du mois</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h2 class="mb-1">{{ $stats['total'] }}</h2>
                                <p class="text-muted mb-0">Total</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h2 class="mb-1">{{ $stats['present'] }}</h2>
                                <p class="text-muted mb-0">À l'heure</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h2 class="mb-1">{{ $stats['late'] }}</h2>
                                <p class="text-muted mb-0">En retard</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Derniers émargements -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mes derniers émargements</h5>
                    <a href="{{ route('esbtp.teacher.attendance.history') }}" class="btn btn-primary btn-sm">
                        Voir tout
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Cours</th>
                                    <th>Statut</th>
                                    <th>Validation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAttendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->signed_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            {{ $attendance->emploiDuTemps->matiere->nom }}
                                            ({{ $attendance->emploiDuTemps->classe->nom }})
                                        </td>
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
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Aucun émargement récent</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('attendanceForm');
    const locationStatus = document.getElementById('locationStatus');
    let watchId = null;

    // Fonction pour mettre à jour les coordonnées
    function updateLocation(position) {
        document.getElementById('latitude').value = position.coords.latitude;
        document.getElementById('longitude').value = position.coords.longitude;
        document.getElementById('accuracy').value = position.coords.accuracy;
        locationStatus.classList.add('d-none');
        form.querySelector('button[type="submit"]').disabled = false;
    }

    // Fonction en cas d'erreur de géolocalisation
    function handleLocationError(error) {
        let message = 'Erreur de géolocalisation: ';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message += 'Vous devez autoriser la géolocalisation pour émarger.';
                break;
            case error.POSITION_UNAVAILABLE:
                message += 'Position non disponible.';
                break;
            case error.TIMEOUT:
                message += 'Délai d\'attente dépassé.';
                break;
            default:
                message += 'Une erreur inconnue est survenue.';
        }
        locationStatus.textContent = message;
        locationStatus.classList.remove('d-none', 'alert-info');
        locationStatus.classList.add('alert-danger');
        form.querySelector('button[type="submit"]').disabled = true;
    }

    // Démarrer la géolocalisation lors de la soumission du formulaire
    form.addEventListener('submit', function(e) {
        if (!document.getElementById('latitude').value) {
            e.preventDefault();
            locationStatus.classList.remove('d-none');
            form.querySelector('button[type="submit"]').disabled = true;

            if (navigator.geolocation) {
                watchId = navigator.geolocation.watchPosition(updateLocation, handleLocationError, {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                });
            } else {
                handleLocationError({ code: 0 });
            }
        }
    });

    // Nettoyer le watch quand le formulaire est soumis
    window.addEventListener('beforeunload', function() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
        }
    });
});
</script>
@endsection
