@extends('layouts.app')

@section('title', 'Émargement des Cours')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Émargement des Cours
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Cours du Jour -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Mes Cours du Jour - {{ now()->format('d/m/Y') }}</h6>
                        </div>
                        <div class="card-body">
                            @if($todayCourses->isEmpty())
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Vous n'avez pas de cours programmé aujourd'hui.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Horaire</th>
                                                <th>Matière</th>
                                                <th>Classe</th>
                                                <th>Salle</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($todayCourses as $course)
                                                <tr>
                                                    <td>{{ $course->heure_debut ? \Carbon\Carbon::parse($course->heure_debut)->format('H:i') : '--:--' }} - {{ $course->heure_fin ? \Carbon\Carbon::parse($course->heure_fin)->format('H:i') : '--:--' }}</td>
                                                    <td>{{ $course->matiere->name ?? '—' }}</td>
                                                    <td>{{ $course->classe->name ?? '—' }}</td>
                                                    <td>{{ $course->salle ?? '—' }}</td>
                                                    <td>
                                                        @if($course->teacherAttendance && $course->teacherAttendance->status === 'fait')
                                                            <span class="badge bg-success">Émargé</span>
                                                        @elseif($course->teacherAttendance && $course->teacherAttendance->status === 'not_signed')
                                                            <span class="badge bg-danger">Non signé</span>
                                                        @else
                                                            <span class="badge bg-warning">En attente</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!$course->teacherAttendance)
                                                            <button type="button" class="btn btn-primary btn-sm"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#markAttendanceModal"
                                                                    data-course-id="{{ $course->id }}">
                                                                <i class="fas fa-signature me-1"></i>
                                                                Émarger
                                                            </button>
                                                        @elseif($course->teacherAttendance && $course->teacherAttendance->status === 'not_signed')
                                                            <button type="button" class="btn btn-danger btn-sm" disabled>
                                                                <i class="fas fa-times-circle me-1"></i>
                                                                Émargement clôturé
                                                            </button>
                                                        @elseif($course->teacherAttendance && $course->teacherAttendance->status === 'fait')
                                                            <button type="button" class="btn btn-success btn-sm" disabled>
                                                                <i class="fas fa-check-circle me-1"></i>
                                                                Déjà émargé
                                                            </button>
                                                        @elseif(!$course->attendance)
                                                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                                                                <i class="fas fa-clock me-1"></i>
                                                                Pas encore disponible
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('modals')
<!-- Modal d'Émargement -->
<div class="modal fade" id="markAttendanceModal" tabindex="-1" aria-labelledby="markAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-signature me-2"></i>
                    Émarger le Cours
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('esbtp.attendance.mark') }}" id="attendanceForm">
                @csrf
                <input type="hidden" name="course_id" id="courseId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="attendanceCode" class="form-label">Code d'Émargement</label>
                        <input type="text" class="form-control" id="attendanceCode"
                               name="code" required maxlength="6"
                               placeholder="Entrez le code à 6 caractères">
                        <div class="form-text">
                            Ce code vous est fourni par l'administration.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitAttendance">
                        <i class="fas fa-check me-2"></i>
                        Confirmer ma présence
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sélection du modal et du champ caché
        const modal = document.getElementById('markAttendanceModal');
        const courseIdInput = document.getElementById('courseId');

        if (modal) {
            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (button && courseIdInput) {
                    const courseId = button.getAttribute('data-course-id');
                    courseIdInput.value = courseId;
                }
                // Focus automatique sur le champ code après l'ouverture
                setTimeout(function() {
                    const codeInput = document.getElementById('attendanceCode');
                    if (codeInput) codeInput.focus();
                }, 300);
            });
        }
    });
</script>
@endpush
@endsection
