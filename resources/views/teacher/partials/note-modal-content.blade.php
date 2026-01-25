@php
    $hasStudents = $etudiants->count() > 0;
@endphp

<div class="modal-premium">
    <div class="modal-premium-header">
        <div>
            <h5 class="modal-premium-title">
                <i class="fas fa-pen-to-square me-2"></i>Saisie rapide des notes
            </h5>
            <p class="modal-premium-subtitle">{{ $evaluation->titre }} • {{ $evaluation->matiere->name ?? 'Matière' }} • {{ $evaluation->classe->name ?? 'Classe' }}</p>
        </div>
        <div class="modal-premium-badges">
            <span class="modal-pill">
                <i class="fas fa-calendar-day"></i>
                {{ $evaluation->date_evaluation ? $evaluation->date_evaluation->format('d/m/Y') : 'Date non définie' }}
            </span>
            <span class="modal-pill">
                <i class="fas fa-scale-balanced"></i>
                /{{ $evaluation->bareme ?? 20 }} pts
            </span>
            <span class="modal-pill modal-pill-neutral">
                <i class="fas fa-list-check"></i>
                {{ $notesTotal ?? 0 }} notes
            </span>
            <span class="modal-pill modal-pill-warning">
                <i class="fas fa-user-xmark"></i>
                {{ $absentsTotal ?? 0 }} absents
            </span>
        </div>
    </div>

    @if($evaluation->date_evaluation && $evaluation->date_evaluation->isFuture())
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
            <i class="fas fa-clock"></i>
            <div>
                <strong>La saisie est verrouillée.</strong>
                <div>Vous pourrez saisir les notes après la date de l'évaluation.</div>
            </div>
        </div>
    @endif

    <form id="teacherNoteForm" action="{{ route('teacher.grades.note-store', $evaluation) }}" method="POST" data-evaluation-id="{{ $evaluation->id }}">
        @csrf
        <div class="modal-premium-body">
            <div class="modal-section">
                <div class="modal-section-title">
                    <i class="fas fa-user-graduate"></i>
                    Étudiant
                </div>
                <select name="etudiant_id" id="teacher_note_etudiant" class="form-select" {{ $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture() ? 'disabled' : '' }} {{ !$hasStudents ? 'disabled' : '' }} required>
                    <option value="">-- Sélectionner un étudiant --</option>
                    @foreach($etudiants as $etudiant)
                        <option value="{{ $etudiant->id }}">{{ $etudiant->nom }} {{ $etudiant->prenoms }} ({{ $etudiant->matricule ?? 'N/A' }})</option>
                    @endforeach
                </select>
                @if(!$hasStudents)
                    <small class="text-muted">Tous les étudiants de cette classe ont déjà une note pour cette évaluation.</small>
                @endif
            </div>

            <div class="modal-grid">
                <div class="modal-section">
                    <div class="modal-section-title">
                        <i class="fas fa-star"></i>
                        Note
                    </div>
                    <div class="input-group">
                        <input type="text" name="note" class="form-control" placeholder="Ex: 15" {{ $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture() ? 'disabled' : '' }} required>
                        <span class="input-group-text">/{{ $evaluation->bareme ?? 20 }}</span>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="teacher_note_absent" name="is_absent" {{ $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture() ? 'disabled' : '' }}>
                        <label class="form-check-label" for="teacher_note_absent">Étudiant absent</label>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">
                        <i class="fas fa-comment-dots"></i>
                        Commentaire
                    </div>
                    <textarea name="commentaire" class="form-control" rows="4" placeholder="Optionnel" {{ $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture() ? 'disabled' : '' }}></textarea>
                </div>
            </div>
        </div>

        <div class="modal-premium-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary" {{ $evaluation->date_evaluation && $evaluation->date_evaluation->isFuture() ? 'disabled' : '' }} {{ !$hasStudents ? 'disabled' : '' }}>
                <i class="fas fa-check me-1"></i>Enregistrer la note
            </button>
        </div>
    </form>
</div>
