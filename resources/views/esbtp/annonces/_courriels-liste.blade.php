<div class="table-responsive">
    <table id="aps-email-table" class="aps-deliveries-table table align-middle">
        <thead><tr>
            <th scope="col">Destinataire</th>
            <th scope="col">État</th>
            <th scope="col">Tentatives</th>
            <th scope="col">Dernière mise à jour</th>
            <th scope="col">Motif / code</th>
            @can('annonces.email_retry')<th scope="col">Correction</th>@endcan
        </tr></thead>
        <tbody>
        @forelse($destinataires as $livraison)
            @php
                $etatTexte = match($livraison->status) {
                    'delivered' => 'Délivré', 'delayed' => 'Retardé',
                    'submitted' => 'Soumis', 'failed' => 'Échec',
                    'retrying' => 'Nouvelle tentative', 'processing' => 'En cours',
                    'awaiting_dispatch' => 'À confirmer', default => 'En attente'
                };
                $couleur = match($livraison->status) {
                    'delivered' => 'success', 'failed' => 'danger',
                    'delayed', 'retrying' => 'warning', default => 'secondary'
                };
            @endphp
            <tr data-livraison-id="{{ $livraison->id }}">
                <td class="aps-deliveries-email">{{ $livraison->email }}</td>
                <td><span class="aps-delivery-status aps-delivery-status--{{ $couleur }}">{{ $etatTexte }}</span></td>
                <td>{{ (int) $livraison->attempts }}</td>
                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($livraison->updated_at)->format('d/m/Y H:i') }}</td>
                <td class="aps-deliveries-code"><small>{{ $livraison->error_code === 'local_rate_limit' ? 'Quota local temporaire' : ($livraison->error_code ?: '—') }}</small></td>
                @can('annonces.email_retry')
                    <td>
                        @if($livraison->status === 'failed' && in_array($livraison->error_code, ['email_bounced','bounced'], true))
                            <button type="button" class="aps-btn aps-btn--white"
                                data-aps-retry-id="{{ $livraison->id }}" data-aps-retry-email="{{ $livraison->email }}"
                                aria-label="Préparer une correction de l’adresse {{ $livraison->email }}"
                                {{ config('services.mailpulse.announcement_retries_enabled', false) ? '' : 'disabled' }}>
                                <i class="fas fa-envelope" aria-hidden="true"></i>Corriger
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="{{ auth()->user()?->can('annonces.email_retry') ? 6 : 5 }}" class="text-muted py-3 text-center">
                Aucune adresse pour ce filtre. Vérifiez le traitement de la file.
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{-- Le paginateur sert UNIQUEMENT de métadonnée aux fetch AJAX.
     Aucun numéro de page visible si JavaScript fonctionne. --}}
<div id="aps-email-pages" hidden style="display:none!important" aria-hidden="true">{{ $destinataires->links() }}</div>
<noscript><nav class="aps-email-pagination-fallback" aria-label="Pages des destinataires">{{ $destinataires->links() }}</nav></noscript>
<div class="d-flex justify-content-center mt-2">
    <button type="button" id="aps-email-more" class="btn btn-outline-primary btn-sm" hidden>Charger plus de destinataires</button>
</div>
<p id="aps-email-ajax-status" class="small text-muted mt-2" role="status" aria-live="polite"></p>
