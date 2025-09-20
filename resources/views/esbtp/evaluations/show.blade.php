@extends('layouts.app')

@section('title', 'Évaluation : ' . $evaluation->titre . ' - ESBTP-yAKRO')

@section('page_title', 'Détails de l\'évaluation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-file-alt me-2"></i>{{ $evaluation->titre }}
                    </h5>
                    <div>
                        @if($evaluation->isEditable())
                            <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="btn btn-warning shadow-sm me-2">
                                <i class="fas fa-edit me-2"></i>Modifier
                            </a>
                        @endif
                        <a href="{{ route('esbtp.evaluations.index') }}" class="btn btn-outline-secondary shadow-sm">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start border-success border-4">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-start border-danger border-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold text-primary mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Informations générales
                                    </h6>
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 text-muted fw-normal">Type</dt>
                                        <dd class="col-sm-8">
                                            @php
                                                $typeIcons = [
                                                    'examen' => '<i class="fas fa-file-alt text-primary me-1"></i>',
                                                    'devoir' => '<i class="fas fa-pencil-alt text-success me-1"></i>',
                                                    'tp' => '<i class="fas fa-flask text-warning me-1"></i>',
                                                    'projet' => '<i class="fas fa-project-diagram text-info me-1"></i>',
                                                    'controle' => '<i class="fas fa-tasks text-secondary me-1"></i>',
                                                    'rattrapage' => '<i class="fas fa-redo text-danger me-1"></i>',
                                                ];
                                                $icon = $typeIcons[$evaluation->type] ?? '<i class="fas fa-file-alt text-primary me-1"></i>';
                                            @endphp
                                            {!! $icon !!} {{ ucfirst($evaluation->type) }}
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Date</dt>
                                        <dd class="col-sm-8">
                                            <i class="far fa-calendar-alt text-secondary me-1"></i>
                                            {{ $evaluation->date_evaluation->format('d/m/Y') }}
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Durée</dt>
                                        <dd class="col-sm-8">
                                            <i class="far fa-clock text-secondary me-1"></i>
                                            {{ $evaluation->duree_minutes }} minutes
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Coefficient</dt>
                                        <dd class="col-sm-8">
                                            <i class="fas fa-balance-scale text-secondary me-1"></i>
                                            {{ $evaluation->coefficient }}
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Barème</dt>
                                        <dd class="col-sm-8">
                                            <i class="fas fa-calculator text-secondary me-1"></i>
                                            {{ $evaluation->bareme }} points
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold text-primary mb-3">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>Classe et matière
                                    </h6>
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 text-muted fw-normal">Classe</dt>
                                        <dd class="col-sm-8">
                                            <i class="fas fa-users text-secondary me-1"></i>
                                            {{ $evaluation->classe->name }}
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Matière</dt>
                                        <dd class="col-sm-8">
                                            <i class="fas fa-book text-secondary me-1"></i>
                                            {{ $evaluation->matiere->name }}
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Période</dt>
                                        <dd class="col-sm-8">
                                            <i class="fas fa-calendar-check text-secondary me-1"></i>
                                            {{ ucfirst($evaluation->periode) }}
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Publication</dt>
                                        <dd class="col-sm-8">
                                            @if($evaluation->is_published)
                                                <span class="badge bg-success rounded-pill px-3"><i class="fas fa-check-circle me-1"></i>Publiée</span>
                                            @else
                                                <span class="badge bg-secondary rounded-pill px-3"><i class="fas fa-eye-slash me-1"></i>Non publiée</span>
                                            @endif
                                        </dd>

                                        <dt class="col-sm-4 text-muted fw-normal">Notes</dt>
                                        <dd class="col-sm-8">
                                            @if($evaluation->notes_published)
                                                <span class="badge bg-success rounded-pill px-3"><i class="fas fa-check-circle me-1"></i>Publiées</span>
                                            @else
                                                <span class="badge bg-secondary rounded-pill px-3"><i class="fas fa-eye-slash me-1"></i>Non publiées</span>
                                            @endif
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        @if($evaluation->description)
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold text-primary mb-3">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </h6>
                                    <p class="card-text mb-0">{{ $evaluation->description }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="col-12">
                            <div class="card border-0">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 fw-bold text-primary">
                                        <i class="fas fa-list-alt me-2"></i>Notes des étudiants
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    @if($notesAnneeCourante->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Étudiant</th>
                                                        <th class="text-center">Note</th>
                                                        <th class="text-center">Sur</th>
                                                        <th class="text-center">Coefficient</th>
                                                        <th class="text-center">Note finale</th>
                                                        <th class="text-center">Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($notesAnneeCourante as $note)
                                                        <tr>
                                                            <td class="fw-medium">{{ $note->etudiant->nom_complet }}</td>
                                                            <td class="text-center">{{ $note->note }}</td>
                                                            <td class="text-center">{{ $evaluation->bareme }}</td>
                                                            <td class="text-center">{{ $evaluation->coefficient }}</td>
                                                            <td class="text-center fw-bold">{{ $note->note * $evaluation->coefficient }}</td>
                                                            <td class="text-center">
                                                                @php
                                                                    $percentage = ($note->note / $evaluation->bareme) * 100;
                                                                @endphp
                                                                @if($note->is_absent)
                                                                    <span class="badge bg-secondary">Absent</span>
                                                                @elseif($percentage >= 60)
                                                                    <span class="badge bg-success">Réussi</span>
                                                                @elseif($percentage >= 40)
                                                                    <span class="badge bg-warning text-dark">Moyen</span>
                                                                @else
                                                                    <span class="badge bg-danger">Échec</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-clipboard fa-2x mb-3 d-block"></i>
                                            Aucune note n'a encore été saisie pour cette évaluation
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-cog me-2"></i>Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('esbtp.notes.saisie-rapide', $evaluation) }}" class="btn btn-primary">
                            <i class="fas fa-pen-alt me-2"></i>Gérer les notes
                        </a>

                        @if($evaluation->isEditable())
                        <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Modifier l'évaluation
                        </a>
                        @endif

                        <form action="{{ route('esbtp.evaluations.toggle-published', $evaluation) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn {{ $evaluation->is_published ? 'btn-outline-secondary' : 'btn-success' }} w-100">
                                <i class="fas {{ $evaluation->is_published ? 'fa-eye-slash' : 'fa-eye' }} me-2"></i>
                                {{ $evaluation->is_published ? 'Masquer l\'évaluation' : 'Publier l\'évaluation' }}
                            </button>
                        </form>

                        <form action="{{ route('esbtp.evaluations.toggle-notes-published', $evaluation) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn {{ $evaluation->notes_published ? 'btn-outline-secondary' : 'btn-success' }} w-100" {{ !$evaluation->canPublishNotes() && !$evaluation->notes_published ? 'disabled' : '' }}>
                                <i class="fas {{ $evaluation->notes_published ? 'fa-eye-slash' : 'fa-eye' }} me-2"></i>
                                {{ $evaluation->notes_published ? 'Masquer les notes' : 'Publier les notes' }}
                            </button>
                        </form>

                        <a href="{{ route('esbtp.evaluations.pdf', $evaluation) }}" class="btn btn-info">
                            <i class="fas fa-file-pdf me-2"></i>Exporter en PDF
                        </a>

                        @if($evaluation->isDeletable())
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="fas fa-trash-alt me-2"></i>Supprimer l'évaluation
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @if($notesAnneeCourante->isNotEmpty())
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-chart-bar me-2"></i>Statistiques
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h6 class="mb-1 text-muted">Moyenne de classe</h6>
                        <h3 class="mb-0 fw-bold">{{ number_format($notesAnneeCourante->avg('note'), 2) }} / {{ $evaluation->bareme }}</h3>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="bg-light rounded p-3">
                                <h6 class="mb-1 text-muted small">Note maximale</h6>
                                <h5 class="mb-0 text-success">{{ number_format($notesAnneeCourante->max('note'), 2) }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-3">
                                <h6 class="mb-1 text-muted small">Note minimale</h6>
                                <h5 class="mb-0 text-danger">{{ number_format($notesAnneeCourante->min('note'), 2) }}</h5>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="bg-light rounded p-3">
                                <h6 class="mb-1 text-muted small">Nombre de notes</h6>
                                <h5 class="mb-0">{{ $notesAnneeCourante->count() }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-3">
                                <h6 class="mb-1 text-muted small">Taux de réussite</h6>
                                @php
                                    $successCount = $notesAnneeCourante->filter(function($note) use ($evaluation) {
                                        return $note->note >= ($evaluation->bareme / 2);
                                    })->count();
                                    $successRate = $notesAnneeCourante->count() > 0
                                        ? ($successCount / $notesAnneeCourante->count()) * 100
                                        : 0;
                                @endphp
                                <h5 class="mb-0 {{ $successRate >= 60 ? 'text-success' : ($successRate >= 40 ? 'text-warning' : 'text-danger') }}">
                                    {{ number_format($successRate, 1) }}%
                                </h5>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Distribution des notes</h6>
                        <div class="progress" style="height: 25px;">
                            @php
                                $ranges = [
                                    ['min' => 0, 'max' => 5, 'class' => 'bg-danger'],
                                    ['min' => 5, 'max' => 10, 'class' => 'bg-warning'],
                                    ['min' => 10, 'max' => 15, 'class' => 'bg-info'],
                                    ['min' => 15, 'max' => 20, 'class' => 'bg-success']
                                ];

                                $total = $notesAnneeCourante->count();
                            @endphp

                            @foreach($ranges as $range)
                                @php
                                    $count = $notesAnneeCourante->filter(function($note) use ($range) {
                                        return $note->note >= $range['min'] && $note->note < $range['max'];
                                    })->count();

                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                @endphp

                                @if($percentage > 0)
                                    <div class="progress-bar {{ $range['class'] }}"
                                         role="progressbar"
                                         style="width: {{ $percentage }}%"
                                         title="{{ $count }} notes entre {{ $range['min'] }} et {{ $range['max'] }}"
                                         data-bs-toggle="tooltip">
                                        {{ number_format($percentage, 0) }}%
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">0</small>
                            <small class="text-muted">5</small>
                            <small class="text-muted">10</small>
                            <small class="text-muted">15</small>
                            <small class="text-muted">20</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'évaluation "{{ $evaluation->titre }}" ?</p>
                <p class="text-danger mb-0"><strong>Attention :</strong> Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.evaluations.destroy', $evaluation) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
:root {
    --esbtp-green: #01632f;
    --esbtp-green-dark: #014a23;
    --esbtp-orange: #f29400;
}

.card {
    border: none;
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background-color: var(--esbtp-green);
    border-color: var(--esbtp-green);
}

.btn-primary:hover {
    background-color: var(--esbtp-green-dark);
    border-color: var(--esbtp-green-dark);
}

.text-primary {
    color: var(--esbtp-green) !important;
}

.bg-primary {
    background-color: var(--esbtp-green) !important;
}

.progress {
    background-color: #f8f9fa;
    border-radius: 1rem;
}

.progress-bar {
    transition: width 0.6s ease;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>
@endpush
