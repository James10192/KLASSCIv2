@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Émargement des Cours</h3>
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

                    <h4>Cours du Jour</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Horaire</th>
                                    <th>Classe</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todayCourses as $course)
                                    <tr>
                                        <td>{{ $course->name }}</td>
                                        <td>{{ $course->emploiTemps->first()->seances->where('matiere_id', $course->id)->first()->heure_debut }} -
                                            {{ $course->emploiTemps->first()->seances->where('matiere_id', $course->id)->first()->heure_fin }}</td>
                                        <td>{{ $course->emploiTemps->first()->classe->name }}</td>
                                        <td>
                                            @php
                                                $attendance = $attendances->where('course_id', $course->id)->first();
                                            @endphp
                                            @if($attendance)
                                                <span class="badge bg-success">Émargé</span>
                                            @else
                                                <span class="badge bg-warning">En attente</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$attendance)
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#signModal{{ $course->id }}">
                                                    Émarger
                                                </button>

                                                <!-- Modal -->
                                                <div class="modal fade" id="signModal{{ $course->id }}" tabindex="-1" aria-labelledby="signModalLabel{{ $course->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="{{ route('esbtp.teacher-attendance.sign') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="course_id" value="{{ $course->id }}">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="signModalLabel{{ $course->id }}">Émarger pour {{ $course->name }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label for="code{{ $course->id }}" class="form-label">Code d'émargement</label>
                                                                        <input type="text" class="form-control" id="code{{ $course->id }}" name="code" required maxlength="6">
                                                                    </div>
                                                                    <input type="hidden" name="latitude" id="latitude{{ $course->id }}">
                                                                    <input type="hidden" name="longitude" id="longitude{{ $course->id }}">
                                                                    <input type="hidden" name="accuracy" id="accuracy{{ $course->id }}">
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <button type="submit" class="btn btn-primary">Confirmer</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-success">
                                                    <i class="fas fa-check"></i> Émargé à {{ $attendance->signed_at->format('H:i') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun cours aujourd'hui</td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour obtenir la géolocalisation
    function getLocation(courseId) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('latitude' + courseId).value = position.coords.latitude;
                    document.getElementById('longitude' + courseId).value = position.coords.longitude;
                    document.getElementById('accuracy' + courseId).value = position.coords.accuracy;
                },
                function(error) {
                    console.error("Erreur de géolocalisation:", error);
                    alert("Impossible d'obtenir votre position. Veuillez activer la géolocalisation.");
                }
            );
        } else {
            alert("La géolocalisation n'est pas supportée par votre navigateur.");
        }
    }

    // Ajouter des écouteurs d'événements pour chaque modal
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(button) {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-bs-target').replace('#signModal', '');
            getLocation(courseId);
        });
    });
});
</script>
@endpush
@endsection
