@extends('layouts.app')

@section('title', 'Évaluation : ' . $evaluation->titre . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@php
    use Carbon\Carbon;

    $startAt = $evaluation->date_evaluation ? Carbon::parse($evaluation->date_evaluation) : null;
    $endAt = null;
    if ($startAt) {
        $duration = $evaluation->duree_minutes ?? 0;
        $endAt = $startAt->copy()->addMinutes($duration > 0 ? $duration : 120);
    }
    $classe = $evaluation->classe;
    $matiere = $evaluation->matiere;
    $enseignant = $evaluation->enseignant;
    $enseignantNom = $enseignant
        ? ($enseignant->name ?? $enseignant->full_name ?? $enseignant->email)
        : ($evaluation->enseignant_externe_nom ?: 'Non défini');
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-file-alt me-2"></i>{{ $evaluation->titre }}</h1>
                <p class="header-subtitle">
                    {{ $matiere->name ?? $matiere->nom ?? 'Matière non définie' }} &nbsp;•&nbsp;
                    {{ $classe->name ?? $classe->libelle ?? 'Classe non définie' }}
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
                @if($evaluation->isEditable())
                    <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="btn-acasi primary">
                        <i class="fas fa-edit"></i>Modifier
                    </a>
                @endif
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card card-moderne">
                <div class="kpi-title">Début</div>
                <div class="kpi-value">
                    <i class="far fa-calendar-alt me-2"></i>{{ $startAt?->format('d/m/Y') ?? '—' }}
                </div>
                <div class="kpi-trend">
                    <i class="far fa-clock me-1"></i>{{ $startAt?->format('H:i') ?? '--:--' }}
                </div>
            </div>

            <div class="kpi-card card-moderne">
                <div class="kpi-title">Fin estimée</div>
                <div class="kpi-value">
                    <i class="far fa-calendar-check me-2"></i>{{ $endAt?->format('d/m/Y') ?? '—' }}
                </div>
                <div class="kpi-trend">
                    <i class="far fa-clock me-1"></i>{{ $endAt?->format('H:i') ?? '--:--' }}
                </div>
            </div>

            <div class="kpi-card card-moderne">
                <div class="kpi-title">Durée</div>
                <div class="kpi-value">
                    <i class="fas fa-stopwatch me-2"></i>{{ $evaluation->duree_minutes ?? 0 }}
                </div>
                <div class="kpi-trend">
                    Minutes prévues pour l'épreuve
                </div>
            </div>

            <div class="kpi-card card-moderne">
                <div class="kpi-title">Coefficient & Barème</div>
                <div class="kpi-value">
                    <span>{{ $evaluation->coefficient }}</span>
                    <span class="divider">/</span>
                    <span>{{ $evaluation->bareme }}</span>
                </div>
                <div class="kpi-trend">
                    <i class="fas fa-scale-balanced me-1"></i>
                    Pondération de l'évaluation
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-lg">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-lg">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="details-layout">
            <div class="details-main">
                <div class="main-card">
                    <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-info-circle"></i>
                        Informations générales
                    </div>
                </div>
                <div class="main-card-body">
                    <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Type</span>
                                <span class="info-value text-capitalize">
                                    @php
                                        $typeIcons = [
                                            'examen' => 'fa-file-alt text-primary',
                                            'devoir' => 'fa-pencil-alt text-success',
                                            'tp' => 'fa-flask text-warning',
                                            'projet' => 'fa-project-diagram text-info',
                                            'controle' => 'fa-tasks text-secondary',
                                            'rattrapage' => 'fa-redo text-danger',
                                        ];
                                        $iconClass = $typeIcons[$evaluation->type] ?? 'fa-file-alt text-primary';
                                    @endphp
                                    <i class="fas {{ $iconClass }} me-2"></i>{{ ucfirst($evaluation->type) }}
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Classe</span>
                                <span class="info-value">
                                    <i class="fas fa-users me-2 text-primary"></i>
                                    {{ $classe->name ?? $classe->libelle ?? '—' }}
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Matière</span>
                                <span class="info-value">
                                    <i class="fas fa-book me-2 text-primary"></i>
                                    {{ $matiere->name ?? $matiere->nom ?? '—' }}
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Enseignant</span>
                                <span class="info-value">
                                    <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>
                                    {{ $enseignantNom }}
                                </span>
                            </div>

                            <div class="info-item dual">
                                <div>
                                    <span class="info-label">Publication</span>
                                    <span class="info-value">
                                        @if($evaluation->is_published)
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Publiée</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="fas fa-eye-slash me-1"></i>Non publiée</span>
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="info-label">Notes</span>
                                    <span class="info-value">
                                        @if($evaluation->notes_published)
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Visibles</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="fas fa-eye-slash me-1"></i>Masquées</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        @if($evaluation->description)
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-align-left"></i>
                            Description
                        </div>
                    </div>
                    <div class="main-card-body">
                        <p class="mb-0">{{ $evaluation->description }}</p>
                    </div>
                </div>
        @endif

                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-list-alt"></i>
                            Notes des étudiants
                        </div>
                    </div>
                    <div class="main-card-body p-0">
                        @if($notesAnneeCourante->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
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
                                            @php
                                                $percentage = $evaluation->bareme > 0
                                                    ? ($note->note / $evaluation->bareme) * 100
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td class="fw-medium">{{ $note->etudiant->nom_complet }}</td>
                                                <td class="text-center">{{ number_format($note->note, 2) }}</td>
                                                <td class="text-center">{{ number_format($evaluation->bareme, 2) }}</td>
                                                <td class="text-center">{{ number_format($evaluation->coefficient, 2) }}</td>
                                                <td class="text-center fw-bold">
                                                    {{ number_format($note->note * $evaluation->coefficient, 2) }}
                                                </td>
                                                <td class="text-center">
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
                            <div class="empty-state py-5 text-center">
                                <i class="fas fa-clipboard fa-2x mb-3 text-muted"></i>
                                <p class="text-muted mb-0">Aucune note n'a encore été saisie pour cette évaluation.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="details-sidebar">
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-cog"></i>
                            Actions rapides
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="actions-grid">
                            @php
                                $notesDisabled = !$evaluation->is_published;
                            @endphp
                            <a href="{{ $notesDisabled ? '#' : route('esbtp.notes.saisie-rapide', $evaluation) }}"
                               class="btn-acasi primary {{ $notesDisabled ? 'disabled' : '' }}"
                               title="{{ $notesDisabled ? 'Publiez l’évaluation pour saisir les notes' : 'Gérer les notes' }}"
                               aria-disabled="{{ $notesDisabled ? 'true' : 'false' }}"
                               tabindex="{{ $notesDisabled ? '-1' : '0' }}">
                                <i class="fas fa-pen-alt"></i> Gérer les notes
                            </a>

                            @if($evaluation->isEditable())
                                <a href="{{ route('esbtp.evaluations.edit', $evaluation) }}" class="btn-acasi secondary">
                                    <i class="fas fa-edit"></i> Modifier l'évaluation
                                </a>
                            @endif

                            <form action="{{ route('esbtp.evaluations.toggle-published', $evaluation) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-acasi {{ $evaluation->is_published ? 'secondary' : 'success' }} w-100">
                                    <i class="fas {{ $evaluation->is_published ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                    {{ $evaluation->is_published ? 'Masquer l\'évaluation' : 'Publier l\'évaluation' }}
                                </button>
                            </form>

                            <form action="{{ route('esbtp.evaluations.toggle-notes-published', $evaluation) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-acasi {{ $evaluation->notes_published ? 'secondary' : 'success' }} w-100" {{ !$evaluation->canPublishNotes() && !$evaluation->notes_published ? 'disabled' : '' }}>
                                    <i class="fas {{ $evaluation->notes_published ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                    {{ $evaluation->notes_published ? 'Masquer les notes' : 'Publier les notes' }}
                                </button>
                            </form>

                            <a href="{{ route('esbtp.evaluations.pdf', $evaluation) }}" class="btn-acasi secondary">
                                <i class="fas fa-file-pdf"></i> Exporter en PDF
                            </a>

                            @if($evaluation->isDeletable())
                                <button type="button" class="btn-acasi danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash-alt"></i> Supprimer l'évaluation
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if($notesAnneeCourante->isNotEmpty())
                    <div class="main-card">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-chart-bar"></i>
                                Statistiques
                            </div>
                        </div>
                        <div class="main-card-body">
                            <div class="stat-block text-center mb-4">
                                <h6 class="text-muted mb-1">Moyenne de classe</h6>
                                <h3 class="fw-bold mb-0">{{ number_format($notesAnneeCourante->avg('note'), 2) }} / {{ $evaluation->bareme }}</h3>
                            </div>

                            <div class="stat-row">
                                <div class="stat-card">
                                    <span class="stat-label">Note max</span>
                                    <span class="stat-value text-success">{{ number_format($notesAnneeCourante->max('note'), 2) }}</span>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-label">Note min</span>
                                    <span class="stat-value text-danger">{{ number_format($notesAnneeCourante->min('note'), 2) }}</span>
                                </div>
                            </div>

                            <div class="distribution-block mt-4">
                                <h6 class="text-muted mb-2">Répartition des notes</h6>
                                @php
                                    $ranges = [
                                        ['min' => 0, 'max' => 5, 'class' => 'bg-danger'],
                                        ['min' => 5, 'max' => 10, 'class' => 'bg-warning'],
                                        ['min' => 10, 'max' => 15, 'class' => 'bg-info'],
                                        ['min' => 15, 'max' => 20, 'class' => 'bg-success']
                                    ];
                                    $total = $notesAnneeCourante->count();
                                @endphp
                                <div class="progress" style="height: 12px;">
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
                                                 title="{{ $count }} notes entre {{ $range['min'] }} et {{ $range['max'] }}">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="d-flex justify-content-between mt-2 text-muted small">
                                    <span>0</span><span>5</span><span>10</span><span>15</span><span>20</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'évaluation « {{ $evaluation->titre }} » ?</p>
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
.details-layout {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
    gap: var(--space-xl);
}

@media (max-width: 1200px) {
    .details-layout {
        grid-template-columns: 1fr;
    }
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--space-lg);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.info-item.dual {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: var(--space-lg);
}

.info-label {
    font-size: var(--text-small);
    font-weight: 600;
    text-transform: uppercase;
    color: var(--text-secondary);
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.info-value .divider {
    color: rgba(15, 23, 42, 0.35);
    margin: 0 var(--space-xs);
}

.actions-grid {
    display: grid;
    gap: var(--space-md);
}

.actions-grid .btn-acasi i {
    margin-right: var(--space-sm);
}

.stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--space-md);
}

.stat-card {
    background: rgba(15, 23, 42, 0.04);
    border-radius: var(--radius-medium);
    padding: var(--space-md);
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: var(--space-sm);
}

.stat-label {
    font-size: var(--text-small);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: 700;
}

.distribution-block .progress {
    background: rgba(148, 163, 184, 0.2);
}

.table-modern thead tr {
    background: var(--surface);
}

.table-modern tbody tr:hover {
    background: rgba(59, 130, 246, 0.05);
}

.empty-state {
    color: var(--text-secondary);
}
</style>
@endpush
