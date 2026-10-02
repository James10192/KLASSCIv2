{{-- Fenêtre de rappel rendue à la demande du navigateur (App\Support\RappelsDuGabarit, route gabarit.rappel-du-moment). --}}
@php
    $gradingCtaUrl = $peutVoirEvaluations
        ? route('esbtp.evaluations.index')
        : route('esbtp.notes.index');
@endphp
<div class="modal fade" id="evaluationGradingReminderModal" tabindex="-1" role="dialog" aria-labelledby="evaluationGradingReminderModalLabel" aria-hidden="true" data-reminder-key="{{ $cle }}">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evaluationGradingReminderModalLabel">
                    Notes a saisir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong>{{ $resume['total'] ?? 0 }}</strong> evaluation(s) sont passees et attendent la saisie des notes.</p>
                <ul class="mb-3">
                    @if(($resume['missing_notes'] ?? 0) > 0)
                        <li><strong>{{ $resume['missing_notes'] }}</strong> sans notes saisies</li>
                    @endif
                    @if(($resume['notes_unpublished'] ?? 0) > 0)
                        <li><strong>{{ $resume['notes_unpublished'] }}</strong> notes saisies mais non publiees</li>
                    @endif
                </ul>
                @if(!empty($resume['items']))
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Evaluation</th>
                                    <th>Classe</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resume['items'] as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item['title'] ?? '—' }}</div>
                                            <small class="text-muted">{{ $item['matiere'] ?? '—' }}</small>
                                        </td>
                                        <td>{{ $item['classe'] ?? '—' }}</td>
                                        <td>{{ !empty($item['date']) ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('evaluationGradingReminderModal').dataset.reminderKey, String(Date.now()))">
                    Rappeler plus tard
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ $gradingCtaUrl }}" class="btn btn-primary">
                    <i class="fas fa-pen-to-square me-1"></i>Aller a la saisie
                </a>
            </div>
        </div>
    </div>
</div>
