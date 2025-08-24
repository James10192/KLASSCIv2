@extends('layouts.app')

@section('title', 'Détails de la note - ESBTP-yAKRO')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-eye me-2"></i>Détails de la note</h1>
                <p class="header-subtitle">{{ $note->etudiant->nom }} {{ $note->etudiant->prenom }} - {{ $note->evaluation->titre }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.evaluations.show', $note->evaluation) }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-arrow-left"></i>Retour à l'évaluation
                </a>
                <a href="{{ route('esbtp.notes.edit', $note) }}" class="btn-acasi warning">
                    <i class="fas fa-edit"></i>Modifier cette note
                </a>
            </div>
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="main-card h-100">
                    <div class="main-card-header" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.05));">
                        <div class="main-card-title">
                            <i class="fas fa-file-alt"></i>
                            Informations sur l'évaluation
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="info-item">
                            <div class="info-label">Titre</div>
                            <div class="info-value">{{ $note->evaluation->titre }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Type</div>
                            <div class="info-value">
                                @php
                                    $typeIcons = [
                                        'examen' => '<i class="fas fa-file-alt color-primary me-1"></i>',
                                        'devoir' => '<i class="fas fa-pencil-alt color-success me-1"></i>',
                                        'tp' => '<i class="fas fa-flask color-warning me-1"></i>',
                                        'projet' => '<i class="fas fa-project-diagram color-accent me-1"></i>',
                                        'controle' => '<i class="fas fa-tasks color-neutral me-1"></i>',
                                        'rattrapage' => '<i class="fas fa-redo color-danger me-1"></i>',
                                    ];
                                    $icon = $typeIcons[$note->evaluation->type] ?? '<i class="fas fa-file-alt color-primary me-1"></i>';
                                @endphp
                                {!! $icon !!} {{ ucfirst($note->evaluation->type) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date</div>
                            <div class="info-value">
                                <i class="far fa-calendar-alt color-neutral me-1"></i>
                                {{ date('d/m/Y', strtotime($note->evaluation->date_evaluation)) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Classe</div>
                            <div class="info-value">
                                <i class="fas fa-users color-neutral me-1"></i>
                                {{ $note->evaluation->classe ? $note->evaluation->classe->name : 'N/A' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Matière</div>
                            <div class="info-value">
                                <i class="fas fa-book color-neutral me-1"></i>
                                {{ $note->evaluation->matiere ? $note->evaluation->matiere->name : 'N/A' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Barème</div>
                            <div class="info-value">
                                <i class="fas fa-calculator color-neutral me-1"></i>
                                {{ $note->evaluation->bareme }} points
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="main-card h-100">
                    <div class="main-card-header" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));">
                        <div class="main-card-title">
                            <i class="fas fa-user-graduate"></i>
                            Informations sur l'étudiant
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="info-item">
                            <div class="info-label">Matricule</div>
                            <div class="info-value">{{ $note->etudiant->matricule }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nom</div>
                            <div class="info-value">{{ $note->etudiant->nom }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Prénom</div>
                            <div class="info-value">{{ $note->etudiant->prenom }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Classe</div>
                            <div class="info-value">
                                <i class="fas fa-users color-neutral me-1"></i>
                                {{ $note->etudiant->classe ? $note->etudiant->classe->name : 'N/A' }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Statut</div>
                            <div class="info-value">
                                @if($note->etudiant->active)
                                    <span class="status-badge success">Actif</span>
                                @else
                                    <span class="status-badge danger">Inactif</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-card">
            <div class="main-card-header" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05));">
                <div class="main-card-title">
                    <i class="fas fa-star"></i>
                    Informations de la note
                </div>
            </div>
            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Note</div>
                            <div class="info-value">
                                @if($note->is_absent)
                                    <span class="status-badge danger">
                                        <i class="fas fa-user-slash me-1"></i>Absent
                                    </span>
                                @else
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-4 fw-bold color-primary">{{ $note->note }}/{{ $note->evaluation->bareme }}</span>
                                        <div class="text-muted small">
                                            <i class="fas fa-calculator me-1"></i>
                                            Note sur 20 : {{ number_format(($note->note * 20) / $note->evaluation->bareme, 2) }}/20
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Date de saisie</div>
                            <div class="info-value">
                                <i class="far fa-calendar-alt color-neutral me-1"></i>
                                {{ $note->created_at->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                        @if($note->updated_at && $note->updated_at != $note->created_at)
                        <div class="info-item">
                            <div class="info-label">Dernière modification</div>
                            <div class="info-value">
                                <i class="far fa-clock color-neutral me-1"></i>
                                {{ $note->updated_at->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                @if($note->commentaire)
                <div class="info-item mt-4">
                    <div class="info-label">Commentaire</div>
                    <div class="info-value">
                        <div class="p-3 bg-light rounded border-start border-4 border-primary">
                            <i class="fas fa-quote-left color-primary me-2"></i>
                            {{ $note->commentaire }}
                        </div>
                    </div>
                </div>
                @endif

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Créé par</div>
                            <div class="info-value">
                                <i class="fas fa-user color-neutral me-1"></i>
                                {{ $note->createdBy ? $note->createdBy->name : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    @if($note->updatedBy)
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Mis à jour par</div>
                            <div class="info-value">
                                <i class="fas fa-user-edit color-neutral me-1"></i>
                                {{ $note->updatedBy->name }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <button type="button" class="btn-acasi danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash"></i>Supprimer la note
                    </button>
                    <a href="{{ route('esbtp.notes.edit', $note) }}" class="btn-acasi success">
                        <i class="fas fa-edit"></i>Modifier cette note
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette note ?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cette action est irréversible et pourrait affecter les calculs de moyennes et les bulletins.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>Annuler
                </button>
                <form action="{{ route('esbtp.notes.destroy', $note) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-acasi danger">
                        <i class="fas fa-trash"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialisation des tooltips si nécessaire
        if (typeof $().tooltip === 'function') {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
</script>
@endsection
