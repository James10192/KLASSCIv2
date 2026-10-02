{{-- Fenêtre de rappel rendue à la demande du navigateur (App\Support\RappelsDuGabarit, route gabarit.rappel-du-moment). --}}
<div class="modal fade" id="evaluationPublishReminderModal" tabindex="-1" role="dialog" aria-labelledby="evaluationPublishReminderModalLabel" aria-hidden="true" data-reminder-key="{{ $cle }}">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evaluationPublishReminderModalLabel">
                    Evaluations a activer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong>{{ $resume['total'] ?? 0 }}</strong> evaluation(s) sont encore en brouillon.</p>
                <ul class="mb-3">
                    @if(($resume['overdue'] ?? 0) > 0)
                        <li><strong>{{ $resume['overdue'] }}</strong> en retard (date depassee)</li>
                    @endif
                    @if(($resume['soon'] ?? 0) > 0)
                        <li><strong>{{ $resume['soon'] }}</strong> a publier bientot</li>
                    @endif
                    @if(($resume['undated'] ?? 0) > 0)
                        <li><strong>{{ $resume['undated'] }}</strong> sans date</li>
                    @endif
                </ul>
                <div class="alert alert-info mb-0">
                    <strong>Action :</strong> publiez les evaluations pour activer la saisie des notes.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('evaluationPublishReminderModal').dataset.reminderKey, String(Date.now()))">
                    Rappeler plus tard
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.evaluations.index') }}" class="btn btn-primary">
                    <i class="fas fa-clipboard-check me-1"></i>Aller aux evaluations
                </a>
            </div>
        </div>
    </div>
</div>
