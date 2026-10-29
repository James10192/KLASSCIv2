{{-- Fenêtre de rappel rendue à la demande du navigateur (App\Support\RappelsDuGabarit, route gabarit.rappel-du-moment). --}}
<div class="modal fade" id="timetableReminderModal" tabindex="-1" role="dialog" aria-labelledby="timetableReminderModalLabel" aria-hidden="true" data-reminder-key="{{ $cle }}">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timetableReminderModalLabel">
                    Emplois du temps a renouveler
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Certaines classes n'ont pas d'emploi du temps valide pour la periode courante.</p>
                <ul class="mb-3">
                    @if($resume['missing'] > 0)
                        <li><strong>{{ $resume['missing'] }}</strong> classe(s) sans emploi du temps</li>
                    @endif
                    @if($resume['expired'] > 0)
                        <li><strong>{{ $resume['expired'] }}</strong> emploi(s) expire(s)</li>
                    @endif
                    @if($resume['expiring_soon'] > 0)
                        <li><strong>{{ $resume['expiring_soon'] }}</strong> emploi(s) expirant bientot</li>
                    @endif
                </ul>
                <div class="alert alert-warning mb-0">
                    <strong>Astuce :</strong> utilisez la generation rapide pour creer ou dupliquer les emplois du temps manquants.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('timetableReminderModal').dataset.reminderKey, String(Date.now()))">
                    Rappeler plus tard
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.emploi-temps.index', ['quick_generate' => 1]) }}" class="btn btn-warning">
                    <i class="fas fa-calendar-plus me-1"></i>Aller aux emplois du temps
                </a>
            </div>
        </div>
    </div>
</div>
