@extends('layouts.app')

@section('title', 'Saisie des notes : ' . $evaluation->titre . ' - ESBTP-yAKRO')

@section('page_title', 'Saisie rapide des notes')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-pen-alt me-2"></i>Saisie des notes</h1>
                <p class="header-subtitle">{{ $evaluation->titre }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.notes.saisie-rapide.pdf', $evaluation) }}" class="btn-acasi danger me-2">
                    <i class="fas fa-file-pdf"></i>Feuille PDF vierge
                </a>
                <a href="{{ route('esbtp.evaluations.pdf', $evaluation) }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-file-pdf"></i>Exporter PDF
                </a>
                <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="btn-acasi secondary me-2">
                    <i class="fas fa-eye"></i>Voir l'évaluation
                </a>
                <a href="{{ route('esbtp.evaluations.index') }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Informations sur l'évaluation -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="main-card h-100">
                    <div class="main-card-header" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.05));">
                        <div class="main-card-title">
                            <i class="fas fa-info-circle"></i>
                            Informations sur l'évaluation
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <div class="info-label">Titre</div>
                                    <div class="info-value">{{ $evaluation->titre }}</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
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
                                            $icon = $typeIcons[$evaluation->type] ?? '<i class="fas fa-file-alt color-primary me-1"></i>';
                                        @endphp
                                        {!! $icon !!} {{ ucfirst($evaluation->type) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <div class="info-label">Date</div>
                                    <div class="info-value">
                                        <i class="far fa-calendar-alt color-neutral me-1"></i>
                                        {{ date('d/m/Y', strtotime($evaluation->date_evaluation)) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <div class="info-label">Classe</div>
                                    <div class="info-value">
                                        <i class="fas fa-users color-neutral me-1"></i>
                                        {{ $evaluation->classe->name }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <div class="info-label">Matière</div>
                                    <div class="info-value">
                                        <i class="fas fa-book color-neutral me-1"></i>
                                        {{ $evaluation->matiere->name }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <div class="info-label">Barème</div>
                                    <div class="info-value">
                                        <i class="fas fa-calculator color-neutral me-1"></i>
                                        {{ $evaluation->bareme }} points
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <div class="info-label">Coefficient</div>
                                    <div class="info-value">
                                        <i class="fas fa-balance-scale color-neutral me-1"></i>
                                        {{ $evaluation->coefficient }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">État des notes</div>
                                    <div class="info-value">
                                        <span class="status-badge {{ $evaluation->notes->count() > 0 ? 'success' : 'warning' }}">
                                            <i class="fas {{ $evaluation->notes->count() > 0 ? 'fa-check-circle' : 'fa-exclamation-circle' }} me-1"></i>
                                            {{ $evaluation->notes->count() > 0 ? $evaluation->notes->count() . ' notes saisies' : 'Aucune note saisie' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                                </div>
                            </div>
                        </div>
            <div class="col-md-6">
                <div class="main-card h-100">
                    <div class="main-card-header" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));">
                        <div class="main-card-title">
                            <i class="fas fa-question-circle"></i>
                            Guide de saisie
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="alert alert-info border-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Conseils pour une saisie efficace :</strong>
                            <ul class="ps-4 mt-2 mb-0">
                                <li>Utilisez la touche <kbd>Tab</kbd> pour passer au champ suivant</li>
                                <li>Appuyez sur <kbd>Enter</kbd> pour valider une note et passer à la suivante</li>
                                <li>Double-cliquez sur un champ pour le modifier rapidement</li>
                                <li>Les champs en rouge indiquent des erreurs de saisie</li>
                            </ul>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card-moderne mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold">Notes</h6>
                                                <p class="mb-0 small">Entre 0 et {{ $evaluation->bareme }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card-moderne mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px">
                                                <i class="fas fa-user-slash"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold">Absents</h6>
                                                <p class="mb-0 small">Cochez la case</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Formulaire de saisie des notes -->
        @php
            $hasExistingNotes = $notes->isNotEmpty();
            $isCoordinateur = Auth::user()->hasRole('coordinateur');
            $isReadOnly = $hasExistingNotes && $isCoordinateur;
        @endphp

        @if($isReadOnly)
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Mode lecture seule :</strong> Des notes ont déjà été enregistrées pour cette évaluation. En tant que coordinateur, vous pouvez consulter les notes mais ne pouvez pas les modifier.
            </div>
        @endif

        <form action="{{ route('esbtp.notes.store-batch') }}" method="POST" id="notesForm">
            @csrf
            <input type="hidden" name="evaluation_id" value="{{ $evaluation->id }}">

            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-list"></i>
                        Liste des étudiants ({{ $etudiants->count() }})
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <input type="search" class="search-bar" id="searchStudent" placeholder="Rechercher un étudiant...">
                        @if(!$isReadOnly)
                            <button type="button" class="btn-acasi secondary" id="resetForm">
                                <i class="fas fa-undo"></i>Réinitialiser
                            </button>
                            <button type="submit" class="btn-acasi primary" id="saveAllBtn">
                                <i class="fas fa-save"></i>Enregistrer toutes les notes
                            </button>
                        @else
                            <span class="badge bg-info">Mode consultation uniquement</span>
                        @endif
                    </div>
                </div>
                <div class="main-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="notesTable">
                            <thead class="bg-light">
                                            <tr>
                                                <th class="border-top-0 border-bottom-0 py-3" width="5%">#</th>
                                                <th class="border-top-0 border-bottom-0 py-3" width="10%">Matricule</th>
                                                <th class="border-top-0 border-bottom-0 py-3" width="25%">Nom et Prénom</th>
                                                <th class="border-top-0 border-bottom-0 py-3 text-center" width="15%">
                                                    Note <span class="badge bg-primary rounded-pill">/ {{ $evaluation->bareme }}</span>
                                                </th>
                                                <th class="border-top-0 border-bottom-0 py-3 text-center" width="10%">
                                                    <i class="fas fa-user-slash" data-bs-toggle="tooltip" title="Absent"></i>
                                                </th>
                                                <th class="border-top-0 border-bottom-0 py-3" width="35%">Commentaire</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($etudiants as $index => $etudiant)
                                                @php
                                                    $note = $notes->where('etudiant_id', $etudiant->id)->first();
                                                    $rowClass = $note ? 'bg-light-success' : '';
                                                @endphp
                                                <tr class="{{ $rowClass }} student-row">
                                                    <td class="fw-medium">{{ $index + 1 }}</td>
                                                    <td>{{ $etudiant->matricule }}</td>
                                                    <td class="fw-medium student-name">{{ $etudiant->nom }} {{ $etudiant->prenom }}</td>
                                                    <td>
                                                        <input type="hidden" name="notes[{{ $etudiant->id }}][etudiant_id]" value="{{ $etudiant->id }}">
                                                        <div class="input-group">
                                                            <input type="number"
                                                                class="form-control note-input text-center @error('notes.' . $etudiant->id . '.valeur') is-invalid @enderror"
                                                                name="notes[{{ $etudiant->id }}][valeur]"
                                                                value="{{ old('notes.' . $etudiant->id . '.valeur', $note ? $note->valeur : '') }}"
                                                                min="0"
                                                                max="{{ $evaluation->bareme }}"
                                                                step="0.25"
                                                                {{ ($note && $note->absent) || $isReadOnly ? 'disabled' : '' }}
                                                                autocomplete="off">
                                                        </div>
                                                        @error('notes.' . $etudiant->id . '.valeur')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-flex justify-content-center">
                                                            <input class="form-check-input absent-checkbox"
                                                                type="checkbox"
                                                                name="notes[{{ $etudiant->id }}][absent]"
                                                                value="1"
                                                                role="switch"
                                                                style="width: 2.5em; height: 1.25em;"
                                                                {{ old('notes.' . $etudiant->id . '.absent', $note && $note->absent ? '1' : '') ? 'checked' : '' }}
                                                                {{ $isReadOnly ? 'disabled' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            class="form-control commentaire-input"
                                                            name="notes[{{ $etudiant->id }}][commentaire]"
                                                            value="{{ old('notes.' . $etudiant->id . '.commentaire', $note ? $note->commentaire : '') }}"
                                                            maxlength="255"
                                                            placeholder="Commentaire optionnel"
                                                            {{ $isReadOnly ? 'disabled' : '' }}>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                                            Aucun étudiant inscrit dans cette classe.
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                    </div>
                </div>
                <div class="main-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="progress" style="height: 20px;" id="progressBar">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="submit" class="btn-acasi success">
                                <i class="fas fa-save"></i>Enregistrer toutes les notes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .note-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(30, 58, 138, 0.25);
        border-color: var(--primary);
    }

    .bg-light-success {
        background-color: rgba(16, 185, 129, 0.05);
    }

    .table th {
        font-weight: 600;
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    /* Animation pour les notifications de sauvegarde */
    @keyframes fadeInOut {
        0% { opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { opacity: 0; }
    }

    .save-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: var(--space-md) var(--space-lg);
        background-color: var(--primary);
        color: white;
        border-radius: var(--radius-small);
        box-shadow: var(--shadow-elevated);
        z-index: 1050;
        animation: fadeInOut 2s forwards;
    }

    /* Style pour les cellules modifiées */
    .modified {
        background-color: rgba(245, 158, 11, 0.1);
    }

    /* Style pour les inputs dans la table */
    .table .form-control {
        border: 1px solid #dee2e6;
        border-radius: var(--radius-small);
    }

    .table .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(30, 58, 138, 0.25);
    }

    /* Style pour la ligne active */
    .table-active {
        background-color: rgba(30, 58, 138, 0.05) !important;
    }

    /* Style pour les lignes d'absents */
    .bg-light-danger {
        background-color: rgba(239, 68, 68, 0.05) !important;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialiser les tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Compter les notes déjà saisies pour le progressBar
        const updateProgressBar = function() {
            const totalStudents = {{ $etudiants->count() }};
            if (totalStudents === 0) return;

            let filledNotes = 0;
            $('.note-input').each(function() {
                if ($(this).val() !== '' || $(this).closest('tr').find('.absent-checkbox').is(':checked')) {
                    filledNotes++;
                }
            });

            const percentage = Math.round((filledNotes / totalStudents) * 100);
            $('#progressBar .progress-bar').css('width', percentage + '%');
            $('#progressBar .progress-bar').attr('aria-valuenow', percentage);
            $('#progressBar .progress-bar').text(percentage + '%');

            // Changer la couleur de la barre de progression selon le pourcentage
            const $progressBar = $('#progressBar .progress-bar');
            if (percentage < 25) {
                $progressBar.removeClass('bg-success bg-warning bg-info').addClass('bg-danger');
            } else if (percentage < 50) {
                $progressBar.removeClass('bg-success bg-danger bg-info').addClass('bg-warning');
            } else if (percentage < 75) {
                $progressBar.removeClass('bg-success bg-danger bg-warning').addClass('bg-info');
            } else {
                $progressBar.removeClass('bg-danger bg-warning bg-info').addClass('bg-success');
            }
        };

        // Mise à jour initiale de la barre de progression
        updateProgressBar();

        // Gestion des cases à cocher "Absent"
        $('.absent-checkbox').change(function() {
            const noteInput = $(this).closest('tr').find('.note-input');

            if ($(this).is(':checked')) {
                // Si "Absent" est coché, désactiver le champ de note et effacer sa valeur
                noteInput.prop('disabled', true).val('');
                $(this).closest('tr').addClass('bg-light-danger');
            } else {
                // Sinon, réactiver le champ de note
                noteInput.prop('disabled', false);
                $(this).closest('tr').removeClass('bg-light-danger');
            }

            // Marquer la ligne comme modifiée
            $(this).closest('tr').addClass('modified');

            // Mettre à jour la barre de progression
            updateProgressBar();
        });

        // Marquer les champs modifiés
        $('.note-input, .commentaire-input').on('input', function() {
            $(this).closest('tr').addClass('modified');
            updateProgressBar();
        });

        // Réinitialiser le formulaire
        $('#resetForm').click(function() {
            if (confirm('Voulez-vous vraiment réinitialiser le formulaire ? Toutes les modifications non enregistrées seront perdues.')) {
                $('.note-input').val('').prop('disabled', false);
                $('.absent-checkbox').prop('checked', false);
                $('input[name$="[commentaire]"]').val('');
                $('.modified').removeClass('modified');
                updateProgressBar();
            }
        });

        // Validation du formulaire
        $('#notesForm').submit(function() {
            let valid = true;

            $('.note-input:not(:disabled)').each(function() {
                const value = $(this).val();
                if (value !== '' && (isNaN(value) || parseFloat(value) < 0 || parseFloat(value) > {{ $evaluation->bareme }})) {
                    alert('Veuillez saisir des notes valides entre 0 et {{ $evaluation->bareme }}.');
                    $(this).focus();
                    valid = false;
                    return false;
                }
            });

            if (valid) {
                // Afficher une indication visuelle de l'envoi
                $('#saveAllBtn').html('<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement en cours...');
                $('#saveAllBtn').prop('disabled', true);
            }

            return valid;
        });

        // Filtrage des étudiants
        $('#searchStudent').on('input', function() {
            const value = $(this).val().toLowerCase();

            $('.student-row').each(function() {
                const studentName = $(this).find('.student-name').text().toLowerCase();
                const matricule = $(this).find('td:eq(1)').text().toLowerCase();

                if (studentName.includes(value) || matricule.includes(value)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Navigation au clavier entre les champs de notes
        $('.note-input').keydown(function(e) {
            if (e.which === 13) { // Touche Enter
                e.preventDefault();

                // Trouver l'index de la ligne actuelle
                const currentRow = $(this).closest('tr');
                const currentIndex = $('#notesTable tbody tr').index(currentRow);

                // Passer à la ligne suivante si elle existe
                const nextRow = $('#notesTable tbody tr').eq(currentIndex + 1);
                if (nextRow.length) {
                    nextRow.find('.note-input').focus();
                } else {
                    // Si c'est la dernière ligne, soumettre le formulaire
                    $('#notesForm').submit();
                }
            }
        });

        // Mise en surbrillance de la ligne actuelle
        $('.note-input, .commentaire-input, .absent-checkbox').focus(function() {
            $('.student-row').removeClass('table-active');
            $(this).closest('tr').addClass('table-active');
        });
    });
</script>
@endpush
