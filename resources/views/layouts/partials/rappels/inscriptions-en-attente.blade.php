{{-- Fenêtre de rappel rendue à la demande du navigateur (App\Support\RappelsDuGabarit, route gabarit.rappel-du-moment). --}}
<div class="modal fade" id="pendingInscriptionsReminderModal" tabindex="-1" role="dialog" aria-labelledby="pendingInscriptionsReminderModalLabel" aria-hidden="true" data-reminder-key="{{ $cle }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pendingInscriptionsReminderModalLabel">
                    Inscriptions en attente - {{ $annee->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>{{ $resume['count'] }}</strong> inscription(s) sont en attente de validation pour l'année universitaire courante.</p>
                @if(!empty($resume['by_step']))
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin: 12px 0;">
                        <div style="font-weight: 600; margin-bottom: 6px;">Répartition par étape :</div>
                        <ul style="padding-left: 20px; margin: 0;">
                            <li>Prospect : <strong>{{ $resume['by_step']['prospect'] ?? 0 }}</strong></li>
                            <li>Documents complets : <strong>{{ $resume['by_step']['documents_complets'] ?? 0 }}</strong></li>
                            <li>En validation : <strong>{{ $resume['by_step']['en_validation'] ?? 0 }}</strong></li>
                        </ul>
                    </div>
                @endif
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li>Les dossiers dont le workflow n'est pas à <strong>etudiant_cree</strong> restent en attente.</li>
                    <li>Ces étudiants ne seront pas comptés dans KLASSCI pour l'année <strong>{{ $annee->name }}</strong>.</li>
                    <li>Validez ou complétez les dossiers pour finaliser l'inscription.</li>
                </ol>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Astuce :</strong><br>
                    Le workflow doit atteindre <strong>etudiant_cree</strong> pour activer l'étudiant dans l'année courante.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="localStorage.setItem(document.getElementById('pendingInscriptionsReminderModal').dataset.reminderKey, String(Date.now()))">
                    Rappeler plus tard
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.inscriptions.administration', ['annee' => $annee->id]) }}" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Consulter les inscriptions
                </a>
            </div>
        </div>
    </div>
</div>
