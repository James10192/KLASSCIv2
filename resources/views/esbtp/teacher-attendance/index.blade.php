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
                                        <td>{{ $course->matiere->name ?? 'Matière non définie' }}</td>
                                        <td>
                                            {{ $course->heure_debut ? \Carbon\Carbon::parse($course->heure_debut)->format('H:i') : '--:--' }} - 
                                            {{ $course->heure_fin ? \Carbon\Carbon::parse($course->heure_fin)->format('H:i') : '--:--' }}
                                        </td>
                                        <td>{{ $course->emploiTemps->classe->name ?? $course->classe->name ?? 'Classe non définie' }}</td>
                                        <td>
                                            @if($course->teacherAttendance)
                                                <span class="badge bg-success">Émargé</span>
                                            @else
                                                <span class="badge bg-warning">En attente</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$course->teacherAttendance)
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#signModal{{ $course->id }}">
                                                    Émarger
                                                </button>

                                                <!-- Modal -->
                                                <div class="modal fade" id="signModal{{ $course->id }}" tabindex="-1" aria-labelledby="signModalLabel{{ $course->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="{{ route('esbtp.teacher.attendance.sign') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="course_id" value="{{ $course->id }}">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="signModalLabel{{ $course->id }}">Émarger pour {{ $course->matiere->name ?? 'ce cours' }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label for="code{{ $course->id }}" class="form-label">Code d'émargement</label>
                                                                        <input type="text" class="form-control" id="code{{ $course->id }}" name="code" required maxlength="6" placeholder="Demandez le code au coordinateur">
                                                                        <div class="form-text">
                                                                            Saisissez le code fourni par le coordinateur académique.
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <button type="submit" class="btn btn-primary">Confirmer ma présence</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-success">
                                                    <i class="fas fa-check"></i> Émargé à {{ $course->teacherAttendance->validated_at->format('H:i') }}
                                                </span>
                                                <div class="mt-2">
                                                    <a href="{{ route('teacher.roll-call', $course->id) }}" class="btn btn-info btn-sm">
                                                        <i class="fas fa-list-check"></i> Faire l'appel
                                                    </a>
                                                </div>
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
    // Focus automatique sur le champ code lors de l'ouverture du modal
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(button) {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-bs-target').replace('#signModal', '');
            setTimeout(function() {
                const codeInput = document.getElementById('code' + courseId);
                if (codeInput) {
                    codeInput.focus();
                }
            }, 500);
        });
    });
});
</script>
@endpush
@endsection
